<?php

class cabinetFrontendCategoryAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        // Подключаем layout (как в shop)
        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new cabinetFrontendLayout());
        }
    }

    public function execute()
    {
        $theme = $this->getTheme();
        $current_template = $this->getTemplate();
        $theme_dir_url = $this->getThemeUrl();

        $this->getResponse()->setTitle(_ws('Каталог'));

        $this->setThemeTemplate('category.html');
    }
}
