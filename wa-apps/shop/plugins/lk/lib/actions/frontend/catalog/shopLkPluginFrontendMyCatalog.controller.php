<?php

class shopLkPluginFrontendMyCatalogController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new shopLkPluginFrontendLayout());

        $this->executeAction(new shopLkPluginFrontendMyCatalogToolbarAction(), 'main_toolbar_left_items');
        $this->executeAction(new shopLkPluginFrontendMyCatalogTableAction(), 'main_body_table_items');
    }
}
