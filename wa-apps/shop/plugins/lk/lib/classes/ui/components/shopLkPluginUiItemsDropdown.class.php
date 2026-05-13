<?php

/**
 * shopLkPluginUiItemsDropdown
 *
 * Универсальный dropdown-компонент со списком элементов.
 *
 * Компонент предназначен для вывода выпадающего списка, внутри которого:
 * - отображается список элементов;
 * - можно менять порядок элементов через sortable;
 * - можно включать/выключать элементы через switch;
 * - можно выводить ссылки редактирования и удаления;
 * - можно выводить нижнюю кнопку добавления нового элемента.
 *
 * Компонент НЕ привязан к каталогам.
 * В качестве items можно передавать любые данные:
 * каталоги, склады, пользователи, статусы, тарифы, группы и т.д.
 *
 * Каждый элемент items описывается простым массивом:
 * - id         — идентификатор элемента;
 * - name       — название элемента;
 * - is_enabled — включен ли элемент;
 * - is_system  — системный ли элемент;
 * - edit_url   — ссылка редактирования (необязательно);
 * - delete_url — ссылка удаления (необязательно);
 * - toggle_id  — id switch-поля (необязательно).
 *
 * Если edit_url / delete_url не переданы в самом элементе,
 * их можно построить автоматически через параметры:
 * - edit_url_pattern
 * - delete_url_pattern
 *
 * Поддерживаемые параметры компонента:
 *
 * Основные:
 * - dropdown_title   => string  Заголовок кнопки dropdown
 * - items            => array   Массив элементов
 *
 * Toggle:
 * - toggle_post_url   => string       URL для hx-post
 * - toggle_include    => string       hx-include
 * - toggle_oob        => string|array hx-select-oob
 * - toggle_swap       => string       hx-swap
 * - toggle_name       => string       name switch-поля
 * - toggle_value      => string       value switch-поля
 * - hidden_input_name => string       name hidden-поля с id элемента
 *
 * Sortable:
 * - sort_post_url         => string       data-sortable-url
 * - sort_oob      => string|array data-sortable-update
 *
 * Ссылки:
 * - edit_url_pattern   => string  Шаблон ссылки редактирования
 * - delete_url_pattern => string  Шаблон ссылки удаления
 *
 * Кнопка добавления:
 * - add_url          => string  URL кнопки добавления
 * - add_button_name  => string  Текст кнопки
 * - show_add_button  => bool    Показывать ли кнопку принудительно
 *
 * Оформление:
 * - wrapper_class    => string|array
 * - wrapper_attrs    => array
 * - button_class     => string|array
 * - button_attrs     => array
 * - menu_class       => string|array
 * - menu_attrs       => array
 * - menu_style       => string
 * - button_icon      => string  HTML иконки кнопки dropdown
 * - create_icon      => string  HTML иконки кнопки добавления
 * - handle_icon      => string  HTML иконки ручки сортировки
 * - link_boost       => string  значение hx-boost для ссылок
 *
 * Поддерживаемые плейсхолдеры в шаблонах URL:
 * - %id%
 * - {id}
 * - :id
 *
 * Пример 1. Dropdown каталогов:
 *
 * $html = shopLkPluginUi::getControl('items_dropdown', 'catalog_dropdown', [
 *     'dropdown_title'  => 'Каталоги',
 *     'items'           => $catalogs,
 *     'toggle_post_url' => '?module=catalogToggle',
 *     'sort_post_url'        => '?module=catalogSort',
 *     'sort_oob'     => [
 *         '#sidebar_body',
 *         '#header_left',
 *         '#main_navbar_left',
 *     ],
 *     'toggle_oob'      => [
 *         '#sidebar_body',
 *         '#header_left',
 *         '#main_navbar_left',
 *         '#main_toolbar',
 *         '#main_body',
 *         '#main_footer',
 *     ],
 *     'edit_url_pattern'   => '?edit_catalog=%id%',
 *     'delete_url_pattern' => '?delete_catalog=%id%',
 *     'add_url'            => '?add_catalog',
 *     'add_button_name'    => 'Создать каталог',
 *     'hidden_input_name'  => 'catalog_id',
 * ]);
 *
 * Пример 2. Dropdown пользователей:
 *
 * $html = shopLkPluginUi::getControl('items_dropdown', 'users_dropdown', [
 *     'dropdown_title'     => 'Пользователи',
 *     'items'              => $users,
 *     'toggle_post_url'    => '?module=userToggle',
 *     'edit_url_pattern'   => '?edit_user=%id%',
 *     'delete_url_pattern' => '?delete_user=%id%',
 *     'add_url'            => '?add_user',
 *     'add_button_name'    => 'Добавить пользователя',
 *     'hidden_input_name'  => 'user_id',
 * ]);
 *
 * Пример 3. Если URL уже лежат внутри элементов:
 *
 * $items = [
 *     [
 *         'id'         => 1,
 *         'name'       => 'Основной',
 *         'is_enabled' => 1,
 *         'is_system'  => 1,
 *         'edit_url'   => '?edit_item=1',
 *         'delete_url' => '?delete_item=1',
 *     ],
 * ];
 *
 * $html = shopLkPluginUi::getControl('items_dropdown', 'my_dropdown', [
 *     'dropdown_title' => 'Элементы',
 *     'items'          => $items,
 * ]);
 *
 * Инварианты:
 * - компонент возвращает массив данных для Smarty-шаблона;
 * - элемент без id или name не попадает в итоговый список;
 * - кнопка добавления показывается автоматически, если передан add_url;
 * - системный элемент is_system блокирует switch и скрывает edit/delete;
 * - sort_oob и toggle_oob можно передавать как строкой, так и массивом.
 */
