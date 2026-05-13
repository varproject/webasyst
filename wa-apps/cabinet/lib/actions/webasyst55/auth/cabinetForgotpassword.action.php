<?php

class cabinetForgotpasswordAction extends waForgotPasswordAction
{
    public function execute()
    {
        $this->setLayout(new cabinetFrontendLayout());
        $this->setThemeTemplate('forgotpassword.html');
        parent::execute();
        wa()->getResponse()->setTitle(_ws('Восстановление пароля'));
    }
}
