<?php

class shopLkPluginCompanyMemberModel extends waModel
{
    protected $table = 'shop_lk_company_member';

    public function getCompaniesByContact($route_id, $contact_id)
    {
        $sql = "SELECT m.*, c.name, c.company, c.email
                FROM {$this->table} m
                JOIN wa_contact c ON c.id = m.company_contact_id
                WHERE m.route_id = i:route_id AND m.contact_id = i:contact_id AND m.status = 'active'
                ORDER BY c.name, c.company";
        return $this->query($sql, array(
            'route_id' => (int) $route_id,
            'contact_id' => (int) $contact_id,
        ))->fetchAll('company_contact_id');
    }

    public function getMember($route_id, $company_contact_id, $contact_id)
    {
        return $this->getByField(array(
            'route_id' => (int) $route_id,
            'company_contact_id' => (int) $company_contact_id,
            'contact_id' => (int) $contact_id,
        ));
    }

    public function addOwner($route_id, $company_contact_id, $contact_id)
    {
        return $this->saveMember($route_id, $company_contact_id, $contact_id, array(
            'role' => 'owner',
            'status' => 'active',
            'is_owner' => 1,
        ));
    }

    public function saveMember($route_id, $company_contact_id, $contact_id, array $data)
    {
        $now = date('Y-m-d H:i:s');
        $row = array(
            'route_id' => (int) $route_id,
            'company_contact_id' => (int) $company_contact_id,
            'contact_id' => (int) $contact_id,
            'role' => trim((string) ifset($data, 'role', 'member')) ?: 'member',
            'status' => trim((string) ifset($data, 'status', 'active')) ?: 'active',
            'is_owner' => !empty($data['is_owner']) ? 1 : 0,
            'update_datetime' => $now,
        );

        $old = $this->getMember($route_id, $company_contact_id, $contact_id);
        if ($old) {
            $this->updateById($old['id'], $row);
            return (int) $old['id'];
        }

        $row['create_contact_id'] = wa()->getUser()->getId();
        $row['create_datetime'] = $now;
        return (int) $this->insert($row);
    }
}
