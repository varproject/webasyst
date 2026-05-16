<?php

/**
 * Сервис управления поселениями приложения apanel.
 *
 * Назначение:
 * - читать глобальные правила маршрутизации из wa-config/routing.php;
 * - добавлять, обновлять и удалять только правила приложения apanel;
 * - сохранять изменения стандартным способом Webasyst;
 * - обновлять конфигурационный кеш после записи.
 *
 * Зависимости:
 * - waSystemConfig;
 * - waRouting;
 * - waUtils;
 * - waConfigCache.
 *
 * Инварианты:
 * - сервис работает только с app = apanel;
 * - сервис не изменяет правила других приложений;
 * - URL поселения хранится без начального слеша;
 * - обычное поселение приложения сохраняется в формате path/*;
 * - правило * добавляется только если явно передано пустое значение URL.
 *
 * Побочные эффекты:
 * - изменяет файл wa-config/routing.php;
 * - обновляет кеш конфигурации.
 *
 * Ошибки:
 * - waException, если файл routing.php недоступен для записи;
 * - waException, если домен не найден;
 * - waException, если попытка сохранить некорректный URL.
 */
class apanelRoutingService
{
    /**
     * ID приложения.
     *
     * @var string
     */
    protected $app_id = 'apanel';

    /**
     * Возвращает все домены из системного роутинга.
     *
     * @return array
     */
    public function getDomains($for_nav = false)
    {
        $domains = wa()->getRouting()->getDomains();

        if ($for_nav) {
            $result = [];
            foreach ($domains as $domain) {
                $result[$domain] = [
                    'id'     => hash('crc32', $domain),
                    'domain' => $domain,
                    'name'   => $domain,
                ];
            }

            uksort($result, function ($a, $b) {
                $rev_a = implode('.', array_reverse(explode('.', $a)));
                $rev_b = implode('.', array_reverse(explode('.', $b)));
                return strnatcasecmp($rev_a, $rev_b);
            });

            return $result;
        }

        return $domains;
    }


    /**
     * Возвращает домены и поселения приложения.
     *
     * Логика:
     * - собирает домены;
     * - нормализует поселения приложения;
     * - если название поселения пустое — ставит Проект_{route_id};
     * - добавляет route_id и full_url;
     * - если передан корректный $domain_hash — возвращает его поселения;
     * - если не передан или кривой — возвращает первый доступный домен.
     *
     * @param string|null $domain_hash Хеш домена.
     * @return array
     */
    public function getAppDomainsRoutes($domain_hash = null)
    {
        $raw_routes = $this->getAppRoutes();

        // dd($raw_routes);

        $domains = [];
        $routes_by_domain = [];

        foreach ($raw_routes as $domain => $domain_routes) {
            $hash = hash('crc32', $domain);

            $domains[$hash] = [
                'id'   => $hash,
                'name' => $domain,
            ];

            foreach ($domain_routes as $route_id => $route) {
                $url = ifset($route['url'], '');
                $clean_url = waRouting::clearUrl($url);

                $routes_by_domain[$hash][$route_id] = [
                    'route_id'    => $route_id,
                    'domain_hash' => $hash,
                    'domain'      => $domain,
                    '_name'       => ifempty($route['_name'], 'Проект_' . $route_id),
                    'url'         => $url,
                    'full_url'    => 'https://' . rtrim($domain, '/') . '/' . ltrim($clean_url, '/'),
                    'app'         => ifset($route['app'], ''),
                ];
            }
        }

        $active_domain_hash = ($domain_hash && !empty($routes_by_domain[$domain_hash]))
            ? $domain_hash
            : key($routes_by_domain);

        $active_domain = ifset($domains[$active_domain_hash]['name'], null);

        $routes = [];

        if ($active_domain_hash && $active_domain) {
            $routes[$active_domain] = $routes_by_domain[$active_domain_hash];
        }


        return [
            'domains'            => $domains,
            'routes'             => $routes,
            'active_domain_hash' => $active_domain_hash,
            'active_domain'      => $active_domain,
            'result'             => (!empty($domains) && !empty($routes)),
        ];
    }


    /**
     * Возвращает поселения приложения apanel.
     *
     * @param string|null $domain Домен сайта.
     * @return array
     */
    public function getAppRoutes($domain = null)
    {
        return wa()->getRouting()->getByApp($this->app_id, $domain);
    }

    /**
     * Возвращает все поселения приложения плоским массивом.
     *
     * @return array
     */
    public function getFlatAppRoutes()
    {
        $routes = $this->getAppRoutes();
        $result = [];
        $index = 1;

        foreach ($routes as $domain => $domain_routes) {
            foreach ($domain_routes as $route_id => $route) {
                $url = ifset($route['url'], '');
                $url = trim(str_replace('*', '', $url), '/');

                $result[$index] = [
                    'id'     => $domain,
                    // 'url'    => $url,
                    'name'  => ifset($route['_name'], $domain),
                    // 'domain' => $domain,
                ];

                $index++;
            }
        }

        return $result;
    }

