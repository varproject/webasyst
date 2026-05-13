<?php

class logsInvalidDataException extends Exception
{
    public function __construct($message = null, $code = null)
    {
        parent::__construct(_w('An error has occurred. Reload this page and try again.'));
    }
}
