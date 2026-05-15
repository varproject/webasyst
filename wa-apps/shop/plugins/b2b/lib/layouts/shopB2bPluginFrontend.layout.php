<?php

class shopB2bPluginFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->view->assign([
            // 'b2b_route'       => $route,
            // 'b2b_menu'        => $route ? shopLkPluginNavigation::getMenu($route) : array(),
            // 'b2b_cabinet_url' => $route ? shopLkPluginUrlService::getCabinetUrl($route) : wa()->getAppUrl('shop'),
            // 'b2b_user'        => wa()->getUser(),
            'b2b_static_url'  => wa('shop')->getPlugin('b2b')->getPluginStaticUrl(),
        ]);

        // waRequest::param()
    }
}
