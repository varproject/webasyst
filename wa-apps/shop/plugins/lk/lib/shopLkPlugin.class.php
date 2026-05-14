<?php

class shopLkPlugin extends shopPlugin
{
    public function saveSettings($settings = array())
    {
        $settings = is_array($settings) ? $settings : array();
        $main = isset($settings['main']) && is_array($settings['main']) ? $settings['main'] : array();
        $main['enabled'] = !empty($main['enabled']) ? 1 : 0;

        $service = new shopLkPluginRouteService();
        $service->saveFromSettings($settings);

        parent::saveSettings(array(
            'main' => $main,
        ));

        shopLkPluginRouteService::resetRuntimeCache();

        return array(
            'message' => 'Настройки B2B-кабинетов сохранены.',
        );
    }

    public static function getFrontUrl($absolute = false)
    {
        $route = shopLkPluginRouteService::getCurrentRoute();
        if (!$route) {
            return wa()->getRouteUrl('shop/frontend', array(), $absolute);
        }

        return shopLkPluginUrlService::getCabinetUrl($route, $absolute);
    }
}
