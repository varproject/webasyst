<?php

abstract class shopB2bPluginChannelSettingsSaveController extends waJsonController
{
    protected string $service_class = 'shopB2bPluginChannelMainSettingsService';

    public function execute()
    {
        $this->checkSettingsRights();

        $channel_id = waRequest::post('channel_id', 0, waRequest::TYPE_INT);
        $settings = waRequest::post('settings', array(), waRequest::TYPE_ARRAY);

        if ($channel_id <= 0) {
            $this->errors[] = array('error_description' => 'B2B-канал не найден.');
            return;
        }

        /** @var shopB2bPluginChannelSettingsService $service */
        $service = new $this->service_class();
        $service->getChannel($channel_id);
        $normalized = method_exists($service, 'normalize') ? $service->normalize($settings) : $settings;
        $errors = method_exists($service, 'validate') ? $service->validate($channel_id, $normalized) : array();

        if ($errors) {
            $this->errors = $errors;
            return;
        }

        if (method_exists($service, 'save')) {
            $service->save($channel_id, $settings);
        } else {
            $service->updateParams($channel_id, $normalized);
        }

        $this->response = array('channel_id' => $channel_id);
    }

    protected function checkSettingsRights(): void
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
