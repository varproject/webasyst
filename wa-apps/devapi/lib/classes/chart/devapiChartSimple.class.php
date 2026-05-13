<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiChartSimple extends devapiEntity
{
    protected string $type = 'pie';
    protected array $labels = [];
    /** @var devapiChartDataset[] */
    protected array $datasets = [];

    public function __construct($data)
    {
        if (isset($data['type'])) $this->type = $data['type'];
        foreach (ifset($data['datasets'], []) as $dataset) {
            $this->datasets[] = new devapiChartDataset($dataset);
        }
        if (isset($data['labels'])) $this->labels = $data['labels'];
    }

    public function getDataCSV()
    {
        $data = [];
        foreach ($this->datasets as $dataset) {
            $data[] = $dataset->getDataCSV();
        }
        return $data;
    }
    public function getLabels()
    {
        return $this->labels;
    }
    public function setChartType($type)
    {
        $this->type = $type;
    }
}