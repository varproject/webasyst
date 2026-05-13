<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiSettingsModel extends waModel
{
    protected $table = 'devapi_settings';
    protected $id = ['contact_id', 'name'];

    public function getSettings(int $contact_id): array
    {
        $query = <<<SQL
select name,value from {$this->table} where contact_id=i:contact_id
SQL;
        $data = $this->query($query, ['contact_id' => $contact_id])->fetchAll('name', true);
        foreach ($data as $name => $value) {
            try {
                $value = waUtils::jsonDecode($value, true);
            } catch (waException $e) {
            }
            $data[$name] = $value;
        }
        return $data;
    }

    public function saveSettings(array $data)
    {
        $contact_id = wa()->getUser()->getId();
        $settings = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) $value = json_encode($value);
            $settings[] = [
                'contact_id' => $contact_id,
                'name' => $key,
                'value' => $value
            ];
        }
        return $this->multipleInsert($settings, ['value=VALUES(value)']);
    }
}