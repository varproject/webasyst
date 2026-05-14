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
        $root = trim((string) ifset($route, 'route', ''), '/');
        return $root === '' ? $url : $url.$root.'/';
    }

    public static function sectionUrl(array $route, $section)
    {
        $base = self::getCabinetUrl($route);
        $section = trim((string) $section, '/');
        return $section === '' ? $base : $base.$section.'/';
    }
}
