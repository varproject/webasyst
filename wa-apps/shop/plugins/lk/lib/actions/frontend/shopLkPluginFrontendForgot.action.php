<?php

class shopLkPluginFrontendForgotAction extends waForgotPasswordAction
{
    protected $lk_route;

    public function execute()
    {
        $this->lk_route = shopLkPluginRouteService::getCurrentRoute();
        if (!$this->lk_route) {
            throw new waException('B2B route not found', 404);
        }
        $this->setLayout(new shopLkPluginFrontendAuthLayout($this->lk_route));
        $this->setTemplate('frontend/FrontendForgot', true);
        $this->assignAuthUrls();

        if (wa()->getUser()->isAuth()) {
            $this->redirect(shopLkPluginUrlService::getCabinetUrl($this->lk_route));
            return;
        }

        parent::execute();
    }

    protected function handleException(Exception $e)
    {
        $this->redirect(shopLkPluginUrlService::forgotUrl($this->lk_route));
    }

    protected function assignAuthUrls()
    {
        $this->view->assign(array(
            'auth_url' => shopLkPluginUrlService::loginUrl($this->lk_route),
            'signup_url' => shopLkPluginUrlService::signupUrl($this->lk_route),
            'forgotpassword_url' => shopLkPluginUrlService::forgotUrl($this->lk_route),
        ));
    }
}
