<?php

class shopB2bPlugin extends shopPlugin
{
    public function __construct($info)
    {
        parent::__construct($info);

        // Подключает пользовательские функции и модификаторы плагина.
        foreach (['functions.php', 'modifiers.php'] as $file) {
            $path = $this->path . '/lib/config/' . $file;

            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    // Регистрирует тип канала продаж B2B.
    public function salesChannelTypes(&$params)
    {
        return [
            [
                'id'        => 'b2b',
                'name'      => 'B2B-витрина',
                'class'     => 'shopB2bPluginSalesChannelType',
                'menu_icon' => '<i class="fas fa-briefcase"></i>',
                'available' => true,
            ],
        ];
    }

    // Описывает неизвестные sales_channel ID вида b2b:{id}.
    public function salesChannels(array &$params)
    {
        $missing_channel_ids = ifset($params, 'missing_channel_ids', []);

        if (!$missing_channel_ids) {
            return [];
        }

        $result = [];

        foreach ($missing_channel_ids as $sales_channel_id) {
            if (!preg_match('~^b2b:(\d+)$~', (string) $sales_channel_id)) {
                continue;
            }

            $result[] = [
                'id'       => $sales_channel_id,
                'type'     => 'storefront',
                'name'     => 'B2B-витрина',
                'icon_url' => wa()->getRootUrl(true) . 'wa-apps/shop/plugins/' . $this->id . '/img/b2b-channel.png',
            ];
        }

        return $result;
    }

    // Добавляет frontend routes B2B-каналов.
    public function routingHandler($route)
    {
        if (wa()->getEnv() === 'backend') {
            return [];
        }

        $route_key = $this->getRouteKeyByRoute($route);

        if (!$route_key) {
            return [];
        }

        $channel_model  = new shopSalesChannelModel();
        $params_model   = new shopSalesChannelParamsModel();
        $access_service = new shopB2bPluginCustomerAccessService();
        $channels       = $channel_model->getByField('type', 'b2b', true);
        $routes         = [];

        if (!$channels) {
            return [];
        }

        foreach ($channels as $channel) {
            if (empty($channel['status'])) {
                continue;
            }

            $channel_params = $params_model->get((int) $channel['id']);
            $frontend_url   = trim((string) ifset($channel_params, 'frontend_url', ''));

            $has_access = $access_service->canAccess(wa()->getUser()->getId(), $channel_params);
            $behavior   = ifset($channel_params, 'access_denied_behavior', 'ignore');

            if (!$has_access && $behavior === 'ignore') {
                continue;
            }

            // Канал должен быть привязан именно к текущему поселению.
            if (ifset($channel_params, 'route_key', '') !== $route_key || $frontend_url === '') {
                continue;
            }

            // Этот URL внутри текущего поселения забирает B2B-плагин.
            $routes[$frontend_url] = [
                'module'             => 'frontend',
                'b2b_channel_id'     => (int) $channel['id'],
                'sales_channel'      => 'b2b:' . $channel['id'],
                'b2b_access_allowed' => $has_access ? 1 : 0,
                'secure'             => !empty($channel_params['auth_required']),
            ];
        }

        return $routes;
    }

    // После создания заказа проставляет правильный канал продаж.
    public function orderActionCreate($params)
    {
        if (wa()->getEnv() !== 'frontend') {
            return;
        }

        $order_id = (int) ifset($params, 'order_id', 0);

        if ($order_id <= 0) {
            return;
        }

        $channel = $this->getCurrentB2bChannel();

        if (!$channel) {
            return;
        }

        $order_params_model = new shopOrderParamsModel();

        // false нужен, чтобы обновить только переданные параметры и не стереть остальные параметры заказа.
        $order_params_model->set($order_id, [
            'sales_channel'  => 'b2b:' . $channel['id'],
            'b2b_channel_id' => $channel['id'],
        ], false);
    }

    // Находит B2B-канал, который привязан к текущему frontend-поселению.
    protected function getCurrentB2bChannel()
    {
        $route_key = $this->getCurrentShopRouteKey();

        if (!$route_key) {
            return null;
        }

        $channel_model = new shopSalesChannelModel();
        $params_model  = new shopSalesChannelParamsModel();
        $channels      = $channel_model->getByField('type', 'b2b', true);

        if (!$channels) {
            return null;
        }

        foreach ($channels as $channel) {
            if (empty($channel['status'])) {
                continue;
            }

            $channel_params = $params_model->get((int) $channel['id']);

            if (ifset($channel_params, 'route_key', '') !== $route_key) {
                continue;
            }

            $channel['params'] = $channel_params;

            return $channel;
        }

        return null;
    }

    // Возвращает ключ текущего shop-поселения в формате domain|route_id.
    protected function getCurrentShopRouteKey()
    {
        $routing       = wa()->getRouting();
        $domain        = $routing->getDomain();
        $current_route = $routing->getRoute();

        if (!$domain || !is_array($current_route)) {
            return null;
        }

        if (ifset($current_route, 'app', '') !== 'shop') {
            return null;
        }

        if (isset($current_route['_id'])) {
            return $domain . '|' . $current_route['_id'];
        }

        $routes = $routing->getByApp('shop', $domain);

        foreach ($routes as $route_id => $route) {
            if ($this->isSameRoute($current_route, $route)) {
                return $domain . '|' . $route_id;
            }
        }

        return null;
    }

    // Сравнивает два settlement route.
    protected function isSameRoute(array $route_a, array $route_b)
    {
        return ifset($route_a, 'app', '') === ifset($route_b, 'app', '')
            && ifset($route_a, 'url', '') === ifset($route_b, 'url', '');
    }

    // Возвращает route_key для settlement-а, для которого Shop-Script сейчас собирает routes.
    protected function getRouteKeyByRoute($route)
    {
        if (!is_array($route)) {
            return null;
        }

        if (ifset($route, 'app', '') !== 'shop') {
            return null;
        }

        $routing = wa()->getRouting();
        $domain  = $routing->getDomain(null, true);

        if (!$domain) {
            return null;
        }

        $routes = $routing->getByApp('shop', $domain);

        foreach ($routes as $route_id => $shop_route) {
            if ($this->isSameRoute($route, $shop_route)) {
                return $domain . '|' . $route_id;
            }
        }

        return null;
    }
}