final class shopLkPluginUiItemsDropdown
{
    /**
     * Подготовить данные компонента для рендера шаблона.
     *
     * @param array $params Параметры компонента
     * @return array
     */
    public static function execute($params = [])
    {
        $source_items = (array) ifset($params['items'], []);
        $items = [];

        foreach ($source_items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = self::normalizeItem($item, $params);
            if ($normalized) {
                $items[] = $normalized;
            }
        }

        $add_url = (string) ifset($params['add_url'], '');

        if (array_key_exists('show_add_button', $params)) {
            $show_add_button = !empty($params['show_add_button']);
        } else {
            $show_add_button = ($add_url !== '');
        }

        return [
            'dropdown_title'      => (string) ifset($params['dropdown_title'], 'Элементы'),
            'items'               => $items,

            'toggle_post_url'     => (string) ifset($params['toggle_post_url'], ''),
            'toggle_include'      => (string) ifset($params['toggle_include'], 'closest .form-check'),
            'toggle_oob'          => self::normalizeSelectorList(ifset($params['toggle_oob'], '')),
            'toggle_swap'         => (string) ifset($params['toggle_swap'], 'none'),
            'toggle_name'         => (string) ifset($params['toggle_name'], 'is_enabled'),
            'toggle_value'        => (string) ifset($params['toggle_value'], '1'),
            'hidden_input_name'   => (string) ifset($params['hidden_input_name'], 'item_id'),

            'sort_post_url'       => (string) ifset($params['sort_post_url'], ''),
            'sort_oob'            => self::normalizeSelectorList(ifset($params['sort_oob'], '')),

            'wrapper_class'       => self::normalizeClass(
                ifset($params['wrapper_class'], ''),
                'dropdown btn-group nav-item user-select-none'
            ),
            'wrapper_attrs_html'  => self::buildAttrsHtml((array) ifset($params['wrapper_attrs'], [])),

            'button_class'        => self::normalizeClass(
                ifset($params['button_class'], ''),
                'nav-link dropdown-toggle pe-0 fw-bold'
            ),
            'button_attrs_html'   => self::buildAttrsHtml((array) ifset($params['button_attrs'], [])),

            'menu_class'          => self::normalizeClass(
                ifset($params['menu_class'], ''),
                'dropdown-menu dropdown-menu-end p-3 text-nowrap'
            ),
            'menu_attrs_html'     => self::buildAttrsHtml((array) ifset($params['menu_attrs'], [])),
            'menu_style'          => (string) ifset($params['menu_style'], 'max-height: 400px; overflow-y: auto;'),

            'button_icon'         => (string) ifset($params['button_icon'], '<i class="bi bi-sliders me-1"></i>'),
            'create_icon'         => (string) ifset($params['create_icon'], '<i class="bi bi-plus-lg"></i>'),
            'handle_icon'         => (string) ifset($params['handle_icon'], '<i class="bi bi-grip-vertical"></i>'),

            'link_boost'          => (string) ifset($params['link_boost'], 'false'),

            'add_url'             => $add_url,
            'add_button_name'     => (string) ifset($params['add_button_name'], 'Добавить'),
            'show_add_button'     => $show_add_button,
            'show_divider'        => ($show_add_button && !empty($items)),
        ];
    }

