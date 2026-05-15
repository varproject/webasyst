<?php

class shopLkPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $service = new shopLkPluginRouteService();
        $data = $service->getSettingsRows();
        $this->view->assign(array(
            'main' => shopLkPluginRouteService::getMainSettings(),
            'storefront_rows' => $data['storefronts'],
            'copy_sources' => $data['copy_sources'],
            'sections' => shopLkPluginNavigation::getSections(),
            'is_ui2' => wa()->whichUI('shop') == '2.0',
        ));
    }
}
