<?php

/**
 * Apanel
 *
 * @author Vagram Petrosian <var_project@mail.ru>
 * @copyright 2026 «Apanel»
 * @link http://wapv.ru
 *
 * apanelStorefrontPluginRegistry
 *
 * Реестр plugin-продуктов витрин Apanel.
 *
 * Назначение:
 * - собрать доступные Apanel plugin-продукты через событие storefront_plugins;
 * - нормализовать декларации плагинов для backend и runtime;
 * - предоставить единый список plugin-продуктов для выбора в настройках витрины;
 * - нормализовать декларативные fields и settings выбранного plugin;
 * - безопасно обрабатывать ситуацию, когда plugin-продукт не выбран или недоступен.
 *
 * Зависимости:
 * - wa('apanel')->event();
 * - ifset().
 *
 * Инварианты:
 * - core не хранит список plugin-продуктов в storefront.plugins.php;
 * - B2B и любые другие продукты регистрируются только установленными плагинами приложения Apanel;
 * - plugin.id является главным выбором функционального продукта витрины;
 * - пустой plugin_id является валидным состоянием «Плагин не выбран»;
 * - результат кэшируется только на время текущего запроса.
 */
final class apanelStorefrontPluginRegistry
{
    protected static $plugins = null;

    /**
     * Возвращает все доступные plugin-продукты витрин.
     *
     * @return array
     */
    public function getPlugins()
    {
        if (self::$plugins !== null) {
            return self::$plugins;
        }

        $plugins = [];
        $params = [
            'plugins' => [],
        ];

        $result = wa('apanel')->event('storefront_plugins', $params);

        $this->appendEventPlugins($plugins, $result);
        $this->appendEventPlugins($plugins, ifset($params['plugins'], []));

        uasort($plugins, [$this, 'sortPlugins']);

        self::$plugins = $plugins;

        return self::$plugins;
    }

    /**
     * Возвращает один plugin-продукт.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param mixed $default Значение по умолчанию.
     * @return array|mixed
     */
    public function getPlugin($plugin_id, $default = null)
    {
        $plugin_id = trim((string) $plugin_id);
        $plugins = $this->getPlugins();

        return isset($plugins[$plugin_id]) ? $plugins[$plugin_id] : $default;
    }

    /**
     * Проверяет наличие plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @return bool
     */
    public function hasPlugin($plugin_id)
    {
        $plugin_id = trim((string) $plugin_id);

        if ($plugin_id === '') {
            return false;
        }

        $plugins = $this->getPlugins();

        return isset($plugins[$plugin_id]);
    }

    /**
     * Возвращает нормализованные settings fields plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @return array
     */
    public function getPluginFields($plugin_id)
    {
        $plugin = $this->getPlugin($plugin_id, []);

        if (!$plugin) {
            return [];
        }

        $fields = ifset($plugin['settings']['fields'], []);

        if (!is_array($fields)) {
            return [];
        }

        $result = [];

        foreach ($fields as $id => $field) {
            $field = $this->normalizeField($field, $id);

            if (!$field) {
                continue;
            }

            $result[$field['id']] = $field;
        }

        uasort($result, [$this, 'sortFields']);

        return $result;
    }

    /**
     * Возвращает дефолтные settings выбранного plugin-продукта.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @return array
     */
    public function getPluginSettingsDefaults($plugin_id)
    {
        $plugin = $this->getPlugin($plugin_id, []);

        if (!$plugin) {
            return [];
        }

        $defaults = ifset($plugin['settings']['defaults'], []);

        if (!is_array($defaults)) {
            $defaults = [];
        }

        foreach ($this->getPluginFields($plugin_id) as $id => $field) {
            if (!array_key_exists($id, $defaults)) {
                $defaults[$id] = ifset($field['value'], '');
            }
        }

        return $defaults;
    }

    /**
     * Нормализует settings выбранного plugin-продукта по декларации fields.
     *
     * @param string $plugin_id ID plugin-продукта.
     * @param array $values Новые значения.
     * @param array $current Текущие значения.
     * @return array
     */
    public function normalizePluginSettings($plugin_id, $values, $current = [])
    {
        $fields = $this->getPluginFields($plugin_id);
        $defaults = $this->getPluginSettingsDefaults($plugin_id);
        $result = [];

        foreach ($fields as $id => $field) {
            $type = (string) ifset($field['control_type'], 'input');
            $value = array_key_exists($id, $values) ? $values[$id] : ifset($current[$id], ifset($defaults[$id], ''));

            if ($type === 'checkbox') {
                $result[$id] = !empty($value) ? 1 : 0;
                continue;
            }

            if ($type === 'select') {
                $options = $this->getFieldOptionValues($field);
                $value = trim((string) $value);

                if ($options && !in_array($value, $options, true)) {
                    $value = trim((string) ifset($defaults[$id], reset($options)));
                }

                $result[$id] = $value;
                continue;
            }

            if ($type === 'html') {
                continue;
            }

            $result[$id] = trim((string) $value);
        }

        return $result;
    }

