<?php

class apanelPluginsSettingsDialogAction extends waViewAction
{
    public function execute()
    {
        $this->checkRights();

        $this->setTemplate('plugins/PluginsSettingsDialog.html', true);

        $plugin_id = waRequest::get('id', '', waRequest::TYPE_STRING_TRIM);
        $catalog = $this->getCatalog();

        $plugin_info = $catalog->getPluginInfo($plugin_id);
        $controls = $catalog->getSettingsControls($plugin_id);

        $this->view->assign([
            'plugin_id'        => $plugin_id,
            'plugin_info'      => $plugin_info,
            'controls'         => $controls,
            'modal_title'      => 'Настройки плагина',
            'modal_size'       => 'modal-lg',
            'post_action_url'  => wa()->getAppUrl('apanel') . 'settings/plugins/' . $plugin_id . '/settings/save/',
            'close_button_url' => wa()->getAppUrl('apanel') . 'settings/plugins/',
        ]);
    }

    protected function getCatalog()
    {
        return new apanelPluginCatalog();
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
