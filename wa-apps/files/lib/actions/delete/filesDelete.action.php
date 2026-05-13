<?php

class filesDeleteAction extends filesController
{
    public function execute() {
        $files = $this->getFiles();
        $this->assign(array(
            'allowed_files' => $files['allowed'],
            'unallowed_files' => $files['unallowed'],
            'is_permanently' => $this->isPermanently()
        ));
    }

    public function getFiles()
    {
        $file_ids = wa()->getRequest()->request('file_id', array(), waRequest::TYPE_ARRAY_INT);
        $files = $this->getFileModel()->getById($file_ids);
        $allowed = $this->dropUnallowed($files);
        $allowed = $this->dropInSync($allowed);
        $unallowed = filesApp::assocDiff($files, $allowed);
        return array(
            'allowed' => $allowed,
            'unallowed' => $unallowed
        );
    }

    public function isPermanently()
    {
        return wa()->getRequest()->request('permanently');
    }

    public function dropUnallowed($files)
    {
        if (!$files) {
            return array();
        }
        if (!$this->isPermanently()) {
            return filesRights::inst()->dropUnallowedToMove($files);
        }

        return filesRights::inst()->dropHasNotAnyAccess($files);
    }

    public function dropInSync($files)
    {
        if (!$files) {
            return array();
        }
        $sync_map = $this->getFileModel()->inSync($files);
        foreach ($files as $id => $file) {
            if (!empty($sync_map[$id])) {
                unset($files[$id]);
            }
        }
        return $files;
    }

}