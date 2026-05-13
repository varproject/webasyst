<?php

class apanelTableAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('html', apanelUi::getControl('table', 'table', (array) $this->params));
    }
}
