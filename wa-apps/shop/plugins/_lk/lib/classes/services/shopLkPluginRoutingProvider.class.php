<?php

final class shopLkPluginRoutingProvider
{
    public static function getRouting()
    {
        if (wa()->getEnv() !== 'frontend') {
            return array();
        }

        if (!shopLkPluginRouteService::isPluginEnabled()) {
            return null;
        }

        $route = shopLkPluginRouteService::getRouteForCurrentStorefront(true);
        if (!$route) {
            return null;
        }

        $route_id = (int) $route['id'];
        $secure = true;
        $public = false;
        $routes = array();

        if (!empty($route['b2b_mode'])) {
            // B2B root mode owns the whole Shop-Script settlement.
            // Do not use '?' as an empty route. waRouting treats it as raw regex and emits
            // "quantifier does not follow a repeatable item". Numeric keys with explicit url
            // avoid collision with Shop-Script core ''/login/signup routes during array_merge().
            $routes[] = array('url' => '', 'module' => 'frontend', 'action' => 'dashboard', 'secure' => $secure, '_lk_route_id' => $route_id);
            $routes[self::literalRoute('login').'/?'] = array('module' => 'frontend', 'action' => 'login', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[self::literalRoute('signup').'/?'] = array('module' => 'frontend', 'action' => 'signup', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[self::literalRoute('forgotpassword').'/?'] = array('module' => 'frontend', 'action' => 'forgot', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[self::literalRoute('setpassword').'/?'] = array('module' => 'frontend', 'action' => 'forgot', 'secure' => $public, '_lk_route_id' => $route_id);

            $prefix = '';
        } else {
            // Cabinet-only mode lives under a root segment. Use a placeholder route even for
            // default "my" so Shop-Script core my/? routes do not overwrite plugin routes by
            // identical associative keys when shopConfig merges plugin routes with core routes.
            $root = trim((string) ifset($route, 'route', 'my'), '/');
            if ($root === '') {
                $root = 'my';
            }
            $prefix = self::literalRoute($root).'/';

            $routes[$prefix.'login/?'] = array('module' => 'frontend', 'action' => 'login', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[$prefix.'signup/?'] = array('module' => 'frontend', 'action' => 'signup', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[$prefix.'forgotpassword/?'] = array('module' => 'frontend', 'action' => 'forgot', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[$prefix.'setpassword/?'] = array('module' => 'frontend', 'action' => 'forgot', 'secure' => $public, '_lk_route_id' => $route_id);
            $routes[$prefix.'?'] = array('module' => 'frontend', 'action' => 'dashboard', 'secure' => $secure, '_lk_route_id' => $route_id);
        }

        $routes[$prefix.'companies/?'] = array('module' => 'frontend', 'action' => 'companies', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'companies/save/?'] = array('module' => 'frontend', 'action' => 'companySave', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'company/select/?'] = array('module' => 'frontend', 'action' => 'companySelect', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'addresses/?'] = array('module' => 'frontend', 'action' => 'addresses', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'addresses/save/?'] = array('module' => 'frontend', 'action' => 'addressSave', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'payments/?'] = array('module' => 'frontend', 'action' => 'payments', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'payments/save/?'] = array('module' => 'frontend', 'action' => 'paymentSelect', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'orders/?'] = array('module' => 'frontend', 'action' => 'orders', 'secure' => $secure, '_lk_route_id' => $route_id);
        $routes[$prefix.'*'] = array('module' => 'frontend', 'action' => 'dashboard', 'secure' => $secure, '_lk_route_id' => $route_id);

        return $routes;
    }

    protected static function literalRoute($segment)
    {
        $segment = trim((string) $segment, '/');
        if ($segment === '') {
            return '';
        }

        // Keep route key unique to prevent Shop-Script core routes from overwriting plugin
        // routes with the same literal key, e.g. my/?. The regex stays a literal segment.
        return '<lk_root:'.str_replace('>', '', $segment).'>';
    }
}
