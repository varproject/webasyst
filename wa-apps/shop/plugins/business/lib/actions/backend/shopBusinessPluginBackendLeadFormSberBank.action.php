<?php

class shopBusinessPluginBackendLeadFormSberBankAction extends shopBusinessPluginBackendLeadFormAction
{
    public function execute()
    {
        parent::execute();

        $this->view->assign([
            'links' => [
                'Регистрация бизнеса' => 'https://partners.dasreda.ru/landing/products?partnerID=070c3bf7aec8514cd3d6',
                'Счет для ИП/ООО (РКО)' => 'https://partners.dasreda.ru/landing/products?partnerID=070c3bf7aec8514cd3d6',
                'Самозанятым' => 'https://partners.dasreda.ru/landing/self-employed?erid=2RanynAMpzk&partnerID=defcf12cd855affceacb',
            ]
        ]);
    }
}
