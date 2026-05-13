<?php

$m = new waModel();

try {
    $m->exec("SELECT storage_id FROM files_source WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE files_source ADD COLUMN storage_id INT(11) NULL DEFAULT NULL");
}

try {
    $m->exec("SELECT folder_id FROM files_source WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE files_source ADD COLUMN folder_id INT(11) NULL DEFAULT NULL");
}
