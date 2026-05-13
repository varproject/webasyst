<?php

class filesSendAction extends filesController
{

    public function execute()
    {
        $file_ids = wa()->getRequest()->request('file_id', array(), waRequest::TYPE_ARRAY_INT);
        $files = $this->getFileModel()->getById($file_ids);
        $size = 0;
        foreach ($files as $f) {
            $size += $f['size'];
        }

        $this->assign(array(
            'files' => $files,
            'size_str' => filesApp::formatFileSize($size),
            'allowed' => $size < wa()->getConfig()->getOption('messages_attach_max_size')
        ));
    }

}