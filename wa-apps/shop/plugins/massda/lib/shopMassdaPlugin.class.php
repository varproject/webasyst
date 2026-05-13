<?php

/**
 * Плагин MassDA для Shop-Script.
 *
 * Назначение:
 * - Добавляет своё массовое действие в список массовых операций товаров.
 * - Использует диалоговый интерфейс для запуска процесса удаления артикулов.
 *
 * Основные элементы:
 * 1) shopMassdaPlugin::backendProdMassActions()
 *    — Хук backend_prod_mass_actions.
 *    — Подмешивает своё действие "Удалить артикулы (sku)" в общий список.
 *    — Регистрирует URL диалога и подключает ресурсы плагина.
 *
 * 2) smarty_modifier_massda_end()
 *    — Модификатор Smarty для склонения слова по числу.
 *    — Умеет возвращать как "N слово", так и просто слово.
 *
 * @author  Petrosian Vagram
 * @since   1.1.0 (2025-11-22)
 */
class shopMassdaPlugin extends shopPlugin
{
    /**
     * Регистрирует массовое действие «Удалить артикулы (sku)».
     * формирует URL диалога, через который запускается обработка SKU.
     *
     * @param array $params Параметры доступных массовых действий (передаются по ссылке)
     * @return void
     */
    public function backendProdMassActions(&$params)
    {
        $this->addJs('js/htmx.min.js');
        $this->addCss('css/massda-dialog.css');

        $wa_app_url = wa()->getAppUrl(null, true);

        $action = [
            'id'         => $this->id . '_deleting_articles',
            'name'       => _wp('Удалить артикулы (sku)'),
            'icon'       => '<i class="fas fa-trash text-red"></i>',
            'pinned'     => true,
            'dialog_url' => $wa_app_url . '?plugin=' . $this->id . '&module=dialog',
        ];

        if (!empty($params['actions']['edit']['actions'])) {
            array_unshift($params['actions']['edit']['actions'], $action);
        }
    }

    /**
     * Поддержка старого интерфейса (UI 1.3) – пункт в правой панели "Организовать"
     */
    public function backendProducts()
    {
        $wa_app_url = wa()->getAppUrl(null, true);

        wa()->getResponse()->addJs(wa_url() . 'wa-content/js/jquery-wa/wa.dialog.js');
        $this->addJs('js/htmx.min.js');
        $this->addJs('js/massda-legacy.js');
        $this->addCss('css/massda-dialog.css');

        return [
            'toolbar_organize_li' =>
            '<li>
            <a href="#"
               id="' . $this->id . '_deleting_articles' . '"
               class="massda-delete-skus-legacy"
               data-dialog-url="' . $wa_app_url . '?plugin=' . $this->id . '&module=dialog">
                <i class="icon16 cross"></i>
                ' . _wp('Удалить (SKU)') . '
            </a>
        </li>',
        ];
    }
}


/**
 * Модификатор Smarty: склонение слова по числу.
 *
 * Возвращает строку вида:
 *   - "5 товаров"
 *   - "товаров" (если $with_number = false)
 *
 * Логику склонений выполняет на основе трёх форм слова:
 *   1) единственное
 *   2) родительный
 *   3) множественное
 *
 * @param int    $number       Число, влияющее на склонение слова
 * @param string $forms_string Формы слова через запятую: "товар, товара, товаров"
 * @param bool   $with_number  Добавлять ли число в возвращаемую строку
 *
 * @return string Готовая строка со склонением
 */
function smarty_modifier_massda_end($number, $forms_string, $with_number = true)
{
    $forms = array_map('trim', explode(',', $forms_string));

    // Защита: если форм меньше 3 — дублируем последнюю
    while (count($forms) < 3) {
        $forms[] = end($forms);
    }

    $abs = abs((int)$number);
    $n100 = $abs % 100;
    $n10  = $abs % 10;

    if ($n100 >= 11 && $n100 <= 14) {
        $word = $forms[2];
    } elseif ($n10 == 1) {
        $word = $forms[0];
    } elseif ($n10 >= 2 && $n10 <= 4) {
        $word = $forms[1];
    } else {
        $word = $forms[2];
    }

    return $with_number ? "{$number} {$word}" : $word;
}
