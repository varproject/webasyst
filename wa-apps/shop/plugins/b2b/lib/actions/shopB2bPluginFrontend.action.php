<?php

class shopB2bPluginFrontendAction extends shopB2bPluginFrontendBaseAction
{
    public function execute()
    {
        $this->prepareB2b('home');
        if (empty($this->access['allowed'])) {
            return;
        }
        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/Frontend.html', 'shop'));
    }
}
