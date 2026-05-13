<?php

/**
 * Apanel
 *
 * @author Vagram Petrosian <var_project@mail.ru>
 * @copyright 2026 «Apanel»
 * @link http://wapv.ru
 *
 * apanelStorefrontSettingsService
 *
 * Сервис подготовки и сохранения настроек витрин приложения Apanel.
 *
 * Назначение:
 * - получить поселения приложения из apanelRoutingService;
 * - связать каждое поселение с настройками из apanelSettings;
 * - хранить настройки каждой витрины в отдельной области scope=storefront, scope_id={storefront_key};
 * - собрать готовые строки для таблицы витрин;
 * - читать и сохранять группы настроек конкретной витрины;
 * - выполнять массовое сохранение настроек для нескольких витрин;
 * - работать с выбранным plugin-продуктом и его frontend screens;
 * - централизованно применять access/auth policy выбранного plugin-продукта.
 *
 * Зависимости:
 * - apanelRoutingService;
 * - apanelStorefrontPluginRegistry;
 * - apanelStorefrontScreenRegistry;
 * - apanelSettings;
 * - ifset();
 * - ifempty().
 *
 * Инварианты:
 * - источник роутов — wa-config/routing.php через apanelRoutingService;
 * - route/domain/url/full_url не дублируются в настройках;
 * - storefront_key хранится в scope_id, а не внутри JSON;
 * - одна группа настроек витрины = одна строка apanel_settings;
 * - главный выбор функционального продукта хранится в scope=storefront, scope_id={key}, name=plugin;
 * - frontend screens берутся из выбранного plugin-продукта;
 * - access/auth policy применяется одинаково в backend save и frontend runtime;
 * - template, scheme, scenario и theme-ветки не используются.
 */
final class apanelStorefrontSettingsService
{
    const SETTINGS_PATH = 'storefronts';
    const DEFAULTS_PATH = 'storefront_defaults';
    const SCOPE = 'storefront';

    protected $routing_service;
    protected $plugin_registry;
    protected $screen_registry;

    /**
     * Конструктор сервиса.
     *
     * @param apanelRoutingService|null $routing_service Сервис роутинга.
     * @param apanelStorefrontPluginRegistry|null $plugin_registry Реестр plugin-продуктов.
     * @param apanelStorefrontScreenRegistry|null $screen_registry Реестр screens.
     */
    public function __construct($routing_service = null, $plugin_registry = null, $screen_registry = null)
    {
        $this->routing_service = $routing_service ?: new apanelRoutingService();
        $this->plugin_registry = $plugin_registry ?: new apanelStorefrontPluginRegistry();
        $this->screen_registry = $screen_registry ?: new apanelStorefrontScreenRegistry($this->plugin_registry);
    }

    /**
     * Возвращает домены и подготовленные витрины активного домена.
     *
     * @param string|null $domain_hash Хеш домена.
     * @return array
     */
    public function getDomainStorefronts($domain_hash = null)
    {
        $items = $this->routing_service->getAppDomainsRoutes($domain_hash);

        if (empty($items['result'])) {
            return $items;
        }

        $items['routes'] = $this->prepareRoutes(ifset($items['routes'], []));

        return $items;
    }

    /**
     * Собирает ключ витрины.
     *
     * @param string $domain_hash Хеш домена.
     * @param int|string $route_id ID правила маршрутизации.
     * @return string
     */
    public function getStorefrontKey($domain_hash, $route_id)
    {
        return $domain_hash . '_' . $route_id;
    }

