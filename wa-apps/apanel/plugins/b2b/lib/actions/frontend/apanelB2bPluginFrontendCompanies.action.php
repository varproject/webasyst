<?php

/**
 * apanelB2bPluginFrontendCompaniesAction
 *
 * Frontend screen «Компании» B2B-кабинета.
 *
 * Назначение:
 * - открыть список компаний B2B-витрины;
 * - подготовить базовые данные компаний;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendCompaniesAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'companies';

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $data['companies'] = [
            'items' => [],
            'total' => 0,
        ];
    }
}
