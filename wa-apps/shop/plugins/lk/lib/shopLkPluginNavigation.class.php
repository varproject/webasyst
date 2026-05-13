<?php

final class shopLkPluginNavigation
{
    const DEFAULT_ROOT_URL = 'my';

    protected static $menu            = [];
    protected static $routes          = [];
    protected static $tabs            = [];
    protected static $fallback        = [];
    protected static $static_url      = [];
    protected static $settings        = [];
    protected static $settings_loaded = false;
    protected static $storefront      = [];
    protected static $storefront_key  = '';
    protected static $lk_url          = '';

    protected static $navigations = [
        #КАТАЛОГ
        'catalog' => [
            'id'     => 'catalog',
            'name'   => 'Каталог',
            'action' => 'myCatalog',
        ],

        #ЗАКАЗЫ
        'order/<id>' => [
            'id'        => 'order',
            'parent_id' => 'orders',
            'name'      => 'Заказ',
            'action'    => 'myOrder',
            '_param'    => true,
        ],
        'orders' => [
            'id'     => 'orders',
            'name'   => 'Заказы',
            'action' => 'myOrders',
            'childs' => ['myOrder'],
        ],

        #КОРЗИНЫ
        'carts' => [
            'id'     => 'carts',
            'name'   => 'Корзины',
            'action' => 'myCarts',
        ],

        #КОМПАНИИ
        'companies' => [
            'id'     => 'companies',
            'name'   => 'Компании',
            'action' => 'myCompanies',
        ],

        #АДРЕСА
        'addresses' => [
            'id'     => 'addresses',
            'name'   => 'Адреса',
            'action' => 'myAddresses',
        ],

        #ПАРТНЕРСКАЯ ПРОГРАММА
        'affiliate' => [
            'id'     => 'affiliate',
            'name'   => 'Бонусы',
            'action' => 'myAffiliate',
        ],

        #РАССЫЛКИ
        'mailer' => [
            'id'     => 'mailer',
            'name'   => 'Рассылки',
            'action' => 'myMailer',
        ],

        #БЛОГ
        'blog' => [
            'id'     => 'blog',
            'name'   => 'Новости',
            'action' => 'myBlog',
        ],

        #ОБРАТНАЯ СВЯЗЬ
        'feedback' => [
            'id'     => 'feedback',
            'name'   => 'Обратная связь',
            'action' => 'myFeedback',
        ],

        #ПОМОЩЬ
        'help' => [
            'id'     => 'help',
            'name'   => 'Помощь',
            'action' => 'myHelp',
        ],

        #ПРОФИЛЬ
        'profile' => [
            'id'             => 'profile',
            'name'           => 'Профиль',
            'action'         => 'myProfile',
            'show_in_menu'   => false,
            'always_enabled' => true,
        ],
    ];

    // Сбросить runtime-кэш после сохранения настроек
    public static function reset($settings = null)
    {
        self::$menu           = [];
        self::$routes         = [];
        self::$tabs           = [];
        self::$fallback       = [];
        self::$storefront     = [];
        self::$storefront_key = '';
        self::$lk_url         = '';

        if ($settings === null) {
            self::$settings        = [];
            self::$settings_loaded = false;
            return;
        }

        self::$settings        = $settings;
        self::$settings_loaded = true;
    }

    // Получить настройки плагина
    public static function getSettings()
    {
        if (self::$settings_loaded) {
            return self::$settings;
        }

        try {
            self::$settings = wa('shop')->getPlugin('lk')->getSettings() ?: [];
        } catch (Exception $e) {
            self::$settings = [];
        }

        if (empty(self::$settings)) {
            $model = new waAppSettingsModel();
            $json  = $model->get('shop', 'lk_runtime_settings', '');
            $data  = $json ? json_decode($json, true) : [];

            if (is_array($data)) {
                self::$settings = $data;
            }
        }

        self::$settings_loaded = true;

        return self::$settings;
    }

