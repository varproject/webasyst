<?php

class filesUploadNameConflictAction extends filesController
{
    public function execute()
    {
        $storage_id = $this->getStorageId();
        $folder_id = $this->getFolderId();
        $file_names = $this->getFileNames();

        $conflict_files = $this->getConflictFiles($file_names, $storage_id, $folder_id);
        $this->assign(array(
            'can_replace' => filesRights::inst()->canReplaceFiles($conflict_files),
            'conflict_files' => $conflict_files,
            'folder_id' => $this->getFolderId(),
            'storage_id' => $this->getStorageId()
        ));
    }

    public function getConflictFiles($file_names, $storage_id, $folder_id)
    {
        $files = $this->getFileModel()->getFilesByNames($file_names, $storage_id, $folder_id);
        return $this->getFileModel()->workupItems($files);
    }

    public function getFileNames()
    {
        $file_names = wa()->getRequest()->request('file_names', array());
        if (!$file_names) {
            return array();
        }
        return $file_names;
    }

    public function getFolderId()
    {
        return wa()->getRequest()->get('folder_id', 0, waRequest::TYPE_INT);
    }

    public function getStorageId()
    {
        return wa()->getRequest()->get('storage_id', 0, waRequest::TYPE_INT);
    }
}