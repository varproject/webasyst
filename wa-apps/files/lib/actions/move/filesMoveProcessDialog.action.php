<?php

class filesMoveProcessDialogAction extends filesController
{
    public function execute() {
        $this->view->assign(array(
            'copytask_process_id' => (int) wa()->getRequest()->get('copytask_process_id')
        ));
    }
}