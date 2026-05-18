<?php

class shopBtobPluginChannelUsersSettingsService extends shopBtobPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        $allow_contact_ids = $this->getIdList(ifset($params, 'btob_access_allow_contact_ids', ''));
        $deny_contact_ids = $this->getIdList(ifset($params, 'btob_access_deny_contact_ids', ''));

        return array(
            'settings' => array(
                'access_mode' => ifset($params, 'btob_access_mode', 'public'),
                'guest_behavior' => ifset($params, 'btob_access_guest_behavior', 'login'),
                'allow_contact_ids' => $allow_contact_ids,
                'allow_category_ids' => $this->getIdList(ifset($params, 'btob_access_allow_category_ids', '')),
                'deny_contact_ids' => $deny_contact_ids,
                'deny_category_ids' => $this->getIdList(ifset($params, 'btob_access_deny_category_ids', '')),
                'denied_behavior' => ifset($params, 'btob_access_denied_behavior', 'page'),
                'denied_page_mode' => ifset($params, 'btob_access_denied_page_mode', 'plugin'),
                'denied_block_id' => ifset($params, 'btob_access_denied_block_id', ''),
                'denied_redirect_url' => ifset($params, 'btob_access_denied_redirect_url', ''),
            ),
            'customer_categories' => shopCustomer::getAllCategories(),
            'allow_contacts' => $this->getSelectedContacts($allow_contact_ids),
            'deny_contacts' => $this->getSelectedContacts($deny_contact_ids),
        );
    }

    public function normalize(array $input): array
    {
        $mode = ifset($input, 'btob_access_mode', ifset($input, 'access_mode', 'public'));
        if (!in_array($mode, array('public', 'authorized', 'restricted'), true)) {
            $mode = 'public';
        }

        $guest_behavior = ifset($input, 'btob_access_guest_behavior', 'login');
        if (!in_array($guest_behavior, array('allow', 'login', 'deny'), true)) {
            $guest_behavior = 'login';
        }

        if ($mode === 'authorized') {
            $guest_behavior = 'login';
        }

        $denied_behavior = ifset($input, 'btob_access_denied_behavior', 'page');
        if (!in_array($denied_behavior, array('page', '404', 'redirect', 'ignore'), true)) {
            $denied_behavior = 'page';
        }

        $page_mode = ifset($input, 'btob_access_denied_page_mode', 'plugin');
        if (!in_array($page_mode, array('plugin', 'block'), true)) {
            $page_mode = 'plugin';
        }

        return array(
            'btob_access_mode' => $mode,
            'btob_access_guest_behavior' => $guest_behavior,
            'btob_access_allow_contact_ids' => $this->encodeIdList(ifset($input, 'btob_access_allow_contact_ids', array())),
            'btob_access_allow_category_ids' => $this->encodeIdList(ifset($input, 'btob_access_allow_category_ids', array())),
            'btob_access_deny_contact_ids' => $this->encodeIdList(ifset($input, 'btob_access_deny_contact_ids', array())),
            'btob_access_deny_category_ids' => $this->encodeIdList(ifset($input, 'btob_access_deny_category_ids', array())),
            'btob_access_denied_behavior' => $denied_behavior,
            'btob_access_denied_page_mode' => $page_mode,
            'btob_access_denied_block_id' => trim((string) ifset($input, 'btob_access_denied_block_id', '')),
            'btob_access_denied_redirect_url' => trim((string) ifset($input, 'btob_access_denied_redirect_url', '')),
        );
    }

    public function validate(int $channel_id, array $settings): array
    {
        $errors = array();
        $mode = ifset($settings, 'btob_access_mode', 'public');

        if ($mode === 'restricted') {
            $has_rules = $this->getIdList($settings['btob_access_allow_contact_ids'])
                || $this->getIdList($settings['btob_access_allow_category_ids'])
                || $this->getIdList($settings['btob_access_deny_contact_ids'])
                || $this->getIdList($settings['btob_access_deny_category_ids']);

            if (!$has_rules) {
                $errors[] = array('field' => 'settings[btob_access_mode]', 'error_description' => 'Для ограниченного доступа добавьте белый или чёрный список.');
            }
        }

        if (
            ifset($settings, 'btob_access_denied_behavior', 'page') === 'page'
            && ifset($settings, 'btob_access_denied_page_mode', 'plugin') === 'block'
            && !$this->validateBlockId(ifset($settings, 'btob_access_denied_block_id', ''))
        ) {
            $errors[] = array('field' => 'settings[btob_access_denied_block_id]', 'error_description' => 'Укажите корректный ID блока приложения «Сайт».');
        }

        if (ifset($settings, 'btob_access_denied_behavior', 'page') === 'redirect' && trim(ifset($settings, 'btob_access_denied_redirect_url', '')) === '') {
            $errors[] = array('field' => 'settings[btob_access_denied_redirect_url]', 'error_description' => 'Укажите URL для перенаправления.');
        }

        return $errors;
    }

    public function save(int $channel_id, array $input): void
    {
        $this->updateParams($channel_id, $this->normalize($input));
    }

    protected function getSelectedContacts(array $ids): array
    {
        if (!$ids) {
            return array();
        }

        $model = new waModel();
        return $model->query(
            "SELECT c.id, c.name, e.email, p.value AS phone
             FROM wa_contact c
             LEFT JOIN wa_contact_emails e ON e.contact_id = c.id AND e.sort = 0
             LEFT JOIN wa_contact_data p ON p.contact_id = c.id AND p.field = 'phone'
             WHERE c.id IN (i:ids)
             GROUP BY c.id
             ORDER BY c.name",
            array('ids' => $ids)
        )->fetchAll('id');
    }
}
