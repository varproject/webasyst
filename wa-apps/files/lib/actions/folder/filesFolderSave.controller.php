<?php

class filesFolderSaveController extends filesController
{

    public function execute()
    {
        $data = $this->getData();
        $errors = $this->validate($data);
        if (!empty($errors)) {
            $this->errors = $errors;
            return;
        }

        $file_id = $this->getFileModel()->addFolder($data);

        /**
         * Save folder event
         * @event folder_save
         * @params array[string]int $params['id'] Folder id
         * @params array[string]bool $params['just_created'] Has folder be just created
         */
        $params = array('id' => $file_id, 'just_created' => true);
        wa()->event('folder_save', $params);

        $folder = $this->getFileModel()->getFolder($file_id);

        $this->logAction('folder_create', $file_id);

        $this->response['folder'] = $folder;
    }

    public function validate(&$data)
    {
        $errors = array();

        if (strlen($data['name']) <= 0) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('This field is required')
            );
        }

        $val = $data['name'];
        $banned_symbols = $this->getFileModel()->getBannedSymbols();
        if (preg_match($banned_symbols, $val)) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('There are forbidden characters.')
            );
        }

        $suffix = $this->getFileModel()->generateUniqueNameSuffix(
            array(
                'name' => $data['name'],
                'storage_id' => $data['storage_id'],
                'parent_id' => $data['parent_id'],
                'type' => filesFileModel::TYPE_FOLDER
            )
        );
        if ($suffix === false) {
            $errors[] = array(
                'name' => 'name',
                'msg' => _w('Name is not unique. Try to rename.')
            );
        }
        $data['name'] .= $suffix;

        return $errors;
    }

    public function getData()
    {
        $folder_id = $this->getFolderId();
        $storage_id = $this->getStorageId();

        if (!filesRights::inst()->canCreateFolder($storage_id, $folder_id)) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        if ($folder_id) {
            $in_sync = $this->getFileModel()->inSync($folder_id);
        } else {
            $in_sync = $this->getStorageModel()->inSync($storage_id);
        }
        if ($in_sync) {
            $this->reportAboutError($this->getInSyncError());
        }

        $source_id = $this->getSourceId($storage_id, $folder_id);

        $data = array(
            'name' => $this->getName(),
            'parent_id' => $folder_id,
            'storage_id' => $storage_id,
            'source_id' => $source_id
        );

        return $data;
    }

    public function getName()
    {
        return $this->getRequest()->post('name', '', waRequest::TYPE_STRING_TRIM);
    }

    public function getFolderId()
    {
        return (int) $this->getRequest()->post('folder_id', 0, waRequest::TYPE_INT);
    }

    public function getStorageId()
    {
        return (int) $this->getRequest()->post('storage_id');
    }

    public function getSourceId($storage_id, $folder_id)
    {
        $folder = $this->getFileModel()->getFolder($folder_id);
        if ($folder) {
            return $folder['source_id'];
        }

        $storage = $this->getStorageModel()->getById($storage_id);
        if ($storage) {
            return $storage['source_id'];
        }

        return 0;
    }

}
