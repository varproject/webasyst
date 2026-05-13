<?php

class filesBackendAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new filesDefaultLayout());
    }
}
