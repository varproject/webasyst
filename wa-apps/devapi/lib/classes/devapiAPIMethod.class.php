<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiAPIMethod extends waAPIMethod
{
    protected $method = waNet::METHOD_POST;
    protected int $account_id;
    protected int $remote_id;
    protected devapiRemote $remote;

    public function __construct()
    {
        $token = waRequest::request('access_token', null, 'string');
        if (!$token) {
            if (function_exists('getallheaders')) {
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
                $token = ifset($headers, 'authorization', null);
            }
            if (!$token) {
                $token = waRequest::server('HTTP_AUTHORIZATION', null, 'string');
            }
            if ($token) {
                $token = preg_replace('~^(\s*Bearer\s+)~ui', '', $token);
                $token = trim($token);
            }
        }
        if (!$token) {
            throw new waAPIException('token_required', 'Access token is missing', 400);
        }

        $tokens_model = new waApiTokensModel();
        $data = $tokens_model->getById($token);
        if (!$data || $data['token'] != $token) {
            throw new waAPIException('invalid_token', 'Invalid access token', 401, [
                'sha256' => hash('sha256', $token),
            ]);
        }
        if ($data['expires'] && (strtotime($data['expires']) < time())) {
            throw new waAPIException('invalid_token', 'Access token has expired', 401);
        }
        $client = explode('_', $data['client_id']);
        if (count($client) !== 3) {
            throw new waAPIException('Bad client ID', 403);
        }
        $this->account_id = $client[1];
        $this->remote_id = $client[2];
        $this->remote = new devapiRemote($this->remote_id, $this->account_id);
    }

    public function post($name, $required = false)
    {
        if (!$v = waRequest::post($name)) {
            $post = json_decode(file_get_contents("php://input"), true);
            $v = ifset($post[$name]);
        }
        if ($required && $v === null) {
            throw new waAPIException('invalid_param', 'Required parameter is missing: ' . $name, 400);
        }
        return $v;
    }
}