<?php
class devapiBackendAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new devapiDefaultLayout());
    }
}
