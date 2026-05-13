<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiGetPromocodesMethod extends devapiAPIMethod
{
    protected $method = waNet::METHOD_GET;

    public function execute()
    {
        $code = $this->get('code');
        $this->response = $this->remote->getPromocodes($code);
    }
}