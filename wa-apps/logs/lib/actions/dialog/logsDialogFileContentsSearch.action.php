<?php

class logsDialogFileContentsSearchAction extends waViewAction
{
    public function execute()
    {
        try {
            $path = waRequest::get('path', '');
            $item = logsHelper::getPathParts($path);

            $this->view->assign('path', $path);
            $this->view->assign('item', $item);
            $this->view->assign('file_search_cancel_url', waRequest::get('search_cancel_url'));
        } catch (Throwable $exception) {
            $this->view->assign('error', $exception->getMessage());
        }
    }
}
