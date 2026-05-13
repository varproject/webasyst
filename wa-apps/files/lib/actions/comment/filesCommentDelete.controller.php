<?php

class filesCommentDeleteController extends filesController
{

    public function execute()
    {
        $comment_id = waRequest::get('id', 0, waRequest::TYPE_INT);
        if (!$comment_id) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            $this->errors = 'Empty comment ID';
            return;
        }
        $comment = $this->getFileCommentsModel()->getById($comment_id);
        if (!$comment) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            $this->errors = 'Empty comment';
            return;
        }
        if ($comment['contact_id'] != $this->contact_id && !filesRights::inst()->hasFullAccess()) {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
            $this->errors = 'Access denied';
            return;
        }
        $this->getFileCommentsModel()->deleteById($comment_id);
    }

}