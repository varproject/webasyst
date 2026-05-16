<?php

/**
 * apanelUiDropdownPanel
 *
 * Универсальная панель из нескольких dropdown-кнопок и счетчика.
 *
 * Назначение:
 * - вывести слева счетчик;
 * - собрать несколько dropdown-кнопок в одну панель;
 * - отдельно вывести кнопку config;
 * - позволить просто управлять составом панели через массив groups;
 * - поддержать пункты dropdown: link, checkbox, divider, html.
 *
 * Структура входных данных:
 *
 * [
 *     'counter'  => 15,
 *     'disabled' => false,
 *     'groups'   => [
 *         [
 *             'label' => 'Изменить',
 *             'icon'  => 'bi bi-sliders2',
 *             'items' => [
 *                 [
 *                     'type'    => 'checkbox',
 *                     'id'      => 'check1',
 *                     'name'    => 'category_1',
 *                     'value'   => '1',
 *                     'label'   => 'Категория 1',
 *                     'checked' => true,
 *                 ],
 *                 ['divider' => true],
 *                 [
 *                     'label' => 'Настройка шаблонов',
 *                     'url'   => '#',
 *                 ],
 *             ],
 *         ],
 *     ],
 *     'config' => [
 *         'items' => [
 *             ['label' => 'Действие', 'url' => '#'],
 *         ],
 *     ],
 * ]
 *
 * Поддерживаемые параметры верхнего уровня:
 * - counter              => int|string
 * - disabled             => bool
 * - groups               => array
 * - config               => array
 * - panel_class          => string|array
 * - panel_attrs          => array
 * - counter_class        => string|array
 * - counter_wrap_class   => string|array
 * - render_style         => bool
 *
 * Формат одного group:
 * - show                 => bool
 * - label                => string
 * - icon                 => string      Например: bi bi-printer
 * - icon_html            => string      Если нужен свой html вместо icon
 * - disabled             => bool
 * - auto_close           => string      Например: outside
 * - button_class         => string|array
 * - button_attrs         => array
 * - menu_class           => string|array
 * - menu_attrs           => array
 * - menu_style           => string
 * - items                => array
 *
 * Формат одного item:
 * - divider              => true
 * - либо:
 *   - type               => checkbox
 *   - id                 => string
 *   - name               => string
 *   - value              => string
 *   - label              => string
 *   - checked            => bool
 *   - disabled           => bool
 *   - wrap_class         => string|array
 *   - input_class        => string|array
 *   - label_class        => string|array
 *   - input_attrs        => array
 * - либо:
 *   - label              => string
 *   - url                => string
 *   - icon               => string
 *   - icon_html          => string
 *   - class              => string|array
 *   - target             => string
 *   - title              => string
 *   - disabled           => bool
 *   - attrs              => array
 * - либо:
 *   - html               => string
 *   - item_class         => string|array
 *   - item_attrs         => array
 */
final class apanelUiDropdownPanel
{
    public static function execute($params = [])
    {
        $panel_disabled = !empty($params['disabled']);
        $groups = [];

        foreach ((array) ifset($params['groups'], []) as $group) {
            if (!is_array($group)) {
                continue;
            }

            $normalized = self::normalizeGroup($group, $panel_disabled);
            if ($normalized) {
                $groups[] = $normalized;
            }
        }

        $config = self::normalizeConfig((array) ifset($params['config'], []), $panel_disabled);

        if (!$groups && empty($config['items'])) {
            return '';
        }

        return [
            'counter'              => (string) ifset($params['counter'], 0),
            'disabled'             => $panel_disabled,
            'groups'               => $groups,
            'config'               => $config,

            'panel_class'          => self::normalizeClass(
                ifset($params['panel_class'], ''),
                'btn-group'
            ),
            'panel_attrs_html'     => self::buildAttrsHtml((array) ifset($params['panel_attrs'], [])),

            'counter_wrap_class'   => self::normalizeClass(
                ifset($params['counter_wrap_class'], ''),
                'btn-group'
            ),
            'counter_class'        => self::normalizeClass(
                ifset($params['counter_class'], ''),
                'btn-group-counter'
            ),

            'render_style'         => array_key_exists('render_style', $params) ? !empty($params['render_style']) : true,
        ];
    }

