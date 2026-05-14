<?php

final class shopLkPluginSchema
{
    protected static $checked = false;

    public static function ensure()
    {
        if (self::$checked) {
            return;
        }

        self::$checked = true;

        $model = new waModel();

        $model->exec(
            "CREATE TABLE IF NOT EXISTS `shop_lk_route` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `domain` varchar(255) NOT NULL,
                `shop_url` varchar(255) NOT NULL DEFAULT '',
                `storefront_key` varchar(32) NOT NULL DEFAULT '',
                `route` varchar(64) NOT NULL DEFAULT 'my',
                `name` varchar(255) NOT NULL DEFAULT '',
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `b2b_mode` tinyint(1) NOT NULL DEFAULT 1,
                `lock_mode` varchar(32) NOT NULL DEFAULT 'cabinet',
                `config` mediumtext NULL,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `storefront_route` (`storefront_key`, `route`),
                KEY `storefront_key` (`storefront_key`),
                KEY `enabled` (`enabled`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        self::addColumnIfMissing('shop_lk_route', 'storefront_key', "ALTER TABLE `shop_lk_route` ADD `storefront_key` varchar(32) NOT NULL DEFAULT '' AFTER `shop_url`");

        $model->exec(
            "CREATE TABLE IF NOT EXISTS `shop_lk_company_profile` (
                `company_contact_id` int(11) NOT NULL,
                `legal_name` varchar(255) NOT NULL DEFAULT '',
                `inn` varchar(32) NOT NULL DEFAULT '',
                `kpp` varchar(32) NOT NULL DEFAULT '',
                `ogrn` varchar(32) NOT NULL DEFAULT '',
                `status` varchar(32) NOT NULL DEFAULT 'new',
                `manager_contact_id` int(11) NOT NULL DEFAULT 0,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`company_contact_id`),
                KEY `inn` (`inn`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $model->exec(
            "CREATE TABLE IF NOT EXISTS `shop_lk_company_member` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `company_contact_id` int(11) NOT NULL,
                `contact_id` int(11) NOT NULL,
                `role` varchar(32) NOT NULL DEFAULT 'member',
                `status` varchar(32) NOT NULL DEFAULT 'active',
                `is_owner` tinyint(1) NOT NULL DEFAULT 0,
                `create_contact_id` int(11) NOT NULL DEFAULT 0,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `company_contact` (`company_contact_id`, `contact_id`),
                KEY `contact_id` (`contact_id`),
                KEY `company_contact_id` (`company_contact_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $model->exec(
            "CREATE TABLE IF NOT EXISTS `shop_lk_company_address` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `company_contact_id` int(11) NOT NULL,
                `name` varchar(255) NOT NULL DEFAULT '',
                `address` text NULL,
                `is_default` tinyint(1) NOT NULL DEFAULT 0,
                `sort` int(11) NOT NULL DEFAULT 0,
                `create_datetime` datetime NOT NULL,
                `update_datetime` datetime NULL,
                PRIMARY KEY (`id`),
                KEY `company_contact_id` (`company_contact_id`),
                KEY `sort` (`sort`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $model->exec(
            "CREATE TABLE IF NOT EXISTS `shop_lk_payment_type` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `route_id` int(11) NOT NULL,
                `code` varchar(64) NOT NULL,
                `name` varchar(255) NOT NULL DEFAULT '',
                `description` text NULL,
                `enabled` tinyint(1) NOT NULL DEFAULT 1,
                `sort` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `route_code` (`route_id`, `code`),
                KEY `route_id` (`route_id`),
                KEY `enabled` (`enabled`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        self::fillStorefrontKeys();
    }

    protected static function addColumnIfMissing($table, $column, $sql)
    {
        $model = new waModel();

        try {
            $fields = $model->describe($table);
        } catch (Exception $e) {
            return;
        }

        if (empty($fields[$column])) {
            $model->exec($sql);
        }
    }

    protected static function fillStorefrontKeys()
    {
        $model = new waModel();

        try {
            $rows = $model->query("SELECT id, domain, shop_url FROM `shop_lk_route` WHERE storefront_key = '' OR storefront_key IS NULL")->fetchAll();
        } catch (Exception $e) {
            return;
        }

        foreach ($rows as $row) {
            $domain = shopLkPluginRouteService::normalizeDomain($row['domain']);
            $shop_url = shopLkPluginRouteService::normalizeShopUrl($row['shop_url']);
            $key = shopLkPluginRouteService::getStorefrontKey($domain, $shop_url);

            $model->exec(
                "UPDATE `shop_lk_route` SET storefront_key = s:key WHERE id = i:id",
                array(
                    'key' => $key,
                    'id' => $row['id'],
                )
            );
        }
    }
}
