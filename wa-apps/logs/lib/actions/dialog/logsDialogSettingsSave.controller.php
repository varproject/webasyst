<?php

class logsDialogSettingsSaveController extends waJsonController
{
    private $settings = [];

    public function execute()
    {
        try {
            $this->settings = waRequest::post('settings', [], waRequest::TYPE_ARRAY);

            $this->handlePersonalSettings();

            if ($this->getRights('change_settings')) {
                $this->handlePhpSettings();
                $this->handleCommonSettings();
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function handlePersonalSettings()
    {
        $settings = ifset($this->settings, 'personal', []);

        $fields = [
            'large_logs_notify' => 0,
            'email_updated_files' => 0,
            'files_check_words' => '',
            'remember_sort_mode' => 0,
        ];

        $contact_settings_model = new waContactSettingsModel();

        foreach ($fields as $setting => $default_value) {
            $value = ifset($settings, $setting, $default_value);

            if ($setting == 'files_check_words') {
                $value = $this->getPreparedValueFilesCheckWords($value);
            }

            $contact_settings_model->set(
                $this->getUserId(),
                $this->getAppId(),
                $setting,
                $value
            );
        }
    }

    private function getPreparedValueFilesCheckWords($value)
    {
        $result = trim($value);
        $result = mb_strtolower($result);
        $result = explode(PHP_EOL, $result);
        $result = array_map('trim', $result);
        $result = array_filter($result, 'strlen');
        $result = array_unique($result);
        $result = implode(PHP_EOL, $result);

        return $result;
    }

    private function handlePhpSettings()
    {
        $settings = ifset($this->settings, 'php', []);

        $error = (string) (new logsPhpLogging())->setSetting(
            !empty($settings['php_log']),
            ifset($settings, 'php_log_errors', null)
        );

        if (strlen($error)) {
            throw new Exception($error);
        }
    }

    private function handleCommonSettings()
    {
        $settings = ifset($this->settings, 'common', []);

        if (!isset($settings['hide'])) {
            //save empty array to differentiate it from null
            // when there is no value yet and a default value must be used
            $settings['hide'] = [
                'root_path' => [],
                'ip' => [],
            ];
        }

        $app_settings_model = new waAppSettingsModel();

        foreach ($settings as $name => $value) {
            $app_settings_model->set($this->getAppId(), $name, is_array($value) ? json_encode($value) : $value);
        }
    }
}