    protected static function normalizeGroup($group, $panel_disabled = false)
    {
        if (array_key_exists('show', $group) && empty($group['show'])) {
            return [];
        }

        $items = self::normalizeItems((array) ifset($group['items'], []));
        if (!$items) {
            return [];
        }

        $label = trim((string) ifset($group['label'], ''));
        if ($label === '') {
            return [];
        }

        return [
            'label'             => $label,
            'icon'              => (string) ifset($group['icon'], ''),
            'icon_html'         => (string) ifset($group['icon_html'], ''),
            'disabled'          => ($panel_disabled || !empty($group['disabled'])),
            'auto_close'        => (string) ifset($group['auto_close'], ''),

            'button_class'      => self::normalizeClass(
                ifset($group['button_class'], ''),
                'btn bg-light text-dark border btn-press btn-sm dropdown-toggle'
            ),
            'button_attrs_html' => self::buildAttrsHtml((array) ifset($group['button_attrs'], [])),

            'menu_class'        => self::normalizeClass(
                ifset($group['menu_class'], ''),
                'dropdown-menu'
            ),
            'menu_attrs_html'   => self::buildAttrsHtml((array) ifset($group['menu_attrs'], [])),
            'menu_style'        => (string) ifset($group['menu_style'], ''),

            'items'             => $items,
        ];
    }

    protected static function normalizeConfig($config, $panel_disabled = false)
    {
        $items = self::normalizeItems((array) ifset($config['items'], []));

        return [
            'disabled'          => ($panel_disabled || !empty($config['disabled'])),
            'button_class'      => self::normalizeClass(
                ifset($config['button_class'], ''),
                'btn btn-secondary btn-sm dropdown-toggle'
            ),
            'button_attrs_html' => self::buildAttrsHtml((array) ifset($config['button_attrs'], [])),
            'menu_class'        => self::normalizeClass(
                ifset($config['menu_class'], ''),
                'dropdown-menu'
            ),
            'menu_attrs_html'   => self::buildAttrsHtml((array) ifset($config['menu_attrs'], [])),
            'menu_style'        => (string) ifset($config['menu_style'], ''),
            'icon'              => (string) ifset($config['icon'], 'bi bi-gear'),
            'icon_html'         => (string) ifset($config['icon_html'], ''),
            'items'             => $items,
        ];
    }

    protected static function normalizeItems($items)
    {
        $result = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!empty($item['divider'])) {
                $result[] = [
                    'type' => 'divider',
                ];
                continue;
            }

            if (isset($item['show']) && !$item['show']) {
                continue;
            }

            if (isset($item['html']) && trim((string) $item['html']) !== '') {
                $result[] = [
                    'type'            => 'html',
                    'html'            => (string) $item['html'],
                    'item_class'      => self::normalizeClass(ifset($item['item_class'], '')),
                    'item_attrs_html' => self::buildAttrsHtml((array) ifset($item['item_attrs'], [])),
                ];
                continue;
            }

            if ((string) ifset($item['type'], '') === 'checkbox') {
                $id = trim((string) ifset($item['id'], ''));
                $label = trim((string) ifset($item['label'], ''));

                if ($id === '' || $label === '') {
                    continue;
                }

                $result[] = [
                    'type'             => 'checkbox',
                    'id'               => $id,
                    'name'             => (string) ifset($item['name'], $id),
                    'value'            => (string) ifset($item['value'], '1'),
                    'label'            => $label,
                    'checked'          => !empty($item['checked']),
                    'disabled'         => !empty($item['disabled']),
                    'wrap_class'       => self::normalizeClass(
                        ifset($item['wrap_class'], ''),
                        'form-check'
                    ),
                    'input_class'      => self::normalizeClass(
                        ifset($item['input_class'], ''),
                        'form-check-input'
                    ),
                    'label_class'      => self::normalizeClass(
                        ifset($item['label_class'], ''),
                        'form-check-label w-100'
                    ),
                    'input_attrs_html' => self::buildAttrsHtml((array) ifset($item['input_attrs'], [])),
                ];
                continue;
            }

            $label = trim((string) ifset($item['label'], ''));
            if ($label === '') {
                continue;
            }

            $result[] = [
                'type'       => 'link',
                'label'      => $label,
                'url'        => (string) ifset($item['url'], '#'),
                'icon'       => (string) ifset($item['icon'], ''),
                'icon_html'  => (string) ifset($item['icon_html'], ''),
                'class'      => self::normalizeClass(ifset($item['class'], '')),
                'target'     => (string) ifset($item['target'], ''),
                'title'      => (string) ifset($item['title'], ''),
                'disabled'   => !empty($item['disabled']),
                'attrs_html' => self::buildAttrsHtml((array) ifset($item['attrs'], [])),
            ];
        }

        return $result;
    }

    protected static function normalizeClass($value, $default = '')
    {
        $classes = self::collectClasses($value);

        if (!$classes && $default !== '') {
            $classes = self::collectClasses($default);
        }

        return implode(' ', $classes);
    }

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
