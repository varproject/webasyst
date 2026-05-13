<?php

/**
 * apanelB2bPluginFrontendForgotpasswordAction
 *
 * Страница восстановления пароля B2B-кабинета.
 *
 * Назначение:
 * - использовать штатное восстановление пароля Webasyst через waForgotPasswordAction;
 * - подключить собственный шаблон восстановления пароля.
 */
class apanelB2bPluginFrontendForgotpasswordAction extends waForgotPasswordAction
{
    /**
     * Страница восстановления пароля.
     *
     * @return void
     */
    public function execute()
    {
        $this->setTemplate('frontend/auth/FrontendForgot', true);
        $this->assignAuthUrls();

        if (wa()->getUser()->isAuth()) {
            $this->redirect($this->getCabinetUrl());
            return;
        }

        parent::execute();
    }

    /**
     * Некорректную или устаревшую ссылку восстановления отправляем на новую форму восстановления.
     *
     * @param Exception $e Исключение.
     * @return void
     */
    protected function handleException(Exception $e)
    {
        $this->redirect($this->getForgotpasswordUrl());
    }

    /**
     * Передаёт URL-ы в шаблон формы.
     *
     * @return void
     */
    protected function assignAuthUrls()
    {
        $this->view->assign([
            'auth_url'           => $this->getAuthUrl(),
            'forgotpassword_url' => $this->getForgotpasswordUrl(),
        ]);
    }

    /**
     * Возвращает URL страницы входа.
     *
     * @return string
     */
    protected function getAuthUrl()
    {
        return $this->getCabinetUrl('login/');
    }

    /**
     * Возвращает URL восстановления пароля.
     *
     * @return string
     */
    protected function getForgotpasswordUrl()
    {
        return $this->getCabinetUrl('forgotpassword/');
    }

    /**
     * Возвращает URL кабинета или внутренний URL кабинета.
     *
     * @param string $path Внутренний путь.
     * @return string
     */
    protected function getCabinetUrl($path = '')
    {
        $app_url = rtrim(wa()->getAppUrl('apanel'), '/') . '/';
        $path = trim((string) $path, '/');

        return $app_url . ($path !== '' ? $path . '/' : '');
    }
}
