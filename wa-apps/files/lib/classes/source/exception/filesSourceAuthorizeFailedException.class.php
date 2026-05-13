<?php

class filesSourceAuthorizeFailedException extends filesSourceException
{
    protected $params;

    public function setParams($params = array())
    {
        $this->params = $params;
    }
    public function getParams()
    {
        return $this->params;
    }
}