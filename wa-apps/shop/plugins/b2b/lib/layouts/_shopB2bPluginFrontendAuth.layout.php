<?php

class _shopB2bPluginFrontendAuthLayout extends waLayout
{
    protected $route;

    public function __construct($route = null)
    {
        parent::__construct();
        $this->route = $route;
    }

    public function execute()
    {
        $route = $this->route ?: shopLkPluginRouteService::getCurrentRoute();
        $config = $route ? (new shopLkPluginRouteModel())->decodeConfig($route) : array();
        $auth = isset($config['auth']) && is_array($config['auth']) ? $config['auth'] : array();
        $this->view->assign(array(
            'lk_route' => $route,
            'lk_static_url' => wa('shop')->getPlugin('lk')->getPluginStaticUrl(),
            'lk_auth_title' => trim((string) ifset($auth, 'title', 'B2B кабинет')),
            'lk_auth_text' => trim((string) ifset($auth, 'text', 'Войдите для работы с компаниями, адресами и заказами.')),
            'lk_cabinet_url' => $route ? shopLkPluginUrlService::getCabinetUrl($route) : wa()->getAppUrl('shop'),
        ));
    }
}
