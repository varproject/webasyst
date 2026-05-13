<?php

class logsFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->getResponse()->setCookie('force_set_wa_backend_ui_version', '2.0', time() + 1, '/');
        $this->executeAction('navigation', new logsFrontendNavigationAction());
    }
}
