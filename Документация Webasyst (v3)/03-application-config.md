# 03. Конфигурация приложения в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить `wa-apps/{app_id}/lib/config/app.php` как декларацию возможностей приложения, а не как произвольный набор метаданных.

---

## 1. Назначение механизма

`lib/config/app.php` — главный конфигурационный файл приложения Webasyst.

Он отвечает не за runtime-настройки пользователя, а за описание того, **каким приложением является app** и какие системные механизмы Webasyst должны быть для него включены.

Через `app.php` приложение сообщает ядру:

- как оно называется;
- какая у него версия и vendor;
- есть ли frontend;
- поддерживает ли оно темы;
- поддерживает ли оно страницы;
- есть ли плагины;
- нужны ли права доступа;
- нужна ли CSRF-защита;
- поддерживает ли приложение авторизацию на витрине;
- поддерживает ли личный кабинет;
- какие route params должны добавляться к поселениям;
- какую версию Webasyst UI поддерживает backend;
- поддерживает ли приложение payment/shipping/sms plugins.

Важно: `app.php` не должен превращаться в склад пользовательских настроек, route-specific данных, runtime-флагов и бизнес-конфигурации. Для этого есть другие уровни:

```text
wa-apps/{app_id}/lib/config/config.php          // дефолтные технические options приложения
wa-config/apps/{app_id}/config.php              // пользовательский override config.php
wa_app_settings / waAppSettingsModel            // persisted settings
wa-config/routing.php                           // поселения frontend
wa-config/apps/{app_id}/plugins.php             // включённые plugins приложения
```

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/config/waAppConfig.class.php` | Загружает `app.php`, `config.php`, classes, factories, routing. |
| `wa-system/config/waSystemConfig.class.php` | Создаёт app config через `SystemConfig::getAppConfig()`. |
| `wa-system/waSystem.class.php` | Инициализирует app system и возвращает app info через `getAppInfo()`. |
| `wa-system/controller/waDispatch.class.php` | Использует `csrf`, `auth`, `secure`, `frontend`, route params во время dispatch. |
| `wa-system/controller/waFrontController.class.php` | Проверяет app rights и plugin config при запуске controller/action. |
| `wa-system/routing/waRouting.class.php` | Использует app routing и route params, связанные с `app.php`. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | Главная декларация возможностей приложения. |
| `wa-apps/{app_id}/lib/config/{app_id}Config.class.php` | Опциональный кастомный config class приложения. |
| `wa-apps/{app_id}/lib/config/config.php` | Дефолтные technical options приложения. |
| `wa-config/apps/{app_id}/config.php` | Пользовательские overrides для `config.php`. |
| `wa-apps/{app_id}/lib/config/routing.php` | Frontend app-level routes. |
| `wa-apps/{app_id}/lib/config/routing.backend.php` | Backend app-level routes. |
| `wa-apps/{app_id}/lib/config/factories.php` | Переопределение factories: front controller, default controller, view и др. |
| `wa-apps/{app_id}/lib/config/db.php` | Схема БД для установки приложения. |
| `wa-apps/{app_id}/lib/config/install.php` | Install script. |
| `wa-apps/{app_id}/lib/config/uninstall.php` | Uninstall script. |
| `wa-apps/{app_id}/lib/config/rights.php` | Конфигурация прав, если `rights => true`. |
| `wa-apps/{app_id}/lib/config/cron.php` | Cron jobs приложения. |
| `wa-apps/{app_id}/lib/config/logs.php` | Описание log actions. |

### 2.3. Файлы plugin-экосистемы

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Конфиг конкретного plugin-а. |
| `wa-config/apps/{app_id}/plugins.php` | Список включённых plugins приложения. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php` | Настройки plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php` | Frontend routing plugin-а, если plugin добавляет routes через событие `routing`. |

---

## 3. Системная цепочка выполнения

### 3.1. Когда загружается `app.php`

При инициализации приложения Webasyst создаёт app-specific config:

```text
wa($app_id)
→ waSystem::getInstance($app_id, ...)
→ SystemConfig::getAppConfig($app_id, $environment, $root_path, $locale)
→ {app_id}Config или waAppConfig
→ waAppConfig::init()
→ include wa-apps/{app_id}/lib/config/app.php
```

Внутри `waAppConfig::init()` происходит несколько важных действий:

1. Загружаются default options из:

```text
wa-apps/{app_id}/lib/config/config.php
```

2. Загружаются user overrides из:

```text
wa-config/apps/{app_id}/config.php
```

3. Загружается `app.php`:

```php
$this->info = include($this->getAppPath().'/lib/config/app.php');
```

4. Регистрируются классы приложения:

```php
waAutoload::getInstance()->add($this->getClasses());
```

5. При наличии `payment_plugins` и `shipping_plugins` добавляются классы соответствующих plugin-типов.

6. При наличии `factories.php` загружаются factories приложения.

### 3.2. Как `app.php` используется во время dispatch

#### Backend

```text
/{backend_url}/{app_id}/...
→ waDispatch::dispatchBackend()
→ проверка существования app
→ проверка backend rights
→ wa($app_id, 1)
→ app config читает app.php
→ проверка csrf, если app.php содержит csrf => true
→ $wa_app->getFrontController()->dispatch()
```

В backend `csrf => true` включает системную проверку POST-запросов на уровне `waDispatch::dispatchBackend()`.

#### Frontend

```text
HTTP request
→ waDispatch::dispatchFrontend()
→ wa()->getRouting()->dispatch()
→ route определяет app
→ wa($app_id, 1)
→ app config читает app.php
→ secure/auth проверяются по route params и app config
→ app front controller запускает action/controller
```

В frontend `app.php` сам по себе не делает route публичным. Frontend должен быть включён в app config, но конкретная доступность URL определяется:

```text
wa-config/routing.php
+ wa-apps/{app_id}/lib/config/routing.php
+ route params
```

### 3.3. Как `app.php` связан с routes

`app.php` может объявить дефолтные параметры поселения:

```php
'routing_params' => array(
    'blog_url_type' => 1,
),
```

или:

```php
'routing_params' => array(
    'priority_settlement' => true,
),
```

или:

```php
'routing_params' => array(
    'private' => true,
),
```

Эти параметры используются при создании/управлении поселениями приложения и затем попадают в route data. Во время dispatch они становятся частью `waRequest::param()`.

---

## 4. Ключевые классы и методы

### 4.1. `waAppConfig`

Главный системный класс для app config.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `__construct($environment, $root_path, $application, $locale)` | Создаёт config приложения. |
| `init()` | Загружает `config.php`, `wa-config/apps/{app}/config.php`, `app.php`, autoload, factories. |
| `getInfo($name = null)` | Возвращает данные из `app.php`. |
| `getOption($name = null)` | Возвращает данные из `config.php`, не из `app.php`. |
| `getApplication()` | Возвращает app id. |
| `getPrefix()` | Возвращает class prefix: `prefix` из `app.php` или app id. |
| `getRouting()` | Возвращает routing rules приложения. |
| `getRoutingRules()` | Загружает `routing.php` / `routing.backend.php`. |
| `getClasses()` | Сканирует классы приложения/plugins/widgets/api и регистрирует autoload map. |
| `install()` | Выполняет DB schema и install script. |
| `checkUpdates()` | Проверяет install/update state приложения. |

### 4.2. `waSystem`

Использует app config для инициализации приложения.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `waSystem::getInstance($app_id, null, true)` | Создаёт/активирует instance приложения. |
| `getAppInfo($app_id = null)` | Возвращает данные `app.php`. |
| `getConfig()` | Возвращает `waAppConfig` текущего приложения. |
| `getFrontController()` | Возвращает front controller приложения. |
| `whichUI($app_id = null)` | Определяет текущую UI-версию с учётом `ui` в `app.php`. |
| `appExists($app_id)` | Проверяет наличие приложения. |

### 4.3. `waDispatch`

Использует flags из `app.php` при dispatch.

Backend:

- проверяет права на app backend;
- инициализирует app;
- проверяет CSRF, если `csrf => true`;
- передаёт управление app front controller.

Frontend:

- определяет app через routing;
- инициализирует app;
- если route содержит `secure` или `auth`, проверяет авторизацию;
- проверяет CSRF для secure POST, если `csrf => true`;
- передаёт управление app front controller.

### 4.4. `waFrontController`

Использует app config для:

- получения class prefix через `getPrefix()`;
- проверки app rights через `checkRights($module, $action)`;
- проверки наличия plugin support через `getInfo('plugins')`;
- проверки включённости plugin-а через `wa-config/apps/{app_id}/plugins.php`.

### 4.5. App-specific config class

Приложение может иметь собственный config class:

```text
wa-apps/{app_id}/lib/config/{app_id}Config.class.php
```

Например:

```text
wa-apps/shop/lib/config/shopConfig.class.php
```

Если такой файл существует, `SystemConfig::getAppConfig()` создаёт `{app_id}Config`, иначе — базовый `waAppConfig`.

App-specific config class используется, когда приложение должно переопределить системные методы:

- `getRouting()`;
- `getRoutingRules()`;
- `checkRights()`;
- app-specific helpers/options;
- storefront-specific behavior.

Пример: `shopConfig::getRouting()` выбирает routing set по `url_type`, добавляет category routes и plugin routes.

---

## 5. Параметры `app.php`

### 5.1. Идентификационные параметры

```php
return array(
    'name'        => 'My App',
    'description' => '...',
    'icon'        => 'img/myapp.svg',
    'sash_color'  => '#49a2e0',
    'version'     => '1.0.0',
    'critical'    => '1.0.0',
    'vendor'      => 'myvendor',
);
```

| Параметр | Назначение |
|---|---|
| `name` | Название приложения в backend/UI. |
| `description` | Описание приложения. |
| `icon` | Иконка приложения. |
| `sash_color` | Цветовая метка приложения. |
| `version` | Текущая версия приложения. |
| `critical` | Минимально критичная версия для update logic. |
| `vendor` | Vendor приложения. |
| `license` | Тип лицензии, например `commercial`. |

### 5.2. `frontend`

```php
'frontend' => true,
```

Означает: приложение может иметь frontend-поселения.

Но этого недостаточно, чтобы URL начал работать. Для frontend нужны:

```text
1. frontend => true в app.php
2. settlement в wa-config/routing.php
3. app-level routes в lib/config/routing.php
4. frontend action/controller/template
```

Если `frontend` не включён, приложение не должно проектироваться как публичный storefront app.

### 5.3. `auth`

```php
'auth' => true,
```

Означает: приложение может участвовать в frontend auth-flow.

Типичные routes:

```php
'login/'          => 'login/',
'forgotpassword/' => 'forgotpassword/',
'signup/'         => 'signup/',
```

`auth` не заменяет route `secure => true`. Это capability приложения, а не защита конкретного URL.

### 5.4. `my_account`

```php
'my_account' => true,
```

Означает: приложение участвует в личном кабинете пользователя.

Примеры route-правил:

```php
'my/' => array(
    'module' => 'frontend',
    'action' => 'my',
    'secure' => true,
),
```

```php
'my/orders/?' => array(
    'module' => 'frontend',
    'action' => 'myOrders',
    'secure' => true,
),
```

`my_account => true` не делает все `my/*` routes автоматически. Routes должны быть описаны в `routing.php`.

### 5.5. `themes`

```php
'themes' => true,
```

Означает: приложение поддерживает design themes.

Следствия:

- frontend action может использовать `setThemeTemplate()`;
- темы приложения лежат в `wa-apps/{app_id}/themes/{theme_id}/`;
- у приложения могут быть theme templates;
- `waAppConfig` добавляет system log actions для theme operations.

Типичные theme templates:

```text
wa-apps/{app_id}/themes/{theme_id}/index.html
wa-apps/{app_id}/themes/{theme_id}/home.html
wa-apps/{app_id}/themes/{theme_id}/page.html
wa-apps/{app_id}/themes/{theme_id}/error.html
```

### 5.6. `pages`

```php
'pages' => true,
```

Означает: приложение поддерживает страницы.

Следствия:

- возможно использование page model `{app_id}PageModel`;
- `waRouting::getPageRoutes()` может добавлять page routes для app, если app не `site`;
- `waAppConfig` добавляет system log actions для page operations;
- frontend action может использовать `waPageAction` или собственный page flow.

Важно: `pages => true` требует аккуратной работы с `page_id`, `full_url`, theme templates и escaping page content.

### 5.7. `plugins`

```php
'plugins' => true,
```

Означает: приложение поддерживает plugins.

Следствия:

- plugin config ищется в `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php`;
- enabled plugins хранятся в `wa-config/apps/{app_id}/plugins.php`;
- `waAppConfig::getClasses()` сканирует plugin `lib/` directories;
- `waFrontController` может dispatch-ить plugin backend actions;
- события приложения могут обрабатываться plugins.

Если `plugins` не включён, backend plugin dispatch должен считаться невозможным.

### 5.8. `rights`

```php
'rights' => true,
```

Означает: приложение имеет систему прав.

Следствия:

- backend проверяет право пользователя на приложение;
- приложение может иметь `lib/config/rights.php`;
- app config может переопределить `checkRights($module, $action)`;
- plugin rights могут проверяться через `plugin.{plugin_id}`.

Важно: `rights => true` не освобождает action/controller от проверки прав на изменяющие операции. UI-права и server-side rights должны быть согласованы.

### 5.9. `csrf`

```php
'csrf' => true,
```

Означает: Webasyst включает системную CSRF-защиту.

Backend:

```text
если csrf => true и request method POST
→ waDispatch::dispatchBackend()
→ сравнение waRequest::post('_csrf') и waRequest::cookie('_csrf')
```

Frontend:

```text
если route secure и request method POST и csrf => true
→ waDispatch::dispatchFrontend()
→ сравнение _csrf post/cookie
```

Практический вывод:

- в backend POST-формах нужен `{$wa->csrf()}`;
- в secure frontend POST-формах нужен `{$wa->csrf()}`;
- не нужно изобретать собственный CSRF-механизм;
- JSON/AJAX/htmx POST тоже должен передавать `_csrf`, если работает внутри CSRF-protected flow.

### 5.10. `routing_params`

```php
'routing_params' => array(
    'blog_url_type' => 1,
),
```

`routing_params` задаёт параметры поселения по умолчанию.

Примеры из официальных приложений:

```php
// Blog
'routing_params' => array(
    'blog_url_type' => 1,
),
```

```php
// Site
'routing_params' => array(
    'priority_settlement' => true,
),
```

```php
// CRM
'routing_params' => array(
    'private' => true,
),
```

```php
// Shop
'routing_params' => array(
    'checkout_version'       => 2,
    'checkout_storefront_id' => class_exists('shopCheckoutConfig') ? ['shopCheckoutConfig', 'generateStorefrontId'] : md5(uniqid()),
),
```

`routing_params` — это не request defaults для action. Это параметры settlement/route, которые участвуют в routing и попадают в `waRequest::param()` после route match.

### 5.11. `ui`

```php
'ui' => '2.0',
```

или:

```php
'ui' => '1.3,2.0',
```

или:

```php
'ui' => '2.0,1.3',
```

`ui` описывает поддерживаемую backend UI-версию приложения.

`waSystem::whichUI()` учитывает:

- глобальную system UI setting;
- cookie force UI;
- `ui` приложения;
- `forcible_ui`, если приложение его использует;
- single app mode.

Практические правила:

| Значение | Смысл |
|---|---|
| `2.0` | Только Webasyst UI 2.0. |
| `1.3,2.0` | Поддержаны обе версии, предпочтение определяется системной настройкой/cookie. |
| `2.0,1.3` | Поддержаны обе версии, но приложение явно декларирует 2.0 первым. |

Важно: если app заявляет `ui => '2.0'`, backend templates/actions не должны проектироваться как UI 1.3-only.

### 5.12. `payment_plugins`, `shipping_plugins`, `sms_plugins`

Пример из Shop-Script:

```php
'payment_plugins' => array(
    'taxes'  => true,
    'rights' => 'settings',
),

'shipping_plugins' => array(
    'desired_date'  => true,
    'draft'         => true,
    'ready'         => true,
    'cancel'        => true,
    'taxes'         => true,
    'custom_fields' => true,
    'dimensions'    => false,
    'sync'          => true,
    'callback'      => array(),
    'rights'        => 'settings',
),

'sms_plugins' => true,
```

Эти flags нужны не каждому приложению. Их добавляют только если app реально является host-приложением для соответствующего типа plugins.

`waAppConfig::init()` отдельно реагирует на `payment_plugins` и `shipping_plugins`, добавляя соответствующие plugin classes в autoload.

---

## 6. Паттерны официального Webasyst-кода

### 6.1. `site` — system app для поселений, страниц и тем

`site/lib/config/app.php` включает:

```php
'frontend'   => true,
'system'     => true,
'rights'     => true,
'plugins'    => true,
'themes'     => true,
'pages'      => true,
'auth'       => true,
'csrf'       => true,
'my_account' => true,
'routing_params' => array(
    'priority_settlement' => true,
),
'ui' => '2.0,1.3',
```

Паттерн:

- приложение является системным центром site/routing/theme/page management;
- поддерживает frontend;
- поддерживает темы и страницы;
- участвует в auth и my account;
- использует `priority_settlement`.

### 6.2. `blog` — компактное frontend app с темами, pages и routing mode

`blog/lib/config/app.php` включает:

```php
'frontend' => true,
'auth' => true,
'themes' => true,
'plugins' => true,
'pages' => true,
'mobile' => true,
'csrf' => true,
'my_account' => true,
'routing_params' => array(
    'blog_url_type' => 1,
),
'ui' => '1.3,2.0',
```

Паттерн:

- публичный frontend;
- несколько URL-моделей через `blog_url_type`;
- themes/pages/plugins включены;
- backend поддерживает обе UI-версии.

### 6.3. `shop` — большое commerce app и host для plugin-типов

`shop/lib/config/app.php` включает:

```php
'frontend'         => true,
'auth'             => true,
'themes'           => true,
'plugins'          => true,
'pages'            => true,
'mobile'           => true,
'my_account'       => true,
'csrf'             => true,
'payment_plugins'  => array(...),
'shipping_plugins' => array(...),
'sms_plugins'      => true,
'routing_params'   => array(
    'checkout_version'       => 2,
    'checkout_storefront_id' => ...,
),
'ui'               => '1.3,2.0'
```

Паттерн:

- полноценный frontend storefront;
- checkout и личный кабинет;
- темы, страницы, plugins;
- payment/shipping/sms plugin ecosystem;
- app-specific `shopConfig` переопределяет routing и rights.

### 6.4. `crm` — backend-first приложение с private frontend endpoints

`crm/lib/config/app.php` включает:

```php
'plugins'  => true,
'rights'   => true,
'csrf'     => true,
'routing_params' => array(
    'private' => true,
),
'payment_plugins' => array(
    'taxes' => true,
),
'sms_plugins' => true,
'frontend' => true,
'ui' => '2.0',
```

Паттерн:

- backend-first приложение;
- frontend есть, но не как обычная публичная витрина;
- route params делают settlement private;
- UI только 2.0;
- есть интеграция с payment/sms plugins.

---

## 7. Минимальная реализация

### 7.1. Минимальное backend-only приложение

```php
<?php

return array(
    'name'       => 'My App',
    'icon'       => 'img/myapp.svg',
    'sash_color' => '#49a2e0',
    'version'    => '1.0.0',
    'critical'   => '1.0.0',
    'vendor'     => 'myvendor',
    'rights'     => true,
    'csrf'       => true,
    'ui'         => '2.0',
);
```

Файл:

```text
wa-apps/myapp/lib/config/app.php
```

Такое приложение:

- доступно в backend;
- использует права;
- защищает POST через CSRF;
- рассчитано на Webasyst UI 2.0;
- не имеет frontend, themes, pages и plugins.

### 7.2. Минимальное frontend-приложение

```php
<?php

return array(
    'name'       => 'My App',
    'icon'       => 'img/myapp.svg',
    'sash_color' => '#49a2e0',
    'version'    => '1.0.0',
    'critical'   => '1.0.0',
    'vendor'     => 'myvendor',
    'frontend'   => true,
    'themes'     => true,
    'csrf'       => true,
    'ui'         => '2.0',
);
```

Дополнительно нужны:

```text
wa-apps/myapp/lib/config/routing.php
wa-apps/myapp/lib/actions/frontend/myappFrontend.action.php
wa-apps/myapp/themes/default/index.html
wa-apps/myapp/themes/default/home.html
```

Route skeleton:

```php
<?php

return array(
    '' => 'frontend/',
);
```

Action skeleton:

```php
<?php

class myappFrontendAction extends waViewAction
{
    public function execute()
    {
        $this->setThemeTemplate('home.html');
    }
}
```

### 7.3. Приложение с plugins

```php
<?php

return array(
    'name'       => 'My App',
    'icon'       => 'img/myapp.svg',
    'sash_color' => '#49a2e0',
    'version'    => '1.0.0',
    'critical'   => '1.0.0',
    'vendor'     => 'myvendor',
    'rights'     => true,
    'plugins'    => true,
    'csrf'       => true,
    'ui'         => '2.0',
);
```

Если `plugins => true`, нужно учитывать:

```text
wa-apps/myapp/plugins/{plugin_id}/lib/config/plugin.php
wa-config/apps/myapp/plugins.php
```

Plugin backend URL обычно будет старого вида:

```text
/{backend_url}/myapp/?plugin={plugin_id}&module=settings
```

---

## 8. Расширенная реализация

### 8.1. Когда нужен `{app_id}Config.class.php`

Создавать app-specific config class стоит, если нужно переопределить системное поведение приложения.

Пример path:

```text
wa-apps/myapp/lib/config/myappConfig.class.php
```

Skeleton:

```php
<?php

class myappConfig extends waAppConfig
{
    public function checkRights($module, $action)
    {
        if ($module == 'settings') {
            return wa()->getUser()->getRights($this->getApplication(), 'settings');
        }

        return true;
    }
}
```

Использовать для:

- тонкой проверки rights по module/action;
- app-specific routing selection;
- добавления динамических routes;
- app-specific storefront behavior;
- специальных install/update hooks.

Не использовать для:

- хранения бизнес-логики action;
- работы с конкретными request POST payload;
- вывода HTML;
- пользовательских настроек, которые должны быть в settings/model.

### 8.2. Расширенный `routing_params`

Если app поддерживает несколько режимов поселения, `routing_params` можно использовать как default route params.

Пример:

```php
'routing_params' => array(
    'catalog_mode' => 'flat',
    'private'      => false,
),
```

Но нужно помнить:

- route params становятся частью routing state;
- они влияют на URL generation и dispatch;
- они не должны использоваться как замена app settings;
- изменяемые пользователем runtime-настройки лучше хранить в settings/model.

### 8.3. App с темами и страницами

Если приложение заявляет:

```php
'themes' => true,
'pages'  => true,
```

то нужно обеспечить:

```text
wa-apps/myapp/themes/default/theme.xml
wa-apps/myapp/themes/default/index.html
wa-apps/myapp/themes/default/page.html
wa-apps/myapp/lib/models/myappPage.model.php
```

и корректный frontend action/page flow.

Если `pages => true` добавлен без page model/page UI, приложение будет выглядеть как поддерживающее механизм, которого фактически нет.

---

## 9. Типовые ошибки

### Ошибка 1. Хранить пользовательские настройки в `app.php`

Неправильно:

```php
return array(
    'items_per_page' => 50,
    'show_sidebar' => true,
);
```

Если это runtime/user setting, использовать:

```text
waAppSettingsModel
wa-config/apps/{app_id}/config.php
model/table
settings UI
```

`app.php` — декларация возможностей приложения, не пользовательская конфигурация.

### Ошибка 2. Включить `frontend => true`, но не создать frontend routing

Неполная реализация:

```php
'frontend' => true,
```

но нет:

```text
lib/config/routing.php
lib/actions/frontend/{app}Frontend.action.php
```

Результат: приложение заявляет capability, но frontend request не имеет нормального app-level dispatch.

### Ошибка 3. Включить `themes => true`, но не поддержать theme templates

Если приложение использует `setThemeTemplate('home.html')`, тема должна иметь нужный файл.

Нужно проверить:

```text
themes/default/index.html
themes/default/home.html
themes/default/page.html
themes/default/error.html
```

### Ошибка 4. Добавить `pages => true` без page pattern

`pages => true` должен быть связан с:

- page model;
- page UI;
- route/page action;
- templates;
- корректной обработкой `page_id`.

Иначе Webasyst будет считать, что приложение поддерживает pages, хотя фактической реализации нет.

### Ошибка 5. Отключить `csrf` для удобства

Неправильно:

```php
'csrf' => false,
```

если приложение имеет backend POST или secure frontend POST.

Правильно:

```php
'csrf' => true,
```

и в формах:

```smarty
{$wa->csrf()}
```

### Ошибка 6. Указать `ui => '2.0'`, но писать backend как UI 1.3

Если app config заявляет:

```php
'ui' => '2.0',
```

то backend templates должны использовать Webasyst UI 2.0 patterns. Не нужно строить интерфейс как legacy UI 1.3-only.

### Ошибка 7. Включить `plugins => true` без понимания plugin lifecycle

`plugins => true` означает:

- plugin configs;
- plugin updates;
- plugin rights;
- plugin events;
- plugin backend actions;
- plugin autoload.

Если приложение не рассчитано на расширение plugins, этот флаг лучше не включать.

### Ошибка 8. Использовать `routing_params` как settings

Неправильно:

```php
'routing_params' => array(
    'items_per_page' => 50,
),
```

если это пользовательская настройка.

Правильно: route params — только для routing/settlement behavior.

### Ошибка 9. Придумать нестандартный class prefix без необходимости

`waAppConfig::getPrefix()` берёт `prefix` из `app.php`, если он задан, иначе использует app id.

Если задать:

```php
'prefix' => 'customPrefix',
```

то dispatch будет искать классы с этим prefix. Это может сломать ожидаемый naming.

Для обычных приложений лучше не задавать `prefix` и использовать app id как class prefix.

---

## 10. Чеклист разработчика

Перед commit нового или изменённого `app.php` проверить:

### Identity

- [ ] Есть `name`.
- [ ] Есть `icon`.
- [ ] Есть `version`.
- [ ] Есть `critical`, если приложение участвует в update lifecycle.
- [ ] Есть `vendor`.
- [ ] `sash_color` задан осознанно.

### Capabilities

- [ ] `frontend => true` указан только если есть frontend routes/actions/templates.
- [ ] `themes => true` указан только если приложение реально поддерживает themes.
- [ ] `pages => true` указан только если есть page pattern.
- [ ] `plugins => true` указан только если приложение реально поддерживает plugins.
- [ ] `rights => true` указан, если нужен backend access control.
- [ ] `csrf => true` включён для backend/secure POST.
- [ ] `auth => true` указан только если app участвует в frontend auth.
- [ ] `my_account => true` указан только если app имеет личный кабинет.

### Routing

- [ ] `routing_params` содержит только route/settlement параметры.
- [ ] Значения `routing_params` не дублируют settings.
- [ ] Есть соответствующий `routing.php`, если app frontend-enabled.
- [ ] Есть соответствующий `routing.backend.php`, если app использует красивые backend URL.

### UI

- [ ] `ui` соответствует реальным backend templates.
- [ ] Если `ui => '2.0'`, backend не зависит от legacy UI 1.3.
- [ ] Если `ui => '1.3,2.0'`, проверены оба режима.

### Plugins

- [ ] `payment_plugins` указан только для app-host payment plugins.
- [ ] `shipping_plugins` указан только для app-host shipping plugins.
- [ ] `sms_plugins` указан только если приложение действительно использует SMS plugins.
- [ ] Plugin rights и plugin settings согласованы.

### Security

- [ ] Не отключён CSRF без причины.
- [ ] Backend rights проверяются не только в UI.
- [ ] Secure frontend routes используют `secure => true`.
- [ ] Private frontend endpoints имеют route params вроде `private => true`, если это нужно.

---

## 11. Чеклист ИИ-агента

Перед ответом по `app.php` ИИ-агент обязан:

1. Открыть `wa-apps/{app_id}/lib/config/app.php`.
2. Проверить, есть ли кастомный `wa-apps/{app_id}/lib/config/{app_id}Config.class.php`.
3. Проверить `frontend`, `themes`, `pages`, `plugins`, `rights`, `csrf`, `auth`, `my_account`, `routing_params`, `ui`.
4. Если задача касается frontend — открыть `lib/config/routing.php`.
5. Если задача касается backend URL — открыть `lib/config/routing.backend.php`.
6. Если задача касается plugins — открыть `wa-config/apps/{app_id}/plugins.php` и plugin config.
7. Если задача касается UI — проверить `ui` и существующие templates.
8. Если задача касается прав — открыть `lib/config/rights.php` и app config `checkRights()`.
9. Если задача касается POST — проверить `csrf` и наличие `{$wa->csrf()}` в форме.
10. Если задача касается route params — проверить `routing_params` и фактические route params в settlement.
11. Только после этого предлагать изменение `app.php`.

ИИ-агенту запрещено:

- добавлять flags в `app.php` “на всякий случай”;
- использовать `app.php` как storage пользовательских настроек;
- включать `frontend` без routing/actions;
- включать `themes` без theme templates;
- включать `pages` без page pattern;
- отключать `csrf` ради упрощения;
- менять `ui`, не проверив backend templates;
- задавать `prefix`, если это не подтверждено текущим naming приложения;
- придумывать app config параметры без подтверждения в ядре или официальных приложениях.

---

## 12. Мини-сводка

`app.php` — это паспорт приложения Webasyst.

Правильная цепочка:

```text
wa($app_id)
→ SystemConfig::getAppConfig($app_id)
→ waAppConfig или {app_id}Config
→ waAppConfig::init()
→ include lib/config/config.php
→ include wa-config/apps/{app_id}/config.php
→ include lib/config/app.php
→ register autoload classes
→ load factories
→ dispatch использует app capabilities
```

`app.php` должен отвечать на вопрос:

```text
Какие системные возможности Webasyst включает это приложение?
```

А не на вопрос:

```text
Какие пользовательские настройки сейчас выбраны?
```

Для разработчика и ИИ-агента это означает: перед изменением `app.php` нужно проверить не только сам файл, но и связанные routing/actions/templates/plugins/rights/security механизмы.
