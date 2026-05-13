<?php

class filesSourceSaveController extends filesController
{
    public function execute()
    {
        $source = $this->getSource();

        if ($source->getId() > 0) {
            $source = $this->prepareExisting($source);
        }

        if (!$source) {
            return;
        }

        $source = $source->save();

        $this->assign(array(
            'source' => $source->getInfo(),
            'path' => $source->getPath(),
            'create_datetime_str' => wa_date('humandate', $source->getField('create_datetime'))
        ));

    }

    /**
     * @param filesSource $source
     * @return null|filesSource
     */
    public function prepareExisting($source)
    {
        if (!$source->isMounted()) {
            $this->mount($source);
        }

        return $source;
    }

    /**
     * @return filesSource
     */
    public function getSource()
    {
        $id = $this->getRequest()->post('id');
        $source = filesSource::factory($id);
        if (!$source) {
            $this->reportAboutError(_w("Source not found"));
        }

        // check access
        if (!is_numeric($source->getId())) {
            $access = true;
        } else if (filesRights::inst()->isAdmin() || $source->getOwner()->getId() == $this->contact_id) {
            $access = true;
        } else {
            $access = false;
        }

        return $access ? $source : $this->reportAboutError($this->getAccessDeniedError());
    }

    public function getStorageId()
    {
        return (int) wa()->getRequest()->post('storage_id');
    }

    public function getFolderId()
    {
        return (int) wa()->getRequest()->post('folder_id');
    }

    public function mount(filesSource $source)
    {
        if (wa()->getRequest()->post('point') === 'root') {
            $storage_id = $this->createStorage($source);
            $folder_id = 0;
        } else {
            $folder_id = $this->createFolder($source->getName());
            $folder = $this->getFileModel()->getItem($folder_id, false);
            $storage_id = $folder['storage_id'];
        }
        $source->mount(array(
            'storage_id' => $storage_id,
            'folder_id' => $folder_id
        ));
        $source->pauseSync();
    }

    public function createStorage(filesSource $source)
    {
        $name = $source->getName();
        if (!filesRights::inst()->canCreateStorage()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }
        $storage_id = $this->getStorageModel()->add(array(
            'access_type' => filesStorageModel::ACCESS_TYPE_PERSONAL,
            'name' => $name
        ));
        if (!$storage_id) {
            $this->reportAboutError(_w("Can't create storage"));
        }
        $storage = $this->getStorageModel()->getStorage($storage_id);
        if (!$storage) {
            $this->reportAboutError(_w("Can't create storage"));
        }
        if ($name !== $storage['name']) {
            $source->setName($storage['name']);
        }
        return $storage_id;
    }

    public function createFolder($name)
    {
        $storage_id = $this->getStorageId();
        $folder_id = $this->getFolderId();

        if (!filesRights::inst()->canCreateFolder($storage_id, $folder_id)) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        if ($folder_id) {
            $folder = $this->getFileModel()->getItem($folder_id, false);
            if ($folder['source_id'] > 0) {
                $this->reportAboutError($this->getAccessDeniedError("Folder is already related with another source"));
            }
            $storage_id = $folder['storage_id'];
        }

        $storage = $this->getStorageModel()->getStorage($storage_id);
        if ($storage['source_id'] > 0) {
            $this->reportAboutError($this->getAccessDeniedError("Storage is already related with another source"));
        }

        $folder_id = $this->getFileModel()->addFolder(array(
            'name' => $name,
            'parent_id' => $folder_id,
            'storage_id' => $storage_id
        ));
        if (!$folder_id) {
            $this->reportAboutError(_w("Can't create folder"));
        }
        return $folder_id;
    }

    public function getToken() {
        $token = wa()->getRequest()->request('token', '', waRequest::TYPE_STRING_TRIM);
        return $token;
    }

}
