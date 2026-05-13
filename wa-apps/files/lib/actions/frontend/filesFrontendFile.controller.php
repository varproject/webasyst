<?php

class filesFrontendFileController extends filesController
{
    public function execute()
    {
        $file = $this->getFile();
        $this->sendFile($file);
    }

    public function sendFile($file)
    {
        wa()->getResponse()->addHeader('Expires', 'tomorrow');
        wa()->getResponse()->addHeader('Cache-Control', 'private, max-age=' . (86400*30));    // one month
        wa()->getResponse()->addHeader('Content-Type', 'application/octet-stream');
        
        $source = filesSource::factory($file['source_id']);
        $source->download($file);
        exit;
    }

    public function getFile()
    {
        $hash = wa()->getRequest()->param('hash', '', waRequest::TYPE_STRING_TRIM);
        if (!$hash) {
            $this->reportAboutError($this->getPageNotFoundError());
        }

        $file_id = substr($hash, 16, -16);
        $hash = substr($hash, 0, 16) . substr($hash, -16);
        $file = $this->getFileModel()->getFile($file_id);
        if (!$file) {
            $this->reportAboutError($this->getPageNotFoundError());
        }
        if ($file['in_copy_process']) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }

        if (!$file['hash'] || $file['hash'] !== $hash || $file['storage_id'] <= 0) {
            $this->reportAboutError($this->getPageNotFoundError());
        }

        return $file;

    }

}