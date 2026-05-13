<?php

/**
 * apanelUiTable
 *
 * Назначение:
 * - подготовить данные для универсального table-компонента;
 * - нормализовать колонки;
 * - подготовить служебные поля строк: _row_id, _action_url;
 * - управлять checkbox/action колонками;
 * - подготовить tbody-атрибуты для обычного режима и сортировки.
 *
 * Инварианты:
 * - основная логика таблицы живёт здесь;
 * - apanelTableAction остаётся тонкой обёрткой;
 * - шаблон таблицы получает уже подготовленные строки и колонки.
 */
final class apanelUiTable
{
    // Подготовить параметры table-компонента.
    public static function execute($params = [])
    {
        $columns = self::normalizeColumns((array) ifset($params['columns'], []));
        $items = self::prepareItems((array) ifset($params['items'], []), $params);

        $show_checkbox = array_key_exists('show_checkbox', $params)
            ? !empty($params['show_checkbox'])
            : true;

        $show_actions = !empty($params['show_actions']);

        $colspan = self::getVisibleColumnsCount($columns);

        if ($show_checkbox) {
            $colspan++;
        }

        if ($show_actions) {
            $colspan++;
        }

        return [
            'items'            => $items,
            'columns'          => $columns,
            'show_checkbox'    => $show_checkbox,
            'show_actions'     => $show_actions,
            'action_icon'      => (string) ifset($params['action_icon'], ''),
            'empty_text'       => (string) ifset($params['empty_text'], 'Записи не найдены.'),
            'tbody_attrs_html' => self::buildTbodyAttrsHtml($params),
            'colspan'          => $colspan,
        ];
    }

    // Подготовить строки таблицы.
    protected static function prepareItems($items, $params)
    {
        $result = [];

        $item_key = (string) ifset($params['action_item_key'], 'id');
        $action_url_pattern = (string) ifset($params['action_url_pattern'], '');
        $action_base_url = (string) ifset($params['action_base_url'], '');

        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item[$item_key])) {
                $row_id = $item[$item_key];
            } elseif (isset($item['id'])) {
                $row_id = $item['id'];
            } else {
                $row_id = $key;
            }

            $item['_row_id'] = $row_id;

            if ($action_url_pattern !== '') {
                $item['_action_url'] = str_replace('%id%', $row_id, $action_url_pattern);
            } elseif ($action_base_url !== '') {
                $item['_action_url'] = $action_base_url . $row_id;
            } elseif (!isset($item['_action_url'])) {
                $item['_action_url'] = '';
            }

            $result[$key] = $item;
        }

        return $result;
    }

    // Нормализовать колонки таблицы.
    protected static function normalizeColumns($columns)
    {
        $result = [];

        foreach ($columns as $key => $column) {
            if (!is_array($column)) {
                continue;
            }

            $result[$key] = [
                'title'       => (string) ifset($column['title'], $key),
                'type'        => (string) ifset($column['type'], 'text'),
                'visible'     => array_key_exists('visible', $column) ? (bool) $column['visible'] : true,
                'thclass'     => (string) ifset($column['thclass'], ''),
                'tdclass'     => (string) ifset($column['tdclass'], ''),
                'empty'       => array_key_exists('empty', $column) ? $column['empty'] : '—',
                'url_key'     => (string) ifset($column['url_key'], ''),
                'url_pattern' => (string) ifset($column['url_pattern'], ''),
            ];
        }

        return $result;
    }

    // Посчитать видимые колонки.
    protected static function getVisibleColumnsCount($columns)
    {
        $count = 0;

        foreach ($columns as $column) {
            if (!empty($column['visible'])) {
                $count++;
            }
        }

        return $count;
    }

    // Собрать HTML атрибутов tbody.
    protected static function buildTbodyAttrsHtml($params)
    {
        $attrs = (array) ifset($params['tbody_attrs'], []);

        $sort_driver = trim((string) ifset($params['sort_driver'], ''));
        $sort_url = trim((string) ifset($params['sort_url'], ''));

        if ($sort_driver !== '' && $sort_url !== '') {
            $attrs['data-sortable'] = 1;
            $attrs['data-sort-driver'] = $sort_driver;
            $attrs['data-sort-url'] = $sort_url;
        }

        return self::buildAttrsHtml($attrs);
    }

    // Собрать HTML-строку атрибутов.
    protected static function buildAttrsHtml($attrs = [])
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
