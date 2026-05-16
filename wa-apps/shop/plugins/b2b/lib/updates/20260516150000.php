<?php

$model = new waModel();
$model->exec("CREATE TABLE IF NOT EXISTS `shop_b2b_channel_page` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `channel_id` int(11) NOT NULL,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `title` varchar(255) NOT NULL DEFAULT '',
    `url` varchar(255) NOT NULL DEFAULT '',
    `source` varchar(32) NOT NULL DEFAULT 'own',
    `content` text NULL,
    `shop_page_id` int(11) NOT NULL DEFAULT 0,
    `site_page_id` int(11) NOT NULL DEFAULT 0,
    `site_block_id` varchar(255) NOT NULL DEFAULT '',
    `external_url` varchar(1024) NOT NULL DEFAULT '',
    `show_in_menu` tinyint(1) NOT NULL DEFAULT 1,
    `sort` int(11) NOT NULL DEFAULT 0,
    `access_policy` varchar(32) NOT NULL DEFAULT 'inherit',
    `create_datetime` datetime NULL,
    `update_datetime` datetime NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `channel_url` (`channel_id`, `url`),
    KEY `channel_sort` (`channel_id`, `sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");