    // Получить строки витрин Shop-Script из системного роутинга
    public static function getRoutingStorefronts()
    {
        $rows = [];

        foreach ((array) wa()->getRouting()->getDomains() as $domain) {
            foreach ((array) wa()->getRouting()->getRoutes($domain) as $route) {
                if (empty($route['app']) || $route['app'] !== 'shop') {
                    continue;
                }

                $domain   = self::normalizeDomain($domain);
                $shop_url = self::normalizeShopUrl($route['url'] ?? '');
                $key      = self::getStorefrontKey($domain, $shop_url);

                $rows[$key] = [
                    'key'      => $key,
                    'domain'   => $domain,
                    'shop_url' => $shop_url,
                ];
            }
        }

        return $rows;
    }

    // Получить строки витрин для таблицы настроек
    public static function getSettingsStorefrontRows($absolute = true)
    {
        self::prepareContext();

        $settings    = self::getSettings();
        $storefronts = !empty($settings['storefronts']) && is_array($settings['storefronts']) ? $settings['storefronts'] : [];
        $rows        = [];

        foreach (self::getRoutingStorefronts() as $key => $row) {
            $config  = self::prepareStorefrontConfig($key, $row, $storefronts[$key] ?? []);
            $base    = self::getStorefrontFrontUrl($config, $absolute);
            $cabinet = self::getStorefrontCabinetUrl($config, $absolute);

            $rows[$key] = [
                'key'         => $key,
                'domain'      => $config['domain'],
                'shop_url'    => $config['shop_url'],
                'shop_label'  => $config['shop_url'] !== '' ? '/' . $config['shop_url'] : '/',
                'base_url'    => $base,
                'cabinet_url' => $cabinet,
                'enabled'     => !empty($config['enabled']),
                'b2b_mode'    => !empty($config['b2b_mode']),
                'route'       => $config['route'],
                'status'      => self::getStorefrontStatus($config),
                'sections'    => self::getPreparedSections($config),
                'auth'        => self::getPreparedAuth($config),
            ];
        }

        return $rows;
    }

    public static function getSettingsStorefrontRow($key, $absolute = true)
    {
        $rows = self::getSettingsStorefrontRows($absolute);

        return $rows[$key] ?? null;
    }

    // Получить строки разделов для настроек
    public static function getNavigationSettingsRows()
    {
        $rows = [];

        foreach (self::$navigations as $key => $nav) {
            if (!empty($nav['_param'])) {
                continue;
            }

            $id = $nav['id'] ?? $key;

            $rows[$id] = [
                'id'             => $id,
                'key'            => $key,
                'name'           => $nav['name'] ?? $key,
                'path'           => $nav['path'] ?? $key,
                'icon'           => $nav['icon'] ?? '',
                'always_enabled' => !empty($nav['always_enabled']),
                'show_in_menu'   => !isset($nav['show_in_menu']) || !empty($nav['show_in_menu']),
            ];
        }

        return $rows;
    }

    // Получить текущую витрину
    public static function getCurrentStorefront()
    {
        self::prepareContext();

        return self::$storefront;
    }

    // Получить URL кабинета текущей витрины
    public static function getRootFrontUrl($absolute = false)
    {
        self::prepareContext();

        return self::getStorefrontCabinetUrl(self::$storefront, $absolute);
    }

    // Получить URL штатного кабинета Shop-Script
    public static function getDefaultCabinetFrontUrl($absolute = false)
    {
        return self::getShopFrontUrl($absolute) . self::DEFAULT_ROOT_URL . '/orders/';
    }

    // Получить URL первого раздела кабинета
    public static function getFirstMenuUrl()
    {
        $menu = self::getMenu();

        if (!empty($menu)) {
            $item = reset($menu);
            return $item['url'];
        }

        return self::getRootFrontUrl() . 'profile/';
    }

    // Получить action первого доступного раздела
    public static function getFallbackAction()
    {
        $nav = self::getFirstEnabledSection();

        return $nav['action'] ?? 'myProfile';
    }

