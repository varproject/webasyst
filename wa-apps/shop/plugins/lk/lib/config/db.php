<?php

return array(
    'shop_lk_route' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),

        // Длинные значения НЕ индексируем вместе: на utf8mb4 это ломает MySQL key length.
        'domain' => array('varchar', 255, 'null' => 0),
        'shop_url' => array('varchar', 255, 'null' => 0, 'default' => ''),

        // md5(domain . "|" . shop_url), короткий ключ витрины для индексов.
        'storefront_key' => array('varchar', 32, 'null' => 0, 'default' => ''),

        'route' => array('varchar', 64, 'null' => 0, 'default' => 'my'),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'enabled' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'b2b_mode' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'lock_mode' => array('varchar', 32, 'null' => 0, 'default' => 'cabinet'),
        'config' => array('mediumtext'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),

        ':keys' => array(
            'PRIMARY' => 'id',
            'storefront_route' => array('storefront_key', 'route', 'unique' => 1),
            'storefront_key' => 'storefront_key',
            'enabled' => 'enabled',
        ),
    ),

    'shop_lk_company_profile' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'route_id' => array('int', 11, 'null' => 0),
        'company_contact_id' => array('int', 11, 'null' => 0),
        'legal_name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'inn' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'kpp' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'ogrn' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'status' => array('varchar', 32, 'null' => 0, 'default' => 'active'),
        'payment_type_id' => array('int', 11),
        'manager_contact_id' => array('int', 11),
        'create_contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'route_company' => array('route_id', 'company_contact_id', 'unique' => 1),
            'route_inn' => array('route_id', 'inn'),
            'status' => 'status',
        ),
    ),

    'shop_lk_company_member' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'route_id' => array('int', 11, 'null' => 0),
        'company_contact_id' => array('int', 11, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'role' => array('varchar', 32, 'null' => 0, 'default' => 'member'),
        'status' => array('varchar', 32, 'null' => 0, 'default' => 'active'),
        'is_owner' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'create_contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'route_company_contact' => array('route_id', 'company_contact_id', 'contact_id', 'unique' => 1),
            'contact' => array('route_id', 'contact_id'),
            'company' => array('route_id', 'company_contact_id'),
            'status' => 'status',
        ),
    ),

    'shop_lk_company_address' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'route_id' => array('int', 11, 'null' => 0),
        'company_contact_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'type' => array('varchar', 32, 'null' => 0, 'default' => 'shipping'),
        'country' => array('varchar', 3, 'null' => 0, 'default' => ''),
        'region' => array('varchar', 64, 'null' => 0, 'default' => ''),
        'city' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'street' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'zip' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'comment' => array('text'),
        'is_default' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'status' => array('varchar', 32, 'null' => 0, 'default' => 'active'),
        'create_contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'route_company' => array('route_id', 'company_contact_id'),
            'status' => 'status',
        ),
    ),

    'shop_lk_payment_type' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'route_id' => array('int', 11, 'null' => 0),
        'code' => array('varchar', 64, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'description' => array('text'),
        'enabled' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'config' => array('mediumtext'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'route_code' => array('route_id', 'code', 'unique' => 1),
            'route_sort' => array('route_id', 'sort'),
            'enabled' => 'enabled',
        ),
    ),
);
