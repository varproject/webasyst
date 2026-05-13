<?php

class filesMoveAction extends filesController
{
    public function execute() {
        $files = $this->getFiles();
        $this->assign(array(
            'unallowed_files' => $files['unallowed'],
            'allowed_files' => $files['allowed'],
            'is_restore' => $this->isRestore(),
            'level' => filesRightConfig::RIGHT_LEVEL_ADD_FILES
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

    public function dropUnallowed($files)
    {
        if ($this->isRestore()) {
            return filesRights::inst()->dropHasNotAnyAccess($files);
        } else {
            return filesRights::inst()->dropUnallowedToMove($files);
        }
    }

    public function dropInSync($files)
    {
        $sync_map = $this->getFileModel()->inSync($files);
        foreach ($files as $id => $file) {
            if (!empty($sync_map[$id])) {
                unset($files[$id]);
            }
        }
        return $files;
    }

    public function isRestore()
    {
        return wa()->getRequest()->request('restore');
    }

}