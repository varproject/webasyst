<?php

return array(
    'shop_b2b_channel_page' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'channel_id' => array('int', 11, 'null' => 0),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'title' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'url' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'source' => array('varchar', 32, 'null' => 0, 'default' => 'own'),
        'content' => array('text'),
        'shop_page_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'site_page_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'site_block_id' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'external_url' => array('varchar', 1024, 'null' => 0, 'default' => ''),
        'show_in_menu' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'access_policy' => array('varchar', 32, 'null' => 0, 'default' => 'inherit'),
        'create_datetime' => array('datetime'),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'channel_url' => array('channel_id', 'url', 'unique' => 1),
            'channel_sort' => array('channel_id', 'sort'),
        ),
    ),
);
