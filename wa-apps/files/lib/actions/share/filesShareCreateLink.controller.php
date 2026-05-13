<?php

class filesShareCreateLinkController extends filesController
{
    public function execute()
    {
        $file = $this->getFileForShareModule();

        $file['hash'] = $this->getFileModel()->generateHash();
        $this->getFileModel()->setHash($file['id'], $file['hash']);
        $link = $this->getFileModel()->getPrivateLink($file, false);

        $this->assign(array(
            'link' => $link
        ));
    }
}