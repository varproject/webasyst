<?php

$m = new waModel();

try {
    $m->query("SELECT `slice_expired_datetime` FROM `files_source_sync` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_source_sync` ADD COLUMN `slice_expired_datetime` DATETIME NULL DEFAULT NULL");
}