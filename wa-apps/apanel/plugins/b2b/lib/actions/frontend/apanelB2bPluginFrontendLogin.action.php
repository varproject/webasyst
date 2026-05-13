<?php

/**
 * apanelB2bPluginFrontendLoginAction
 *
 * Страница входа в B2B-кабинет.
 *
 * Назначение:
 * - использовать штатную авторизацию Webasyst через waLoginAction;
 * - подключить собственный шаблон формы входа;
 * - после входа вернуть пользователя в кабинет или на return URL.
 */
class apanelB2bPluginFrontendLoginAction extends waLoginAction
{
    /**
     * Страница входа.
     *
     * @return void
     */
    public function execute()
    {
        $this->setTemplate('frontend/auth/FrontendLogin', true);
        $this->assignAuthUrls();

        if (wa()->getUser()->isAuth()) {
            $this->redirect($this->getReturnUrl());
            return;
        }

        parent::execute();
    }

    /**
     * Не даём родителю сохранить auth-страницу как referer.
     *
     * @return void
     */
    protected function saveReferer()
    {
        $this->getStorage()->set('auth_referer', $this->getReturnUrl());
    }

    /**
     * После входа возвращаем пользователя в нужный URL.
     *
     * @return void
     */
    protected function redirectAfterAuth()
    {
        $url = $this->getStorage()->get('auth_referer');
        $this->getStorage()->del('auth_referer');

        $this->redirect($this->isLocalUrl($url) ? $url : $this->getCabinetUrl());
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
            'return_url'         => $this->getReturnUrl(),
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

    /**
     * Возвращает безопасный return URL.
     *
     * @return string
     */
    protected function getReturnUrl()
    {
        $url = waRequest::get('return', '', waRequest::TYPE_STRING_TRIM);

        return $this->isLocalUrl($url) ? $url : $this->getCabinetUrl();
    }

    /**
     * Проверяет локальный URL.
     *
     * @param string $url URL.
     * @return bool
     */
    protected function isLocalUrl($url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        if (strpos($url, '//') === 0) {
            return false;
        }

        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $url)) {
            return false;
        }

        return substr($url, 0, 1) === '/';
    }
}
