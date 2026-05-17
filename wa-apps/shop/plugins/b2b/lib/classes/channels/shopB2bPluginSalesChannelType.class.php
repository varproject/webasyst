<?php

class shopB2bPluginSalesChannelType extends shopSalesChannelType
{
    protected function getBaseFieldsConfig(): array
    {
        $base = parent::getBaseFieldsConfig();
        $base['description']['class'] = 'smallest';

        return ['status' => [
            'title' => _w('Enabled'),
            'control_type' => waHtmlControl::CHECKBOX,
        ]] + $base;
    }

    public function getFormHtml(array $channel): string
    {
        $view       = wa('shop')->getView();
        $view_dir   = wa()->getAppPath('plugins/b2b/templates/actions/channels/', 'shop');

        // Вкладки и формы
        $data       = $this->getTabs($channel);
        $tabs       = $data['all'] ?? [];
        $active     = $data['active'] ?? [];
        $base_form  = $this->getBaseRenderedFields($channel);

        $view->assign([
            'channel'   => $channel,
            'tabs'      => $tabs,
            'active'    => $active,
            'base_form' => $base_form,
        ]);

        // Рендер формы текущей вкладки
        $view->assign('form_html', $view->fetch($view_dir . ($active['tpl'] ?? '')));

        return $view->fetch($view_dir . 'Channels.html');
    }



    // Валидация формы настроек
    // public function sanitizeAndValidateParams(?int $id, array &$params, $params_mode): array
    // {
    //     $tab = $params['_b2b_settings_tab'] ?? 'main';
    //     $class = '';


    //     // if (!class_exists($class)) {
    //     //     return [];
    //     // }

    //     // $action = new $class();

    //     // if (method_exists($action, 'normalizeParams')) {
    //     //     $params = $action->normalizeParams($params, $params_mode, $id);
    //     // }

    //     // return method_exists($action, 'validateParams')
    //     //     ? $action->validateParams($params, $params_mode, $id)
    //     //     : [];

    //     return [];
    // }

    // Рендер базовых полей. Вернет html
    protected function getBaseRenderedFields(array $channel): array
    {
        $result = [];

        if (empty($channel['id'])) {
            $channel['name'] = $this->get('name');
        }

        $field_params = ['namespace' => 'data'] + $this->getFormFieldParams();

        foreach ($this->getBaseFieldsConfig() as $name => $row) {
            $result[$name] = $this->getControl($name, ifset($channel, $name, ''), $field_params + $row);
        }

        return $result;
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
                'tpl'    => 'Channels' . ucfirst($key) . '.html',
                'url'    => $this->getTabUrl($channel_id, $key),
            ];
        }

        return [
            'all' => $tabs,
            'active' => $tabs[$active_tab_id] ?? ($tabs['main'] ?? []),
        ];
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
