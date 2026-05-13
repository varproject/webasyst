<?php

class devapiAccountCashTarget extends devapiEntity
{
    const API_CLIENT_NAME = 'Приложение "Разработчик Webasyst" (%s)';
    const TARGET_OPTIONS_KEY = 'CASH_OPTIONS_%s';

    protected int $id = 0;
    protected int $account_id = 0;
    protected string $name = '';
    protected string $url = '';

    private string $token = '';
    private devapiAccountCashTargetsModel $model;

    public function __construct($data = [])
    {
        $this->model = new devapiAccountCashTargetsModel();
        if (wa_is_int($data) || ifset($data['id'])) {
            $id = wa_is_int($data) ? $data : $data['id'];
            $datum = $this->model->getByTargetId($id);
            if (is_array($data)) {
                if (!ifset($data['token'])) unset($data['token']);
                $data = array_merge($datum, $data);
            } else $data = $datum;
        }
        foreach ($this->getProperties() as $property) {
            if (isset($data[$property->name])) $this->{$property->name} = $data[$property->name];
        }
        if (isset($data['token'])) $this->token = $data['token'];
    }

    public function save($data)
    {
        $data = array_merge($this->jsonSerialize(), $data);
        if (!$data['token'] && $data['id']) $data['token'] = $this->token;
        if (!$data['token']) throw new waException('Отсутствует token для подключения');
        if (!$data['id']) unset($data['id']);
        $id = $this->model->insert($data, 1);
        if (!isset($data['id'])) $data['id'] = (int) $id;
        foreach ($this->getProperties() as $property) {
            if (isset($data[$property->name])) $this->{$property->name} = $data[$property->name];
        }
        $this->token = $data['token'];
    }

    public function delete()
    {
        $model = new devapiAccountCashRulesModel();
        $model->deleteByField(['target_id' => $this->id]);
        $this->model->deleteById($this->id);
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['token'] = '';
        return $data;
    }

    public function getOptions($type = null)
    {
        $options = [];
        $cacheKey = sprintf(self::TARGET_OPTIONS_KEY, $this->id);
        $cache = new waSerializeCache($cacheKey, 300, 'devapi');
        if ($cache->isCached()) $options = $cache->get();
        else {
            $socket = $this->getSocket();
            foreach ( ['account', 'category'] as $type) {
                $items = $socket->getList($type);
                switch ($type) {
                    case 'account':
                        foreach ($items as $item) {
                            $options[$type][] = ['value' => $item['id'], 'title' => $item['name']];
                        }
                        break;
                    case 'category':
                        $cats = array_filter($items, function ($item) {return $item['parent_category_id'] === null;});
                        foreach ($cats as $cat) {
                            $options['category_' . $cat['type']][$cat['id']] = ['value' => $cat['id'], 'title' => $cat['name']];
                        }
                        $cats = array_filter($items, function ($item) {return $item['parent_category_id'] !== null;});
                        foreach ($cats as $cat) {
                            $options['category_' . $cat['type']][$cat['parent_category_id']]['subcats'][] = ['value' => $cat['id'], 'title' => $cat['name']];
                        }
                        break;
                }
            }
            $cache->set($options);
        }
        return $options;
    }

    private function getSocket()
    {
        $socket = new devapiCashApi();
        $socket->setReceiver(['url' => $this->url, 'token' => $this->token]);
        return $socket;
    }
}