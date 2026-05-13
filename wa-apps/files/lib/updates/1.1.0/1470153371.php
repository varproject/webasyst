<?php

$m = new waModel();
try {
    $m->query("SELECT `in_copy_process` FROM `files_file` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_file` ADD COLUMN `in_copy_process` BIGINT(20) NOT NULL DEFAULT '0'");
}

$m->exec("UPDATE `files_file` 
          SET `in_copy_process` = 1, source_path = NULL 
          WHERE source_id = 0 AND source_path IS NOT NULL");