<?php

class shopLkPluginFrontendPaymentsAction extends shopLkPluginFrontendBaseAction
{
    public function execute()
    {
        $this->requireCompany();
        $payment_model = new shopLkPluginPaymentTypeModel();
        $payment_model->ensureDefaults($this->context->getRouteId());
        $profile = (new shopLkPluginCompanyProfileModel())->getByCompany($this->context->getRouteId(), $this->context->getActiveCompanyId());
        $this->view->assign(array(
            'payments' => $payment_model->getEnabledByRoute($this->context->getRouteId()),
            'profile' => $profile,
            'can_manage' => $this->context->canManageCompany(),
        ));
    }
}
