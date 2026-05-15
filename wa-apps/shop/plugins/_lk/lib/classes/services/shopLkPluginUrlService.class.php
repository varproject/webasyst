<?php

final class shopLkPluginUrlService
{
    public static function getStorefrontUrl(array $route, $absolute = false)
    {
        $path = self::buildStorefrontPath((string) ifset($route, 'shop_url', ''));

        if (!$absolute) {
            return $path;
        }

        $domain = (string) ifset($route, 'domain', '');
        if ($domain === '') {
            $domain = (string) wa()->getRouting()->getDomain();
        }

        $scheme = waRequest::isHttps() ? 'https://' : 'http://';

        return $scheme . $domain . $path;
    }

    public static function getCabinetUrl(array $route, $absolute = false)
    {
        $url = self::getStorefrontUrl($route, $absolute);
        $root = !empty($route['b2b_mode']) ? '' : trim((string) ifset($route, 'route', ''), '/');

        return $root === '' ? $url : self::withTrailingSlash($url) . $root . '/';
    }

    public static function sectionUrl(array $route, $section)
    {
        $base = self::getCabinetUrl($route);
        $section = trim((string) $section, '/');

        return $section === '' ? $base : self::withTrailingSlash($base) . $section . '/';
    }

    public static function sectionUrlAbsolute(array $route, $section)
    {
        $base = self::getCabinetUrl($route, true);
        $section = trim((string) $section, '/');

        return $section === '' ? $base : self::withTrailingSlash($base) . $section . '/';
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

    protected static function buildStorefrontPath($shop_url)
    {
        $root = (string) wa()->getRootUrl(false);
        if ($root === '') {
            $root = '/';
        }

        $root = '/' . trim($root, '/') . '/';
        if ($root === '//') {
            $root = '/';
        }

        $shop_url = trim((string) $shop_url, '/');
        if ($shop_url === '' || $shop_url === '*') {
            return $root;
        }

        return self::withTrailingSlash($root . $shop_url);
    }

    protected static function withTrailingSlash($url)
    {
        $url = (string) $url;

        return $url !== '' && substr($url, -1) !== '/' ? $url . '/' : $url;
    }
}
