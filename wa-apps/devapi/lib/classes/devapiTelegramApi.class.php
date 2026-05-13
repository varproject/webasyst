<?php
class devapiTelegramApi
{
    private $token;

    public function __construct($token)
    {
        if (!$token) {
            throw new waException(_wp('Отсутствует токен'));
        }
        $this->token = $token;
    }

    public function sendMessages($messages)
    {
        foreach ($messages as &$message) {
            $message['sending'] = $this->sendMessage($message);
        }
        unset($message);
        return $messages;
    }

    public function sendMessage($message)
    {
        try {
            $result = $this->goData('sendMessage', $message);
        } catch (waException $e) {
            $result = false;
        }
        return $result;
    }

    private function goData($method, $data = [])
    {

        $params = ['disable_web_page_preview' => true];
        $data = array_merge($data, $params);
        if (!isset($data['parse_mode'])) $data['parse_mode'] = 'HTML';
        $response = $this->_goData($method, $data);

        if ($response['ok']) {
            return $response['result'];
        } else {
            devapiHelper::setLog('', 'error', ['data' => $data, 'response' => $response]);
            throw new waException($response['description'], $response['error_code']);
        }
    }

    private function _goData($method, $data = [])
    {
        $socket = new waNet(array(
            'format' => waNet::FORMAT_JSON,
            'expected_http_code' => [200, 400]
        ));
        $url = 'https://api.telegram.org/bot' . $this->token . '/' . $method;
        $socket->query($url, $data, waNet::METHOD_POST);
        $response = $socket->getResponse();
        return $response;
    }
}