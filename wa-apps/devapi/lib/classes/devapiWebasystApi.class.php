<?php

class devapiWebasystApi
{
    const
        WA_MAX_TRANSACTIONS = 100;

    const WA_TRANSACTION_TYPES = [
        'lease' => [
            'id' => 'lease',
            'name' => 'Аренда в облаке',
            'messages' => ['Аренда в облаке']
        ],
        'payout_bank' => [
            'id' => 'payout_bank',
            'name' => 'Выплата на счет разработчика',
            'messages' => ['Developer fee payout']
        ],
        'purchase' => [
            'id' => 'purchase',
            'name' => 'Продажа в маркете',
            'messages' => ['Начисление за', 'Royalty fee for']
        ],

        'purchase_cancel' => [
            'id' => 'purchase_cancel',
            'name' => 'Отмена заказа',
            'messages' => ['Cancel of', 'Отмена транзакции']
        ],
        'purchase_upgrade' => [
            'id' => 'purchase_upgrade',
            'name' => 'Upgrade лицензии',
            'messages' => ['Начисление за Переход']
        ],
        'subscribe' => [
            'id' => 'subscribe',
            'name' => 'Подписка',
            'messages' => []
        ],
        'subscribe_month' => [
            'id' => 'subscribe_month',
            'name' => 'Подписка на месяц',
            'messages' => ['подписка на месяц', 'monthly subscription']
        ],
        'subscribe_year' => [
            'id' => 'subscribe_year',
            'name' => 'Подписка на год',
            'messages' => ['подписка на год', 'annual subscription']
        ],
        'subscribe_cancel' => [
            'id' => 'subscribe_cancel',
            'name' => 'Отмена подписки',
            'messages' => ['Cancel of:']
        ],
        'order_payment' => [
            'id' => 'order_payment',
            'name' => 'Покупка в маркете со счета разработчика',
            'messages' => ['Оплата для заказа']
        ],
        'order_payment_cancel' => [
            'id' => 'order_payment_cancel',
            'name' => 'Отмена покупки в маркете со счета разработчика',
            'messages' => ['Оплата для заказа']
        ]
    ];

    const
        TRANSACTION_TYPE_PURCHASE = 'purchase',
        TRANSACTION_TYPE_SUBSCRIBE = 'subscribe',
        TRANSACTION_TYPE_PAYOUT = 'payout_bank',
        TRANSACTION_TYPE_ORDER_PAYMENT = 'order_payment',
        TRANSACTION_TYPE_UPGRADE = 'purchase_upgrade',
        TRANSACTION_TYPES_CANCEL = ['order_payment_cancel', 'subscribe_cancel', 'purchase_cancel'],
        TRANSACTION_TYPES_SUBSCRIBES = ['subscribe_month', 'subscribe_year'];

    const CACHE_BLOCK_TRANSACTION = 'devapi_transaction_%s';
    const DISCOUNT_TYPES = [
        'special_offer' => 'Программа скидок Webasyst',
        'product_offer' => 'Акция на продукт',
        'promocode' => 'По промокоду',
        'repeate_buy' => 'Повторная лицензия',
        'partner' => 'Партнерская программа Webasyst'
    ];

    private waNet $socket;
    private $api_key;
    private string $api_url = 'https://www.webasyst.com/my/api/developer/';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->socket = new waNet(
            array('format' => waNet::FORMAT_JSON, 'request_format' => waNet::FORMAT_RAW, 'expected_http_code' => null),
            array('X-API-Key' => $api_key)
        );
    }

    public function getTransactions($params)
    {
        return $this->query('ca/', $params);
    }

    public function getProducts()
    {
        $data = $this->query('product/');
        return $data;
    }

    public function promocodes($params, $method)
    {
        $waMethod = 'promocode/';
        if ($params && $method !== waNet::METHOD_POST) {
            $waMethod .= '?' . http_build_query($params);
            $params = [];
        }
        return $this->query($waMethod, $params, $method);
    }

    public function getOrder($params)
    {
        return $this->query('order/?id=' . (int)ifset($params['value']));
    }

    public function getResellerStore()
    {
        return $this->query('resellerstore/');
    }

    public function checkLicenses($params)
    {
        $type = ifset($params['type']) === 'domain' ? 'domain' : 'static_id';
        $items = $this->query('check/?' . $type . '=' . ifset($params['value']));
        return ['items' => $items];
    }

    public function getBalance()
    {
        return $this->query('balance/');
    }

    protected function _validateResponse($response)
    {
        if (!is_array($response) || !isset($response['status'])) {
            throw new waException(_w('Error parse WA-response'));
        }

        if ($response['status'] !== 'ok') {
            if (isset($response['errors'])) {
                if (is_string($response['errors'])) {
                    throw new waException($response['errors']);
                }
                if (is_array($response['errors'])) {
                    throw new waException($response['errors'][0]);
                }
            }
            throw new waException('Request error');
        }
        if ($this->socket->getResponseHeader('http_code') != '200') {
            throw new waException('Error ' . $this->socket->getResponseHeader('http_code'), $this->socket->getResponseHeader('http_code'));
        }
        return true;
    }

    private function query($url, $params = [], $method = waNet::METHOD_GET)
    {
        $response = $this->socket->query($this->api_url . $url, $params, $method);
        $this->_validateResponse($response);
        return ifset($response['data'], false);
    }
}