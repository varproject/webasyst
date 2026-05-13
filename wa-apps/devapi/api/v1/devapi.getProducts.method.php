<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiGetProductsMethod extends devapiAPIMethod
{
    protected $method = waNet::METHOD_GET;

    public function execute()
    {
        $this->response = $this->remote->getProducts();
    }
}