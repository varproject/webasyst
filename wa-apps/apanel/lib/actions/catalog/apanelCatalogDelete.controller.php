<?php

class apanelCatalogDeleteController extends waController
{
    public function execute()
    {
        if (waRequest::method() !== 'post') {
            throw new waException('Method Not Allowed', 405);
        }

        if (!$this->getUser()->getRights('apanel', 'catalog.delete')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $delete_id = filter_var(waRequest::post('delete_id', 0), FILTER_VALIDATE_INT);
        if ($delete_id <= 0) {
            throw new waException('Не передан идентификатор записи "delete_id".');
        }

        $data = waRequest::post('delete', [], waRequest::TYPE_ARRAY);
        if (empty($data['confirm'])) {
            throw new waException('Необходимо подтвердить удаление чекбоксом.');
        }

        $current_id = (int)($data['current_id'] ?? 0);

        try {
            $model = new apanelCatalogModel();
            $model->delete($delete_id);

            $first_id = $model->getFirstEnabledCatalogId();

            if ($current_id === $delete_id && $first_id > 0) {
                $redirect_url = waConfig::get('apanel_module_path') . $first_id . '/';
            } elseif ($first_id <= 0) {
                $redirect_url = waConfig::get('apanel_module_path');
            } else {
                $redirect_url = '/' . wa()->getRouting()->getCurrentUrl();
            }

            apanelRedirect::redirectBack($redirect_url);
        } catch (Exception $e) {
            waLog::log("Ошибка при удалении записи ID {$delete_id}: " . $e->getMessage(), 'apanel.log');
            throw new waException('Не удалось удалить каталог: ' . $e->getMessage());
        }
    }
}
