<?php

abstract class shopLkPluginFrontendBaseAction extends waViewAction
{
    /** @var shopLkPluginCabinetContext */
    protected $context;

    public function preExecute()
    {
        parent::preExecute();
        $route = shopLkPluginRouteService::getCurrentRoute();
        if (!$route) {
            throw new waException('B2B route not found', 404);
        }
        if (!wa()->getUser()->isAuth()) {
            $this->redirect(shopLkPluginUrlService::loginUrl($route));
        }
        $this->context = new shopLkPluginCabinetContext($route);
        $this->setLayout(new shopLkPluginFrontendLayout($this->context));
        $route_for_urls = $this->context->getRoute();
        $this->view->assign(array(
            'context' => $this->context,
            'lk_urls' => array(
                'dashboard' => shopLkPluginUrlService::getCabinetUrl($route_for_urls),
                'companies' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'companies'),
                'company_save' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'companies/save'),
                'company_select' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'company/select'),
                'addresses' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'addresses'),
                'address_save' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'addresses/save'),
                'payments' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'payments'),
                'payment_save' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'payments/save'),
                'orders' => shopLkPluginUrlService::sectionUrl($route_for_urls, 'orders'),
                'login' => shopLkPluginUrlService::loginUrl($route_for_urls),
                'signup' => shopLkPluginUrlService::signupUrl($route_for_urls),
                'forgot' => shopLkPluginUrlService::forgotUrl($route_for_urls),
            ),
        ));
    }

    protected function requireCompany()
    {
        if (!$this->context->hasCompanies()) {
            $this->redirect(shopLkPluginUrlService::sectionUrl($this->context->getRoute(), 'companies'));
        }
    }

    protected function requireCompanyManager()
    {
        $this->requireCompany();
        if (!$this->context->canManageCompany()) {
            throw new waRightsException('Недостаточно прав для изменения данных компании.');
        }
    }
}
