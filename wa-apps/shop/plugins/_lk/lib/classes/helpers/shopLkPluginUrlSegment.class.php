<?php

final class shopLkPluginUrlSegment
{
    private static $cache = null;

    public static function get($position, $default = null)
    {
        $segments = self::all();
        return isset($segments[$position - 1]) ? $segments[$position - 1] : $default;
    }

    public static function all()
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $path = (string) parse_url((string) waRequest::server('REQUEST_URI', ''), PHP_URL_PATH);
        $app_url = (string) parse_url((string) wa()->getAppUrl('shop'), PHP_URL_PATH);
        $app_url = rtrim($app_url, '/') . '/';
        if ($app_url !== '/' && strpos($path, $app_url) === 0) {
            $path = substr($path, strlen($app_url));
        } else {
            $path = trim($path, '/');
        }
        self::$cache = $path === '' ? array() : array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
        return self::$cache;
    }

    public static function reset()
    {
        self::$cache = null;
    }
}
