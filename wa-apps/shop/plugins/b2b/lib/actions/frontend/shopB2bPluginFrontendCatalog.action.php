<?php

class shopB2bPluginFrontendCatalogAction extends shopB2bPluginFrontendBaseAction
{
    public function execute()
    {
        $this->prepareB2b('catalog');
        if (empty($this->access['allowed'])) {
            return;
        }
        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/FrontendCatalog.html', 'shop'));
    }
}
