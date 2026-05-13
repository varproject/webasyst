<?php

class shopLkPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $storefront_rows = shopLkPluginNavigation::getSettingsStorefrontRows(true);

        $this->view->assign([
            'settings'             => shopLkPluginNavigation::getSettings(),
            'settings_tabs'        => shopLkPluginNavigation::getSettingsTabs(),
            'storefront_rows'      => $storefront_rows,
            'default_root_url'     => shopLkPluginNavigation::DEFAULT_ROOT_URL,
            'navigation_sections'  => shopLkPluginNavigation::getNavigationSettingsRows(),
            'action_modal_page'    => $this->getModalPageHtml($storefront_rows),
            'settings_path'        => wa()->getAppPath('plugins/lk/templates/actions/settings/', 'shop'),
            'settings_legacy_path' => wa()->getAppPath('plugins/lk/templates/actions-legacy/settings/', 'shop'),
        ]);
    }

    // Получить HTML модальной страницы настроек
    protected function getModalPageHtml($storefront_rows)
    {
        $page = waRequest::get('settings_page', '', waRequest::TYPE_STRING_TRIM);
        $key  = waRequest::get('storefront', '', waRequest::TYPE_STRING_TRIM);

        if ($page === '' || $key === '') {
            return '';
        }

        if (empty($storefront_rows[$key])) {
            return $this->getNotFoundHtml();
        }

        $row = $storefront_rows[$key];

        if ($page === 'sections') {
            return new waLazyDisplay(new shopLkPluginSettingsSectionsAction([
                'row'            => $row,
                'storefront_key' => $key,
            ]));
        }

        if ($page === 'auth') {
            return new waLazyDisplay(new shopLkPluginSettingsAuthAction([
                'row'            => $row,
                'auth'           => $row['auth'] ?? [],
                'storefront_key' => $key,
            ]));
        }

        return '';
    }
}