    /**
     * Добавляет поселение приложения.
     *
     * @param string $domain Домен сайта.
     * @param string $url URL поселения: apanel, cabinet, my/panel или *.
     * @param array $params Дополнительные параметры правила.
     * @return int|string ID созданного правила.
     * @throws waException
     */
    public function addRoute($domain, $url, $params = [])
    {
        $routes = $this->getAllRoutes();

        if (empty($routes[$domain]) || !is_array($routes[$domain])) {
            throw new waException('Домен не найден в wa-config/routing.php: ' . $domain);
        }

        $route = $this->prepareRoute($url, $params);
        $this->checkDuplicateUrl($routes[$domain], $route['url']);

        $route_id = $this->getNextRouteId($routes[$domain]);

        if ($route['url'] == '*') {
            $routes[$domain][$route_id] = $route;
        } else {
            $routes[$domain] = [$route_id => $route] + $routes[$domain];
        }

        $this->saveRoutes($routes);

        return $route_id;
    }

    /**
     * Обновляет поселение приложения.
     *
     * @param string $domain Домен сайта.
     * @param int|string $route_id ID правила.
     * @param string $url Новый URL поселения.
     * @param array $params Дополнительные параметры правила.
     * @return void
     * @throws waException
     */
    public function updateRoute($domain, $route_id, $url, $params = [])
    {
        $routes = $this->getAllRoutes();

        if (empty($routes[$domain][$route_id])) {
            throw new waException('Правило маршрутизации не найдено.');
        }

        if (ifset($routes[$domain][$route_id]['app']) != $this->app_id) {
            throw new waException('Нельзя изменять правило другого приложения.');
        }

        $route = $this->prepareRoute($url, $params);
        $this->checkDuplicateUrl($routes[$domain], $route['url'], $route_id);

        $routes[$domain][$route_id] = $route;

        $this->saveRoutes($routes);
    }

    /**
     * Удаляет поселение приложения.
     *
     * @param string $domain Домен сайта.
     * @param int|string $route_id ID правила.
     * @return void
     * @throws waException
     */
    public function deleteRoute($domain, $route_id)
    {
        $routes = $this->getAllRoutes();

        if (empty($routes[$domain][$route_id])) {
            return;
        }

        if (ifset($routes[$domain][$route_id]['app']) != $this->app_id) {
            throw new waException('Нельзя удалить правило другого приложения.');
        }

        unset($routes[$domain][$route_id]);

        $this->saveRoutes($routes);
    }

    /**
     * Возвращает массив всех правил из wa-config/routing.php.
     *
     * @return array
     */
    protected function getAllRoutes()
    {
        $path = $this->getRoutingPath();

        if (!file_exists($path)) {
            return [];
        }

        $routes = include($path);

        return is_array($routes) ? $routes : [];
    }

    /**
     * Подготавливает правило поселения приложения.
     *
     * @param string $url URL поселения.
     * @param array $params Дополнительные параметры.
     * @return array
     * @throws waException
     */
    protected function prepareRoute($url, $params = [])
    {
        $url = $this->normalizeUrl($url);

        $route = [
            'url' => $url,
            'app' => $this->app_id,
        ];

        if (!empty($params['_name'])) {
            $route['_name'] = $params['_name'];
        }

        if (!empty($params['locale'])) {
            $route['locale'] = $params['locale'];
        }

        if (!empty($params['private'])) {
            $route['private'] = true;
        }

        return $route;
    }

    /**
     * Нормализует URL поселения.
     *
     * @param string $url URL поселения.
     * @return string
     * @throws waException
     */
    protected function normalizeUrl($url)
    {
        $url = trim((string) $url);
        $url = trim($url, '/');

        if ($url === '' || $url === '*') {
            return '*';
        }

        if (strpos($url, '..') !== false) {
            throw new waException('Некорректный URL поселения.');
        }

        if (strpos($url, '*') === false) {
            $url .= '/*';
        }

        return $url;
    }

    /**
     * Проверяет дублирование URL в пределах одного домена.
     *
     * @param array $routes Правила домена.
     * @param string $url URL поселения.
     * @param int|string|null $skip_route_id ID правила, которое нужно пропустить.
     * @return void
     * @throws waException
     */
    protected function checkDuplicateUrl($routes, $url, $skip_route_id = null)
    {
        foreach ($routes as $route_id => $route) {
            if ((string) $route_id === (string) $skip_route_id) {
                continue;
            }

            if (ifset($route['url']) === $url) {
                throw new waException('URL уже используется другим правилом маршрутизации: ' . $url);
            }
        }
    }

    /**
     * Возвращает следующий числовой ID правила.
     *
     * @param array $routes Правила домена.
     * @return int
     */
    protected function getNextRouteId($routes)
    {
        $id = 0;

        foreach ($routes as $route_id => $route) {
            if (is_numeric($route_id) && $route_id > $id) {
                $id = $route_id;
            }
        }

        return $id + 1;
    }

    /**
     * Сохраняет правила в wa-config/routing.php.
     *
     * @param array $routes Все правила маршрутизации.
     * @return void
     * @throws waException
     */
    protected function saveRoutes($routes)
    {
        $path = $this->getRoutingPath();

        if (file_exists($path) && !is_writable($path)) {
            throw new waException('Нет прав на запись файла wa-config/routing.php.');
        }

        if (!waUtils::varExportToFile($routes, $path)) {
            throw new waException('Не удалось сохранить wa-config/routing.php.');
        }

        waConfigCache::getInstance()->setFileContents($path, $routes);
        wa()->getRouting()->setRoutes($routes);
    }

    /**
     * Возвращает путь к wa-config/routing.php.
     *
     * @return string
     */
    protected function getRoutingPath()
    {
        return wa()->getConfig()->getPath('config', 'routing');
    }
}
