<?php

class filesFolderCreateAction extends filesController
{

    public function execute()
    {
        $storage_id = $this->getStorageId();
        $folder_id = $this->getFolderId();
        $this->view->assign(array(
            'storage_id' => $storage_id,
            'folder_id' => $folder_id,
            'can_add' => filesRights::inst()->canCreateFolder($storage_id, $folder_id),
            'in_sync' => $this->inSync($storage_id, $folder_id)
        ));
    }

    public function getStorageId()
    {
        return (int) $this->getRequest()->get('storage_id');
    }

    public function getFolderId()
    {
        return (int) $this->getRequest()->get('folder_id');
    }

    public function inSync($storage_id, $folder_id)
    {
        if ($folder_id) {
            return $this->getFileModel()->inSync($folder_id);
        } else {
            return $this->getStorageModel()->inSync($storage_id);
        }
    }

}