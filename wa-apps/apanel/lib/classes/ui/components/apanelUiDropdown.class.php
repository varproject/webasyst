<?php

/**
 * apanelUiDropdown
 *
 * Назначение:
 * - подготовить данные для рендера выпадающей кнопки Bootstrap 5;
 * - нормализовать входные параметры компонента;
 * - поддержать обычные пункты меню, разделители и кастомный HTML;
 * - поддержать HTML-атрибуты для wrapper/menu/item/button;
 * - собрать безопасные HTML-атрибуты для шаблона.
 *
 * Зависимости:
 * - ifset();
 * - htmlspecialchars().
 *
 * Инварианты:
 * - execute() возвращает пустую строку, если рендерить нечего;
 * - execute() возвращает массив, если нужно рендерить шаблон;
 * - class-параметры могут приходить как строкой, так и массивом;
 * - actions содержит только валидные элементы меню;
 * - dropdown-toggle гарантированно присутствует в классе кнопки.
 *
 * Поддерживаемые параметры компонента:
 * - id             => string
 * - class          => string|array
 * - wrapper_class  => string|array
 * - wrapper_attrs  => array
 * - menu_class     => string|array
 * - menu_attrs     => array
 * - button_type    => string
 * - label          => string
 * - icon           => string
 * - disabled       => bool
 * - attrs          => array
 * - actions        => array
 *
 * Формат actions:
 * - divider => true
 * - либо:
 *   - label      => string
 *   - icon       => string
 *   - url        => string
 *   - class      => string|array
 *   - target     => string
 *   - title      => string
 *   - disabled   => bool
 *   - attrs      => array
 * - либо:
 *   - html       => string
 *   - item_class => string|array
 *   - item_attrs => array
 *
 * Побочные эффекты:
 * - отсутствуют.
 *
 * Ошибки:
 * - невалидные элементы actions пропускаются;
 * - невалидные HTML-атрибуты пропускаются.
 */
final class apanelUiDropdown
{
    /**
     * Подготавливает данные dropdown-компонента.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>|string
     */
    public static function execute(array $params = [])
    {
        $actions = [];

        foreach ((array) ifset($params['actions'], []) as $action) {
            if (!is_array($action)) {
                continue;
            }

            if (!empty($action['divider'])) {
                $actions[] = [
                    'divider' => true,
                ];
                continue;
            }

            $label = (string) ifset($action['label'], '');
            $html  = (string) ifset($action['html'], '');

            if ($label === '' && $html === '') {
                continue;
            }

            $actions[] = [
                'divider'         => false,
                'label'           => $label,
                'html'            => $html,
                'icon'            => (string) ifset($action['icon'], ''),
                'url'             => (string) ifset($action['url'], ''),
                'class'           => self::normalizeClass(ifset($action['class'], '')),
                'target'          => (string) ifset($action['target'], ''),
                'title'           => (string) ifset($action['title'], ''),
                'disabled'        => !empty($action['disabled']),
                'attrs_html'      => self::buildAttrsHtml((array) ifset($action['attrs'], [])),
                'item_class'      => self::normalizeClass(ifset($action['item_class'], '')),
                'item_attrs_html' => self::buildAttrsHtml((array) ifset($action['item_attrs'], [])),
            ];
        }

        if (!$actions) {
            return '';
        }

        $button_class = self::normalizeClass(
            ifset($params['class'], []),
            'btn btn-secondary btn-sm dropdown-toggle'
        );

        $dropdown_id = (string) ifset($params['id'], '');

        if ($dropdown_id === '') {
            $seed = (string) ifset($params['name'], 'dropdown');
            $seed .= '-' . (string) ifset($params['label'], '');
            $seed .= '-' . md5((string) json_encode($actions));

            $dropdown_id = 'apanel-dropdown-' . trim(
                (string) preg_replace('~[^a-z0-9\-_]+~i', '-', $seed),
                '-'
            );
        }

        return [
            'dropdown_id'        => $dropdown_id,
            'wrapper_class'      => self::normalizeClass(ifset($params['wrapper_class'], '')),
            'wrapper_attrs_html' => self::buildAttrsHtml((array) ifset($params['wrapper_attrs'], [])),
            'button_class'       => trim($button_class),
            'button_type'        => (string) ifset($params['button_type'], 'button'),
            'button_attrs_html'  => self::buildAttrsHtml((array) ifset($params['attrs'], [])),
            'label'              => (string) ifset($params['label'], ''),
            'icon'               => (string) ifset($params['icon'], ''),
            'menu_class'         => self::normalizeClass(ifset($params['menu_class'], '')),
            'menu_attrs_html'    => self::buildAttrsHtml((array) ifset($params['menu_attrs'], [])),
            'disabled'           => !empty($params['disabled']),
            'actions'            => $actions,
        ];
    }

    /**
     * Нормализует class-значение из string|array в строку.
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    private static function normalizeClass($value, string $default = ''): string
    {
        $classes = self::collectClasses($value);
        if (count($classes) <= 1 && $default !== '') {
            $classes = self::collectClasses($default);
        }

        return implode(' ', $classes);
    }

    /**
     * Собирает список CSS-классов из строки или массива.
     *
     * @param mixed $value
     * @return array<int, string>
     */
    private static function collectClasses($value): array
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
     * Собирает HTML-строку дополнительных атрибутов.
     *
     * @param array<string, mixed> $attrs
     * @return string
     */
    private static function buildAttrsHtml(array $attrs = []): string
    {
        if (!$attrs) {
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
