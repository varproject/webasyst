<?php

class shopLkPluginFrontendLoginAction extends waLoginAction
{
    protected $lk_route;

    public function execute()
    {
        $this->lk_route = shopLkPluginRouteService::getCurrentRoute();
        if (!$this->lk_route) {
            throw new waException('B2B route not found', 404);
        }
        $this->setLayout(new shopLkPluginFrontendAuthLayout($this->lk_route));
        $this->setTemplate('frontend/FrontendLogin', true);
        $this->assignAuthUrls();

        if (wa()->getUser()->isAuth()) {
            $this->redirect(shopLkPluginUrlService::getCabinetUrl($this->lk_route));
            return;
        }

        parent::execute();
    }

    protected function saveReferer()
    {
        $this->getStorage()->set('auth_referer', shopLkPluginUrlService::getCabinetUrl($this->lk_route));
    }

    protected function redirectAfterAuth()
    {
        $this->redirect(shopLkPluginUrlService::getCabinetUrl($this->lk_route));
    }

    protected function sendConfirmation()
    {
        $login = $this->getData('login');
        $url = shopLkPluginUrlService::signupUrl($this->lk_route);
        if ($login) {
            $url .= '?send_confirmation=1&login=' . urlencode($login);
        }
        $this->redirect($url);
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
