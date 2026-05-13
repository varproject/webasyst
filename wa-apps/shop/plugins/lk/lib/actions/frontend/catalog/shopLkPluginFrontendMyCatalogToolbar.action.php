<?php

class shopLkPluginFrontendMyCatalogToolbarAction extends waViewAction
{
    public function execute()
    {
        $this->layout->assign('main_toolbar_left_title_enabled', false);
    }
}
