<?php

class shopBtobPluginChannelCartSettingsService extends shopBtobPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array('settings' => array(
            'enabled' => ifset($params, 'btob_cart_enabled', '1'),
            'url' => ifset($params, 'btob_cart_url', 'cart'),
            'title' => ifset($params, 'btob_cart_title', 'Корзина'),
            'checkout_enabled' => ifset($params, 'btob_cart_checkout_enabled', '1'),
            'min_order_amount' => ifset($params, 'btob_cart_min_order_amount', ''),
            'min_order_items' => ifset($params, 'btob_cart_min_order_items', ''),
            'show_in_menu' => ifset($params, 'btob_cart_show_in_menu', '1'),
        ));
    }

    public function normalize(array $input): array
    {
        return array(
            'btob_cart_enabled' => $this->getBool(ifset($input, 'btob_cart_enabled', 0)),
            'btob_cart_url' => $this->normalizeSlug(ifset($input, 'btob_cart_url', 'cart'), 'cart'),
            'btob_cart_title' => trim((string) ifset($input, 'btob_cart_title', 'Корзина')),
            'btob_cart_checkout_enabled' => $this->getBool(ifset($input, 'btob_cart_checkout_enabled', 0)),
            'btob_cart_min_order_amount' => trim((string) ifset($input, 'btob_cart_min_order_amount', '')),
            'btob_cart_min_order_items' => trim((string) ifset($input, 'btob_cart_min_order_items', '')),
            'btob_cart_show_in_menu' => $this->getBool(ifset($input, 'btob_cart_show_in_menu', 1)),
        );
    }

    public function validate(int $channel_id, array $settings): array
    {
        return array();
    }

    public function save(int $channel_id, array $input): void
    {
        $this->updateParams($channel_id, $this->normalize($input));
    }
}
