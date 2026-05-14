<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    /**
     * Поля формы настроек канала продаж.
     *
     * Эти поля отображаются в стандартном интерфейсе Shop-Script:
     * /webasyst/shop/channels/new/b2b/
     */
    protected function getFormFieldsConfig($values = []): array
    {
        return [
            'route_key' => [
                'title' => _wp('B2B portal storefront'),
                'description' => _wp('Select the Shop-Script settlement used as the frontend entry point for this B2B portal.'),
                'control_type' => waHtmlControl::SELECT,
                'options' => $this->getShopRouteOptions(),
                'value' => ifset($values, 'route_key', ''),
            ],

            'auth_required' => [
                'title' => _wp('Require authorization'),
                'description' => _wp('Only authorized customers can access the B2B portal.'),
                'control_type' => waHtmlControl::CHECKBOX,
                'value' => ifset($values, 'auth_required', 1),
            ],

            'company_required' => [
                'title' => _wp('Require company'),
                'description' => _wp('Customer must be linked to a company to use the portal.'),
                'control_type' => waHtmlControl::CHECKBOX,
                'value' => ifset($values, 'company_required', 1),
            ],

            'price_mode' => [
                'title' => _wp('Price mode'),
                'description' => _wp('Internal B2B price mode code.'),
                'control_type' => waHtmlControl::INPUT,
                'value' => ifset($values, 'price_mode', 'b2b'),
            ],
        ];
    }

    /**
     * Проверяет и нормализует параметры канала перед сохранением.
     *
     * Shop-Script сам сохранит результат в shop_sales_channel_params.
     */
    public function sanitizeAndValidateParams(?int $id, array &$params, $params_mode): array
    {
        $errors = [];

        $params['route_key'] = trim((string) ifset($params, 'route_key', ''));
        $params['auth_required'] = !empty($params['auth_required']) ? 1 : 0;
        $params['company_required'] = !empty($params['company_required']) ? 1 : 0;
        $params['price_mode'] = trim((string) ifset($params, 'price_mode', 'b2b'));

        if ($params_mode === 'set' && $params['route_key'] === '') {
            $errors[] = [
                'field' => 'data[params][route_key]',
                'error_description' => _wp('Select a storefront settlement.'),
            ];

            return $errors;
        }

        $route = $this->parseRouteKey($params['route_key']);

        if (!$route) {
            $errors[] = [
                'field' => 'data[params][route_key]',
                'error_description' => _wp('Selected storefront settlement was not found.'),
            ];

            return $errors;
        }

        // Дублируем нормализованные данные в params канала,
        // чтобы их было удобно читать без повторного разбора route_key.
        $params['domain'] = $route['domain'];
        $params['route_id'] = $route['route_id'];
        $params['route_url'] = $route['url'];
        $params['settlement'] = $route['settlement'];

        return $errors;
    }

    /**
     * Дополнительное действие после сохранения канала.
     *
     * Сейчас ничего не делаем:
     * связь с поселением уже сохранена в shop_sales_channel_params.
     */
    public function onSave(array $channel)
    {
        // intentionally empty
    }

    /**
     * Параметры канала, которые можно отдавать во frontend Headless API.
     *
     * Не возвращаем служебные domain/route_id, если они не нужны публично.
     */
    public function getPublicStorefrontParams(array $channel): array
    {
        $params = ifset($channel, 'params', []);

        return array_intersect_key($params, [
            'auth_required' => 1,
            'company_required' => 1,
            'price_mode' => 1,
        ]);
    }

    /**
     * Формирует список shop-поселений для select.
     *
     * Используется только системный waRouting:
     * - getDomains()
     * - getByApp('shop', $domain)
     */
    protected function getShopRouteOptions(): array
    {
        $options = [
            [
                'value' => '',
                'title' => _wp('Select a storefront'),
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

    /**
     * Разбирает route_key вида:
     * domain|route_id
     *
     * И проверяет, что такое shop-поселение реально существует.
     */
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

    /**
     * Единый формат ключа привязки канала к поселению.
     */
    protected function buildRouteKey($domain, $route_id): string
    {
        return $domain . '|' . $route_id;
    }

    /**
     * Человекочитаемое название поселения.
     *
     * Пример:
     * webasyst.loc/b2b/
     */
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
