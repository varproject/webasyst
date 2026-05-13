<?php

class logsBackendFilesAction extends logsViewAction
{
    public function execute()
    {
        $mode = waRequest::get('mode', '');

        if (in_array($mode, logsItems::getItemListModes(), true)) {
            if (
                !waRequest::get('from_count')
                && logsLicensing::check()->hasPremiumLicense()
                && $this->getUser()->getSettings($this->getAppId(), 'remember_sort_mode')
            ) {
                $this->getUser()->setSettings($this->getAppId(), 'sort_mode', $mode);
            }

            $this->view->assign('items', (new logsItems($mode))->get());
        } else {
            logsHelper::redirect();
        }
    }
}
