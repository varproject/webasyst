<?php

/**
 * Apanel
 *
 * @author Vagram Petrosian <var_project@mail.ru>
 * @copyright 2026 «Apanel»
 * @link http://wapv.ru
 *
 * apanelStorefrontScreenRegistry
 *
 * Реестр frontend-экранов выбранного plugin-продукта витрины.
 *
 * Назначение:
 * - получить screens из декларации выбранного Apanel plugin;
 * - дать плагину возможность дополнить screens через событие storefront_screens;
 * - применить настройки storefronts.{key}.screens;
 * - нормализовать включение, название, сортировку и URL экранов;
 * - вернуть screens для backend-таблицы, модалки и frontend runtime.
 *
 * Зависимости:
 * - apanelStorefrontPluginRegistry;
 * - wa('apanel')->event();
 * - ifset().
 *
 * Инварианты:
 * - core не содержит собственных бизнес-экранов витрины;
 * - screens принадлежат выбранному plugin-продукту;
 * - термин section в новой реализации не используется;
 * - результат не кэшируется глобально, потому что зависит от plugin_id и настроек конкретной storefront.
 */
final class apanelStorefrontScreenRegistry
{
    protected $plugin_registry;

    /**
     * Конструктор реестра.
     *
     * @param apanelStorefrontPluginRegistry|null $plugin_registry Реестр plugin-продуктов.
     */
    public function __construct($plugin_registry = null)
    {
        $this->plugin_registry = $plugin_registry ?: new apanelStorefrontPluginRegistry();
    }

    /**
     * Возвращает screens выбранного plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param array $settings Настройки витрины или группы screens.
     * @param string $storefront_key Ключ витрины.
     * @return array
     */
    public function getPluginScreens($plugin_id, $settings = [], $storefront_key = '')
    {
        $plugin = $this->plugin_registry->getPlugin($plugin_id, []);

        if (!$plugin) {
            return [];
        }

        $screens = [];

        foreach ((array) ifset($plugin['screens'], []) as $id => $screen) {
            $screen = $this->normalizeScreen($screen, $id, $plugin);

            if (!$screen) {
                continue;
            }

            $screens[$screen['id']] = $screen;
        }

        foreach ($this->getEventScreens($plugin, $screens, $storefront_key) as $id => $screen) {
            $screen = $this->normalizeScreen($screen, $id, $plugin);

            if (!$screen) {
                continue;
            }

            $screens[$screen['id']] = $screen;
        }

        $settings_screens = $this->getScreensSettings($settings);

        foreach ($screens as $id => &$screen) {
            $screen_settings = ifset($settings_screens[$id], []);

            if (is_array($screen_settings) && $screen_settings) {
                $screen = array_replace_recursive($screen, [
                    'enabled' => !empty($screen_settings['enabled']) ? 1 : 0,
                    'name'    => trim((string) ifset($screen_settings['name'], $screen['name'])),
                    'sort'    => (int) ifset($screen_settings['sort'], $screen['sort']),
                ]);
            }

            if ($screen['enabled'] === null) {
                $screen['enabled'] = !empty($screen['default_enabled']) ? 1 : 0;
            }
        }

        unset($screen);

        uasort($screens, [$this, 'sortScreens']);

        return $screens;
    }

    /**
     * Возвращает один screen выбранного plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param string $screen_id ID screen.
     * @param array $settings Настройки витрины или группы screens.
     * @param mixed $default Значение по умолчанию.
     * @return array|mixed
     */
    public function getPluginScreen($plugin_id, $screen_id, $settings = [], $default = null)
    {
        $screens = $this->getPluginScreens($plugin_id, $settings);
        $screen_id = trim((string) $screen_id);

        return isset($screens[$screen_id]) ? $screens[$screen_id] : $default;
    }

    /**
     * Возвращает screens из события storefront_screens.
     *
     * @param array $plugin Выбранный plugin-продукт.
     * @param array $screens Уже собранные screens.
     * @param string $storefront_key Ключ витрины.
     * @return array
     */
    protected function getEventScreens($plugin, $screens, $storefront_key = '')
    {
        $params = [
            'plugin_id'      => ifset($plugin['id'], ''),
            'plugin'         => $plugin,
            'storefront_key' => $storefront_key,
            'screens'        => $screens,
        ];

        $result = wa('apanel')->event('storefront_screens', $params);
        $event_screens = [];

        $this->appendEventScreens($event_screens, $result, $plugin);
        $this->appendEventScreens($event_screens, ifset($params['screens'], []), $plugin, true);

        return $event_screens;
    }

