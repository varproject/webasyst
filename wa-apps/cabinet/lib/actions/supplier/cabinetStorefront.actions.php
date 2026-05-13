<?php

class cabinetSupplierActions extends waViewActions
{
    public function preExecute()
    {
        $this->setLayout(new cabinetBackendLayout());
        $this->setTemplate(wa()->getAppPath('templates/actions/backend/main.html', 'cabinet'));
        waRequest::setParam('sidebar_mode', true);

        $this->view->assign([
            'title'             => 'Мои кабинеты',
        ]);
    }

    public function defaultAction() {}
}
