<?php

class filesException extends waException
{
    public function __toString()
    {
        if (!waSystemConfig::isDebug() && $this->getCode() == 404 && waRequest::isXMLHttpRequest()) {
            $html  =
                '<h1>' . _ws('Error') . ' #404</h1>' .
                '<div style="border:1px solid #EAEAEA;padding:10px; margin:10px 0">' .
                    '<p style="color:red; font-weight: bold">' .
                        $this->getMessage() .
                    '</p>' .
                    '<p>' . _w('Please contact app developer.') . '</p>' .
                '</div>';
            $response = new waResponse();
            $response->setStatus(404);
            $response->sendHeaders();
        } else {
            $html = parent::__toString();
        }
        return $html;
    }
}