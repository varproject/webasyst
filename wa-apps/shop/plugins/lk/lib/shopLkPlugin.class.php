<?php

class shopLkPlugin extends shopPlugin
{
    // Подключить пользовательские функции и модификаторы
    public function __construct($info)
    {
        parent::__construct($info);

        foreach (['functions.php', 'modifiers.php'] as $file) {
            $path = $this->path . '/lib/config/' . $file;

            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    // Получить ссылку на кабинет текущей витрины
    public static function getFrontUrl($absolute = false)
    {
        $settings = shopLkPluginNavigation::getSettings();

        if (empty($settings['main']['enabled'])) {
            return shopLkPluginNavigation::getDefaultCabinetFrontUrl($absolute);
        }

        return shopLkPluginNavigation::getRootFrontUrl($absolute);
    }

    // Нормализовать и сохранить настройки плагина
    public function saveSettings($settings = [])
    {
        $old_settings = $this->getSettings() ?: [];

        if (empty($settings['main']) || !is_array($settings['main'])) {
            $settings['main'] = [];
        }

        $settings['main']['enabled'] = !empty($settings['main']['enabled']) ? 1 : 0;
        $settings['storefronts']     = $this->prepareStorefronts($settings['storefronts'] ?? [], $old_settings);

        if (!empty($settings['main']['enabled']) && !$this->hasEnabledStorefront($settings['storefronts'])) {
            throw new waException('Включите хотя бы одну витрину Shop-Script');
        }

        parent::saveSettings($settings);

        // Снимок нужен для редиректа со старых URL, если плагин выключили в Installer
        $model = new waAppSettingsModel();
        $model->set('shop', 'lk_runtime_settings', json_encode($settings));

        shopLkPluginNavigation::reset($settings);

        return [
            'main_cabinet_url' => self::getFrontUrl(true),
        ];
    }

    // Подготовить настройки витрин
    protected function prepareStorefronts($input_storefronts, $old_settings)
    {
        $input_storefronts = is_array($input_storefronts) ? $input_storefronts : [];
        $old_storefronts   = !empty($old_settings['storefronts']) && is_array($old_settings['storefronts'])
            ? $old_settings['storefronts']
            : [];

        $result = [];

        foreach (shopLkPluginNavigation::getRoutingStorefronts() as $row) {
            $key   = $row['key'];
            $input = $input_storefronts[$key] ?? [];
            $old   = $old_storefronts[$key] ?? [];

            $b2b_mode  = !empty($input['b2b_mode']) ? 1 : 0;
            $old_route = $this->prepareRoute($old['route'] ?? shopLkPluginNavigation::DEFAULT_ROOT_URL, !empty($old['b2b_mode']));
            $route     = $this->prepareRoute($input['route'] ?? '', $b2b_mode);

            $result[$key] = [
                'enabled'         => !empty($input['enabled']) ? 1 : 0,
                'b2b_mode'        => $b2b_mode,
                'domain'          => $row['domain'],
                'shop_url'        => $row['shop_url'],
                'route'           => $route,
                'redirect_routes' => $this->prepareRedirectRoutes($old['redirect_routes'] ?? [], $old_route, $route),
                'sections'        => $this->prepareSections($input['sections'] ?? []),
                'auth'            => $this->prepareAuth($input['auth'] ?? []),
            ];
        }

        return $result;
    }

    // Подготовить route кабинета
    protected function prepareRoute($route, $is_b2b)
    {
        $route = shopLkPluginSlugify::generate($route, false, 64, '-');

        if ($route === '' && !$is_b2b) {
            return shopLkPluginNavigation::DEFAULT_ROOT_URL;
        }

        return $route;
    }

    // Подготовить старые root-адреса для редиректа
    protected function prepareRedirectRoutes($routes, $old_route, $route)
    {
        $routes = is_array($routes) ? $routes : [];

        if ($old_route !== '' && $old_route !== $route) {
            $routes[] = $old_route;
        }

        $result = [];

        foreach ($routes as $item) {
            $item = shopLkPluginSlugify::generate($item, false, 64, '-');

            if ($item !== '' && $item !== $route) {
                $result[] = $item;
            }
        }

        return array_slice(array_values(array_unique($result)), -5);
    }

    // Подготовить настройки разделов витрины
    protected function prepareSections($sections)
    {
        $sections = is_array($sections) ? $sections : [];
        $result   = [];

        foreach (shopLkPluginNavigation::getNavigationSettingsRows() as $id => $row) {
            $section = $sections[$id] ?? [];

            $result[$id] = [
                'enabled' => !empty($section['enabled']) || !empty($row['always_enabled']) ? 1 : 0,
                'name'    => isset($section['name']) ? trim((string) $section['name']) : '',
                'path'    => isset($section['path']) ? shopLkPluginSlugify::generate($section['path'], false, 64, '-') : '',
                'icon'    => isset($section['icon']) ? shopLkPluginGetIcon::font($section['icon']) : '',
            ];
        }

        $result['profile']['enabled'] = 1;

        return $result;
    }

    // Подготовить настройки страницы авторизации витрины
    protected function prepareAuth($auth)
    {
        $auth = is_array($auth) ? $auth : [];

        return [
            'auth_bg_url'       => trim((string) ($auth['auth_bg_url'] ?? '')),
            'auth_logo_img_url' => trim((string) ($auth['auth_logo_img_url'] ?? '')),
            'auth_logo_text'    => trim((string) ($auth['auth_logo_text'] ?? '')),
            'auth_logo_slogan'  => trim((string) ($auth['auth_logo_slogan'] ?? '')),
            'auth_hero_title'   => trim((string) ($auth['auth_hero_title'] ?? '')),
            'auth_hero_text'    => trim((string) ($auth['auth_hero_text'] ?? '')),
            'auth_shop_url'     => trim((string) ($auth['auth_shop_url'] ?? '')),
        ];
    }

    // Проверить, есть ли хотя бы одна активная витрина
    protected function hasEnabledStorefront($storefronts)
    {
        foreach ($storefronts as $row) {
            if (!empty($row['enabled'])) {
                return true;
            }
        }

        return false;
    }
}
