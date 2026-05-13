<?php

class filesFilterSettingsAction extends filesController
{
    public function execute()
    {
        $filter = $this->getFilter();
        $this->assign(array(
            'filter' => $filter,
            'acting_type' => $this->getActingType(),
            'is_admin' => filesRights::inst()->hasFullAccess(),
            'icons' => filesApp::inst()->getConfig()->getFilterIcons(),
            'storages' => $this->getStorageModel()->getAvailableStorages(),
            'types' => filesApp::inst()->getConfig()->getFileTypes()
        ));
    }

    public function getActingType()
    {
        $type = wa()->getRequest()->param('acting_type');
        if (!$type) {
            $type = wa()->getRequest()->get('acting_type');
        }
        return (string) $type;
    }

    public function getFilter()
    {
        $filter_id = $this->getFilterId();
        $filter = $this->getFilterModel()->getFilter($filter_id);
        if (!$filter) {
            $filter = $this->getFilterModel()->getEmptyRow();
        }
        $type = $this->getActingType();
        $types = $this->getFilterModel()->getAllActingTypes();
        if ($type && in_array($type, $types)) {
            $filter['acting_type'] = $type;
        }
        return $filter;
    }

    public function getFilterId()
    {
        $filter_id = (int) wa()->getRequest()->param('id');
        if (!$filter_id) {
            $filter_id = (int) wa()->getRequest()->get('id');
        }
        return $filter_id;
    }
}