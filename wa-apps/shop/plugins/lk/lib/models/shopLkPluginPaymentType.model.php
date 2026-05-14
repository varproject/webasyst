<?php

class shopLkPluginPaymentTypeModel extends waModel
{
    protected $table = 'shop_lk_payment_type';

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

        $defaults = array(
            array('code' => 'invoice', 'name' => 'Безналичный расчет', 'description' => 'Оплата по счету для юридических лиц.', 'sort' => 10),
            array('code' => 'card', 'name' => 'Банковская карта', 'description' => 'Онлайн-оплата картой, если подключена на витрине.', 'sort' => 20),
            array('code' => 'postpay', 'name' => 'Отсрочка платежа', 'description' => 'Доступно после согласования с менеджером.', 'sort' => 30),
        );

        foreach ($defaults as $row) {
            $row['route_id'] = $route_id;
            $row['enabled'] = 1;
            $row['config'] = '{}';
            $row['create_datetime'] = date('Y-m-d H:i:s');
            $this->insert($row);
        }
    }

    public function saveRoutePayments($route_id, array $rows)
    {
        $route_id = (int) $route_id;
        foreach ($rows as $id => $row) {
            $id = (int) $id;
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
                $data['create_datetime'] = date('Y-m-d H:i:s');
                $this->insert($data);
            }
        }
    }

    public function copyRoutePaymentTypes($from_route_id, $to_route_id)
    {
        foreach ($this->getByRoute((int) $from_route_id) as $row) {
            unset($row['id']);
            $row['route_id'] = (int) $to_route_id;
            $row['create_datetime'] = date('Y-m-d H:i:s');
            $row['update_datetime'] = null;
            $this->insert($row);
        }
    }
}
