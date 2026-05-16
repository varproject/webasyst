<?php

class shopB2bPluginChannelSettingsTabs
{
    // Возвращает вкладки настроек
    public static function getTabs(int $channel_id): array
    {
        $active = waRequest::get('b2b_tab', 'main', waRequest::TYPE_STRING_TRIM);
        $tabs = [];

        $raw_data = [
            'main'    => ['label' => 'Главная', 'icon' => ''],
            'users'   => ['label' => 'Пользователи', 'icon' => ''],
            'catalog' => ['label' => 'Каталог', 'icon' => ''],
            'pages'   => ['label' => 'Страницы', 'icon' => 'fas fa-globe'],
            'blog'    => ['label' => 'Блог', 'icon' => 'fas fa-newspaper'],
            'support' => ['label' => 'Поддержка', 'icon' => ''],
            'cart'    => ['label' => 'Корзина', 'icon' => 'fas fa-shopping-cart']
        ];

        foreach ($raw_data as $key => $tab) {
            $tabs[$key] = [
                'id'     => $key,
                'label'  => $tab['label'],
                'active' => $key === $active,
                'class'  => 'shopB2bPluginChannels' . ucfirst($key) . 'Action',
                'tpl'    => 'ChannelSettingsTabs' . ucfirst($key) . '.html',
                'url'    => self::getTabUrl($channel_id, $key),
            ];
        }


        return [
            'all' => $tabs ?? [],
            'active' => $tabs[$active] ?? [],
        ];
    }

    // Возвращает корректный url вкладки
    public static function getTabUrl(int $channel_id, string $tab_id): string
    {
        if ($channel_id <= 0) {
            return '';
        }

        $url = wa()->getAppUrl('shop') . 'channels/editor/' . $channel_id . '/';

        if ($tab_id !== 'main') {
            $url .= '?b2b_tab=' . urlencode($tab_id);
        }

        return $url;
    }
}
