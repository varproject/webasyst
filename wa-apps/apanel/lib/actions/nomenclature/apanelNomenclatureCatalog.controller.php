<?php

class apanelNomenclatureCatalogController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new apanelBackendLayout());

        // Ппанель навигации и управления
        $catalog_model = new apanelCatalogModel();
        $catalog_data  = $catalog_model->getCatalogsData(apanelUrlSegment::getInt(3));
        $catalog_id    = $catalog_data['default']['id'] ?? 0;

        waRequest::setParam('active_catalog_id', $catalog_id);

        $this->prepareNavbar($catalog_data);
        $this->prepareToolbar($catalog_data);


        // Дерево категории
        $this->executeAction(new apanelCategoryTreeAction(), 'main_body_tree_items');


        // Таблица товаров
        $category_id     = waRequest::param('active_category_id');
        $product_service = new apanelCategoryProductReadService();
        $products_data   = $product_service->getCategoryProducts($catalog_id, $category_id);


        // Таблица товаров
        $this->executeAction(new apanelTableAction([
            'items' => $products_data['items'] ?? $products_data,

            'columns' => [
                'name' => [
                    'title'       => 'Наименование',
                    'type'        => 'title',
                    'tdclass'     => 'w-100',
                    'url_pattern' => '?edit_product=%id%',
                ],
                'sku' => [
                    'title' => 'Артикул',
                    'type'  => 'text',
                ],
                'price' => [
                    'title'   => 'Цена',
                    'type'    => 'price',
                    'tdclass' => 'text-end',
                ],
                'count' => [
                    'title'   => 'Остаток',
                    'type'    => 'number',
                    'tdclass' => 'text-end',
                ],
                'is_active' => [
                    'title' => 'Статус',
                    'type'  => 'bool',
                ],
            ],

            'show_checkbox'      => true,
            'show_actions'       => true,
            'action_url_pattern' => '?edit_product=%id%',
            'action_item_key'    => 'id',
            'empty_text'         => 'В этой категории пока нет товаров.',
        ]), 'main_body_table_items');
    }


    public function prepareNavbar(array $catalog_data)
    {
        $all_catalogs       = $catalog_data['all']     ?? [];
        $enabled_catalogs   = $catalog_data['enabled'] ?? [];
        $default_catalog    = $catalog_data['default'] ?? null;

        // dd($enabled_catalogs);

        // 1. Левая часть. Узлы навигации
        if (!empty($default_catalog['id'])) {
            apanelNavigation::insertNode('nomenclature.catalog', $enabled_catalogs);
            // $this->layout->assign('main_navbar_left_items', apanelNavigation::getLvl(3, [])); // из лояута вставка
            $push_url = waConfig::get('apanel_module_path') . $default_catalog['id'] . '/';
        } else {
            $this->layout->assign('main_navbar_left_message_text', 'Нет активных каталогов. Сначала создайте или включите в настройках.');
            $push_url = waConfig::get('apanel_module_path');
        }

        // 2. Правая часть. Выпадающий список настроек
        $dropdown_params = [
            'dropdown_title'     => 'КАТАЛОГИ',
            'items'              => $all_catalogs,
            'sort_post_url'      => '?module=catalogSort',
            'sort_oob'           => ['#sidebar_body', '#header_left', '#main_navbar_left'],
            'toggle_post_url'    => '?module=catalogSwitch',
            'toggle_oob'         => ['#sidebar_body', '#header_left', '#main_navbar_left', '#main_toolbar', '#main_body', '#main_footer'],
            'edit_url_pattern'   => '?edit_catalog=%id%',
            'delete_url_pattern' => '?delete_catalog=%id%',
            'add_button_name'    => 'Создать каталог',
            'add_url'            => '?add_catalog',
            'hidden_input_name'  => 'catalog_id',
        ];

        $items_dropdown_html = apanelUi::getControl('items_dropdown', 'items_dropdown', $dropdown_params);
        $this->layout->assign('main_navbar_right_items', $items_dropdown_html);

        // 3. Обновление url в строке
        if (
            !waRequest::get('category_id', 0, waRequest::TYPE_INT)
            && !waRequest::get('toggle', 0, waRequest::TYPE_INT)
            && !waRequest::get('items_action', '', waRequest::TYPE_STRING)
        ) {
            wa()->getResponse()->addHeader('HX-Push-Url', $push_url);
        }
    }

    public function prepareToolbar(array $catalog_data)
    {
        $catalog         = $catalog_data['default'] ?? null;
        $current_url     = '/' . wa()->getRouting()->getCurrentUrl();
        $disabled_status = empty($catalog) ? ' disabled' : '';

        $toolbar_left_items = [
            apanelUi::getControl('dropdown', 'create_button', [
                'class'      => 'btn btn-success btn-sm dropdown-toggle',
                'label'      => 'Создать',
                'icon'       => '<i class="bi bi-plus-lg"></i>',
                'disabled'   => !empty($disabled_status),
                'menu_class' => 'user-select-none',
                'attrs'      => [
                    'data-bs-auto-close' => 'outside',
                ],
                'actions'    => [
                    [
                        'label' => 'Категория',
                        'icon'  => '<i class="bi bi-folder-plus me-1"></i>',
                        'url'   => $current_url . '?add_category',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                    [
                        'label' => 'Товар',
                        'icon'  => '<i class="bi bi-box-seam me-1"></i>',
                        'url'   => $current_url . '?add_product',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                    [
                        'label' => 'Комплект',
                        'icon'  => '<i class="bi bi-boxes me-1"></i>',
                        'url'   => $current_url . '?add_set',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                    [
                        'label' => 'Услуга',
                        'icon'  => '<i class="bi bi-tools me-1"></i>',
                        'url'   => $current_url . '?add_service',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                ],
            ]),

            apanelUi::getControl('dropdown', 'filter_button', [
                'class'      => 'btn btn-primary btn-sm dropdown-toggle',
                'label'      => 'Фильтр',
                'icon'       => '<i class="bi bi-funnel"></i>',
                'disabled'   => !empty($disabled_status),
                'menu_class' => 'user-select-none',
                'attrs'      => [
                    'data-bs-auto-close' => 'outside',
                ],
                'actions'    => [
                    [
                        'label' => 'По категориям',
                        'icon'  => '<i class="bi bi-folder me-1"></i>',
                        'url'   => $current_url . '?filter_category',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                    [
                        'label' => 'По товарам',
                        'icon'  => '<i class="bi bi-box-seam me-1"></i>',
                        'url'   => $current_url . '?filter_product',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                    [
                        'label' => 'По комплектам',
                        'icon'  => '<i class="bi bi-boxes me-1"></i>',
                        'url'   => $current_url . '?filter_set',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                    [
                        'label' => 'По услугам',
                        'icon'  => '<i class="bi bi-tools me-1"></i>',
                        'url'   => $current_url . '?filter_service',
                        'attrs' => ['hx-boost' => 'true'],
                    ],
                ],
            ]),

            apanelUi::getControl('search', 'search', [
                'class'       => 'form-control-sm w-25',
                'disabled'    => !empty($disabled_status),
                'placeholder' => 'Название или код',
                'action_url'  => $current_url,
                'hidden_id'   => '',
                'query'       => '',
            ]),
        ];

        $toolbar_right_items = apanelUi::getControl('dropdown_panel', 'toolbar_panel', [
            'counter'  => 12,
            'disabled' => $disabled_status,

            'groups' => [
                [
                    'label'      => 'Изменить',
                    'icon'       => 'bi bi-sliders2',
                    'auto_close' => 'outside',
                    'menu_class' => 'dropdown-menu user-select-none',
                    'menu_style' => '--bs-dropdown-link-active-bg: transparent; --bs-dropdown-link-active-color: inherit;',
                    'items'      => [
                        [
                            'type'    => 'checkbox',
                            'id'      => 'check1',
                            'name'    => 'check1',
                            'value'   => '1',
                            'label'   => 'Категория 1',
                            'checked' => true,
                        ],
                        [
                            'type'    => 'checkbox',
                            'id'      => 'check2',
                            'name'    => 'check2',
                            'value'   => '1',
                            'label'   => 'Категория 2',
                        ],
                        ['divider' => true],
                        [
                            'label' => 'Настройка шаблонов',
                            'url'   => '#',
                        ],
                    ],
                ],

                [
                    'label' => 'Печать',
                    'icon'  => 'bi bi-printer',
                    'items' => [
                        ['label' => 'Счет', 'url' => '#'],
                        ['label' => 'Накладная', 'url' => '#'],
                        ['divider' => true],
                        ['label' => 'Настройка шаблонов', 'url' => '#'],
                    ],
                ],

                [
                    'label' => 'Импорт',
                    'icon'  => 'bi bi-arrow-up-short',
                    'items' => [
                        ['label' => 'Импорт CSV', 'url' => '#'],
                        ['label' => 'Импорт XML', 'url' => '#'],
                        ['divider' => true],
                        ['label' => 'Настройка шаблонов', 'url' => '#', 'icon' => 'bi bi-gear'],
                    ],
                ],

                [
                    'label' => 'Экспорт',
                    'icon'  => 'bi bi-arrow-down-short',
                    'items' => [
                        ['label' => 'Экспорт CSV', 'url' => '#'],
                        ['label' => 'Экспорт XLSX', 'url' => '#'],
                        ['divider' => true],
                        ['label' => 'Настройка шаблонов', 'url' => '#', 'icon' => 'bi bi-gear'],
                    ],
                ],
            ],

            'config' => [
                'items' => [
                    ['label' => 'Действие', 'url' => '#'],
                    ['label' => 'Другое действие', 'url' => '#'],
                    ['label' => 'Что-то ещё', 'url' => '#'],
                    ['divider' => true],
                    ['label' => 'Отдельная ссылка', 'url' => '#'],
                ],
            ],
        ]);

        $this->layout->assign('main_toolbar_left_title_text', $catalog['name'] ?? 'Каталог');
        $this->layout->assign('main_toolbar_left_items', $toolbar_left_items);
        $this->layout->assign('main_toolbar_right_items', $toolbar_right_items);
    }
}
