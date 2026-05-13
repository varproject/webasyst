<?php

class apanelSettingsSaveController extends waJsonController
{
    protected $allowed = [
        'backend_title',
        'navigations',
        'layout',
        'ui',
        'features',
    ];

    public function execute()
    {
        $replace = waRequest::post('replace', 0, waRequest::TYPE_INT) ? true : false;
        $path    = waRequest::post('path', '', waRequest::TYPE_STRING_TRIM);

        if ($path) {
            $this->savePath($path, $replace);
        } else {
            $this->savePost($replace);
        }

        $this->response = [
            'status' => 'ok',
        ];
    }

    // Сохранить настройку по path + value.
    protected function savePath($path, $replace)
    {
        if (!$this->isAllowed($path)) {
            throw new waException('Настройка недоступна для изменения.');
        }

        apanelSettings::save($path, waRequest::post('value', null), $replace);
    }

    // Сохранить настройки из POST-массива.
    protected function savePost($replace)
    {
        $data = waRequest::post();

        unset($data['_csrf'], $data['path'], $data['value'], $data['replace']);

        foreach ($data as $path => $value) {
            if ($this->isAllowed($path)) {
                apanelSettings::save($path, $value, $replace);
            }
        }
    }

    // Проверить верхний ключ настройки.
    protected function isAllowed($path)
    {
        $parts = explode('.', trim($path, '.'));
        return in_array(reset($parts), $this->allowed);
    }
}
