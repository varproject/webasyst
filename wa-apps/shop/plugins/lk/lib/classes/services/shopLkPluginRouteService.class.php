<?php

final class shopLkPluginRouteService
{
    protected static $routes_cache = null;
    protected static $current_route = false;

    public static function resetRuntimeCache()
    {
        self::$routes_cache = null;
        self::$current_route = false;
    }

    public static function ensureSchema()
    {
        shopLkPluginSchema::ensure();
    }

    public static function getMainSettings()
    {
        try {
            $settings = wa('shop')->getPlugin('lk')->getSettings();
        } catch (Exception $e) {
            $settings = array();
        }
        return isset($settings['main']) && is_array($settings['main']) ? $settings['main'] : array('enabled' => 0);
    }

    public static function isPluginEnabled()
    {
        $main = self::getMainSettings();
        return !empty($main['enabled']);
    }

    public static function getRoutingStorefronts()
    {
        $rows = array();
        foreach ((array) wa()->getRouting()->getDomains() as $domain) {
            foreach ((array) wa()->getRouting()->getRoutes($domain) as $route) {
                if (empty($route['app']) || $route['app'] !== 'shop') {
                    continue;
                }
                $domain = self::normalizeDomain($domain);
                $shop_url = self::normalizeShopUrl(ifset($route, 'url', ''));
                $key = md5($domain.'|'.$shop_url);
                $rows[$key] = array(
                    'key' => $key,
                    'domain' => $domain,
                    'shop_url' => $shop_url,
                    'label' => $domain.'/'.($shop_url ?: ''),
                );
            }
        }
        return $rows;
    }

    public static function getCurrentStorefront()
    {
        $domain = self::normalizeDomain(wa()->getRouting()->getDomain());
        $route = wa()->getRouting()->getRoute();
        $shop_url = self::normalizeShopUrl(ifset($route, 'url', ''));
        return array(
            'domain' => $domain,
            'shop_url' => $shop_url,
        );
    }

    public static function getRoutesForCurrentStorefront()
    {
        self::ensureSchema();
        $sf = self::getCurrentStorefront();
        return (new shopLkPluginRouteModel())->getEnabledByStorefront($sf['domain'], $sf['shop_url']);
    }

    public static function getCurrentRoute()
    {
        self::ensureSchema();
        if (self::$current_route !== false) {
            return self::$current_route;
        }
        $route_id = (int) waRequest::param('_lk_route_id', 0, waRequest::TYPE_INT);
        $model = new shopLkPluginRouteModel();
        if ($route_id > 0) {
            self::$current_route = $model->getById($route_id);
            return self::$current_route;
        }

        $segment = shopLkPluginUrlSegment::get(1, '');
        foreach (self::getRoutesForCurrentStorefront() as $row) {
            if (trim($row['route'], '/') === trim($segment, '/')) {
                self::$current_route = $row;
                return self::$current_route;
            }
        }

        self::$current_route = null;
        return null;
    }

    public function getSettingsRows()
    {
        self::ensureSchema();
        $model = new shopLkPluginRouteModel();
        $payment_model = new shopLkPluginPaymentTypeModel();
        $storefronts = self::getRoutingStorefronts();
        $routes = $model->select('*')->order('domain, shop_url, route')->fetchAll('id');

        foreach ($routes as &$row) {
            $row['config_array'] = $model->decodeConfig($row);
            $row['payments'] = $payment_model->getByRoute($row['id']);
            $row['storefront_key'] = md5($row['domain'].'|'.$row['shop_url']);
            $row['front_url'] = shopLkPluginUrlService::getCabinetUrl($row, true);
        }
        unset($row);

        return array(
            'storefronts' => $storefronts,
            'routes' => $routes,
        );
    }

    public function saveFromSettings(array $settings)
    {
        self::ensureSchema();
        $route_model = new shopLkPluginRouteModel();
        $payment_model = new shopLkPluginPaymentTypeModel();

        $routes = isset($settings['routes']) && is_array($settings['routes']) ? $settings['routes'] : array();
        foreach ($routes as $id => $row) {
            $row = is_array($row) ? $row : array();
            $row['id'] = (int) $id;
            if (empty($row['domain']) || !isset($row['shop_url'])) {
                continue;
            }
            $row['config'] = $this->prepareRouteConfig(ifset($row, 'config', array()));
            $route_id = $route_model->saveRoute($row);
            $payment_model->ensureDefaults($route_id);
            if (!empty($row['payments']) && is_array($row['payments'])) {
                $payment_model->saveRoutePayments($route_id, $row['payments']);
            }
        }

        $new = isset($settings['new_route']) && is_array($settings['new_route']) ? $settings['new_route'] : array();
        if (!empty($new['domain']) && isset($new['shop_url']) && !empty($new['route'])) {
            $new['enabled'] = !empty($new['enabled']) ? 1 : 0;
            $new['b2b_mode'] = 1;
            $new['lock_mode'] = ifset($new, 'lock_mode', 'cabinet');
            $new['config'] = $this->prepareRouteConfig(ifset($new, 'config', array()));
            $route_id = $route_model->saveRoute($new);
            $payment_model->ensureDefaults($route_id);
        }
    }

    protected function prepareRouteConfig($config)
    {
        $config = is_array($config) ? $config : array();
        $sections = isset($config['sections']) && is_array($config['sections']) ? $config['sections'] : array();
        $result_sections = array();
        foreach (shopLkPluginNavigation::getSections() as $id => $section) {
            $row = isset($sections[$id]) && is_array($sections[$id]) ? $sections[$id] : array();
            $result_sections[$id] = array(
                'enabled' => !empty($row['enabled']) || !empty($section['always_enabled']) ? 1 : 0,
                'name' => trim((string) ifset($row, 'name', $section['name'])),
                'path' => self::normalizeRoute(ifset($row, 'path', $section['path'])),
            );
        }

        return array(
            'sections' => $result_sections,
            'auth' => array(
                'title' => trim((string) ifset($config, 'auth', 'title', 'B2B кабинет')),
                'text' => trim((string) ifset($config, 'auth', 'text', 'Войдите для работы с компаниями, адресами и заказами.')),
            ),
        );
    }

    public static function normalizeDomain($domain)
    {
        $domain = trim((string) $domain);
        $domain = preg_replace('~^https?://~i', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('~:\d+$~', '', $domain);
        return strtolower(trim($domain));
    }

    public static function normalizeShopUrl($url)
    {
        $url = trim((string) $url);
        $url = trim($url, '/');
        if ($url === '*' || $url === '') {
            return '';
        }
        $url = preg_replace('~\*$~', '', $url);
        return trim($url, '/').'/';
    }

    public static function normalizeRoute($route)
    {
        return self::slug((string) $route, 'my');
    }

    public static function slug($value, $default = '')
    {
        $value = trim((string) $value);
        $value = preg_replace('~[^a-z0-9а-яё\-_]+~iu', '-', $value);
        $value = trim($value, '-_/');
        $value = mb_strtolower($value, 'UTF-8');
        return $value !== '' ? $value : $default;
    }
}
