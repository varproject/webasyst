<?php

final class shopLkPluginCompanyService
{
    public function createCompany($route_id, array $data)
    {
        $company = trim((string) ifset($data, 'company', ''));
        if ($company === '') {
            throw new waException('Укажите название компании.');
        }

        $contact = new waContact();
        $contact->save(array(
            'is_company' => 1,
            'company' => $company,
            'name' => $company,
            'email' => trim((string) ifset($data, 'email', '')),
            'phone' => trim((string) ifset($data, 'phone', '')),
        ), true);

        $company_contact_id = $contact->getId();
        (new shopLkPluginCompanyProfileModel())->saveProfile($route_id, $company_contact_id, array(
            'legal_name' => ifset($data, 'legal_name', $company),
            'inn' => ifset($data, 'inn', ''),
            'kpp' => ifset($data, 'kpp', ''),
            'ogrn' => ifset($data, 'ogrn', ''),
            'status' => 'active',
        ));
        (new shopLkPluginCompanyMemberModel())->addOwner($route_id, $company_contact_id, wa()->getUser()->getId());
        shopLkPluginCabinetContext::setActiveCompany($route_id, $company_contact_id);

        return $company_contact_id;
    }
}
