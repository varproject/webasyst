<?php

class _shopB2bPlugin extends shopPlugin
{
    // Подключить пользовательские функции и модификаторы
    public function __construct($info)
    {
        parent::__construct($info);

        foreach (['functions.php', 'modifiers.php'] as $file) {
            $path = $this->path . '/lib/config/' . $file;

            if (file_exists($path)) {
                require_once $path;
            }
        }
    }


    public function salesChannelTypes(&$params)
    {
        // Если нужно ограничить регион:
        // if (!in_array($params['locality'], ['all', 'ru'])) {
        //     return [];
        // }

        return [
            [
                'id' => 'b2b',
                'name' => _wp('B2B кабинет'),
                'class' => 'shopB2bPluginSalesChannelType',
                'menu_icon' => '<i class="fas fa-briefcase"></i>',
                'available' => true,
            ],
        ];
    }

    public function salesChannels(&$params)
    {
        // Это fallback для старых/битых заказов, где sales_channel = b2b:{id},
        // но соответствующей строки уже нет в shop_sales_channel.
        // Для нормального канала, созданного через shop_sales_channel, этот хук не основной.

        $missing_ids = ifset($params, 'missing_channel_ids', []);
        if (!$missing_ids) {
            return [];
        }

        dd($missing_ids);

        $result = [];

        foreach ($missing_ids as $sales_channel_id) {
            if (!preg_match('~^b2b:(\d+)$~', (string) $sales_channel_id, $m)) {
                continue;
            }

            $result[] = [
                'id' => $sales_channel_id,
                'type' => 'storefront',
                'name' => _wp('B2B кабинет'),
                'icon_url' => wa()->getRootUrl(true) . 'wa-apps/shop/plugins/b2b/img/b2b-channel.png',
            ];
        }

        return $result;
    }

    public function orderActionCreate($params)
    {
        if (wa()->getEnv() !== 'frontend') {
            return;
        }

        $order_id = (int) ifset($params, 'order_id', 0);
        if (!$order_id) {
            return;
        }

        $channel = shopB2bPluginChannelResolver::getCurrentChannel();
        if (!$channel) {
            return;
        }

        $sales_channel = 'b2b:' . $channel['id'];

        $order_params_model = new shopOrderParamsModel();

        // Важно: третий параметр false, чтобы не стереть остальные параметры заказа.
        $order_params_model->set($order_id, [
            'sales_channel' => $sales_channel,
            'b2b_channel_id' => $channel['id'],
        ], false);
    }





    // public function routingHandler($route)
    // {
    //     if (wa()->getEnv() !== 'backend') {
    //         return [];
    //     }

    //     return [
    //         $this->id . '/' => 'backend/settings',
    //         $this->id . '/settings/' => 'backend/settings',
    //         $this->id . '/settings/save/' => 'backend/saveSettings',
    //     ];
    // }


    // Добавляет ссылку в боковое меню магазина WA 2.0
    // public function backendExtendedMenu(&$params)
    // {
    //     if (!wa()->getUser()->getRights('shop', 'settings')) {
    //         return;
    //     }

    //     $shop_backend_url = wa('shop')->getAppUrl(null, true);

    //     $params['menu'][$this->id . '_channel'] = [
    //         'id' => $this->id . '_channel',
    //         'name' => _wp('B2B'),
    //         'icon' => '<i class="fas fa-briefcase"></i>',
    //         'url' => $shop_backend_url . $this->id . '/',
    //         'placement' => 'channels',
    //         'insert_after' => 'storefront',
    //         'userRights' => [
    //             'settings',
    //         ],
    //     ];
    // }

    // public function backendMenu($params)
    // {
    //     $selected = (waRequest::get('plugin') == $this->id) ? 'selected' : 'no-tab';

    //     return [
    //         'aux_li' => '<li class="small float-right ' . $selected . '" id="s-plugin-debug">
    //             <a href="' . $this->id . '/">B2B портал</a>
    //         </li>',
    //     ];
    // }
}
