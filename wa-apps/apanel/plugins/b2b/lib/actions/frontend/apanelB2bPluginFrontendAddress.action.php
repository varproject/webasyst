<?php

/**
 * apanelB2bPluginFrontendAddressAction
 *
 * Frontend action просмотра одного адреса B2B-кабинета.
 *
 * Назначение:
 * - открыть карточку адреса по ID из routing;
 * - использовать screen addresses;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendAddressAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'addresses';

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

        $data['address'] = [
            'id' => $id,
        ];
    }
}
