<?php

class devapiAccountRemotesModel extends waModel
{
    protected $table = 'devapi_account_remotes';

    public function getByAccount(int $account_id)
    {
        return $this->select('id,account_id')->where('account_id=i:id', ['id' => $account_id])->fetchAll();
    }
}