    // Получить роуты кабинета текущей витрины
    public static function getRoutes()
    {
        self::prepareContext();

        if (!empty(self::$routes)) {
            return self::$routes;
        }

        $prefix = self::$lk_url === '' ? '' : self::$lk_url . '/';
        $routes = [];

        foreach (self::$navigations as $key => $nav) {
            if (!self::isEnabled($key, $nav)) {
                continue;
            }

            $nav = self::prepareNavigationItem($key, $nav);

            $route_key = $prefix . $nav['path'] . '/?*';
            $route_url = !empty($nav['_param']) ? $prefix . $nav['path'] . '/?' : $route_key;

            $routes[$route_key] = [
                'url'    => $route_url,
                'module' => 'frontend',
                'action' => $nav['action'],
                '_param' => $nav['_param'] ?? false,
                'secure' => true,
            ];
        }

        self::$routes = $routes;

        return self::$routes;
    }

    // Получить fallback для кривых адресов внутри root кабинета
    public static function getFallback()
    {
        self::prepareContext();

        if (!empty(self::$fallback)) {
            return self::$fallback;
        }

        if (self::$lk_url === '') {
            self::$fallback = [];
            return self::$fallback;
        }

        $prefix = self::$lk_url . '/';
        $nav    = self::getFirstEnabledSection();

        self::$fallback = [
            $prefix . '*' => [
                'url'          => $prefix . '?',
                'module'       => 'frontend',
                'action'       => $nav['action'] ?? 'myProfile',
                'secure'       => true,
                'fallback'     => true,
                'fallback_url' => $prefix . ($nav['path'] ?? ''),
            ],
        ];

        return self::$fallback;
    }

    // Получить меню кабинета текущей витрины
    public static function getMenu()
    {
        self::prepareContext();

        if (!empty(self::$menu)) {
            return self::$menu;
        }

        $current_action = waRequest::param('action');
        $menu = [];

        foreach (self::$navigations as $key => $section) {
            if (!empty($section['_param']) || !self::isEnabled($key, $section)) {
                continue;
            }

            $section = self::prepareNavigationItem($key, $section);

            if (isset($section['show_in_menu']) && empty($section['show_in_menu'])) {
                continue;
            }

            $section['enabled']      = true;
            $section['show_in_menu'] = true;
            $section['is_active']    = ($current_action === $section['action'] || (!empty($section['childs']) && in_array($current_action, $section['childs'])));
            $section['url']          = self::getRootFrontUrl() . $section['path'] . '/';

            $menu[$section['id']] = $section;
        }

        self::$menu = $menu;

        return self::$menu;
    }

    // Получить табы настроек
    public static function getSettingsTabs()
    {
        if (!empty(self::$tabs)) {
            return self::$tabs;
        }

        self::$tabs = [
            [
                'id'   => 'main',
                'name' => 'Плагин',
                'hash' => '#/lk',
                'file' => 'SettingsTabMain.html',
            ],
        ];

        return self::$tabs;
    }

    // Получить static URL плагина
    public static function getStaticUrl($absolute = false)
    {
        if (!empty(self::$static_url[$absolute])) {
            return self::$static_url[$absolute];
        }

        self::$static_url[$absolute] = wa('shop')->getPlugin('lk')->getPluginStaticUrl($absolute);

        return self::$static_url[$absolute];
    }

    // Получить правила маршрутизации
    public static function getRouting()
    {
        self::prepareContext();

        $plugins = wa('shop')->getConfig()->getPlugins();

        if (empty(self::$settings['main']['enabled']) || !isset($plugins['lk']) || empty(self::$storefront['enabled'])) {
            if (wa()->getEnv() === 'frontend') {
                self::redirectAuthToCanonical();
                self::redirectFromInactivePlugin();
            }

            return null;
        }

        if (wa()->getEnv() === 'frontend' && !self::isLogoutRequest() && wa()->getUser()->isAuth()) {
            self::redirectFromMovedRoot();
        }

        if (!empty(self::$storefront['b2b_mode'])) {
            return self::getB2bRouting();
        }

        if (wa()->getEnv() === 'frontend' && !self::isLogoutRequest()) {
            self::redirectToEnabledSection();
        }

        return self::getRoutes() + self::getFallback();
    }

