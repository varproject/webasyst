<?php

/**
 * apanelB2bPluginFrontendOrdersAction
 *
 * Frontend screen «Заказы» B2B-кабинета.
 *
 * Назначение:
 * - открыть список заказов B2B-витрины;
 * - подготовить базовые данные списка заказов;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendOrdersAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'orders';

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $data['orders'] = [
            'items' => [],
            'total' => 0,
            'page'  => max(1, waRequest::get('page', 1, waRequest::TYPE_INT)),
        ];
    }
}
