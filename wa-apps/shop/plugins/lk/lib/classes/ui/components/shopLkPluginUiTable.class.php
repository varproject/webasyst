<?php

/**
 * shopLkPluginUiTable
 *
 * Назначение:
 * - подготовить данные для универсального table-компонента;
 * - нормализовать конфигурацию колонок;
 * - управлять показом checkbox/action колонок;
 * - подготавливать tbody-атрибуты для обычного режима и сортировки.
 *
 * Зависимости:
 * - ifset();
 * - htmlspecialchars().
 *
 * Инварианты:
 * - execute() всегда возвращает массив параметров для шаблона;
 * - columns содержит только нормализованные колонки;
 * - colspan рассчитывается заранее;
 * - сортировка включается только если переданы sort_driver и sort_url.
 *
 * Поддерживаемые параметры:
 * - items             => array
 * - columns           => array
 * - show_checkbox     => bool
 * - show_actions      => bool
 * - action_base_url   => string
 * - action_item_key   => string
 * - action_icon       => string
 * - empty_text        => string
 * - sort_driver       => string
 * - sort_url          => string
 * - tbody_attrs       => array
 *
 * Ошибки:
 * - невалидные колонки пропускаются;
 * - невалидные HTML-атрибуты пропускаются.
 */
final class shopLkPluginUiTable
{
    /**
     * Подготавливает параметры table-компонента.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function execute(array $params = []): array
    {
        $columns = self::normalizeColumns((array) ifset($params['columns'], []));
        $items   = (array) ifset($params['items'], []);

        $show_checkbox = array_key_exists('show_checkbox', $params)
            ? !empty($params['show_checkbox'])
            : true;

        $show_actions = !empty($params['show_actions']);

        $visible_columns_count = count($columns);
        $colspan               = $visible_columns_count;

        if ($show_checkbox) {
            $colspan++;
        }

        if ($show_actions) {
            $colspan++;
        }

        return [
            'items'             => $items,
            'columns'           => $columns,
            'show_checkbox'     => $show_checkbox,
            'show_actions'      => $show_actions,
            'action_base_url'   => (string) ifset($params['action_base_url'], ''),
            'action_item_key'   => (string) ifset($params['action_item_key'], 'id'),
            'action_icon'       => (string) ifset($params['action_icon'], ''),
            'empty_text'        => (string) ifset($params['empty_text'], 'Записи не найдены.'),
            'tbody_attrs_html'  => self::buildTbodyAttrsHtml($params),
            'colspan'           => $colspan,
        ];
    }

    /**
     * Нормализует колонки таблицы.
     *
     * @param array<string, array<string, mixed>> $columns
     * @return array<string, array<string, mixed>>
     */
    protected static function normalizeColumns(array $columns): array
    {
        $result = [];

        foreach ($columns as $key => $column) {
            if (!is_array($column)) {
                continue;
            }

            $result[$key] = [
                'title'   => (string) ifset($column['title'], $key),
                'type'    => (string) ifset($column['type'], 'text'),
                'visible' => array_key_exists('visible', $column) ? (bool) $column['visible'] : true,
                'thclass' => (string) ifset($column['thclass'], ''),
                'tdclass' => (string) ifset($column['tdclass'], ''),
            ];
        }

        return $result;
    }

    /**
     * Собирает HTML атрибутов tbody.
     *
     * @param array<string, mixed> $params
     * @return string
     */
    protected static function buildTbodyAttrsHtml(array $params): string
    {
        $attrs = (array) ifset($params['tbody_attrs'], []);

        $sort_driver = trim((string) ifset($params['sort_driver'], ''));
        $sort_url    = trim((string) ifset($params['sort_url'], ''));

        if ($sort_driver !== '' && $sort_url !== '') {
            $attrs['data-sortable']    = 1;
            $attrs['data-sort-driver'] = $sort_driver;
            $attrs['data-sort-url']    = $sort_url;
        }

        return self::buildAttrsHtml($attrs);
    }

    /**
     * Собирает HTML-строку атрибутов.
     *
     * @param array<string, mixed> $attrs
     * @return string
     */
    protected static function buildAttrsHtml(array $attrs = []): string
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