    // Редирект на первый включенный раздел внутри кабинета
    public static function redirectToEnabledSection()
    {
        self::prepareContext();

        if (wa()->getEnv() !== 'frontend' || !self::isInsideCabinetRoot()) {
            return;
        }

        $segment = self::$lk_url === ''
            ? shopLkPluginUrlSegment::get(1)
            : shopLkPluginUrlSegment::get(2);

        $enabled = self::getEnabledSegments();

        if ($segment !== null && $segment !== '' && !empty($enabled[$segment])) {
            return;
        }

        self::redirectToFirstCabinetUrl();
    }

    // Получить URL страницы авторизации
    public static function getAuthUrl($absolute = false)
    {
        return self::getShopFrontUrl($absolute) . 'login/';
    }

    // Получить URL страницы регистрации
    public static function getSignupUrl($absolute = false)
    {
        return self::getShopFrontUrl($absolute) . 'signup/';
    }

    // Получить URL страницы восстановления пароля
    public static function getForgotpasswordUrl($absolute = false)
    {
        return self::getShopFrontUrl($absolute) . 'forgotpassword/';
    }

    // Редирект auth-страниц на каноническую витрину
    public static function redirectAuthToCanonical()
    {
        if (wa()->getEnv() !== 'frontend' || self::isLogoutRequest()) {
            return;
        }

        $segment = shopLkPluginUrlSegment::get(1);

        if (!in_array($segment, ['login', 'signup', 'forgotpassword', 'setpassword'], true)) {
            return;
        }

        $target = self::getEnabledStorefrontForCurrentDomain();

        if (empty($target)) {
            return;
        }

        $query   = (string) waRequest::server('QUERY_STRING', '');
        $url     = self::getStorefrontFrontUrl($target) . $segment . '/';
        $url    .= $query !== '' ? '?' . $query : '';

        if (self::normalizeComparableUrl($url) === self::normalizeComparableUrl((string) waRequest::server('REQUEST_URI', ''))) {
            return;
        }

        shopLkPluginRedirect::redirectBack($url);
    }

