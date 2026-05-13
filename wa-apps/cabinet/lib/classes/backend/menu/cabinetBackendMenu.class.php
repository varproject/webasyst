<?php

class cabinetBackendMenu
{
    /**
     * Единая структура меню B2B:
     *  - главное меню
     *  - вложенные подменю
     *
     * @return array
     */
    public static function getMenu(): array
    {
        // ИСПРАВЛЕНО: корректное имя параметра
        $base = waRequest::param('backend_full_app_url');

        return [

            // =======================
            // ДАШБОРД
            // =======================
            [
                'id'          => 'dashboard',
                'name'        => 'Показатели',
                'url'         => '',
                'full_url'    => $base,
                'icon'        => 'fas fa-home',
                'permissions' => ['dashboard'],
                'submenu'     => [], // нет подменю
            ],

            // =======================
            // ПРОДАЖИ
            // =======================
            [
                'id'          => 'selling',
                'name'        => 'Продажи',
                'url'         => 'selling',
                'full_url'    => $base . 'selling/new/',
                'icon'        => 'fas fa-building',
                'heading'     => 'Продажи',
                'permissions' => ['selling'],
                'submenu'     => [
                    [
                        'heading' => 'Менеджер',
                        'class'   => 'header',
                    ],
                    [
                        'id'       => 'new',
                        'name'     => 'Новый заказ',
                        'url'      => 'new',
                        'full_url' => $base . 'selling/new/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'processing',
                        'name'     => 'Доп. обработка',
                        'url'      => 'processing',
                        'full_url' => $base . 'selling/processing/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'awaiting_payment',
                        'url'      => 'awaiting-payment',
                        'name'     => 'Ожидание оплаты',
                        'full_url' => $base . 'selling/awaiting-payment/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'paid',
                        'url'      => 'paid',
                        'name'     => 'Оплачено',
                        'full_url' => $base . 'selling/paid/',
                        'icon'     => '',
                    ],

                    [
                        'heading' => 'Склад и доставка',
                        'class'   => 'body',
                    ],
                    [
                        'id'       => 'awaiting_assembly',
                        'name'     => 'Комплектация',
                        'url'      => 'awaiting-assembly',
                        'full_url' => $base . 'selling/awaiting-assembly/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'awaiting_shipment',
                        'name'     => 'Готов к отгрузке',
                        'url'      => 'awaiting-shipment',
                        'full_url' => $base . 'selling/awaiting-shipment/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'delivering',
                        'name'     => 'Доставляется',
                        'url'      => 'delivering',
                        'full_url' => $base . 'selling/delivering/',
                        'icon'     => '',
                    ],

                    [
                        'heading' => 'Контроль качества',
                        'class'   => 'header',
                    ],
                    [
                        'id'       => 'delivered',
                        'name'     => 'Выполнен',
                        'url'      => 'delivered',
                        'full_url' => $base . 'selling/delivered/',
                        'icon'     => '',
                    ],

                    // Дальше у тебя сейчас три пункта под один и тот же URL.
                    // Я просто выровнял url под full_url, чтобы активность работала.
                    [
                        'id'       => 'cancelled',
                        'name'     => 'Отмененен без отгрузки',
                        'url'      => 'cancelled',
                        'full_url' => $base . 'selling/cancelled/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'cancelled',
                        'name'     => 'Отмененен без отгрузки',
                        'url'      => 'cancelled',
                        'full_url' => $base . 'selling/cancelled/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'cancelled',
                        'name'     => 'Частичный возврат',
                        'url'      => 'cancelled',
                        'full_url' => $base . 'selling/cancelled/',
                        'icon'     => '',
                    ],
                    [
                        'id'       => 'status_settings',
                        'name'     => 'Настройки',
                        'url'      => 'statuses',
                        'full_url' => $base . 'selling/statuses/',
                        'icon'     => 'fas fa-cog',
                        'class'    => 'footer',
                    ],
                ],
            ],

        ];
    }
}
