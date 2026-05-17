<?php

return array(
    'name'        => 'B2B-витрины / Оптовый кабинет',
    'description' => 'Изолированные каналы продаж, для Shop-Script.',
    'img'         => 'img/b2b.svg',
    'version'     => '2.0.0',
    'vendor'      => '977221',
    'frontend'    => true,
    // 'custom_settings' => true,

    'handlers' => array(
        // 'routing'             => 'routingHandler',
        'sales_channels'      => 'salesChannels',
        'sales_channel_types' => 'salesChannelTypes',
        'order_action.create' => 'orderActionCreate',
    ),
);
