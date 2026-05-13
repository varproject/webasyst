<?php

class apanelShopordersPlugin extends waPlugin
{
    public function storefrontDataSources($params = [])
    {
        return [
            'shop_orders' => [
                'id'          => 'shop_orders',
                'plugin_id'   => $this->getId(),
                'plugin'      => $this->getId(),
                'name'        => 'Заказы Shop-Script',
                'description' => 'Заказы Shop-Script с фильтрацией по статусам, каналам продаж, доставке и оплате.',
                'type'        => 'orders',
                'settings'    => $this->getNormalizedSettings(),
            ],
        ];
    }

    public function storefrontShopOrders($params = [])
    {
        $provider = new apanelShopordersPluginOrderProvider($this);

        return [
            'source_id' => 'shop_orders',
            'plugin_id' => $this->getId(),
            'orders'    => $provider->getOrders((array) $params),
            'filters'   => $this->getNormalizedSettings(),
        ];

        dd($this->storefrontDataSources());
    }

    public function getControls($params = [])
    {
        $namespace = (string) ifset($params['namespace'], 'settings');
        $settings = $this->getNormalizedSettings();
        $provider = new apanelShopordersPluginOrderProvider($this);

        return [
            'statuses' => $this->renderCheckboxGroupControl([
                'id'          => 'statuses',
                'name'        => $namespace . '[statuses][]',
                'title'       => 'Статусы заказов',
                'description' => 'Если ничего не выбрано, будут возвращаться заказы во всех статусах.',
                'options'     => $provider->getStatusOptions(),
                'value'       => $settings['statuses'],
            ]),

            'sales_channels' => $this->renderCheckboxGroupControl([
                'id'          => 'sales_channels',
                'name'        => $namespace . '[sales_channels][]',
                'title'       => 'Каналы продаж',
                'description' => 'Если ничего не выбрано, канал продаж не ограничивается.',
                'options'     => $provider->getSalesChannelOptions(),
                'value'       => $settings['sales_channels'],
            ]),

            'shipping_types' => $this->renderCheckboxGroupControl([
                'id'          => 'shipping_types',
                'name'        => $namespace . '[shipping_types][]',
                'title'       => 'Типы доставки',
                'description' => 'Фильтрация по значениям shipping_id в заказах Shop-Script.',
                'options'     => $provider->getShippingTypeOptions(),
                'value'       => $settings['shipping_types'],
            ]),

            'payment_types' => $this->renderCheckboxGroupControl([
                'id'          => 'payment_types',
                'name'        => $namespace . '[payment_types][]',
                'title'       => 'Типы оплаты',
                'description' => 'Фильтрация по значениям payment_id в заказах Shop-Script.',
                'options'     => $provider->getPaymentTypeOptions(),
                'value'       => $settings['payment_types'],
            ]),
        ];
    }

    public function saveSettings($settings = [])
    {
        $settings = [
            'statuses'       => $this->normalizeStringArray(ifset($settings['statuses'], [])),
            'sales_channels' => $this->normalizeStringArray(ifset($settings['sales_channels'], [])),
            'shipping_types' => $this->normalizeStringArray(ifset($settings['shipping_types'], [])),
            'payment_types'  => $this->normalizeStringArray(ifset($settings['payment_types'], [])),
        ];

        parent::saveSettings($settings);
    }

    public function getNormalizedSettings()
    {
        return [
            'statuses'       => $this->normalizeStringArray($this->getSettings('statuses')),
            'sales_channels' => $this->normalizeStringArray($this->getSettings('sales_channels')),
            'shipping_types' => $this->normalizeStringArray($this->getSettings('shipping_types')),
            'payment_types'  => $this->normalizeStringArray($this->getSettings('payment_types')),
        ];
    }

    protected function renderCheckboxGroupControl(array $params)
    {
        $id = (string) ifset($params['id'], '');
        $name = (string) ifset($params['name'], '');
        $title = (string) ifset($params['title'], '');
        $description = (string) ifset($params['description'], '');
        $options = (array) ifset($params['options'], []);
        $selected = $this->normalizeStringArray(ifset($params['value'], []));

        $html = '<div class="mb-3">';
        $html .= '<div class="form-label fw-bold">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';

        if ($description !== '') {
            $html .= '<div class="form-text mb-2">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        if (!$options) {
            $html .= '<div class="alert alert-light border mb-0">';
            $html .= 'Нет доступных значений. Проверьте наличие приложения Shop-Script и заказов.';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }

        $index = 0;

        foreach ($options as $value => $label) {
            $value = (string) $value;
            $label = (string) $label;
            $field_id = 'plugin_' . $this->getId() . '_' . $id . '_' . $index;

            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input" type="checkbox"';
            $html .= ' id="' . htmlspecialchars($field_id, ENT_QUOTES, 'UTF-8') . '"';
            $html .= ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
            $html .= ' value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';

            if (in_array($value, $selected, true)) {
                $html .= ' checked';
            }

            $html .= '>';

            $html .= '<label class="form-check-label" for="' . htmlspecialchars($field_id, ENT_QUOTES, 'UTF-8') . '">';
            $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $html .= '</label>';
            $html .= '</div>';

            $index++;
        }

        $html .= '</div>';

        return $html;
    }

    protected function normalizeStringArray($value)
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $result = [];

        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item === '') {
                continue;
            }

            $result[] = $item;
        }

        return array_values(array_unique($result));
    }
}
