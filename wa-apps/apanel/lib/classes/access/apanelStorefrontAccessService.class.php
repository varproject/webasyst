<?php

/**
 * apanelStorefrontAccessService
 *
 * Сервис проверки доступа к витрине.
 *
 * Назначение:
 * - проверить, может ли текущий или переданный пользователь открыть витрину;
 * - работать с режимами public, authorized, groups, contacts, closed;
 * - возвращать простой результат проверки без редиректов и рендера.
 *
 * Инварианты:
 * - сервис не делает redirect;
 * - сервис не читает route напрямую;
 * - сервис работает только с настройками access;
 * - frontend runtime позже будет использовать этот сервис перед рендером витрины.
 */
final class apanelStorefrontAccessService
{
    /**
     * Проверяет доступ к витрине.
     *
     * @param array $settings Итоговые или групповые настройки витрины.
     * @param waUser|waContact|null $contact Пользователь.
     * @return bool
     */
    public function check($settings, $contact = null)
    {
        return $this->getResult($settings, $contact)['allowed'];
    }

    /**
     * Возвращает результат проверки доступа.
     *
     * @param array $settings Итоговые или групповые настройки витрины.
     * @param waUser|waContact|null $contact Пользователь.
     * @return array
     */
    public function getResult($settings, $contact = null)
    {
        $contact = $contact ?: wa()->getUser();
        $access = $this->getAccessSettings($settings);
        $mode = ifset($access['mode'], 'public');

        switch ($mode) {
            case 'public':
                return $this->allow('public');

            case 'authorized':
                return $this->isAuth($contact)
                    ? $this->allow('authorized')
                    : $this->deny('auth_required');

            case 'groups':
                return $this->checkGroups($contact, ifset($access['groups'], []));

            case 'contacts':
                return $this->checkContacts($contact, ifset($access['contacts'], []));

            case 'closed':
                return $this->deny('closed');

            default:
                return $this->allow('public');
        }
    }

    /**
     * Возвращает настройки access.
     *
     * @param array $settings Настройки.
     * @return array
     */
    protected function getAccessSettings($settings)
    {
        if (isset($settings['access']) && is_array($settings['access'])) {
            return $settings['access'];
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * Проверяет доступ по группам.
     *
     * @param waUser|waContact|null $contact Пользователь.
     * @param array $groups Разрешённые группы.
     * @return array
     */
    protected function checkGroups($contact, $groups)
    {
        if (!$this->isAuth($contact)) {
            return $this->deny('auth_required');
        }

        $groups = $this->prepareIds($groups);

        if (!$groups) {
            return $this->deny('groups_empty');
        }

        $contact_groups = $this->getContactGroupIds($contact);

        foreach ($groups as $group_id) {
            if (in_array($group_id, $contact_groups, true)) {
                return $this->allow('groups');
            }
        }

        return $this->deny('group_denied');
    }

    /**
     * Проверяет доступ по пользователям.
     *
     * @param waUser|waContact|null $contact Пользователь.
     * @param array $contacts Разрешённые пользователи.
     * @return array
     */
    protected function checkContacts($contact, $contacts)
    {
        if (!$this->isAuth($contact)) {
            return $this->deny('auth_required');
        }

        $contacts = $this->prepareIds($contacts);

        if (!$contacts) {
            return $this->deny('contacts_empty');
        }

        return in_array((int) $contact->getId(), $contacts, true)
            ? $this->allow('contacts')
            : $this->deny('contact_denied');
    }

    /**
     * Подготавливает список ID.
     *
     * @param mixed $ids Список ID.
     * @return array
     */
    protected function prepareIds($ids)
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $result = [];

        foreach ((array) $ids as $id) {
            $id = (int) trim((string) $id);

            if ($id > 0) {
                $result[] = $id;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Возвращает успешный результат.
     *
     * @param string $reason Причина.
     * @return array
     */
    protected function allow($reason)
    {
        return [
            'allowed' => true,
            'reason'  => $reason,
        ];
    }

    /**
     * Возвращает отказ.
     *
     * @param string $reason Причина.
     * @return array
     */
    protected function deny($reason)
    {
        return [
            'allowed' => false,
            'reason'  => $reason,
        ];
    }

    /**
     * Проверяет, авторизован ли пользователь.
     *
     * @param mixed $contact
     * @return bool
     */
    protected function isAuth($contact)
    {
        if (!$contact) {
            return false;
        }

        if (method_exists($contact, 'isAuth')) {
            return $contact->isAuth();
        }

        if (method_exists($contact, 'getId')) {
            return (int) $contact->getId() > 0;
        }

        return false;
    }

    /**
     * Возвращает ID групп пользователя.
     *
     * @param mixed $contact
     * @return array
     */
    protected function getContactGroupIds($contact)
    {
        if (!$contact || !method_exists($contact, 'getId')) {
            return [];
        }

        if (method_exists($contact, 'getGroupIds')) {
            return $this->prepareIds($contact->getGroupIds());
        }

        $contact_id = (int) $contact->getId();

        if (!$contact_id) {
            return [];
        }

        $user = new waUser($contact_id);

        return $this->prepareIds($user->getGroupIds());
    }
}
