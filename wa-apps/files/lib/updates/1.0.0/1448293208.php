<?php

$sm = new filesStorageModel();

if (filesRights::inst()->isAdmin()) {
    $sm->updateByField(array(
        'access_type' => filesStorageModel::ACCESS_TYPE_PERSONAL
    ), array(
        'sort' => 0
    ));

    $sort = 0;
    foreach ($sm->query('
        SELECT id FROM files_storage
        WHERE access_type != s:access_type
        ORDER BY sort',
        array(
            'access_type' => filesStorageModel::ACCESS_TYPE_PERSONAL
        ))->fetchAll(null, true) as $id)
    {
        $sm->updateById($id, array(
            'sort' => $sort
        ));
        $sort += 1;
    }
}