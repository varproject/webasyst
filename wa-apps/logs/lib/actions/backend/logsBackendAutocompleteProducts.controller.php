<?php

class logsBackendAutocompleteProductsController extends logsBackendAutocompleteController
{
    private $installed_products = [];

    public function execute()
    {
        if (!logsLicensing::check()->hasPremiumLicense()) {
            return [];
        }

        $query = waRequest::get('term', '');

        $this->installed_products = logsHelper::getInstalledProducts();
        $items = $this->getProductsByQuery($query);
        $response = $this->getResults($items);

        $this->response($response);
    }

    private function getProductsByQuery($query)
    {
        $prepared_query = trim(mb_strtolower($query));

        if (!strlen($prepared_query)) {
            return [];
        }

        // find Shop-Script by "Shop-Script" query
        if (mb_strlen($prepared_query) > 4
            && mb_strlen($prepared_query) <= 9
            && strpos('shop-script', $prepared_query) === 0
        ) {
            $prepared_query = 'shop';
        }

        $products = array_filter(
            $this->installed_products,
            function($product, $slug) use ($prepared_query) {
                if ($product['type'] == 'apps') {
                    $clean_slug = $slug;
                } else {
                    $clean_slug = end(ref(explode('/', $slug)));
                }

                return mb_strpos(mb_strtolower($product['title']), $prepared_query) !== false
                    || mb_strpos($clean_slug, $prepared_query) !== false;
            },
            ARRAY_FILTER_USE_BOTH
        );

        return $products;
    }

    protected function getResultsContents($cut_items)
    {
        $result = [];

        foreach ($cut_items as $slug => $value) {
            if ($value['type'] == 'apps') {
                if ($slug == 'webasyst') {
                    $label = '<strong>Webasyst</strong>';
                } else {
                    $label = sprintf(
                        '<strong>%s</strong> <span class="gray">%s</span>',
                        $value['title'],
                        _w('app')
                    );
                }
            } elseif ($value['type'] == 'plugins') {
                if ($value['app'] == 'wa-plugins/sms') {
                    $app_name = _w('SMS');
                } elseif ($value['app'] == 'wa-plugins/shipping') {
                    $app_name = _w('shipping');
                } elseif ($value['app'] == 'wa-plugins/payment') {
                    $app_name = _w('payment');
                } else {
                    $app_name = $this->installed_products[$value['app']]['title'];
                }

                $label = sprintf(
                    '<strong>%s</strong> <span class="gray">%s (%s)</span>',
                    $value['title'],
                    _w('plugin'),
                    $app_name
                );
            } elseif ($value['type'] == 'widgets') {
                $label = sprintf(
                    '<strong>%s</strong> <span class="gray">%s (%s)</span>',
                    $value['title'],
                    _w('widget'),
                    $this->installed_products[$value['app']]['title']
                );
            }

            $result[] = [
                'value' => logsHelper::getLogsBackendUrl() . '?'
                . http_build_query([
                    'action' => 'files',
                    'mode' => 'product',
                    'slug' => $slug,
                ]),
                'label' => $label,
            ];
        }

        return $result;
    }
}
