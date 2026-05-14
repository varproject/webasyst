<?php

class shopLkPluginCompanyProfileModel extends waModel
{
    protected $table = 'shop_lk_company_profile';

    public function getByCompany($route_id, $company_contact_id)
    {
        return $this->getByField(array(
            'route_id' => (int) $route_id,
            'company_contact_id' => (int) $company_contact_id,
        ));
    }

    public function saveProfile($route_id, $company_contact_id, array $data)
    {
        $now = date('Y-m-d H:i:s');
        $row = array(
            'route_id' => (int) $route_id,
            'company_contact_id' => (int) $company_contact_id,
            'legal_name' => trim((string) ifset($data, 'legal_name', '')),
            'inn' => trim((string) ifset($data, 'inn', '')),
            'kpp' => trim((string) ifset($data, 'kpp', '')),
            'ogrn' => trim((string) ifset($data, 'ogrn', '')),
            'status' => trim((string) ifset($data, 'status', 'active')) ?: 'active',
            'payment_type_id' => (int) ifset($data, 'payment_type_id', 0) ?: null,
            'manager_contact_id' => (int) ifset($data, 'manager_contact_id', 0) ?: null,
            'update_datetime' => $now,
        );

        $old = $this->getByCompany($route_id, $company_contact_id);
        if ($old) {
            $this->updateById($old['id'], $row);
            return (int) $old['id'];
        }

        $row['create_contact_id'] = wa()->getUser()->getId();
        $row['create_datetime'] = $now;
        return (int) $this->insert($row);
    }
}