    /**
     * Возвращает настройки витрины или одной группы настроек.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string|null $group Группа настроек.
     * @param bool $with_defaults Добавлять дефолтные настройки.
     * @return array
     */
    public function getSettings($storefront_key, $group = null, $with_defaults = true)
    {
        if ($group) {
            $settings = apanelSettings::getScoped(self::SCOPE, $storefront_key, $group, []);

            if (!is_array($settings)) {
                $settings = [];
            }

            if (!$with_defaults) {
                return $settings;
            }

            return array_replace_recursive($this->getDefaults($group), $settings);
        }

        $settings = apanelSettings::getScoped(self::SCOPE, $storefront_key, null, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        if (!$with_defaults) {
            return $settings;
        }

        return array_replace_recursive($this->getDefaults(), $settings);
    }

    /**
     * Сохраняет настройки витрины или конкретной группы.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string|null $group Группа настроек.
     * @param array $settings Настройки.
     * @param bool $replace Полностью заменить значение.
     * @return bool|int|array
     */
    public function saveSettings($storefront_key, $group, $settings, $replace = true)
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        if ($group === null) {
            apanelSettings::deleteScoped(self::SCOPE, $storefront_key);

            $result = [];

            foreach ($settings as $name => $value) {
                $name = trim((string) $name);

                if ($name === '') {
                    continue;
                }

                $result[$name] = apanelSettings::saveScoped(self::SCOPE, $storefront_key, $name, $value, true);
            }

            return $result;
        }

        return apanelSettings::saveScoped(self::SCOPE, $storefront_key, $group, $settings, $replace);
    }

    /**
     * Удаляет все настройки витрины или одну группу.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string|null $group Группа настроек.
     * @return bool|int
     */
    public function deleteSettings($storefront_key, $group = null)
    {
        return apanelSettings::deleteScoped(self::SCOPE, $storefront_key, $group);
    }

    /**
     * Массово сохраняет группу настроек для нескольких витрин.
     *
     * @param array $storefront_keys Ключи витрин.
     * @param string $group Группа настроек.
     * @param array $settings Настройки.
     * @param bool $replace Полностью заменить значение.
     * @return array
     */
    public function saveSettingsMass($storefront_keys, $group, $settings, $replace = true)
    {
        $result = [];

        foreach ((array) $storefront_keys as $storefront_key) {
            $storefront_key = trim((string) $storefront_key);
            $prepared = $this->normalizeGroupSettings($storefront_key, $group, $settings);

            $result[$storefront_key] = $this->saveSettings($storefront_key, $group, $prepared, $replace);
        }

        return $result;
    }

    /**
     * Сохраняет plugin-продукт витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string $plugin_id ID plugin-продукта.
     * @param array $plugin_settings Настройки plugin-продукта.
     * @return bool|int
     * @throws waException
     */
    public function savePluginSettings($storefront_key, $plugin_id, $plugin_settings = [])
    {
        $plugin_id = trim((string) $plugin_id);

        if ($plugin_id !== '' && !$this->plugin_registry->hasPlugin($plugin_id)) {
            throw new waException('Плагин витрины не найден.', 400);
        }

        $current = $this->getSettings($storefront_key, 'plugin', false);
        $current_id = trim((string) ifset($current['id'], ''));
        $current_settings = is_array(ifset($current['settings'], [])) ? $current['settings'] : [];

        $settings = [
            'id'       => $plugin_id,
            'settings' => $plugin_id !== ''
                ? $this->plugin_registry->normalizePluginSettings($plugin_id, $plugin_settings, $current_settings)
                : [],
        ];

        $result = $this->saveSettings($storefront_key, 'plugin', $settings, true);

        if ($plugin_id !== $current_id) {
            $this->saveSettings($storefront_key, 'screens', [], true);
            $this->refreshPolicySettings($storefront_key, $plugin_id);
        }

        return $result;
    }

    /**
     * Возвращает screens витрины с учётом выбранного plugin и настроек.
     *
     * @param string $storefront_key Ключ витрины.
     * @return array
     */
    public function getScreens($storefront_key)
    {
        $settings = $this->getSettings($storefront_key);
        $plugin_id = $this->getPluginId($settings);

        if ($plugin_id === '' || !$this->plugin_registry->hasPlugin($plugin_id)) {
            return [];
        }

        return $this->screen_registry->getPluginScreens($plugin_id, $settings, $storefront_key);
    }

    /**
     * Возвращает ID выбранного plugin-продукта витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @return string
     */
    public function getSelectedPluginId($storefront_key)
    {
        $settings = $this->getSettings($storefront_key, 'plugin', false);

        return trim((string) ifset($settings['id'], ''));
    }

    /**
     * Возвращает выбранный plugin-продукт витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @return array
     */
    public function getSelectedPlugin($storefront_key)
    {
        $plugin_id = $this->getSelectedPluginId($storefront_key);

        if ($plugin_id === '') {
            return [];
        }

        return $this->plugin_registry->getPlugin($plugin_id, []);
    }

