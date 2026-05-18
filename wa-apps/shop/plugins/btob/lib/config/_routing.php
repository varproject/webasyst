<?php

if (wa()->getEnv() !== 'frontend') {
    return null;
}

return array(
    '*' => array(
        // 'module' => 'frontend',
        'action' => 'btob',
        'plugin' => 'btob',
        'secure' => true,
    ),

    'btob/orders/' => array(
        'module' => 'frontend',
        'action' => 'orders',
        'plugin' => 'btob',
        'secure' => true,
    ),

    'btob/order/<id:\d+>/' => array(
        'module' => 'frontend',
        'action' => 'order',
        'plugin' => 'btob',
        'secure' => true,
    ),
);
