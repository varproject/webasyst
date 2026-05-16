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
            $tab['panel_id'] = 'b2b-channel-tab-' . $id;
            $tab['url'] = '#' . $tab['panel_id'];
            $tab['save_url'] = '';
            $tab['content_url'] = self::getContentUrl($channel_id, $tab['module']);
            $tab['save_endpoint'] = self::getContentUrl($channel_id, $tab['module'], 'save');
        }
        unset($tab);

        return $tabs;
    }

    public static function getContentUrl($channel_id, $module, $action = null): string
    {
        $url = wa()->getAppUrl('shop') . '?plugin=b2b&module=' . urlencode($module) . '&channel_id=' . (int) $channel_id;

        if ($action) {
            $url .= '&action=' . urlencode($action);
        }

        return $url;
    }
}