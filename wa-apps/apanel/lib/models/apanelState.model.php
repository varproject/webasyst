<?php

class apanelStateModel extends waModel
{
    protected $table = 'apanel_state';


    /**
     * Получает список ID для ключа состояния.
     * Если состояние 'all', возвращает специальный маркер.
     */
    public function getState($key)
    {
        $user_id = wa()->getUser()->getId();
        $row = $this->getByField(['user_id' => $user_id, 'key' => $key]);

        if (!$row) {
            return [];
        }

        $value = json_decode($row['value'], true);
        return $value === 'all' ? 'all' : (array)$value;
    }

    /**
     * Сохраняет состояние.
     * $value может быть массивом ID или строкой 'all'.
     */
    public function setState($key, $value)
    {
        $user_id = wa()->getUser()->getId();
        $data = [
            'user_id' => $user_id,
            'key'        => $key,
            'value'      => json_encode($value),
            'update_datetime' => date('Y-m-d H:i:s'),
        ];

        // Используем INSERT ... ON DUPLICATE KEY UPDATE
        $this->insert($data, 1);
    }
}
