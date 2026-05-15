<?php

class shopLkPluginFrontendLayout extends waLayout
{
    protected $context;

    public function __construct($context = null)
    {
        parent::__construct();
        $this->context = $context;
    }

    public function execute()
    {
        $route = $this->context ? $this->context->getRoute() : shopLkPluginRouteService::getCurrentRoute();
        $this->view->assign(array(
            'lk_route' => $route,
            'lk_context' => $this->context,
            'lk_menu' => $route ? shopLkPluginNavigation::getMenu($route) : array(),
            'lk_cabinet_url' => $route ? shopLkPluginUrlService::getCabinetUrl($route) : wa()->getAppUrl('shop'),
            'lk_user' => wa()->getUser(),
            'lk_static_url' => wa('shop')->getPlugin('lk')->getPluginStaticUrl(),
        ));
    }
}
