<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiChartDataset extends devapiEntity
{
    protected string $label = '';
    protected array $data = [];

    public function getDataCSV()
    {
        return array_merge([$this->label], $this->data);
    }
    public function jsonSerialize(): array
    {
        foreach ($this->data as &$datum) {
            $datum = wa_is_int($datum) ? (int) $datum : (float) $datum;
        }
        unset($datum);
        return parent::jsonSerialize();
    }
}