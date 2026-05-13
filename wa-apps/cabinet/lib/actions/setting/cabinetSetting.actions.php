<?php

class cabinetSettingActions extends waViewActions
{
    public function preExecute()
    {
        $this->view->assign([
            // 'action_tpl_mode' => true,
        ]);
        dd(555555);

    }

    public function defaultAction()
    {
        $this->view->assign([]);
    }


    public function statusesAction()
    {
        $this->view->assign([]);
    }
}
