<?php

$model = new waModel();

try {
    $model->exec("SELECT synchronize_datetime FROM files_source WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE files_source ADD COLUMN synchronize_datetime datetime NULL DEFAULT NULL");
}

$model->exec("UPDATE files_source SET synchronize_datetime = :datetime WHERE synchronize_datetime IS NULL",
    array('datetime' => date('Y-m-d H:i:s')));

try {
    $model->exec("SELECT in_progress_datetime FROM files_source WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE files_source ADD COLUMN in_progress_datetime datetime NULL DEFAULT NULL");
}