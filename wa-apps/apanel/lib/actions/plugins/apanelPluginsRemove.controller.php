<?php

class apanelPluginsRemoveController extends waController
{
    public function execute()
    {
        $this->checkRights();
        $this->checkPostMethod();

        $this->getCatalog()->remove($this->getPluginId());

        $this->redirect($this->getPluginsUrl());
    }

    protected function checkPostMethod()
    {
        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }
    }

    protected function getPluginId()
    {
        return waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);
    }

    protected function getPluginsUrl()
    {
        return wa()->getAppUrl('apanel') . 'settings/plugins/';
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
