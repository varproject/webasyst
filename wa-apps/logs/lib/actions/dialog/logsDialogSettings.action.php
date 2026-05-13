<?php

class logsDialogSettingsAction extends waViewAction
{
    private $controls = [];

    public function execute()
    {
        try {
            $this->addPersonalSettings();
            $this->addCommonSettings();

            $controls_html = $this->getControlsHtml();

            $this->view->assign('controls', $controls_html);
            $this->view->assign('cron_command', 'php ' . $this->getConfig()->getRootPath() . '/cli.php logs check');
            $this->view->assign('last_cron_datetime', (int) wa()->getSetting('last_cli_timestamp_check'));
            $this->view->assign('in_cloud', logsHelper::inCloud());
            $this->view->assign('premium', logsLicensing::check()->hasPremiumLicense());
        } catch (Exception $e) {
            $this->view->assign('error', $e->getMessage());
        }
    }

    private function addPersonalSettings()
    {
        $csm = new waContactSettingsModel();
        $large_logs_notify_setting = $csm->getOne($this->getUserId(), $this->getAppId(), 'large_logs_notify');

        $this->controls = [
            'large_logs_notify' => [
                'title' => _w('Notify me on large logs size'),
                'control_type' => waHtmlControl::CHECKBOX,
                'namespace' => 'personal',
                'value' => 1,
                'checked' => strlen(strval($large_logs_notify_setting)) ? (bool) (int) $large_logs_notify_setting : true,    //enabled by default
                'description' => sprintf(
                    _w('Show the %s&nbsp;badge next to the app’s icon in the main menu when the total logs size exceeds 1&nbsp;GB.'),
                    sprintf(
                        '<span class="badge red">%s</span>',
                        _w('1+ GB')
                    )
                ),
            ],
        ];

        if (logsLicensing::check()->hasPremiumLicense()) {
            $this->controls = array_merge($this->controls, [
                'remember_sort_mode' => [
                    'title' => _w('Remember sort order'),
                    'description' => _w('Sort files and directories according to your last choice when opening the app’s home page.'),
                    'control_type' => waHtmlControl::CHECKBOX,
                    'namespace' => 'personal',
                    'value' => '1',
                    'checked' => (bool) (int) $csm->getOne($this->getUserId(), $this->getAppId(), 'remember_sort_mode'),
                ],
            ]);

            $missing_email_notice = $this->getUser()->get('email', 'default') ? '' :
                '<br><br>'
                . '<span class="highlighted">'
                . sprintf_wp(
                    'You have no email address specified in your <a %s>user profile</a>; no notifications will be sent to you until you add an address.',
                    sprintf('href="%s"', $this->getConfig()->getBackendUrl(true) . '?module=profile')
                )
                . '</span>';

            $this->controls = array_merge($this->controls, [
                'email_updated_files' => [
                    'title' => _w('Notify me on updated tracked files'),
                    'description' => _w('Send me the names of updated tracked files by email.')
                        . '<br>'
                        . _w('<a href="javascript:void(0);" class="show-cron-setup-link">CRON setup</a> is required.')
                        . $missing_email_notice,
                    'control_type' => waHtmlControl::CHECKBOX,
                    'namespace' => 'personal',
                    'value' => 1,
                    'checked' => (bool) (int) $csm->getOne($this->getUserId(), $this->getAppId(), 'email_updated_files'),
                ],
            ]);

            $this->controls = array_merge($this->controls, [
                'files_check_words' => [
                    'title' => _w('Check words'),
                    'description' => _w('Write <strong>words</strong> or <strong>groups of words</strong>, each on a separate line, that must be available in updated tracked log files that you want to be notified of by email.'),
                    'control_type' => waHtmlControl::TEXTAREA,
                    'namespace' => 'personal',
                    'value' => $csm->getOne($this->getUserId(), $this->getAppId(), 'files_check_words'),
                ],
            ]);
        }
    }

    private function addCommonSettings()
    {
        if ($this->getRights('change_settings')) {
            $php_logging = new logsPhpLogging();
            $php_log_setting = $php_logging->getSetting();
            $php_log_errors = $php_logging->getSetting('errors');

            if (!$php_log_errors) {
                $php_log_errors = logsPhpLogging::getDefaultErrors();
            }

            $this->controls = array_merge($this->controls, [
                'php_log' => [
                    'title' => _w('Enable PHP error log'),
                    'control_type' => waHtmlControl::CHECKBOX,
                    'namespace' => 'php',
                    'value' => 1,
                    'checked' => !empty($php_log_setting),
                    'description' => $this->getPhpLoggingDescription($php_log_setting),
                    'class' => 'php_log_setting',
                ],
                'php_log_errors' => [
                    'title' => _w('Types of PHP errors'),
                    'control_type' => waHtmlControl::GROUPBOX,
                    'options' => [
                        [
                            'value' => 'E_ALL',
                            'title' => _w('all'),
                            'description' => 'E_ALL',
                        ],
                        [
                            'value' => 'E_ERROR',
                            'title' => _w('fatal errors'),
                            'description' => 'E_ERROR',
                        ],
                        [
                            'value' => 'E_WARNING',
                            'title' => _w('warnings'),
                            'description' => 'E_WARNING',
                        ],
                    ],
                    'value' => $php_log_errors,
                    'namespace' => 'php',
                    'field_style' => $php_log_setting ? '' : 'display: none',
                ],
                'root_path' => [
                    'title' => _w('Show relative file paths instead of absolute paths in error logs'),
                    'namespace' => ['common', 'hide'],
                    'control_type' => waHtmlControl::GROUPBOX,
                    'options' => [
                        'frontend' => _w('in published files'),
                        'backend' => _w('in backend'),
                    ],
                    'value' => array_fill_keys(logsHelper::getHideSetting('root_path'), 1),
                ],
                'ip' => [
                    'title' => _w('Hide IP addresses in error logs'),
                    'namespace' => ['common', 'hide'],
                    'control_type' => waHtmlControl::GROUPBOX,
                    'options' => [
                        'frontend' => _w('in published files'),
                        'backend' => _w('in backend'),
                    ],
                    'value' => array_fill_keys(logsHelper::getHideSetting('ip'), 1),
                ],
            ]);
        }
    }

