<?php

class filesUploadAction extends filesController
{
    public function execute()
    {
        $this->assign(array(
            'max_file_size' => $this->getConfig()->getAppUploadMaxFilesSize()
        ));
    }
}
