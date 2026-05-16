<?php

abstract class shopB2bPluginChannelSettingsAction extends waViewAction
{
    protected string $tab_id = 'main';
    protected string $service_class = 'shopB2bPluginChannelMainSettingsService';

    public function execute()
    {
        $this->checkSettingsRights();

        $channel_id = waRequest::get('channel_id', 0, waRequest::TYPE_INT);
        if ($channel_id <= 0) {
            throw new waException('B2B-канал не найден.', 404);
        }

        /** @var shopB2bPluginChannelSettingsService $service */
        $service = new $this->service_class();
        $channel = $service->getChannel($channel_id);
        $data = method_exists($service, 'getViewData') ? $service->getViewData($channel) : array();

        $tabs = shopB2bPluginChannelSettingsTabs::getTabs($channel_id, $this->tab_id);

        $this->view->assign($data + array(
            'channel' => $channel,
            'channel_id' => $channel_id,
            'tabs' => $tabs,
            'active_tab' => $this->tab_id,
            'tabs_template' => wa()->getAppPath('plugins/b2b/templates/actions/channels/SettingsTabs.inc.html', 'shop'),
            'save_url' => $tabs[$this->tab_id]['save_url'],
        ));
    }

    protected function checkSettingsRights(): void
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
