<?php

class logsBackendLayout extends waLayout
{
    public function execute()
    {
        $this->executeAction('navigation', new logsBackendNavigationAction());
        $this->view->assign('ajax', waRequest::isXMLHttpRequest());
    }
}
