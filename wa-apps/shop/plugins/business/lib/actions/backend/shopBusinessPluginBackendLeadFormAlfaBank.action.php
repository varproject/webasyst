<?php

class shopBusinessPluginBackendLeadFormAlfaBankAction extends shopBusinessPluginBackendLeadFormAction
{
    public function execute()
    {
        parent::execute();

        $path = wa()->getAppPath('plugins/business/lib/config/data/fias.php', 'shop');
        $fias = @include($path);

        $rko_required_fields = [
            'organizationInfo' => ['organizationName','inn'],
            'contactInfo[0]' => ['fullName','phoneNumber'],
            'requestInfo' => ['cityCode']
        ];

        $this->view->assign([
            'bank_name' => 'Альфа-Банк',
            'products' => [
                'LP_ACQ_E' => [
                    'title' => 'Интернет-эквайринг',
                    'with_product_id' => 'LP_RKO',
                    'description' => 'Интернет-эквайринг от Альфа-Банка <b>с пониженной ставкой по картам — <mark>2,5%</mark> навсегда, СБП — от 0,4% до 0,7%</b>.',
                    'enabled' => true,
                ],
                'LP_RKO' => [
                    'title' => 'Открыть счет ИП/ООО',
                    'description' => 'Открытие и ведение расчетного счета в Альфа-Банке.',
                    'enabled' => true,
                ],
                'LP_ACQ_TR' => [
                    'title' => 'Торговый эквайринг',
                    'with_product_id' => 'LP_RKO',
                    'description' => 'Прием платежей по картам в офлайне со ставкой от 1%.',
                ],
                'LP_AKASSA' => [
                    'title' => 'Альфа-касса',
                    'with_product_id' => 'LP_RKO',
                    'description' => 'Универсальное решение для продаж в офлайне: прием наличных, карт и оплаты QR-кодом.',
                ],
            ],
            'required_fields_by_product' => [
                'LP_RKO' =>    $rko_required_fields,
                'LP_ACQ_E' =>  $rko_required_fields,
                'LP_ACQ_TR' => $rko_required_fields,
                'LP_AKASSA' => $rko_required_fields,
            ],
            'fias' => $fias,
        ]);
    }
}
