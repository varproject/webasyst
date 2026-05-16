<?php

class apanelCategoryAddAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->getRights('apanel', 'category.add')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        header('HX-Push-Url: ' . '?add_category');

        $this->view->assign([
            'modal_title'      => 'Новая категория',
            'moda_size'        => 'modal-lg',
            'post_action_url'  => '?module=categoryAdd',
            'save_button_name' => 'Создать',
            'close_button_url' => '/' . wa()->getRouting()->getCurrentUrl(),
        ]);
    }
}
