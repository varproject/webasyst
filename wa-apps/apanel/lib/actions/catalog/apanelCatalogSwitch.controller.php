<?php

class apanelCatalogSwitchController extends waViewController
{
    public function execute()
    {
        if (waRequest::getMethod() !== 'post') {
            throw new waException('Method Not Allowed', 405);
        }

        if (!$this->getUser()->getRights('apanel', 'catalog.toggle')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $id = waRequest::post('catalog_id', 0, waRequest::TYPE_INT);
        $is_enabled = waRequest::post('is_enabled', 0, waRequest::TYPE_INT);

        if ($id <= 0) {
            throw new waException('Не передан идентификатор записи "catalog_id".');
        }

        if ($is_enabled !== 0 && $is_enabled !== 1) {
            throw new waException('Ошибка передачи флага "is_enabled".');
        }

        try {
            $model = new apanelCatalogModel();
            $model->setEnabled($id, $is_enabled);

            apanelRedirect::redirectBack();
        } catch (Exception $e) {
            waLog::log("Ошибка ID {$id}: " . $e->getMessage(), 'apanel.log');
            throw new waException('Ошибка при обновлении статуса.');
        }
    }
}
