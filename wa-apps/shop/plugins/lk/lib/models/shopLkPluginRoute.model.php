<?php

class shopLkPluginRouteModel extends waModel
{
    protected $table = 'shop_lk_route';

    public function getEnabledByStorefront($domain, $shop_url)
    {
        $domain = shopLkPluginRouteService::normalizeDomain($domain);
        $shop_url = shopLkPluginRouteService::normalizeShopUrl($shop_url);
        $storefront_hash = shopLkPluginRouteService::getStorefrontHash($domain, $shop_url);

        $sql = "SELECT * FROM {$this->table}
                WHERE storefront_hash = s:storefront_hash
                    AND domain = s:domain
                    AND shop_url = s:shop_url
                    AND enabled = 1
                ORDER BY id";
        return $this->query($sql, array(
            'storefront_hash' => $storefront_hash,
            'domain' => $domain,
            'shop_url' => $shop_url,
        ))->fetchAll('id');
    }

    public function getByStorefrontAndRoute($domain, $shop_url, $route)
    {
        $domain = shopLkPluginRouteService::normalizeDomain($domain);
        $shop_url = shopLkPluginRouteService::normalizeShopUrl($shop_url);
        $route = shopLkPluginRouteService::normalizeRoute($route);
        $route_hash = shopLkPluginRouteService::getRouteHash($domain, $shop_url, $route);

        return $this->getByField(array(
            'route_hash' => $route_hash,
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
        $route = shopLkPluginRouteService::normalizeRoute(ifset($data, 'route', 'my'));

        if ($route === '') {
            $route = 'my';
        }

        $row = array(
            'domain' => $domain,
            'shop_url' => $shop_url,
            'route' => $route,
            'storefront_hash' => shopLkPluginRouteService::getStorefrontHash($domain, $shop_url),
            'route_hash' => shopLkPluginRouteService::getRouteHash($domain, $shop_url, $route),
            'name' => trim((string) ifset($data, 'name', '')),
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'b2b_mode' => !empty($data['b2b_mode']) ? 1 : 0,
            'lock_mode' => in_array(ifset($data, 'lock_mode', 'cabinet'), array('cabinet', 'storefront'), true) ? $data['lock_mode'] : 'cabinet',
            'config' => json_encode(ifset($data, 'config', array()), JSON_UNESCAPED_UNICODE),
            'update_datetime' => $now,
        );

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
            $base_route = $row['route'].'-copy';
        }

        $route = $base_route;
        $i = 2;
        while ($this->getByStorefrontAndRoute($row['domain'], $row['shop_url'], $route)) {
            $route = $base_route.'-'.$i;
            $i++;
        }

        unset($row['id']);
        $row['route'] = $route;
        $row['storefront_hash'] = shopLkPluginRouteService::getStorefrontHash($row['domain'], $row['shop_url']);
        $row['route_hash'] = shopLkPluginRouteService::getRouteHash($row['domain'], $row['shop_url'], $route);
        $row['name'] = trim($row['name']) !== '' ? $row['name'].' — копия' : 'Копия кабинета';
        $row['enabled'] = 0;
        $row['create_datetime'] = date('Y-m-d H:i:s');
        $row['update_datetime'] = null;

        $new_id = (int) $this->insert($row);
        (new shopLkPluginPaymentTypeModel())->copyRoutePaymentTypes((int) $id, $new_id);

        return $new_id;
    }
}
