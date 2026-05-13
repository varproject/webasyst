<?php

return array(
  'name'        => 'Массовый редактор характеристик',
  'description' => 'Плагин, для массового редактирования характеристик из списка, для Webasyst 2.0',
  'img'         => 'img/massef.svg',
  'version'     => '1.0.0',
  'ui'          => '2.0',
  'vendor'      => 977221,

  'handlers'    => array(
    'backend_prod_mass_actions' => 'backendProdMassActions',
  )
);
