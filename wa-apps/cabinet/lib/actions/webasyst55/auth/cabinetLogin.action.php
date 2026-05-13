<?php

class cabinetLoginAction extends waLoginAction
{
    public function execute()
    {
        $this->setLayout(new cabinetFrontendLayout());
        $this->setThemeTemplate('login.html');
        parent::execute();
        wa()->getResponse()->setTitle(_ws('Авторизация'));
    }
}
