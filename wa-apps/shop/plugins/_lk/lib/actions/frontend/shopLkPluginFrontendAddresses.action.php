<?php

class shopLkPluginFrontendAddressesAction extends shopLkPluginFrontendBaseAction
{
    public function execute()
    {
        $this->requireCompany();
        $addresses = (new shopLkPluginCompanyAddressModel())->getByCompany($this->context->getRouteId(), $this->context->getActiveCompanyId());
        $this->view->assign(array(
            'active_company' => $this->context->getActiveCompany(),
            'addresses' => $addresses,
            'can_manage' => $this->context->canManageCompany(),
        ));
    }
}
