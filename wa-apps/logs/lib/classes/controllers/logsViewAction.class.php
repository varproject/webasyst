<?php

abstract class logsViewAction extends waViewAction
{
    public function __construct()
    {
        parent::__construct();
        $this->view->assign('premium', logsLicensing::check()->hasPremiumLicense());

        if ($this instanceof logsBackendNavigationAction) {
            if (!in_array(waRequest::get('action', ''), ['file', 'action'])) {
                wa()->getResponse()->setCookie('back_url', wa()->getConfig()->getCurrentUrl());
            }
        } else {
            $this->setLayout(new logsBackendLayout());
        }
    }
}
