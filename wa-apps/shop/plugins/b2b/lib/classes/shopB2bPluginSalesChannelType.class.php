<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    protected function getBaseFieldsConfig(): array
    {
        $base = parent::getBaseFieldsConfig();
        $base['description']['class'] = 'smallest';

        return ['status' => [
            'title' => _w('Enabled'),
            'control_type' => waHtmlControl::CHECKBOX,
        ]] + $base;
    }

    public function getFormHtml(array $channel): string
    {
        dd($channel);
        return (string) new waLazyDisplay(new shopB2bPluginChannelsAction([
            'channel' => $channel,
            'base_form_fields_html' => $this->getBaseRenderedFields($channel),
        ]));
    }

    public function sanitizeAndValidateParams(?int $id, array &$params, $params_mode): array
    {
        // if ($params_mode == 'set' && empty($params['stock_id'])) {
        //     $errors['stock_id'] = [
        //         'error_description' => _wp('This is a required field.'),
        //         'field' => 'data[params][stock_id]',
        //     ];
        // }

        return array_values($errors ?? []);
    }

    // Возвращает базовые поля формы настроек
    protected function getBaseRenderedFields(array $channel): array
    {
        $result = [];

        if (empty($channel['id'])) {
            $channel['name'] = $this->get('name');
        }

        $field_params = ['namespace' => 'data'] + $this->getFormFieldParams();

        foreach ($this->getBaseFieldsConfig() as $name => $row) {
            $result[$name] = $this->getControl($name, ifset($channel, $name, ''), $field_params + $row);
        }

        return $result;
    }







    // public function getFormHtml(array $channel): string
    // {
    //     $tab_view           = wa('shop')->getView();
    //     $tab_tpl_dir        = wa()->getAppPath('plugins/b2b/templates/actions/channels/', 'shop');

    //     $channel_id         = (int) $channel['id'] ?? 0;

    //     $tab_data           = shopB2bPluginChannelSettingsTabs::getTabs($channel_id);
    //     $tab_items          = $tab_data['all'] ?? [];
    //     $active_tab         = $tab_data['active'] ?? [];

    //     $active_tab_tpl     = $tab_tpl_dir . ($active_tab['tpl'] ?? '');
    //     $active_tab_content = $tab_view->fetch($active_tab_tpl);

    //     $tab_view->assign([
    //         'channel'     => $channel,
    //         'tabs'        => $tab_items,
    //         'tab_content' => $active_tab_content,
    //     ]);

    //     return $tab_view->fetch($tab_tpl_dir . 'ChannelSettings.html');
    // }

}
