<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiGetTransactionsMethod extends devapiAPIMethod
{
    public function execute()
    {
        $offset = $this->post('offset', true);
        $limit = $this->post('last', true);
        $this->response = $this->remote->getTransactions($offset, $limit);
    }
}