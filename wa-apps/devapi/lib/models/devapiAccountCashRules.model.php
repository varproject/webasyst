<?php

class devapiAccountCashRulesModel extends waModel
{
    protected $table = 'devapi_account_cash_rules';

    public function getByAccountId($account_id)
    {
        $rules = $this->where('account_id=i:acc_id', ['acc_id' => $account_id])->order('sort')->fetchAll();
        foreach ($rules as &$data) {
            $data['data'] = json_decode($data['data'], true);
        }
        unset($data);
        return $rules;
    }

    public function getIdsByAccountId(int $account_id)
    {
        return $this->select('id')->where('account_id=i:acc_id', ['acc_id' => $account_id])->order('sort')->fetchAll(null, true);
    }

    public function getRuleById($rule_id)
    {
        if ($data = $this->getById($rule_id)) {
            $data['data'] = json_decode($data['data'], true);
        }
        return $data;
    }

    public function getNextSort(int $account_id)
    {
        $query = <<<SQL
select max(sort) as srt from {$this->table} where account_id=i:account_id
SQL;
        $sort = (int) $this->query($query, ['account_id' => $account_id])->fetchField('srt') + 1;
        return $sort;
    }
}