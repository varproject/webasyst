<?php

class filesUploadChangePathAction extends filesController
{
    public function execute()
    {
        $this->assign(array(
            'level' => filesRightConfig::RIGHT_LEVEL_ADD_FILES
        ));
    }
}