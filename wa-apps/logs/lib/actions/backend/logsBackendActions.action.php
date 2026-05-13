<?php

class logsBackendActionsAction extends logsViewAction
{
    public function execute()
    {
        $this->getResponse()->setTitle(_w('User actions'));
        $item_list = new logsItems(logsItems::MODE_ACTIONS);
        $this->view->assign('items', $item_list->get());
    }
}