    private function getPhpLoggingDescription($php_log_setting)
    {
        $debug_mode_setting_name = _ws('Developer mode');
        $end_time = $php_log_setting && !empty($php_log_setting['time']) ? date('H:i', $php_log_setting['time'] + 3600) : null;

        $php_logging = new logsPhpLogging();
        $php_logging_admin_config_enabled = $php_logging->adminConfigEnabled();
        $system_settings_url = wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/';

        $description = _w('PHP error messages are saved to file <em>wa-log/<b>php.log</b></em>.');
        $description .= '<br><br>';

        if (logsHelper::inCloud()) {
            $description .= '<span class="bold black">';

            if ($php_logging_admin_config_enabled) {
                if ($php_log_setting) {
                    $description .= _w('PHP errors are being logged without limitations, because <tt>php_logging_admin</tt> parameter is enabled in Logs’ configuration file.');
                    $description .= ' ' . sprintf(
                        _w('To make PHP errors be logged only during 1 hour, disable <tt>php_logging_admin</tt> parameter in file <em>%s</em>.'),
                        'wa-config/apps/logs/config.php'
                    );
                } else {
                    $description .= _w('PHP errors will be logged without limitations, because <tt>php_logging_admin</tt> parameter is enabled in Logs’ configuration file.');
                    $description .= ' ' . sprintf(
                        _w('To make PHP errors be logged only during 1 hour, disable <tt>php_logging_admin</tt> parameter in file <em>%s</em>.'),
                        'wa-config/apps/logs/config.php'
                    );
                }
            } else {
                if ($end_time) {
                    $description .= sprintf(_w('PHP errors will be logged during 1 hour only (until %s), to prevent large error logs from occupying server disk space.'), $end_time);
                } else {
                    $description .= _w('PHP errors will be logged during 1 hour only, to prevent large error logs from occupying server disk space.');
                }

                $description .= ' ' . _w('Re-enable logging after this time expires, if necessary.');
            }

            $description .= '</span> ';
        } else {
            //not in Cloud

            if (waSystemConfig::isDebug()) {
                $description .= '<span class="bold black">';

                if ($php_log_setting) {
                    $description .= sprintf(
                        _w('PHP errors are being logged without limitations, because the “%s” is enabled <a href="%s" target="_blank">in&nbsp;system settings</a>.'),
                        $debug_mode_setting_name,
                        $system_settings_url
                    )
                        . ' ' . sprintf(_w('To make PHP errors be logged only during 1 hour, disable the “%s”.'), $debug_mode_setting_name);
                } else {
                    $description .= sprintf(
                        _w('PHP errors will be logged without limitations, because “%s” is enabled <a href="%s" target="_blank">in&nbsp;system settings</a>.'),
                        $debug_mode_setting_name,
                        $system_settings_url
                    )
                        . ' ' . sprintf(_w('To make PHP errors be logged only during 1 hour, disable the “%s”.'), $debug_mode_setting_name);
                }

                $description .= '</span> ';
            } else {
                $description .= '<span class="bold black">';
                if ($end_time) {
                    $description .= sprintf(
                        _w('PHP errors will be logged during 1 hour only (until %s), to prevent large error logs from occupying server disk space.'),
                        $end_time
                    );
                } else {
                    $description .= _w('PHP errors will be logged during 1 hour only, to prevent large error logs from occupying server disk space.');
                }

                $description .= ' ' . _w('Re-enable logging after this time expires, if necessary.')
                    . '<br>'
                    . sprintf(
                        _w('This limitation will not be applied if you enable “%s” setting <a href="%s" target="_blank">in&nbsp;system settings</a>.'),
                        $debug_mode_setting_name,
                        $system_settings_url
                    );
                $description .= '</span> ';
            }
        }

        return $description;
    }

    private function getControlsHtml()
    {
        $html_parts = [];

        foreach ($this->controls as $control_name => $control) {
            $this->setControlNamespace($control);

            $style = isset($control['field_style']) ? sprintf(' style="%s"', $control['field_style']) : '';

            $control += [
                'control_wrapper' => '<div class="field field-' . str_replace('_', '-', $control_name)
                    . '"' . $style . '><div class="name">%s</div><div class="value">%s<br>%s<br><br></div></div>',
                'title_wrapper' => '%s',
                'description_wrapper' => '<span class="hint">%s</span>',
            ];

            $html_parts[] = waHtmlControl::getControl($control['control_type'], $control_name, $control);
        }

        return implode('', $html_parts);
    }

    private function setControlNamespace(&$control)
    {
        if (isset($control['namespace'])) {
            $control_temp = $control;
            $control_temp['namespace'] = ['settings'];
            waHtmlControl::addNamespace($control_temp, $control['namespace']);
            $control['namespace'] = $control_temp['namespace'];
        } else {
            $control['namespace'] = 'settings';
        }
    }
}
