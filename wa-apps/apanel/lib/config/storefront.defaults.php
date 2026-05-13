<?php

return [
    'profile' => [
        'enabled'     => 1,
        'name'        => '',
        'description' => '',
    ],
    'plugin' => [
        'id'       => '',
        'settings' => [],
    ],
    'screens' => [],
    'access' => [
        'mode'     => 'public',
        'groups'   => [],
        'contacts' => [],
    ],
    'auth' => [
        'enabled'              => 1,
        'required'             => 1,
        'registration_enabled' => 0,
        'registration_allowed' => 0,
        'login_by'             => 'email',
        'allowed_login_by'     => ['email', 'phone'],
    ],
    'ui'       => [],
    'data'     => [],
    'seo'      => [],
    'advanced' => [],
];
