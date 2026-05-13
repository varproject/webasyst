<?php

class filesCopyTaskController extends filesController
{
    public function execute()
    {
        $this->getStorage()->close();
        $copytask = new filesCopytask($this->getConfig()->getTasksPerRequest());
        $copytask->execute();
    }
}
