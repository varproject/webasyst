<?php

class filesStatisticsTrashAction extends filesListController
{
    /**
     * @see filesListController
     * @var array
     */
    protected $orders = array('name', 'size', 'count');

    public function execute()
    {
        if (!filesRights::inst()->isAdmin()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        $order = $this->getOrder(true);
        $stat = $this->getStatistics(array('order' => $order))->getTrash();
        $this->assign($stat);
        $this->assign('order_info', $this->getOrderInfo());
    }
}