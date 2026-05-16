<?php

/**
 * apanelSettingsStorefrontSaveController
 *
 * Сохраняет группу настроек витрины.
 *
 * Назначение:
 * - принять storefront_key;
 * - принять group;
 * - сохранить данные в storefronts.{key}.{group};
 * - валидировать выбор plugin-продукта;
 * - сохранять settings fields выбранного plugin-продукта;
 * - сохранять frontend screens выбранной витрины;
 * - применять access/auth policy выбранного plugin-продукта через apanelStorefrontSettingsService.
 *
 * Зависимости:
 * - waJsonController;
 * - waRequest;
 * - apanelStorefrontSettingsService;
 * - apanelStorefrontPluginRegistry;
 * - ifset().
 *
 * Инварианты:
 * - group=plugin сохраняет только plugin.id и plugin.settings;
 * - group=screens сохраняет только настройки экранов выбранного plugin;
 * - group=access/group=auth не сохраняют значения, запрещённые policy выбранного plugin;
 * - template/section/scheme/theme-настройки не сохраняются.
 *
 * Побочные эффекты:
 * - изменяет записи apanel_settings по пути storefronts.{storefront_key};
 * - при смене plugin сбрасывает screens и нормализует access/auth под новый plugin.
 *
 * Ошибки:
 * - waRightsException при отсутствии прав администратора Apanel;
 * - waException 400 при некорректном storefront_key, group, JSON или plugin_id.
 */
class apanelSettingsStorefrontSaveController extends waJsonController
{
    /**
     * Выполняет сохранение.
     *
     * @return void
     * @throws waException
     */
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('apanel')) {
            throw new waRightsException('Недостаточно прав.');
        }

        $storefront_key = waRequest::post('storefront_key', '', waRequest::TYPE_STRING_TRIM);
        $group = waRequest::post('group', '', waRequest::TYPE_STRING_TRIM);

        if (!$this->isValidStorefrontKey($storefront_key)) {
            throw new waException('Некорректный ключ витрины.', 400);
        }

        if (!$this->isAllowedGroup($group)) {
            throw new waException('Некорректная группа настроек.', 400);
        }

        if ($group === 'plugin') {
            $this->savePlugin($storefront_key);
            return;
        }

        if ($group === 'screens') {
            $this->saveScreens($storefront_key);
            return;
        }

        if ($group === 'profile') {
            $this->saveProfile($storefront_key);
            return;
        }

        if ($group === 'access') {
            $this->saveAccess($storefront_key);
            return;
        }

        if ($group === 'auth') {
            $this->saveAuth($storefront_key);
            return;
        }

        $this->saveJsonGroup($storefront_key, $group);
    }

    /**
     * Сохраняет выбранный plugin-продукт.
     *
     * @param string $storefront_key Ключ витрины.
     * @return void
     * @throws waException
     */
    protected function savePlugin($storefront_key)
    {
        $plugin_id = waRequest::post('plugin_id', '', waRequest::TYPE_STRING_TRIM);
        $plugin_settings = waRequest::post('plugin_settings', [], waRequest::TYPE_ARRAY);
        $registry = new apanelStorefrontPluginRegistry();

        if ($plugin_id !== '' && !$registry->hasPlugin($plugin_id)) {
            throw new waException('Плагин витрины не найден.', 400);
        }

        $service = new apanelStorefrontSettingsService(null, $registry);
        $service->savePluginSettings($storefront_key, $plugin_id, $plugin_settings);

        $this->setSavedResponse($storefront_key, 'plugin');
    }

    /**
     * Сохраняет screens витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @return void
     */
    protected function saveScreens($storefront_key)
    {
        $screens = waRequest::post('screens', [], waRequest::TYPE_ARRAY);
        $result = [];

        foreach ($screens as $id => $screen) {
            $id = trim((string) $id);

            if ($id === '') {
                continue;
            }

            $result[$id] = [
                'enabled' => !empty($screen['enabled']) ? 1 : 0,
                'name'    => trim((string) ifset($screen['name'], '')),
                'sort'    => (int) ifset($screen['sort'], 0),
            ];
        }

        $service = new apanelStorefrontSettingsService();
        $service->saveSettings($storefront_key, 'screens', $result, true);

        $this->setSavedResponse($storefront_key, 'screens');
    }

    /**
     * Сохраняет профиль витрины.
     *
     * @param string $storefront_key Ключ витрины.
     * @return void
     */
    protected function saveProfile($storefront_key)
    {
        $profile = waRequest::post('profile', [], waRequest::TYPE_ARRAY);

        $settings = [
            'enabled'     => !empty($profile['enabled']) ? 1 : 0,
            'name'        => trim((string) ifset($profile['name'], '')),
            'description' => trim((string) ifset($profile['description'], '')),
        ];

        $service = new apanelStorefrontSettingsService();
        $service->saveSettings($storefront_key, 'profile', $settings, true);

        $this->setSavedResponse($storefront_key, 'profile');
    }

    /**
     * Сохраняет настройки доступа.
     *
     * @param string $storefront_key Ключ витрины.
     * @return void
     */
    protected function saveAccess($storefront_key)
    {
        $access = waRequest::post('access', [], waRequest::TYPE_ARRAY);
        $service = new apanelStorefrontSettingsService();
        $settings = $service->normalizeGroupSettings($storefront_key, 'access', $access);

        $service->saveSettings($storefront_key, 'access', $settings, true);

        $this->setSavedResponse($storefront_key, 'access');
    }

    /**
     * Сохраняет настройки авторизации.
     *
     * @param string $storefront_key Ключ витрины.
     * @return void
     */
    protected function saveAuth($storefront_key)
    {
        $auth = waRequest::post('auth', [], waRequest::TYPE_ARRAY);
        $service = new apanelStorefrontSettingsService();
        $settings = $service->normalizeGroupSettings($storefront_key, 'auth', $auth);

        $service->saveSettings($storefront_key, 'auth', $settings, true);

        $this->setSavedResponse($storefront_key, 'auth');
    }

    /**
     * Сохраняет произвольную JSON-группу настроек.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string $group Группа настроек.
     * @return void
     * @throws waException
     */
    protected function saveJsonGroup($storefront_key, $group)
    {
        $settings_json = waRequest::post('settings_json', '', waRequest::TYPE_STRING_TRIM);
        $settings = json_decode($settings_json, true);

        if (!is_array($settings)) {
            throw new waException('Некорректный JSON настроек.', 400);
        }

        $service = new apanelStorefrontSettingsService();
        $settings = $service->normalizeGroupSettings($storefront_key, $group, $settings);

        $service->saveSettings($storefront_key, $group, $settings, true);

        $this->setSavedResponse($storefront_key, $group);
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
     * Устанавливает стандартный JSON-ответ после сохранения.
     *
     * @param string $storefront_key Ключ витрины.
     * @param string $group Группа настроек.
     * @return void
     */
    protected function setSavedResponse($storefront_key, $group)
    {
        $this->response = [
            'storefront_key' => $storefront_key,
            'group'          => $group,
            'saved'          => true,
        ];
    }
}
