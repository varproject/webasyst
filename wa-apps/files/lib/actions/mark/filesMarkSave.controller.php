<?php

class filesMarkSaveController extends filesController
{
    public function execute()
    {
        $file_ids = $this->getFileIds();
        $mark = $this->getMark();
        $this->getFileModel()->updateById($file_ids, array(
            'mark' => $mark
        ));
        $this->assign(array(
            'file_id' => $file_ids,
            'mark' => $mark
        ));
    }

    public function getMark()
    {
        $mark = wa()->getRequest()->request('mark');
        $marks = $this->getFileModel()->getAvailableMarks();
        if (!in_array($mark, $marks)) {
            $mark = null;
        }
        return $mark;
    }

    public function getFileIds()
    {
        $file_ids = wa()->getRequest()->request('file_id', array(), waRequest::TYPE_ARRAY_INT);
        $files = $this->getFileModel()->getById($file_ids);
        $allowed = $this->dropUnallowed($files);
        return array_keys($allowed);
    }

    public function dropUnallowed($files)
    {
        // the same rights as for the move
        return filesRights::inst()->dropUnallowedToMove($files);
    }

}