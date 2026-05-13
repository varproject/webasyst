<?php

class filesBackendRepairAction extends filesController
{
    public function execute()
    {
        if (!filesRights::inst()->hasFullAccess()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        $file_model = new filesFileModel();
        $file_model->repair();
        echo 'Repaired';
        exit;
    }
}
