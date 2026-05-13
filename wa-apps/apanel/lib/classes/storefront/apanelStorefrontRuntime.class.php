<?php

/**
 * apanelStorefrontRuntime
 *
 * Runtime-сервис текущей frontend-витрины.
 *
 * Назначение:
 * - определить текущую витрину по frontend route Webasyst;
 * - собрать storefront_key;
 * - получить настройки витрины;
 * - получить выбранный Apanel plugin-продукт;
 * - получить frontend screens выбранного plugin;
 * - подготовить результат access/auth для frontend action/plugin action.
 *
 * Инварианты:
 * - runtime не матчить URL;
 * - runtime не выбирает plugin action;
 * - routing выполняет системный Webasyst через apanelConfig::getRouting();
 * - runtime не делает redirect;
 * - runtime не рендерит шаблоны;
 * - route/domain/url берутся из Webasyst routing, а не из apanelSettings;
 * - настройки витрины читаются из scope=storefront, scope_id={storefront_key};
 * - Webasyst theme, storefront template, scenario и scheme не используются.
 */
final class apanelStorefrontRuntime
{
    protected $routing_service;
    protected $plugin_registry;
    protected $screen_registry;
    protected $access_service;
    protected $auth_service;

    protected $storefront;
    protected $settings;
    protected $plugin;
    protected $screens;
    protected $access_result;
    protected $auth;

    /**
     * Конструктор.
     *
     * @param apanelRoutingService|null $routing_service Сервис роутинга.
     * @param apanelStorefrontPluginRegistry|null $plugin_registry Реестр plugin-продуктов.
     * @param apanelStorefrontScreenRegistry|null $screen_registry Реестр screens.
     * @param apanelStorefrontAccessService|null $access_service Сервис доступа.
     * @param apanelStorefrontAuthService|null $auth_service Сервис авторизации.
     */
    public function __construct($routing_service = null, $plugin_registry = null, $screen_registry = null, $access_service = null, $auth_service = null)
    {
        $this->routing_service = $routing_service ?: new apanelRoutingService();
        $this->plugin_registry = $plugin_registry ?: new apanelStorefrontPluginRegistry();
        $this->screen_registry = $screen_registry ?: new apanelStorefrontScreenRegistry($this->plugin_registry);
        $this->access_service  = $access_service ?: new apanelStorefrontAccessService();
        $this->auth_service    = $auth_service ?: new apanelStorefrontAuthService();
    }

    /**
     * Собирает runtime витрины.
     *
     * @param string|null $domain Домен витрины.
     * @param int|string|null $route_id ID правила маршрутизации.
     * @return array
     * @throws waException
     */
    public function build($domain = null, $route_id = null)
    {
        $this->storefront = $this->getCurrentStorefront($domain, $route_id);

        if (!$this->storefront) {
            throw new waException('Витрина Apanel не найдена.', 404);
        }

        $settings_service = new apanelStorefrontSettingsService(
            $this->routing_service,
            $this->plugin_registry,
            $this->screen_registry
        );

        $storefront_settings = $settings_service->getSettings($this->storefront['storefront_key'], null, false);

        if (!is_array($storefront_settings)) {
            $storefront_settings = [];
        }

        $core_defaults = $this->getCoreDefaults();
        $plugin_id = trim((string) ifset($storefront_settings['plugin']['id'], ifset($core_defaults['plugin']['id'], '')));

        $this->plugin = $plugin_id !== '' ? $this->plugin_registry->getPlugin($plugin_id, []) : [];
        $plugin_defaults = $this->getPluginDefaults($this->plugin);

        $this->settings = array_replace_recursive(
            $core_defaults,
            $plugin_defaults,
            $storefront_settings
        );

        $this->settings['plugin']['id'] = $plugin_id;
        $this->settings = $settings_service->applyPluginPolicy($this->settings, $this->plugin);

        $this->screens = $this->plugin
            ? $this->screen_registry->getPluginScreens($plugin_id, $this->settings, $this->storefront['storefront_key'])
            : [];

        $this->access_result = $this->access_service->getResult($this->settings);
        $this->auth = $this->auth_service->getAuth($this->settings, ifset($this->storefront['full_url'], ''));

        $this->setRuntimeParams($plugin_id);

        return [
            'storefront'    => $this->storefront,
            'settings'      => $this->settings,
            'plugin'        => $this->plugin,
            'plugin_id'     => $plugin_id,
            'plugin_status' => $this->getPluginStatus($plugin_id, $this->plugin),
            'screens'       => $this->screens,
            'access_result' => $this->access_result,
            'auth'          => $this->auth,
        ];
    }

