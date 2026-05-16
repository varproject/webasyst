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

        $active_tab = shopB2bPluginChannelSettingsTabs::normalizeTabId(waRequest::get('b2b_tab', 'main', waRequest::TYPE_STRING_TRIM));
        if ($channel_id <= 0) {
            $active_tab = 'main';
        }

        $main_service = new shopB2bPluginChannelMainSettingsService();
        $main_data = $main_service->getViewData($channel);
        $tabs = shopB2bPluginChannelSettingsTabs::getTabs($channel_id, $active_tab);
        $tab_config = $this->getTabRenderConfig($active_tab);

        /** @var shopB2bPluginChannelSettingsService $tab_service */
        $tab_service = new $tab_config['service']();
        $tab_data = $tab_service->getViewData($channel);

        $tab_view = wa('shop')->getView();
        $tab_view->assign($tab_data + array(
            'channel' => $channel,
            'channel_id' => $channel_id,
            'base_fields' => $active_tab === 'main' ? $this->getBaseRenderedFields($channel) : array(),
            'tabs' => $tabs,
            'active_tab' => $active_tab,
            'show_tabs' => false,
            'tabs_template' => wa()->getAppPath('plugins/b2b/templates/actions/channels/SettingsTabs.inc.html', 'shop'),
            'save_url' => ifset($tabs, $active_tab, 'save_url', ''),
        ));
        $tab_content = $tab_view->fetch('file:' . wa()->getAppPath($tab_config['template'], 'shop'));

        $view->assign(array(
            'channel' => $channel,
            'channel_id' => $channel_id,
            'tabs' => $tabs,
            'active_tab' => $active_tab,
            'settings' => $main_data['settings'],
            'tabs_template' => wa()->getAppPath('plugins/b2b/templates/actions/channels/SettingsTabs.inc.html', 'shop'),
            'tab_content' => $tab_content,
        ));

        return $view->fetch('file:' . wa()->getAppPath('plugins/b2b/templates/actions/channels/b2b_channel.include.html', 'shop'));
    }

    protected function getTabRenderConfig(string $tab_id): array
    {
        $map = array(
            'main' => array(
                'service' => 'shopB2bPluginChannelMainSettingsService',
                'template' => 'plugins/b2b/templates/actions/channelMain/ChannelMain.html',
            ),
            'users' => array(
                'service' => 'shopB2bPluginChannelUsersSettingsService',
                'template' => 'plugins/b2b/templates/actions/channelUsers/ChannelUsers.html',
            ),
            'catalog' => array(
                'service' => 'shopB2bPluginChannelCatalogSettingsService',
                'template' => 'plugins/b2b/templates/actions/channelCatalog/ChannelCatalog.html',
            ),
            'pages' => array(
                'service' => 'shopB2bPluginChannelPagesService',
                'template' => 'plugins/b2b/templates/actions/channelPages/ChannelPages.html',
            ),
            'blog' => array(
                'service' => 'shopB2bPluginChannelBlogSettingsService',
                'template' => 'plugins/b2b/templates/actions/channelBlog/ChannelBlog.html',
            ),
            'support' => array(
                'service' => 'shopB2bPluginChannelSupportSettingsService',
                'template' => 'plugins/b2b/templates/actions/channelSupport/ChannelSupport.html',
            ),
            'cart' => array(
                'service' => 'shopB2bPluginChannelCartSettingsService',
                'template' => 'plugins/b2b/templates/actions/channelCart/ChannelCart.html',
            ),
        );

        return ifset($map, $tab_id, $map['main']);
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