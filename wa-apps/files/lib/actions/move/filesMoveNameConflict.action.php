<?php

class filesMoveNameConflictAction extends filesController
{
    public function execute()
    {
        $storage_id = $this->getStorageId();
        $folder_id = $this->getFolderId();
        $files = $this->getFiles();

        $conflict = $this->getFileModel()->getConflictFiles($files, $storage_id, $folder_id);
        $this->assign(array(
            'files' => $files,
            'can_replace' => filesRights::inst()->canReplaceFiles($conflict['dst_conflict_files']),
            'conflict_files' => $conflict['src_conflict_files'],
            'folder_id' => $this->getFolderId(),
            'storage_id' => $this->getStorageId(),
            'is_restore' => $this->isRestore()
        ));
    }

    public function getFiles()
    {
        $file_ids = wa()->getRequest()->request('file_id', null, waRequest::TYPE_ARRAY_INT);
        if (!$file_ids) {
            return array();
        }
        $files = $this->getFileModel()->getById($file_ids);
        $files = $this->getFileModel()->workupItems($files);
        return $files;
    }

    public function getFolderId()
    {
        return wa()->getRequest()->get('folder_id', 0, waRequest::TYPE_INT);
    }

    public function getStorageId()
    {
        return wa()->getRequest()->get('storage_id', 0, waRequest::TYPE_INT);
    }

    public function isRestore()
    {
        return wa()->getRequest()->request('restore');
    }

}