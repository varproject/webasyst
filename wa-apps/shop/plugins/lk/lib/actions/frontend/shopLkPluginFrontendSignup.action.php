<?php

class shopLkPluginFrontendSignupAction extends waSignupAction
{
    protected $lk_route;

    public function execute()
    {
        $this->lk_route = shopLkPluginRouteService::getCurrentRoute();
        if (!$this->lk_route) {
            throw new waException('B2B route not found', 404);
        }
        $this->setLayout(new shopLkPluginFrontendAuthLayout($this->lk_route));
        $this->setTemplate('frontend/FrontendSignup', true);
        $this->assignAuthUrls();

        if ($this->isEndPointMessageAction()) {
            return $this->executeEndPointMessageAction();
        }

        if ($this->isSendConfirmationAction()) {
            return $this->executeSendConfirmationAction();
        }

        $confirm_hash = (string) $this->getGetParam('confirm');

        if (wa()->getAuth()->isAuth() && !$confirm_hash) {
            if ($this->needRedirects()) {
                $this->redirectToAppPage();
            }
            return;
        }

        if (!$this->auth_config->getAuth()) {
            $this->notFound();
        }

        $this->getStorage()->set('auth_referer', shopLkPluginUrlService::getCabinetUrl($this->lk_route));

        if ($this->isPost()) {
            return $this->executeSignupAction($this->getData());
        }

        if ($confirm_hash) {
            $this->executeConfirmEmailAction($confirm_hash);
        }
    }

    protected function redirectToLastPage()
    {
        $url = $this->getStorage()->get('auth_referer');
        $this->getStorage()->del('auth_referer');
        $this->redirect($url ?: shopLkPluginUrlService::getCabinetUrl($this->lk_route));
    }

    protected function redirectToLoginPage()
    {
        $this->redirect(shopLkPluginUrlService::loginUrl($this->lk_route));
    }

    protected function redirectToAppPage()
    {
        $this->redirect(shopLkPluginUrlService::getCabinetUrl($this->lk_route));
    }

    protected function redirectToSignupPage()
    {
        $this->redirect(shopLkPluginUrlService::signupUrl($this->lk_route));
    }

    protected function redirectToEmailConfirmedPage()
    {
        $this->redirect(shopLkPluginUrlService::signupUrl($this->lk_route) . '?email_confirmed=1');
    }

    public function sendLink($recipient)
    {
        $email = '';
        if ($recipient instanceof waContact && $recipient->exists()) {
            $email = $recipient->get('email', 'default');
        }
        if (!$email || !$this->auth_config->getSignUpConfirm()) {
            return false;
        }
        $confirmation_url = shopLkPluginUrlService::signupUrl($this->lk_route, true) . '?confirm={$confirmation_hash}';
        $channel = $this->auth_config->getEmailVerificationChannelInstance();
        return (bool) $channel->sendSignUpConfirmationMessage($recipient, array(
            'site_url' => $this->auth_config->getSiteUrl(),
            'site_name' => $this->auth_config->getSiteName(),
            'confirmation_url' => $confirmation_url,
        ));
    }

    protected function afterSignup(waContact $contact)
    {
        $contact->addToCategory('shop');
    }

    protected function afterAuth()
    {
        $this->getStorage()->set('auth_referer', shopLkPluginUrlService::getCabinetUrl($this->lk_route));
    }

    protected function assignAuthUrls()
    {
        $this->view->assign(array(
            'auth_url' => shopLkPluginUrlService::loginUrl($this->lk_route),
            'signup_url' => shopLkPluginUrlService::signupUrl($this->lk_route),
            'forgotpassword_url' => shopLkPluginUrlService::forgotUrl($this->lk_route),
            'return_url' => shopLkPluginUrlService::getCabinetUrl($this->lk_route),
        ));
    }
}
