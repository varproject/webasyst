<?php

class shopB2bPluginChannelsAction extends waViewAction
{
    protected array $channel = [];
    protected array $channel_tabs = [];
    protected array $channel_tab = [];
    protected array $base_form_fields_html = [];

    public function __construct($params = null)
    {
        $this->channel = $params['channel'] ?? [];
        $this->base_form_fields_html = $params['base_form_fields_html'] ?? [];

        // Инициализируем вкладки
        $this->getTabs($this->channel);
        
        parent::__construct($params);
    }

    public function execute()
    {
        // Cоздаем lazy-объект экшена конкретного таба
        $tab_form_html = $this->channel_tab['action']
            ? new waLazyDisplay(new $this->channel_tab['action']())
            : '';

        $this->view->assign([
            'channel'               => $this->channel,
            'channel_tabs'          => $this->channel_tabs,
            'channel_tab'           => $this->channel_tab,
            'channel_tab_form_html' => $tab_form_html,
            'base_form_fields_html' => $this->base_form_fields_html,
        ]);
    }

    // Возвращает вкладки настроек
    protected function getTabs(array $channel)
    {
        $channel_id = (int)($channel['id'] ?? 0);
        $active_tab_id = waRequest::get('b2b_tab', 'main', waRequest::TYPE_STRING_TRIM);

        $raw_data = [
            'main'    => ['label' => 'Главная', 'icon' => ''],
            'users'   => ['label' => 'Пользователи', 'icon' => ''],
            'catalog' => ['label' => 'Каталог', 'icon' => ''],
            'pages'   => ['label' => 'Страницы', 'icon' => 'fas fa-globe'],
            'blog'    => ['label' => 'Блог', 'icon' => 'fas fa-newspaper'],
            'support' => ['label' => 'Поддержка', 'icon' => ''],
            'cart'    => ['label' => 'Корзина', 'icon' => 'fas fa-shopping-cart']
        ];

        $tabs = [];
        foreach ($raw_data as $key => $tab) {
            $tabs[$key] = [
                'id'     => $key,
                'label'  => $tab['label'],
                'active' => ($key === $active_tab_id),
                'action' => 'shopB2bPluginChannels' . ucfirst($key) . 'Action',
                'url'    => $this->getTabUrl($channel_id, $key),
            ];
        }

        $this->channel_tabs = $tabs;
        $this->channel_tab  = $tabs[$active_tab_id] ?? ($tabs['main'] ?? []);

        return $this;
    }

    // Возвращает корректный url вкладки
    protected function getTabUrl(int $channel_id, string $tab_id): string
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
