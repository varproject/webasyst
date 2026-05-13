<?php

$m = new waModel();

$m->exec("
    CREATE TABLE IF NOT EXISTS files_source_sync (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `uid` VARCHAR (32) NOT NULL,
        `source_id` int(11) NOT NULL,
        `source_inner_id` VARBINARY(255) NULL DEFAULT NULL,
        `source_path` VARCHAR(255) NOT NULL,
        `type` ENUM('file','folder','delete','move','rename') NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `size` bigint(20) NOT NULL DEFAULT 0,
        `data` TEXT NOT NULL,
        `datetime` datetime NOT NULL,
        `slice_id` VARCHAR (32) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uid` (`uid`),
        KEY `source_id_path` (`source_id`, `source_path`),
        KEY `source_id_inner_id` (`source_id`, `source_inner_id`),
        KEY `slice_id` (`slice_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8
");