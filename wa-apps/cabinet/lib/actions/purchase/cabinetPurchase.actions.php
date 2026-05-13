<?php

class cabinetPurchaseActions extends waViewActions
{
    public function preExecute()
    {
        $this->setLayout(new cabinetBackendLayout());
        $this->setTemplate(wa()->getAppPath('templates/actions/backend/main.html', 'cabinet'));

        $this->view->assign([
            // 'sidebar_mode' => false,
            // 'sidebar_custom' => true,
        ]);

        // dd(waRequest::param());
    }

    public function defaultAction() {}
}
