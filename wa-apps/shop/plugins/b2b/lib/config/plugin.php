<?php
return array(
  'name'            => 'B2B / Канал продаж для клиентов',
  'description'     => 'Один изолированный B2B/ЛК-кабинет на каждую витрину Shop-Script: домен + поселение.',
  'img'             => 'img/lk.svg',
  'version'         => '1.0.0',
  'vendor'          => '123456',
  'frontend'        => true,
  // 'custom_settings' => true,

  'handlers'  => [
    // Регистрирует тип канала продаж b2b.
    'sales_channel_types' => 'salesChannelTypes',

    // Описывает неизвестные b2b:* каналы для старых заказов.
    'sales_channels' => 'salesChannels',

    // Добавляет пункт B2B в левое меню Shop-Script WA 1.3.
    'backend_menu'   => 'backendMenu',
    
    // Добавляет пункт B2B в левое меню Shop-Script WA 2.0.
    'backend_extended_menu' => 'backendExtendedMenu',

    // Добавляет backend-роут /webasyst/shop/b2b/.
    'routing' => 'routingHandler',

    // После создания заказа проставляет sales_channel = b2b:{id}.
    'order_action.create' => 'orderActionCreate',
  ],
);
