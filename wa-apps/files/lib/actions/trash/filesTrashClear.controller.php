<?php

class filesTrashClearController extends filesRemoveFilesController
{
    public function getFileIds()
    {
        $options = array(
            'workup' => 'array_keys'
        );

        // delete by storage?
        $storage_id = $this->getStorageId();
        if ($storage_id !== null) {
            $options['filter'] = array(
                'storage_id' => -$storage_id
            );
        }

        $col = $this->getCollection('trash', $options);
        $total_count = max($col->count(), 500);
        return $col->getItems('id', 0, $total_count);
    }

    public function getStorageId()
    {
        $storage_id = wa()->getRequest()->post('storage_id');

        // ignore
        if (!$storage_id) {
            return null;
        }

        // check existence
        $storage = $this->getStorageModel()->getStorage($storage_id);
        if (!$storage) {
            $this->reportAboutError($this->getPageNotFoundError());
        }

        // check rights
        if (!filesRights::inst()->isAdmin()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        return $storage['id'];
    }

    public function isPermanently()
    {
        return true;
    }

    protected function unmountInnerSources($files)
    {
        return;
    }
}
