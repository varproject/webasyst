<?php

class filesDeleteFilesController extends filesRemoveFilesController
{
    public function isPermanently()
    {
        return wa()->getRequest()->request('permanently');
    }

    public function dropInSync($file_ids)
    {
        if (!$file_ids) {
            return array();
        }
        $sync_map = $this->getFileModel()->inSync($file_ids);
        foreach ($file_ids as $i => $id) {
            if (!empty($sync_map[$id])) {
                unset($file_ids[$i]);
            }
        }
        return $file_ids;
    }

    public function getFileIds()
    {
        $file_ids = wa()->getRequest()->request('file_id', null, waRequest::TYPE_ARRAY_INT);
        if (!$file_ids) {
            return array();
        }
        if (!$this->isPermanently()) {
            $file_ids = filesRights::inst()->dropUnallowedToMove($file_ids);
        } else {
            $file_ids = filesRights::inst()->dropHasNotAnyAccess($file_ids);
        }

        $file_ids = $this->dropInSync($file_ids);
        if (count($file_ids) > 500) {
            $file_ids = array_slice($file_ids, 0, 500);
        }
        return $file_ids;
    }
}
