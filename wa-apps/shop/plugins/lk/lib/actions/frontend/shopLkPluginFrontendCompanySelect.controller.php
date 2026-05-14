<?php

class shopLkPluginFrontendCompanySelectController extends waController
{
    public function execute()
    {
        $route = shopLkPluginRouteService::getCurrentRoute();
        if (!$route || !wa()->getUser()->isAuth()) {
            throw new waRightsException('Access denied');
        }
        $company_contact_id = waRequest::post('company_contact_id', 0, waRequest::TYPE_INT);
        shopLkPluginCabinetContext::setActiveCompany((int)$route['id'], $company_contact_id);
        $this->redirect(waRequest::server('HTTP_REFERER') ?: shopLkPluginUrlService::getCabinetUrl($route));
    }
}
