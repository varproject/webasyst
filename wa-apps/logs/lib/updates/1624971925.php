<?php

try {
    $model = new waModel();
    $model->exec('CREATE TABLE IF NOT EXISTS `logs_tracked` (
        `path` varchar(255) NOT NULL,
        `contact_id` int(11) UNSIGNED NOT NULL,
        `update_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated` tinyint(1) UNSIGNED NOT NULL DEFAULT "0"
      ) DEFAULT CHARSET=utf8;');

    $model->exec('ALTER TABLE `logs_tracked`
                  ADD PRIMARY KEY (`path`,`contact_id`)');
} catch (Exception $exception) {
}
