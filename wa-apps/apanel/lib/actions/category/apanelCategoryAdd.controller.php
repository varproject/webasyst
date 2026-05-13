<?php

class apanelCategoryAddController extends waController
{
    public function execute()
    {
        if (waRequest::method() !== 'post') {
            throw new waException('Method Not Allowed', 405);
        }

        if (!$this->getUser()->getRights('apanel', 'category.add')) {
            throw new waRightsException('У вас недостаточно прав для этой операции.');
        }

        $data = waRequest::post('category', [], waRequest::TYPE_ARRAY_TRIM);
        if (empty($data)) {
            throw new waException('Данные не переданы или некорректны.');
        }

        try {
            $model = new apanelCatalogModel();
            $insert_id  = $model->add($data);

            if ($insert_id > 0) {
                apanelRedirect::redirectBack(waConfig::get('apanel_module_path') . $insert_id . '/');
            } else {
                throw new waException('Не удалось создать запись. Проверьте введённые данные.');
            }
        } catch (Exception $e) {
            waLog::log("Ошибка при создании каталога: " . $e->getMessage(), 'apanel.log');
            throw new waException('Не удалось создать каталог: ' . $e->getMessage());
        }
    }
}
