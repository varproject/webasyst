<?php

class filesFavoriteFilesAction extends filesFilesAction
{
    public function filesExecute() {
        return array(
            'hash' => "favorite",
            'url' => $this->getUrl()
        );
    }
}