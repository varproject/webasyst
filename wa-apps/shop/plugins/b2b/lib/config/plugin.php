<?php
return array(
    'name'        => 'B2B / Канал продаж для клиентов',
    'description' => 'Изолированные B2B-витрины Shop-Script с отдельными настройками доступа, каталога, страниц, блога, поддержки и корзины.',
    'img'         => 'img/lk.svg',
    'version'     => '2.0.0',
    'vendor'      => '123456',
    'frontend'    => true,

    'handlers' => array(
        'sales_channel_types' => 'salesChannelTypes',
        'sales_channels'      => 'salesChannels',
        'routing'             => 'routingHandler',
        'order_action.create' => 'orderActionCreate',
    ),
);
