<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2022 waResearchLab
 */

class devapiCashApi
{
    const
        ERROR_ACCESS_DENIED = 'Доступ запрещён',
        ERROR_CATEGORY_REQUIRED = 'Необходимо указать категорию доходов или расходов',
        ERROR_INCORRECT_PARAM = 'Некорректный параметр %s',
        ERROR_RECEIVER_URI_EXIST = 'Подключение с указанным URL уже существует',
        ERROR_REQUIRED_PARAM = 'Отсутствует обязательный параметр %s',
        ERROR_UNKNOWN = 'Неизвестная ошибка';

    private $socket;
    private $uri;
    public $format;

    public function __construct($format = 'JSON')
    {
        if (!in_array($format, ['JSON', 'XML'])) throw new waException(sprintf(self::ERROR_INCORRECT_PARAM, 'format'));
        $this->format = $format;
    }

    public function setReceiver($data)
    {
        foreach (['uri', 'token'] as $field) {
            if (!$field) throw new waException(sprintf(self::ERROR_REQUIRED_PARAM, $field));
        }
        $this->uri = $data['url'];
        $params = [
            'authorization' => true,
            'auth_type' => 'Bearer',
            'auth_key' => $data['token'],
            'format' => waNet::FORMAT_JSON,
            'expected_http_code' => [200, 400, 401, 403]
        ];
        $this->socket = new waNet($params);

    }

    public function getList($type, $params = [])
    {
        if (!in_array($type, ['account', 'category', 'contact', 'transaction'])) {
            throw new waException(sprintf(self::ERROR_INCORRECT_PARAM, $type));
        }
        $method = 'cash.' . $type . '.getList';
        return $this->_getData($method, $params);
    }

    public function getTransactions($params, $transaction_id = null)
    {
        $transactions = $this->_getData('transaction.getList', $params);
        if ($transaction_id) {
            if ($transactions['data']) {
                $idx = array_search($transaction_id, array_column($transactions['data'], 'id'));
                if ($idx !== false) {
                    return $transactions['data'][$idx];
                }
            }
            return false;
        }
        return $transactions['data'];
    }

    public function addTransaction($data)
    {
        return $this->_getData('transaction.create', $data, waNet::METHOD_POST);
    }

    public function updateTransaction($data)
    {
        return $this->_getData('transaction.update', $data, waNet::METHOD_POST);
    }

    public function bulkComplete($transaction_ids)
    {
        return $this->_getData('transaction.bulkComplete', $transaction_ids, waNet::METHOD_POST);
    }

    public function deleteTransaction($transaction_id, $all_repeating = false, $with_purge = true)
    {
        $result = $this->_getData('transaction.delete', ['id' => $transaction_id, 'all_repeating' => $all_repeating], waNet::METHOD_POST);
        if ($with_purge) {
            $result = $this->purgeTransaction([$transaction_id]);
        }
        return $result;
    }

    public function purgeTransaction($transaction_ids)
    {
        if (!is_array($transaction_ids)) {
            $transaction_ids = [$transaction_ids];
        }
        return $this->_getData('transaction.purge', ['ids' => $transaction_ids], waNet::METHOD_POST);
    }

    private function _getData($method, $params = [], $request_method = waNet::METHOD_GET)
    {
        if (strpos($method, 'cash.') !== 0) $method = 'cash.' . $method;
        $response = $this->socket->query($this->uri . '/' . $method, $params, $request_method);
        if ($this->socket->getResponseHeader('http_code') != 200) {
            throw new waException(ifset($response['error']) . ': ' . ifset($response['error_description']), $this->socket->getResponseHeader('http_code'));
        }
        return $response;
    }

    public static function getAccessToken($uri, $redirect_uri, $code, $client_id)
    {
        $url = $uri . '/token?' . http_build_query(['redirect_uri' => $redirect_uri]);
        $params = [
            'code' => $code,
            'client_id' => $client_id,
            'grant_type' => 'authorization_code'
        ];

        $socket = new waNet(['format' => waNet::FORMAT_JSON, 'request_format' => waNet::FORMAT_RAW]);
        $response = $socket->query($url, $params, waNet::METHOD_POST);
        return $response['access_token'];
    }

    public static function checkConnection($uri)
    {
        $socket = new waNet(['format' => waNet::FORMAT_JSON, 'expected_http_code' => 400]);
        return $socket->query($uri);
    }
}