    // Нормализовать домен
    public static function normalizeDomain($domain)
    {
        $domain = trim((string) $domain);
        $domain = preg_replace('~^https?://~i', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('~:\d+$~', '', $domain);

        return strtolower(trim($domain));
    }

    // Получить ключ витрины
    public static function getStorefrontKey($domain, $shop_url)
    {
        return md5(self::normalizeDomain($domain) . '|' . self::normalizeShopUrl($shop_url));
    }

    // Получить роутинг B2B-режима
    protected static function getB2bRouting()
    {
        if (wa()->getEnv() !== 'frontend') {
            return self::getRoutes() + self::getFallback();
        }

        if (self::isLogoutRequest()) {
            return self::getRoutes() + self::getFallback();
        }

        self::redirectAuthToCanonical();

        if (!wa()->getUser()->isAuth()) {
            self::redirectToAuth();
            return self::getAuthRoutes();
        }

        if (self::$lk_url !== '' && !self::isInsideCabinetRoot()) {
            self::redirectToFirstCabinetUrl();
        }

        self::redirectToEnabledSection();

        return self::getRoutes() + self::getFallback();
    }

    // Получить auth-роуты B2B-режима
    protected static function getAuthRoutes()
    {
        return [
            ''  => 'frontendAuth',
            '*' => 'frontendAuth',
        ];
    }

    // Редирект гостя на страницу авторизации
    protected static function redirectToAuth()
    {
        $segment = shopLkPluginUrlSegment::get(1);

        if (in_array($segment, ['login', 'forgotpassword', 'signup', 'setpassword'], true)) {
            return;
        }

        shopLkPluginRedirect::redirectBack(self::getAuthUrl());
    }

    // Редирект в первый раздел кабинета
    protected static function redirectToFirstCabinetUrl()
    {
        shopLkPluginRedirect::redirectBack(self::getFirstMenuUrl());
    }

    // Редирект со старого root кабинета на новый
    protected static function redirectFromMovedRoot()
    {
        $segments = self::getSegments();

        if (empty($segments) || self::isDefaultCabinetUrl()) {
            return;
        }

        $old_root = $segments[0];
        $roots    = self::$storefront['redirect_routes'] ?? [];

        if (!is_array($roots) || !in_array($old_root, $roots, true)) {
            return;
        }

        $tail    = array_slice($segments, 1);
        $enabled = self::getEnabledSegments();

        if (!empty($tail) && !empty($enabled[$tail[0]])) {
            shopLkPluginRedirect::redirectBack(
                self::getRootFrontUrl() . implode('/', $tail) . '/'
            );
        }

        self::redirectToFirstCabinetUrl();
    }

    // Редирект из старого кабинета в штатный кабинет Shop-Script
    protected static function redirectFromInactivePlugin()
    {
        $segments = self::getSegments();

        if (empty($segments) || self::isDefaultCabinetUrl()) {
            return;
        }

        $root  = $segments[0];
        $roots = self::$storefront['redirect_routes'] ?? [];

        if (self::$lk_url !== '') {
            $roots[] = self::$lk_url;
        }

        $roots = array_values(array_unique(array_filter((array) $roots)));

        if (self::$lk_url === '' && !empty(self::getEnabledSegments()[$root])) {
            shopLkPluginRedirect::redirectBack(self::getDefaultCabinetFrontUrl());
        }

        if (in_array($root, $roots, true)) {
            shopLkPluginRedirect::redirectBack(self::getDefaultCabinetFrontUrl());
        }
    }

    // Подготовить runtime-контекст текущей витрины
    protected static function prepareContext()
    {
        self::$settings = self::getSettings();

        $storefront = self::getRequestStorefront();

        if (empty($storefront)) {
            $storefront = self::getFirstStorefront();
        }

        $lk_url = trim($storefront['route'] ?? '', '/');

        if ($lk_url === '' && empty($storefront['b2b_mode'])) {
            $lk_url = self::DEFAULT_ROOT_URL;
        }

        if (self::$storefront_key !== ($storefront['key'] ?? '') || self::$lk_url !== $lk_url) {
            self::$menu     = [];
            self::$routes   = [];
            self::$fallback = [];
        }

        self::$storefront     = $storefront;
        self::$storefront_key = $storefront['key'] ?? '';
        self::$lk_url         = $lk_url;
    }

    // Получить витрину текущего запроса
    protected static function getRequestStorefront()
    {
        $settings    = self::getSettings();
        $storefronts = !empty($settings['storefronts']) && is_array($settings['storefronts']) ? $settings['storefronts'] : [];

        $domain   = self::getCurrentDomain();
        $shop_url = self::getCurrentShopUrl();
        $key      = self::getStorefrontKey($domain, $shop_url);
        $routing  = self::getRoutingStorefronts();

        if (!empty($routing[$key])) {
            return self::prepareStorefrontConfig($key, $routing[$key], $storefronts[$key] ?? []);
        }

        return [];
    }

    // Получить первую витрину для служебных вызовов
    protected static function getFirstStorefront()
    {
        $settings    = self::getSettings();
        $storefronts = !empty($settings['storefronts']) && is_array($settings['storefronts']) ? $settings['storefronts'] : [];

        foreach (self::getRoutingStorefronts() as $key => $row) {
            return self::prepareStorefrontConfig($key, $row, $storefronts[$key] ?? []);
        }

        return [];
    }

    // Получить активную витрину текущего домена для canonical auth redirect
    protected static function getEnabledStorefrontForCurrentDomain()
    {
        $domain      = self::getCurrentDomain();
        $settings    = self::getSettings();
        $storefronts = !empty($settings['storefronts']) && is_array($settings['storefronts']) ? $settings['storefronts'] : [];

        foreach (self::getRoutingStorefronts() as $key => $row) {
            if ($row['domain'] !== $domain) {
                continue;
            }

            $config = self::prepareStorefrontConfig($key, $row, $storefronts[$key] ?? []);

            if (!empty($config['enabled'])) {
                return $config;
            }
        }

        return [];
    }

    // Подготовить конфиг витрины
    protected static function prepareStorefrontConfig($key, $row, $config)
    {
        $config = is_array($config) ? $config : [];

        $result = array_merge([
            'key'             => $key,
            'enabled'         => 0,
            'b2b_mode'        => 0,
            'domain'          => $row['domain'],
            'shop_url'        => $row['shop_url'],
            'route'           => self::DEFAULT_ROOT_URL,
            'redirect_routes' => [],
            'sections'        => [],
            'auth'            => [],
        ], $config);

        $result['key']      = $key;
        $result['domain']   = $row['domain'];
        $result['shop_url'] = $row['shop_url'];
        $result['route']    = trim((string) $result['route'], '/');

        if ($result['route'] === '' && empty($result['b2b_mode'])) {
            $result['route'] = self::DEFAULT_ROOT_URL;
        }

        return $result;
    }

    // Получить URL поселения витрины
    protected static function getStorefrontFrontUrl($storefront, $absolute = false)
    {
        $root = wa()->getRootUrl(false);
        $url  = $root . ($storefront['shop_url'] ?? '');

        if (!$absolute) {
            return $url;
        }

        $scheme = waRequest::isHttps() ? 'https://' : 'http://';

        return $scheme . ($storefront['domain'] ?? self::getCurrentDomain()) . $url;
    }

    // Получить URL кабинета витрины
    protected static function getStorefrontCabinetUrl($storefront, $absolute = false)
    {
        $url   = self::getStorefrontFrontUrl($storefront, $absolute);
        $route = trim((string) ($storefront['route'] ?? ''), '/');

        if ($route === '') {
            return $url;
        }

        return $url . $route . '/';
    }

    // Получить текстовый статус витрины
    protected static function getStorefrontStatus($storefront)
    {
        if (empty($storefront['enabled'])) {
            return 'Выкл';
        }

        return !empty($storefront['b2b_mode']) ? 'B2B' : 'Вкл';
    }

    // Получить auth-настройки витрины с дефолтами
    protected static function getPreparedAuth($storefront)
    {
        $auth = !empty($storefront['auth']) && is_array($storefront['auth']) ? $storefront['auth'] : [];

        return array_merge([
            'auth_bg_url'       => '',
            'auth_logo_img_url' => '',
            'auth_logo_text'    => '',
            'auth_logo_slogan'  => '',
            'auth_hero_title'   => '',
            'auth_hero_text'    => '',
            'auth_shop_url'     => '',
        ], $auth);
    }

    // Получить настройки разделов витрины с дефолтами
    protected static function getPreparedSections($storefront)
    {
        $settings = !empty($storefront['sections']) && is_array($storefront['sections']) ? $storefront['sections'] : [];
        $result   = [];

        foreach (self::getNavigationSettingsRows() as $id => $row) {
            $set = $settings[$id] ?? [];

            $result[$id] = [
                'id'             => $id,
                'name'           => trim((string) ($set['name'] ?? '')) !== '' ? $set['name'] : $row['name'],
                'path'           => trim((string) ($set['path'] ?? '')) !== '' ? $set['path'] : $row['path'],
                'icon'           => trim((string) ($set['icon'] ?? '')) !== '' ? $set['icon'] : $row['icon'],
                'enabled'        => !empty($set['enabled']) || !empty($row['always_enabled']),
                'always_enabled' => !empty($row['always_enabled']),
                'show_in_menu'   => !empty($row['show_in_menu']),
            ];
        }

        return $result;
    }

    // Проверить включение раздела
    protected static function isEnabled($key, $nav)
    {
        if (!empty($nav['always_enabled'])) {
            return true;
        }

        $id       = $nav['parent_id'] ?? ($nav['id'] ?? $key);
        $sections = self::$storefront['sections'] ?? [];

        return !empty($sections[$id]['enabled']);
    }

    // Подготовить пункт навигации
    protected static function prepareNavigationItem($key, $nav)
    {
        $id       = $nav['id'] ?? $key;
        $sections = self::$storefront['sections'] ?? [];
        $settings = $sections[$id] ?? [];
        $item     = $nav;

        foreach (['name', 'path', 'icon'] as $field) {
            $default = $nav[$field] ?? ($field === 'icon' ? '' : $key);
            $value   = $settings[$field] ?? $default;

            $item[$field] = trim((string) $value) !== '' ? trim((string) $value) : $default;
        }

        $item['key'] = $key;
        $item['id']  = $id;

        return $item;
    }

    // Получить первый включенный раздел
    protected static function getFirstEnabledSection()
    {
        foreach (self::$navigations as $key => $nav) {
            if (!empty($nav['_param']) || !self::isEnabled($key, $nav)) {
                continue;
            }

            if (isset($nav['show_in_menu']) && empty($nav['show_in_menu'])) {
                continue;
            }

            return self::prepareNavigationItem($key, $nav);
        }

        return null;
    }

    // Проверить нахождение внутри root кабинета
    protected static function isInsideCabinetRoot()
    {
        return self::$lk_url === '' || shopLkPluginUrlSegment::get(1) === self::$lk_url;
    }

    // Получить доступные первые сегменты кабинета
    protected static function getEnabledSegments()
    {
        $segments = [];

        foreach (self::getRoutes() as $route => $params) {
            $parts = explode('/', trim($route, '/'));

            if (self::$lk_url !== '') {
                if (($parts[0] ?? '') === self::$lk_url && !empty($parts[1])) {
                    $segments[$parts[1]] = true;
                }

                continue;
            }

            if (!empty($parts[0])) {
                $segments[$parts[0]] = true;
            }
        }

        return $segments;
    }

    // Получить текущие сегменты внутри поселения Shop-Script
    protected static function getSegments()
    {
        $segments = [];

        for ($i = 1; $i <= 10; $i++) {
            $segment = shopLkPluginUrlSegment::get($i);

            if ($segment === null || $segment === '') {
                break;
            }

            $segments[] = $segment;
        }

        return $segments;
    }

    // Проверить logout-запрос
    protected static function isLogoutRequest()
    {
        return waRequest::get('logout', null) !== null;
    }

    // Проверить штатный кабинет Shop-Script
    protected static function isDefaultCabinetUrl()
    {
        $root    = shopLkPluginUrlSegment::get(1);
        $segment = shopLkPluginUrlSegment::get(2);

        if ($root !== self::DEFAULT_ROOT_URL) {
            return false;
        }

        return $segment === null || $segment === '' || in_array($segment, ['orders', 'order', 'profile', 'affiliate'], true);
    }

    // Получить URL текущего поселения Shop-Script
    protected static function getShopFrontUrl($absolute = false)
    {
        self::prepareContext();

        return self::getStorefrontFrontUrl(self::$storefront, $absolute);
    }

    // Получить текущий домен
    protected static function getCurrentDomain()
    {
        foreach (self::getDomainCandidates() as $domain) {
            $domain = self::normalizeDomain($domain);

            if ($domain !== '') {
                return $domain;
            }
        }

        return '';
    }

    // Получить текущий URL поселения Shop-Script
    protected static function getCurrentShopUrl()
    {
        if (wa()->getEnv() !== 'frontend') {
            return '';
        }

        $route = wa()->getRouting()->getRoute();

        if (!is_array($route) || empty($route['app']) || $route['app'] !== 'shop') {
            return '';
        }

        return self::normalizeShopUrl($route['url'] ?? '');
    }

    // Получить варианты текущего домена
    protected static function getDomainCandidates()
    {
        $domains = [
            wa()->getRouting()->getDomain(null, false, true),
            wa()->getRouting()->getDomain(null, false, false),
            waRequest::server('HTTP_HOST'),
        ];

        $result = [];

        foreach ($domains as $domain) {
            $domain = trim((string) $domain);

            if ($domain !== '') {
                $result[] = $domain;
            }
        }

        return array_values(array_unique($result));
    }

    // Нормализовать URL поселения
    protected static function normalizeShopUrl($url)
    {
        $url = waRouting::clearUrl((string) $url);
        $url = trim($url, '/');

        if ($url === '' || $url === '*' || $url === '?*') {
            return '';
        }

        return $url . '/';
    }

    // Нормализовать URL для сравнения редиректов
    protected static function normalizeComparableUrl($url)
    {
        $parts = parse_url(trim((string) $url));

        if ($parts === false) {
            return '';
        }

        $path  = $parts['path'] ?? '';
        $query = !empty($parts['query']) ? '?' . $parts['query'] : '';

        return $path . $query;
    }
}
