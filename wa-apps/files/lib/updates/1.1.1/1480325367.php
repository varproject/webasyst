<?php

$m = new waModel();

try {
    $m->query("SELECT `md5_checksum` FROM `files_file` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `files_file` ADD COLUMN `md5_checksum` VARCHAR (32) NULL DEFAULT NULL AFTER `size`");
}
