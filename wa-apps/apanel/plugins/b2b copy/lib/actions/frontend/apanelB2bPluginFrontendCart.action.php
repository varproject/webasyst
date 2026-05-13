<?php

/**
 * apanelB2bPluginFrontendCartAction
 *
 * Frontend screen «Корзина» B2B-кабинета.
 *
 * Назначение:
 * - открыть корзину B2B-витрины;
 * - подготовить базовые данные корзины;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendCartAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'cart';

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $data['cart'] = [
            'items' => [],
            'total' => 0,
        ];
    }
}
