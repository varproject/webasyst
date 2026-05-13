<?php

class apanelPluginsDisableController extends waController
{
    const APP_ID = 'apanel';

    public function execute()
    {
        $this->checkRights();
        $this->checkPostMethod();

        $plugin_id = $this->getPluginId();
        $this->setPluginStatus($plugin_id, false);

        $this->redirect($this->getPluginsUrl());
    }

    protected function setPluginStatus($plugin_id, $status)
    {
        $old_app = wa()->getApp();

        wa('installer', true);

        try {
            $result = installerHelper::pluginSetStatus(self::APP_ID, $plugin_id, $status);
        } catch (Exception $e) {
            wa($old_app, true);
            throw $e;
        }

        wa($old_app, true);

        if ($result !== true) {
            throw new waException(implode("\n", (array) $result), 500);
        }
    }

    protected function checkPostMethod()
    {
        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }
    }

    protected function getPluginId()
    {
        $plugin_id = waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);

        if (!$plugin_id || !preg_match('~^[a-z0-9_]+$~i', $plugin_id)) {
            throw new waException('Invalid plugin ID', 400);
        }

        return $plugin_id;
    }

    protected function getPluginsUrl()
    {
        return wa()->getAppUrl(self::APP_ID) . 'settings/plugins/';
    }

    protected function checkRights()
    {
        if (!$this->getRights('plugins')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
