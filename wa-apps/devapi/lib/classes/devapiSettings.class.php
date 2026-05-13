<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiSettings extends devapiEntity
{
    public int $refresh_rate = 30;
    public string $list_type = 'month';
    public int $limit = 20;
    public int $counter = 1;
    public array $announcement = ['enabled' => 0, 'type' => 'me', 'values' => []];
    public array $telegram;
    public array $max;

    private int $contact_id;
    private devapiSettingsModel $model;

    public function __construct($data = [], $contact_id = null)
    {
        $this->model = new devapiSettingsModel();
        $this->contact_id = $contact_id ?: wa()->getUser()->getId();
        $data = array_merge($this->model->getSettings($this->contact_id), $data);
        foreach ($data as $prop => $value) {
            $this->$prop = $value;
        }
        foreach (['enabled' => 0, 'type' => 'me', 'values' => [], 'token' => ''] as $field => $value) {
            foreach (['announcement', 'telegram', 'max'] as $prop) {
                if (!isset($this->{$prop}) || !$this->{$prop} || !ifset($this->{$prop}[$field])) {
                    $this->{$prop}[$field] = $value;
                }
            }
        }
    }

    public function save()
    {
        return $this->model->saveSettings($this->jsonSerialize());
    }
}