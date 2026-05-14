<?php

class shopB2bPlugin extends shopPlugin
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

    public function salesChannelTypes($params)
    {
        return [
            'b2b' => [
                'class' => 'shopB2bPluginSalesChannel',
                'name' => _wp('B2B portal'),
                'menu_icon' => '<i class="fas fa-briefcase"></i>',
                'available' => true,
            ],
        ];
    }

    public function routingHandler($route)
    {
        if (wa()->getEnv() !== 'backend') {
            return [];
        }

        return [
            $this->id . '/' => 'backend/settings',
            $this->id . '/settings/' => 'backend/settings',
            $this->id . '/settings/save/' => 'backend/saveSettings',
        ];
    }


    // Добавляет ссылку в боковое меню магазина WA 2.0
    public function backendExtendedMenu(&$params)
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            return;
        }

        $shop_backend_url = wa('shop')->getAppUrl(null, true);

        $params['menu'][$this->id . '_channel'] = [
            'id' => $this->id . '_channel',
            'name' => _wp('B2B'),
            'icon' => '<i class="fas fa-briefcase"></i>',
            'url' => $shop_backend_url . $this->id . '/',
            'placement' => 'channels',
            'insert_after' => 'storefront',
            'userRights' => [
                'settings',
            ],
        ];
    }

    public function backendMenu($params)
    {
        $selected = (waRequest::get('plugin') == $this->id) ? 'selected' : 'no-tab';

        return [
            'aux_li' => '<li class="small float-right ' . $selected . '" id="s-plugin-debug">
                <a href="' . $this->id . '/">B2B портал</a>
            </li>',
        ];
    }
}
