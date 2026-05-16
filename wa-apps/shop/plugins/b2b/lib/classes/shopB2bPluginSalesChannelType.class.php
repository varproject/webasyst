<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    protected function getBaseFieldsConfig(): array
    {
        $base = parent::getBaseFieldsConfig();
        $base['description']['class'] = 'smallest';
        return $base;
    }

    public function getFormHtml(array $channel): string
    {
        $view = wa('shop')->getView();
        $channel_id = (int) ifset($channel, 'id', 0);
        $channel['params'] = ifset($channel, 'params', array());

        $service = new shopB2bPluginChannelMainSettingsService();
        $main_data = $service->getViewData($channel);
        $tabs = shopB2bPluginChannelSettingsTabs::getTabs($channel_id, 'main');

        $tab_view = wa('shop')->getView();
        $tab_view->assign($main_data + array(
            'channel' => $channel,
            'channel_id' => $channel_id,
            'base_fields' => $this->getBaseRenderedFields($channel),
            'tabs' => $tabs,
            'active_tab' => 'main',
            'tabs_template' => wa()->getAppPath('plugins/b2b/templates/actions/channels/SettingsTabs.inc.html', 'shop'),
            'save_url' => ifset($tabs, 'main', 'save_url', ''),
        ));
        $tab_content = $tab_view->fetch('file:' . wa()->getAppPath('plugins/b2b/templates/actions/channelMain/ChannelMain.html', 'shop'));

        $view->assign(array(
            'channel' => $channel,
            'channel_id' => $channel_id,
            'tabs' => $tabs,
            'active_tab' => 'main',
            'settings' => $main_data['settings'],
            'tabs_template' => wa()->getAppPath('plugins/b2b/templates/actions/channels/SettingsTabs.inc.html', 'shop'),
            'tab_content' => $tab_content,
        ));

        return $view->fetch('file:' . wa()->getAppPath('plugins/b2b/templates/actions/channels/b2b_channel.include.html', 'shop'));
    }

    protected function getBaseRenderedFields(array $channel): array
    {
        $result = array();
        if (empty($channel['id'])) {
            $channel['name'] = $this->get('name');
        }

        $field_params = array('namespace' => 'data') + $this->getFormFieldParams();
        foreach ($this->getBaseFieldsConfig() as $name => $row) {
            $result[$name] = $this->getControl($name, ifset($channel, $name, ''), $field_params + $row);
        }

        return $result;
    }

    public function sanitizeAndValidateParams(?int $id, array &$params, $params_mode): array
    {
        $service = new shopB2bPluginChannelMainSettingsService();
        return $service->sanitizeSalesChannelParams($id, $params);
    }

    public function onSave(array $channel) {}
}
