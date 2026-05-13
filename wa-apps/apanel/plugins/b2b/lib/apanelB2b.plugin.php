<?php

/**
 * apanelB2bPlugin
 *
 * Официальный B2B plugin-продукт для storefront platform приложения Apanel.
 *
 * Назначение:
 * - зарегистрировать B2B как доступный plugin-продукт витрины;
 * - объявить frontend screens B2B-кабинета;
 * - отдать дефолты access/auth для B2B-сценария;
 * - предоставить базовые настройки и assets plugin-продукта.
 *
 * Зависимости:
 * - waPlugin;
 * - события приложения Apanel: storefront_plugins, storefront_screens, storefront_data_sources.
 *
 * Инварианты:
 * - B2B не является core-конфигом Apanel;
 * - B2B регистрируется только как установленный Webasyst plugin приложения Apanel;
 * - публичный доступ по умолчанию для B2B не используется;
 * - frontend screens принадлежат plugin id b2b.
 */
class apanelB2bPlugin extends waPlugin
{
    /**
     * Регистрирует B2B как plugin-продукт витрины.
     *
     * @param array $params Параметры события.
     * @return array
     */
    public function storefrontPlugins($params = [])
    {
        return [
            'b2b' => [
                'id'          => 'b2b',
                'name'        => 'B2B кабинет',
                'description' => 'Личный кабинет для B2B-продаж, заказов и работы с контрагентами.',
                'version'     => '1.0.0',
                'plugin'      => 'b2b',
                'sort'        => 10,
                'access'      => [
                    'default_mode'  => 'authorized',
                    'allowed_modes' => ['authorized', 'groups', 'contacts', 'closed'],
                ],
                'auth' => [
                    'enabled'              => 1,
                    'registration_enabled' => 0,
                    'login_by'             => 'email',
                ],
                'screens'  => $this->getScreens(),
                'settings' => [
                    'defaults' => [],
                    'fields'   => [],
                ],
                'assets' => [
                    'css' => ['css/b2b.css'],
                    'js'  => ['js/b2b.js'],
                ],
            ],
        ];
    }

    /**
     * Возвращает screens B2B-кабинета.
     *
     * @param array $params Параметры события.
     * @return array
     */
    public function storefrontScreens($params = [])
    {
        if ((string) ifset($params['plugin_id'], '') !== 'b2b') {
            return [];
        }

        return [
            'screens' => $this->getScreens(),
        ];
    }

    /**
     * Регистрирует источники данных B2B.
     *
     * @param array $params Параметры события.
     * @return array
     */
    public function storefrontDataSources($params = [])
    {
        if ((string) ifset($params['plugin_id'], '') !== 'b2b') {
            return [];
        }

        return [
            'shop_products'      => ['id' => 'shop_products', 'name' => 'Товары Shop-Script'],
            'shop_orders'        => ['id' => 'shop_orders', 'name' => 'Заказы Shop-Script'],
            'contacts_companies' => ['id' => 'contacts_companies', 'name' => 'Компании и контакты'],
        ];
    }

    /**
     * Возвращает декларацию screens B2B-кабинета.
     *
     * @return array
     */
    protected function getScreens()
    {
        return [
            'dashboard' => [
                'id'              => 'dashboard',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Главная',
                'description'     => 'Стартовый экран B2B-кабинета.',
                'url'             => '',
                'sort'            => 10,
                'default_enabled' => 1,
                'template'        => 'screens/Dashboard.html',
            ],
            'catalog' => [
                'id'              => 'catalog',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Каталог',
                'description'     => 'Каталог товаров для B2B-заказа.',
                'url'             => 'catalog',
                'sort'            => 20,
                'default_enabled' => 1,
                'template'        => 'screens/Catalog.html',
            ],
            'cart' => [
                'id'              => 'cart',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Корзина',
                'description'     => 'Корзина или заявка на заказ.',
                'url'             => 'cart',
                'sort'            => 30,
                'default_enabled' => 1,
                'template'        => 'screens/Cart.html',
            ],
            'orders' => [
                'id'              => 'orders',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Заказы',
                'description'     => 'История и статусы заказов.',
                'url'             => 'orders',
                'sort'            => 40,
                'default_enabled' => 1,
                'template'        => 'screens/Orders.html',
            ],
            'companies' => [
                'id'              => 'companies',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Компании',
                'description'     => 'Компании и контрагенты пользователя.',
                'url'             => 'companies',
                'sort'            => 50,
                'default_enabled' => 1,
                'template'        => 'screens/Companies.html',
            ],
            'addresses' => [
                'id'              => 'addresses',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Адреса',
                'description'     => 'Адреса доставки.',
                'url'             => 'addresses',
                'sort'            => 60,
                'default_enabled' => 1,
                'template'        => 'screens/Addresses.html',
            ],
            'profile' => [
                'id'              => 'profile',
                'plugin_id'       => 'b2b',
                'plugin'          => 'b2b',
                'name'            => 'Профиль',
                'description'     => 'Данные пользователя.',
                'url'             => 'profile',
                'sort'            => 70,
                'default_enabled' => 1,
                'template'        => 'screens/Profile.html',
            ],
        ];
    }
}
