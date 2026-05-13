<?php

return [

    // ГЛАВНАЯ
    'home' => [
        'name'    => 'Главная',
        'icon'     => 'apanel-icon-home',
        // 'module' => 'homeIndicator',
        'children' => [

            // ПОКАЗАТЕЛИ
            'indicator' => [
                'name' => 'Показатели',
                'icon'  => '<i class="bi bi-speedometer"></i>',
                'module' => 'homeIndicator',
            ],

            // КАЛЕНДАРЬ
            'calendar' => [
                'name' => 'Календарь',
                'icon'  => '<i class="bi bi-calendar2-date"></i>',
            ],

            // ЖУРНАЛ
            'journal' => [
                'name' => 'Журнал',
                'icon'  => '<i class="bi bi-journal-code"></i>',
            ],

            // БАЗА ЗНАНИЙ
            'infopage' => [
                'name' => 'База знаний',
                'icon'  => '<i class="bi bi-info-square"></i>',
            ],
        ],
    ],

    // КАТАЛОГ
    'nomenclature' => [
        'name' => 'Номенклатура',
        'icon'  => 'apanel-icon-automatic-gearbox',

        'children' => [

            // Каталог
            'catalog' => [
                'name' => 'Каталог',
                // 'icon'  => 'apanel-icon-packages',
                'icon'  => '<i class="bi bi-boxes"></i>',
                'module' => 'nomenclatureCatalog',
            ],


            // ЦЕНЫ
            'price' => [
                'name' => 'Цены',
                // 'icon'  => 'apanel-icon-receipt-2',
                'icon'  => '<i class="bi bi-currency-dollar"></i>',

                'children' => [

                    // Типы цен
                    'type' => [
                        'name'  => 'Типы цен',
                        'icon'   => 'icon-info-square',
                        'module' => 'nomenclaturePrice',
                    ],

                    'export' => [
                        'name' => 'Экпорт',
                        'icon'  => 'icon-export',
                        'module' => 'nomenclaturePrice',
                    ],
                ],
            ],

            'feature' => [
                'name' => 'Доп. поля',
                'icon'  => 'icon-filter',
            ],
            'pricelist' => [
                'name' => 'Прайсы',
                'icon'  => 'icon-file-excel',
            ],
            'crptorder' => [
                'name' => 'Маркировка',
                'icon'  => 'icon-checkbox',
            ],
            'import' => [
                'name' => 'Импорт',
                'icon'  => 'icon-arrow-autofit-down',
            ],
            'export' => [
                'name' => 'Экспорт',
                'icon'  => 'icon-arrow-autofit-up',
            ],
        ],
    ],

    // ПРОДАЖИ
    'selling' => [
        'name'    => 'Продажи',
        'icon'     => 'apanel-icon-shopping-cart',
        'module' => 'selling',
        'children' => [
            // ЗАКАЗЫ
            'order' => [
                'name' => 'Заказы',
                'icon'  => 'icon-shopping-bag',
            ],
            // СЧЕТА
            'payment' => [
                'name' => 'Счета',
                'icon'  => 'icon-receipt',
            ],
            // СБОРКА
            'product' => [
                'name' => 'Сборка',
                'icon'  => 'icon-list-search',
            ],
            // СЦЕНАРИЙ
            'script' => [
                'name' => 'Сценарий',
                'icon'  => 'icon-calendar-code',
            ],
            // ЭКРАНЫ
            'screen' => [
                'name' => 'Экраны',
                'icon'  => 'icon-screen-share',
            ],
        ],
    ],

    // МОЙ СКЛАД
    'warehouse' => [
        'name'    => 'Мой склад',
        'icon'     => 'apanel-icon-building-warehouse',
        'module' => 'warehouse',
        'children' => [
            // ОСТАТКИ
            'stock' => [
                'name' => 'Остатки',
            ],
            // ЗАКУПКИ
            'purchase' => [
                'name' => 'Закупки',
            ],
            // ПРИЕМКИ
            'enter' => [
                'name' => 'Приемки',
            ],
            // ОТГРУЗКИ
            'shipment' => [
                'name' => 'Отгрузки',
                'icon'  => 'icon-truck-delivery',
            ],
            // ВОЗВРАТЫ
            'return' => [
                'name' => 'Возвраты',
            ],
            // СПИСАНИЯ
            'writeoff' => [
                'name' => 'Списания',
            ],
            // ПЕРЕМЕЩЕНИЯ
            'transfer' => [
                'name' => 'Перемещения',
            ],
            // ИНВЕНТАРИЗАЦИЯ
            'inventory' => [
                'name' => 'Инвентаризация',
            ],
            // МАРКИРОВКА
            'marking' => [
                'name' => 'Маркировка',
                'icon'  => 'icon-barcode',
            ],
        ],
    ],

    // КОНТРАГЕНТЫ
    'counterparty' => [
        'name'    => 'Контрагенты',
        'icon'     => 'apanel-icon-briefcase',
        'module' => 'counterparty',
        'children' => [
            'company' => [
                'name' => 'Контрагенты',
            ],
            'contracts' => [
                'name' => 'Договоры',
            ],
            'import' => [
                'name' => 'Импорт/Экспорт',
            ],
            'setting' => [
                'name' => 'Настройки',
            ],
            'buyers' => [
                'name' => 'Покупатели',
            ],
            'suppliers' => [
                'name' => 'Поставщики',
            ],
            'contractors' => [
                'name' => 'Подрядчики',
            ],
            'services' => [
                'name' => 'Сервисы и площадки',
            ],
            'employees' => [
                'name' => 'Сотрудники',
            ],
        ],
    ],

    // АНАЛИТИКА
    'analytics' => [
        'name'    => 'Аналитика',
        'icon'     => 'apanel-icon-brand-google-analytics',
        'module' => 'analytics',
        'children' => [
            'finance' => [
                'name' => 'Финансы',
            ],
            'warehouse' => [
                'name' => 'Запасы',
            ],
            'export' => [
                'name' => 'Экспорт товаров',
            ],
        ],
    ],

    // БУХГАЛТЕРИЯ
    'accounting' => [
        'name'    => 'Бухгалтерия',
        'icon'     => 'apanel-icon-calculator',
        'module' => 'accounting',
        'children' => [
            'bankcash' => [
                'name' => 'Счета',
            ],
            'selling' => [
                'name' => 'Отгрузки',
            ],
            'returns' => [
                'name' => 'Возвраты',
            ],
            'purchase' => [
                'name' => 'Закупки',
            ],
            'expenses' => [
                'name' => 'Расходы',
            ],
            'payroll' => [
                'name' => 'Зарплата',
            ],
            'taxes' => [
                'name' => 'Налоги',
            ],
            'kudir' => [
                'name' => 'КУДиР',
            ],
            'reconcile' => [
                'name' => 'Сверки',
            ],
            'reports' => [
                'name' => 'Отчетность',
            ],
            'setting' => [
                'name' => 'Настройки',
            ],
        ],
    ],

    // НАСТРОЙКИ
    'settings' => [
        'name'    => 'Настройки',
        'icon'    => 'apanel-icon-settings-outline',
        'children' => [
            'storefront' => [
                'name' => 'Витрина',
            ],
            'system' => [
                'name' => 'Система',
            ],
            'company' => [
                'name' => 'Организации',
            ],
            'employee' => [
                'name' => 'Сотрудники',
            ],
            'channel' => [
                'name' => 'Каналы продаж',
            ],
            'warehouse' => [
                'name' => 'Склады',
            ],
            'currency' => [
                'name' => 'Валюты',
            ],
            'taxrate' => [
                'name' => 'Налоги',
            ],
            'country' => [
                'name' => 'Страны',
            ],
            'uom' => [
                'name' => 'Единицы измерения',
            ],
        ],
    ],
];
