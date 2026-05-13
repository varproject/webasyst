<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiAccounts extends devapiEntity
{
    protected array $accounts = [];

    private devapiAccountModel $model;

    public function __construct($with_products = false)
    {
        $this->model = new devapiAccountModel();
        foreach ($this->model->getAll() as $datum) {
            try {
                $acc = new devapiAccount($datum['id']);
                if ($with_products) $acc->setProducts();
                $this->accounts[] = $acc;
            } catch (Exception $e) {
                devapiHelper::setLog($e->getMessage(), 'error');
            }
        }
    }

    public function getAccounts()
    {
        return $this->accounts;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        return ifset($data['accounts'], []);
    }
}