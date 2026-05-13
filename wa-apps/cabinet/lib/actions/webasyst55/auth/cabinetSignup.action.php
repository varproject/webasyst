<?php

class cabinetSignupAction extends waSignupAction
{
    public function execute()
    {
        $this->setLayout(new cabinetFrontendLayout());
        $this->setThemeTemplate('signup.html');
        parent::execute();
        wa()->getResponse()->setTitle(_ws('Регистрация в системе'));
    }


    /**
     * Этот метод вызывается после успешного создания нового контакта.
     * В нём можно, например, отправить приветственное письмо новому пользователю
     * или добавить его контакт в категорию, соответствующую вашему приложению.
     */
    protected function afterSignup(waContact $contact)
    {
        $contact->addToCategory($this->getAppId());
    }
}
