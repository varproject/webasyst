<?php

class shopB2bPluginBackendSaveSettingsController extends waJsonController
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('b2b');

        $settings = waRequest::post('settings', [], waRequest::TYPE_ARRAY);

        $plugin->saveSettings($settings);

        $this->response = [
            'status' => 'ok',
        ];
    }
}
