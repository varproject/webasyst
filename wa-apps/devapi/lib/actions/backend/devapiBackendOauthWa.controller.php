<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiBackendOauthWaController extends waController
{
    public function execute()
    {
        $get = waRequest::get();
        if (ifempty($get['error'])) {
            $this->setError($get['error'] != 'access_denied' ?: devapiCashApi::ERROR_ACCESS_DENIED);
            return;
        }
        foreach (['hash', 'code'] as $field) {
            if (!ifempty($get[$field])) {
                $this->setError(sprintf(devapiCashApi::ERROR_REQUIRED_PARAM, $field));
                return;
            }
        }
        if (!$receiver = wa()->getStorage()->get($get['hash'])) {
            $this->setError('Ошибка получения данных из сессии');
            return;
        }
        $redirect_uri = wa()->getUrl(true) . '?action=oauthWa&hash=' . $get['hash'];
        try {
            $receiver['token'] = devapiCashApi::getAccessToken($receiver['url'], $redirect_uri, $get['code'], devapiHelper::getCashClientId($receiver['url']));
            $target = new devapiAccountCashTarget($receiver);
            $target->save($receiver);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return;
        }
        $html = <<<HTML
<script type='text/javascript'>opener.location.reload();window.close();</script>
HTML;
        echo $html;
    }

    private function setError($message)
    {
        $html = <<<HTML
<strong>$message</strong>
HTML;
        echo $html;
    }
}