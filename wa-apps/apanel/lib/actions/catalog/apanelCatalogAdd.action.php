<?php

class apanelCatalogAddAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->getRights('apanel', 'catalog.add')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $this->view->assign([
            'modal_title'      => 'Новый каталог',
            'post_action_url'  => '?module=catalogAdd',
            'save_button_name' => 'Создать',
            'close_button_url' => '/' . wa()->getRouting()->getCurrentUrl(),
        ]);
    }
}
