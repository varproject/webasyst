<?php

class shopB2bPlugin extends shopPlugin
{
    public function __construct($info)
    {
        parent::__construct($info);
        foreach (array('functions.php', 'modifiers.php') as $file) {
            $path = $this->path . '/lib/config/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function salesChannelTypes(&$params)
    {
        return array(array(
            'id' => 'b2b',
            'name' => 'B2B-витрина',
            'class' => 'shopB2bPluginSalesChannelType',
            'menu_icon' => '<i class="fas fa-briefcase"></i>',
            'available' => true,
        ));
    }

    public function salesChannels(array &$params)
    {
        $result = array();
        foreach ((array) ifset($params, 'missing_channel_ids', array()) as $sales_channel_id) {
            if (preg_match('~^b2b:(\d+)$~', (string) $sales_channel_id)) {
                $result[] = array(
                    'id' => $sales_channel_id,
                    'type' => 'storefront',
                    'name' => 'B2B-витрина',
                    'icon_url' => wa()->getRootUrl(true) . 'wa-apps/shop/plugins/' . $this->id . '/img/b2b-channel.png',
                );
            }
        }
        return $result;
    }

    public function routingHandler($route)
    {
        if (wa()->getEnv() === 'backend') {
            return array();
        }

        $route_key = $this->getRouteKeyByRoute($route);
        if (!$route_key) {
            return array();
        }

        $channel_model = new shopSalesChannelModel();
        $params_model = new shopSalesChannelParamsModel();
        $channels = $channel_model->getByField('type', 'b2b', true);
        $routes = array();

        foreach ($channels as $channel) {
            if (empty($channel['status'])) {
                continue;
            }

            $params = $params_model->get((int) $channel['id']);
            if (ifset($params, 'b2b_version', '') !== '2') {
                continue;
            }
            if (ifset($params, 'b2b_main_route_key', '') !== $route_key) {
                continue;
            }

            $base = $this->getBaseUrl($params);
            $channel_id = (int) $channel['id'];

            $this->addRoute($routes, $base, '', 'default', $channel_id, 'home');

            if (!empty($params['b2b_catalog_enabled'])) {
                $this->addRoute($routes, $base, ifset($params, 'b2b_catalog_url', 'catalog'), 'catalog', $channel_id, 'catalog');
            }
            $this->addRoute($routes, $base, 'page/<page_url>/', 'page', $channel_id, 'page');

            if (!empty($params['b2b_blog_enabled'])) {
                $blog_url = ifset($params, 'b2b_blog_url', 'blog');
                $this->addRoute($routes, $base, $blog_url, 'blog', $channel_id, 'blog');
                $this->addRoute($routes, $base, trim($blog_url, '/') . '/<post_url>/', 'blogPost', $channel_id, 'blog');
            }
            if (!empty($params['b2b_support_enabled'])) {
                $this->addRoute($routes, $base, ifset($params, 'b2b_support_url', 'support'), 'support', $channel_id, 'support');
            }
            if (!empty($params['b2b_cart_enabled'])) {
                $this->addRoute($routes, $base, ifset($params, 'b2b_cart_url', 'cart'), 'cart', $channel_id, 'cart');
            }
        }

        return $routes;
    }

    public function orderActionCreate($params)
    {
        if (wa()->getEnv() !== 'frontend') {
            return;
        }

        $channel_id = waRequest::param('b2b_channel_id', 0, waRequest::TYPE_INT);
        $order_id = (int) ifset($params, 'order_id', 0);
        if ($channel_id <= 0 || $order_id <= 0) {
            return;
        }

        $order_params_model = new shopOrderParamsModel();
        $order_params_model->set($order_id, array(
            'sales_channel' => 'b2b:' . $channel_id,
            'b2b_channel_id' => $channel_id,
        ), false);
    }

    protected function addRoute(array &$routes, string $base, string $suffix, string $action, int $channel_id, string $section): void
    {
        $url = $this->joinRouteUrl($base, $suffix);
        $routes[$url] = array(
            'module' => 'frontend',
            'action' => $action === 'default' ? null : $action,
            'b2b_channel_id' => $channel_id,
            'b2b_section' => $section,
            'sales_channel' => 'b2b:' . $channel_id,
        );
    }

    protected function getBaseUrl(array $params): string
    {
        $url = trim((string) ifset($params, 'b2b_main_frontend_url', ''));
        $url = str_replace('*', '', $url);
        return trim($url, '/');
    }

    protected function joinRouteUrl(string $base, string $suffix): string
    {
        $suffix = trim($suffix, '/');
        $url = trim($base . '/' . $suffix, '/');
        return $url === '' ? '' : $url . '/';
    }

    protected function getRouteKeyByRoute($route)
    {
        if (!is_array($route) || ifset($route, 'app', '') !== 'shop') {
            return null;
        }

        $routing = wa()->getRouting();
        $domain = $routing->getDomain(null, true);
        if (!$domain) {
            return null;
        }

        foreach ($routing->getByApp('shop', $domain) as $route_id => $shop_route) {
            if ($this->isSameRoute($route, $shop_route)) {
                return $domain . '|' . $route_id;
            }
        }

        return null;
    }

    protected function isSameRoute(array $route_a, array $route_b)
    {
        return ifset($route_a, 'app', '') === ifset($route_b, 'app', '')
            && ifset($route_a, 'url', '') === ifset($route_b, 'url', '');
    }
}
