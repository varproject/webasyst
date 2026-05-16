<?php

return array(
    'b2b/' => array(
        'module' => 'frontend',
        'action' => 'b2b',
        'plugin' => 'b2b',
        'secure' => true,
    ),

    'b2b/orders/' => array(
        'module' => 'frontend',
        'action' => 'orders',
        'plugin' => 'b2b',
        'secure' => true,
    ),

    'b2b/order/<id:\d+>/' => array(
        'module' => 'frontend',
        'action' => 'order',
        'plugin' => 'b2b',
        'secure' => true,
    ),
);
