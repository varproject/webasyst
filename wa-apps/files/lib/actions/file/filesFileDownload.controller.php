<?php

class filesFileDownloadController extends filesController
{
    public function execute()
    {
        $file = $this->getFile();
        if ($file['storage_id']) {
            $this->sendFile($file);
            return;
        } else {
            //TODO: hook
        }
    }

    public function sendFile($file)
    {
        if (wa()->getRequest()->server('HTTP_IF_MODIFIED_SINCE')) {
            $modified_since = strtotime(wa()->getRequest()->server('HTTP_IF_MODIFIED_SINCE'));
            $update_time = strtotime($file['update_datetime']);
            if ($update_time == $modified_since) {
                wa()->getResponse()->setStatus(304);
                wa()->getResponse()->sendHeaders();
                exit;
            }
        }

        wa()->getResponse()->addHeader('Cache-Control', 'private, max-age=0');
        wa()->getResponse()->addHeader('Content-Type', 'application/octet-stream');
        wa()->getResponse()->addHeader('Last-Modified', strtotime($file['update_datetime']));

        $source = filesSource::factory($file['source_id']);
        $source->download($file);

        exit;
    }

    public function getFile()
    {
        $id = wa()->getRequest()->get('id', null, waRequest::TYPE_INT);
        $file = $this->getFileModel()->getFile($id);
        if (!$file) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if ($file['in_copy_process']) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        $storage = $this->getStorageModel()->getStorage($file['storage_id']);
        if (!$storage) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if (!filesRights::inst()->canReadFile($file['id'])) {
            $this->reportAboutError($this->getAccessDeniedError());
            return false;
        }
        return $file;
    }

}
