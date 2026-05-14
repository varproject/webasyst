<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    // Поля стандартной формы настройки канала продаж.
    protected function getFormFieldsConfig($values = []): array
    {
        return [
            'route_key' => [
                'title' => 'Витрина B2B-портала',
                'description' => 'Выберите поселение Shop-Script, через которое будет открываться клиентский B2B-портал.',
                'control_type' => waHtmlControl::SELECT,
                'options' => $this->getShopRouteOptions(),
                'value' => ifset($values, 'route_key', ''),
            ],

            'frontend_url' => [
                'title' => 'Адрес B2B-портала',
                'description' => 'Укажите URL внутри поселения Shop-Script. Например: b2b, clients, portal.',
                'control_type' => waHtmlControl::INPUT,
                'value' => ifset($values, 'frontend_url', 'b2b'),
            ],

            'auth_required' => [
                'title' => 'Требовать авторизацию',
                'description' => 'Доступ к B2B-порталу будет разрешён только авторизованным клиентам.',
                'control_type' => waHtmlControl::CHECKBOX,
                'value' => ifset($values, 'auth_required', 1),
            ],

            'company_required' => [
                'title' => 'Требовать компанию',
                'description' => 'Клиент должен быть привязан к компании для работы с B2B-порталом.',
                'control_type' => waHtmlControl::CHECKBOX,
                'value' => ifset($values, 'company_required', 1),
            ],
        ];
    }

    // Проверяет и нормализует параметры канала перед сохранением.
    public function sanitizeAndValidateParams(?int $id, array &$params, $params_mode): array
    {
        $errors = [];

        $params['route_key'] = trim((string) ifset($params, 'route_key', ''));
        $params['frontend_url'] = trim((string) ifset($params, 'frontend_url', ''));
        $params['auth_required'] = !empty($params['auth_required']) ? 1 : 0;
        $params['company_required'] = !empty($params['company_required']) ? 1 : 0;
        $params['price_mode'] = trim((string) ifset($params, 'price_mode', 'b2b'));

        if ($params['route_key'] === '') {
            $errors[] = [
                'field' => 'data[params][route_key]',
                'error_description' => 'Выберите поселение Shop-Script.',
            ];

            return $errors;
        }

        if ($params['frontend_url'] === '') {
            $errors[] = [
                'field' => 'data[params][frontend_url]',
                'error_description' => 'Укажите адрес B2B-портала.',
            ];

            return $errors;
        }

        $route = $this->parseRouteKey($params['route_key']);

        if (!$route) {
            $errors[] = [
                'field' => 'data[params][route_key]',
                'error_description' => 'Выбранное поселение Shop-Script не найдено.',
            ];

            return $errors;
        }

        $params['frontend_url'] = $this->normalizeFrontendUrl($params['frontend_url']);

        // Дублируем данные поселения в params канала.
        $params['domain'] = $route['domain'];
        $params['route_id'] = $route['route_id'];
        $params['route_url'] = $route['url'];
        $params['settlement'] = $route['settlement'];

        return $errors;
    }

    // Нормализует URL внутри поселения.
    // b2b   → b2b/*
    // b2b/  → b2b/*
    // b2b/* → b2b/*
    protected function normalizeFrontendUrl($url): string
    {
        $url = trim((string) $url);
        $url = trim($url, '/');

        if ($url === '' || $url === '*') {
            return '*';
        }

        if (substr($url, -1) === '*') {
            return rtrim($url, '/');
        }

        return $url . '/*';
    }

    // Дополнительные действия после сохранения канала сейчас не требуются.
    public function onSave(array $channel) {}

    // Публичные параметры канала для Headless API.
    public function getPublicStorefrontParams(array $channel): array
    {
        $params = ifset($channel, 'params', []);

        return array_intersect_key($params, [
            'auth_required' => 1,
            'company_required' => 1,
            'price_mode' => 1,
        ]);
    }

    // Формирует список shop-поселений для select.
    protected function getShopRouteOptions(): array
    {
        $options = [
            [
                'value' => '',
                'title' => 'Выберите витрину',
            ],
        ];

        $routing = wa()->getRouting();

        foreach ($routing->getDomains() as $domain) {
            $routes = $routing->getByApp('shop', $domain);

            foreach ($routes as $route_id => $route) {
                $url = trim((string) ifset($route, 'url', ''));

                $options[] = [
                    'value' => $this->buildRouteKey($domain, $route_id),
                    'title' => $this->formatSettlementTitle($domain, $url),
                ];
            }
        }

        return $options;
    }

    // Разбирает route_key вида domain|route_id и проверяет существование поселения.
    protected function parseRouteKey($route_key)
    {
        $route_key = (string) $route_key;

        if (strpos($route_key, '|') === false) {
            return null;
        }

        list($domain, $route_id) = explode('|', $route_key, 2);

        $domain = trim($domain);
        $route_id = trim($route_id);

        if ($domain === '' || $route_id === '') {
            return null;
        }

        $routes = wa()->getRouting()->getByApp('shop', $domain);

        if (!isset($routes[$route_id])) {
            return null;
        }

        $route = $routes[$route_id];
        $url = trim((string) ifset($route, 'url', ''));

        return [
            'domain' => $domain,
            'route_id' => $route_id,
            'url' => $url,
            'settlement' => $this->formatSettlementTitle($domain, $url),
            'route' => $route,
        ];
    }

    // Формирует ключ привязки канала к поселению.
    protected function buildRouteKey($domain, $route_id): string
    {
        return $domain . '|' . $route_id;
    }

    // Формирует человекочитаемое название поселения.
    protected function formatSettlementTitle($domain, $url): string
    {
        $url = trim((string) $url);
        $url = str_replace('*', '', $url);
        $url = trim($url, '/');

        if ($url === '') {
            return $domain . '/';
        }

        return $domain . '/' . $url . '/';
    }
}
