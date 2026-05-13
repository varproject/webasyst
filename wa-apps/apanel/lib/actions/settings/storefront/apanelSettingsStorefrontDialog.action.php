<?php

/**
 * apanelSettingsStorefrontDialogAction
 *
 * Открывает окно настройки группы витрины.
 *
 * Назначение:
 * - принять storefront_key и group;
 * - проверить базовую корректность;
 * - получить настройки группы;
 * - подготовить данные для шаблона диалога;
 * - собрать plugin-продукты, plugin settings fields и screens выбранного plugin;
 * - учесть access/auth policy выбранного plugin-продукта.
 */
class apanelSettingsStorefrontDialogAction extends waViewAction
{
    /**
     * Выполняет подготовку диалога.
     *
     * @return void
     * @throws waException
     */
    public function execute()
    {
        $this->setTemplate('settings/storefront/SettingsStorefrontDialog.html', true);

        $storefront_key = waRequest::get('storefront_key', '', waRequest::TYPE_STRING_TRIM);
        $group = waRequest::get('group', '', waRequest::TYPE_STRING_TRIM);

        if (!$this->isValidStorefrontKey($storefront_key)) {
            throw new waException('Некорректный ключ витрины.', 400);
        }

        if (!$this->isAllowedGroup($group)) {
            throw new waException('Некорректная группа настроек.', 400);
        }

        $service = new apanelStorefrontSettingsService();
        $registry = new apanelStorefrontPluginRegistry();
        $settings = $service->getSettings($storefront_key, $group);
        $storefront_settings = $service->getSettings($storefront_key);
        $plugin_id = trim((string) ifset($storefront_settings['plugin']['id'], ''));
        $plugin = $registry->getPlugin($plugin_id, []);
        $settings_json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $assign = [
            'storefront_key' => $storefront_key,
            'group'          => $group,
            'settings_json'  => $settings_json,
            'settings'       => $settings,

            'modal_title'      => $this->getModalTitle($group),
            'modal_size'       => 'modal-lg',
            'post_action_url'  => wa()->getAppUrl('apanel') . '?module=settings&action=storefrontSave',
            'close_button_url' => '/' . wa()->getRouting()->getCurrentUrl(),
            'save_button_name' => 'Сохранить',
        ];

        if ($group === 'profile') {
            $assign['profile'] = $settings;
        }

        if ($group === 'plugin') {
            $selected_plugin_id = trim((string) ifset($settings['id'], ''));
            $selected_plugin = $registry->getPlugin($selected_plugin_id, []);
            $plugin_settings = is_array(ifset($settings['settings'], [])) ? $settings['settings'] : [];

            $assign['plugins'] = $registry->getPlugins();
            $assign['plugin_id'] = $selected_plugin_id;
            $assign['selected_plugin'] = $selected_plugin;
            $assign['plugin_fields'] = $registry->getPluginFields($selected_plugin_id);
            $assign['plugin_settings'] = array_replace(
                $registry->getPluginSettingsDefaults($selected_plugin_id),
                $plugin_settings
            );
        }

        if ($group === 'screens') {
            $assign['plugin_id'] = $plugin_id;
            $assign['screens'] = $service->getScreens($storefront_key);
        }

        if ($group === 'access') {
            $assign['access'] = $settings;
            $assign['access_modes'] = $service->getAccessModes($plugin);
            $assign['selected_plugin'] = $plugin;
        }

        if ($group === 'auth') {
            $assign['auth'] = $settings;
            $assign['auth_login_by_options'] = $service->getAuthLoginByOptions($plugin);
            $assign['auth_required'] = !empty($plugin['auth']['required']);
            $assign['registration_allowed'] = $service->isRegistrationAllowed($plugin);
            $assign['selected_plugin'] = $plugin;
        }

        $this->view->assign($assign);
    }

    /**
     * Проверяет ключ витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @return bool
     */
    protected function isValidStorefrontKey($storefront_key)
    {
        return (bool) preg_match('/^[a-f0-9]{8}_[0-9]+$/', $storefront_key);
    }

    /**
     * Проверяет группу настроек.
     *
     * @param string $group Группа настроек.
     * @return bool
     */
    protected function isAllowedGroup($group)
    {
        return in_array($group, [
            'profile',
            'plugin',
            'screens',
            'access',
            'auth',
            'ui',
            'data',
            'seo',
            'advanced',
        ], true);
    }

    /**
     * Возвращает заголовок модального окна.
     *
     * @param string $group Группа настроек.
     * @return string
     */
    protected function getModalTitle($group)
    {
        switch ($group) {
            case 'profile':
                return 'Профиль витрины';

            case 'plugin':
                return 'Плагин витрины';

            case 'screens':
                return 'Экраны витрины';

            case 'access':
                return 'Доступы витрины';

            case 'auth':
                return 'Авторизация витрины';

            default:
                return 'Настройки витрины';
        }
    }
}
