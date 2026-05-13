<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiConfig extends waAppConfig
{
    public function onCount()
    {
        $settings = new devapiSettings();
        if (!$settings->counter) return 0;
        $count = 0;
        foreach ((new devapiAccounts())->getAccounts() as $account) {
            try {
                $count += $account->updateTransactions();
            } catch (Exception $e) {
                devapiHelper::setLog($e->getMessage(), 'error', null, $e);
            }
        }
        return $count;
    }
}