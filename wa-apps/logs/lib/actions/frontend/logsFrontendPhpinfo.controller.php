<?php

class logsFrontendPhpinfoController extends logsFrontendPublishedItemController
{
    public function __construct()
    {
        $this->hash = waRequest::param('hash');
        $this->path = '//phpinfo//';
        $this->action = 'phpinfo';
        $this->not_published_warning = _w('The PHP configuration page is not published.');
    }

    protected function check()
    {
        parent::check();

        if (!function_exists('phpinfo')) {
            throw new Exception(_w('This server does not allow viewing the PHP configuration.'));
        }
    }

    protected function getData()
    {
        phpinfo();
    }
}
