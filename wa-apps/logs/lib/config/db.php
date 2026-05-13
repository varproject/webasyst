<?php
return array(
    'logs_published' => array(
        'path' => array('text', 'null' => 0),
        'hash' => array('varchar', 255, 'null' => 0),
        'password' => array('varchar', 16, 'null' => 0),
        ':keys' => array(
            'hash' => array('hash', 'unique' => 1),
        ),
    ),
    'logs_tracked' => array(
        'path' => array('varchar', 255, 'null' => 0),
        'contact_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'viewed_datetime' => array('datetime', 'null' => 0),
        'updated' => array('tinyint', 1, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => array('path', 'contact_id'),
        ),
    ),
);
