<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    // Возвращает штатные поля канала с мелкой настройкой внешнего вида.
    protected function getBaseFieldsConfig(): array
    {
        $base = parent::getBaseFieldsConfig();
        $base['description']['class'] = 'smallest';
        return $base;
    }

    // Рендерит кастомную форму настройки B2B-канала.
    public function getFormHtml(array $channel): string
    {
        $view           = wa('shop')->getView();
        $params         = ifset($channel, 'params', []);
        $from_root      = !empty($params['frontend_from_root']) || ifset($params, 'frontend_url', '') === '*';
        $access_service = new shopB2bPluginCustomerService();

        $access_mode = ifset($params, 'access_mode', 'all');
        $route_key   = ifset($params, 'route_key', '');
        $lk_state    = $this->getShopPersonalAccountStateByRouteKey($route_key);

        $auth_required_available = !empty($lk_state['enabled']);
        $auth_required           = (!empty($params['auth_required']) || !array_key_exists('auth_required', $params)) && $auth_required_available;

        $customer_ids        = $access_service->getIds(ifset($params, 'access_customer_ids', ''));
        $except_customer_ids = $access_service->getIds(ifset($params, 'access_except_customer_ids', ''));
        $category_ids        = $access_service->getIds(ifset($params, 'access_category_ids', ''));

        if (
            $access_mode === 'except_customers'
            && !$except_customer_ids
            && !array_key_exists('access_except_customer_ids', $params)
        ) {
            $except_customer_ids = $customer_ids;
            $customer_ids        = [];
        }

        $view->assign([
            'channel'                    => $channel,
            'base_fields'                => $this->getBaseRenderedFields($channel),
            'route_options'              => $this->getShopRouteOptions(),
            'frontend_from_root'         => $from_root,
            'frontend_custom_url'        => $this->getFrontendCustomUrl($params),
            'auth_required'              => $auth_required,
            'auth_required_available'    => $auth_required_available,
            'auth_required_message'      => ifset($lk_state, 'message', ''),
            'access_mode'                => $access_mode,
            'access_customer_ids'        => $customer_ids,
            'access_except_customer_ids' => $except_customer_ids,
            'access_category_ids'        => $category_ids,
            'access_customers'           => $access_service->getSelectedCustomers($customer_ids),
            'access_except_customers'    => $access_service->getSelectedCustomers($except_customer_ids),
            'customer_categories'        => $access_service->getCustomerCategories(),
            'access_denied_behavior'     => ifset($params, 'access_denied_behavior', 'ignore'),
            'access_denied_page_mode'    => ifset($params, 'access_denied_page_mode', 'plugin'),
            'access_denied_block_id'     => ifset($params, 'access_denied_block_id', ''),
        ]);

        return $view->fetch('file:' . wa()->getAppPath('plugins/b2b/templates/actions/channels/b2b_channel.include.html', 'shop'));
    }

    // Рендерит базовые поля канала через стандартный механизм Shop-Script.
    protected function getBaseRenderedFields(array $channel): array
    {
        $result = [];

        if (empty($channel['id'])) {
            $channel['name'] = $this->get('name');
        }

        $field_params = ['namespace' => 'data'] + $this->getFormFieldParams();

        foreach ($this->getBaseFieldsConfig() as $name => $row) {
            $result[$name] = $this->getControl($name, ifset($channel, $name, ''), $field_params + $row);
        }

        return $result;
    }

    // Проверяет и нормализует параметры канала перед сохранением.
    public function sanitizeAndValidateParams(?int $id, array &$params, $params_mode): array
    {
        $errors = [];

        $params['route_key']          = trim((string) ifset($params, 'route_key', ''));
        $params['frontend_from_root'] = !empty($params['frontend_from_root']) ? 1 : 0;
        $params['auth_required']      = !empty($params['auth_required']) ? 1 : 0;

        $access_service = new shopB2bPluginCustomerService();

        $params['access_mode']             = ifset($params, 'access_mode', 'all');
        $params['access_denied_behavior']  = ifset($params, 'access_denied_behavior', 'ignore');
        $params['access_denied_page_mode'] = ifset($params, 'access_denied_page_mode', 'plugin');
        $params['access_denied_block_id']  = trim((string) ifset($params, 'access_denied_block_id', ''));

        if (!in_array($params['access_mode'], ['all', 'except_customers', 'customers', 'categories'], true)) {
            $params['access_mode'] = 'all';
        }

        if (!in_array($params['access_denied_behavior'], ['ignore', 'page'], true)) {
            $params['access_denied_behavior'] = 'ignore';
        }

        if (!in_array($params['access_denied_page_mode'], ['plugin', 'block'], true)) {
            $params['access_denied_page_mode'] = 'plugin';
        }

        if (
            $params['access_denied_behavior'] === 'page'
            && $params['access_denied_page_mode'] === 'block'
            && $params['access_denied_block_id'] === ''
        ) {
            $errors[] = [
                'field'             => 'data[params][access_denied_block_id]',
                'error_description' => 'Укажите ID блока для страницы ограничения доступа.',
            ];

            return $errors;
        }

        if (
            $params['access_denied_behavior'] === 'page'
            && $params['access_denied_page_mode'] === 'block'
            && !$this->isValidBlockId($params['access_denied_block_id'])
        ) {
            $errors[] = [
                'field'             => 'data[params][access_denied_block_id]',
                'error_description' => 'ID блока может содержать только латинские буквы, цифры, дефис, подчёркивание и точку.',
            ];

            return $errors;
        }

        $customer_ids        = $access_service->getIds(ifset($params, 'access_customer_ids', []));
        $except_customer_ids = $access_service->getIds(ifset($params, 'access_except_customer_ids', []));
        $category_ids        = $access_service->getIds(ifset($params, 'access_category_ids', []));

        // Backward compatibility: старый blacklist мог прийти в access_customer_ids.
        if (
            $params['access_mode'] === 'except_customers'
            && !$except_customer_ids
            && !array_key_exists('access_except_customer_ids', $params)
        ) {
            $except_customer_ids = $customer_ids;
            $customer_ids        = [];
        }

        if ($params['access_mode'] === 'except_customers' && !$except_customer_ids) {
            $errors[] = [
                'field'             => 'data[params][access_except_customer_ids][]',
                'error_description' => 'Выберите контакты, которым нужно запретить доступ.',
            ];

            return $errors;
        }

        if ($params['access_mode'] === 'customers' && !$customer_ids) {
            $errors[] = [
                'field'             => 'data[params][access_customer_ids][]',
                'error_description' => 'Выберите контакты, которым разрешён доступ.',
            ];

            return $errors;
        }

        if ($params['access_mode'] === 'categories' && !$category_ids) {
            $errors[] = [
                'field'             => 'data[params][access_category_ids][]',
                'error_description' => 'Выберите категории покупателей, которым разрешён доступ.',
            ];

            return $errors;
        }

        $params['access_customer_ids']        = json_encode($customer_ids);
        $params['access_except_customer_ids'] = json_encode($except_customer_ids);
        $params['access_category_ids']        = json_encode($category_ids);

        if ($params['route_key'] === '') {
            $errors[] = [
                'field'             => 'data[params][route_key]',
                'error_description' => 'Выберите поселение Shop-Script.',
            ];

            return $errors;
        }

        $route = $this->parseRouteKey($params['route_key']);

        if (!$route) {
            $errors[] = [
                'field'             => 'data[params][route_key]',
                'error_description' => 'Выбранное поселение Shop-Script не найдено.',
            ];

            return $errors;
        }

        $lk_state = $this->getShopPersonalAccountStateByDomain($route['domain']);
        if (empty($lk_state['enabled'])) {
            $params['auth_required'] = 0;
        }

        $custom_url = $this->normalizeFrontendCustomUrl(ifset($params, 'frontend_custom_url', ''));

        if ($custom_url === '') {
            $custom_url = $this->normalizeFrontendCustomUrl(ifset($params, 'frontend_url', ''));
        }

        if ($custom_url === '') {
            $custom_url = 'b2b';
        }

        // Храним последний пользовательский URL отдельно, чтобы он не терялся в режиме от корня.
        $params['frontend_custom_url'] = $custom_url;

        if ($params['frontend_from_root']) {
            $params['frontend_url'] = '*';
        } else {
            $params['frontend_url'] = $custom_url === '' ? '*' : $custom_url . '/*';
        }

        // Дублируем данные поселения в params канала для быстрого чтения после сохранения.
        $params['domain']     = $route['domain'];
        $params['route_id']   = $route['route_id'];
        $params['route_url']  = $route['url'];
        $params['settlement'] = $route['settlement'];

        return $errors;
    }

    // Нормализует пользовательский URL для хранения и показа в поле ввода.
    protected function normalizeFrontendCustomUrl($url): string
    {
        $url = trim((string) $url);
        $url = str_replace('*', '', $url);
        $url = trim($url, '/');

        if ($url === '') {
            return '';
        }

        $url = preg_replace('/[^a-zа-я0-9\-]/ui', '', $url);
        $url = mb_strtolower($url, 'UTF-8');

        return $url;
    }

    // Проверяет ID блока приложения «Сайт» перед передачей в $wa->block().
    protected function isValidBlockId($block_id): bool
    {
        $block_id = trim((string) $block_id);

        return $block_id !== '' && preg_match('/^[a-z0-9_.-]+$/i', $block_id);
    }

    // Возвращает последний пользовательский URL без routing-mask.
    protected function getFrontendCustomUrl(array $params): string
    {
        $url = $this->normalizeFrontendCustomUrl(ifset($params, 'frontend_custom_url', ''));

        if ($url !== '') {
            return $url;
        }

        $url = ifset($params, 'frontend_url', '');

        if ($url !== '*') {
            $url = $this->normalizeFrontendCustomUrl($url);

            if ($url !== '') {
                return $url;
            }
        }

        return 'b2b';
    }

    // Проверяет доступность личного кабинета Shop-Script для route_key вида domain|route_id.
    protected function getShopPersonalAccountStateByRouteKey($route_key): array
    {
        $route_key = trim((string) $route_key);

        if ($route_key === '') {
            return [
                'enabled' => false,
                'message' => 'Выберите поселение Shop-Script, чтобы проверить доступность личного кабинета.',
            ];
        }

        $route = $this->parseRouteKey($route_key);

        if (!$route) {
            return [
                'enabled' => false,
                'message' => 'Выбранное поселение Shop-Script не найдено.',
            ];
        }

        return $this->getShopPersonalAccountStateByDomain($route['domain']);
    }

    // Проверяет, что в настройках сайта для домена включены авторизация и личный кабинет Shop-Script.
    protected function getShopPersonalAccountStateByDomain($domain): array
    {
        $domain = trim((string) $domain);

        if ($domain === '') {
            return [
                'enabled' => false,
                'message' => 'Домен поселения не определён.',
            ];
        }

        $auth_config = wa()->getAuthConfig($domain);
        if (empty($auth_config['auth'])) {
            return [
                'enabled' => false,
                'message' => 'На сайте для этого домена выключена авторизация. Включите авторизацию и личный кабинет Shop-Script в приложении «Сайт».',
            ];
        }

        $domain_config_path = wa('site')->getConfig()->getConfigPath('domains/' . $domain . '.php');
        $domain_config      = file_exists($domain_config_path) ? include($domain_config_path) : [];

        if (isset($domain_config['personal']['shop']) && empty($domain_config['personal']['shop'])) {
            return [
                'enabled' => false,
                'message' => 'Личный кабинет Shop-Script выключен в настройках сайта для этого домена. Включите его в приложении «Сайт».',
            ];
        }

        return [
            'enabled' => true,
            'message' => '',
        ];
    }

    // Выполняется после сохранения канала.
    public function onSave(array $channel) {}

    // Формирует список shop-поселений для select.
    protected function getShopRouteOptions(): array
    {
        $options = [
            [
                'value'                    => '',
                'title'                    => 'Выберите поселение',
                'domain'                   => '',
                'route_id'                 => '',
                'route_url'                => '',
                'personal_account_enabled' => false,
                'personal_account_message' => 'Выберите поселение Shop-Script, чтобы проверить доступность личного кабинета.',
            ],
        ];

        $routing = wa()->getRouting();

        foreach ($routing->getDomains() as $domain) {
            $routes   = $routing->getByApp('shop', $domain);
            $lk_state = $this->getShopPersonalAccountStateByDomain($domain);

            foreach ($routes as $route_id => $route) {
                $url = trim((string) ifset($route, 'url', ''));

                $options[] = [
                    'value'                    => $domain . '|' . $route_id,
                    'title'                    => $this->formatSettlementTitle($domain, $url),
                    'domain'                   => $domain,
                    'route_id'                 => $route_id,
                    'route_url'                => $url,
                    'personal_account_enabled' => !empty($lk_state['enabled']),
                    'personal_account_message' => ifset($lk_state, 'message', ''),
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

        $domain   = trim($domain);
        $route_id = trim($route_id);

        if ($domain === '' || $route_id === '') {
            return null;
        }

        $routes = wa()->getRouting()->getByApp('shop', $domain);

        if (!isset($routes[$route_id])) {
            return null;
        }

        $route = $routes[$route_id];
        $url   = trim((string) ifset($route, 'url', ''));

        return [
            'domain'     => $domain,
            'route_id'   => $route_id,
            'url'        => $url,
            'settlement' => $this->formatSettlementTitle($domain, $url),
            'route'      => $route,
        ];
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
