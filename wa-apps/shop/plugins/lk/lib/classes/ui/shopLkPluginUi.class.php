<?php

/**
 * shopLkPluginUi
 *
 * Назначение:
 * - регистрировать пользовательские UI-контролы через waHtmlControl;
 * - строить служебные имена компонента по краткому id;
 * - рендерить компонент в одном из трёх режимов:
 *   1) только шаблон;
 *   2) только PHP;
 *   3) PHP + шаблон.
 *
 * Зависимости:
 * - waHtmlControl;
 * - shopLkPluginFetchHtml;
 * - shopLkPluginStringCase.
 *
 * Инварианты:
 * - каждый тип контрола регистрируется в waHtmlControl один раз за runtime;
 * - control type строится как Shop_lk_plugin_ui_{snake_case};
 * - PHP-класс строится как shopLkPluginUi{PascalCase};
 * - шаблон строится как ShopLkPluginUi{PascalCase}.html;
 * - UI-шаблоны располагаются в lib/classes/ui/templates/.
 *
 * Ошибки:
 * - при пустом id компонента возвращается пустая строка;
 * - если класс компонента найден, но метод execute() отсутствует, выбрасывается waException;
 * - если шаблон указан, но файл не найден, исключение пробрасывается из shopLkPluginFetchHtml.
 */
final class shopLkPluginUi
{
    /** @var array<string, bool> */
    private static array $registered_controls = [];

    /**
     * Возвращает HTML пользовательского UI-контрола.
     *
     * Пример для $type = 'tree':
     * - type:     Shop_lk_plugin_ui_tree
     * - class:    shopLkPluginUiTree
     * - template: ShopLkPluginUiTree
     *
     * @param string $type
     * @param string $name
     * @param array<string, mixed> $params
     * @return string
     */
    public static function getControl(string $type, string $name = '', array $params = []): string
    {
        if ($type === '') {
            return '';
        }

        $component_id = shopLkPluginStringCase::toSnakeCase($type);

        if ($component_id === '') {
            return '';
        }

        $component_suffix = shopLkPluginStringCase::toPascalCase($component_id);

        $params['control_type']       = 'Shop_lk_plugin_ui' . $component_id;
        $params['component_id']       = $component_id;
        $params['component_class']    = 'shopLkPluginUi' . $component_suffix;
        $params['component_template'] = 'ShopLkPluginUi' . $component_suffix;

        if ($name === '') {
            $name = $component_id;
        }

        if (!isset(self::$registered_controls[$params['control_type']])) {
            waHtmlControl::registerControl($params['control_type'], [__CLASS__, 'render']);
            self::$registered_controls[$params['control_type']] = true;
        }

        return waHtmlControl::getControl($params['control_type'], $name, $params);
    }

    /**
     * Рендерит зарегистрированный UI-контрол.
     *
     * Режимы:
     * - класс существует и execute() вернул string -> только PHP;
     * - класс существует и execute() вернул array  -> PHP + шаблон;
     * - класс отсутствует                          -> только шаблон.
     *
     * @param string $name
     * @param array<string, mixed> $params
     * @return string
     * @throws waException
     */
    public static function render(string $name, array $params = []): string
    {
        $component_class    = (string) ifset($params['component_class'], '');
        $component_template = (string) ifset($params['component_template'], '');

        if ($component_class === '' && $component_template === '') {
            return '';
        }

        $params['name'] = $name;

        if ($component_class !== '' && class_exists($component_class)) {
            if (!method_exists($component_class, 'execute')) {
                throw new waException("Метод execute() в классе {$component_class} не определён.");
            }

            $result = $component_class::execute($params);

            if (is_string($result)) {
                return $result;
            }

            if (is_array($result)) {
                $params = array_merge($params, $result);
            } else {
                throw new waException("Метод execute() в классе {$component_class} должен вернуть string или array.");
            }
        }

        if ($component_template === '') {
            return '';
        }

        return shopLkPluginFetchHtml::getHtml([
            'file'   => "plugins/lk/lib/classes/ui/templates/{$component_template}.html",
            'assign' => $params,
        ]);
    }

    /**
     * Рендерит произвольный Smarty-шаблон без UI-контрола.
     *
     * Поддерживаемые параметры:
     * - file   => путь к шаблону относительно wa-apps/plugins/lk/;
     * - assign => массив переменных шаблона.
     *
     * Если assign не передан, в шаблон передаются все параметры,
     * кроме служебного ключа file.
     *
     * @param array<string, mixed> $params
     * @return string
     * @throws waException
     */
    public static function getHtml(array $params = []): string
    {
        $file = (string) ifset($params['file'], '');

        if ($file === '') {
            return '';
        }

        if (array_key_exists('assign', $params)) {
            $assign = (array) $params['assign'];
        } else {
            $assign = $params;
            unset($assign['file']);
        }

        return shopLkPluginFetchHtml::getHtml([
            'file'   => $file,
            'assign' => $assign,
        ]);
    }
}
