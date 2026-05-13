<?php

/**
 * apanelB2bPluginFrontendOrderAction
 *
 * Frontend action просмотра одного заказа B2B-кабинета.
 *
 * Назначение:
 * - открыть карточку заказа по ID из routing;
 * - использовать screen orders;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendOrderAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'orders';

    /**
     * Возвращает screen action.
     *
     * @return string
     */
    protected function getScreenAction()
    {
        return 'view';
    }

    /**
     * Возвращает параметры screen action.
     *
     * @return array
     */
    protected function getScreenParams()
    {
        return [
            'id' => waRequest::param('id', 0, waRequest::TYPE_INT),
        ];
    }

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

        $data['order'] = [
            'id' => $id,
        ];
    }
}
