<?php

class filesFilterDeleteController extends filesController
{
    public function execute()
    {
        $filter = $this->getFilter();
        $this->getFilterModel()->deleteById($filter['id']);
    }

    public function getFilterId()
    {
        return (int) wa()->getRequest()->post('id');
    }

    public function getFilter()
    {
        $filter_id = $this->getFilterId();
        $filter = $this->getFilterModel()->getById($filter_id);
        if (!$filter) {
            filesApp::inst()->reportAboutError($this->getPageNotFoundError());
        }
        $is_admin = filesRights::inst()->hasFullAccess();
        $is_shared_and_admin = $filter['access_type'] === filesFilterModel::ACCESS_TYPE_SHARED && $is_admin;
        $is_own_filter = $filter['access_type'] == filesFilterModel::ACCESS_TYPE_PERSONAL && $filter['contact_id'] == $this->contact_id;
        if (!$is_shared_and_admin && !$is_own_filter)
        {
            filesApp::inst()->reportAboutError($this->getAccessDeniedError());
        }
        return $filter;
    }
}