    /**
     * Добавляет screens из результата события.
     *
     * @param array $event_screens Итоговый список screens.
     * @param mixed $event_result Результат события или изменённые params.screens.
     * @param array $plugin Выбранный plugin-продукт.
     * @param bool $plain_result Результат уже является плоским списком screens.
     * @return void
     */
    protected function appendEventScreens(&$event_screens, $event_result, $plugin, $plain_result = false)
    {
        if (!$event_result || !is_array($event_result)) {
            return;
        }

        $owner = (string) ifset($plugin['plugin'], '');
        $product_id = (string) ifset($plugin['id'], '');

        if ($plain_result) {
            $event_result = [$owner => $event_result];
        }

        foreach ($event_result as $event_plugin_id => $items) {
            if ($owner !== '' && (string) $event_plugin_id !== $owner) {
                continue;
            }

            if (!$items || !is_array($items)) {
                continue;
            }

            if (isset($items['screens']) && is_array($items['screens'])) {
                $items = $items['screens'];
            }

            foreach ($items as $id => $screen) {
                if (!is_array($screen)) {
                    continue;
                }

                $screen_plugin_id = (string) ifset($screen['plugin_id'], $product_id);

                if ($screen_plugin_id !== '' && $screen_plugin_id !== $product_id) {
                    continue;
                }

                $event_screens[$id] = $screen;
            }
        }
    }

    /**
     * Нормализует screen.
     *
     * @param array $screen Сырые данные screen.
     * @param string|int $fallback_id ID из ключа массива.
     * @param array $plugin Выбранный plugin-продукт.
     * @return array|null
     */
    protected function normalizeScreen($screen, $fallback_id, $plugin)
    {
        if (!is_array($screen)) {
            return null;
        }

        $id = trim((string) ifset($screen['id'], $fallback_id));

        if ($id === '') {
            return null;
        }

        return [
            'id'              => $id,
            'plugin_id'       => (string) ifset($screen['plugin_id'], ifset($plugin['id'], '')),
            'plugin'          => (string) ifset($screen['plugin'], ifset($plugin['plugin'], '')),
            'name'            => trim((string) ifset($screen['name'], $id)),
            'description'     => trim((string) ifset($screen['description'], '')),
            'icon'            => trim((string) ifset($screen['icon'], '')),
            'url'             => trim((string) ifset($screen['url'], '')),
            'sort'            => (int) ifset($screen['sort'], 0),
            'default_enabled' => !empty($screen['default_enabled']) ? 1 : 0,
            'enabled'         => array_key_exists('enabled', $screen) ? (!empty($screen['enabled']) ? 1 : 0) : null,
            'action'          => trim((string) ifset($screen['action'], '')),
            'template'        => trim((string) ifset($screen['template'], '')),
            'actions'         => is_array(ifset($screen['actions'], [])) ? $screen['actions'] : [],
            'assets'          => is_array(ifset($screen['assets'], [])) ? $screen['assets'] : [],
            'settings'        => is_array(ifset($screen['settings'], [])) ? $screen['settings'] : [],
        ];
    }

    /**
     * Возвращает настройки screens из разных форм входных данных.
     *
     * @param array $settings Настройки.
     * @return array
     */
    protected function getScreensSettings($settings)
    {
        if (isset($settings['screens']) && is_array($settings['screens'])) {
            return $settings['screens'];
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * Сортирует screens.
     *
     * @param array $a Первый screen.
     * @param array $b Второй screen.
     * @return int
     */
    protected function sortScreens($a, $b)
    {
        $sort_a = (int) ifset($a['sort'], 0);
        $sort_b = (int) ifset($b['sort'], 0);

        if ($sort_a === $sort_b) {
            return strcmp((string) ifset($a['name'], ''), (string) ifset($b['name'], ''));
        }

        return $sort_a < $sort_b ? -1 : 1;
    }
}
