<?php

return array(
    'shop_lk_route' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),

        'domain' => array('varchar', 255, 'null' => 0),
        'shop_url' => array('varchar', 255, 'null' => 0, 'default' => ''),
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
        'company_contact_id' => array('int', 11, 'null' => 0),
        'legal_name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'inn' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'kpp' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'ogrn' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'status' => array('varchar', 32, 'null' => 0, 'default' => 'new'),
        'manager_contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),

        ':keys' => array(
            'PRIMARY' => 'company_contact_id',
            'inn' => 'inn',
            'status' => 'status',
        ),
    ),

    'shop_lk_company_member' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
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
            'company_contact' => array('company_contact_id', 'contact_id', 'unique' => 1),
            'contact_id' => 'contact_id',
            'company_contact_id' => 'company_contact_id',
            'status' => 'status',
        ),
    ),

    'shop_lk_company_address' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'company_contact_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'address' => array('text'),
        'is_default' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),

        ':keys' => array(
            'PRIMARY' => 'id',
            'company_contact_id' => 'company_contact_id',
            'sort' => 'sort',
        ),
    ),

    'shop_lk_payment_type' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'route_id' => array('int', 11, 'null' => 0),
        'code' => array('varchar', 64, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'description' => array('text'),
        'enabled' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),

        ':keys' => array(
            'PRIMARY' => 'id',
            'route_code' => array('route_id', 'code', 'unique' => 1),
            'route_id' => 'route_id',
            'enabled' => 'enabled',
        ),
    ),
);
