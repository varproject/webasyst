<?php

class shopLkPluginFrontendLayout extends waLayout
{
    protected $params = [
        'is_htmx'                               => false,
        'action_modal_page'                     => '',

        'sidebar_enabled'                       => true,
        'sidebar_header_enabled'                => true,
        'sidebar_header_logo_target_url'        => '',
        'sidebar_header_logo_img_enabled'       => true,
        'sidebar_header_logo_img_file_name'     => 'favicon-shop.png',
        'sidebar_header_logo_text'              => 'B2B кабинет',
        'sidebar_body_enabled'                  => true,
        'sidebar_body_items'                    => [],
        'sidebar_footer_enabled'                => false,
        'sidebar_footer_items'                  => [],

        'header_enabled'                        => true,
        'header_left_enabled'                   => true,
        'header_left_toggle_switch'             => true,
        'header_left_items'                     => [],
        'header_right_enabled'                  => true,
        'header_right_shop_target'              => '/',
        'header_right_profile_dropdown_enabled' => true,
        'header_right_profile_dropdown_items'   => [],

        'main_enabled'                          => true,
        'main_navbar_enabled'                   => false,
        'main_navbar_left_enabled'              => true,
        'main_navbar_left_items'                => [],
        'main_navbar_left_custom_items'         => [],
        'main_navbar_left_message_enabled'      => true,
        'main_navbar_left_message_text'         => '',
        'main_navbar_right_enabled'             => true,
        'main_navbar_right_items'               => [],

        'main_toolbar_enabled'                  => true,
        'main_toolbar_left_enabled'             => true,
        'main_toolbar_left_title_enabled'       => true,
        'main_toolbar_left_title_text'          => '',
        'main_toolbar_left_items'               => [],
        'main_toolbar_right_enabled'            => true,
        'main_toolbar_right_items'              => [],

        'main_body_enabled'                     => true,
        'main_body_tree_enabled'                => false,
        'main_body_tree_items'                  => [],
        'main_body_table_enabled'               => true,
        'main_body_table_items'                 => [],

        'main_footer_enabled'                   => true,
        'main_footer_left_enabled'              => true,
        'main_footer_left_items'                => [],
        'main_footer_right_enabled'             => true,
        'main_footer_right_items'               => [],
    ];

    public function execute()
    {
        $this->prepareRuntimeContext();
        $this->view->assign($this->params);
    }

    protected function prepareRuntimeContext()
    {
        $user_info              = $this->getUserInfo();
        $is_htmx                = (bool) waRequest::server('HTTP_HX_REQUEST');
        $plugin_settings        = shopLkPluginNavigation::getSettings();
        $plugin_static_url      = shopLkPluginNavigation::getStaticUrl(true);
        $frontend_root_url      = shopLkPluginNavigation::getRootFrontUrl();
        $sidebar_menu           = shopLkPluginNavigation::getMenu();
        $sidebar_menu_urls      = waUtils::getFieldValues($sidebar_menu, 'url', null);
        $sidebar_first_url      = ifset($sidebar_menu_urls[0], $frontend_root_url);

        $profile_url = isset($plugin_settings['sections']['profile']['path'])
            ? $plugin_settings['sections']['profile']['path']
            : 'profile';

        $runtime_params = [
            'is_htmx'                               => $is_htmx,
            'user_info'                             => $user_info,
            'sidebar_body_items'                    => $sidebar_menu,
            'header_right_profile_dropdown_items'   => $user_info,
            'plugin_static_url'                     => $plugin_static_url,
            'frontend_root_url'                     => $frontend_root_url,
            'sidebar_header_logo_target_url'        => $sidebar_first_url,
            'profile_url'                           => $profile_url,
            'plugin_settings'                       => $plugin_settings,
        ];

        $this->params = array_merge($this->params, $runtime_params);
    }

    protected function getUserInfo()
    {
        $user = wa()->getUser();
        return [
            'fullname'   => $user->get('name', 'value'),
            'lastname'   => $user->get('lastname', 'value'),
            'firstname'  => $user->get('firstname', 'value'),
            'middlename' => $user->get('middlename', 'value'),
            'email'      => $user->get('email', 'default'),
            'photo_url'  => $user->getPhotoUrl($user->getId(), $user->get('photo', 'value')),
            'settings'   => $user->getSettings('shop'),
        ];
    }

    // Установить один параметр layout
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    // Получить один параметр layout
    public function getParam($key, $default = null)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }
}
