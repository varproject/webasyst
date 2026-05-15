<?php

class shopB2bPluginCustomerAccessService
{
    // Возвращает ID из JSON, массива или строки.
    public function getIds($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value   = is_array($decoded) ? $decoded : [];
        }

        $ids = array_filter(array_map('intval', (array) $value));

        return array_values(array_unique($ids));
    }

    // Возвращает категории покупателей магазина.
    public function getCustomerCategories(): array
    {
        return shopCustomer::getAllCategories();
    }

    // Возвращает выбранные контакты для Select2.
    public function getSelectedCustomers(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $model = new waModel();
        $sql   = "
            SELECT c.id, c.name, e.email, p.value AS phone
            FROM wa_contact c
                LEFT JOIN wa_contact_emails e
                    ON e.contact_id = c.id AND e.sort = 0
                LEFT JOIN wa_contact_data p
                    ON p.contact_id = c.id AND p.field = 'phone'
            WHERE c.id IN (i:ids)
            GROUP BY c.id
            ORDER BY c.name
        ";

        return $model->query($sql, [
            'ids' => $ids,
        ])->fetchAll('id');
    }

    // Ищет покупателей магазина для Select2.
    public function searchCustomers($query, $limit = 20): array
    {
        $query = trim((string) $query);
        $limit = max(1, min(50, (int) $limit));

        if ($query === '') {
            return [];
        }

        $model = new shopCustomerModel();

        list($customers, $total) = $model->getList(null, $query, 0, $limit);

        if (!$customers) {
            return [];
        }

        $result = [];

        foreach ($customers as $customer) {
            $result[$customer['id']] = [
                'id'    => (int) $customer['id'],
                'name'  => ifset($customer, 'name', ''),
                'email' => ifset($customer, 'email', ''),
                'phone' => ifset($customer, 'phone', ''),
            ];
        }

        return $result;
    }

    // Проверяет доступ контакта к B2B-каналу.
    public function canAccess($contact_id, array $params): bool
    {
        $contact_id = (int) $contact_id;
        $mode       = ifset($params, 'access_mode', 'all');

        if (!in_array($mode, ['all', 'customers', 'categories'])) {
            $mode = 'all';
        }

        if ($mode === 'all') {
            return true;
        }

        if ($contact_id <= 0) {
            return false;
        }

        if ($mode === 'customers') {
            return in_array($contact_id, $this->getIds(ifset($params, 'access_customer_ids', '')), true);
        }

        $category_ids = $this->getIds(ifset($params, 'access_category_ids', ''));

        if (!$category_ids) {
            return false;
        }

        $model = new waModel();
        $sql   = "
            SELECT COUNT(*)
            FROM wa_contact_categories
            WHERE contact_id = i:contact_id
                AND category_id IN (i:category_ids)
        ";

        return (int) $model->query($sql, [
            'contact_id'   => $contact_id,
            'category_ids' => $category_ids,
        ])->fetchField() > 0;
    }
}
