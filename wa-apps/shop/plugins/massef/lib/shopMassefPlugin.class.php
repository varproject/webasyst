<?php

// PHP 7.4 compatibility: load string polyfills (str_* functions)
require_once dirname(__FILE__) . '/classes/polyfills/polyfills.php';
 
/**
 * Плагин Massef: подключение ассетов и регистрация массового действия.
 *
 * Подключает CSS/JS плагина через WA2-хук и добавляет действие
 * «Редактировать характеристики» в блок массовых действий «Редактирование».
 *
 * @author  Petrosian Vagram
 * @since   1.0.0 (2025-11-02)
 */
class shopMassefPlugin extends shopPlugin
{
    /**
     * Хук backendProdMassActions (WA2).
     *
     * Регистрирует диалог массового редактирования характеристик, подключает
     * фронтенд-ассеты плагина для корректной работы диалога и логики батч-сохранения.
     *
     * @param array<string,mixed> $params Параметры массовых действий (по ссылке)
     * @return void
     */
    public function backendProdMassActions(&$params)
    {
        $wa_app_url = wa()->getAppUrl(null, true);

        // Ассеты плагина
        $this->addCss('css/massef.css');
        $this->addJs('js/massef.js');
        $this->addJs('js/massef.progress.js');

        // Описание действия для меню массовых операций
        $action = [
            'id'         => $this->id . '_edit',
            'name'       => _wp('Характеристики'),
            'icon'       => '<i class="fas fa-cubes text-blue"></i>',
            'pinned'     => true,
            'dialog_url' => $wa_app_url . '?plugin=' . $this->id . '&module=dialog',
        ];

        // Добавляем в секцию "Редактирование"
        if (!empty($params['actions']['edit']['actions'])) {
            array_unshift($params['actions']['edit']['actions'], $action);
        }
    }
}
