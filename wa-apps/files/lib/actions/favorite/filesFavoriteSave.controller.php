<?php

class filesFavoriteSaveController extends filesController
{
    public function execute() {
        $file_id = $this->getFileId();
        if ($this->getFavoriteModel()->isFavorite($file_id)) {
            $this->getFavoriteModel()->unsetFavorite($file_id);
        } else {
            $this->getFavoriteModel()->setFavorite($file_id);
        }
        $this->assign(array(
            'favorite' => $this->getFavoriteModel()->isFavorite($file_id)
        ));
    }

    public function getFileId()
    {
        $id = wa()->getRequest()->get('id', null, waRequest::TYPE_INT);
        $file = $this->getFileModel()->getItem($id);
        if (!$file) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        $storage = $this->getStorageModel()->getStorage($file['storage_id']);
        if (!$storage) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if (!filesRights::inst()->canReadFile($file['id'])) {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
            return false;
        }
        return $file['id'];
    }
}