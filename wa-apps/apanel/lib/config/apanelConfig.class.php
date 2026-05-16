<?php

class apanelConfig extends waAppConfig
{
    protected $config_array_runtime_cache = [];
    protected $_routes = [];

    public function __construct($env, $root_path, $app_id = null, $lang = null)
    {
        parent::__construct($env, $root_path, $app_id, $lang);

        $this->initVendors();
        $this->setModulePath();
        $this->getConfigFunctions('functions.php');
        $this->getConfigFunctions('modifiers.php');

        // временно, для разработки - потом перенести в installer.php
        // $this->setAppSettings();
    }


    

    /**
     * Возвращает routing приложения с подмешанными routing-правилами выбранного plugin-продукта.
     *
     * @param array $route Текущее правило поселения Webasyst.
     * @param bool $dispatch Признак runtime-dispatch.
     * @return array
     */
    public function getRouting($route = [], $dispatch = false)
    {
        $cache_key = $this->getRoutingCacheKey($route, $dispatch);

        if (!isset($this->_routes[$cache_key]) || $dispatch) {
            $routes = parent::getRouting($route);

            if ($dispatch && empty($route['is_backend_route'])) {
                $plugin_routes = $this->getSelectedPluginRoutes($route);

                if ($plugin_routes) {
                    $routes = array_merge($plugin_routes, $routes);
                }
            }

            $this->_routes[$cache_key] = $routes;
        }

        return $this->_routes[$cache_key];
    }

    /**
     * Возвращает routing выбранного plugin-продукта для текущего storefront route.
     *
     * @param array $route Текущее правило поселения Webasyst.
     * @return array
     */
    protected function getSelectedPluginRoutes($route)
    {
        $storefront = $this->getRuntimeStorefront($route);

        if (!$storefront) {
            return [];
        }

        $settings_service = new apanelStorefrontSettingsService();
        $plugin_id = $settings_service->getSelectedPluginId($storefront['storefront_key']);

        if ($plugin_id === '') {
            return [];
        }

        $plugin_registry = new apanelStorefrontPluginRegistry();

        if (!$plugin_registry->hasPlugin($plugin_id)) {
            return [];
        }

        $routes = $this->getPluginRoutingRules($plugin_id);

        if (!$routes) {
            return [];
        }

        $result = [];

        foreach ($routes as $url => $rule) {
            $rule = $this->preparePluginRoute($plugin_id, $rule);
            $result[$url] = $rule;
        }

        return $result;
    }

    /**
     * Возвращает routing.php выбранного plugin.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @return array
     */
    protected function getPluginRoutingRules($plugin_id)
    {
        $path = $this->getPluginPath($plugin_id) . '/lib/config/routing.php';

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $routes = include($path);

        return is_array($routes) ? $routes : [];
    }

    /**
     * Подготавливает одно routing-правило plugin для системного waFrontController.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param string|array $rule Правило routing.php plugin.
     * @return array
     */
    protected function preparePluginRoute($plugin_id, $rule)
    {
        if (!is_array($rule)) {
            $parts = explode('/', (string) $rule, 2);

            $rule = [
                'module' => ifset($parts[0], 'frontend'),
                'action' => ifset($parts[1], null),
            ];
        }

        $rule['app'] = 'apanel';
        $rule['plugin'] = $plugin_id;

        if (empty($rule['module'])) {
            $rule['module'] = 'frontend';
        }

        return $rule;
    }

    /**
     * Возвращает runtime-данные storefront по текущему route Webasyst.
     *
     * @param array $route Текущее правило поселения Webasyst.
     * @return array
     */
    protected function getRuntimeStorefront($route)
    {
        $domain = wa()->getRouting()->getDomain();

        if (!$domain || !is_array($route)) {
            return [];
        }

        $current_url = ifset($route['url'], '');
        $routing_service = new apanelRoutingService();
        $routes = $routing_service->getAppRoutes($domain);

        foreach ($routes as $route_id => $item) {
            if (ifset($item['url'], '') !== $current_url) {
                continue;
            }

            return [
                'route_id'       => $route_id,
                'domain_hash'    => hash('crc32', $domain),
                'domain'         => $domain,
                'url'            => ifset($item['url'], ''),
                'storefront_key' => hash('crc32', $domain) . '_' . $route_id,
            ];
        }

        return [];
    }

    /**
     * Возвращает ключ runtime-кэша routing.
     *
     * @param array $route Текущее правило поселения Webasyst.
     * @param bool $dispatch Признак runtime-dispatch.
     * @return string
     */
    protected function getRoutingCacheKey($route, $dispatch)
    {
        if (!is_array($route)) {
            $route = [];
        }

        return (empty($route['is_backend_route']) ? 'frontend' : 'backend')
            . ':'
            . ($dispatch ? 'dispatch' : 'plain')
            . ':'
            . ifset($route['url'], '');
    }

















    /**
     * Чтение PHP-массивов.
     *
     * @param string $file_name Имя файла.
     * @return array
     * @throws waException
     */
    public function getConfigArray(string $file_name): array
    {
        if (isset($this->config_array_runtime_cache[$file_name])) {
            return $this->config_array_runtime_cache[$file_name];
        }

        $file_path = $this->getConfigPath($file_name, false);

        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new waException(sprintf('File not found: %s', $file_path));
        }

        $result = include $file_path;
        return $this->config_array_runtime_cache[$file_name] = (is_array($result) ? $result : []);
    }

    /**
     * Подключение файлов логики.
     *
     * @param string $file_name Имя файла.
     * @return mixed|null
     */
    public function getConfigFunctions(string $file_name)
    {
        $file_path = $this->getConfigPath($file_name, false);
        if (is_file($file_path) && is_readable($file_path)) {
            return require_once $file_path;
        }
        return null;
    }

    /**
     * Подключение автозагрузчика Composer.
     *
     * @return void
     */
    protected function initVendors(): void
    {
        $autoload = $this->getAppPath('lib/vendors/vendor/autoload.php');

        if (file_exists($autoload)) {
            require_once $autoload;
        }
    }

    /**
     * Устанавливает путь от корня до модуля приложения.
     *
     * @return void
     */
    protected function setModulePath(): void
    {
        $segments = apanelUrlSegment::all();
        $parts = array_slice($segments, 0, 2);

        $app_url = rtrim(wa()->getAppUrl('apanel'), '/');
        $module_path = $app_url . '/' . implode('/', $parts) . '/';
        $module_path = preg_replace('#/+#', '/', $module_path);

        waConfig::add(['apanel_module_path' => $module_path]);
    }

    /**
     * Временная установка app-настроек.
     *
     * @return void
     */
    protected function setAppSettings()
    {
        apanelSettings::delete();

        $defaults = $this->getConfigArray('defaults.backend.php');
        apanelSettings::save('ui.backend.config', $defaults, true);

        $navigation = $this->getConfigArray('navigation.backend.php');
        apanelSettings::save('ui.backend.navigation', $navigation, true);
    }
}
