<?php

$m = new waModel();

$m->exec("
    CREATE TABLE IF NOT EXISTS files_source_sync_params (
        `source_sync_id` INT(11) UNSIGNED NOT NULL,
        `name` VARCHAR (255) NOT NULL DEFAULT '',
        `value` TEXT,
      PRIMARY KEY (`source_sync_id`, `name`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8
");

try {
    $m->query("SELECT data FROM `files_source_sync` WHERE 0");
    $m->exec("ALTER TABLE `files_source_sync` DROP COLUMN `data`");
} catch (waDbException $e) {
    
}