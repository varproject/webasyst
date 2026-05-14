<?php

class shopLkPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $service = new shopLkPluginRouteService();
        $data = $service->getSettingsRows();
        $this->view->assign(array(
            'main' => shopLkPluginRouteService::getMainSettings(),
            'storefronts' => $data['storefronts'],
            'routes' => $data['routes'],
            'sections' => shopLkPluginNavigation::getSections(),
            'copy_url' => '?plugin=lk&module=settings&action=copyRoute',
            'delete_url' => '?plugin=lk&module=settings&action=deleteRoute',
        ));
    }
}
