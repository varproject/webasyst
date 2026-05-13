<?php

class cabinetFrontendAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new cabinetFrontendLayout());
        }
    }

    public function execute()
    {
        wa()->getResponse()->setTitle('Главная — Личный кабинет');
        $this->setThemeTemplate('home.html');
    }
}
