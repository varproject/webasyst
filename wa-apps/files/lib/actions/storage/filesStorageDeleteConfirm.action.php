<?php

class filesStorageDeleteConfirmAction extends filesController
{
    public function execute()
    {
        $storage = $this->getFilesStorage();
        $files_count = $this->getCollection("storage/{$storage['id']}")->count();
        $this->assign(array(
            'storage' => $storage,
            'files_count' => $files_count
        ));
    }

    public function getFilesStorage()
    {
        $id = (int) $this->getRequest()->get('id');
        $storage = $this->getStorageModel()->getStorage($id);
        if (!$storage) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }
        $check_res = $this->checkStorageId($storage['id']);
        if ($check_res !== true) {
            filesApp::inst()->reportAboutError($check_res);
        }

        if (!filesRights::inst()->canDeleteStorage($storage)) {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
        }

        return $storage;
    }

}