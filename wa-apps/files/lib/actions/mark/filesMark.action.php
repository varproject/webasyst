<?php

class filesMarkAction extends filesController
{
    public function execute()
    {
        $files = $this->getFiles();
        $this->assign(array(
            'marks' => $this->getMarks(),
            'files' => $files,
            'unallowed_files' => $files['unallowed'],
            'allowed_files' => $files['allowed'],
        ));
    }

    public function getMarks()
    {
        $marks = $this->getFileModel()->getAvailableMarks();
        array_unshift($marks, '');
        return $marks;
    }

    public function getFiles()
    {
        $file_ids = wa()->getRequest()->request('file_id', array(), waRequest::TYPE_ARRAY_INT);
        $files = $this->getFileModel()->getById($file_ids);
        $allowed = $this->dropUnallowed($files);
        $unallowed = filesApp::assocDiff($files, $allowed);

        return array(
            'allowed' => $allowed,
            'unallowed' => $unallowed
        );
    }

    public function dropUnallowed($files)
    {
        // the same rights as for the move
        return filesRights::inst()->dropUnallowedToMove($files);
    }
}
