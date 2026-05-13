<?php

class filesCopyAction extends filesController
{
    public function execute() {
        $files = $this->getFiles();
        $this->assign(array(
            'unallowed_files' => $files['unallowed'],
            'allowed_files' => $files['allowed'],
            'file_ids' => $files,
            'level' => filesRightConfig::RIGHT_LEVEL_ADD_FILES
        ));
    }

    public function getFiles()
    {
        $file_ids = wa()->getRequest()->request('file_id', array(), waRequest::TYPE_ARRAY_INT);
        if (!$file_ids) {
            return array();
        }

        $hash = 'list/' . join(',', $file_ids);
        $collection = new filesCollection($hash, array(
            'workup' => false
        ));
        $allowed = $collection->getItems('id, name', 0, count($file_ids));
        $unallowed = $this->getFileModel()->getById(array_diff($file_ids, array_keys($allowed)));
        return array(
            'allowed' => $allowed,
            'unallowed' => $unallowed
        );
    }
}