<?php

final class shopLkPluginCabinetContext
{
    protected $route;
    protected $contact_id;
    protected $companies;
    protected $active_company_id;
    protected $member;

    public function __construct(array $route)
    {
        $this->route = $route;
        $this->contact_id = wa()->getUser()->getId();
        $member_model = new shopLkPluginCompanyMemberModel();
        $this->companies = $member_model->getCompaniesByContact($this->getRouteId(), $this->contact_id);
        $this->active_company_id = $this->resolveActiveCompanyId();
        $this->member = $this->active_company_id ? $member_model->getMember($this->getRouteId(), $this->active_company_id, $this->contact_id) : null;
    }

    public function getRoute() { return $this->route; }
    public function getRouteId() { return (int) $this->route['id']; }
    public function getContactId() { return (int) $this->contact_id; }
    public function getCompanies() { return $this->companies; }
    public function getActiveCompanyId() { return (int) $this->active_company_id; }
    public function getMember() { return $this->member; }
    public function hasCompanies() { return !empty($this->companies); }

    public function getActiveCompany()
    {
        return $this->active_company_id && isset($this->companies[$this->active_company_id]) ? $this->companies[$this->active_company_id] : null;
    }

    public function canManageCompany()
    {
        return $this->member && in_array($this->member['role'], array('owner', 'admin'), true);
    }

    protected function resolveActiveCompanyId()
    {
        if (!$this->companies) {
            return 0;
        }
        $settings_model = new waContactSettingsModel();
        $key = 'lk_active_company_'.$this->getRouteId();
        $stored = (int) $settings_model->getOne($this->contact_id, 'shop', $key);
        if ($stored && isset($this->companies[$stored])) {
            return $stored;
        }
        $first = reset($this->companies);
        $id = (int) $first['company_contact_id'];
        $settings_model->set($this->contact_id, 'shop', $key, $id);
        return $id;
    }

    public static function setActiveCompany($route_id, $company_contact_id)
    {
        $member_model = new shopLkPluginCompanyMemberModel();
        $contact_id = wa()->getUser()->getId();
        $member = $member_model->getMember($route_id, $company_contact_id, $contact_id);
        if (!$member || $member['status'] !== 'active') {
            throw new waRightsException('Нет доступа к компании.');
        }
        (new waContactSettingsModel())->set($contact_id, 'shop', 'lk_active_company_'.(int)$route_id, (int)$company_contact_id);
    }
}
