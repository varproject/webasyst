<?php

class apanelPluginsSettingsSaveController extends waController
{
    public function execute()
    {
        $this->checkRights();
        $this->checkPostMethod();

        $plugin_id = waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);
        $settings = waRequest::post('settings', [], waRequest::TYPE_ARRAY_TRIM);

        $this->getCatalog()->saveSettings($plugin_id, $settings);

        $this->redirect(wa()->getAppUrl('apanel') . 'settings/plugins/');
    }

    protected function checkPostMethod()
    {
        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }
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
