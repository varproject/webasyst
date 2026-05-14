<?php

class shopLkPluginRouteModel extends waModel
{
    protected $table = 'shop_lk_route';

    public function getByStorefront($domain, $shop_url)
    {
        $hash = shopLkPluginRouteService::getStorefrontHash($domain, $shop_url);
        return $this->getByField('storefront_hash', $hash);
    }

    public function getEnabledByStorefront($domain, $shop_url)
    {
        $row = $this->getByStorefront($domain, $shop_url);
        if (!$row || empty($row['enabled'])) {
            return array();
        }
        return array((int) $row['id'] => $row);
    }

    public function decodeConfig($row)
    {
        $config = !empty($row['config']) ? json_decode($row['config'], true) : array();
        return is_array($config) ? $config : array();
    }

    public function getAllByStorefrontHash()
    {
        return $this->select('*')->order('domain, shop_url')->fetchAll('storefront_hash');
    }

    public function saveRoute(array $data)
    {
        $now = date('Y-m-d H:i:s');

        $domain = shopLkPluginRouteService::normalizeDomain(ifset($data, 'domain', ''));
        $shop_url = shopLkPluginRouteService::normalizeShopUrl(ifset($data, 'shop_url', ''));
        $b2b_mode = !empty($data['b2b_mode']) ? 1 : 0;
        $route = shopLkPluginRouteService::normalizeRoute(ifset($data, 'route', ''), $b2b_mode ? '' : 'my');

        if ($b2b_mode) {
            $route = '';
        } elseif ($route === '') {
            $route = 'my';
        }

        $storefront_hash = shopLkPluginRouteService::getStorefrontHash($domain, $shop_url);

        $row = array(
            'domain' => $domain,
            'shop_url' => $shop_url,
            'route' => $route,
            'storefront_hash' => $storefront_hash,
            'route_hash' => shopLkPluginRouteService::getRouteHash($domain, $shop_url, $route),
            'name' => trim((string) ifset($data, 'name', '')),
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'b2b_mode' => $b2b_mode,
            'lock_mode' => $b2b_mode ? 'storefront' : 'cabinet',
            'config' => json_encode(ifset($data, 'config', array()), JSON_UNESCAPED_UNICODE),
            'update_datetime' => $now,
        );

        $old = $this->getByStorefront($domain, $shop_url);
        if ($old) {
            $this->updateById($old['id'], $row);
            return (int) $old['id'];
        }

        $row['create_datetime'] = $now;
        return (int) $this->insert($row);
    }
}
