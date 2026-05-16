<?php

class shopB2bPlugin extends shopPlugin
{
    public function __construct(array $info)
    {
        parent::__construct($info);

        static $initialized = false;

        if (!$initialized) {
            $functions = $this->path . '/lib/config/functions.php';
            if (file_exists($functions)) {
                require_once $functions;
            }

            $modifiers = $this->path . '/lib/config/modifiers.php';
            if (file_exists($modifiers)) {
                require_once $modifiers;
            }

            $initialized = true;
        }
    }

    // Создание нового типа канала продаж
    public function salesChannelTypes(array &$params): array
    {
        return array(array(
            'id'        => 'b2b',
            'class'     => 'shopB2bPluginSalesChannelType',
            'name'      => 'B2B-витрина',
            'menu_icon' => '<i class="fas fa-briefcase"></i>',
            'available' => true,
        ));
    }

    // Обработка имени канала для интерфейса шопа
    public function salesChannels(array $params): array
    {
        return array(array(
            'id'   => 'storefront:b2b',
            'name' => 'B2B-кабинет',
            'type' => 'storefront',
        ));
    }

    // Заглушка для страницы настроек плагина
    public function getSettingsDisclaimerHtml()
    {
        $wa = wa('shop');
        $view = $wa->getView();
        $plugin = $wa->getPlugin('b2b');

        $view->assign([
            'b2b_static_url' => $plugin->getPluginStaticUrl(),
            'plugin_version' => preg_replace('/^(\d+\.\d+\.\d+).*/', '$1', $plugin->getVersion()),
        ]);

        return $view->fetch('file:plugins/b2b/templates/layouts/plugin-presentation.html');
    }

    public function routingHandler(array $route)
    {
        $routes = [];
        if (wa()->getEnv() === 'backend') {
            return [
                // 'channels/new/b2b/?*' => 'tttttttttt'
            ];
        } else {
            $routes = $this->routing($route);
        }

        // dd($routes);

        return $routes;
    }
}
