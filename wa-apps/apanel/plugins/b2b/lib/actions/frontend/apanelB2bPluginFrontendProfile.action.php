<?php

/**
 * apanelB2bPluginFrontendProfileAction
 *
 * Frontend screen «Профиль» B2B-кабинета.
 *
 * Назначение:
 * - открыть профиль пользователя B2B-витрины;
 * - подготовить текущий contact;
 * - использовать общий runtime и shell Apanel.
 */
class apanelB2bPluginFrontendProfileAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'profile';

    /**
     * Подготавливает дополнительные данные для шаблона.
     *
     * @param array $data Runtime-данные.
     * @param array $screen Screen.
     * @return void
     */
    protected function prepareViewData(&$data, $screen)
    {
        $data['contact'] = wa()->getUser();
    }
}
