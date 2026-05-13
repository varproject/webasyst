<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiCreatePromocodeMethod extends devapiAPIMethod
{
    protected $method = waNet::METHOD_POST;

    public function execute()
    {
        $code = $this->post('code', true);
        if (!$this->remote->params['promo']) {
            throw new waAPIException('Для данного аккаунта отсутствуют права на создание промокодов');
        }
        $this->response = $this->remote->createPromocode($code);
    }
}