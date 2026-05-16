<?php

class shopB2bPluginChannelMainSettingsService extends shopB2bPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());
        $settings = array(
            'route_key' => ifset($params, 'b2b_main_route_key', ''),
            'frontend_from_root' => !empty($params['b2b_main_frontend_from_root']),
            'frontend_custom_url' => ifset($params, 'b2b_main_frontend_custom_url', 'b2b'),
            'frontend_url' => ifset($params, 'b2b_main_frontend_url', ''),
        );
        $settings['open_url'] = $this->getOpenUrl($params);

        return array(
            'settings' => $settings,
            'route_options' => $this->getShopRouteOptions(),
        );
    }

    public function sanitizeSalesChannelParams(?int $channel_id, array &$params): array
    {
        $current = $channel_id ? $this->getParams($channel_id) : array();
        $input = array_merge($current, $params);
        $normalized = $this->normalize($input);
        $errors = $this->validate($channel_id ?: 0, $normalized);

        if ($errors) {
            return $errors;
        }

        $params = array_merge($current, $params, $normalized);
        return array();
    }

    public function save(int $channel_id, array $input): void
    {
        $normalized = $this->normalize($input);
        $this->updateParams($channel_id, $normalized);
    }

    public function normalize(array $input): array
    {
        $route_key = trim((string) ifset($input, 'b2b_main_route_key', ifset($input, 'route_key', '')));
        $from_root = $this->getBool(ifset($input, 'b2b_main_frontend_from_root', ifset($input, 'frontend_from_root', 0)));
        $custom_url = $this->normalizeSlug(ifset($input, 'b2b_main_frontend_custom_url', ifset($input, 'frontend_custom_url', 'b2b')), 'b2b');
        $frontend_url = $from_root ? '*' : $custom_url . '/*';

        $data = array(
            'b2b_version' => '2',
            'b2b_main_route_key' => $route_key,
            'b2b_main_frontend_from_root' => $from_root,
            'b2b_main_frontend_custom_url' => $custom_url,
            'b2b_main_frontend_url' => $frontend_url,

            // Дубли для системного списка каналов и быстрого чтения.
            'route_key' => $route_key,
            'frontend_from_root' => $from_root,
            'frontend_custom_url' => $custom_url,
            'frontend_url' => $frontend_url,
        );

        $route = $this->parseRouteKey($route_key);
        if ($route) {
            $data += array(
                'domain' => $route['domain'],
                'route_id' => $route['route_id'],
                'route_url' => $route['url'],
                'settlement' => $route['settlement'],
                'b2b_main_open_url' => $this->buildOpenUrl($data),
            );
        }

        return $data;
    }

    public function validate(int $channel_id, array $settings): array
    {
        $errors = array();

        if (ifset($settings, 'b2b_main_route_key', '') === '') {
            $errors[] = array('field' => 'data[params][b2b_main_route_key]', 'error_description' => 'Выберите поселение Shop-Script.');
            return $errors;
        }

        $route = $this->parseRouteKey($settings['b2b_main_route_key']);
        if (!$route) {
            $errors[] = array('field' => 'data[params][b2b_main_route_key]', 'error_description' => 'Выбранное поселение Shop-Script не найдено.');
            return $errors;
        }

        if (ifset($settings, 'b2b_main_frontend_url', '') === '') {
            $errors[] = array('field' => 'data[params][b2b_main_frontend_custom_url]', 'error_description' => 'Укажите URL витрины.');
            return $errors;
        }

        if (!empty($settings['b2b_main_frontend_from_root'])) {
            $errors[] = array(
                'field' => 'data[params][b2b_main_frontend_from_root]',
                'error_description' => 'Открытие B2B-витрины от корня выбранного поселения сейчас конфликтует с основным маршрутом Shop-Script. Укажите URL витрины, например b2b.'
            );
            return $errors;
        }

        $errors = array_merge($errors, $this->validateShopPersonal($route['domain']));
        return $errors;
    }

    public function buildOpenUrl(array $params): string
    {
        $route_key = ifset($params, 'b2b_main_route_key', '');
        $route = $this->parseRouteKey($route_key);
        if (!$route) {
            return '';
        }

        $frontend_url = ifset($params, 'b2b_main_frontend_url', '');
        $path = trim(str_replace('*', '', $frontend_url), '/');
        $settlement = trim(str_replace('*', '', $route['url']), '/');
        $parts = array_filter(array($settlement, $path), 'strlen');
        $protocol = waRequest::isHttps() ? 'https://' : 'http://';

        return $protocol . $route['domain'] . '/' . ($parts ? implode('/', $parts) . '/' : '');
    }

    protected function getOpenUrl(array $params): string
    {
        return $this->buildOpenUrl($params);
    }

    protected function validateShopPersonal(string $domain): array
    {
        $errors = array();
        $auth_config = wa()->getAuthConfig($domain);

        if (empty($auth_config['auth'])) {
            $errors[] = array(
                'field' => 'data[params][b2b_main_route_key]',
                'error_description' => 'Для выбранного домена не включена авторизация в приложении «Сайт». Включите авторизацию и личный кабинет Shop-Script для домена.'
            );
            return $errors;
        }

        if (!empty($auth_config['app']) && $auth_config['app'] !== 'shop') {
            $errors[] = array(
                'field' => 'data[params][b2b_main_route_key]',
                'error_description' => 'Авторизация выбранного домена привязана не к Shop-Script. Выберите Shop-Script как приложение личного кабинета в настройках домена.'
            );
        }

        $domain_config = $this->getDomainConfig($domain);
        if (isset($domain_config['personal']['shop']) && empty($domain_config['personal']['shop'])) {
            $errors[] = array(
                'field' => 'data[params][b2b_main_route_key]',
                'error_description' => 'Личный кабинет Shop-Script отключён для выбранного домена в приложении «Сайт».'
            );
        }

        return $errors;
    }
}
