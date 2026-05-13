<?php

class filesStatisticsFileTypesAction extends filesListController
{
    /**
     * @see filesListController
     * @var array
     */
    protected $orders = array('ext', 'size', 'count');

    public function execute()
    {
        if (!filesRights::inst()->isAdmin()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        $order = $this->getOrder(true);
        $stat = $this->getStatistics(array('order' => $order))->getFileTypes();;
        $this->assign($stat);
        $this->assign('order_info', $this->getOrderInfo());
    }
}