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
        self::$menu     = [];
        self::$routes   = [];
        self::$tabs     = [];
        self::$fallback = [];
        self::$lk_url   = '';

        if ($settings === null) {
            self::$settings        = [];
            self::$settings_loaded = false;
            return;
        }

        self::$settings        = $settings;
        self::$settings_loaded = true;
    }

    // Получить URL кабинета
    public static function getRootFrontUrl($absolute = false)
    {
        self::prepareContext();

        $url = self::getShopFrontUrl($absolute);

        return self::$lk_url === '' ? $url : $url . self::$lk_url . '/';
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

    // Получить роуты кабинета
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

    // Получить меню кабинета
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

        $tabs = [
            [
                'id'   => 'main',
                'name' => 'Плагин',
                'hash' => '#/lk',
                'file' => 'SettingsTabMain.html',
            ],
            [
                'id'   => 'auth',
                'name' => 'Авторизация',
                'hash' => '#/lk/auth/',
                'file' => 'SettingsTabAuth.html',
            ],
        ];

        foreach (self::$navigations as $key => $nav) {
            if (!empty($nav['_param'])) {
                continue;
            }

            $id = $nav['id'] ?? $key;

            $tabs[] = [
                'id'   => $id,
                'name' => $nav['name'],
                'hash' => '#/lk/' . $id,
                'file' => 'SettingsTab' . ucfirst($id) . '.html',
            ];
        }

        self::$tabs = $tabs;

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

        if (empty(self::$settings['main']['enabled']) || !isset($plugins['lk']) || !self::isCurrentDomainEnabled()) {
            if (wa()->getEnv() === 'frontend') {
                self::redirectFromInactivePlugin();
            }

            return null;
        }

        if (wa()->getEnv() === 'frontend' && !self::isLogoutRequest() && wa()->getUser()->isAuth()) {
            self::redirectFromMovedRoot();
        }

        if (!empty(self::$settings['main']['b2b_mode'])) {
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

    // Редирект auth-страниц на каноническое поселение магазина
    public static function redirectAuthToCanonical()
    {
        if (wa()->getEnv() !== 'frontend' || self::isLogoutRequest()) {
            return;
        }

        $segment = shopLkPluginUrlSegment::get(1);

        if (!in_array($segment, ['login', 'signup', 'forgotpassword', 'setpassword'], true)) {
            return;
        }

        $query   = (string) waRequest::server('QUERY_STRING', '');
        $target  = self::getShopFrontUrl() . $segment . '/';
        $target .= $query !== '' ? '?' . $query : '';

        if (self::normalizeComparableUrl($target) === self::normalizeComparableUrl((string) waRequest::server('REQUEST_URI', ''))) {
            return;
        }

        shopLkPluginRedirect::redirectBack($target);
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

        if (!in_array($old_root, self::getRedirectRoots(), true)) {
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
        $roots = self::getRedirectRoots();

        if (self::$lk_url !== '') {
            $roots[] = self::$lk_url;
        }

        $roots = array_values(array_unique(array_filter($roots)));

        if (self::$lk_url === '' && !empty(self::getEnabledSegments()[$root])) {
            shopLkPluginRedirect::redirectBack(self::getDefaultCabinetFrontUrl());
        }

        if (in_array($root, $roots, true)) {
            shopLkPluginRedirect::redirectBack(self::getDefaultCabinetFrontUrl());
        }
    }

    // Проверить доступность текущего домена
    protected static function isCurrentDomainEnabled()
    {
        $mode = self::$settings['main']['domain_mode'] ?? 'all';

        if ($mode !== 'selected') {
            return true;
        }

        $domains = self::$settings['main']['domains'] ?? [];

        if (empty($domains) || !is_array($domains)) {
            return false;
        }

        $allowed = [];

        foreach ($domains as $domain) {
            $domain = self::normalizeDomain($domain);

            if ($domain !== '') {
                $allowed[$domain] = true;
            }
        }

        foreach (self::getDomainCandidates() as $domain) {
            $domain = self::normalizeDomain($domain);

            if ($domain !== '' && !empty($allowed[$domain])) {
                return true;
            }
        }

        return false;
    }

    // Подготовить runtime-контекст
    protected static function prepareContext()
    {
        self::$settings = self::getSettings();

        $lk_url = trim(self::$settings['main']['route'] ?? '', '/');

        if ($lk_url === '' && empty(self::$settings['main']['b2b_mode'])) {
            $lk_url = self::DEFAULT_ROOT_URL;
        }

        if (self::$lk_url !== $lk_url) {
            self::$menu     = [];
            self::$routes   = [];
            self::$fallback = [];
        }

        self::$lk_url = $lk_url;
    }

    // Проверить включение раздела
    protected static function isEnabled($key, $nav)
    {
        if (!empty($nav['always_enabled'])) {
            return true;
        }

        $id = $nav['parent_id'] ?? ($nav['id'] ?? $key);

        return !empty(self::$settings['sections'][$id]['enabled']);
    }

    // Подготовить пункт навигации
    protected static function prepareNavigationItem($key, $nav)
    {
        $id       = $nav['id'] ?? $key;
        $settings = self::$settings['sections'][$id] ?? [];
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

    // Получить старые root-адреса
    protected static function getRedirectRoots()
    {
        $routes = self::$settings['main']['redirect_routes'] ?? [];

        if (empty($routes) || !is_array($routes)) {
            return [];
        }

        $result = [];

        foreach ($routes as $route) {
            $route = trim((string) $route, '/');

            if ($route !== '' && $route !== self::$lk_url) {
                $result[] = $route;
            }
        }

        return array_values(array_unique($result));
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

    // Получить URL поселения Shop-Script
    protected static function getShopFrontUrl($absolute = false)
    {
        $root_url       = wa()->getRootUrl($absolute);
        $current_prefix = null;
        $prefixes       = [];

        if (wa()->getEnv() === 'frontend') {
            $route = wa()->getRouting()->getRoute();

            if (is_array($route) && !empty($route['app']) && $route['app'] === 'shop') {
                $current_prefix = self::normalizeShopPrefix($route['url'] ?? '');

                if ($current_prefix !== '') {
                    return $root_url . $current_prefix;
                }

                $prefixes[] = '';
            }
        }

        foreach (self::getDomainCandidates() as $domain) {
            $routes = wa()->getRouting()->getRoutes($domain);

            foreach ((array) $routes as $route) {
                if (!empty($route['app']) && $route['app'] === 'shop') {
                    $prefixes[] = self::normalizeShopPrefix($route['url'] ?? '');
                }
            }
        }

        $prefixes = array_values(array_unique($prefixes));

        foreach ($prefixes as $prefix) {
            if ($prefix !== '') {
                return $root_url . $prefix;
            }
        }

        return $root_url . ($current_prefix ?? '');
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
    protected static function normalizeShopPrefix($url)
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
