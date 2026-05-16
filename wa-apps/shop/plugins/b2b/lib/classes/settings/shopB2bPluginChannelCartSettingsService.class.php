<?php

class shopB2bPluginChannelCartSettingsService extends shopB2bPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array('settings' => array(
            'enabled' => ifset($params, 'b2b_cart_enabled', '1'),
            'url' => ifset($params, 'b2b_cart_url', 'cart'),
            'title' => ifset($params, 'b2b_cart_title', 'Корзина'),
            'checkout_enabled' => ifset($params, 'b2b_cart_checkout_enabled', '1'),
            'min_order_amount' => ifset($params, 'b2b_cart_min_order_amount', ''),
            'min_order_items' => ifset($params, 'b2b_cart_min_order_items', ''),
            'show_in_menu' => ifset($params, 'b2b_cart_show_in_menu', '1'),
        ));
    }

    public function normalize(array $input): array
    {
        return array(
            'b2b_cart_enabled' => $this->getBool(ifset($input, 'b2b_cart_enabled', 0)),
            'b2b_cart_url' => $this->normalizeSlug(ifset($input, 'b2b_cart_url', 'cart'), 'cart'),
            'b2b_cart_title' => trim((string) ifset($input, 'b2b_cart_title', 'Корзина')),
            'b2b_cart_checkout_enabled' => $this->getBool(ifset($input, 'b2b_cart_checkout_enabled', 0)),
            'b2b_cart_min_order_amount' => trim((string) ifset($input, 'b2b_cart_min_order_amount', '')),
            'b2b_cart_min_order_items' => trim((string) ifset($input, 'b2b_cart_min_order_items', '')),
            'b2b_cart_show_in_menu' => $this->getBool(ifset($input, 'b2b_cart_show_in_menu', 1)),
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
