<?php

class shopB2bPluginChannelSupportSettingsService extends shopB2bPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array('settings' => array(
            'enabled' => ifset($params, 'b2b_support_enabled', '1'),
            'url' => ifset($params, 'b2b_support_url', 'support'),
            'title' => ifset($params, 'b2b_support_title', 'Поддержка'),
            'manager_contact_id' => ifset($params, 'b2b_support_manager_contact_id', ''),
            'email' => ifset($params, 'b2b_support_email', ''),
            'phone' => ifset($params, 'b2b_support_phone', ''),
            'telegram' => ifset($params, 'b2b_support_telegram', ''),
            'whatsapp' => ifset($params, 'b2b_support_whatsapp', ''),
            'text' => ifset($params, 'b2b_support_text', ''),
            'form_enabled' => ifset($params, 'b2b_support_form_enabled', '0'),
            'form_mode' => ifset($params, 'b2b_support_form_mode', 'plugin'),
            'form_block_id' => ifset($params, 'b2b_support_form_block_id', ''),
            'form_external_url' => ifset($params, 'b2b_support_form_external_url', ''),
            'show_in_menu' => ifset($params, 'b2b_support_show_in_menu', '1'),
        ));
    }

    public function normalize(array $input): array
    {
        $mode = ifset($input, 'b2b_support_form_mode', 'plugin');
        if (!in_array($mode, array('plugin', 'site_block', 'external'), true)) {
            $mode = 'plugin';
        }
        return array(
            'b2b_support_enabled' => $this->getBool(ifset($input, 'b2b_support_enabled', 0)),
            'b2b_support_url' => $this->normalizeSlug(ifset($input, 'b2b_support_url', 'support'), 'support'),
            'b2b_support_title' => trim((string) ifset($input, 'b2b_support_title', 'Поддержка')),
            'b2b_support_manager_contact_id' => (int) ifset($input, 'b2b_support_manager_contact_id', 0),
            'b2b_support_email' => trim((string) ifset($input, 'b2b_support_email', '')),
            'b2b_support_phone' => trim((string) ifset($input, 'b2b_support_phone', '')),
            'b2b_support_telegram' => trim((string) ifset($input, 'b2b_support_telegram', '')),
            'b2b_support_whatsapp' => trim((string) ifset($input, 'b2b_support_whatsapp', '')),
            'b2b_support_text' => trim((string) ifset($input, 'b2b_support_text', '')),
            'b2b_support_form_enabled' => $this->getBool(ifset($input, 'b2b_support_form_enabled', 0)),
            'b2b_support_form_mode' => $mode,
            'b2b_support_form_block_id' => trim((string) ifset($input, 'b2b_support_form_block_id', '')),
            'b2b_support_form_external_url' => trim((string) ifset($input, 'b2b_support_form_external_url', '')),
            'b2b_support_show_in_menu' => $this->getBool(ifset($input, 'b2b_support_show_in_menu', 1)),
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
