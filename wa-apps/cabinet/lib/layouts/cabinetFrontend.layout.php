<?php

class cabinetFrontendLayout extends waLayout
{
    public function execute()
    {
        if (
            !wa()->getUser()->isAuth()
            && !in_array(waRequest::param('module'), ['login', 'signup', 'forgotpassword'])
        ) {
            $this->redirect(wa()->getRouteUrl('cabinet/login/'));
        }



        $this->view->assign([
            'front_url'  => rtrim(wa()->getRouteUrl('cabinet/frontend'), '/'),
            'front_url_absolute' => rtrim(wa()->getRouteUrl('cabinet/frontend', true), '/'),
        ]);


        $this->setThemeTemplate('index.html');
    }
}
