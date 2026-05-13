<?php

$fm = new filesFilterModel();
$all_filters = array(
    filesFilterModel::ACCESS_TYPE_PERSONAL => $fm->getOwnFilters(),
    filesFilterModel::ACCESS_TYPE_SHARED => $fm->getSharedFilters()
);
$filters = array(
    array('value' => 'all', 'title' => /* _w */('All files')),
    array('value' => 'favorite', 'title' => /* _w */('Favorites')),
);
foreach ($all_filters as $f1) {
    foreach ($f1 as $f2) {
        $filters[] = array(
            'value' => 'filter/' . $f2['id'], 'title' => filesApp::truncate($f2['name'])
        );
    }
}

return array(
    'title' => array(
        'title' => /* _wp */('Title'),
        'description' => /* _wp */(''),
        'value' => 'Uploaded files',
        'control_type' => waHtmlControl::INPUT,
    ),
    'filter' => array(
        'title' => /* _w */('Filter'),
        'control_type' => waHtmlControl::SELECT,
        'value' => 'all',
        'options' => $filters
    ),
    'limit' => array(
        'title' => /* _wp */('Count of the files'),
        'description' => /* _wp */(''),
        'value' => '6',
        'control_type' => waHtmlControl::INPUT,
    ),
);
