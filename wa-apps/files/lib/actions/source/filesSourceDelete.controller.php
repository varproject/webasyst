<?php

class filesSourceDeleteController extends filesController
{
    public function execute()
    {
        $source = $this->getSource();
        $source->delete();
    }

    public function getSource()
    {
        $id = (int) $this->getRequest()->post('id');
        $source = filesSource::factory($id);
        if (!$source) {
            $this->reportAboutError(_w("Source not found"));
        }
        if (!filesRights::inst()->isAdmin() && $source->getOwner()->getId() != $this->contact_id) {
           $this->reportAboutError($this->getAccessDeniedError());
        }
        return $source;
   }

}