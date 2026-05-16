<?php

class shopB2bPluginFrontendSupportAction extends shopB2bPluginFrontendBaseAction
{
    public function execute()
    {
        $this->prepareB2b('support');
        if (empty($this->access['allowed'])) {
            return;
        }
        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/FrontendSupport.html', 'shop'));
    }
}
