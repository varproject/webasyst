<?php

class shopLkPluginFrontendOrdersAction extends shopLkPluginFrontendBaseAction
{
    public function execute()
    {
        $this->requireCompany();
        $this->view->assign(array(
            'active_company' => $this->context->getActiveCompany(),
            'message' => 'Раздел заказов готов к фильтрации по active_company_contact_id. Подключите выборку заказов по вашей бизнес-логике.',
        ));
    }
}
