<?php

class filesTagAction extends filesController
{
    public function execute() {
        $this->assign(array(
            'file_ids' => wa()->getRequest()->get('file_id'),
            'popular_tags' => $this->getTagModel()->getPopularTags(10, true)
        ));
    }
}