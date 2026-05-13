<?php

return array(
    'storage_icons' => array(
        'contact fas fa-friends',
        'user fas fa-user',
        'folder fas fa-folder',
        'notebook fas fa-file-alt',
        'lock fas fa-lock',
        'lock-unlocked fas fa-lock-open',
        'broom fas fa-broom',
        'star fas fa-star',
        'livejournal fas fa-pencil-alt',
        'contact fas fa-users',
        'lightning fas fa-bolt',
        'light-bulb fas fa-lightbulb',
        'pictures fas fa-images',
        'reports fas fa-chart-pie',
        'books fas fa-book',
        'marker fas fa-map-marker-alt',
        'lens fas fa-eye',
        'alarm-clock fas fa-clock',
        'animal-monkey fas fa-cat',
        'anchor fas fa-anchor',
        'bean fas fa-beer',
        'car fas fa-car',
        'disk fas fa-save',
        'cookie fas fa-cookie',
        'burn fas fa-burn',
        'clapperboard fas fa-film',
        'bug fas fa-bug',
        'clock fas fa-clock',
        'cup fas fa-coffee',
        'home fas fa-home',
        'fruit fas fa-apple-alt',
        'luggage fas fa-briefcase',
        'guitar fas fa-guitar',
        'smiley fas fa-smile',
        'sport-soccer fas fa-futbol',
        'target fas fa-bullseye',
        'medal fas fa-medal',
        'phone fas fa-phone',
        'store fas fa-store'
    ),
    'filter_icons' => array(
        'contact fas fa-user-friends',
        'user fas fa-user',
        'folder fas fa-folder',
        'notebook fas fa-file-alt',
        'lock fas fa-lock',
        'lock-unlocked fas fa-lock-open',
        'broom fas fa-broom',
        'star fas fa-star',
        'livejournal fas fa-pencil-alt',
        'lightning fas fa-bolt',
        'light-bulb fas fa-lightbulb',
        'pictures fas fa-images',
        'reports fas fa-chart-pie',
        'books fas fa-book',
        'marker fas fa-map-marker-alt',
        'lens fas fa-eye',
        'alarm-clock fas fa-clock',
        'animal-monkey fas fa-cat',
        'anchor fas fa-anchor',
        'bean fas fa-beer',
        'car fas fa-car',
        'disk fas fa-save',
        'cookie fas fa-cookie',
        'burn fas fa-burn',
        'clapperboard fas fa-film',
        'bug fas fa-bug',
        'clock fas fa-clock',
        'cup fas fa-coffee',
        'home fas fa-home',
        'fruit fas fa-apple-alt',
        'luggage fas fa-briefcase',
        'guitar fas fa-guitar',
        'smiley fas fa-smile',
        'sport-soccer fas fa-futbol',
        'target fas fa-bullseye',
        'medal fas fa-medal',
        'phone fas fa-phone',
        'store fas fa-store'
    ),

    'files_per_page' => 30,
    'photo_sizes' => array(
        'file_info' => '750',
        'file_list_small' => '32x32',
        'sidebar' => '16x16'
    ),

    'photo_enable_2x' => true,
    'photo_max_size' => 1500,
    'photo_sharpen' => true,
    'photo_save_quality' => 90,
    'photo_save_quality_2x' => 70,
    'text_file_show_max_size' => 100000,
    'copy_chunk_size' => 262144,
    'copy_retry_pause' => 300,
    'tasks_per_request' => 5,
    'newness_expire_time' => 300,

    // Max count of emails sent at once
    'messages_queue_send_max_count' => 250,
    // Max size of queue of emails messages. Oldest messages will be removed from queue
    'messages_queue_max_size' => 100000,
    // Max size of all attached files
    'messages_attach_max_size' => 10000000,

    // Notifications on new uploaded files
    'upload_file_notification' => 'favorite',

    // App icon badge count
    'app_on_count' => 'uploaded_favorite',

    // timeout of file lock in seconds
    'file_lock_timeout' => 1000,

    // timeout of sync with source in seconds
    'sync_timeout' => 20,

    // max number folders to copy
    'max_copy_folders' => 100000,

    // max number of folder to move
    'max_move_folders' => 100000,

    // max number of files in folder to download as archive
    'max_files_download_in_archive' => 15
);
