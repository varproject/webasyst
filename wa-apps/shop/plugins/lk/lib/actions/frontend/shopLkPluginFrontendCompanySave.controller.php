<?php

class shopLkPluginFrontendCompanySaveController extends waController
{
    public function execute()
    {
        $route = shopLkPluginRouteService::getCurrentRoute();
        if (!$route || !wa()->getUser()->isAuth()) {
            throw new waRightsException('Access denied');
        }
        $data = waRequest::post('company', array(), waRequest::TYPE_ARRAY_TRIM);
        (new shopLkPluginCompanyService())->createCompany((int)$route['id'], $data);
        $this->redirect(shopLkPluginUrlService::sectionUrl($route, 'companies'));
    }
}
