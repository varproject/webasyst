<?php

class filesSourceInfoAction extends filesController
{
    public function execute()
    {
        $source = $this->getSource();
        if ($source->isNull()) {
            $this->reportAboutError($this->getPageNotFoundError());
            return false;
        }

        $token = $source->getToken();
        if (empty($token)) {
            $action = new filesSourceCreateAction(array(
                'source' => $source
            ));
            echo $action->display();
            exit;
        }

        $this->assign(array(
            'source' => $source,
            'auth_failed' => $this->getAuthFailedMessage($source)
        ));
    }

    public function getSource()
    {
        $id = (int) $this->getRequest()->get('id');
        $source = filesSource::factory($id);
        if (!$source || $source->isApp()) {
            $this->reportAboutError(_w("Source not found"));
        }
        if (!filesRights::inst()->isAdmin() && $source->getOwner()->getId() != $this->contact_id) {
            $this->reportAboutError($this->getAccessDeniedError());
        }
        return $source;
    }

    public function getAuthFailedMessage(filesSource $source)
    {
        $key = 'source/' . $source->getId() . '/auth_failed';
        $message = $this->getStorage()->get($key);
        $this->getStorage()->del($key);
        return $message;
    }
}
