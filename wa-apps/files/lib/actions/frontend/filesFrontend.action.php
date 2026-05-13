<?php

class filesFrontendAction extends filesController
{
    public function execute()
    {
        /**
         * @event files_frontend_request
         */
        wa()->event('files_frontend_request');
        $this->setThemeTemplate('home.html');
    }
}
