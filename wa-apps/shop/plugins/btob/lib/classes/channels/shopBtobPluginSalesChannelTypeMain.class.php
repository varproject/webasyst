<?php

class shopBtobPluginSalesChannelTypeMain implements shopBtobPluginSalesChannelTypeInterface
{
    public function renderForm(waSmarty3View $view, array $channel, array $settings): void
    {
        $route_options = $this->getShopRouteOptions();

        // dd($settings);

        $view->assign([
            'routes' => $route_options,
        ]);
    }

    public function validateParams(?int $id, array &$params, string $params_mode): array
    {
        // Обязательный выбор поселения
        if (empty($params['btob_main_route_key'])) {
            $errors['btob_main_route_key'] = [
                'error_description' => 'Выберите поселение.',
                'field' => 'data[params][btob_main_route_key]',
            ];
        }

        return $errors ?? [];
    }





    protected function normalizeParams(array $params): mixed
    {
        return '';
    }

    protected function getShopRouteOptions(): array
    {
        $options = array([
            'value' => '',
            'title' => 'Выберите поселение',
            'domain' => '',
            'route_id' => '',
            'route_url' => '',
        ]);

        $routing = wa()->getRouting()->getByApp('shop');

        foreach ($routing as $domain => $routes) {
            foreach ($routes as $route_id => $route) {
                $clean_url = waRouting::clearUrl($route['url']);
                $options[] = [
                    'value'     => $domain . '_' . $route_id,
                    'title'     => $domain . '/' . $clean_url,
                    'domain'    => $domain,
                    'route_id'  => $route_id,
                    'route_url' => $route['url'],
                ];
            }
        }

        return $options;
    }
}
