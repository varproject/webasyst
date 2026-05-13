<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

class devapiCheckLicensesMethod extends devapiAPIMethod
{
    public function execute()
    {
        $value = $this->post('value', true);
        $this->response = $this->remote->checkLicenses($value);
    }
}