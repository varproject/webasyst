<?php

class shopB2bPluginBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new shopBackendLayout());
        
        $plugin = wa('shop')->getPlugin('b2b');

        $this->view->assign([
            'plugin_id' => 'b2b',
            'settings' => $plugin->getSettings(),
            'save_url' => wa('shop')->getAppUrl(null, true) . 'b2b/settings/save/',
        ]);
    }
}