    /**
     * Возвращает данные текущей витрины.
     *
     * @param string|null $domain Домен.
     * @param int|string|null $route_id ID правила маршрутизации.
     * @return array|null
     */
    public function getCurrentStorefront($domain = null, $route_id = null)
    {
        if ($domain === null && $route_id === null && $this->storefront !== null) {
            return $this->storefront;
        }

        if ($domain !== null && $route_id !== null) {
            $routes = $this->routing_service->getAppRoutes($domain);

            if (empty($routes[$route_id])) {
                return null;
            }

            return $this->setStorefront($domain, $route_id, $routes[$route_id]);
        }

        $domain = wa()->getRouting()->getDomain();
        $current_route = wa()->getRouting()->getRoute();

        if (!$domain || !is_array($current_route)) {
            return null;
        }

        $current_url = ifset($current_route['url'], '');
        $routes = $this->routing_service->getAppRoutes($domain);

        foreach ($routes as $route_id => $route) {
            if (ifset($route['url'], '') !== $current_url) {
                continue;
            }

            return $this->setStorefront($domain, $route_id, $route);
        }

        return null;
    }

    /**
     * Возвращает итоговые настройки.
     *
     * @return array
     */
    public function getSettings()
    {
        return is_array($this->settings) ? $this->settings : [];
    }

    /**
     * Возвращает выбранный plugin-продукт.
     *
     * @return array
     */
    public function getPlugin()
    {
        return is_array($this->plugin) ? $this->plugin : [];
    }

    /**
     * Возвращает screens витрины.
     *
     * @return array
     */
    public function getScreens()
    {
        return is_array($this->screens) ? $this->screens : [];
    }

    /**
     * Возвращает результат проверки доступа.
     *
     * @return array
     */
    public function getAccessResult()
    {
        return is_array($this->access_result) ? $this->access_result : [];
    }

    /**
     * Возвращает auth-карту витрины.
     *
     * @return array
     */
    public function getAuth()
    {
        return is_array($this->auth) ? $this->auth : [];
    }

    /**
     * Записывает storefront в runtime.
     *
     * @param string $domain Домен.
     * @param int|string $route_id ID правила маршрутизации.
     * @param array $route Данные route.
     * @return array
     */
    protected function setStorefront($domain, $route_id, $route)
    {
        $domain_hash = hash('crc32', $domain);
        $url = ifset($route['url'], '');
        $clean_url = waRouting::clearUrl($url);

        $this->storefront = [
            'route_id'       => $route_id,
            'domain_hash'    => $domain_hash,
            'domain'         => $domain,
            'url'            => $url,
            'full_url'       => $this->buildFullUrl($domain, $clean_url),
            'app'            => ifset($route['app'], 'apanel'),
            'storefront_key' => $domain_hash . '_' . $route_id,
        ];

        return $this->storefront;
    }

    /**
     * Возвращает дефолты ядра.
     *
     * @return array
     */
    protected function getCoreDefaults()
    {
        return [
            'profile' => [
                'enabled' => 1,
            ],
            'plugin' => [
                'id'       => '',
                'settings' => [],
            ],
            'screens' => [],
            'access' => [
                'mode'     => 'public',
                'groups'   => [],
                'contacts' => [],
            ],
            'auth' => [
                'enabled'              => 0,
                'registration_enabled' => 0,
                'login_by'             => 'email',
            ],
            'ui'       => [],
            'data'     => [],
            'seo'      => [],
            'advanced' => [],
        ];
    }

    /**
     * Возвращает дефолты выбранного plugin-продукта.
     *
     * @param array $plugin Декларация plugin-продукта.
     * @return array
     */
    protected function getPluginDefaults($plugin)
    {
        if (!$plugin) {
            return [];
        }

        $defaults = [];
        $access = ifset($plugin['access'], []);
        $auth = ifset($plugin['auth'], []);
        $settings = ifset($plugin['settings'], []);

        if (is_array($access) && isset($access['default_mode'])) {
            $defaults['access']['mode'] = (string) $access['default_mode'];
        }

        if (is_array($auth)) {
            foreach (['enabled', 'registration_enabled', 'login_by', 'after_login_url', 'after_logout_url'] as $key) {
                if (array_key_exists($key, $auth)) {
                    $defaults['auth'][$key] = $auth[$key];
                }
            }
        }

        if (is_array($settings) && isset($settings['defaults']) && is_array($settings['defaults'])) {
            $defaults['plugin']['settings'] = $settings['defaults'];
        }

        return $defaults;
    }

    /**
     * Устанавливает runtime params.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @return void
     */
    protected function setRuntimeParams($plugin_id)
    {
        if ($plugin_id !== '') {
            waRequest::setParam('selected_plugin_id', $plugin_id);
        }

        if (!empty($this->storefront['storefront_key'])) {
            waRequest::setParam('storefront_key', $this->storefront['storefront_key']);
        }
    }

    /**
     * Возвращает статус выбранного plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param array $plugin Декларация plugin-продукта.
     * @return string
     */
    protected function getPluginStatus($plugin_id, $plugin)
    {
        if ($plugin_id === '') {
            return 'empty';
        }

        if (!$plugin) {
            return 'missing';
        }

        return 'ready';
    }

    /**
     * Собирает полный URL витрины.
     *
     * @param string $domain Домен.
     * @param string $url URL поселения без звезды.
     * @return string
     */
    protected function buildFullUrl($domain, $url)
    {
        $scheme = waRequest::isHttps() ? 'https://' : 'http://';
        $url = trim((string) $url, '/');

        return $scheme . rtrim($domain, '/') . '/' . ($url !== '' ? $url . '/' : '');
    }
}
