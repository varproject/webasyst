<?php

class logsDialogPremiumPromoAction extends waViewAction
{
    public function execute()
    {
        $feature = waRequest::get('feature', '');
        $this->view->assign('feature', $feature);
    }
}
