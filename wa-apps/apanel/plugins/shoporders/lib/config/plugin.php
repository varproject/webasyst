<?php

return [
  'name'        => 'Заказы Shop-Script',
  'description' => 'Источник заказов Shop-Script для витрин и экранов Apanel.',
  'version'     => '1.0.0',
  'vendor'      => 123456,
  'handlers'    => [
    'storefront_data_sources' => 'storefrontDataSources',
    'storefront_shop_orders'  => 'storefrontShopOrders',
  ],
];
