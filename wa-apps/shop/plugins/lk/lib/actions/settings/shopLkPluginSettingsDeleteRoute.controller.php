<?php

class shopLkPluginSettingsDeleteRouteController extends waJsonController
{
    public function execute()
    {
        $this->checkRights();
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if ($id > 0) {
            (new shopLkPluginRouteModel())->deleteById($id);
            (new shopLkPluginPaymentTypeModel())->deleteByField('route_id', $id);
        }
        shopLkPluginRouteService::resetRuntimeCache();
        $this->response = array('deleted' => $id);
    }

    protected function checkRights()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            throw new waRightsException('Access denied');
        }
    }
}
