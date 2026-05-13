<?php

class shopLkPluginFrontendMyProfileController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new shopLkPluginFrontendLayout());

        $this->executeAction(new shopLkPluginFrontendMyProfileAction());
    }
}
