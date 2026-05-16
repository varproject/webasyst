<?php

class apanelCatalogEditAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->getRights('apanel', 'catalog.edit')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $edit_id = filter_var(waRequest::get('edit_catalog', 0), FILTER_VALIDATE_INT);
        $catalog = ($edit_id > 0) ? (new apanelCatalogModel())->getById($edit_id) : null;

        if (empty($catalog)) {
            apanelRedirect::redirectBack(true);
        }

        $this->view->assign([
            'catalog'          => $catalog,
            'modal_title'      => 'Редактирование каталога',
            'post_action_url'  => '?module=catalogEdit',
            'save_button_name' => 'Сохранить',
            'close_button_url' => '/' . wa()->getRouting()->getCurrentUrl(),
        ]);
    }
}
