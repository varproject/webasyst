<?php

class shopB2bPluginChannelSettingsTabs
{
    public static function getTabs($channel_id, $active = 'main'): array
    {
        $channel_id = (int) $channel_id;
        $tabs = array(
            'main' => array('label' => 'Главное', 'icon' => '', 'module' => 'channelMain', 'sort' => 10),
            'users' => array('label' => 'Пользователи', 'icon' => '', 'module' => 'channelUsers', 'sort' => 20),
            'catalog' => array('label' => 'Каталог', 'icon' => '', 'module' => 'channelCatalog', 'sort' => 30),
            'pages' => array('label' => 'Страницы', 'icon' => 'fas fa-globe', 'module' => 'channelPages', 'sort' => 40),
            'blog' => array('label' => 'Блог', 'icon' => 'fas fa-newspaper', 'module' => 'channelBlog', 'sort' => 50),
            'support' => array('label' => 'Поддержка', 'icon' => '', 'module' => 'channelSupport', 'sort' => 60),
            'cart' => array('label' => 'Корзина', 'icon' => 'fas fa-shopping-cart', 'module' => 'channelCart', 'sort' => 70),
        );

        uasort($tabs, function ($a, $b) {
            return (int) $a['sort'] <=> (int) $b['sort'];
        });

        foreach ($tabs as $id => &$tab) {
            $tab['id'] = $id;
            $tab['active'] = $id === $active;
            $tab['url'] = self::getEditorTabUrl($channel_id, $id);
            $tab['save_url'] = self::getShopChannelSaveUrl();
        }
        unset($tab);

        return $tabs;
    }

    public static function getTabIds(): array
    {
        return array_keys(self::getRawTabs());
    }

    public static function normalizeTabId($tab_id): string
    {
        $tab_id = trim((string) $tab_id);
        return in_array($tab_id, self::getTabIds(), true) ? $tab_id : 'main';
    }

    public static function getEditorTabUrl($channel_id, $tab_id): string
    {
        $channel_id = (int) $channel_id;
        if ($channel_id <= 0) {
            return '#';
        }

        $tab_id = self::normalizeTabId($tab_id);
        $url = wa()->getAppUrl('shop') . 'channels/editor/' . $channel_id . '/';

        if ($tab_id !== 'main') {
            $url .= '?b2b_tab=' . urlencode($tab_id);
        }

        return $url;
    }

    public static function getShopChannelSaveUrl(): string
    {
        return wa()->getAppUrl('shop') . '?module=channels&action=save';
    }

    protected static function getRawTabs(): array
    {
        return array(
            'main' => true,
            'users' => true,
            'catalog' => true,
            'pages' => true,
            'blog' => true,
            'support' => true,
            'cart' => true,
        );
    }
}