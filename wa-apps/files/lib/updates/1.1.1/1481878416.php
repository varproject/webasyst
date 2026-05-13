<?php

$sql = "CREATE TABLE IF NOT EXISTS files_tasks_queue (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `file_id` int(11) UNSIGNED NOT NULL,
    `contact_id` int(11) NOT NULL DEFAULT '0',
    `operation` enum('move','copy','delete'),
    `parent_id` int(11) NOT NULL DEFAULT '0',
    `storage_id` int(11) NOT NULL DEFAULT '0',
    `parent_task_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
    `create_datetime` datetime NOT NULL,
    `lock` varchar(32),
    `lock_expired_datetime` datetime, 
    `process_id` bigint(20) NOT NULL DEFAULT '0',
    `replace` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `file_id` (`file_id`),
    KEY `parent_task_id` (`parent_task_id`),
    KEY `lock` (`lock`),
    KEY `process_id` (`process_id`),
    KEY `contact_id` (`contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$m = new waModel();

$m->exec($sql);
