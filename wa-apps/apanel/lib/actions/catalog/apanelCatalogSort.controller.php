<?php

class apanelCatalogSortController extends waViewController
{
    public function execute()
    {
        if (waRequest::getMethod() !== 'post') {
            throw new waException('Method Not Allowed', 405);
        }

        if (!$this->getUser()->getRights('apanel', 'catalog.sort')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $ids = array_map('intval', (array) waRequest::post('ids', []));
        $ids = array_values(array_unique(array_filter($ids)));

        if (!$ids) {
            throw new waException('Не переданы элементы для сортировки.');
        }

        try {
            $model = new apanelCatalogModel();
            $model->saveSort($ids);
            apanelRedirect::redirectBack();
        } catch (Exception $e) {
            waLog::log('Ошибка сортировки каталогов: ' . $e->getMessage(), 'apanel.log');
            throw new waException('Ошибка при сохранении сортировки каталогов.');
        }
    }
}
