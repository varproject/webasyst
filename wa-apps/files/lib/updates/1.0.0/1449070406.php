<?php

$m = new waModel();

$m->exec("
    CREATE TABLE IF NOT EXISTS files_lock (
        `token` varchar(100) NOT NULL,
        `create_datetime` datetime NOT NULL,
        `contact_id` int(11) NOT NULL DEFAULT '0',
        `file_id` int(11) UNSIGNED NOT NULL,
        `scope` enum('exclusive','shared') NOT NULL DEFAULT 'exclusive',
        `depth` enum('0','infinity') NOT NULL DEFAULT '0',
        `timeout` int(11) UNSIGNED NOT NULL DEFAULT '0',
        `expired_datetime` datetime NOT NULL,
        `owner` varchar(100) NOT NULL, PRIMARY KEY (`token`),
      KEY `file_id` (`file_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");