    /**
     * Добавляет plugin-продукты из результата события.
     *
     * @param array $plugins Итоговый список plugin-продуктов.
     * @param mixed $event_result Результат события или изменённые params.plugins.
     * @return void
     */
    protected function appendEventPlugins(&$plugins, $event_result)
    {
        if (!$event_result || !is_array($event_result)) {
            return;
        }

        foreach ($event_result as $owner_plugin_id => $items) {
            foreach ($this->prepareEventPlugins($owner_plugin_id, $items) as $id => $plugin) {
                $plugin = $this->normalizePlugin($plugin, $id, $owner_plugin_id);

                if (!$plugin) {
                    continue;
                }

                $plugins[$plugin['id']] = $plugin;
            }
        }
    }

    /**
     * Подготавливает результат события storefront_plugins.
     *
     * @param string $plugin_id ID Webasyst plugin.
     * @param mixed $items Данные обработчика события.
     * @return array
     */
    protected function prepareEventPlugins($plugin_id, $items)
    {
        if (!$items || !is_array($items)) {
            return [];
        }

        if (isset($items['plugins']) && is_array($items['plugins'])) {
            return $items['plugins'];
        }

        if (isset($items['id']) || isset($items['name'])) {
            $id = trim((string) ifset($items['id'], $plugin_id));

            return $id !== '' ? [$id => $items] : [];
        }

        return $items;
    }

    /**
     * Нормализует декларацию plugin-продукта.
     *
     * @param array $plugin Сырые данные plugin-продукта.
     * @param string|int $fallback_id ID из ключа массива.
     * @param string $owner_plugin_id ID Webasyst plugin-владельца.
     * @return array|null
     */
    protected function normalizePlugin($plugin, $fallback_id = '', $owner_plugin_id = '')
    {
        if (!is_array($plugin)) {
            return null;
        }

        $id = trim((string) ifset($plugin['id'], $fallback_id));

        if ($id === '') {
            return null;
        }

        $owner = trim((string) ifset($plugin['plugin'], $owner_plugin_id));

        return [
            'id'          => $id,
            'name'        => trim((string) ifset($plugin['name'], $id)),
            'description' => trim((string) ifset($plugin['description'], '')),
            'version'     => trim((string) ifset($plugin['version'], '')),
            'plugin'      => $owner,
            'sort'        => (int) ifset($plugin['sort'], 0),
            'access'      => is_array(ifset($plugin['access'], [])) ? $plugin['access'] : [],
            'auth'        => is_array(ifset($plugin['auth'], [])) ? $plugin['auth'] : [],
            'screens'     => is_array(ifset($plugin['screens'], [])) ? $plugin['screens'] : [],
            'settings'    => is_array(ifset($plugin['settings'], [])) ? $plugin['settings'] : [],
            'assets'      => is_array(ifset($plugin['assets'], [])) ? $plugin['assets'] : [],
        ];
    }

    /**
     * Нормализует декларацию поля plugin settings.
     *
     * @param array $field Сырые данные поля.
     * @param string|int $fallback_id ID из ключа массива.
     * @return array|null
     */
    protected function normalizeField($field, $fallback_id)
    {
        if (!is_array($field)) {
            return null;
        }

        $id = trim((string) ifset($field['id'], $fallback_id));

        if ($id === '') {
            return null;
        }

        $type = trim((string) ifset($field['control_type'], 'input'));

        if (!in_array($type, ['input', 'textarea', 'checkbox', 'select', 'hidden', 'html'], true)) {
            $type = 'input';
        }

        return [
            'id'           => $id,
            'title'        => trim((string) ifset($field['title'], $id)),
            'description'  => trim((string) ifset($field['description'], '')),
            'control_type' => $type,
            'value'        => ifset($field['value'], ''),
            'options'      => is_array(ifset($field['options'], [])) ? $field['options'] : [],
            'html'         => (string) ifset($field['html'], ''),
            'sort'         => (int) ifset($field['sort'], 0),
        ];
    }

    /**
     * Возвращает допустимые значения select-поля.
     *
     * @param array $field Поле.
     * @return array
     */
    protected function getFieldOptionValues($field)
    {
        $result = [];

        foreach ((array) ifset($field['options'], []) as $key => $option) {
            if (is_array($option)) {
                $value = trim((string) ifset($option['value'], $key));
            } else {
                $value = trim((string) $key);
            }

            if ($value !== '') {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Сортирует plugin-продукты.
     *
     * @param array $a Первый plugin-продукт.
     * @param array $b Второй plugin-продукт.
     * @return int
     */
    protected function sortPlugins($a, $b)
    {
        $sort_a = (int) ifset($a['sort'], 0);
        $sort_b = (int) ifset($b['sort'], 0);

        if ($sort_a === $sort_b) {
            return strcmp((string) ifset($a['name'], ''), (string) ifset($b['name'], ''));
        }

        return $sort_a < $sort_b ? -1 : 1;
    }

    /**
     * Сортирует fields.
     *
     * @param array $a Первое поле.
     * @param array $b Второе поле.
     * @return int
     */
    protected function sortFields($a, $b)
    {
        $sort_a = (int) ifset($a['sort'], 0);
        $sort_b = (int) ifset($b['sort'], 0);

        if ($sort_a === $sort_b) {
            return strcmp((string) ifset($a['title'], ''), (string) ifset($b['title'], ''));
        }

        return $sort_a < $sort_b ? -1 : 1;
    }
}
