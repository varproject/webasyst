<?php

class shopLkPluginSettingsCopyRouteController extends waJsonController
{
    public function execute()
    {
        $this->checkRights();
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $route = waRequest::post('route', '', waRequest::TYPE_STRING_TRIM);
        $new_id = (new shopLkPluginRouteModel())->duplicate($id, $route);
        shopLkPluginRouteService::resetRuntimeCache();
        $this->response = array('id' => $new_id);
    }

    protected function checkRights()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }
    }
}
