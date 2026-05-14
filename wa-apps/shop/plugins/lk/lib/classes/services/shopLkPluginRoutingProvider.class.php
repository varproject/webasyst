<?php

final class shopLkPluginRoutingProvider
{
    public static function getRouting()
    {
        if (!shopLkPluginRouteService::isPluginEnabled()) {
            return null;
        }

        $routes = array();
        foreach (shopLkPluginRouteService::getRoutesForCurrentStorefront() as $route) {
            $prefix = trim($route['route'], '/');
            if ($prefix !== '') {
                $prefix .= '/';
            }
            $route_id = (int) $route['id'];
            $secure = true;

            $routes[$prefix.'?'] = array('module' => 'frontend', 'action' => 'dashboard', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'companies/?'] = array('module' => 'frontend', 'action' => 'companies', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'companies/save/?'] = array('module' => 'frontend', 'action' => 'companySave', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'company/select/?'] = array('module' => 'frontend', 'action' => 'companySelect', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'addresses/?'] = array('module' => 'frontend', 'action' => 'addresses', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'addresses/save/?'] = array('module' => 'frontend', 'action' => 'addressSave', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'payments/?'] = array('module' => 'frontend', 'action' => 'payments', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'payments/save/?'] = array('module' => 'frontend', 'action' => 'paymentSelect', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'orders/?'] = array('module' => 'frontend', 'action' => 'orders', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[$prefix.'*'] = array('module' => 'frontend', 'action' => 'dashboard', 'secure' => $secure, '_lk_route_id' => $route_id);
        }

        return $routes ?: null;
    }
}
