<?php

class filesSourceRetryLaterException extends filesSourceException
{
    protected $message = 'Please try again in a few minutes';
    protected $params;

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }
}
