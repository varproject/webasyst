<?php
class devapiRunnerCli extends waCliController
{
    public function execute()
    {
        foreach ((new devapiAccountModel())->select('id')->fetchAll(null, true) as $account_id) {
            try {
                $account = new devapiAccount($account_id);
                $account->updateTransactions();
            } catch (Throwable $e) {
            }
        }
    }
}