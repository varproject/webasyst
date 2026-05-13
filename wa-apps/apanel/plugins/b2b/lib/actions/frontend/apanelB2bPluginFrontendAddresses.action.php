<?php

/**
 * apanelB2bPluginFrontendAddressesAction
 *
 * Frontend screen «Адреса» B2B-кабинета.
 *
 * Назначение:
 * - открыть список адресов B2B-витрины;
 * - подготовить базовые данные адресов;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendAddressesAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'addresses';

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $data['addresses'] = [
            'items' => [],
            'total' => 0,
        ];
    }
}