    /**
     * Нормализовать один элемент списка.
     *
     * @param array $item Исходные данные элемента
     * @param array $params Общие параметры компонента
     * @return array
     */
    protected static function normalizeItem($item, $params = [])
    {
        $id = (string) ifset($item['id'], '');
        $name = trim((string) ifset($item['name'], ''));

        if ($id === '' || $name === '') {
            return [];
        }

        $edit_url = trim((string) ifset($item['edit_url'], ''));
        if ($edit_url === '') {
            $edit_url = self::buildUrlByPattern((string) ifset($params['edit_url_pattern'], ''), $id);
        }

        $delete_url = trim((string) ifset($item['delete_url'], ''));
        if ($delete_url === '') {
            $delete_url = self::buildUrlByPattern((string) ifset($params['delete_url_pattern'], ''), $id);
        }

        return [
            'id'         => $id,
            'name'       => $name,
            'is_enabled' => !empty($item['is_enabled']),
            'is_system'  => !empty($item['is_system']),
            'edit_url'   => $edit_url,
            'delete_url' => $delete_url,
            'toggle_id'  => (string) ifset($item['toggle_id'], 'sw_' . $id),
        ];
    }

    /**
     * Построить URL по шаблону.
     *
     * Примеры:
     * - ?edit=%id%
     * - ?edit={id}
     * - ?edit=:id
     *
     * @param string $pattern Шаблон URL
     * @param string $id Идентификатор элемента
     * @return string
     */
    protected static function buildUrlByPattern($pattern, $id)
    {
        $pattern = trim((string) $pattern);

        if ($pattern === '') {
            return '';
        }

        return strtr($pattern, [
            '%id%' => $id,
            '{id}' => $id,
            ':id'  => $id,
        ]);
    }

    /**
     * Нормализовать class-значение из строки или массива.
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    protected static function normalizeClass($value, $default = '')
    {
        $classes = self::collectClasses($value);

        if (!$classes && $default !== '') {
            $classes = self::collectClasses($default);
        }

        return implode(' ', $classes);
    }

    /**
     * Собрать массив классов.
     *
     * @param mixed $value
     * @return array
     */
    protected static function collectClasses($value)
    {
        $result = [];

        if (is_string($value)) {
            $parts = preg_split('~\s+~u', trim($value), -1, PREG_SPLIT_NO_EMPTY);

            if ($parts) {
                foreach ($parts as $part) {
                    $result[$part] = $part;
                }
            }

            return array_values($result);
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                foreach (self::collectClasses($item) as $class_name) {
                    $result[$class_name] = $class_name;
                }
            }
        }

        return array_values($result);
    }

    /**
     * Нормализовать список CSS-селекторов.
     *
     * Можно передавать:
     * - строку '#a,#b,#c'
     * - массив ['#a', '#b', '#c']
     *
     * @param mixed $value
     * @return string
     */
    protected static function normalizeSelectorList($value)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $result = [];
        $parts = explode(',', (string) $value);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $result[] = $part;
            }
        }

        return implode(',', $result);
    }

    /**
     * Собрать HTML-атрибуты из массива.
     *
     * Пример:
     * ['data-test' => '1', 'disabled' => true]
     *
     * Результат:
     *  data-test="1" disabled
     *
     * @param array $attrs
     * @return string
     */
    protected static function buildAttrsHtml($attrs = [])
    {
        if (!$attrs || !is_array($attrs)) {
            return '';
        }

        $html = [];

        foreach ($attrs as $name => $value) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            if (preg_match('~[^a-zA-Z0-9_\-:\.]~', $name)) {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $html[] = $name;
                }
                continue;
            }

            if ($value === null || is_array($value) || is_object($value)) {
                continue;
            }

            $html[] = $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return $html ? ' ' . implode(' ', $html) : '';
    }
}
