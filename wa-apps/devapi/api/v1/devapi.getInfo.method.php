<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */
class devapiGetInfoMethod extends devapiAPIMethod
{
    protected $method = waNet::METHOD_GET;

    public function execute()
    {
        $this->response = [
            'products' => $this->remote->getProducts(),
            'start_date' => $this->remote->start_date,
            'promo' => $this->remote->params['promo'],
            'percent' => $this->remote->params['percent']
        ];
    }
}