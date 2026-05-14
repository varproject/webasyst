<?php

class shopLkPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        shopLkPluginSchema::ensure();

        $service = new shopLkPluginRouteService();
        $data = $service->getSettingsRows();

        $plugins = wa('shop')->getConfig()->getPlugins();

        $this->view->assign(array(
            'main'        => shopLkPluginRouteService::getMainSettings(),
            'storefronts' => $data['storefronts'],
            'routes'      => $data['routes'],
            'sections'    => shopLkPluginNavigation::getSections(),

            'plugin_id'   => 'lk',
            'plugin_info' => ifset($plugins, 'lk', array()),
            'plugins_count' => count($plugins),
            'app_id' => 'shop',
            'need_show_review_widget' => wa()->appExists('installer'),

            'shop_plugins_settings_template' => wa()->getAppPath('templates/actions/plugins/PluginsSettings.html', 'shop'),
            'lk_settings_content_template' => wa()->getAppPath('plugins/lk/templates/actions/settings/SettingsContent.html', 'shop'),
        ));
    }
}
