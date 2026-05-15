<?php

return [
    'vars_tab_names' => [
        'b2b.access_denied' => 'B2B-витрина',
    ],

    'vars' => [
        'b2b.access_denied' => [
            '{$channel}'               => 'Данные B2B-канала продаж.',
            '{$channel.id}'            => 'ID B2B-канала.',
            '{$channel.name}'          => 'Название B2B-канала.',
            '{$params}'                => 'Параметры B2B-канала.',
            '{$params.settlement}'     => 'Название выбранного поселения.',
            '{$params.frontend_url}'   => 'URL-паттерн B2B-витрины.',
            '{$contact}'               => 'Текущий пользователь/контакт.',
            '{$contact->getId()}'      => 'ID текущего пользователя.',
            '{$contact->getName()}'    => 'Имя текущего пользователя.',
            '{$contact->isAuth()}'     => 'Проверка авторизации пользователя.',
            '{$login_url}'             => 'URL страницы входа.',
            '{$register_url}'          => 'URL страницы регистрации или личного кабинета.',
        ],
    ],
];
