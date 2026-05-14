<?php

class shopLkPluginCompanyAddressModel extends waModel
{
    protected $table = 'shop_lk_company_address';

    public function getByCompany($route_id, $company_contact_id)
    {
        return $this->getByField(array(
            'route_id' => (int) $route_id,
            'company_contact_id' => (int) $company_contact_id,
            'status' => 'active',
        ), true);
    }

    public function saveAddress($route_id, $company_contact_id, array $data)
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ifset($data, 'id', 0);
        $row = array(
            'route_id' => (int) $route_id,
            'company_contact_id' => (int) $company_contact_id,
            'name' => trim((string) ifset($data, 'name', '')),
            'type' => trim((string) ifset($data, 'type', 'shipping')) ?: 'shipping',
            'country' => trim((string) ifset($data, 'country', '')),
            'region' => trim((string) ifset($data, 'region', '')),
            'city' => trim((string) ifset($data, 'city', '')),
            'street' => trim((string) ifset($data, 'street', '')),
            'zip' => trim((string) ifset($data, 'zip', '')),
            'comment' => trim((string) ifset($data, 'comment', '')),
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'status' => trim((string) ifset($data, 'status', 'active')) ?: 'active',
            'update_datetime' => $now,
        );

        if ($row['is_default']) {
            $this->updateByField(array(
                'route_id' => (int) $route_id,
                'company_contact_id' => (int) $company_contact_id,
                'type' => $row['type'],
            ), array('is_default' => 0));
        }

        if ($id > 0 && $this->getById($id)) {
            $this->updateById($id, $row);
            return $id;
        }

        $row['create_contact_id'] = wa()->getUser()->getId();
        $row['create_datetime'] = $now;
        return (int) $this->insert($row);
    }
}
