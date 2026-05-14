<?php

class shopLkPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        shopLkPluginSchema::ensure();

        $service = new shopLkPluginRouteService();
        $data = $service->getSettingsRows();

        $this->view->assign(array(
            'main'        => shopLkPluginRouteService::getMainSettings(),
            'storefronts' => $data['storefronts'],
            'routes'      => $data['routes'],
            'sections'    => shopLkPluginNavigation::getSections(),
            'settings_save_url' => '?module=plugins&id=lk&action=save',
            'copy_url'   => '?plugin=lk&module=settings&action=copyRoute',
            'delete_url' => '?plugin=lk&module=settings&action=deleteRoute',
        ));
    }
}
