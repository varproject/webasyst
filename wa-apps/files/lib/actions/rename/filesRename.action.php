<?php

class filesRenameAction extends filesController
{
    public function execute() {
        $file = $this->getFile();
        $this->assign(array(
            'file' => $file,
            'can_rename' => $this->canRename($file),
            'in_sync' => $this->getFileModel()->inSync($file)
        ));
    }

    public function getFile()
    {
        $file_id = (int) wa()->getRequest()->get('id');
        $file = $this->getFileModel()->getItem($file_id);
        if (!$file) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }
        return $file;
    }

    public function canRename($file)
    {
        $files = array($file['id'] => $file);
        $allowed = filesRights::inst()->dropUnallowedToMove($files);
        return !!$allowed;
    }
}