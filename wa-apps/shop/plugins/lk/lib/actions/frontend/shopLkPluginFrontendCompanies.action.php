<?php

class shopLkPluginFrontendCompaniesAction extends shopLkPluginFrontendBaseAction
{
    public function execute()
    {
        $profiles = array();
        $profile_model = new shopLkPluginCompanyProfileModel();
        foreach ($this->context->getCompanies() as $company_id => $company) {
            $profiles[$company_id] = $profile_model->getByCompany($this->context->getRouteId(), $company_id);
        }
        $this->view->assign(array(
            'companies' => $this->context->getCompanies(),
            'profiles' => $profiles,
            'active_company_id' => $this->context->getActiveCompanyId(),
        ));
    }
}
