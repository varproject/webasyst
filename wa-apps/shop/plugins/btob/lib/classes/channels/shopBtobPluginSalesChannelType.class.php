<?php

class shopBtobPluginSalesChannelType extends shopSalesChannelType
{
    public function getFormHtml(array $channel): string
    {
        $view       = wa('shop')->getView();
        $view_dir   = wa()->getAppPath('plugins/btob/templates/actions/channels/', 'shop');

        // Вкладки и формы
        $tab_data       = $this->getTabs($channel, $view_dir);
        $base_fields    = $this->getBaseRenderedFields($channel);
        $settings       = $channel['params'] ?? [];

        $tabs           = $tab_data['all'] ?? [];
        $active_tab     = $tab_data['active'] ?? [];
        $active_class   = $tab_data['active']['class'] ?? '';

        // Общие параметры настроек
        $view->assign([
            'channel'       => $channel,
            'tabs'          => $tabs,
            'base_fields'   => $base_fields,
            'settings'      => $settings,
            'active_class'  => $active_class,
        ]);

        // Рендер формы текущей вкладки
        if (class_exists($active_class)) {
            $instance = new $active_class();
            if ($instance instanceof shopBtobPluginSalesChannelTypeInterface) {
                $instance->renderForm($view, $channel, $settings);
                $view->assign('form_html', $view->fetch($active_tab['template']));
            } else {
                throw new waException("Класс $active_class должен реализовывать shopBtobPluginSalesChannelTypeInterface");
            }
        }

        // Рендер общей обертки
        return $view->fetch($view_dir . 'SalesChannelType.html');
    }

    // Валидация форм настроек, делигируется на класс текущей вкладки
    public function sanitizeAndValidateParams(?int $id, array &$params, mixed $params_mode): array
    {
        $handler = $params['btob_handler_class'] ?? '';

        if (class_exists($handler)) {
            $instance = new $handler();
            if ($instance instanceof shopBtobPluginSalesChannelTypeInterface) {
                $errors = $instance->validateParams($id, $params, $params_mode);
            } else {
                throw new waException("Класс $handler должен реализовывать shopBtobPluginSalesChannelTypeInterface");
            }
        }

        return array_values($errors ?? []);
    }

    // Оверайд базовых полей
    protected function getBaseFieldsConfig(): array
    {
        $base = parent::getBaseFieldsConfig();
        $base['description']['class'] = 'smallest';

        return ['status' => [
            'title' => _w('Enabled'),
            'control_type' => waHtmlControl::CHECKBOX,
        ]] + $base;
    }

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
    protected function getTabs(array $channel, string $view_dir)
    {
        $channel_id = (int)($channel['id'] ?? 0);
        $active_tab_id = waRequest::get('btob_tab', 'main', waRequest::TYPE_STRING_TRIM);

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
                'id'        => $key,
                'label'     => $tab['label'],
                'active'    => ($key === $active_tab_id),
                'template'  => $view_dir . 'SalesChannelType' . ucfirst($key) . '.html',
                'class'     => 'shopBtobPluginSalesChannelType' . ucfirst($key),
                'url'       => $this->getTabUrl($channel_id, $key),
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
            $url .= '?btob_tab=' . urlencode($tab_id);
        }

        return $url;
    }
}
