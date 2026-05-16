<?php

class shopB2bPluginAccessService extends shopB2bPluginChannelSettingsService
{
    public function resolve(int $channel_id, ?int $contact_id): array
    {
        $channel = $this->getChannel($channel_id);
        if (empty($channel['status'])) {
            return $this->deny('channel_disabled', '404');
        }

        $params = $channel['params'];
        $mode = ifset($params, 'b2b_access_mode', 'public');
        if (!in_array($mode, array('public', 'authorized', 'restricted'), true)) {
            $mode = 'public';
        }

        $contact_id = (int) $contact_id;

        if ($mode === 'public') {
            return $this->allow();
        }

        if ($contact_id <= 0) {
            $guest_behavior = $mode === 'authorized' ? 'login' : ifset($params, 'b2b_access_guest_behavior', 'login');
            if ($guest_behavior === 'allow') {
                return $this->allow();
            }
            if ($guest_behavior === 'deny') {
                return $this->deny('guest', $this->deniedBehavior($params));
            }
            return array('allowed' => false, 'requires_auth' => true, 'reason' => 'guest', 'behavior' => 'login');
        }

        if ($mode === 'authorized') {
            return $this->allow();
        }

        if ($this->matches($contact_id, $this->getIdList(ifset($params, 'b2b_access_deny_contact_ids', '')), $this->getIdList(ifset($params, 'b2b_access_deny_category_ids', '')))) {
            return $this->deny('denied_list', $this->deniedBehavior($params));
        }

        $allow_contacts = $this->getIdList(ifset($params, 'b2b_access_allow_contact_ids', ''));
        $allow_categories = $this->getIdList(ifset($params, 'b2b_access_allow_category_ids', ''));

        if ($allow_contacts || $allow_categories) {
            return $this->matches($contact_id, $allow_contacts, $allow_categories)
                ? $this->allow()
                : $this->deny('not_in_allowlist', $this->deniedBehavior($params));
        }

        return $this->allow();
    }

    protected function matches(int $contact_id, array $contact_ids, array $category_ids): bool
    {
        if ($contact_ids && in_array($contact_id, $contact_ids, true)) {
            return true;
        }

        if (!$category_ids) {
            return false;
        }

        $model = new waModel();
        return (int) $model->query(
            'SELECT COUNT(*) FROM wa_contact_categories WHERE contact_id = i:contact_id AND category_id IN (i:category_ids)',
            array('contact_id' => $contact_id, 'category_ids' => $category_ids)
        )->fetchField() > 0;
    }

    protected function deniedBehavior(array $params): string
    {
        $behavior = ifset($params, 'b2b_access_denied_behavior', 'page');
        return in_array($behavior, array('page', '404', 'redirect', 'ignore'), true) ? $behavior : 'page';
    }

    protected function allow(): array
    {
        return array('allowed' => true, 'requires_auth' => false, 'reason' => null, 'behavior' => 'allow');
    }

    protected function deny(string $reason, string $behavior): array
    {
        return array('allowed' => false, 'requires_auth' => false, 'reason' => $reason, 'behavior' => $behavior);
    }
}
