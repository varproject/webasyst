<?php

class filesStorageCreateAction extends filesController
{
    public function execute() {

        if (!filesRights::inst()->canCreateStorage()) {
            $this->reportAboutError($this->getAccessDeniedError());
        }

        $this->view->assign(array(
            'icons' => filesApp::inst()->getConfig()->getStorageIcons(),
            'all_groups' => filesRights::inst()->getAllGroups(),
            'levels' => filesApp::inst()->getRightConfig()->getRightLevels(),
            'storage' => $this->getEmptyStorage(),
            'admin_map' => array_fill_keys(array_keys(filesRights::inst()->getAllAdmins()), true),
            'can_delete' => false
        ));
    }

    public function getEmptyStorage()
    {
        $row = $this->getStorageModel()->getEmptyRow();
        $row['is_persistent'] = false;
        return $row;
    }
}