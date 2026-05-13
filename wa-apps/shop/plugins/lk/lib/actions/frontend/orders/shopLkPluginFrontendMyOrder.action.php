<?php

class shopLkPluginFrontendMyOrderAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new shopLkPluginFrontendLayout());

        dd(waRequest::param());
    }
}
