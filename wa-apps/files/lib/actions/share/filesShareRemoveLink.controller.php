<?php

class filesShareRemoveLinkController extends filesController
{
    public function execute()
    {
        $file = $this->getFileForShareModule();
        $this->getFileModel()->deleteHash($file['id']);
    }
}