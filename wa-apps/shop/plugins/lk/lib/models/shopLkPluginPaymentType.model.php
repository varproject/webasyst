<?php

class shopLkPluginPaymentTypeModel extends waModel
{
    protected $table = 'shop_lk_payment_type';

    public static function getDefaultRows()
    {
        return array(
            'new_invoice' => array('id' => 'new_invoice', 'code' => 'invoice', 'name' => 'Безналичный расчет', 'description' => 'Оплата по счету для юридических лиц.', 'enabled' => 1, 'sort' => 10),
            'new_card' => array('id' => 'new_card', 'code' => 'card', 'name' => 'Банковская карта', 'description' => 'Онлайн-оплата картой, если подключена на витрине.', 'enabled' => 1, 'sort' => 20),
            'new_postpay' => array('id' => 'new_postpay', 'code' => 'postpay', 'name' => 'Отсрочка платежа', 'description' => 'Доступно после согласования с менеджером.', 'enabled' => 1, 'sort' => 30),
        );
    }

    public function getEnabledByRoute($route_id)
    {
        return $this->getByField(array(
            'route_id' => (int) $route_id,
            'enabled' => 1,
        ), 'id');
    }

    public function getByRoute($route_id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE route_id = i:route_id ORDER BY sort, id";
        return $this->query($sql, array('route_id' => (int) $route_id))->fetchAll('id');
    }

    public function ensureDefaults($route_id)
    {
        $route_id = (int) $route_id;
        if ($this->countByField('route_id', $route_id)) {
            return;
        }

        foreach (self::getDefaultRows() as $row) {
            unset($row['id']);
            $row['route_id'] = $route_id;
            $row['config'] = '{}';
            $row['create_datetime'] = date('Y-m-d H:i:s');
            $this->insert($row);
        }
    }

    public function saveRoutePayments($route_id, array $rows)
    {
        $route_id = (int) $route_id;
        foreach ($rows as $id => $row) {
            $id = is_numeric($id) ? (int) $id : 0;
            $data = array(
                'route_id' => $route_id,
                'code' => shopLkPluginRouteService::slug(trim((string) ifset($row, 'code', 'payment'))),
                'name' => trim((string) ifset($row, 'name', '')),
                'description' => trim((string) ifset($row, 'description', '')),
                'enabled' => !empty($row['enabled']) ? 1 : 0,
                'sort' => (int) ifset($row, 'sort', 0),
                'config' => json_encode(ifset($row, 'config', array()), JSON_UNESCAPED_UNICODE),
                'update_datetime' => date('Y-m-d H:i:s'),
            );
            if ($data['code'] === '') {
                $data['code'] = 'payment';
            }
            if ($data['name'] === '') {
                continue;
            }
            if ($id > 0 && $this->getById($id)) {
                $this->updateById($id, $data);
            } else {
                $old = $this->getByField(array('route_id' => $route_id, 'code' => $data['code']));
                if ($old) {
                    $this->updateById($old['id'], $data);
                    continue;
                }
                $data['create_datetime'] = date('Y-m-d H:i:s');
                $this->insert($data);
            }
        }
    }

    public function copyRoutePaymentTypes($from_route_id, $to_route_id)
    {
        $this->deleteByField('route_id', (int) $to_route_id);
        foreach ($this->getByRoute((int) $from_route_id) as $row) {
            unset($row['id']);
            $row['route_id'] = (int) $to_route_id;
            $row['create_datetime'] = date('Y-m-d H:i:s');
            $row['update_datetime'] = null;
            $this->insert($row);
        }
    }
}
