<?php

class filesCommentSaveController extends filesController
{
    public function execute() {
        $comment = $this->getComment();
        $errors = $this->validate($comment);
        if (!empty($errors)) {
            $this->errors = $errors;
            return;
        }
        $file_id = $this->getFileId();

        $comment_id = $this->getFileCommentsModel()->add($file_id, $comment);
        if ($comment_id > 0) {
            $this->logAction('comment_add', $comment_id);
        }
        $this->assign(array(
            'comment_id' => $comment_id
        ));
    }

    public function validate($comment)
    {
        $errors = array();
        if (strlen($comment['content']) <= 0) {
            $errors[] = array(
                'name' => 'content',
                'msg' => _w('This field is required')
            );
        }
        return $errors;
    }

    public function getFileId()
    {
        $id = wa()->getRequest()->get('file_id', null, waRequest::TYPE_INT);
        $file = $this->getFileModel()->getFile($id);
        if (!$file) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        $storage = $this->getStorageModel()->getStorage($file['storage_id']);
        if (!$storage) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
            return false;
        }
        if (!filesRights::inst()->canCommentFile($file['id'])) {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
            return false;
        }
        return $file['id'];
    }
    
    public function getComment()
    {
        return array(
            'content' => wa()->getRequest()->post('content', '', waRequest::TYPE_STRING_TRIM)
        );
    }

}
