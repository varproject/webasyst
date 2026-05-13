<?php

class shopBusinessPluginBackendLeadFormModulBankAction extends shopBusinessPluginBackendLeadFormAction
{
    public function execute()
    {
        parent::execute();

        $this->view->assign([
            'links' => [
                'Открыть счет ИП/ООО' => 'https://modulbank.ru/rko/?utm_source=agentnet&utm_medium=partner&utm_campaign=RKO_ag-7706505623',
                'Селлерам на маркетплейсах' => 'https://modulbank.ru/marketplace/?utm_source=agentnet&utm_medium=partner&utm_campaign=RKO_ag-7706505623',
                'МодульБухгалтерия' => 'https://modulbuh.ru/?utm_source=agentnet&utm_medium=partner&utm_campaign=RKO_ag-7706505623'
            ],
            'products' => [
                'MODULKASSA' => [
                    'title' => 'МодульКасса',
                    'inn_required' => true,
                    'description' => '<b>Смарт-терминал МодульКасса</b> для торговли в офлайне: прием платежей по картам и QR-коду в точке продаж, простая фискализация платежей, интеграция с мобильной кассой Shop-Script.',
                    'enabled' => true,
                ],
                'ACCOPEN' => [
                    'title' => 'Открыть счет ИП/ООО',
                    'inn_required' => true,
                    'description' => 'Открытие и ведение расчетного счета в Модульбанке.',
                    'enabled' => true,
                ],
                'MERCHANT.IA' => [
                    'title' => 'Интернет-эквайринг',
                    'inn_required' => true,
                    'description' => 'Интернет-эквайринг от Модульбанка.',
                ],
                // 'MERCHANT.TA' => [
                //     'title' => 'Торговый эквайринг',
                //     'inn_required' => true,
                //     'description' => '',
                // ],
                // 'CLOUDPAY' => [
                //     'title' => 'Кассовые чеки',
                //     'inn_required' => true,
                //     'with_product_id' => 'ACCOPEN',
                //     'description' => '',
                // ],
                'FNS.REG' => [
                    'title' => 'Регистрация бизнеса',
                    'description' => 'Регистрация новой коммерческой организации — ИП/ООО.'
                ],
            ],
            'required_fields_by_product' => [
                'FNS.REG' =>     ['person_phone','person_firstname','person_surname','company_type'],
                'ACCOPEN' =>     ['inn', 'ogrn', 'person_phone','person_firstname','person_surname','company_type'],
                'MERCHANT.IA' => ['inn', 'ogrn', 'person_phone','person_firstname','person_surname','company_type'],
                'MERCHANT.TA' => ['inn', 'ogrn', 'person_phone','person_firstname','person_surname','company_type'],
                'MODULKASSA' =>  ['inn', 'ogrn', 'person_phone','person_firstname','person_surname','company_type'],
                'CLOUDPAY' =>    ['inn', 'ogrn', 'person_phone','person_firstname','person_surname','company_type'],
            ],
            'company_types' => [
                'IP' => 'ИП',
                'UL' => 'ООО'
            ],
        ]);
    }
}
