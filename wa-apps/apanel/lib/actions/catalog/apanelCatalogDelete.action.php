<?php

class apanelCatalogDeleteAction extends waViewAction
{
    public function execute()
    {
        if (!$this->getUser()->getRights('apanel', 'catalog.delete')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $delete_id = filter_var(waRequest::get('delete_catalog', 0), FILTER_VALIDATE_INT);
        $catalog   = ($delete_id > 0) ? (new apanelCatalogModel())->getById($delete_id) : null;

        if (empty($catalog)) {
            apanelRedirect::redirectBack(true);
        }

        $this->view->assign([
            'catalog'          => $catalog,
            'current_id'       => apanelUrlSegment::get(3),
            'modal_title'      => "Удаление каталога:",
            'post_action_url'  => '?module=catalogDelete',
            'save_button_name' => 'Подтвердить удаление',
            'close_button_url' => '/' . wa()->getRouting()->getCurrentUrl(),
        ]);
    }
}
