<?php


$m = new waModel();
try {
    $m->query("SELECT color FROM `files_storage` WHERE 0");
    $m->exec("ALTER TABLE `files_storage` DROP COLUMN `color`");
} catch (Exception $e) {

}