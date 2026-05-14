<?php

class shopLkPluginRouteModel extends waModel
{
    protected $table = 'shop_lk_route';

    public function getEnabledByStorefront($domain, $shop_url)
    {
        $domain = shopLkPluginRouteService::normalizeDomain($domain);
        $shop_url = shopLkPluginRouteService::normalizeShopUrl($shop_url);
        $storefront_key = $this->getStorefrontKey($domain, $shop_url);

        $sql = "SELECT * FROM {$this->table}
            WHERE storefront_key = s:storefront_key AND enabled = 1
            ORDER BY id";

        return $this->query($sql, array(
            'storefront_key' => $storefront_key,
        ))->fetchAll('id');
    }

    public function getByStorefrontAndRoute($domain, $shop_url, $route)
    {
        $domain = shopLkPluginRouteService::normalizeDomain($domain);
        $shop_url = shopLkPluginRouteService::normalizeShopUrl($shop_url);

        return $this->getByField(array(
            'storefront_key' => $this->getStorefrontKey($domain, $shop_url),
            'route' => shopLkPluginRouteService::normalizeRoute($route),
        ));
    }

    public function decodeConfig($row)
    {
        $config = !empty($row['config']) ? json_decode($row['config'], true) : array();
        return is_array($config) ? $config : array();
    }

    public function saveRoute(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ifset($data, 'id', 0);
        $domain = shopLkPluginRouteService::normalizeDomain(ifset($data, 'domain', ''));
        $shop_url = shopLkPluginRouteService::normalizeShopUrl(ifset($data, 'shop_url', ''));
        $row = array(
            'domain' => $domain,
            'shop_url' => $shop_url,
            'storefront_key' => $this->getStorefrontKey($domain, $shop_url),
            'route' => shopLkPluginRouteService::normalizeRoute(ifset($data, 'route', 'my')),
        );

        if ($row['route'] === '') {
            $row['route'] = 'my';
        }

        if ($id > 0 && $this->getById($id)) {
            $this->updateById($id, $row);
            return $id;
        }

        $row['create_datetime'] = $now;
        return (int) $this->insert($row);
    }

    public function duplicate($id, $new_route = '')
    {
        $row = $this->getById((int) $id);
        if (!$row) {
            throw new waException('Маршрут не найден.', 404);
        }

        $base_route = shopLkPluginRouteService::normalizeRoute($new_route);
        if ($base_route === '') {
            $base_route = $row['route'] . '-copy';
        }

        $route = $base_route;
        $i = 2;
        while ($this->getByStorefrontAndRoute($row['domain'], $row['shop_url'], $route)) {
            $route = $base_route . '-' . $i;
            $i++;
        }

        unset($row['id']);
        $row['route'] = $route;
        $row['name'] = trim($row['name']) !== '' ? $row['name'] . ' — копия' : 'Копия кабинета';
        $row['enabled'] = 0;
        $row['create_datetime'] = date('Y-m-d H:i:s');
        $row['update_datetime'] = null;

        $new_id = (int) $this->insert($row);
        (new shopLkPluginPaymentTypeModel())->copyRoutePaymentTypes((int) $id, $new_id);

        return $new_id;
    }

    protected function getStorefrontKey($domain, $shop_url)
    {
        return shopLkPluginRouteService::getStorefrontKey($domain, $shop_url);
    }
}
