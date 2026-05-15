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
                $key = self::getStorefrontHash($domain, $shop_url);
                $rows[$key] = array(
                    'key' => $key,
                    'domain' => $domain,
                    'shop_url' => $shop_url,
                    'shop_label' => $shop_url !== '' ? '/'.$shop_url : '/',
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
            'key' => self::getStorefrontHash($domain, $shop_url),
        );
    }

    public static function getRouteForCurrentStorefront($only_enabled = true)
    {
        self::ensureSchema();
        $sf = self::getCurrentStorefront();
        $row = (new shopLkPluginRouteModel())->getByStorefront($sf['domain'], $sf['shop_url']);
        if (!$row) {
            return null;
        }
        if ($only_enabled && empty($row['enabled'])) {
            return null;
        }
        return $row;
    }

    public static function getRoutesForCurrentStorefront()
    {
        $row = self::getRouteForCurrentStorefront(true);
        return $row ? array((int) $row['id'] => $row) : array();
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

        self::$current_route = self::getRouteForCurrentStorefront(true);
        return self::$current_route;
    }

    public function getSettingsRows()
    {
        self::ensureSchema();
        $route_model = new shopLkPluginRouteModel();
        $payment_model = new shopLkPluginPaymentTypeModel();
        $storefronts = self::getRoutingStorefronts();
        $stored = $route_model->getAllByStorefrontHash();
        $rows = array();

        foreach ($storefronts as $key => $sf) {
            $row = isset($stored[$key]) ? $stored[$key] : array();
            $row = $this->prepareSettingsRow($sf, $row);
            if (!empty($row['id'])) {
                $row['payments'] = $payment_model->getByRoute($row['id']);
                if (!$row['payments']) {
                    $row['payments'] = shopLkPluginPaymentTypeModel::getDefaultRows();
                }
            } else {
                $row['payments'] = shopLkPluginPaymentTypeModel::getDefaultRows();
            }
            $rows[$key] = $row;
        }

        return array(
            'storefronts' => $rows,
            'copy_sources' => $this->getCopySources($rows),
        );
    }

    protected function prepareSettingsRow(array $sf, array $row)
    {
        $has_row = !empty($row['id']);
        $b2b = $has_row ? !empty($row['b2b_mode']) : false;
        $route = $has_row ? (string) ifset($row, 'route', '') : 'my';
        if ($b2b) {
            $route = '';
        } elseif (trim($route) === '') {
            $route = 'my';
        }

        $result = array_merge(array(
            'id' => 0,
            'domain' => $sf['domain'],
            'shop_url' => $sf['shop_url'],
            'storefront_hash' => $sf['key'],
            'route_hash' => '',
            'route' => $route,
            'name' => 'B2B кабинет',
            'enabled' => 0,
            'b2b_mode' => 0,
            'lock_mode' => 'cabinet',
            'config' => '',
        ), $row);

        $result['key'] = $sf['key'];
        $result['domain'] = $sf['domain'];
        $result['shop_url'] = $sf['shop_url'];
        $result['shop_label'] = $sf['shop_label'];
        $result['route'] = $route;
        $result['b2b_mode'] = $b2b ? 1 : 0;
        $result['mode_label'] = $b2b ? 'Вся витрина' : 'Только ЛК';
        $result['config_array'] = $this->normalizeConfig($has_row ? $this->decodeRouteConfig($row) : array());
        $result['storefront_url'] = shopLkPluginUrlService::getStorefrontUrl($result, true);
        $result['front_url'] = shopLkPluginUrlService::getCabinetUrl($result, true);
        $result['status'] = empty($result['enabled']) ? 'Выкл' : ($b2b ? 'B2B' : 'ЛК');

        return $result;
    }

    protected function getCopySources(array $rows)
    {
        $result = array();
        foreach ($rows as $key => $row) {
            if (empty($row['id'])) {
                continue;
            }
            $label = $row['domain'].'/'.$row['shop_url'];
            if (trim($row['name']) !== '') {
                $label .= ' — '.$row['name'];
            }
            $result[$key] = $label;
        }
        return $result;
    }

    public function saveFromSettings(array $settings)
    {
        self::ensureSchema();
        $route_model = new shopLkPluginRouteModel();
        $payment_model = new shopLkPluginPaymentTypeModel();

        $storefronts = isset($settings['storefronts']) && is_array($settings['storefronts']) ? $settings['storefronts'] : array();
        foreach ($storefronts as $key => $row) {
            $row = is_array($row) ? $row : array();
            if (empty($row['domain']) || !isset($row['shop_url'])) {
                continue;
            }

            $copy_from = trim((string) ifset($row, 'copy_from', ''));
            if ($copy_from !== '' && $copy_from !== $key) {
                $source = $route_model->getByField('storefront_hash', $copy_from);
                if ($source) {
                    $source_config = $this->decodeRouteConfig($source);
                    $row['config'] = $source_config;
                }
            }

            $row['config'] = $this->prepareRouteConfig(ifset($row, 'config', array()));
            $route_id = $route_model->saveRoute($row);

            if ($copy_from !== '' && $copy_from !== $key && !empty($source)) {
                $payment_model->copyRoutePaymentTypes((int) $source['id'], $route_id);
            } else {
                $payment_model->ensureDefaults($route_id);
                if (!empty($row['payments']) && is_array($row['payments'])) {
                    $payment_model->saveRoutePayments($route_id, $row['payments']);
                }
            }
        }
    }

    protected function decodeRouteConfig(array $row)
    {
        $config = !empty($row['config']) ? json_decode($row['config'], true) : array();
        return is_array($config) ? $config : array();
    }

    protected function normalizeConfig($config)
    {
        return $this->prepareRouteConfig(is_array($config) ? $config : array());
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
                'path' => self::normalizeRoute(ifset($row, 'path', $section['path']), $section['path']),
            );
        }

        $auth = isset($config['auth']) && is_array($config['auth']) ? $config['auth'] : array();
        return array(
            'sections' => $result_sections,
            'auth' => array(
                'title' => trim((string) ifset($auth, 'title', 'B2B кабинет')),
                'text' => trim((string) ifset($auth, 'text', 'Войдите для работы с компаниями, адресами и заказами.')),
            ),
        );
    }

    public static function getStorefrontHash($domain, $shop_url)
    {
        return md5(self::normalizeDomain($domain).'|'.self::normalizeShopUrl($shop_url));
    }

    public static function getRouteHash($domain, $shop_url, $route)
    {
        return md5(self::normalizeDomain($domain).'|'.self::normalizeShopUrl($shop_url).'|'.self::normalizeRoute($route, ''));
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

    public static function normalizeRoute($route, $default = 'my')
    {
        return self::slug((string) $route, $default);
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
