<?php

class logsDialogPremiumAction extends waViewAction
{
    public function execute()
    {
        $locale = strtolower(substr($this->getUser()->getLocale(), 0, 2)) == 'ru' ? 'ru' : 'en';
        $this->view->assign('locale', $locale);
    }
}
