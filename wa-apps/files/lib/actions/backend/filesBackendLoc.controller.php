<?php

class filesBackendLocController extends waViewController
{
    public function execute()
    {
        $this->executeAction(new filesBackendLocAction());
    }

    public function preExecute()
    {
        // do not save this page as last visited
    }
}
