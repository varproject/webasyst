<?php

foreach ([
    'css-legacy',
    'js-legacy',
    'templates/actions-legacy',
    'templates/includes-legacy',
    'templates/layouts-legacy',
] as $path) {
    waFiles::delete(wa('logs')->getAppPath($path), true);
}
