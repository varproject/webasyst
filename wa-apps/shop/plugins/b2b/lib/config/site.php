<?php

// return [
//     'vars_tab_names' => [
//         'b2b.access_denied' => 'B2B-витрина',
//     ],

//     'vars' => [
//         'b2b.access_denied' => [
//             '{$channel}'                    => 'Данные B2B-канала продаж.',
//             '{$channel.id}'                 => 'ID B2B-канала.',
//             '{$channel.name}'               => 'Название B2B-канала.',
//             '{$params}'                     => 'Параметры B2B-канала.',
//             '{$params.settlement}'          => 'Название выбранного поселения.',
//             '{$params.frontend_url}'        => 'URL-паттерн B2B-витрины.',
//             '{$params.frontend_custom_url}' => 'Пользовательский URL витрины без routing-mask.',
//             '{$params.access_mode}'         => 'Режим доступа: all, customers или categories.',
//             '{$contact}'                    => 'Текущий пользователь/контакт.',
//             '{$contact->getId()}'           => 'ID текущего пользователя.',
//             '{$contact->getName()}'         => 'Имя текущего пользователя.',
//             '{$contact->isAuth()}'          => 'Проверка авторизации пользователя.',
//             '{$login_url}'                  => 'URL страницы входа.',
//             '{$register_url}'               => 'URL страницы регистрации или личного кабинета.',
//         ],
//     ],
// ];

return array(
    'vars' => array(
        '$wa' => array(
            '$wa->shop->b2bPlugin->version()' => 'Проверка, что B2B-плагин установлен и включён.',
        ),
        'b2b.html' => array(
            '$channel' => 'Текущий B2B-канал продаж.',
            '$channel.params' => 'Параметры текущего B2B-канала.',
            '$access_denied_block_params.channel' => 'B2B-канал, переданный в блок страницы ограничения доступа.',
            '$access_denied_block_params.params' => 'Параметры B2B-канала.',
            '$access_denied_block_params.contact' => 'Текущий пользователь/контакт.',
            '$access_denied_block_params.login_url' => 'URL входа.',
            '$access_denied_block_params.register_url' => 'URL регистрации.',
        ),
    ),
);
