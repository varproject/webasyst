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
        if ($route_id <= 0) {
            return;
        }

        foreach (self::getDefaultRows() as $row) {
            $code = shopLkPluginRouteService::slug(trim((string) $row['code']), 'payment');
            $exists = $this->getByField(array(
                'route_id' => $route_id,
                'code' => $code,
            ));

            if ($exists) {
                continue;
            }

            unset($row['id']);
            $row['route_id'] = $route_id;
            $row['code'] = $code;
            $row['config'] = '{}';
            $row['create_datetime'] = date('Y-m-d H:i:s');
            $this->insert($row);
        }
    }

    public function saveRoutePayments($route_id, array $rows)
    {
        $route_id = (int) $route_id;
        if ($route_id <= 0) {
            return;
        }

        foreach ($rows as $id => $row) {
            $id = is_numeric($id) ? (int) $id : 0;
            $code = shopLkPluginRouteService::slug(trim((string) ifset($row, 'code', 'payment')), 'payment');
            $name = trim((string) ifset($row, 'name', ''));

            if ($name === '') {
                continue;
            }

            $data = array(
                'route_id' => $route_id,
                'code' => $code,
                'name' => $name,
                'description' => trim((string) ifset($row, 'description', '')),
                'enabled' => !empty($row['enabled']) ? 1 : 0,
                'sort' => (int) ifset($row, 'sort', 0),
                'config' => json_encode(ifset($row, 'config', array()), JSON_UNESCAPED_UNICODE),
                'update_datetime' => date('Y-m-d H:i:s'),
            );

            $old = null;
            if ($id > 0) {
                $candidate = $this->getById($id);
                if ($candidate && (int) $candidate['route_id'] === $route_id) {
                    $old = $candidate;
                }
            }

            if (!$old) {
                $old = $this->getByField(array('route_id' => $route_id, 'code' => $code));
            }

            if ($old) {
                $this->updateById($old['id'], $data);
                $this->deleteDuplicateCodes($route_id, $code, (int) $old['id']);
                continue;
            }

            $data['create_datetime'] = date('Y-m-d H:i:s');
            $new_id = (int) $this->insert($data);
            $this->deleteDuplicateCodes($route_id, $code, $new_id);
        }
    }

    public function copyRoutePaymentTypes($from_route_id, $to_route_id)
    {
        $from_route_id = (int) $from_route_id;
        $to_route_id = (int) $to_route_id;

        if ($from_route_id <= 0 || $to_route_id <= 0 || $from_route_id === $to_route_id) {
            return;
        }

        $this->deleteByField('route_id', $to_route_id);
        foreach ($this->getByRoute($from_route_id) as $row) {
            unset($row['id']);
            $row['route_id'] = $to_route_id;
            $row['create_datetime'] = date('Y-m-d H:i:s');
            $row['update_datetime'] = null;
            $this->insert($row);
        }
    }

    protected function deleteDuplicateCodes($route_id, $code, $keep_id)
    {
        $rows = $this->getByField(array(
            'route_id' => (int) $route_id,
            'code' => (string) $code,
        ), true);

        foreach ($rows as $row) {
            if ((int) $row['id'] !== (int) $keep_id) {
                $this->deleteById($row['id']);
            }
        }
    }
}
