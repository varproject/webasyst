<?php

class filesFilterFilesAction extends filesFilesAction
{
    public function execute()
    {
        parent::execute();
        $filter = $this->getFilter();
        $this->getUser()->setSettings($this->app_id, 'filters/' . $filter['id'], $this->collection->count());
    }

    public function filesExecute() {
        $filter = $this->getFilter();

        $is_filter_tunable = $this->isFilterTunable($filter);
        $filter_settings_html = '';
        if ($is_filter_tunable) {
            $filter_settings_html = filesApp::inst()->renderViewAction(new filesFilterSettingsAction(array(
                'id' => $filter['id']
            )));
        }

        return array(
            'filter' => $filter,
            'hash' => "filter/{$filter['id']}",
            'url' => $this->getUrl() . "&id={$filter['id']}",
            'is_filter_tunable' => $is_filter_tunable,
            'filter_settings_html' => $filter_settings_html
        );
    }

    public function getFilter()
    {
        $id = (int) wa()->getRequest()->get('id');
        $filter = $this->getFilterModel()->getById($id);
        if (!$this->isOwnFilter($filter) && !$this->isSharedFilter($filter)) {
            $error = $this->getAccessDeniedError();
            $this->reportAboutError($error);
        }
        return $filter;
    }

    public function isOwnFilter($filter)
    {
        return $filter['access_type'] = filesFilterModel::ACCESS_TYPE_PERSONAL &&
                $filter['contact_id'] == $this->contact_id;
    }

    public function isSharedFilter($filter)
    {
        return $filter['access_type'] === filesFilterModel::ACCESS_TYPE_SHARED;
    }

    public function isFilterTunable($filter)
    {
        return filesRights::inst()->hasFullAccess() || $filter['contact_id'] == $this->contact_id;
    }

}
