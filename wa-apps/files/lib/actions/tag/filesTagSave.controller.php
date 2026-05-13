<?php

class filesTagSaveController extends filesController
{
    public function execute()
    {
        $file_ids = $this->getFileIds();
        if (!$file_ids) {
            return;
        }
        $tags = $this->getTags();
        if ($this->isAdd()) {
            $this->getTagModel()->add($file_ids, $tags);
        } else if ($tags || $this->isDelete()) {
            $this->getTagModel()->assign($file_ids[0], $tags);
        }
    }

    public function getTags()
    {
        $tags_str = wa()->getRequest()->post('tags', '', waRequest::TYPE_STRING_TRIM);
        if (!$tags_str) {
            return array();
        }
        $tags = array();
        foreach (explode(',', $tags_str) as $tag) {
            $tag = trim($tag);
            if (!$tag) {
                continue;
            }
            $tags[] = $tag;
        }
        return $tags;
    }

    public function getFileIds()
    {
        $file_id = wa()->getRequest()->request('file_id');
        $file_id = filesApp::toIntArray($file_id);

        $files = $this->getFileModel()->getById($file_id);
        $files = filesRights::inst()->hasFullAccessToFile($files);

        foreach ($files as $fid => $file) {
            if (!$file[':access']) {
                unset($files[$fid]);
            }
        }
        return array_keys($files);
    }

    public function isDelete()
    {
        return wa()->getRequest()->get('delete');
    }

    public function isAdd()
    {
        return wa()->getRequest()->get('add');
    }

}