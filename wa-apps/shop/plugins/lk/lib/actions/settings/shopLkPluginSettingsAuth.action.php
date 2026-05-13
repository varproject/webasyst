<?php

class shopLkPluginSettingsAuthAction extends waViewAction
{
    public function execute()
    {
        $storefront_key = waRequest::get('storefront', '', waRequest::TYPE_STRING_TRIM);
        $row            = shopLkPluginNavigation::getSettingsStorefrontRow($storefront_key, true);

        if (!$storefront_key || !$row) {
            throw new waException('Витрина не найдена', 404);
        }

        $this->view->assign([
            'row'            => $row,
            'auth'           => $row['auth'] ?? [],
            'storefront_key' => $storefront_key,
            'save_url'       => '?plugin=lk&module=settings&action=savePage',
            'back_url'       => '#/lk',
        ]);
    }
}
