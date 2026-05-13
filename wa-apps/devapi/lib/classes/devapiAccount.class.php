<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiAccount extends devapiEntity
{
    const
        CACHE_KEY_BALANCE = 'devapi_balance_%s',
        CACHE_KEY_PRODUCTS = 'devapi_products_%s',
        CACHE_KEY_PROMOCODES = 'devapi_promocodes_%s',
        CACHE_KEY_RESELLER = 'devapi_resellers',
        CACHE_TTL = 43200,
        CACHE_TTL_PRODUCTS = 900,
        CACHE_TTL_PROMOCODES = 600;

    protected int $id = 0;
    protected string $name = '';
    protected int $contact_id;
    protected array $balance = ['balance' => 0, 'currency' => 'RUB', 'update_datetime' => null];
    protected int $is_remote = 0;
    protected array $products = [];
    protected array $cash = ['rules' => [], 'targets' => []];
    protected array $remotes = [];
    protected int $new_transactions = 0;
    protected int $last_update = 0;
    private string $token = '';
    public string $remote_url = '';
    private devapiSettings $settings;
    private array $waMethods;
    private devapiAccountModel $model;
    private devapiTransactionModel $model_transaction;

    public function __construct(int $id = 0, $data = [])
    {
        $this->model = new devapiAccountModel();
        $this->model_transaction = new devapiTransactionModel();
        if ($id) {
            unset($data['id']);
            if (!$source = $this->model->getById($id)) {
                throw new waException('Нет доступа к аккаунту');
            }
            $data = array_merge($source, $data);
        }
        foreach ($this->getProperties() as $property) {
            $name = $property->name;
            if (isset($data[$name])) $this->$name = $data[$name];
        }
        foreach (['token'] as $field) {
            if (isset($data[$field])) $this->$field = $data[$field];
        }
        if (wa()->getEnv() === 'cli') $this->contact_id = ifset($source['contact_id'], 0);
        else $this->contact_id = wa()->getUser()->getId();
        $this->settings = new devapiSettings([], wa()->getEnv() === 'cli' ? $this->contact_id : null);
        // Remotes
        $remotes = [];
        foreach ((new devapiAccountRemotesModel())->getByAccount($this->id) as $data) {
            try {
                $remotes[] = new devapiRemote($data['id'], $this->id);
            } catch (Exception $e) {
                devapiHelper::setLog($e->getMessage() . sprintf(' (account %s)', $this->name));
            }
        }
        $this->remotes = $remotes;
        // Cash
        foreach ((new devapiAccountCashTargetsModel())->getByAccountId($this->id) as $data) {
            try {
                $this->cash['targets'][] = new devapiAccountCashTarget($data);
            } catch (waException $e) {
            }
        }
        foreach ((new devapiAccountCashRulesModel())->getByAccountId($this->id) as $rule) {
            try {
                $this->cash['rules'][] = new devapiAccountCashRule($rule);
            } catch (Exception $e) {
            }
        }
        if ($this->is_remote) {
            $this->waMethods = [
                'balance' => 'getInfo',
                'check' => 'checkLicenses',
                'ca' => 'getTransactions',
                'order' => 'checkLicenses',
                'promocodes' => 'promocodes',
                'product' => 'getProducts'
            ];
        } else {
            $this->waMethods = [
                'balance' => 'getBalance',
                'check' => 'checkLicenses',
                'ca' => 'getTransactions',
                'order' => 'getOrder',
                'promocodes' => 'promocodes',
                'product' => 'getProducts',
                'resellerstore' => 'getResellerStore'
            ];
        }
    }

    public function getWaData(string $apiMethod, array $params = [], $method = waNet::METHOD_GET)
    {
        if (!isset($this->waMethods[$apiMethod])) {
            throw new waException(sprintf('Некорректный параметр %s', $apiMethod));
        }
        $socket = $this->is_remote ? new devapiRemoteApi($this->remote_url, $this->token) : new devapiWebasystApi($this->token);
        $methodName = $this->waMethods[$apiMethod];
        return $socket->$methodName($params, $method);
    }

    public function save()
    {
        foreach (['name', 'token'] as $prop) {
            if (!$this->$prop) {
                throw new waException(sprintf('Отсутствует обязательный параметр %s', $prop));
            }
        }
        $data = $this->jsonSerialize();
        if (!$data['id']) unset($data['id']);
        $data['token'] = $this->token;
        $res = $this->model->insert($data, 1);
        if ($res === false) {
            throw new waException('Не удалось сохранить данные аккаунта');
        }
        if (!ifset($data['id']) && wa_is_int($res)) $this->id = $res;
    }

    public function getSummary($params = [])
    {
        $data = [
            'offset' => ifset($params['offset'], 0),
            'limit' => ifset($params['limit'], $this->settings->limit),
            'list_type' => ifset($params['list_type'], $this->settings->list_type)
        ];
        $params = array_merge($params, $data);
        $summary = new devapiAccountSummary($this->id, $params);
        $summary->prepareData();
        $data = $summary->getData();
        if (!$this->is_remote) {
            try {
                $data['balance'] = $this->getBalance();
            } catch (Throwable $e) {
                $data['balance'] = 0;
            }
        }
        $storage = wa()->getStorage();
        $sData = $storage->get('apps-count');
        if (!ifset($sData['devapi'])) $sData['devapi'] = 0;
        $value = $sData['devapi'] - $this->new_transactions;
        $sData['devapi'] = $value > 0 ? $value : '';
        $storage->write('apps-count', $sData);
        if ($data['new_transactions'] = $this->new_transactions) $this->setNewTransactions();
        return $data;
    }

    public function updateTransactions($new_only = true)
    {
        $cache = new waSerializeCache(sprintf(devapiWebasystApi::CACHE_BLOCK_TRANSACTION, $this->contact_id), 300, 'devapi');
        if (
            $cache->isCached() ||
            ($new_only && $this->last_update + ($this->settings->refresh_rate * 60) >= time())
        ) return $this->new_transactions;
        $transactions = [];
        $done = false;
        $offset = 0;
        if (!$last_transaction = $this->model->getLastTransactionDatetime($this->id)) $new_only = false;
        while (!$done) {
            try {
                $cache->set(1);
                $data = $this->getWaData('ca', ['offset' => $offset, 'last' => devapiWebasystApi::WA_MAX_TRANSACTIONS]);
                if ($data) {
                    if ($new_only && $last_transaction) {
                        $data = array_filter($data, function ($el) use ($last_transaction) {
                            return strtotime($el['datetime']) > strtotime($last_transaction);
                        });
                    }
                    $offset += count($data);
                    $transactions = array_merge($transactions, $data);
                    if (count($data) < devapiWebasystApi::WA_MAX_TRANSACTIONS) $done = true;
                } else $done = true;
            } catch (Throwable $e) {
                devapiHelper::setLog($e->getMessage());
                $cache->delete();
                throw new waException('Не удалось получить транзакции. Подробнее см. в лог-файле devapi.error.log');
            }
        }
        $this->last_update = time();
        $this->model->updateById($this->id, ['last_update' => $this->last_update]);
        $cache->delete();
        if (!$transactions) return $this->new_transactions;
        $transactions = array_reverse($transactions);
        if ($this->is_remote) {
            array_walk($transactions, function (&$item) {
                $item['account_id'] = $this->id;
            });
        } else $transactions = $this->prepareTransactions($transactions, $new_only);
        $model = new devapiTransactionModel();
        if (!$new_only) $this->truncateTransactions();
        $model->multipleInsert($transactions);
        if ($new_only) {
            $this->new_transactions += count($transactions);
            $this->setNewTransactions($this->new_transactions);
        }
        $this->getBalance(false);
        // Notifications
        if ($transactions) {
            // Announcement
            if ($this->settings->announcement['enabled']) {
                $user_ids = $this->getUserIdsByNotificationSetting('announcement');
                if ($user_ids || $this->settings->announcement['type'] === 'all') {
                    foreach ($transactions as $transaction) {
                        $message = $this->getNotificationMessage($transaction);
                        $announce = [
                            'app_id' => 'devapi',
                            'type' => 'markdown',
                            'text' => $message,
                            'datetime' => $transaction['datetime'],
                            'access' => $user_ids ? 'limited' : 'all'
                        ];
                        if ($announce_id = (new waAnnouncementModel())->insert($announce)) {
                            $regs = [];
                            foreach ($user_ids as $user_id) {
                                $regs[] = ['group_id' => $user_id, 'announcement_id' => $announce_id];
                            }
                            (new waAnnouncementRightsModel())->multipleInsert($regs);
                        }
                    }
                }
            }

            // Telegram, Max
            foreach (['telegram', 'max'] as $messenger_id) {
                if ($this->settings->{$messenger_id}['enabled'] && $this->settings->{$messenger_id}['token']) {
                    $this->sendMessendgerNotifications($messenger_id, $transactions);
                }
            }
        }
        return $this->new_transactions;
    }

    private function sendMessendgerNotifications(string $messenger_id, array $transactions)
    {
        if ($user_ids = $this->getUserIdsByNotificationSetting($messenger_id)) {
            $chat_ids = [];
            $collection = new waContactsCollection($user_ids);
            foreach ($collection->getContacts('im,' . $messenger_id . '_id') as $datum) {
                if (isset($datum['im']) && $datum['im'] && is_array($datum['im'])) {
                    foreach ($datum['im'] as $im) {
                        if (!in_array($im['ext'], [$messenger_id . 'ID'])) continue;
                        if (wa_is_int($im['value'])) {
                            $chat_ids[] = $im['value'];
                            continue 2;
                        }
                    }
                } elseif (
                    isset($datum[$messenger_id . '_id']) &&
                    $datum[$messenger_id . '_id'] &&
                    is_array($datum[$messenger_id . '_id'])
                ) {
                    foreach ($datum[$messenger_id . '_id'] as $im) {
                        if (wa_is_int($im['value'])) $chat_ids[] = $im['value'];
                        continue 2;
                    }
                }
            }
            if ($chat_ids) {
                $messages = [];
                foreach ($transactions as $transaction) {
                    $message = $this->getNotificationMessage($transaction, PHP_EOL);
                    foreach ($chat_ids as $chat_id) {
                        $messages[] = [
                            'chat_id' => $chat_id,
                            'text' => $message
                        ];
                    }
                }
                if ($messages) {
                    try {
                        $class = 'devapi' . ucfirst($messenger_id) . 'Api';
                        $token = $this->settings->{$messenger_id}['token'];
                        $socket = new $class($token);
                        $socket->sendMessages($messages);
                    } catch (Throwable $e) {
                        devapiHelper::setLog($e->getMessage(), 'error');
                    }
                }
            }
        }
    }

    public function getProducts($with_cache = true)
    {
        $cache = $this->getWadataCache('products');
        if ($with_cache && $cache->isCached()) $data = $cache->get();
        else {
            if ($data = $this->getWaData('product')) {
                $data = devapiHelper::sortArray($data, 'name');
                $cache->set($data);
                // TODO delete
                $idx = array_search(2274, array_column($data, 'id'));
                if ($idx !== false) {
                    $logPath = wa()->getConfig()->getRootPath() . '/wa-log/devapi_product.log';
                    waFiles::write($logPath, wa_dump_helper($data[$idx]));
                }
            }
        }
        return $data;
    }

    public function getProductTypes()
    {
        $this->setProducts();
        if (!$this->products) return [];
        $types = [];
        foreach ($this->products as $product) {
            if (!$product['published_version']) continue;
            $aSlug = explode('/', $product['slug']);
            switch (count($aSlug)) {
                case 1:
                    $types[] = 'app';
                    break;
                case 3:
                    $types[] = trim($aSlug[1], 's');
                    break;
            }
        }
        return array_values(array_unique($types));
    }

    public function setProducts()
    {
        $this->products = $this->getProducts();
    }

    public function prepareTransactions($transactions, $checkByOrder = false)
    {
        $products = $this->getProducts();
        foreach ($transactions as &$t) {
            $t['discount'] = null;
            $t['account_id'] = $this->id;
            $t['slug'] = ifset($t['product']['slug']);
            $t['type'] = str_replace('-', '_', $t['type']);
            unset($t['product']);
            if (in_array($t['type'], devapiWebasystApi::TRANSACTION_TYPES_CANCEL)) {
                $slug = null;
                if ($source = $this->model_transaction->getByField(['account_id' => $this->id, 'order_id' => $t['order_id']])) {
                    $slug = $source['slug'];
                } else {
                    $sources = array_filter($transactions, function ($tr) use ($t) {
                        return $tr['order_id'] === $t['order_id'] && $tr['datetime'] !== $t['datetime'];
                    });
                    if ($sources) {
                        foreach ($sources as $source) {
                            if ($source['slug']) $slug = $source['slug'];
                            break;
                        }
                    }
                }
                if ($slug) $t['slug'] = $slug;
            } elseif ($checkByOrder && $t['order_id']) {
                $waTypes = devapiWebasystApi::WA_TRANSACTION_TYPES;
                try {
                    $order = $this->getWadata('order', ['value' => (int)$t['order_id']]);
                    if ($order['discounts']) $t['discount'] = $order['discounts'][0]['type'];
                    if (!$t['slug']) {
                        foreach ($order['transactions'] as $key => $tr) {
                            if ($tr['comment'] === $t['comment']) {
                                $t['slug'] = ifset($order['licenses'][$key]['product']);
                            }
                        }
                    }
                    if ($t['type'] === devapiWebasystApi::TRANSACTION_TYPE_PURCHASE) {
                        foreach ($waTypes[devapiWebasystApi::TRANSACTION_TYPE_UPGRADE]['messages'] as $message) {
                            if (preg_match('/' . $message . '/u', $t['comment'], $matches)) {
                                $t['type'] = devapiWebasystApi::TRANSACTION_TYPE_UPGRADE;
                                break;
                            }
                        }
                    }
                    if ($t['type'] === devapiWebasystApi::TRANSACTION_TYPE_SUBSCRIBE) {
                        foreach (devapiWebasystApi::TRANSACTION_TYPES_SUBSCRIBES as $subType) {
                            foreach ($waTypes[$subType]['messages'] as $message) {
                                if (preg_match('/' . $message . '/u', $t['comment'], $matches)) {
                                    $t['type'] = $subType;
                                    break;
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {
                }
            }
            if ($checkByOrder) {
                foreach ($this->cash['rules'] as $rule) {
                    try {
                        if (!$rule->checkTransaction($t)) break;
                    } catch (Throwable $e) {
                        devapiHelper::setLog($e->getMessage(), 'error', $t, $e);
                    }
                }
            }
        }
        unset($t);
        return $transactions;
    }

    private function setNewTransactions($value = 0)
    {
        $this->new_transactions = $value;
        $this->model->updateById($this->id, ['new_transactions' => $value]);
    }

    public function getPromocodes()
    {
        $cache = $this->getWadataCache('promocodes');
        if ($cache->isCached()) $data = $cache->get();
        else {
            if ($data = $this->getWaData('promocodes')) {
                foreach ($data as &$code) {
                    $active = true;
                    if (
                        ($code['type'] === 'single' && $code['usage']) ||
                        ($code['end_date'] && strtotime($code['end_date']) < time()) ||
                        ($code['start_date'] && strtotime($code['start_date'] > time()))
                    ) $active = false;
                    $code['active'] = $active;
                }
                unset($code);
                $cache->set($data);
            }
        }
        return $data;
    }

    public function getBalance($with_cache = true)
    {
        $cache = $this->getWadataCache('balance');
        if ($with_cache && $cache->isCached()) {
            $balance = $cache->get();
        } else {
            $balance = $this->getWaData('balance');
            $cache->set($balance);
        }
        $this->balance = $balance;
        return $balance;
    }

    public function checkLicense($value, $with_cache = true)
    {
        $type = 'installer';
        if (wa_is_int($value)) $type = 'order';
        elseif (strpos($value, '.') !== false) $type = 'domain';
        $method = $type === 'order' ? 'order' : 'check';
        $cache_key = sprintf('devapi_check_%s_%s_%s', $type, $this->id, md5($value));
        $cache = new waSerializeCache($cache_key, 3600, 'devapi');
        if ($with_cache && $cache->isCached()) $data = $cache->get();
        else {
            $data = $this->getWaData($method, ['type' => $type, 'value' => $value]);
            $data['type'] = $type;
            $data['value'] = $value;
            $cache->set($data);
        }
        return $data;
    }

    public function getMonthBalance($format = false)
    {
        $balance = $this->model_transaction->getMonthBalance($this->id);
        if ($format) {
            waCurrency::format("%2i{c}", $balance, "RUB");
        }
        return $balance;
    }

    private function getWadataCache($type)
    {
        $cache = false;
        switch ($type) {
            case 'promocodes':
                $cache = new waSerializeCache(sprintf(self::CACHE_KEY_PROMOCODES, $this->id), self::CACHE_TTL_PROMOCODES, 'devapi');
                break;
            case 'balance':
                $cache = new waSerializeCache(sprintf(self::CACHE_KEY_BALANCE, $this->id), self::CACHE_TTL, 'devapi');
                break;
            case 'products':
                $cache = new waSerializeCache(sprintf(self::CACHE_KEY_PRODUCTS, $this->id), self::CACHE_TTL_PRODUCTS, 'devapi');
                break;
            case 'reseller':
                $cache = new waSerializeCache(self::CACHE_KEY_RESELLER, self::CACHE_TTL, 'devapi');
                break;
        }
        return $cache;
    }

    public function deletePromocode($code)
    {
        $response = $this->getWaData('promocodes', ['code' => $code], waNet::METHOD_DELETE);
        $cache = $this->getWadataCache('promocodes');
        $cache->delete();
        return $response;
    }

    public function createPromocode($code)
    {
        foreach ($code as $field => $value) {
            if (!$value) unset($code[$field]);
            if (wa_is_int($value)) $code[$field] = (int)$value;
        }
        $promo = $this->getWaData('promocodes', $code, waNet::METHOD_POST);
        if (!$this->is_remote) {
            $promo = $this->getWaData('promocodes', ['code' => $code['code']]);
        }
        $cache = $this->getWadataCache('promocodes');
        $cache->delete();
        $promo['active'] = true;
        return $promo;
    }

    public function truncateTransactions()
    {
        $model = new devapiTransactionModel();
        return $model->deleteByField(['account_id' => $this->id]);
    }

    public function getCashRules()
    {
        return $this->cash['rules'];
    }

    public function getWidgetData()
    {
        $this->updateTransactions();
        return [
            'name' => $this->name,
            'balance' => $this->getBalance()
        ];
    }

    private function getUserIdsByNotificationSetting($type)
    {
        $user_ids = [];
        $setting = $this->settings->{$type};
        switch ($setting['type']) {
            case 'me':
                $user_ids = [$this->contact_id];
                break;
            case 'all':
                $user_ids = (new waContactModel())->select('id')->where('is_user=1')->fetchAll(null, true);
                break;
            case 'groups':
                if ($type === 'announcement') {
                    $user_ids = $setting['values'];
                } else {
                    $user_ids = array_values((new waUserGroupsModel())->getContactIds($setting['values']));
                }
                break;
            case 'users':
                $user_ids = $setting['values'];
                break;
            default:
                devapiHelper::setLog(sprintf('Некорректные настройки уведомлений (%s)', $type));
                break;
        }
        if ($user_ids && $type === 'announcement' && $setting['type'] !== 'groups') {
            foreach ($user_ids as &$user_id) {
                $user_id = $user_id * -1;
            }
            unset($user_id);
        }
        return $user_ids;
    }

    public function getResellers()
    {
        $cache = $this->getWadataCache('reseller');
        if ($cache->isCached()) $data = $cache->get();
        else {
            if ($data = $this->getWaData('resellerstore')) {
                $data = devapiHelper::sortArray($data, 'product_name');
                foreach ($data as &$item) {
                    $item['url'] = null;
                    $slug = explode('/', $item['slug']);
                    switch ($item['type']) {
                        case 'APP':
                            $item['url'] = 'https://www.webasyst.ru/store/app/' . $item['slug'] . '/';
                            break;
                        case 'THEME':
                            $item['url'] = 'https://www.webasyst.ru/store/theme/' . array_pop($slug) . '/';
                            break;
                        case 'PLUGIN':
                            if ($slug[0] === 'wa-plugins') {
                                unset ($slug[0]);
                                $item['url'] = 'https://www.webasyst.ru/store/plugin/' . implode('/', $slug) . '/';
                            } else {
                                $item['url'] = 'https://www.webasyst.ru/store/plugin/' . array_shift($slug) . '/' . array_pop($slug) . '/';
                            }
                            break;
                        case 'WIDGET':
                            $item['url'] = 'https://www.webasyst.ru/store/widget/' . array_shift($slug) . '/' . array_pop($slug) . '/';
                            break;
                    }
                }
                unset($item);
                $cache->set($data);
            }
        }
        return $data;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isRemote()
    {
        return !!$this->is_remote;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['cash']['targets'] = array_values($data['cash']['targets']);
        return $data;
    }

    private function getNotificationMessage($transaction, $separator = '<br>')
    {
        $amount = wa_currency($transaction['amount'], 'RUB');
        $message = <<<HTML
<b>{$this->name}</b> {$separator}$amount {$transaction['comment']}
HTML;
        return $message;
    }
}