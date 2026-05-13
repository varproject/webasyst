<?php

/**
 * apanelB2bPluginFrontendDashboardAction
 *
 * Frontend screen «Главная» B2B-кабинета.
 *
 * Назначение:
 * - открыть стартовый экран B2B-витрины;
 * - использовать общий runtime и shell Apanel через apanelB2bPluginFrontendAction.
 */
class apanelB2bPluginFrontendDashboardAction extends apanelB2bPluginFrontendAction
{
    /**
     * ID screen.
     *
     * @var string
     */
    protected $screen_id = 'dashboard';
}