    /**
     * Нормализует группу настроек витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string $group Группа настроек.
     * @param array $settings Настройки.
     * @return array
     */
    public function normalizeGroupSettings($storefront_key, $group, $settings)
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        if ($group === 'access') {
            return $this->normalizeAccessSettings($settings, $this->getSelectedPlugin($storefront_key));
        }

        if ($group === 'auth') {
            return $this->normalizeAuthSettings($settings, $this->getSelectedPlugin($storefront_key));
        }

        return $settings;
    }

    /**
     * Применяет policy выбранного plugin-продукта к полным настройкам витрины.
     *
     * @param array $settings Полные настройки витрины.
     * @param array $plugin Декларация plugin-продукта.
     * @return array
     */
    public function applyPluginPolicy($settings, $plugin)
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        if (!$plugin) {
            return $settings;
        }

        $settings['access'] = $this->normalizeAccessSettings(ifset($settings['access'], []), $plugin);
        $settings['auth'] = $this->normalizeAuthSettings(ifset($settings['auth'], []), $plugin);

        return $settings;
    }

    /**
     * Нормализует настройки доступа с учётом policy выбранного plugin.
     *
     * @param array $settings Настройки access.
     * @param array $plugin Декларация plugin-продукта.
     * @return array
     */
    public function normalizeAccessSettings($settings, $plugin = [])
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        return [
            'mode'     => $this->normalizeAccessMode(ifset($settings['mode'], 'public'), $plugin),
            'groups'   => $this->prepareIds(ifset($settings['groups'], [])),
            'contacts' => $this->prepareIds(ifset($settings['contacts'], [])),
        ];
    }

    /**
     * Нормализует настройки авторизации с учётом policy выбранного plugin.
     *
     * @param array $settings Настройки auth.
     * @param array $plugin Декларация plugin-продукта.
     * @return array
     */
    public function normalizeAuthSettings($settings, $plugin = [])
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        $auth_policy = is_array(ifset($plugin['auth'], [])) ? $plugin['auth'] : [];

        return [
            'enabled'              => !empty($auth_policy['required']) ? 1 : (!empty($settings['enabled']) ? 1 : 0),
            'registration_enabled' => $this->isRegistrationAllowed($plugin) && !empty($settings['registration_enabled']) ? 1 : 0,
            'login_by'             => $this->normalizeLoginBy(ifset($settings['login_by'], ifset($auth_policy['login_by'], 'email')), $plugin),
            'after_login_url'      => trim((string) ifset($settings['after_login_url'], '')),
            'after_logout_url'     => trim((string) ifset($settings['after_logout_url'], '')),
        ];
    }

    /**
     * Возвращает режимы доступа с учётом policy выбранного plugin.
     *
     * @param array $plugin Декларация plugin-продукта.
     * @return array
     */
    public function getAccessModes($plugin)
    {
        $modes = [
            'public'     => 'Публичная витрина',
            'authorized' => 'Только авторизованные',
            'groups'     => 'Только группы пользователей',
            'contacts'   => 'Только выбранные пользователи',
            'closed'     => 'Закрыта полностью',
        ];

        $allowed = ifset($plugin['access']['allowed_modes'], []);

        if (!is_array($allowed) || !$allowed) {
            return $modes;
        }

        return array_intersect_key($modes, array_flip($allowed));
    }

    /**
     * Возвращает варианты логина с учётом policy выбранного plugin.
     *
     * @param array $plugin Декларация plugin-продукта.
     * @return array
     */
    public function getAuthLoginByOptions($plugin)
    {
        $options = [
            'email' => 'Email',
            'phone' => 'Телефон',
            'login' => 'Логин',
        ];

        $allowed = ifset($plugin['auth']['allowed_login_by'], []);

        if (!is_array($allowed) || !$allowed) {
            return $options;
        }

        return array_intersect_key($options, array_flip($allowed));
    }

    /**
     * Проверяет, разрешает ли plugin регистрацию.
     *
     * @param array $plugin Декларация plugin-продукта.
     * @return bool
     */
    public function isRegistrationAllowed($plugin)
    {
        if (!$plugin) {
            return true;
        }

        $auth = ifset($plugin['auth'], []);

        if (!is_array($auth)) {
            return true;
        }

        if (array_key_exists('registration_allowed', $auth)) {
            return !empty($auth['registration_allowed']);
        }

        return true;
    }

    /**
     * Применяет policy выбранного plugin к сохранённым access/auth настройкам.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string $plugin_id ID plugin-продукта.
     * @return void
     */
    protected function refreshPolicySettings($storefront_key, $plugin_id)
    {
        $plugin = $plugin_id !== '' ? $this->plugin_registry->getPlugin($plugin_id, []) : [];

        if (!$plugin) {
            return;
        }

        $access = $this->normalizeAccessSettings($this->getSettings($storefront_key, 'access'), $plugin);
        $auth = $this->normalizeAuthSettings($this->getSettings($storefront_key, 'auth'), $plugin);

        $this->saveSettings($storefront_key, 'access', $access, true);
        $this->saveSettings($storefront_key, 'auth', $auth, true);
    }

    /**
     * Нормализует режим доступа с учётом policy выбранного plugin.
     *
     * @param string $mode Режим доступа.
     * @param array $plugin Декларация plugin-продукта.
     * @return string
     */
    protected function normalizeAccessMode($mode, $plugin)
    {
        $mode = trim((string) $mode);
        $base_modes = ['public', 'authorized', 'groups', 'contacts', 'closed'];

        if (!in_array($mode, $base_modes, true)) {
            $mode = 'public';
        }

        $allowed = ifset($plugin['access']['allowed_modes'], []);

        if (!is_array($allowed) || !$allowed) {
            return $mode;
        }

        $allowed = array_values(array_intersect($base_modes, array_map('strval', $allowed)));

        if (!$allowed) {
            return $mode;
        }

        if (in_array($mode, $allowed, true)) {
            return $mode;
        }

        $default_mode = (string) ifset($plugin['access']['default_mode'], '');

        return in_array($default_mode, $allowed, true) ? $default_mode : reset($allowed);
    }

    /**
     * Нормализует тип логина с учётом policy выбранного plugin.
     *
     * @param string $login_by Тип логина.
     * @param array $plugin Декларация plugin-продукта.
     * @return string
     */
    protected function normalizeLoginBy($login_by, $plugin)
    {
        $login_by = trim((string) $login_by);
        $base = ['email', 'phone', 'login'];

        if (!in_array($login_by, $base, true)) {
            $login_by = 'email';
        }

        $allowed = ifset($plugin['auth']['allowed_login_by'], []);

        if (!is_array($allowed) || !$allowed) {
            return $login_by;
        }

        $allowed = array_values(array_intersect($base, array_map('strval', $allowed)));

        if (!$allowed) {
            return $login_by;
        }

        if (in_array($login_by, $allowed, true)) {
            return $login_by;
        }

        $default_login_by = (string) ifset($plugin['auth']['login_by'], 'email');

        return in_array($default_login_by, $allowed, true) ? $default_login_by : reset($allowed);
    }

    /**
     * Подготавливает список ID из строки или массива.
     *
     * @param mixed $value Список ID.
     * @return array
     */
    protected function prepareIds($value)
    {
        $result = [];

        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        foreach ($items as $id) {
            $id = (int) trim((string) $id);

            if ($id > 0) {
                $result[] = $id;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Подготавливает роуты активного домена для таблицы.
     *
     * @param array $routes Роуты из apanelRoutingService.
     * @return array
     */
    protected function prepareRoutes($routes)
    {
        foreach ($routes as $domain => &$domain_routes) {
            foreach ($domain_routes as $route_id => &$route) {
                $route['route_id'] = ifset($route['route_id'], $route_id);
                $route = $this->prepareRoute($route);
            }

            unset($route);
        }

        unset($domain_routes);

        return $routes;
    }

    /**
     * Подготавливает одну строку витрины.
     *
     * @param array $route Данные поселения.
     * @return array
     */
    protected function prepareRoute($route)
    {
        $domain_hash = ifset($route['domain_hash'], '');
        $route_id = ifset($route['route_id'], '');

        $storefront_key = $this->getStorefrontKey($domain_hash, $route_id);
        $settings = $this->getSettings($storefront_key);
        $profile = ifset($settings['profile'], []);
        $plugin_id = $this->getPluginId($settings);

        $route['_name'] = ifempty($profile['name'], ifset($route['_name'], ''));
        $route['status'] = !isset($profile['enabled']) || !empty($profile['enabled']);

        $route['storefront_key'] = $storefront_key;
        $route['plugin_id'] = $plugin_id;
        $route['plugin_label'] = $this->getPluginLabel($plugin_id);
        $route['screens_label'] = $this->getScreensLabel($plugin_id, $settings, $storefront_key);
        $route['access_label'] = $this->getAccessLabel($settings);
        $route['auth_label'] = $this->getAuthLabel($settings);

        return $route;
    }

    /**
     * Возвращает ID выбранного plugin-продукта.
     *
     * @param array $settings Настройки витрины.
     * @return string
     */
    protected function getPluginId($settings)
    {
        return trim((string) ifset($settings['plugin']['id'], ''));
    }

    /**
     * Возвращает подпись выбранного plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @return string
     */
    protected function getPluginLabel($plugin_id)
    {
        $plugin_id = trim((string) $plugin_id);

        if ($plugin_id === '') {
            return 'Плагин не выбран';
        }

        $plugin = $this->plugin_registry->getPlugin($plugin_id, []);

        if (!$plugin) {
            return 'Плагин недоступен: ' . $plugin_id;
        }

        return ifempty($plugin['name'], $plugin_id);
    }

    /**
     * Возвращает подпись screens выбранного plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param array $settings Настройки витрины.
     * @param string $storefront_key Ключ витрины.
     * @return string
     */
    protected function getScreensLabel($plugin_id, $settings, $storefront_key)
    {
        if ($plugin_id === '' || !$this->plugin_registry->hasPlugin($plugin_id)) {
            return '—';
        }

        $screens = $this->screen_registry->getPluginScreens($plugin_id, $settings, $storefront_key);

        if (!$screens) {
            return 'Нет экранов';
        }

        $enabled = 0;

        foreach ($screens as $screen) {
            if (!empty($screen['enabled'])) {
                $enabled++;
            }
        }

        return $enabled . ' из ' . count($screens);
    }

    /**
     * Возвращает подпись доступов.
     *
     * @param array $settings Настройки витрины.
     * @return string
     */
    protected function getAccessLabel($settings)
    {
        $mode = ifset($settings['access']['mode'], 'public');

        switch ($mode) {
            case 'authorized':
                return 'Авторизованные';

            case 'groups':
                return 'Группы';

            case 'contacts':
                return 'Пользователи';

            case 'closed':
                return 'Закрыта';

            case 'public':
            default:
                return 'Публичная';
        }
    }

    /**
     * Возвращает подпись авторизации.
     *
     * @param array $settings Настройки витрины.
     * @return string
     */
    protected function getAuthLabel($settings)
    {
        if (empty($settings['auth']['enabled'])) {
            return 'Выключена';
        }

        if (!empty($settings['auth']['registration_enabled'])) {
            return 'Вход + регистрация';
        }

        return 'Только вход';
    }

    /**
     * Возвращает дефолтные настройки.
     *
     * @param string|null $group Группа настроек.
     * @return array
     */
    protected function getDefaults($group = null)
    {
        $defaults = apanelSettings::get(self::DEFAULTS_PATH, []);

        if (!is_array($defaults) || !$defaults) {
            $defaults = $this->getFallbackDefaults();
        }

        if ($group) {
            return ifset($defaults[$group], []);
        }

        return $defaults;
    }

    /**
     * Возвращает резервные дефолты, если они еще не сохранены в БД.
     *
     * @return array
     */
    protected function getFallbackDefaults()
    {
        return [
            'profile' => [
                'enabled'     => 1,
                'name'        => '',
                'description' => '',
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
                'after_login_url'      => '',
                'after_logout_url'     => '',
            ],
            'ui'       => [],
            'data'     => [],
            'seo'      => [],
            'advanced' => [],
        ];
    }
}
