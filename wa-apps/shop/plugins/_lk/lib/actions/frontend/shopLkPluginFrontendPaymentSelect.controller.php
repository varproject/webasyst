<?php

class shopLkPluginFrontendPaymentSelectController extends waController
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
        $payment_type_id = waRequest::post('payment_type_id', 0, waRequest::TYPE_INT);
        $profile = (new shopLkPluginCompanyProfileModel())->getByCompany($context->getRouteId(), $context->getActiveCompanyId());
        $data = $profile ?: array();
        $data['payment_type_id'] = $payment_type_id;
        (new shopLkPluginCompanyProfileModel())->saveProfile($context->getRouteId(), $context->getActiveCompanyId(), $data);
        $this->redirect(shopLkPluginUrlService::sectionUrl($route, 'payments'));
    }
}
