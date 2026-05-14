<?php

final class shopLkPluginSchema
{
    protected static $checked = false;

    public static function ensure()
    {
        if (self::$checked) {
            return;
        }

        $model = new waModel();

        foreach (self::getCreateSql() as $sql) {
            $model->exec($sql);
        }

        self::$checked = true;
    }

    protected static function getCreateSql()
    {
        return array(
            "CREATE TABLE IF NOT EXISTS `shop_lk_route` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `domain` varchar(255) NOT NULL,
                `shop_url` varchar(255) NOT NULL DEFAULT '',
                `route` varchar(64) NOT NULL DEFAULT 'my',
                `name` varchar(255) NOT NULL DEFAULT '',
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `b2b_mode` tinyint(1) NOT NULL DEFAULT 1,
                `lock_mode` varchar(32) NOT NULL DEFAULT 'cabinet',
                `config` mediumtext NULL,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `domain_shop_route` (`domain`, `shop_url`, `route`),
                KEY `domain_shop` (`domain`, `shop_url`),
                KEY `enabled` (`enabled`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `shop_lk_company_profile` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `route_id` int(11) NOT NULL,
                `company_contact_id` int(11) NOT NULL,
                `legal_name` varchar(255) NOT NULL DEFAULT '',
                `inn` varchar(32) NOT NULL DEFAULT '',
                `kpp` varchar(32) NOT NULL DEFAULT '',
                `ogrn` varchar(32) NOT NULL DEFAULT '',
                `status` varchar(32) NOT NULL DEFAULT 'active',
                `payment_type_id` int(11) NULL,
                `manager_contact_id` int(11) NULL,
                `create_contact_id` int(11) NOT NULL DEFAULT 0,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `route_company` (`route_id`, `company_contact_id`),
                KEY `route_inn` (`route_id`, `inn`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `shop_lk_company_member` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `route_id` int(11) NOT NULL,
                `company_contact_id` int(11) NOT NULL,
                `contact_id` int(11) NOT NULL,
                `role` varchar(32) NOT NULL DEFAULT 'member',
                `status` varchar(32) NOT NULL DEFAULT 'active',
                `is_owner` tinyint(1) NOT NULL DEFAULT 0,
                `create_contact_id` int(11) NOT NULL DEFAULT 0,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `route_company_contact` (`route_id`, `company_contact_id`, `contact_id`),
                KEY `contact` (`route_id`, `contact_id`),
                KEY `company` (`route_id`, `company_contact_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `shop_lk_company_address` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `route_id` int(11) NOT NULL,
                `company_contact_id` int(11) NOT NULL,
                `name` varchar(255) NOT NULL DEFAULT '',
                `type` varchar(32) NOT NULL DEFAULT 'shipping',
                `country` varchar(3) NOT NULL DEFAULT '',
                `region` varchar(64) NOT NULL DEFAULT '',
                `city` varchar(255) NOT NULL DEFAULT '',
                `street` varchar(255) NOT NULL DEFAULT '',
                `zip` varchar(32) NOT NULL DEFAULT '',
                `comment` text NULL,
                `is_default` tinyint(1) NOT NULL DEFAULT 0,
                `status` varchar(32) NOT NULL DEFAULT 'active',
                `create_contact_id` int(11) NOT NULL DEFAULT 0,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                KEY `route_company` (`route_id`, `company_contact_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `shop_lk_payment_type` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `route_id` int(11) NOT NULL,
                `code` varchar(64) NOT NULL,
                `name` varchar(255) NOT NULL,
                `description` text NULL,
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `sort` int(11) NOT NULL DEFAULT 0,
                `config` mediumtext NULL,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `route_code` (`route_id`, `code`),
                KEY `route_sort` (`route_id`, `sort`),
                KEY `enabled` (`enabled`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }
}
