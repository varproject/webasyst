<?php

class logsBackendDirectoryAction extends logsViewAction
{
    public function execute()
    {
        $path = waRequest::get('path', '');

        if (strlen($path)) {
            $this->getResponse()->setTitle($path . '/');
        } else {
            if (
                logsLicensing::check()->hasPremiumLicense()
                && $this->getUser()->getSettings($this->getAppId(), 'remember_sort_mode')
            ) {
                $this->getUser()->setSettings($this->getAppId(), 'sort_mode', waRequest::get('mode'));
            }
        }

        $item_list = new logsItems(logsItems::MODE_DIRECTORY);
        $items = $item_list->get(array('path' => $path));
        $this->view->assign('items', $items);
    }
}
