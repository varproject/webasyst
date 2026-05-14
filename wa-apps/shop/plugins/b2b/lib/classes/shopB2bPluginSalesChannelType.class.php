<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    // Штатные поля.
    protected function getBaseFieldsConfig(): array
    {
        $res = parent::getBaseFieldsConfig();
        $res['description']['class'] = 'smallest';

        return $res;
    }

    public function getFormHtml(array $channel): string
    {
        try {
            $view = wa('shop')->getView();

            $template = wa()->getAppPath('plugins/b2b/templates/actions/B2bSalesChannelForm.html', 'shop');

            if (!file_exists($template)) {
                throw new waException('Template not found: ' . $template);
            }

            $params = ifset($channel, 'params', []);

            $frontend_from_root = !empty($params['frontend_from_root'])
                || ifset($params, 'frontend_url', '') === '*';

            $view->assign([
                'channel' => $channel,
                'base_fields' => $this->getBaseRenderedFields($channel),
                'route_options' => $this->getShopRouteOptions(),
                'frontend_from_root' => $frontend_from_root,
                'frontend_custom_url' => $this->getFrontendCustomUrl($params),
                'auth_required' => !empty($params['auth_required']) || !array_key_exists('auth_required', $params),
            ]);

            return $view->fetch('file:' . $template);
        } catch (Exception $e) {
            waLog::log(
                $e->getMessage() . "\n" . $e->getTraceAsString(),
                'shop/plugins/b2b/sales-channel-form.log'
            );

            throw $e;
        }
    }

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

        $params['route_key'] = trim((string) ifset($params, 'route_key', ''));
        $params['frontend_from_root'] = !empty($params['frontend_from_root']) ? 1 : 0;
        $params['auth_required'] = !empty($params['auth_required']) ? 1 : 0;

        if ($params['route_key'] === '') {
            $errors[] = [
                'field' => 'data[params][route_key]',
                'error_description' => 'Выберите поселение Shop-Script.',
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

        $custom_url = $this->normalizeFrontendCustomUrl(ifset($params, 'frontend_custom_url', ''));

        if ($custom_url === '') {
            $custom_url = $this->normalizeFrontendCustomUrl(ifset($params, 'frontend_url', ''));
        }

        if ($custom_url === '') {
            $custom_url = 'b2b';
        }

        // Последний пользовательский URL храним отдельно, чтобы он не терялся при режиме "от корня".
        $params['frontend_custom_url'] = $custom_url;

        if ($params['frontend_from_root']) {
            // Канал забирает корень выбранного поселения.
            // frontend_custom_url при этом сохраняется.
            $params['frontend_url'] = '*';
        } else {
            $params['frontend_url'] = $this->normalizeFrontendUrl($custom_url);
        }

        // Дублируем данные поселения в params канала.
        $params['domain'] = $route['domain'];
        $params['route_id'] = $route['route_id'];
        $params['route_url'] = $route['url'];
        $params['settlement'] = $route['settlement'];

        return $errors;
    }

    // Нормализует route pattern внутри поселения для routing.
    protected function normalizeFrontendUrl($url): string
    {
        $url = $this->normalizeFrontendCustomUrl($url);

        if ($url === '') {
            return '*';
        }

        return $url . '/*';
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

    // Возвращает последний пользовательский URL без routing-mask `/*`.
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

    // Дополнительные действия после сохранения канала сейчас не требуются.
    public function onSave(array $channel) {}

    // Формирует список shop-поселений для select.
    protected function getShopRouteOptions(): array
    {
        $options = [
            [
                'value' => '',
                'title' => 'Выберите поселение',
                'domain' => '',
                'route_id' => '',
                'route_url' => '',
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
                    'domain' => $domain,
                    'route_id' => $route_id,
                    'route_url' => $url,
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
