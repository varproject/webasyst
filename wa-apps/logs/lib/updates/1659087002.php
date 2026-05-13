<?php

$model = new waModel();

try {
    $model->exec('SELECT `viewed_datetime` FROM `logs_tracked` WHERE 0');
} catch (Exception $exception) {
    $model->exec('ALTER TABLE `logs_tracked` CHANGE `update_datetime` `viewed_datetime` DATETIME NOT NULL');
}
