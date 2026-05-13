<?php

class filesTrashSaveController extends filesController
{
    public function execute()
    {
        $this->getFileModel()->setTrashType(wa()->getRequest()->post('type'));
    }
}