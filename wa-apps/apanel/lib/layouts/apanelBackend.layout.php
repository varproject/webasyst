<?php

class apanelBackendLayout extends waLayout
{
    public function execute()
    {
        $user_info                                              = $this->getUserInfo();
        $app_settings                                           = apanelSettings::get('ui.backend.config', [], true);
        $first_node                                             = reset(apanelNavigation::getLvl(1, []));
        // dd($first_node);

        // Плноэкранный режим
        $fullscreen_user_setting_mode                           = !empty($user_info['settings']['ui_fullscreen_mode']);
        $fullscreen_single_app_mode                             = (bool) wa()->isSingleAppMode();
        $app_settings['has_fullscreen']                         = (bool) ($fullscreen_user_setting_mode || $fullscreen_single_app_mode);

        // Общие настройки
        $app_settings['is_htmx']                                = (bool) waRequest::server('HTTP_HX_REQUEST');
        $app_settings['main_navbar_left_message_text']          = waRequest::param('message', '', 'string_trim');
        $app_settings['main_toolbar_left_title_text']           = apanelNavigation::getActiveNode(apanelUrlSegment::asString(), 'name');
        $app_settings['header_right_profile_dropdown_items']    = $user_info;

        $app_settings['sidebar_header_logo_target_url']         = wa()->getAppUrl('apanel') . ($first_node['url'] ?? '');

        // Пункты навигации по меню
        $app_settings['sidebar_body_items']                     = apanelNavigation::getLvl(1, []);
        $app_settings['header_left_items']                      = apanelNavigation::getLvl(2, []);
        $app_settings['main_navbar_left_items']                 = apanelNavigation::getLvl(3, []);

        $this->view->assign($app_settings);
    }

    protected function getUserInfo()
    {
        $user      = wa()->getUser();
        $cache     = $user->getCache();
        $photo_url = $user->getPhotoUrl($cache['id'], $cache['photo']);
        $settings  = $user->getSettings('apanel');

        return [
            'id'        => $cache['id'],
            'login'     => $cache['login'],
            'fullname'  => $cache['name'],
            'firstname' => $cache['firstname'],
            'lastname'  => $cache['lastname'],
            'company'   => $cache['company'],
            'jobtitle'  => $cache['jobtitle'],
            'photo_url' => $photo_url,
            'settings'  => $settings,
        ];
    }
}
