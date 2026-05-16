<?php

class shopB2bPluginChannelMainSettingsService extends shopB2bPluginChannelSettingsService
{
    public function getViewData(array $channel): array
    {
        $params = ifset($channel, 'params', array());

        return array(
            'settings' => array(
                'route_key' => ifset($params, 'b2b_main_route_key', ''),
                'frontend_from_root' => !empty($params['b2b_main_frontend_from_root']),
                'frontend_custom_url' => ifset($params, 'b2b_main_frontend_custom_url', 'b2b'),
                'frontend_url' => ifset($params, 'b2b_main_frontend_url', ''),
                'open_url' => $this->getOpenUrl($params),
            ),
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
            );
        }

        return $data;
    }

    public function validate(int $channel_id, array $settings): array
    {
        $errors = array();

        if (ifset($settings, 'b2b_main_route_key', '') === '') {
            $errors[] = array('field' => 'settings[b2b_main_route_key]', 'error_description' => 'Выберите поселение Shop-Script.');
            return $errors;
        }

        if (!$this->parseRouteKey($settings['b2b_main_route_key'])) {
            $errors[] = array('field' => 'settings[b2b_main_route_key]', 'error_description' => 'Выбранное поселение Shop-Script не найдено.');
            return $errors;
        }

        if (ifset($settings, 'b2b_main_frontend_url', '') === '') {
            $errors[] = array('field' => 'settings[b2b_main_frontend_custom_url]', 'error_description' => 'Укажите URL витрины.');
            return $errors;
        }

        return $errors;
    }

    protected function getOpenUrl(array $params): string
    {
        $route_key = ifset($params, 'b2b_main_route_key', '');
        $route = $this->parseRouteKey($route_key);
        if (!$route) {
            return '';
        }

        $frontend_url = ifset($params, 'b2b_main_frontend_url', '');
        $path = trim(str_replace('*', '', $frontend_url), '/');
        $base = '//' . $route['domain'] . '/';
        $settlement = trim(str_replace('*', '', $route['url']), '/');

        $parts = array_filter(array($settlement, $path), 'strlen');
        return $base . ($parts ? implode('/', $parts) . '/' : '');
    }
}
