<?php
return array(
  'name'            => 'B2B личный кабинет',
  'description'     => 'Один изолированный B2B/ЛК-кабинет на каждую витрину Shop-Script: домен + поселение.',
  'img'             => 'img/lk.svg',
  'version'         => '1.0.0',
  'vendor'          => '123456',
  'frontend'        => true,
  'custom_settings' => true,

  'handlers'  => [
    'backend_menu'   => 'backendMenu', // 1.3 tab main menu
    'backend_extended_menu' => 'backendExtendedMenu', // 2.0 sidebar main menu
    'routing' => 'routingHandler',
    'sales_channel_types' => 'salesChannelTypes',
  ],
);
