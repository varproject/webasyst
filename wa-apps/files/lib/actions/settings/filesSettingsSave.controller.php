<?php

class filesSettingsSaveController extends filesController
{
    public function execute()
    {
        $settings = $this->getSettings();
        $this->getConfig()->export($settings);
    }

    public function getSettings()
    {
        $variants = $this->getVariants();

        $settings = array();
        foreach (wa()->getRequest()->post('settings') as $name => $value) {
            if (isset($variants[$name]) && in_array($value, $variants[$name])) {
                $settings[$name] = $value;
            }
        }
        return $settings;
    }

    public function getVariants()
    {
        $config = $this->getConfig();
        return array(
            'upload_file_notification' => $config->getUploadFileNotificationVariants(true),
            'app_on_count' => $config->getAppOnCountVariants(true)
        );
    }
}