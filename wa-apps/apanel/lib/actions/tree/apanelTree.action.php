<?php

class apanelTreeAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('html', apanelUi::getControl('tree', 'tree', (array) $this->params));
    }
}
