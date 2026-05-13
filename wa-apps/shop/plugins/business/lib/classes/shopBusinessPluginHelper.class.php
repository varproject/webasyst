<?php
class shopBusinessPluginHelper
{
    public static function isDisabledWAID()
    {
        if (!class_exists('waServicesApi')) {
            throw new JsonException('Необходимо обновить фреймворк');
        }

        $service_api = new waServicesApi();
        return !$service_api->isConnected();
    }

    public static function attachErrorHTML(&$errors)
    {
        $errors['error_html'] =
        '<div class="alert warning custom-m-0">'.
            '<span class="icon"><i class="fas fa-exclamation-triangle fa-sm"></i></span> '._w('An error occurred').
            '<pre class="custom-my-4">'.$errors['error'].' '.$errors['error_description'].'</pre>'.
            _ws('Please refer to your system administrator.').
        '</div>';
    }
}
