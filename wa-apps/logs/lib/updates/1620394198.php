<?php

foreach ([
    'js/frontend/file.js',
    'templates/actions/ItemLines.html',
    'templates/actions/ItemView.html',
] as $path) {
    waFiles::delete(wa()->getAppPath($path, 'logs'));
}
