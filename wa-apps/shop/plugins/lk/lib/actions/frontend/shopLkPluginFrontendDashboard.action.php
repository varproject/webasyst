<?php

class shopLkPluginFrontendDashboardAction extends shopLkPluginFrontendBaseAction
{
    public function execute()
    {
        $this->view->assign(array(
            'active_company' => $this->context->getActiveCompany(),
            'companies' => $this->context->getCompanies(),
        ));
    }
}
