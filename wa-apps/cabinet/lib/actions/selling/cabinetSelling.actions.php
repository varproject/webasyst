<?php


class cabinetSellingActions extends waViewActions
{
    protected $statuses;

    public function preExecute()
    {
        $csm = new cabinetStatusModel();
        $this->statuses = $csm->getTree();
        // dd($this->statuses);
    }

    public function defaultAction()
    {
        $com = new cabinetOrderModel();
        $orders = $com->getListWithCounterparty();

        $this->view->assign([
            'orders' => $orders,
            'statuses' => $this->statuses,
            // 'action_sidebar_tpl_path' => 'SellingSidebar.html',
        ]);
    }


    public function statusesAction()
    {

        $this->view->assign([
            'action_tpl_mode' => true,
            'statuses' => $this->statuses,
        ]);
    }
}
