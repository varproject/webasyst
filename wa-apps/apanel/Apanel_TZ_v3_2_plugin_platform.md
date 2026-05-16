# Apanel v3.2

**Plugin-based платформа админ-панелей, витрин и frontend/backend-оркестрации**

**Актуализированное техническое задание**

- **Статус:** рабочая архитектурная редакция
- **Дата:** май 2026
- **Целевая платформа:** Webasyst / PHP 7.4+ / Smarty / Webasyst plugin architecture

---

## Содержание

1. [Назначение проекта](#1-назначение-проекта)
2. [Главная продуктовая модель](#2-главная-продуктовая-модель)
3. [Роли системы](#3-роли-системы)
4. [Ответственность Apanel core](#4-ответственность-apanel-core)
5. [Термины](#5-термины)
6. [Логическая модель хранения](#6-логическая-модель-хранения)
7. [Backend-интерфейс Apanel](#7-backend-интерфейс-apanel)
8. [Группы настроек route/storefront](#8-группы-настроек-routestorefront)
9. [Контракт plugin-продукта](#9-контракт-plugin-продукта)
10. [Контракт screen](#10-контракт-screen)
11. [Routing plugin-продукта](#11-routing-plugin-продукта)
12. [Frontend runtime и shell](#12-frontend-runtime-и-shell)
13. [Backend UI-kit и зоны](#13-backend-ui-kit-и-зоны)
14. [Settings fields contract](#14-settings-fields-contract)
15. [Access/Auth policy](#15-accessauth-policy)
16. [События Apanel](#16-события-apanel)
17. [Варианты реализации plugin](#17-варианты-реализации-plugin)
18. [Backend как SDK для разработчиков](#18-backend-как-sdk-для-разработчиков)
19. [Безопасность](#19-безопасность)
20. [Публикационные ограничения](#20-публикационные-ограничения)
21. [Отличия от редакции v3.1](#21-отличия-от-редакции-v31)
22. [Ближайшие реальные правки по коду](#22-ближайшие-реальные-правки-по-коду)
23. [Короткая формула проекта](#23-короткая-формула-проекта)

---

## 1. Назначение проекта

Apanel - приложение Webasyst, которое выступает как core-оркестратор для построения внутренних панелей, личных кабинетов, витрин, frontend-экранов и прикладных бизнес-интерфейсов через плагины приложения Apanel.

Apanel не реализует конкретную бизнес-логику витрины или кабинета. Он предоставляет инфраструктуру: backend-каркас, управление route/storefront, выбор plugin-продукта, runtime-контекст, систему настроек, UI-kit, хуки, сервисы нормализации и сохранения, access/auth-механизмы и frontend shell.

Пользователь устанавливает Apanel как конструктор и платформу управления, настраивает поселение приложения в «Сайте», а затем внутри Apanel выбирает plugin-продукт, который берет на себя конкретную бизнес-функциональность.

| Компонент | Смысл |
|---|---|
| Apanel app | Core-оркестратор, SDK и инфраструктурная платформа. |
| Apanel plugin | Функциональный продукт: B2B, сборщик заказов, каталог, личный кабинет, CRM-раздел и т.д. |
| Route в «Сайте» | Точка входа, которая передает управление приложению Apanel. |
| Storefront / instance | Конкретный route Apanel с настройками и выбранным plugin-продуктом. |

---

## 2. Главная продуктовая модель

### 2.1. Route создается в приложении «Сайт»

Пользователь в приложении «Сайт» создает поселение, которое передает управление приложению Apanel.

```text
domain.ru/b2b/*
domain.ru/cabinet/*
domain.ru/orders/*
domain.ru/collector/*
```

Apanel не обязан быть редактором системного `routing.php` как основной сценарий. Его задача - увидеть свои route-поселения, показать их в backend-таблице и позволить назначить plugin-продукт.

### 2.2. Storefront / panel instance

Каждый route приложения Apanel становится отдельной управляемой сущностью.

```text
storefront = domain + route_id + url + settings
storefront_key = domain_hash + '_' + route_id
```

### 2.3. Plugin-продукт

Plugin-продукт - установленный Webasyst plugin приложения Apanel. Он реализует конкретный функционал: B2B кабинет, сборщик заказов, каталог, личный кабинет клиента, экран очереди, панель партнера, панель поставщика, CRM-раздел или другой бизнес-интерфейс.

---

## 3. Роли системы

| Роль | Ответственность |
|---|---|
| Webasyst «Сайт» | Создает route-поселение и передает запрос приложению Apanel. |
| Apanel app | Оркестрирует route, settings, plugin selection, UI, shell, hooks, runtime. |
| Apanel plugin | Реализует конкретный бизнес-функционал. |
| Разработчик plugin | Подключается к контрактам Apanel и получает готовую инфраструктуру. |
| Пользователь | Настраивает route, выбирает plugin, включает screens, auth/access и UI. |

---

## 4. Ответственность Apanel core

### 4.1. Core делает

1. Находит все route-поселения приложения Apanel.
2. Формирует `storefront_key`.
3. Показывает route в backend-таблице.
4. Хранит настройки конкретного route.
5. Позволяет выбрать plugin-продукт.
6. Собирает декларации plugin-продуктов через события.
7. Собирает screens выбранного plugin.
8. Применяет access/auth policy выбранного plugin.
9. Отдает plugin runtime-контекст.
10. Предоставляет shell, UI-kit, формы и сервисы.
11. Сохраняет настройки единым способом.
12. Нормализует данные перед сохранением.
13. Предоставляет хуки для расширения.
14. Не лезет в бизнес-логику plugin.

### 4.2. Core не делает

- Не реализует B2B как встроенный core-модуль.
- Не хранит список plugin-продуктов в core-конфиге.
- Не знает бизнес-логику screens.
- Не является конструктором тем как основной моделью.
- Не навязывает plugin единственный frontend-шаблон.
- Не смешивает route-данные и settings.
- Не заменяет Webasyst plugin-модель своей module-моделью.

---

## 5. Термины

| Термин | Значение |
|---|---|
| Route / поселение | Правило маршрутизации Webasyst из приложения «Сайт». |
| Storefront / instance | Конкретный route Apanel с настройками. |
| `storefront_key` | Стабильный ключ `domain_hash_route_id`. |
| Apanel core | Оркестратор: routing, settings, UI, auth/access, runtime, hooks. |
| Apanel plugin | Функциональный продукт, установленный как plugin приложения Apanel. |
| Plugin product | Декларация бизнес-продукта внутри plugin. |
| Screen | Frontend/backend-экран, поставляемый plugin. |
| Shell | Общая оболочка Apanel, в которую вставляется plugin-контент. |
| UI-kit | Набор компонентов Apanel для backend/frontend. |
| Policy | Ограничения plugin по доступу, авторизации, screens, настройкам. |
| Runtime | Собранный контекст текущего route + settings + plugin. |

---

## 6. Логическая модель хранения

Route-данные не дублируются в настройках. Domain, `route_id`, `url`, `full_url` читаются из актуального Webasyst routing. В настройках хранится только то, что относится к Apanel и выбранному plugin.

```php
storefronts = [
    '{storefront_key}' => [
        'profile' => [
            'enabled'     => 1,
            'name'        => '',
            'description' => '',
        ],
        'plugin' => [
            'id'       => 'b2b',
            'settings' => [],
        ],
        'screens' => [
            '{screen_id}' => [
                'enabled' => 1,
                'name'    => '',
                'sort'    => 100,
            ],
        ],
        'access' => [
            'mode'     => 'public',
            'groups'   => [],
            'contacts' => [],
        ],
        'auth' => [
            'enabled'              => 0,
            'registration_enabled' => 0,
            'login_by'             => 'email',
            'after_login_url'      => '',
            'after_logout_url'     => '',
        ],
        'ui'       => [],
        'data'     => [],
        'seo'      => [],
        'advanced' => [],
    ],
];
```

Физическая реализация текущего кода может использовать `scope` / `scope_id` / `name` / `value`:

```text
scope=storefront
scope_id={storefront_key}
name=profile|plugin|screens|access|auth|ui|data|seo|advanced
```

---

## 7. Backend-интерфейс Apanel

Основной backend-интерфейс - таблица route-поселений Apanel.

| Колонка | Источник |
|---|---|
| ID | `route_id` |
| Название | `profile.name` или `_name` из routing |
| Домен | route domain |
| Адрес поселения | route url |
| Ссылка | `full_url` |
| Плагин | plugin label по `plugin.id` |
| Экраны | количество включенных screens |
| Доступы | access label |
| Авторизация | auth label |
| Статус | `profile.enabled` |

---

## 8. Группы настроек route/storefront

| Группа | Назначение |
|---|---|
| `profile` | Название, описание, включение/выключение. |
| `plugin` | Выбор plugin-продукта и settings выбранного plugin. |
| `screens` | Включение, переименование, сортировка screens. |
| `access` | Режим доступа с учетом plugin policy. |
| `auth` | Авторизация с учетом plugin policy. |
| `ui` | Общие shell/UI-настройки. |
| `data` | Источники данных и связи. |
| `seo` | Индексация, title/meta, robots-флаги. |
| `advanced` | Технические параметры. |

---

## 9. Контракт plugin-продукта

Plugin-продукт регистрируется через событие Apanel `storefront_plugins`. Дополнительно plugin может использовать `storefront_screens` и другие события.

```php
'handlers' => [
    'storefront_plugins' => 'storefrontPlugins',
    'storefront_screens' => 'storefrontScreens',
]
```

Пример plugin declaration:

```php
public function storefrontPlugins($params = [])
{
    return [
        'b2b' => [
            'id'          => 'b2b',
            'name'        => 'B2B кабинет',
            'description' => 'Личный кабинет для B2B-продаж.',
            'version'     => '1.0.0',
            'plugin'      => 'b2b',
            'sort'        => 10,

            'access' => [
                'default_mode'  => 'authorized',
                'allowed_modes' => ['authorized', 'groups', 'contacts', 'closed'],
            ],

            'auth' => [
                'required'             => 1,
                'enabled'              => 1,
                'registration_allowed' => 0,
                'registration_enabled' => 0,
                'login_by'             => 'email',
                'allowed_login_by'     => ['email'],
            ],

            'screens' => $this->getScreens(),

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
```

---

## 10. Контракт screen

Screen - экран внутри выбранного plugin-продукта. Core не обязан знать бизнес-логику screen.

```php
[
    'id'              => 'catalog',
    'plugin_id'       => 'b2b',
    'plugin'          => 'b2b',
    'name'            => 'Каталог',
    'description'     => 'Каталог товаров для B2B-заказа.',
    'url'             => 'catalog',
    'sort'            => 20,
    'default_enabled' => 1,
    'template'        => 'screens/Catalog.html',
    'action'          => '',
    'actions'         => [],
    'assets'          => [],
    'settings'        => [],
]
```

---

## 11. Routing plugin-продукта

### 11.1. Общая логика

15. Webasyst получает URL.
16. Приложение «Сайт» передает запрос приложению Apanel по route.
17. Apanel определяет текущий `storefront_key`.
18. Apanel читает выбранный `plugin_id`.
19. Apanel подмешивает routing выбранного plugin-продукта.
20. Дальше Webasyst dispatch открывает нужный plugin action.

### 11.2. Нормативный файл routing plugin

```text
wa-apps/apanel/plugins/{plugin_id}/lib/config/routing.php
```

```php
<?php

return [
    '/?' => 'frontend/dashboard',

    'login/?'          => 'frontend/login',
    'forgotpassword/?' => 'frontend/forgotpassword',

    'dashboard/?' => 'frontend/dashboard',
    'catalog/?'   => 'frontend/catalog',
    'cart/?'      => 'frontend/cart',

    'orders/?'          => 'frontend/orders',
    'orders/<id:\d+>/?' => 'frontend/order',

    'profile/?' => 'frontend/profile',
];
```

Важно: `/? => frontend/dashboard` оставить как рабочее правило текущего Webasyst routing-контекста.

---

## 12. Frontend runtime и shell

### 12.1. Runtime

Runtime собирает контекст текущего route.

```php
[
    'storefront'    => [],
    'settings'      => [],
    'plugin'        => [],
    'plugin_id'     => '',
    'plugin_status' => 'empty|missing|ready',
    'screens'       => [],
    'access_result' => [],
    'auth'          => [],
]
```

- Runtime не рендерит бизнес-шаблон.
- Runtime не матчить всю внутреннюю routing-логику plugin.
- Runtime не выбирает Webasyst theme.
- Runtime не выполняет бизнес-запросы plugin.
- Runtime собирает контекст, применяет defaults/policy и готовит данные для plugin action.

### 12.2. Frontend shell

Apanel предоставляет общий frontend shell: core CSS, assets выбранного plugin, общий layout, plugin content, в будущем - меню screens, auth block, breadcrumbs и flash/messages.

- Plugin может использовать shell Apanel.
- Plugin может использовать свои templates внутри shell.
- Plugin может реализовать собственный layout внутри content.
- Позже может быть добавлен режим полного override.

---

## 13. Backend UI-kit и зоны

Apanel должен оставаться образцом удобной backend-админки Webasyst.

- backend layout;
- зоны aside/header/main/footer;
- reusable actions;
- таблицы;
- деревья;
- toolbar;
- dropdown;
- search;
- modal infrastructure;
- htmx-интеграция;
- сервисы сохранения;
- сервисы нормализации;
- единый стиль форм.

---

## 14. Settings fields contract

Базовый режим настроек plugin - декларативный.

```php
'settings' => [
    'defaults' => [
        'price_type_id' => '',
        'show_stocks'   => 1,
    ],

    'fields' => [
        'price_type_id' => [
            'id'           => 'price_type_id',
            'title'        => 'Тип цен',
            'description'  => 'Используется для расчета цен в B2B.',
            'control_type' => 'select',
            'value'        => '',
            'options'      => [
                'base' => 'Базовая цена',
                'b2b'  => 'B2B цена',
            ],
            'sort' => 10,
        ],
    ],
]
```

| `control_type` | Назначение |
|---|---|
| `input` | Однострочное текстовое поле. |
| `textarea` | Многострочное текстовое поле. |
| `checkbox` | Булево значение 0/1. |
| `select` | Выбор из options. |
| `hidden` | Скрытое поле. |
| `html` | Информационный HTML-блок без сохранения значения. |

---

## 15. Access/Auth policy

### 15.1. Access policy

```php
'access' => [
    'default_mode'  => 'authorized',
    'allowed_modes' => ['authorized', 'groups', 'contacts', 'closed'],
]
```

| Режим | Назначение |
|---|---|
| `public` | Открытая витрина. |
| `authorized` | Только авторизованные пользователи. |
| `groups` | Только выбранные группы пользователей. |
| `contacts` | Только выбранные пользователи. |
| `closed` | Полностью закрыта. |

### 15.2. Auth policy

```php
'auth' => [
    'required'             => 1,
    'enabled'              => 1,
    'registration_allowed' => 0,
    'registration_enabled' => 0,
    'login_by'             => 'email',
    'allowed_login_by'     => ['email', 'phone'],
]
```

- `required` - авторизация обязательна и не может быть выключена в UI.
- `enabled` - дефолтное состояние авторизации.
- `registration_allowed` - разрешает или запрещает самостоятельную регистрацию.
- `registration_enabled` - настройка конкретного storefront.
- `login_by` - выбранный/дефолтный способ входа.
- `allowed_login_by` - список допустимых способов входа.

---

## 16. События Apanel

| Event | Назначение |
|---|---|
| `storefront_plugins` | Регистрация plugin-продуктов. |
| `storefront_screens` | Добавление/изменение screens. |
| `storefront_data_sources` | Регистрация источников данных. |
| `backend_ui` | Расширение backend UI. |
| `frontend_shell` | Расширение frontend shell. |
| `storefront_runtime` | Расширение runtime-контекста. |
| `storefront_before_save` | Валидация перед сохранением. |
| `storefront_after_save` | Действия после сохранения. |
| `screen_before_render` | Подготовка screen. |
| `screen_after_render` | Постобработка screen. |

---

## 17. Варианты реализации plugin

| Уровень | Описание | Когда использовать |
|---|---|---|
| 1. Декларативный plugin | Отдает screens, fields, templates и assets. Apanel делает почти все остальное. | Простые кабинеты и экраны. |
| 2. Action-based plugin | Отдает routing и собственные actions. Apanel дает runtime, shell и services. | B2B, заказы, каталог, личный кабинет. |
| 3. Full custom plugin | Использует Apanel как route selector, settings storage, hooks и SDK. | Сложные продукты со своей UI-логикой. |

---

## 18. Backend как SDK для разработчиков

### 18.1. Backend layout

- единый каркас;
- zones;
- navbar;
- sidebar;
- toolbar;
- modals;
- table/tree.

### 18.2. UI components

- table;
- tree;
- dropdown;
- search;
- form fields;
- modal;
- toolbar;
- status labels;
- badges;
- cards.

### 18.3. Settings services

- read settings;
- save settings;
- save group;
- normalize group;
- mass save;
- apply plugin policy;
- resolve storefront;
- resolve selected plugin.

### 18.4. Runtime services

- current storefront;
- selected plugin;
- screens;
- access result;
- auth map;
- full URL;
- route metadata.

---

## 19. Безопасность

Apanel core обязан:

- валидировать `storefront_key`;
- валидировать `group`;
- проверять права администратора в backend save controllers;
- проверять CSRF в POST-формах;
- нормализовать plugin settings;
- не подключать assets вне plugin-директории;
- не позволять template path с `..`;
- не доверять screen IDs из POST;
- не позволять plugin сохранять произвольные core-настройки без контракта.

---

## 20. Публикационные ограничения

### 20.1. На стадии активной разработки

- `db.php` не обязателен;
- структура БД может быть создана вручную;
- `db.php` генерируется позже консольной командой перед публикацией;
- временные параметры themes/pages допустимы, если нужны только для отображения роутов в «Сайте»;
- временные debug-вставки допустимы локально, но должны быть удалены перед стабилизацией runtime.

### 20.2. Перед публикацией

- сгенерировать `db.php`;
- проверить install/update;
- проверить `.htaccess`;
- проверить vendor;
- проверить version;
- удалить debug;
- проверить UTF-8;
- проверить отсутствие XSS/SQL-инъекций;
- проверить frontend/backend на чистой установке.

---

## 21. Отличия от редакции v3.1

21. Убрана трактовка `db.php` как обязательного файла на текущей стадии разработки.
22. Закреплено `/? => frontend/dashboard` как рабочее root-правило plugin routing.
23. Временные themes/pages не считаются частью продуктовой архитектуры.
24. `storefronts.{key}` описан как логическая модель, а физическое хранение допускает `scope` / `scope_id` / `name`.
25. Нормативным routing-файлом plugin признан `lib/config/routing.php`.
26. Разведены `login_by` и `allowed_login_by`.
27. Apanel описан как SDK/платформа для разработчиков, а не только storefront platform.
28. Plugin может использовать Apanel shell или свое решение.
29. Описаны уровни plugin-интеграции: declarative, action-based, full custom.
30. Зафиксировано, что core не знает business-flow plugin.

---

## 22. Ближайшие реальные правки по коду

Без учета `db.php`, `/?`, временных themes/pages.

| Приоритет | Правка | Аргументация |
|---|---|---|
| P0 | Удалить `dd(waRequest::param())` из `apanelFrontendAction`. | Debug dump блокирует нормальный frontend runtime. |
| P0 | Согласовать `apanelFrontendAction` с plugin-dispatch моделью. | Core fallback не должен ждать screen, если screen выбирается plugin action. |
| P0 | В B2B declaration добавить `auth.required`, `registration_allowed`, `allowed_login_by`. | B2B должен явно заявлять обязательную авторизацию и policy. |
| P0 | В `saveScreens()` сохранять только реальные screen IDs выбранного plugin. | Защита от мусорных и чужих IDs в настройках. |
| P0 | Добавить нормализацию assets paths в registry. | Защита от выхода за пределы plugin-директории. |
| P1 | Вынести определение current storefront в один сервисный метод. | Исключить расхождение `storefront_key` / `full_url` в разных местах. |
| P1 | Описать базовый action для plugin frontend screens. | Ускорить разработку plugin-продуктов и унифицировать runtime flow. |
| P1 | Добавить shell navigation по enabled screens. | Сделать Apanel shell полезным по умолчанию. |
| P1 | Добавить hooks `storefront_runtime`, `screen_before_render`, `screen_after_render`. | Расширяемость без правки core. |
| P1 | Подготовить developer guide для plugin-продукта. | Чтобы сторонние разработчики работали по одному контракту. |

---

## 23. Короткая формула проекта

Apanel - это Webasyst-приложение-оркестратор, которое получает route от приложения «Сайт», превращает его в управляемый storefront/panel instance, позволяет назначить plugin-продукт, предоставляет настройки, UI, shell, hooks, runtime и сервисы, а конкретный plugin реализует бизнес-функциональность по документированному контракту.
