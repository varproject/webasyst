<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiAccountModel extends waModel
{
    protected $table = 'devapi_account';

    public function getAll($key = null, $normalize = false)
    {
        $query = <<<SQL
select id,name,token,new_transactions,is_remote from {$this->table} where contact_id=i:contact_id
SQL;
        return $this->query($query, ['contact_id' => wa()->getUser()->getId()])->fetchAll($key, $normalize);
    }

    /*public function getById($value)
    {
        $values = $value;
        $contact_id = wa()->getUser()->getId();
        if (!is_array($value)) $values = [$value];
        $query = <<<SQL
select * from {$this->table} where contact_id=$contact_id and id in (i:ids)
SQL;
        if ($data = $this->query($query, ['ids' => $values])->fetchAll('id')) {
            if (wa_is_int($value)) $data = ifset($data[$value], []);
        }
        return $data;
    }*/

    public function getLastTransactionDatetime($account_id)
    {
        $contact_id = $this->getContactIdByAccountId($account_id);
        $query = <<<SQL
select max(t.datetime) as last from devapi_transaction t
join {$this->table} a on a.id=t.account_id
where t.account_id=i:account_id and a.contact_id=i:contact_id
SQL;
        return $this->query($query, ['account_id' => $account_id, 'contact_id' => $contact_id])->fetchField('last');
    }

    public function deleteByAccount($account_id)
    {
        if (wa()->getEnv() !== 'cli' && !$this->getById($account_id)) {
            throw new waException('Не найден аккаунт для удаления');
        }
        $this->deleteById($account_id);
        (new devapiTransactionModel())->deleteByField('account_id', $account_id);
        return true;
    }

    public function getContactIdByAccountId($account_id) {
        if (wa()->getEnv() === 'cli') {
            $contact_id = $this->query('select contact_id from devapi_account where id=i:account_id', ['account_id' => $account_id])->fetchField('contact_id');
            if (!$contact_id) $contact_id = 0;
        } else $contact_id = wa()->getUser()->getId();
        return $contact_id;
    }
}