<?php

class shopB2bPluginFrontendCartAction extends shopB2bPluginFrontendBaseAction
{
    public function execute()
    {
        $this->prepareB2b('cart');
        if (empty($this->access['allowed'])) {
            return;
        }
        $this->setTemplate(wa()->getAppPath('plugins/b2b/templates/actions/frontend/FrontendCart.html', 'shop'));
    }
}
