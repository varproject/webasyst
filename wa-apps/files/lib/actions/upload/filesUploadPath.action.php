<?php

class filesUploadPathAction extends filesController
{
    public function execute()
    {
        $folder_id = $this->getFolderId();
        $path = $this->getPath($folder_id);
        $this->assign(array(
            'folder_id' => $folder_id,
            'path' => $path,
            'is_personal' => $this->isPersonal($path)
        ));
    }

    public function getPath($folder_id)
    {
        $folder_path = array();
        if ($folder_id) {
            $folder_path = $this->getFileModel()->getPathToFolder($folder_id);
        } else {
            $storage = $this->getFileStorage();
            $storage['type'] = 'storage';
            if ($storage) {
                $folder_path[] = $storage;
            }
        }
        return $folder_path;
    }


    private function isPersonal($path)
    {
        $endpoint = end($path);
        if ($endpoint['type'] === 'storage') {
            return filesRights::inst()->isStoragesPersonal($endpoint);
        } else {
            return filesRights::inst()->isFilesPersonal($endpoint);
        }
    }

    public function getFolderId()
    {
        $folder_id = wa()->getRequest()->get('folder_id', null, waRequest::TYPE_INT);
        $folder = $this->getFileModel()->getFolder($folder_id);
        if ($folder && filesRights::inst()->canAddNewFilesIntoFolder($folder['id']) && !$this->getFileModel()->inSync($folder)) {
            return $folder['id'];
        }
        return false;
    }

    public function getFileStorage()
    {
        $storage_id = wa()->getRequest()->get('storage_id', null, waRequest::TYPE_INT);
        $storage = $this->getStorageModel()->getStorage($storage_id);
        if ($storage && filesRights::inst()->canAddNewFilesIntoStorage($storage) && !$this->getStorageModel()->inSync($storage)) {
            return $storage;
        } else {
            return $this->getStorageModel()->getPersistenStorage();
        }
    }

}