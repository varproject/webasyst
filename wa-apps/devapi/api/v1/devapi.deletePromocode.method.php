<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiDeletePromocodeMethod extends devapiAPIMethod
{
    protected $method = waNet::METHOD_POST;

    public function execute()
    {
        $code = $this->post('code', true);
        $this->remote->deletePromocode($code);
    }
}