<?php

class devapiAccountCashTargetsModel extends waModel
{
    protected $table = 'devapi_account_cash_targets';

    public function getTargetIdsByAccountId($account_id)
    {
        return $this->select('id')->where('account_id=i:acc_id', ['acc_id' => $account_id])->fetchAll(null, true);
    }

    public function getByAccountId($account_id)
    {
        $targets = [];
        if ($target = $this->getLocalTarget()) $targets[$target['id']] = $target;
        return array_merge($targets, $this->getByField('account_id', $account_id, 'id'));
    }

    public function getByTargetId($target_id)
    {
        return $target_id < 0 ? $this->getLocalTarget() : $this->getById($target_id);
    }

    protected function getLocalTarget()
    {
        $target = [];
        $apps = wa()->getApps();
        if (isset($apps['cash'])) {
            if (wa()->getEnv() !== 'backend' || wa()->getUser()->getRights('cash')) {
                $token = (new waModel())->query('select token from wa_api_tokens where client_id="backend.cash.vue"')->fetchField('token');
                $target = [
                    'id' => -1,
                    'account_id' => 0,
                    'name' => 'Приложение "Деньги"',
                    'url' => wa()->getConfig()->getRootUrl(true) . 'api.php',
                    'token' => $token
                ];
            }
        }
        return $target;
    }
}