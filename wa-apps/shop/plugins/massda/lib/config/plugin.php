<?php

return array(
  'name'        => 'Массовое удаление артикулов (вариантов/sku) товара.',
  'description' => 'Плагин позволяет массово удалить все варианты/sku товара, осталяя только главный артикул.',
  'img'         => 'img/massda.svg',
  'version'     => '3.0.1',
  'vendor'      => 977221,

  'handlers'    =>
  array(
    'backend_prod_mass_actions' => 'backendProdMassActions',
    'backend_products' => 'backendProducts',
  ),
);
