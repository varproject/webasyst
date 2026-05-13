<?php

class shopBusinessPluginBackendLeadFormTochkaAction extends shopBusinessPluginBackendLeadFormAction
{
    public function execute()
    {
        $this->view->assign('links', [
            'Подключить интернет-эквайринг от Точки' => 'https://partner.tochka.com/acquiring?referer1=7706505623'
        ]);
    }
}
