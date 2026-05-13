<?php

$model = new waModel();

try {
    $model->exec("SELECT storage_id FROM files_lock WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE files_lock ADD COLUMN storage_id int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER file_id");
    // for lock all app use storage_id=0 and file_id=0
    $model->exec("ALTER TABLE files_lock CHANGE COLUMN file_id file_id int(11) UNSIGNED NOT NULL DEFAULT 0");
}