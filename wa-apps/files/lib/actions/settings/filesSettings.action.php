<?php

class filesSettingsAction extends filesController
{
    public function execute()
    {
        $this->assign(array(
            'settings' => $this->getSettings(),
            'variants' => $this->getVariants()
        ));
    }

    public function getSettings()
    {
        $config = $this->getConfig();
        return array(
            'upload_file_notification' => $config->getUploadFileNotification(),
            'app_on_count' => $config->getAppOnCount()
        );
    }

    public function getVariants()
    {
        $config = $this->getConfig();
        return array(
            'upload_file_notification' => $config->getUploadFileNotificationVariants(),
            'app_on_count' => $config->getAppOnCountVariants()
        );
    }
}