<?php

class devapiRemote extends devapiEntity
{
    protected int $id = 0;
    protected int $account_id = 0;
    public string $start_date = '';
    protected string $name = '';
    public array $params = ['promo' => 0, 'percent' => 1];
    protected array $products = [];
    protected string $last_use = '';

    private string $token = '';
    private devapiAccountRemotesModel $model;

    public function __construct($id = 0, $account_id = 0)
    {
        $this->model = new devapiAccountRemotesModel();
        if ($id) {
            $params = array_filter(['id' => $id, 'account_id' => $account_id], function ($value) {
                return !!$value;
            });
            if ($data = $this->model->getByField($params)) {
                foreach ($this->getProperties() as $property) {
                    if (isset($data[$property->name])) {
                        $value = $data[$property->name];
                        if ($property->getType()->getName() === 'array') $value = json_decode($data[$property->name], true);
                        $this->{$property->name} = $value;
                    }
                }
                $this->token = $this->checkToken();
            } else throw new waAPIException('access_denied', 403);
        }
        if ($this->token) {
            $model = new waApiTokensModel();
            $data = $model->getByField(['client_id' => $this->getTokenClientId(), 'scope' => 'devapi']);
            if (ifset($data['last_use_datetime'])) {
                $this->last_use = wa_date('humandatetime', strtotime($data['last_use_datetime']));
            }
        }
    }

    public function save(array $data = [])
    {
        unset($data['token']);
        if (!isset($data['products'])) $data['products'] = [];
        $data = array_merge($this->jsonSerialize(), $data);
        if (!$data['id']) unset($data['id']);
        $this->validatePreSave($data);
        $db_data = $this->prepareData($data);
        $result = $this->model->insert($db_data, 1);
        if (!isset($data['id'])) {
            $db_data['id'] = $result;
            $this->id = $result;
            $this->account_id = $data['account_id'];
            $this->token = $this->checkToken();
        }
        $db_data = $this->prepareData($db_data, false);
        foreach ($db_data as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    private function checkToken()
    {
        $contact_id = (new devapiAccountModel())->getContactIdByAccountId($this->account_id);
        $model = new waApiTokensModel();
        return $model->getToken($this->getTokenClientId(), $contact_id, 'devapi');
    }

    private function prepareData($data, $encode = true)
    {
        $datum = [];
        $properties = $this->getProperties();
        foreach ($properties as $property) {
            $value = ifset($data[$property->name]);
            if ($property->getType()->getName() === 'array') {
                if ($encode) $value = json_encode($data[$property->name]);
                else {
                    if ($value) $value = json_decode($data[$property->name], true);
                    else $value = [];
                }
            }
            $datum[$property->name] = $encode ? trim($value) : $value;
        }
        return $datum;
    }

    public function getProducts()
    {
        $account = new devapiAccount($this->account_id);
        $data = $account->getProducts();
        $slugs = $this->products;
        $products = array_filter($data, function ($p) use ($slugs) {
            return in_array($p['slug'], $slugs);
        });
        return array_values($products);
    }

    private function validatePreSave($data)
    {
        $errors = [];
        if (!$data['name']) $errors[] = 'Отсутствует название';
        if (!$data['account_id']) $errors[] = 'Не указан родительский аккаунт';
        if (!$data['products']) $errors[] = 'Не добавлено ни одного продукта';
        foreach (['promo', 'percent'] as $param) {
            if (ifset($data['params'][$param]) === null) $errors[] = sprintf('Отсутствует параметр %s', $param);
        }
        if ($errors) {
            throw new waException(implode(', ', $errors));
        }
    }

    public function checkLicenses($value)
    {
        $type = 'installer';
        if (wa_is_int($value)) $type = 'order';
        elseif (strpos($value, '.') !== false) $type = 'domain';
        $account = new devapiAccount($this->account_id);
        $data = $account->checkLicense($value);
        switch ($type) {
            case 'order':
                $keys = [];
                foreach ($data['licenses'] as $key => $datum) {
                    if (!in_array($datum['product'], $this->products)) $keys[] = $key;
                }
                foreach ($keys as $key) {
                    unset($data['transactions'][$key], $data['licenses'][$key]);
                }
                if ($data['licenses']) {
                    $data['licenses'] = array_values($data['licenses']);
                    $data['transactions'] = array_values($data['transactions']);
                } else throw new waException('Заказ существует, но в нём отсутствуют продукты к которым предоставлен доступ');
                break;
            default:
                foreach ($data['items'] as $key => $item) {
                    if (!in_array($item['product'], $this->products)) unset($data['items'][$key]);
                }
                if ($data['items']) $data['items'] = array_values($data['items']);
                else throw new waException('Отсутствуют лицензии на продукты к которым предоставлен доступ');
                break;
        }
        return $data;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function delete()
    {
        $client_id = sprintf('devapi_%s_%s', $this->account_id, $this->id);
        $model = new waApiTokensModel();
        $params = [
            'contact_id' => wa()->getUser()->getId(),
            'client_id' => $client_id,
            'scope' => 'devapi'
        ];
        $model->deleteByField($params);
        $this->model->deleteById($this->id);
    }

    public function getPromocodes()
    {
        $slugs = $this->products;
        $account = new devapiAccount(($this->account_id));
        $data = $account->getPromocodes();
        $promocodes = array_filter($data, function ($code) use ($slugs) {
            return !!array_intersect($slugs, $code['products']);
        });
        return array_values($promocodes);
    }

    public function getTransactions($offset, $limit)
    {
        $model = new devapiTransactionModel();
        $wheres = [
            'account_id=i:account_id',
            'slug in (s:products)'
        ];
        if ($this->start_date) {
            $wheres[] = 'datetime>=s:start_date';
        }
        $where = implode(' and ', $wheres);
        $params = [
            'account_id' => $this->account_id,
            'start_date' => $this->start_date,
            'products' => $this->products
        ];
        $data = $model->where($where, $params)->fetchAll();
        array_walk($data, function (&$item) {
            $item['balance_after'] = $item['balance_before'] = 0;
            unset($item['id']);
        });
        unset($item);
        return $data;
    }

    public function createPromocode($code)
    {
        $description = ifset($code['description'], '') . sprintf('[devapi %s]', $this->name);
        $code['description'] = $description;
        $code['percent'] = min(ifset($code['percent'], 1), $this->params['percent']);
        $account = new devapiAccount($this->account_id);
        return $account->createPromocode($code);
    }

    public function deletePromocode($code)
    {
        $account = new devapiAccount($this->account_id);
        $account->deletePromocode($code);
    }

    private function getTokenClientId()
    {
        return sprintf('devapi_%s_%s', $this->account_id, $this->id);
    }
}