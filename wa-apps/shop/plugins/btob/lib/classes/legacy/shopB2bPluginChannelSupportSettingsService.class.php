<?php

class shopBtobPluginChannelSupportSettingsService extends shopBtobPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        return array('settings' => array(
            'enabled' => ifset($params, 'btob_support_enabled', '1'),
            'url' => ifset($params, 'btob_support_url', 'support'),
            'title' => ifset($params, 'btob_support_title', 'Поддержка'),
            'manager_contact_id' => ifset($params, 'btob_support_manager_contact_id', ''),
            'email' => ifset($params, 'btob_support_email', ''),
            'phone' => ifset($params, 'btob_support_phone', ''),
            'telegram' => ifset($params, 'btob_support_telegram', ''),
            'whatsapp' => ifset($params, 'btob_support_whatsapp', ''),
            'text' => ifset($params, 'btob_support_text', ''),
            'form_enabled' => ifset($params, 'btob_support_form_enabled', '0'),
            'form_mode' => ifset($params, 'btob_support_form_mode', 'plugin'),
            'form_block_id' => ifset($params, 'btob_support_form_block_id', ''),
            'form_external_url' => ifset($params, 'btob_support_form_external_url', ''),
            'show_in_menu' => ifset($params, 'btob_support_show_in_menu', '1'),
        ));
    }

    public function normalize(array $input): array
    {
        $mode = ifset($input, 'btob_support_form_mode', 'plugin');
        if (!in_array($mode, array('plugin', 'site_block', 'external'), true)) {
            $mode = 'plugin';
        }
        return array(
            'btob_support_enabled' => $this->getBool(ifset($input, 'btob_support_enabled', 0)),
            'btob_support_url' => $this->normalizeSlug(ifset($input, 'btob_support_url', 'support'), 'support'),
            'btob_support_title' => trim((string) ifset($input, 'btob_support_title', 'Поддержка')),
            'btob_support_manager_contact_id' => (int) ifset($input, 'btob_support_manager_contact_id', 0),
            'btob_support_email' => trim((string) ifset($input, 'btob_support_email', '')),
            'btob_support_phone' => trim((string) ifset($input, 'btob_support_phone', '')),
            'btob_support_telegram' => trim((string) ifset($input, 'btob_support_telegram', '')),
            'btob_support_whatsapp' => trim((string) ifset($input, 'btob_support_whatsapp', '')),
            'btob_support_text' => trim((string) ifset($input, 'btob_support_text', '')),
            'btob_support_form_enabled' => $this->getBool(ifset($input, 'btob_support_form_enabled', 0)),
            'btob_support_form_mode' => $mode,
            'btob_support_form_block_id' => trim((string) ifset($input, 'btob_support_form_block_id', '')),
            'btob_support_form_external_url' => trim((string) ifset($input, 'btob_support_form_external_url', '')),
            'btob_support_show_in_menu' => $this->getBool(ifset($input, 'btob_support_show_in_menu', 1)),
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
