<?php

class shopLkPluginFrontendAuthLayout extends waLayout
{
    protected $params = [];

    // Подготовить общие параметры standalone-страниц авторизации
    public function execute()
    {
        $storefront = shopLkPluginNavigation::getCurrentStorefront();
        $auth       = !empty($storefront['auth']) && is_array($storefront['auth']) ? $storefront['auth'] : [];

        $this->params = array_merge($this->params, [
            'wa_url'             => wa()->getRootUrl(),
            'plugin_static_url'  => shopLkPluginNavigation::getStaticUrl(),
            'return_url'         => shopLkPluginNavigation::getFirstMenuUrl(),

            'auth_url'           => shopLkPluginNavigation::getAuthUrl(),
            'signup_url'         => shopLkPluginNavigation::getSignupUrl(),
            'forgotpassword_url' => shopLkPluginNavigation::getForgotpasswordUrl(),

            'auth_bg_url'        => $auth['auth_bg_url'] ?? '',
            'auth_logo_img_url'  => $auth['auth_logo_img_url'] ?? '',
            'auth_logo_text'     => $auth['auth_logo_text'] ?? '',
            'auth_logo_slogan'   => $auth['auth_logo_slogan'] ?? '',
            'auth_hero_title'    => $auth['auth_hero_title'] ?? '',
            'auth_hero_text'     => $auth['auth_hero_text'] ?? '',
            'auth_shop_url'      => $auth['auth_shop_url'] ?? '',
        ]);

        $this->view->assign($this->params);
    }
}
