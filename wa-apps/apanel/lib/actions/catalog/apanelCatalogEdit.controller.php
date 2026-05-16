<?php

class apanelCatalogEditController extends waController
{
    public function execute()
    {
        if (waRequest::method() !== 'post') {
            throw new waException('Method Not Allowed', 405);
        }

        if (!$this->getUser()->getRights('apanel', 'catalog.edit')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $edit_id = filter_var(waRequest::post('edit_id', 0), FILTER_VALIDATE_INT);
        if ($edit_id <= 0) {
            throw new waException('Не передан идентификатор записи "edit_id".');
        }

        $data = waRequest::post('catalog', [], waRequest::TYPE_ARRAY_TRIM);
        if (empty($data)) {
            throw new waException('Данные не переданы или некорректны.');
        }

        try {
            $model = new apanelCatalogModel();
            $update_id = $model->update($edit_id, $data);

            if ($update_id > 0) {

                if ($model->getCatalogById($update_id, true)) {
                    apanelRedirect::redirectBack(waConfig::get('apanel_module_path') . $update_id . '/');
                } else {
                    apanelRedirect::redirectBack('/' . wa()->getRouting()->getCurrentUrl());
                }
            } else {
                throw new waException('Не удалось обновить запись. Повторите попытку.');
            }
        } catch (Exception $e) {
            waLog::log("Ошибка обновления каталога ID {$edit_id}: " . $e->getMessage(), 'apanel.log');
            throw new waException('Не удалось обновить каталог: ' . $e->getMessage());
        }
    }
}
