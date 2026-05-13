<?php

class devapiBackendDownloadCsvReportController extends waController
{
    protected $file_name;
    protected $file_path;
    protected string $mode = 'vieww';


    public function run($params = null)
    {
        $this->file_path = wa()->getTempPath();
        $this->file_name = waRequest::get('file', '');
        $this->display();
    }

    public function display($content_type = 'text/csv')
    {
        wa()->getResponse()->addHeader('Content-type', $content_type);
        wa()->getResponse()->addHeader('Content-Disposition', 'inline; filename="' . $this->file_name . '"');
        wa()->getResponse()->addHeader('filename', $this->file_name);
        wa()->getResponse()->sendHeaders();

        switch ($this->mode) {
            case 'view':
                echo readfile($this->file_path);
                break;
            default:
                waFiles::readFile($this->file_path . '/' . $this->file_name);
        }
    }
}