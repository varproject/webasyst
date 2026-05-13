<?php
class shopBusinessPluginBackendLeadFormRobokassaAction extends shopBusinessPluginBackendLeadFormAction
{
    public function execute()
    {
        parent::execute();

        $this->view->assign('links', [
            'Регистрация в Робокассе со ставкой 2,7%' => 'https://partner.robokassa.ru/Reg/Register?PromoCode=01webasyst&culture=ru',
        ]);
    }
}
