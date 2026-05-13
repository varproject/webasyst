<?php

class devapiMaxApi
{
    private $token;

    public function __construct($token)
    {
        if (!$token) {
            throw new waException(_wp('Отсутствует токен'));
        }
        $this->token = $token;
    }

    public function sendMessages(array $messages)
    {
        foreach ($messages as &$message) {
            $user_id = $message['chat_id'];
            unset($message['chat_id']);
            $message['sending'] = $this->sendMessage($user_id, $message);
        }
        unset($message);
        return $messages;
    }

    public function sendMessage(int $user_id, array $message)
    {
        $message['format'] = 'html';
        $params = [
            'disable_link_preview' => 'true',
            'user_id' => $user_id
        ];
        $url = 'https://platform-api.max.ru/messages?' . http_build_query($params);
        return $this->query($url, $message);
    }

    private function query(string $url, ?array $params = [], string $method = waNet::METHOD_POST)
    {
        $socket = new waNet(['format' => waNet::FORMAT_JSON], ['Authorization' => $this->token]);
        $socket->query($url, $params, $method);
        return $socket->getResponse();
    }
}