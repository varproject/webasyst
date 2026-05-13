<?php
return array(
    'shop_skcatimage_groups' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 32, 'null' => 0),
        'title' => array('varchar', 255, 'null' => 0),
        'width' => array('int', 11),
        'height' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_skcatimage_data' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'category_id' => array('int', 11, 'null' => 0),
        'group_name' => array('varchar', 32, 'null' => 0),
        'name' => array('text'),
        'type' => array('varchar', 64, 'null' => 0),
        'size' => array('int', 11),
        'query' => array('varchar', 64, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'category_group' => array('category_id', 'group_name', 'unique' => 1),
        ),
    ),
);