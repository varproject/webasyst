<?php

class shopB2bPlugin extends shopPlugin
{
    // Подключить пользовательские функции и модификаторы
    public function __construct($info)
    {
        parent::__construct($info);

        foreach (['functions.php', 'modifiers.php'] as $file) {
            $path = $this->path . '/lib/config/' . $file;

            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Регистрирует тип канала продаж "B2B portal".
     *
     * Этот хук НЕ создает сам канал.
     * Он только сообщает Shop-Script, что существует тип каналов b2b.
     *
     * После этого Shop-Script сможет создать запись:
     * shop_sales_channel.type = b2b
     */
    public function salesChannelTypes(&$params)
    {
        // Если нужно ограничить регион, можно использовать:
        // if (!in_array($params['locality'], ['all', 'ru'])) {
        //     return [];
        // }

        return [
            [
                'id' => 'b2b',
                'name' => _wp('B2B portal'),
                'class' => 'shopB2bPluginSalesChannelType',
                'menu_icon' => '<i class="fas fa-briefcase"></i>',
                'available' => true,
            ],
        ];
    }

    /**
     * Описывает неизвестные sales_channel ID вида b2b:{id}.
     *
     * Основной механизм отображения b2b-каналов работает через shop_sales_channel.
     * Этот хук нужен только как fallback для старых заказов, если канал был удален.
     */
    public function salesChannels(&$params)
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
                'id' => $sales_channel_id,
                'type' => 'storefront',
                'name' => _wp('B2B portal'),
                'icon_url' => wa()->getRootUrl(true) . 'wa-apps/shop/plugins/' . $this->id . '/img/b2b-channel.png',
            ];
        }

        return $result;
    }

    /**
     * Добавляет пункт B2B в левое меню Shop-Script WA 2.0.
     *
     * Это только ссылка на внутренний backend-интерфейс плагина.
     * Сам канал продаж создается через sales_channel_types + стандартный интерфейс каналов.
     */
    public function backendExtendedMenu(&$params)
    {
        if (!wa()->getUser()->isAdmin('shop')) {
            return;
        }

        $shop_backend_url = wa('shop')->getAppUrl(null, true);

        $params['menu'][$this->id . '_portal'] = [
            'id' => $this->id . '_portal',
            'name' => _wp('B2B'),
            'icon' => '<i class="fas fa-briefcase"></i>',
            'url' => $shop_backend_url . $this->id . '/',
            'placement' => 'channels',
            'insert_after' => 'storefront',
            'userRights' => [
                'settings',
            ],
        ];
    }

    /**
     * Backend route для внутренней страницы плагина.
     *
     * URL:
     * /webasyst/shop/b2b/
     *
     * Класс:
     * shopB2bPluginBackendSettingsAction
     */
    public function routingHandler($route)
    {
        if (wa()->getEnv() !== 'backend') {
            return [];
        }

        return [
            $this->id . '/' => 'backend/settings',
        ];
    }

    /**
     * После создания заказа проставляет правильный канал продаж.
     *
     * Shop-Script при создании заказа сам ставит sales_channel:
     * - storefront:{storefront}, если заказ с витрины;
     * - other:, если storefront неизвестен.
     *
     * Для B2B-портала нужно заменить это значение на:
     * b2b:{channel_id}
     */
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

        $sales_channel = 'b2b:' . $channel['id'];

        $order_params_model = new shopOrderParamsModel();

        // Третий параметр false обязателен:
        // он обновит только переданные параметры и не сотрет остальные order params.
        $order_params_model->set($order_id, [
            'sales_channel' => $sales_channel,
            'b2b_channel_id' => $channel['id'],
        ], false);
    }

    /**
     * Находит B2B-канал, который привязан к текущему frontend-поселению.
     *
     * Никаких дополнительных helper/resolver классов.
     * Используются только системные классы:
     * - waRouting
     * - shopSalesChannelModel
     * - shopSalesChannelParamsModel
     */
    protected function getCurrentB2bChannel()
    {
        $route_key = $this->getCurrentShopRouteKey();

        if (!$route_key) {
            return null;
        }

        $channel_model = new shopSalesChannelModel();

        // Берем все каналы типа b2b.
        $channels = $channel_model->getByField('type', 'b2b', true);

        if (!$channels) {
            return null;
        }

        $params_model = new shopSalesChannelParamsModel();

        foreach ($channels as $channel) {
            if (empty($channel['status'])) {
                continue;
            }

            $channel_params = $params_model->get((int) $channel['id']);

            // route_key был сохранен в настройках канала продаж.
            if (ifset($channel_params, 'route_key', '') !== $route_key) {
                continue;
            }

            $channel['params'] = $channel_params;

            return $channel;
        }

        return null;
    }

    /**
     * Возвращает ключ текущего shop-поселения:
     * domain|route_id
     *
     * Например:
     * webasyst.loc|2
     *
     * Важно:
     * текущий route не всегда содержит свой ID внутри массива,
     * поэтому route_id определяется через waRouting::getByApp('shop', $domain).
     */
    protected function getCurrentShopRouteKey()
    {
        $routing = wa()->getRouting();

        $domain = $routing->getDomain();
        $current_route = $routing->getRoute();

        if (!$domain || !is_array($current_route)) {
            return null;
        }

        if (ifset($current_route, 'app', '') !== 'shop') {
            return null;
        }

        // Если route_id уже есть в текущем route — используем его.
        if (isset($current_route['_id'])) {
            return $domain . '|' . $current_route['_id'];
        }

        // Иначе ищем route_id по списку shop-поселений текущего домена.
        $routes = $routing->getByApp('shop', $domain);

        foreach ($routes as $route_id => $route) {
            if ($this->isSameRoute($current_route, $route)) {
                return $domain . '|' . $route_id;
            }
        }

        return null;
    }

    /**
     * Сравнивает текущий route с route из списка поселений.
     *
     * Используем минимально надежные признаки:
     * - app
     * - url
     *
     * Этого достаточно для определения settlement внутри одного домена.
     */
    protected function isSameRoute(array $route_a, array $route_b)
    {
        return ifset($route_a, 'app', '') === ifset($route_b, 'app', '')
            && ifset($route_a, 'url', '') === ifset($route_b, 'url', '');
    }
}
