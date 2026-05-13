<?php

return array(

    'settings/plugins/?'                         => 'plugins/list',
    'settings/plugins/<id:[a-z0-9_]+>/enable/?'  => 'plugins/enable',
    'settings/plugins/<id:[a-z0-9_]+>/disable/?' => 'plugins/disable',
    'settings/plugins/<id:[a-z0-9_]+>/remove/?'  => 'plugins/remove',

    'nomenclature/*'    => 'nomenclatureCatalog',
    '*'                 => 'settingsStorefront',

    // 'products/<id:\d+|new>/?'                  => 'prod/',
    // '*' => 'error',
);
