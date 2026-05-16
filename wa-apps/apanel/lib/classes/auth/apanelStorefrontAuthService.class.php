<?php

/**
 * apanelStorefrontAuthService
 *
 * Сервис подготовки авторизации витрины.
 *
 * Назначение:
 * - прочитать настройки auth;
 * - определить, включена ли авторизация;
 * - определить, разрешена ли регистрация;
 * - подготовить URL входа, выхода и редиректов;
 * - вернуть нормализованную карту auth для frontend runtime.
 *
 * Инварианты:
 * - сервис не делает redirect;
 * - сервис не рендерит формы;
 * - сервис не читает route напрямую;
 * - сервис работает только с настройками auth и base_url витрины;
 * - frontend runtime позже будет использовать этот сервис перед рендером витрины.
 */
final class apanelStorefrontAuthService
{
    /**
     * Возвращает нормализованные настройки авторизации.
     *
     * @param array $settings Итоговые или групповые настройки витрины.
     * @param string $base_url Базовый URL витрины.
     * @return array
     */
    public function getAuth($settings, $base_url = '')
    {
        $auth = $this->getAuthSettings($settings);
        $base_url = $this->normalizeBaseUrl($base_url);

        $login_url = $this->buildUrl($base_url, ifset($auth['login_url'], 'login/'));
        $logout_url = $this->buildUrl($base_url, ifset($auth['logout_url'], 'logout/'));
        $signup_url = $this->buildUrl($base_url, ifset($auth['signup_url'], 'signup/'));

        return [
            'enabled'              => !empty($auth['enabled']) ? 1 : 0,
            'registration_enabled' => !empty($auth['registration_enabled']) ? 1 : 0,
            'login_by'             => $this->getLoginBy(ifset($auth['login_by'], 'email')),
            'login_url'            => $login_url,
            'logout_url'           => $logout_url,
            'signup_url'           => $signup_url,
            'after_login_url'      => $this->buildUrl($base_url, ifset($auth['after_login_url'], '')),
            'after_logout_url'     => $this->buildUrl($base_url, ifset($auth['after_logout_url'], '')),
        ];
    }

    /**
     * Проверяет, включена ли авторизация.
     *
     * @param array $settings Настройки.
     * @return bool
     */
    public function isEnabled($settings)
    {
        $auth = $this->getAuthSettings($settings);

        return !empty($auth['enabled']);
    }

    /**
     * Проверяет, разрешена ли регистрация.
     *
     * @param array $settings Настройки.
     * @return bool
     */
    public function isRegistrationEnabled($settings)
    {
        $auth = $this->getAuthSettings($settings);

        return !empty($auth['registration_enabled']);
    }

    /**
     * Возвращает настройки auth.
     *
     * @param array $settings Настройки.
     * @return array
     */
    protected function getAuthSettings($settings)
    {
        if (isset($settings['auth']) && is_array($settings['auth'])) {
            return $settings['auth'];
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * Нормализует тип логина.
     *
     * @param string $login_by Тип логина.
     * @return string
     */
    protected function getLoginBy($login_by)
    {
        $login_by = trim((string) $login_by);

        return in_array($login_by, ['email', 'phone', 'login'], true) ? $login_by : 'email';
    }

    /**
     * Нормализует базовый URL витрины.
     *
     * @param string $base_url Базовый URL.
     * @return string
     */
    protected function normalizeBaseUrl($base_url)
    {
        $base_url = trim((string) $base_url);

        if ($base_url === '') {
            return '';
        }

        return rtrim($base_url, '/') . '/';
    }

    /**
     * Собирает URL внутри витрины.
     *
     * @param string $base_url Базовый URL витрины.
     * @param string $url Относительный или абсолютный URL.
     * @return string
     */
    protected function buildUrl($base_url, $url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        if (strpos($url, '//') === 0) {
            return '';
        }

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        if (isset($url[0]) && $url[0] === '/') {
            return $url;
        }

        return $base_url . ltrim($url, '/');
    }
}
