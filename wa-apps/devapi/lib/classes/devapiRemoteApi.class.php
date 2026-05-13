<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiRemoteApi
{
    private waNet $socket;
    private string $token;

    public function __construct($url, $token)
    {
        $this->url = $url;
        $options = [
            'request_format' => waNet::FORMAT_JSON,
            'format' => waNet::FORMAT_JSON,
            'authorization' => true,
            'auth_type' => 'Bearer',
            'auth_key' => $token
        ];
        $this->socket = new waNet($options);
    }

    public function getInfo()
    {
        return $this->query('getInfo');
    }

    public function getTransactions($params)
    {
        return $this->query('getTransactions', $params, waNet::METHOD_POST);
    }

    public function getProducts()
    {
        return $this->query('getProducts');
    }

    public function promocodes($params, $method)
    {
        if ($method === waNet::METHOD_POST) {
            return $this->query('createPromocode', ['code' => $params], waNet::METHOD_POST);
        } elseif ($method === waNet::METHOD_DELETE) {
            return $this->query('deletePromocode', $params, waNet::METHOD_POST);
        } else {
            $promos = $this->query('getPromocodes', $params);
            return $promos ?: [];
        }
    }

    public function getOrder($params)
    {
        return $this->query('getOrder', $params, waNet::METHOD_POST);
    }

    public function checkLicenses($params)
    {
        return $this->query('checkLicenses', $params, waNet::METHOD_POST);
    }

    private function query($apiMethod, $params = [], $method = waNet::METHOD_GET)
    {
        $url = $this->url . 'api.php/devapi.' . lcfirst($apiMethod);
        try {
            $response = $this->socket->query($url, $params, $method);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            try {
                $message = waUtils::jsonDecode($message, true);
                foreach (['error_description', 'error'] as $field) {
                    if (isset($message[$field])) {
                        $message = $message[$field];
                        break;
                    }
                }
            } catch (waException $e) {
            }
            throw new waException($message, $e->getCode());
        }
        if (isset($response['error'])) {
            throw new waException($response['error'] . PHP_EOL . ifset($response['error_descrition'], ''));
        }
        return $response;
    }

}