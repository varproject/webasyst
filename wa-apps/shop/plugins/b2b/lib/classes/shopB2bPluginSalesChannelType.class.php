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
        $tab_tpl_dir        = wa()->getAppPath('plugins/b2b/templates/actions/channels/', 'shop');

        $channel_id         = (int) $channel['id'] ?? 0;

        $tab_data           = shopB2bPluginChannelSettingsTabs::getTabs($channel_id);
        $tab_items          = $tab_data['all'] ?? [];
        $active_tab         = $tab_data['active'] ?? [];

        $active_tab_tpl     = $tab_tpl_dir . ($active_tab['tpl'] ?? '');
        // $active_tab_content = $tab_view->fetch($active_tab_tpl);

        $active_tab_module = $active_tab['class'];
        $active_tab_html = new waLazyDisplay(new $active_tab_module());

        // dd($active_tab_html);
        // dd(wa()->getPlugin('b2b'));

        $tab_view           = wa('shop')->getView();
        $tab_view->assign([
            'channel'     => $channel,
            'tabs'        => $tab_items,
            // 'tab_content' => $active_tab_html,
        ]);

        return $tab_view->fetch($tab_tpl_dir . 'ChannelSettings.html');
    }
}
