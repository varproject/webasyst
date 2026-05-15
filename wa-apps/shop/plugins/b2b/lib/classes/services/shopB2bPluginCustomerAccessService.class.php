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

    // Возвращает выбранных покупателей для Select2.
    public function getSelectedCustomers(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $model = new waModel();
        $sql   = "
            SELECT c.id, c.name, e.email
            FROM shop_customer sc
                JOIN wa_contact c ON c.id = sc.contact_id
                LEFT JOIN wa_contact_emails e ON e.contact_id = c.id AND e.sort = 0
            WHERE c.id IN (i:ids)
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

        $model = new waModel();
        $like  = '%' . $model->escape($query, 'like') . '%';
        $sql   = "
            SELECT c.id, c.name, e.email
            FROM shop_customer sc
                JOIN wa_contact c ON c.id = sc.contact_id
                LEFT JOIN wa_contact_emails e ON e.contact_id = c.id AND e.sort = 0
            WHERE c.name LIKE s:query
                OR e.email LIKE s:query
            GROUP BY c.id
            ORDER BY c.name
            LIMIT {$limit}
        ";

        return $model->query($sql, [
            'query' => $like,
        ])->fetchAll('id');
    }

    // Проверяет доступ контакта к B2B-каналу.
    public function canAccess($contact_id, array $params): bool
    {
        $contact_id = (int) $contact_id;
        $mode       = ifset($params, 'access_mode', 'all');

        if ($mode === 'all') {
            return true;
        }

        if ($contact_id <= 0 || !$this->isCustomer($contact_id)) {
            return false;
        }

        if ($mode === 'customers') {
            return in_array($contact_id, $this->getIds(ifset($params, 'access_customer_ids', '')));
        }

        if ($mode === 'categories') {
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

        return false;
    }

    // Проверяет, что контакт является покупателем магазина.
    protected function isCustomer($contact_id): bool
    {
        $model = new shopCustomerModel();

        return !!$model->getById((int) $contact_id);
    }
}
