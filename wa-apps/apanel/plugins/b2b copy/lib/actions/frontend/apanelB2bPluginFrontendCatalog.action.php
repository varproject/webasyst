<?php

/**
 * apanelB2bPluginFrontendCatalogAction
 *
 * Frontend screen «Каталог» B2B-кабинета.
 *
 * Назначение:
 * - открыть каталог B2B-витрины;
 * - подготовить базовые параметры каталога;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendCatalogAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'catalog';

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $data['catalog'] = [
            'products' => [],
            'total'    => 0,
            'page'     => max(1, waRequest::get('page', 1, waRequest::TYPE_INT)),
        ];
    }
}
