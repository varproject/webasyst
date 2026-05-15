<?php
return array(
  'name'            => 'B2B / Канал продаж для клиентов',
  'description'     => 'Один изолированный B2B/ЛК-кабинет на каждую витрину Shop-Script: домен + поселение.',
  'img'             => 'img/lk.svg',
  'version'         => '1.0.1',
  'vendor'          => '123456',
  'frontend'        => true,

  'handlers'  => [
    // Регистрирует тип канала продаж b2b.
    'sales_channel_types' => 'salesChannelTypes',

    // Описывает неизвестные b2b:* каналы для старых заказов.
    'sales_channels' => 'salesChannels',

    // Добавляет frontend routes B2B-каналов.
    'routing' => 'routingHandler',

    // После создания заказа проставляет sales_channel = b2b:{id}.
    'order_action.create' => 'orderActionCreate',
  ],
);