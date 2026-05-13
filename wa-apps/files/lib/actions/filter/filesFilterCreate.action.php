<?php

class filesFilterCreateAction extends filesController
{
    public function execute()
    {
        $filter = $this->getFilter();
        $filter_settings_html = filesApp::inst()->renderViewAction(new filesFilterSettingsAction(array(
            'acting_type' => $filter['acting_type']
        )));
        $this->assign(array(
            'filter' => $filter,
            'file_ids' => $this->getFileIds(),
            'filter_settings_html' => $filter_settings_html
        ));
    }

    public function getFileIds()
    {
        return filesApp::toIntArray(wa()->getRequest()->get('file_id'));
    }

    public function getFilter()
    {
        $filter = $this->getFilterModel()->getEmptyRow();
        $type = $this->getActingType();
        $types = $this->getFilterModel()->getAllActingTypes();
        if (in_array($type, $types)) {
            $filter['acting_type'] = $type;
        }
        return $filter;
    }

    public function getActingType()
    {
        return (string) wa()->getRequest()->get('acting_type');
    }
}
