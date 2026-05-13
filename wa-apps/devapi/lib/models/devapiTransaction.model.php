<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiTransactionModel extends waModel
{
    protected $table = 'devapi_transaction';

    public function getMonthBalance(int $account_id)
    {
        $from = (new DateTime())->modify('first day of this month')->format('Y-m-d');
        $to = (new DateTime())->modify('first day of next month')->format('Y-m-d');
        $query = <<<SQL
select sum(amount) as total from devapi_transaction where
account_id=$account_id and type!="payout_bank" and datetime between "$from" and "$to"
SQL;
        return $this->query($query)->fetchField('total');
    }
}