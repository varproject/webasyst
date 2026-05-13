<?php

class logsItemLinesAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign($this->params);
        $this->setTemplate('ItemLines.html', true);
    }
}
