<?php

return [
    '/?' => 'frontend/dashboard',

    'login/?'          => 'frontend/login',
    'forgotpassword/?' => 'frontend/forgotpassword',

    'dashboard/?' => 'frontend/dashboard',

    'catalog/?' => 'frontend/catalog',

    'cart/?' => 'frontend/cart',

    'orders/?' => 'frontend/orders',
    'orders/<id:\d+>/?' => 'frontend/order',

    'companies/?' => 'frontend/companies',
    'companies/<id:\d+>/?' => 'frontend/company',

    'addresses/?' => 'frontend/addresses',
    'addresses/<id:\d+>/?' => 'frontend/address',

    'profile/?' => 'frontend/profile',
];
