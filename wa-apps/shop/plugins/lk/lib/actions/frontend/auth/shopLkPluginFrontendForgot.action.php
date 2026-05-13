<?php

class shopLkPluginFrontendForgotAction extends waForgotPasswordAction
{
    // Страница восстановления пароля и установки нового пароля
    public function execute()
    {
        $this->setTemplate('frontend/auth/FrontendForgot', true);
        $this->assignAuthUrls();

        if (wa()->getUser()->isAuth()) {
            $this->redirect(shopLkPluginNavigation::getFirstMenuUrl());
            return;
        }

        parent::execute();
    }

    // Некорректную или устаревшую ссылку восстановления отправляем на новую форму восстановления
    protected function handleException(Exception $e)
    {
        shopLkPluginRedirect::redirectBack(shopLkPluginNavigation::getForgotpasswordUrl());
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
