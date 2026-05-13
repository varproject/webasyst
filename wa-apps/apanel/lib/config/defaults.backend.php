<?php

return  [
    'app' => true,
    'is_htmx' => false,
    'fullscreen_user_setting_mode' => false,
    'fullscreen_single_app_mode' => false,
    'has_fullscreen' => false,
    'backend_modal_page' => '',
    'sidebar' => [
        'enabled' => false,
        'header' => [
            'enabled' => true,
            'logo' => [
                'target_url' => '',
                'img' => [
                    'enabled' => true,
                    'file_name' => 'favicon-apanel.png',
                ],
                'text' => 'ERP: Торговля 1.0',
            ],
        ],
        'body' => [
            'enabled' => true,
            'items' => [],
        ],
        'footer' => [
            'enabled' => false,
            'items' => [],
        ],
    ],
    'header' => [
        'enabled' => true,
        'left' => [
            'enabled' => true,
            'toggle' => [
                'enabled' => false,
                'icon' =>  '<i class="bi bi-list"></i>',
            ],
            'logo' => [
                'enabled' => true,
            ],
            'items' => [],
        ],
        'right' => [
            'enabled' => true,
            'fullscreen_switch' => [
                'enabled' => true,
                'get_url' => '?fullscreen=1',
            ],
            'profile_dropdown' => [
                'enabled' => true,
                'items' => [],
            ],
        ],
    ],
    'main_enabled' => true,
    'main_navbar' => [
        'enabled' => true,
        'left' => [
            'enabled' => true,
            'items' => [],
            'message' => [
                'enabled' => true,
                'text' => '',
            ],
        ],
        'right' => [
            'enabled' => true,
            'items' => [],
        ],
    ],
    'main_toolbar' => [
        'enabled' => true,
        'left' => [
            'enabled' => true,
            'title' => [
                'enabled' => true,
                'text' => '',
            ],
            'items' => [],
        ],
        'right' => [
            'enabled' => true,
            'items' => [],
        ],
    ],
    'main_body' => [
        'enabled' => true,
        'tree' => [
            'enabled' => true,
            'items' => [],
        ],
        'table' => [
            'enabled' => true,
            'items' => [],
        ],
    ],
    'main_footer' => [
        'enabled' => true,
        'left' => [
            'enabled' => true,
            'items' => [],
        ],
        'right' => [
            'enabled' => true,
            'items' => [],
        ],
    ]
];
