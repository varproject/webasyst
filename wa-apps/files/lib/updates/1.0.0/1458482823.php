<?php

$m = new waModel();

try {
    $m->exec("SELECT `source_inner_id` FROM `files_file` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_file` ADD COLUMN `source_inner_id` VARBINARY (255) NULL DEFAULT NULL AFTER `source_id`");
}

try {
    $m->exec("CREATE UNIQUE INDEX `source_id_path` ON `files_file` (`source_id`, `source_path`)");
} catch (waDbException $e) {}

try {
    $m->exec("CREATE UNIQUE INDEX `source_id_inner_id` ON `files_file` (`source_id`, `source_inner_id`)");
} catch (waDbException $e) {}