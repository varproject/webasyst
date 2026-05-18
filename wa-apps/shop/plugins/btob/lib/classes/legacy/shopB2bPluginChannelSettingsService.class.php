<?php

class shopBtobPluginChannelSettingsService
{
    protected shopSalesChannelModel $channel_model;
    protected shopSalesChannelParamsModel $params_model;

    public function __construct()
    {
        $this->channel_model = new shopSalesChannelModel();
        $this->params_model = new shopSalesChannelParamsModel();
    }

    public function getChannel(int $channel_id): array
    {
        $channel = $this->channel_model->getById($channel_id);
        if (!$channel || ifset($channel, 'type', '') !== 'btob') {
            throw new waException('B2B-канал не найден.', 404);
        }

        $channel['params'] = $this->getParams($channel_id);
        return $channel;
    }

    public function getParams(int $channel_id): array
    {
        return $this->params_model->get($channel_id);
    }

    public function updateParams(int $channel_id, array $params): void
    {
        $this->params_model->update($channel_id, $params);
    }

    public function getViewData(array $channel): array
    {
        return array('settings' => ifset($channel, 'params', array()));
    }

    public function normalize(array $input): array
    {
        return $input;
    }

    public function validate(int $channel_id, array $settings): array
    {
        return array();
    }

    public function save(int $channel_id, array $input): void
    {
        $this->updateParams($channel_id, $this->normalize($input));
    }

    public function getIdList($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        }

        $ids = array_filter(array_map('intval', (array) $value));
        return array_values(array_unique($ids));
    }

    public function encodeIdList($value): string
    {
        return json_encode($this->getIdList($value));
    }

    public function getBool($value): int
    {
        return !empty($value) ? 1 : 0;
    }

    public function normalizeSlug($value, string $default = ''): string
    {
        $value = trim((string) $value);
        $value = str_replace('*', '', $value);
        $value = trim($value, '/');

        if ($value === '') {
            return $default;
        }

        $value = preg_replace('/[^a-zа-я0-9\-]/ui', '', $value);
        return mb_strtolower($value, 'UTF-8');
    }

    public function getShopRouteOptions(): array
    {
        $options = array(array(
            'value' => '',
            'title' => 'Выберите поселение',
            'domain' => '',
            'route_id' => '',
            'route_url' => '',
        ));

        $routing = wa()->getRouting();
        foreach ($routing->getDomains() as $domain) {
            foreach ($routing->getByApp('shop', $domain) as $route_id => $route) {
                $url = trim((string) ifset($route, 'url', ''));
                $options[] = array(
                    'value' => $domain . '|' . $route_id,
                    'title' => $this->formatSettlementTitle($domain, $url),
                    'domain' => $domain,
                    'route_id' => $route_id,
                    'route_url' => $url,
                );
            }
        }

        return $options;
    }

    public function parseRouteKey($route_key): ?array
    {
        if (strpos((string) $route_key, '|') === false) {
            return null;
        }

        list($domain, $route_id) = explode('|', (string) $route_key, 2);
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

        return array(
            'domain' => $domain,
            'route_id' => $route_id,
            'url' => $url,
            'settlement' => $this->formatSettlementTitle($domain, $url),
            'route' => $route,
        );
    }

    protected function formatSettlementTitle($domain, $url): string
    {
        $url = trim(str_replace('*', '', (string) $url), '/');
        return $url === '' ? $domain . '/' : $domain . '/' . $url . '/';
    }

    public function validateBlockId($block_id): bool
    {
        $block_id = trim((string) $block_id);
        return $block_id !== '' && preg_match('/^[a-z0-9_.-]+$/i', $block_id);
    }

    protected function getDomainConfig(string $domain): array
    {
        $path = wa()->getConfig()->getConfigPath('domains/' . $domain . '.php', true, 'site');
        return file_exists($path) ? (array) include($path) : array();
    }
}
