<?php

class shopBusinessPluginBackendLeadFormTBankAction extends shopBusinessPluginBackendLeadFormAction
{
    public function execute()
    {
        parent::execute();

        $product = waRequest::get('product', ifempty($this->params['product']));
        $this->view->assign('pre_fill_product', $product && is_string($product) ? $product : 'Интернет-эквайринг');

        $this->view->assign([
            'products' => [
                'Интернет-эквайринг' => [
                    'inn_required' => true,
                    'description' => 'Подключайтесь к Т-Кассе через Webasyst и получите <b>тариф интернет-эквайринга по картам <mark>2,7%</mark> с дальнейшим понижением</b>. <b>СБП — от 0,4% до 0,7%.</b> Высокая конверсия до 98%.',
                    'custom_content' => '<a href="https://www.tbank.ru/kassa/?utm_source=partners_sme&utm_medium=prt.utl&utm_campaign=business.int_acquiring.5-3AKNBMR5&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32" target="_blank" class="semibold">Регистрация в Т-Кассе со ставкой 2,7% <i class="fas fa-external-link-alt fa-xs opacity-40 custom-ml-4"></i></a>',
                ],
                'Аренда торговый эквайринг' => [
                    'title' => 'Торговый эквайринг',
                    'inn_required' => true,
                    'description' => 'Отправьте заявку на <a href=https://www.tbank.ru/business/acquiring/form/rent?utm_medium=ptr.act&utm_campaign=sme.partners&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32&utm_source=partner_rko_a_sme target=_blank>смарт-терминал aQsi 5</a> и принимайте платежи в офлайне с помощью <b>мобильной кассы Shop-Script</b>: оплата заказов на кассе картой и по QR-коду, простая фискализация платежей.',
                ],
                'РКО' => [
                    'title' => 'Открыть счет ИП/ООО',
                    'inn_required' => true,
                    'description' => 'Открывайте <a href=https://www.tbank.ru/business/rko/form?utm_medium=ptr.act&utm_campaign=sme.partners&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32&utm_source=partner_rko_a_sme target=_blank>счет в Т-Бизнесе через Webasyst</a> и получите <b>первые два месяца обслуживания бесплатно</b>.',
                ],
                'Регистрация ИП' => [
                    'description' => 'Отправьте заявку — Т-Бизнес поможет <a href=https://www.tbank.ru/business/registration-ip/form?utm_medium=ptr.act&utm_campaign=sme.partners&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32&utm_source=partner_rko_a_sme target=_blank>зарегистрироваться как ИП</a> (индивидуальный предприниматель).',
                ],
                'Регистрация ООО' => [
                    'description' => 'Отправьте заявку — Т-Бизнес поможет <a href=https://www.tbank.ru/business/registration-ooo/form?utm_medium=ptr.act&utm_campaign=sme.partners&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32&utm_source=partner_rko_a_sme target=_blank>зарегистрировать новое ООО</a>.',
                ],
            ],
            'required_fields_by_product' => [
                'Интернет-эквайринг' =>           ['phoneNumber','innOrOgrn','email','firstName','lastName'],
                'РКО' =>                          ['phoneNumber','email','innOrOgrn','firstName'],
                'Аренда торговый эквайринг' =>    ['phoneNumber','email','innOrOgrn','firstName'],
                'Регистрация ИП' =>               ['phoneNumber','email','firstName','lastName'],
                'Регистрация ООО' =>              ['phoneNumber','email','firstName','lastName'],
            ]
        ]);
    }
}
