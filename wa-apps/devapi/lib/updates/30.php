<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */
$model = new waModel();
$queries = [
    'truncate table devapi_transaction',
    'alter table devapi_transaction modify type varchar(32) null'
];
foreach ($queries as $query) {
    try {
        $model->query($query);
    } catch (Throwable $e) {
    }
}

