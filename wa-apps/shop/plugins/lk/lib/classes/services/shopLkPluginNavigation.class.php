<?php

final class shopLkPluginNavigation
{
    public static function getSections()
    {
        return array(
            'companies' => array('id' => 'companies', 'name' => 'Компании', 'path' => 'companies', 'always_enabled' => 1),
            'addresses' => array('id' => 'addresses', 'name' => 'Адреса', 'path' => 'addresses'),
            'payments' => array('id' => 'payments', 'name' => 'Типы оплаты', 'path' => 'payments'),
            'orders' => array('id' => 'orders', 'name' => 'Заказы', 'path' => 'orders'),
        );
    }

    public static function getMenu(array $route)
    {
        $config = (new shopLkPluginRouteModel())->decodeConfig($route);
        $settings = ifset($config, 'sections', array());
        $result = array();
        foreach (self::getSections() as $id => $section) {
            $row = isset($settings[$id]) ? $settings[$id] : array();
            if (empty($row['enabled']) && empty($section['always_enabled'])) {
                continue;
            }
            $path = ifset($row, 'path', $section['path']);
            $name = ifset($row, 'name', $section['name']);
            $result[$id] = array(
                'id' => $id,
                'name' => $name,
                'path' => $path,
                'url' => shopLkPluginUrlService::sectionUrl($route, $path),
                'selected' => waRequest::param('action') === self::actionBySection($id),
            );
        }
        return $result;
    }

    public static function firstCabinetUrl(array $route)
    {
        return shopLkPluginUrlService::getCabinetUrl($route);
    }

    protected static function actionBySection($id)
    {
        $map = array(
            'companies' => 'companies',
            'addresses' => 'addresses',
            'payments' => 'payments',
            'orders' => 'orders',
        );
        return isset($map[$id]) ? $map[$id] : 'dashboard';
    }
}
