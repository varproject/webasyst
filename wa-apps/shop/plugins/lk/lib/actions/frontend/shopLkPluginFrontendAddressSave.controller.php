<?php

class shopLkPluginFrontendAddressSaveController extends waController
{
    public function execute()
    {
        $route = shopLkPluginRouteService::getCurrentRoute();
        if (!$route || !wa()->getUser()->isAuth()) {
            throw new waRightsException('Access denied');
        }
        $context = new shopLkPluginCabinetContext($route);
        if (!$context->canManageCompany()) {
            throw new waRightsException('Access denied');
        }
        $data = waRequest::post('address', array(), waRequest::TYPE_ARRAY_TRIM);
        (new shopLkPluginCompanyAddressModel())->saveAddress($context->getRouteId(), $context->getActiveCompanyId(), $data);
        $this->redirect(shopLkPluginUrlService::sectionUrl($route, 'addresses'));
    }
}
