<?php

/**
 * apanelStorefrontResolver
 *
 * Сервис определения текущей витрины по domain + route.
 *
 * Назначение:
 * - получить текущий domain и route из Webasyst;
 * - найти соответствующее поселение Apanel;
 * - вернуть storefront_key и основные данные витрины.
 *
 * Инварианты:
 * - resolver работает только при фронтенд-запросе;
 * - domain и route берутся из текущего Webasyst routing;
 * - storefront_key = crc32(domain) . '_' . route_id;
 * - если storefront не найден — возвращает null.
 *
 * Ошибки:
 * - не выбрасывает исключения, возвращает null при ошибке.
 */
final class apanelStorefrontResolver
{
    /**
     * Определяет текущий storefront по domain + route.
     *
     * @param string|null $domain Домен (если null, берётся из текущего routing).
     * @param int|string|null $route_id ID route (если null, определяется по URL).
     * @return array|null
     */
    public function resolve($domain = null, $route_id = null)
    {
        // 1. Определить domain
        if ($domain === null) {
            $domain = wa()->getRouting()->getDomain();
        }

        if (!$domain) {
            return null;
        }

        // 2. Получить текущий route
        if ($route_id === null) {
            $current_route = wa()->getRouting()->getRoute();

            if (!is_array($current_route)) {
                return null;
            }

            $current_url = ifset($current_route['url'], '');
            $routing_service = new apanelRoutingService();
            $routes = $routing_service->getAppRoutes($domain);

            // 3. Найти route_id по URL
            $route_id = $this->findRouteIdByUrl($routes, $current_url);

            if ($route_id === null) {
                return null;
            }
        }

        // 4. Собрать storefront данные
        return $this->buildStorefrontData($domain, $route_id);
    }

    /**
     * Находит route_id по URL поселения.
     *
     * @param array $routes Массив routes (из apanelRoutingService::getAppRoutes).
     * @param string $url URL поселения для поиска.
     * @return int|string|null
     */
    protected function findRouteIdByUrl($routes, $url)
    {
        if (!is_array($routes)) {
            return null;
        }

        foreach ($routes as $route_id => $route) {
            if (ifset($route['url'], '') === $url) {
                return $route_id;
            }
        }

        return null;
    }

    /**
     * Собирает полные данные storefront.
     *
     * @param string $domain Домен.
     * @param int|string $route_id ID route.
     * @return array
     */
    protected function buildStorefrontData($domain, $route_id)
    {
        $domain_hash = hash('crc32', $domain);
        $storefront_key = $domain_hash . '_' . $route_id;

        $routing_service = new apanelRoutingService();
        $routes = $routing_service->getAppRoutes($domain);
        $route = $routes[$route_id] ?? null;

        if (!$route) {
            return null;
        }

        $url = ifset($route['url'], '');
        $clean_url = waRouting::clearUrl($url);

        return [
            'storefront_key' => $storefront_key,
            'route_id' => $route_id,
            'domain_hash' => $domain_hash,
            'domain' => $domain,
            'url' => $url,
            'clean_url' => $clean_url,
            'full_url' => $this->buildFullUrl($domain, $clean_url),
            'app' => ifset($route['app'], 'apanel'),
        ];
    }

    /**
     * Собирает полный URL витрины.
     *
     * @param string $domain Домен.
     * @param string $clean_url Чистый URL без звёздочки и слешей.
     * @return string
     */
    protected function buildFullUrl($domain, $clean_url)
    {
        $scheme = waRequest::isHttps() ? 'https://' : 'http://';
        $domain = rtrim($domain, '/');
        $clean_url = trim($clean_url, '/');

        return $scheme . $domain . '/' . ($clean_url !== '' ? $clean_url . '/' : '');
    }
}
