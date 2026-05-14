<?php

final class shopLkPluginUrlService
{
    public static function getStorefrontUrl(array $route, $absolute = false)
    {
        $root = wa()->getRootUrl(false);
        $url = $root . ifset($route, 'shop_url', '');
        if (!$absolute) {
            return $url;
        }
        $scheme = waRequest::isHttps() ? 'https://' : 'http://';
        return $scheme . ifset($route, 'domain', wa()->getRouting()->getDomain()) . $url;
    }

    public static function getCabinetUrl(array $route, $absolute = false)
    {
        $url = self::getStorefrontUrl($route, $absolute);
        $root = !empty($route['b2b_mode']) ? '' : trim((string) ifset($route, 'route', ''), '/');
        return $root === '' ? $url : $url.$root.'/';
    }

    public static function sectionUrl(array $route, $section)
    {
        $base = self::getCabinetUrl($route);
        $section = trim((string) $section, '/');
        return $section === '' ? $base : $base.$section.'/';
    }

    public static function sectionUrlAbsolute(array $route, $section)
    {
        $base = self::getCabinetUrl($route, true);
        $section = trim((string) $section, '/');
        return $section === '' ? $base : $base.$section.'/';
    }

    public static function loginUrl(array $route, $absolute = false)
    {
        return $absolute ? self::sectionUrlAbsolute($route, 'login') : self::sectionUrl($route, 'login');
    }

    public static function signupUrl(array $route, $absolute = false)
    {
        return $absolute ? self::sectionUrlAbsolute($route, 'signup') : self::sectionUrl($route, 'signup');
    }

    public static function forgotUrl(array $route, $absolute = false)
    {
        return $absolute ? self::sectionUrlAbsolute($route, 'forgotpassword') : self::sectionUrl($route, 'forgotpassword');
    }
}
