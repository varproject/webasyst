<?php

class shopLkPluginFrontendLoginAction extends waLoginAction
{
    // Страница входа
    public function execute()
    {
        $this->setTemplate('frontend/auth/FrontendLogin', true);
        $this->assignAuthUrls();

        if (wa()->getUser()->isAuth()) {
            $this->redirect(shopLkPluginNavigation::getFirstMenuUrl());
            return;
        }

        parent::execute();
    }

    // Не даем родителю сохранить auth-страницы как referer
    protected function saveReferer()
    {
        $this->getStorage()->set('auth_referer', shopLkPluginNavigation::getFirstMenuUrl());
    }

    // После входа всегда возвращаем пользователя в кабинет
    protected function redirectAfterAuth()
    {
        $this->redirect(shopLkPluginNavigation::getFirstMenuUrl());
    }

    // Повторная отправка письма подтверждения должна идти через наш signup URL
    protected function sendConfirmation()
    {
        $login = $this->getData('login');
        $url   = shopLkPluginNavigation::getSignupUrl();

        if ($login) {
            $url .= '?send_confirmation=1&login=' . urlencode($login);
        }

        $this->redirect($url);
    }

    // Передать URL-ы в шаблон формы
    protected function assignAuthUrls()
    {
        $this->view->assign([
            'auth_url'           => shopLkPluginNavigation::getAuthUrl(),
            'signup_url'         => shopLkPluginNavigation::getSignupUrl(),
            'forgotpassword_url' => shopLkPluginNavigation::getForgotpasswordUrl(),
        ]);
    }
}
