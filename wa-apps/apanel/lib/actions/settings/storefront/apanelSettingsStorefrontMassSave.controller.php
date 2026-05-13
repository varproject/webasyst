<?php

/**
 * apanelSettingsStorefrontMassSaveController
 *
 * Массово сохраняет группу настроек для нескольких витрин.
 *
 * Назначение:
 * - принять список storefront_keys;
 * - принять группу настроек;
 * - принять settings_json;
 * - применить настройки ко всем выбранным витринам;
 * - для access/auth нормализовать значения отдельно для каждой витрины с учётом выбранного plugin.
 *
 * Зависимости:
 * - waJsonController;
 * - waRequest;
 * - apanelStorefrontSettingsService.
 *
 * Инварианты:
 * - массовое сохранение не работает с plugin и screens;
 * - access/auth нельзя массово сохранить в состоянии, запрещённом policy выбранного plugin;
 * - template/section/scheme/theme-настройки не сохраняются.
 *
 * Побочные эффекты:
 * - изменяет записи apanel_settings по пути storefronts.{storefront_key}.{group}.
 *
 * Ошибки:
 * - waRightsException при отсутствии прав администратора Apanel;
 * - waException 400 при некорректной группе, JSON или пустом списке витрин.
 */
class apanelSettingsStorefrontMassSaveController extends waJsonController
{
    /**
     * Выполняет массовое сохранение.
     *
     * @return void
     * @throws waException
     */
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('apanel')) {
            throw new waRightsException('Недостаточно прав.');
        }

        $storefront_keys = waRequest::post('storefront_keys', [], waRequest::TYPE_ARRAY);
        $group = waRequest::post('group', '', waRequest::TYPE_STRING_TRIM);
        $settings_json = waRequest::post('settings_json', '', waRequest::TYPE_STRING_TRIM);

        if (!$this->isAllowedGroup($group)) {
            throw new waException('Некорректная группа настроек.', 400);
        }

        $settings = json_decode($settings_json, true);

        if (!is_array($settings)) {
            throw new waException('Некорректный JSON настроек.', 400);
        }

        $keys = $this->prepareStorefrontKeys($storefront_keys);

        if (!$keys) {
            throw new waException('Не выбраны витрины для массового сохранения.', 400);
        }

        $service = new apanelStorefrontSettingsService();
        $result = $service->saveSettingsMass($keys, $group, $settings, true);

        $this->response = [
            'group'  => $group,
            'count'  => count($keys),
            'saved'  => true,
            'result' => $result,
        ];
    }

    /**
     * Подготавливает список ключей витрин.
     *
     * @param array $storefront_keys Ключи витрин.
     * @return array
     */
    protected function prepareStorefrontKeys($storefront_keys)
    {
        $keys = [];

        foreach ((array) $storefront_keys as $storefront_key) {
            $storefront_key = trim((string) $storefront_key);

            if (!$this->isValidStorefrontKey($storefront_key)) {
                continue;
            }

            $keys[] = $storefront_key;
        }

        return array_values(array_unique($keys));
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
            'access',
            'auth',
            'ui',
            'data',
            'seo',
            'advanced',
        ], true);
    }
}
