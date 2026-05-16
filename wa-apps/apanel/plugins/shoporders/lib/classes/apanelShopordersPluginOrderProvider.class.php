<?php

class apanelShopordersPluginOrderProvider
{
    protected $plugin;

    public function __construct(apanelShopordersPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getOrders(array $params = [])
    {
        if (!$this->isShopAvailable()) {
            return [];
        }

        $old_app = wa()->getApp();

        wa('shop', true);

        try {
            $orders = $this->fetchOrders($params);
        } catch (Exception $e) {
            waLog::log($e->getMessage(), 'apanel/shoporders.log');
            $orders = [];
        }

        wa($old_app, true);

        return $orders;
    }

    public function getStatusOptions()
    {
        if (!$this->isShopAvailable()) {
            return [];
        }

        $old_app = wa()->getApp();
        $options = [];

        wa('shop', true);

        try {
            $workflow = new shopWorkflow();
            $states = $workflow->getAllStates();

            foreach ($states as $state) {
                $id = (string) $state->getId();
                $name = (string) $state->getName();

                if ($id === '') {
                    continue;
                }

                $options[$id] = $name !== '' ? $name : $id;
            }
        } catch (Exception $e) {
            waLog::log($e->getMessage(), 'apanel/shoporders.log');
        }

        wa($old_app, true);

        return $options;
    }

    public function getSalesChannelOptions()
    {
        return $this->getDistinctOrderFieldOptions('sales_channel');
    }

    public function getShippingTypeOptions()
    {
        return $this->getDistinctOrderFieldOptions('shipping_id');
    }

    public function getPaymentTypeOptions()
    {
        return $this->getDistinctOrderFieldOptions('payment_id');
    }

    protected function fetchOrders(array $params = [])
    {
        $settings = $this->plugin->getNormalizedSettings();

        $limit = $this->normalizeLimit(ifset($params['limit'], 500));
        $offset = max(0, (int) ifset($params['offset'], 0));

        $where = [];
        $bind = [];

        $this->appendInCondition($where, $bind, 'o.state_id', 'statuses', $settings['statuses']);
        $this->appendInCondition($where, $bind, 'o.sales_channel', 'sales_channels', $settings['sales_channels']);
        $this->appendInCondition($where, $bind, 'o.shipping_id', 'shipping_types', $settings['shipping_types']);
        $this->appendInCondition($where, $bind, 'o.payment_id', 'payment_types', $settings['payment_types']);

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT
                o.id,
                o.id_str,
                o.state_id,
                o.create_datetime,
                o.update_datetime,
                o.contact_id,
                o.total,
                o.currency,
                o.rate,
                o.shipping,
                o.tax,
                o.discount,
                o.sales_channel,
                o.shipping_id,
                o.payment_id,
                o.paid_datetime
            FROM shop_order o
            {$where_sql}
            ORDER BY o.create_datetime DESC, o.id DESC
            LIMIT i:limit OFFSET i:offset
        ";

        $bind['limit'] = $limit;
        $bind['offset'] = $offset;

        $model = new shopOrderModel();
        $orders = $model->query($sql, $bind)->fetchAll('id');

        if (!$orders) {
            return [];
        }

        return $this->prepareOrders($orders);
    }

    protected function prepareOrders(array $orders)
    {
        $states = $this->getStatusOptions();

        foreach ($orders as &$order) {
            $state_id = (string) ifset($order['state_id'], '');
            $shipping_id = (string) ifset($order['shipping_id'], '');
            $payment_id = (string) ifset($order['payment_id'], '');
            $sales_channel = (string) ifset($order['sales_channel'], '');

            $order['state_name'] = ifset($states[$state_id], $state_id);
            $order['sales_channel_name'] = $sales_channel !== '' ? $sales_channel : '—';
            $order['shipping_type_name'] = $shipping_id !== '' ? $shipping_id : '—';
            $order['payment_type_name'] = $payment_id !== '' ? $payment_id : '—';
        }
        unset($order);

        return array_values($orders);
    }

    protected function getDistinctOrderFieldOptions($field)
    {
        if (!$this->isShopAvailable()) {
            return [];
        }

        $allowed_fields = [
            'sales_channel',
            'shipping_id',
            'payment_id',
        ];

        if (!in_array($field, $allowed_fields, true)) {
            return [];
        }

        $old_app = wa()->getApp();
        $options = [];

        wa('shop', true);

        try {
            $model = new shopOrderModel();

            $sql = "
                SELECT DISTINCT {$field} value
                FROM shop_order
                WHERE {$field} IS NOT NULL
                  AND {$field} != ''
                ORDER BY {$field}
            ";

            $rows = $model->query($sql)->fetchAll();

            foreach ($rows as $row) {
                $value = trim((string) ifset($row['value'], ''));

                if ($value === '') {
                    continue;
                }

                $options[$value] = $value;
            }
        } catch (Exception $e) {
            waLog::log($e->getMessage(), 'apanel/shoporders.log');
        }

        wa($old_app, true);

        return $options;
    }

    protected function appendInCondition(array &$where, array &$bind, $field, $prefix, array $values)
    {
        $values = $this->normalizeStringArray($values);

        if (!$values) {
            return;
        }

        $placeholders = [];

        foreach ($values as $i => $value) {
            $key = $prefix . '_' . $i;
            $placeholders[] = 's:' . $key;
            $bind[$key] = $value;
        }

        if (!$placeholders) {
            return;
        }

        $where[] = $field . ' IN (' . implode(', ', $placeholders) . ')';
    }

    protected function normalizeStringArray($value)
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $result = [];

        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item === '') {
                continue;
            }

            $result[] = $item;
        }

        return array_values(array_unique($result));
    }

    protected function normalizeLimit($limit)
    {
        $limit = (int) $limit;

        if ($limit <= 0) {
            return 500;
        }

        if ($limit > 5000) {
            return 5000;
        }

        return $limit;
    }

    protected function isShopAvailable()
    {
        return wa()->appExists('shop');
    }
}
