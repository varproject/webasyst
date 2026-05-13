<?php

class shopLkPluginFrontendSignupAction extends waSignupAction
{
    // Страница регистрации
    public function execute()
    {
        $this->setTemplate('frontend/auth/FrontendSignup', true);
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

        // Родительский execute() не используем: он редиректит /shop55/signup/ на системный /signup/
        $this->getStorage()->set('auth_referer', shopLkPluginNavigation::getFirstMenuUrl());

        if ($this->isPost()) {
            return $this->executeSignupAction($this->getData());
        }

        if ($confirm_hash) {
            $this->executeConfirmEmailAction($confirm_hash);
        }
    }

    // После регистрации возвращаем в кабинет
    protected function redirectToLastPage()
    {
        $url = $this->getStorage()->get('auth_referer');
        $this->getStorage()->del('auth_referer');

        $this->redirect($url ?: shopLkPluginNavigation::getFirstMenuUrl());
    }

    // Системный login URL заменяем на URL плагина
    protected function redirectToLoginPage()
    {
        $this->redirect(shopLkPluginNavigation::getAuthUrl());
    }

    // Системную app page заменяем на первый раздел кабинета
    protected function redirectToAppPage()
    {
        $this->redirect(shopLkPluginNavigation::getFirstMenuUrl());
    }

    // Системный signup URL заменяем на URL плагина
    protected function redirectToSignupPage()
    {
        $this->redirect(shopLkPluginNavigation::getSignupUrl());
    }

    // После подтверждения email остаемся в auth-странице плагина
    protected function redirectToEmailConfirmedPage()
    {
        $this->redirect(shopLkPluginNavigation::getSignupUrl() . '?email_confirmed=1');
    }

    // Ссылка подтверждения регистрации должна учитывать поселение Shop-Script
    public function sendLink($recipient)
    {
        $email = '';

        if ($recipient instanceof waContact && $recipient->exists()) {
            $email = $recipient->get('email', 'default');
        }

        if (!$email || !$this->auth_config->getSignUpConfirm()) {
            return false;
        }

        $confirmation_url = shopLkPluginNavigation::getSignupUrl(true) . '?confirm={$confirmation_hash}';
        $channel = $this->auth_config->getEmailVerificationChannelInstance();

        return (bool) $channel->sendSignUpConfirmationMessage($recipient, [
            'site_url'         => $this->auth_config->getSiteUrl(),
            'site_name'        => $this->auth_config->getSiteName(),
            'confirmation_url' => $confirmation_url,
        ]);
    }

    // После регистрации можно привязать контакт к категории приложения
    protected function afterSignup(waContact $contact)
    {
        $contact->addToCategory('shop');
    }

    // После автоавторизации возвращаем пользователя в кабинет
    protected function afterAuth()
    {
        $this->getStorage()->set('auth_referer', shopLkPluginNavigation::getFirstMenuUrl());
    }

    // Передать URL-ы в шаблон формы
    protected function assignAuthUrls()
    {
        $this->view->assign([
            'auth_url'           => shopLkPluginNavigation::getAuthUrl(),
            'signup_url'         => shopLkPluginNavigation::getSignupUrl(),
            'forgotpassword_url' => shopLkPluginNavigation::getForgotpasswordUrl(),
            'return_url'         => shopLkPluginNavigation::getFirstMenuUrl(),
        ]);
    }
}
