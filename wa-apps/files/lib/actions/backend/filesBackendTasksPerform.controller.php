<?php

class filesBackendTasksPerformController extends filesController
{
    public function execute()
    {
        $this->getStorage()->close();

        filesTasksPerformer::perform();
    }
}
