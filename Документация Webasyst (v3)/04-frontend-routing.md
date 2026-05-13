# 04. Frontend routing в Webasyst

**Статус:** опубликован v3
**Язык:** русский  
**Назначение:** объяснить frontend routing Webasyst как рабочий механизм: от публичного URL до конкретного controller/action-класса приложения.

---

## 1. Назначение механизма

Frontend routing отвечает за то, как публичный HTTP-запрос на сайте Webasyst превращается в:

1. выбранный домен/сайт;
2. выбранное settlement-правило из `wa-config/routing.php`;
3. выбранное приложение;
4. внутренний route приложения из `wa-apps/{app_id}/lib/config/routing.php`;
5. параметры `waRequest::param()`;
6. конкретный controller/action/actions-класс приложения или плагина.

Frontend routing работает на двух уровнях:

```text
HTTP URL
→ global routing: wa-config/routing.php
→ app settlement: app, url, theme, locale, route params
→ app routing: wa-apps/{app_id}/lib/config/routing.php
→ module/action/plugin/placeholders
→ waFrontController
→ Controller / Action / Actions
```

Главное отличие от backend routing:

- в backend системный URL уже содержит `/{backend_url}/{app_id}/`;
- во frontend сначала нужно определить, какому приложению принадлежит публичный URL;
- frontend routing зависит от домена, settlement, темы, locale, route params и app-level routes.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `index.php` | HTTP entrypoint. |
| `wa-system/waSystem.class.php` | Создаёт системный runtime и запускает dispatch. |
| `wa-system/controller/waDispatch.class.php` | Разделяет backend/frontend flow и запускает frontend routing. |
| `wa-system/routing/waRouting.class.php` | Матчит global routes и app routes, выставляет `waRequest::param()`, строит URL. |
| `wa-system/config/waSystemConfig.class.php` | Загружает системную конфигурацию, включая `wa-config/routing.php`. |
| `wa-system/config/waAppConfig.class.php` | Загружает app config и app routing. |
| `wa-system/controller/waFrontController.class.php` | После routing запускает нужный controller/action/actions-класс. |
| `wa-system/controller/waController.class.php` | Базовый command controller. |
| `wa-system/controller/waViewAction.class.php` | Action для HTML-страницы или HTML-фрагмента. |
| `wa-system/controller/waViewController.class.php` | Controller с layout/blocks. |
| `wa-system/controller/waViewActions.class.php` | Multi-action controller. |
| `wa-system/request/waRequest.class.php` | Доступ к GET/POST/server/routing params. |
| `wa-system/response/waResponse.class.php` | Redirect, headers, status, response lifecycle. |

### 2.2. Системная конфигурация сайта

| Файл | Роль |
|---|---|
| `wa-config/routing.php` | Главный frontend routing всех доменов и settlement-правил. Обычно редактируется приложением «Сайт». |
| `wa-config/apps/{app_id}/routing.php` | Пользовательское переопределение app routing, если файл существует. |

### 2.3. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | Объявляет `frontend`, `themes`, `pages`, `auth`, `csrf`, `routing_params` и другие возможности приложения. |
| `wa-apps/{app_id}/lib/config/routing.php` | Внутренние frontend routes приложения. |
| `wa-apps/{app_id}/lib/config/{app_id}Config.class.php` | App-specific config; может переопределять `getRouting()`. |
| `wa-apps/{app_id}/lib/actions/frontend/...` | Frontend controller/action/actions classes. |
| `wa-apps/{app_id}/templates/actions/frontend/...` | App templates для frontend actions, если используются не theme templates. |
| `wa-apps/{app_id}/themes/{theme_id}/...` | Theme templates: `index.html`, app templates, page templates. |
| `wa-apps/{app_id}/lib/classes/...` | Helpers/services/models, участвующие в frontend request. |

### 2.4. Файлы плагина

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Plugin config, handlers, frontend flags. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/frontend/...` | Frontend actions плагина, если app routing добавляет `plugin`. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/frontend/...` | Templates frontend actions плагина. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/{app}{Plugin}Plugin.class.php` | Основной класс plugin-а, может возвращать frontend routes через event, если приложение поддерживает такой механизм. |

---

## 3. Адресное пространство frontend

Frontend Webasyst работает не от `/{backend_url}/`, а от публичного адресного пространства сайта.

Примеры:

```text
https://example.com/
https://example.com/shop/
https://example.com/blog/post-url/
https://example.com/my/orders/
https://example.com/form/abc123/
```

На первом уровне Webasyst определяет домен:

```text
example.com
www.example.com
example.com/subdir
```

Затем в `wa-config/routing.php` для этого домена ищется первое подходящее settlement-правило.

Типовой global route:

```php
<?php

return array(
    'example.com' => array(
        array(
            'url'   => 'shop/*',
            'app'   => 'shop',
            'theme' => 'default',
            'locale'=> 'ru_RU',
        ),
        array(
            'url'   => 'blog/*',
            'app'   => 'blog',
            'theme' => 'default',
            'locale'=> 'ru_RU',
        ),
        array(
            'url'   => '*',
            'app'   => 'site',
            'theme' => 'default',
            'locale'=> 'ru_RU',
        ),
    ),
);
```

Здесь:

| URL | Settlement | App | Внутренний app URL |
|---|---|---|---|
| `/shop/cart/` | `shop/*` | `shop` | `cart/` |
| `/blog/post-url/` | `blog/*` | `blog` | `post-url/` |
| `/about/` | `*` | `site` | `about/` |

---

## 4. Системная цепочка выполнения

Frontend request проходит такую цепочку:

```text
index.php
→ waSystem::dispatch()
→ waDispatch::dispatch()
→ waDispatch::dispatchFrontend($request_url)
→ wa()->getRouting()->dispatch()
→ waRouting::dispatch()
→ waRouting::dispatchRoutes(global routes, request URL)
→ выбран settlement из wa-config/routing.php
→ waRequest::param('app') = {app_id}
→ waRequest::param('theme'), locale, route params
→ waRouting::getAppRoutes($app, $route, true)
→ waAppConfig::getRouting($route, true)
→ wa-apps/{app_id}/lib/config/routing.php
→ waRouting::dispatchRoutes(app routes, internal app URL)
→ waRequest::param('module'), action, placeholders
→ waDispatch::dispatchFrontend()
→ wa($app, 1)
→ $app_system->getFrontController()->dispatch()
→ waFrontController::getDispatchParams()
→ waFrontController::getController()
→ Controller / Action / Actions
```

Ключевой момент: `waRouting` не запускает controller напрямую. Он только определяет route и записывает параметры в `waRequest::param()`.

Controller запускает `waFrontController` уже после того, как `waDispatch::dispatchFrontend()` инициализировал выбранное приложение.

---

## 5. Global routing: `wa-config/routing.php`

`wa-config/routing.php` распределяет публичное адресное пространство между приложениями.

Минимальный пример:

```php
<?php

return array(
    'example.com' => array(
        array(
            'url' => 'blog/*',
            'app' => 'blog',
        ),
        array(
            'url' => '*',
            'app' => 'site',
        ),
    ),
);
```

Правила обрабатываются по порядку. Первое подходящее правило выбирает приложение.

### 5.1. Основные поля settlement

| Поле | Назначение |
|---|---|
| `url` | Маска публичного URL внутри домена. |
| `app` | Приложение, которому передаётся управление. |
| `theme` | Тема оформления для desktop. |
| `theme_mobile` | Тема оформления для mobile. |
| `locale` | Locale route. |
| `ssl` / `ssl_all` | SSL/HTTPS-поведение, если используется в route/domain config. |
| `_name` | Человеческое имя поселения, используется UI приложения «Сайт». |
| `private` | Служебный признак settlement. |
| `priority_settlement` | Приоритетное поселение, если приложение поддерживает такую логику. |

Settlement может содержать app-specific параметры. Например, Shop-Script хранит в route параметры витрины: `url_type`, `currency`, `checkout_version`, `payment_id`, `shipping_id`, `products_per_page` и другие.

Эти параметры становятся доступными через:

```php
$route = wa()->getRouting()->getRoute();
$url_type = waRequest::param('url_type');
```

---

## 6. App-level routing: `lib/config/routing.php`

После выбора settlement Webasyst берёт остаток URL внутри поселения и матчит его по app routes.

Файл:

```text
wa-apps/{app_id}/lib/config/routing.php
```

### 6.1. Строковый формат

```php
'cart/' => 'frontend/cart',
```

Означает:

```php
array(
    'url'    => 'cart/',
    'module' => 'frontend',
    'action' => 'cart',
)
```

Класс, который будет искаться:

```text
{app_id}FrontendCartController
{app_id}FrontendCartAction
{app_id}FrontendActions::cartAction()
```

Для Shop-Script:

```text
shopFrontendCartController
shopFrontendCartAction
shopFrontendActions::cartAction()
```

### 6.2. Строковый формат без action

```php
'' => 'frontend/',
```

Означает:

```php
array(
    'module' => 'frontend',
)
```

Action не задан. Дальше работает default-dispatch:

```text
{app_id}FrontendController
{app_id}FrontendAction
{app_id}FrontendActions::defaultAction()
```

### 6.3. Строковый формат с модулем без слеша

Shop-Script использует правила вида:

```php
'cart/<action:add|save|delete>/' => 'frontendCart',
```

Это не `module=frontend`, `action=cart`. Это:

```php
array(
    'module' => 'frontendCart',
)
```

А placeholder `<action:add|save|delete>` записывает routing param:

```php
waRequest::param('action') = 'add'; // или save/delete
```

Итоговый dispatch:

```text
module = frontendCart
action = add
```

Ожидаемые классы:

```text
shopFrontendCartAddController
shopFrontendCartAddAction
shopFrontendCartActions::addAction()
```

Если в route value нет action, но placeholder называется `action`, это допустимо: `waRouting::dispatchRoutes()` запишет placeholder в `waRequest::param('action')`.

### 6.4. Массивный формат

```php
'my/orders/?' => array(
    'module' => 'frontend',
    'action' => 'myOrders',
    'secure' => true,
),
```

Массивный формат нужен, когда route должен задать дополнительные параметры:

- `secure`;
- `auth`;
- `plugin`;
- `url`;
- app-specific flags;
- любые параметры, которые action потом читает через `waRequest::param()`.

### 6.5. Route key как alias при явном `url`

В массивном формате ключ может быть условным идентификатором, если внутри есть `url`:

```php
'verification' => array(
    'url' => 'verification/<verification_key>/<message_id>/<hash>/?',
    'module' => 'frontend',
    'action' => 'verification',
    'secure' => true,
),
```

В этом случае реальная маска URL берётся из поля `url`, а не из ключа `verification`.

---

## 7. Placeholder, wildcard и optional slash

### 7.1. Placeholder без regex

```php
'<post_url>/' => 'frontend/post',
```

Запрос:

```text
/blog/hello-world/
```

Даёт:

```php
waRequest::param('post_url') = 'hello-world';
```

Если regex не указан, placeholder матчится широко.

### 7.2. Placeholder с regex

```php
'author/<contact_id:(\d+)>/' => 'frontend/',
```

Даёт:

```php
waRequest::param('contact_id') = '123';
```

Читать нужно так:

```php
$contact_id = waRequest::param('contact_id', 0, waRequest::TYPE_INT);
```

### 7.3. Placeholder с набором значений

```php
'cart/<action:add|save|delete>/' => 'frontendCart',
```

Даёт:

```php
waRequest::param('action') = 'add';
```

Это часто используется для command-style endpoints.

### 7.4. Wildcard `*`

Global route:

```php
array(
    'url' => 'shop/*',
    'app' => 'shop',
)
```

Означает: всё, что начинается с `shop/`, отдаётся приложению `shop`.

App route:

```php
'api/v1/*' => 'frontend/apiErr404',
```

Означает: все нераспознанные `api/v1/...` запросы попадут в `frontend/apiErr404`.

Wildcard нужно ставить после более точных routes, иначе он перехватит специализированные URL.

### 7.5. Optional slash через `/?`

В официальных routes часто используется:

```php
'products/?' => 'prod/list',
'form/?'     => 'frontend/formSubmit',
```

Практический смысл: правило допускает URL с завершающим `/` и без него.

---

## 8. Как route params попадают в `waRequest::param()`

При совпадении route `waRouting::dispatchRoutes()` делает две операции:

1. записывает значения placeholder-параметров;
2. записывает все поля route, кроме `url`.

Пример:

```php
'product/<id:\d+>/' => array(
    'module' => 'frontend',
    'action' => 'product',
    'secure' => true,
),
```

Запрос:

```text
/product/123/
```

Итог:

```php
waRequest::param('id')     = '123';
waRequest::param('module') = 'frontend';
waRequest::param('action') = 'product';
waRequest::param('secure') = true;
```

Читать route placeholders через `waRequest::get()` нельзя:

```php
// Неправильно
$id = waRequest::get('id');

// Правильно
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

---

## 9. Что делает `waDispatch::dispatchFrontend()` после routing

После `wa()->getRouting()->dispatch()` frontend dispatcher:

1. Проверяет, найден ли route.
2. Читает выбранное приложение:

```php
$app = waRequest::param('app', null, 'string');
```

3. Инициализирует app runtime:

```php
$app_system = wa($app, 1);
```

4. Если route требует auth:

```php
waRequest::param('secure') || waRequest::param('auth')
```

и пользователь не авторизован — запускает login flow.

5. Если route secure, метод POST и app config содержит `csrf => true`, проверяет CSRF.
6. Передаёт управление front controller приложения:

```php
$app_system->getFrontController()->dispatch();
```

---

## 10. Как frontend route превращается в класс

В frontend `waFrontController::getDispatchParams()` сначала выставляет базовые значения:

```php
$module = 'frontend';
$action = null;
$plugin = null;
```

Затем routing params переопределяют их:

```php
$plugin = waRequest::param('plugin', $plugin, 'string');
$module = waRequest::param('module', $module, 'string');
$action = waRequest::param('action', $action, 'string');
```

Дальше порядок поиска классов такой же, как в backend:

1. `{app_id}{Module}{Action}Controller`
2. `{app_id}{Module}{Action}Action`
3. `{app_id}{Module}Actions::{action}Action()`
4. default fallback, если он разрешён
5. 404

### Пример 1. Site route

Route:

```php
'<url>' => 'frontend/'
```

Итог:

```text
module = frontend
action = null
url = about
```

Ожидаемые классы:

```text
siteFrontendController
siteFrontendAction
siteFrontendActions::defaultAction()
```

### Пример 2. Blog post

Route:

```php
'<blog_url>/<post_url>/' => 'frontend/post'
```

Запрос:

```text
/blog/news/hello-world/
```

Итог:

```text
module = frontend
action = post
blog_url = news
post_url = hello-world
```

Ожидаемые классы:

```text
blogFrontendPostController
blogFrontendPostAction
blogFrontendActions::postAction()
```

### Пример 3. Shop product

Route:

```php
'<product_url:[^/]+>/' => 'frontend/product'
```

Запрос:

```text
/iphone-15/
```

Итог:

```text
module = frontend
action = product
product_url = iphone-15
```

Ожидаемые классы:

```text
shopFrontendProductController
shopFrontendProductAction
shopFrontendActions::productAction()
```

### Пример 4. CRM private endpoint

Route:

```php
'verification' => array(
    'url' => 'verification/<verification_key>/<message_id>/<hash>/?',
    'module' => 'frontend',
    'action' => 'verification',
    'secure' => true,
),
```

Итог:

```text
module = frontend
action = verification
verification_key = ...
message_id = ...
hash = ...
secure = true
```

Ожидаемый класс:

```text
crmFrontendVerificationAction
```

или controller/actions-варианты по стандартному порядку поиска.

---

## 11. App config и frontend routing

Перед проектированием frontend route нужно открыть:

```text
wa-apps/{app_id}/lib/config/app.php
```

Ключевые параметры:

| Параметр | Значение для routing |
|---|---|
| `frontend => true` | Приложение имеет публичную frontend-часть. |
| `themes => true` | Приложение поддерживает темы оформления. |
| `pages => true` | Приложение поддерживает page routes. |
| `auth => true` | Приложение участвует в frontend auth/account flow. |
| `csrf => true` | CSRF проверяется для secure POST-запросов. |
| `my_account => true` | Приложение может иметь раздел личного кабинета. |
| `routing_params` | Разрешённые/поддерживаемые дополнительные route params в UI приложения «Сайт». |

Если приложение не объявляет `frontend => true`, нельзя проектировать публичный route как будто frontend поддерживается.

---

## 12. Page routes

Если приложение объявляет `pages => true`, Webasyst может добавлять routes для страниц приложения.

Для non-site приложений `waRouting::getAppRoutes()` при dispatch добавляет page routes перед app routes:

```php
array(
    'url'     => $row['full_url'],
    'module'  => 'frontend',
    'action'  => 'page',
    'page_id' => $row['id'],
)
```

Практическое следствие:

```text
/about-delivery/
→ module = frontend
→ action = page
→ page_id = 123
```

Ожидаемый класс:

```text
{app_id}FrontendPageAction
```

или соответствующий controller/actions-вариант.

Для `site` есть отдельная логика: приложение `site` само обрабатывает страницы и route `<url>` через свой frontend flow.

---

## 13. Паттерны официальных приложений

### 13.1. `site`: settlement/page/theme pattern

`site/lib/config/routing.php` минимален:

```php
return array(
    'login/' => 'login/',
    'forgotpassword/' => 'forgotpassword/',
    'signup/' => 'signup/',
    'data/regions/' => 'frontend/regions',
    'my/' => array(
        'module' => 'frontend',
        'action' => 'my',
        'secure' => true,
    ),
    '<url>' => 'frontend/'
);
```

Смысл:

- `site` закрывает произвольные page URL через `<url>`;
- login/signup/auth routes находятся на уровне приложения;
- `my/` помечен как secure;
- точное определение страницы выполняется уже внутри frontend action/model слоя `site`.

### 13.2. `blog`: compact app pattern

`blog/lib/config/routing.php` возвращает несколько наборов routes по типу URL.

Примеры:

```php
'<blog_url>/<post_url>/' => 'frontend/post',
'<post_url>/comment/'    => 'frontend/comment',
'rss/'                   => 'frontend/rss',
''                       => 'frontend/',
```

Смысл:

- разные схемы URL выбираются по настройке приложения/settlement;
- timeline routes используют regex для года, месяца, дня;
- post/comment/rss остаются обычными frontend actions.

### 13.3. `shop`: large commerce pattern

Shop-Script использует несколько route-наборов по `url_type`.

Примеры:

```php
'cart/' => 'frontend/cart',
'checkout/' => 'frontend/checkout',
'my/orders/?' => array(
    'module' => 'frontend',
    'action' => 'myOrders',
    'secure' => true,
),
'<product_url:[^/]+>/' => 'frontend/product',
```

Смысл:

- routing зависит от route param `url_type`;
- публичная витрина, cart, checkout, личный кабинет и API живут в одном app routing;
- secure routes используются для личного кабинета;
- API routes тоже описаны в app routing и ведут в frontend actions.

`shopConfig::getRouting()` дополнительно:

- выбирает набор routes по `url_type`;
- для `url_type == 2` при dispatch подмешивает category routes из модели категорий;
- вызывает event `routing` и добавляет routes плагинов перед routes приложения.

### 13.4. `crm`: backend-first/private frontend endpoints

`crm/lib/config/routing.php` показывает другой pattern:

```php
'form/<hash>/?' => 'frontend/form',
'invoice/<hash>/?' => 'frontend/invoice',
'verification' => array(
    'url' => 'verification/<verification_key>/<message_id>/<hash>/?',
    'module' => 'frontend',
    'action' => 'verification',
    'secure' => true,
),
```

Смысл:

- CRM не строит классическую публичную витрину;
- frontend routes используются для форм, invoice, verification и callback-like endpoints;
- часть endpoints может быть secure.

---

## 14. Plugin frontend routing

Plugin frontend routing нельзя проектировать абстрактно без проверки приложения.

В базовом `waAppConfig` есть вспомогательная логика для plugin routes, но конкретная интеграция зависит от app config. В Shop-Script `shopConfig::getRouting()` явно вызывает событие:

```php
wa()->event(array($this->application, 'routing'), $route);
```

и затем нормализует результат plugin-а:

```php
$route['plugin'] = $plugin;
$route['app'] = $this->application;
```

Практический вывод:

1. Перед frontend routing plugin-а открыть `wa-apps/{app_id}/lib/config/{app_id}Config.class.php`.
2. Проверить, вызывает ли приложение event `routing`.
3. Проверить формат route, который ожидает app-specific config.
4. Только после этого писать `handlers => array('routing' => 'routing')` в `plugin.php` и метод plugin-а.

Если route содержит `plugin`, `waFrontController` будет искать классы с plugin-сегментом:

```text
{app_id}{Plugin}Plugin{Module}{Action}Controller
{app_id}{Plugin}Plugin{Module}{Action}Action
{app_id}{Plugin}Plugin{Module}Actions::{action}Action()
```

Пример ожидаемого имени для shop plugin `foo`:

```text
shopFooPluginFrontendPromoAction
```

---

## 15. Генерация frontend URL

Не нужно собирать frontend URL вручную:

```php
// Неправильно
$url = '/shop/product/iphone/';
```

Правильно использовать routing:

```php
$url = wa()->getRouteUrl('shop/frontend/product', array(
    'product_url' => $product['url'],
));
```

Если нужен абсолютный URL:

```php
$url = wa()->getRouteUrl('shop/frontend/product', array(
    'product_url' => $product['url'],
), true);
```

Если в системе несколько доменов, нужно явно передавать домен или route context:

```php
$url = wa()->getRouteUrl('shop/frontend/tag', array(
    'domain' => 'example.com',
    'tag'    => 'sony',
), true);
```

Для backend URL это не подходит: frontend URL строятся через `getRouteUrl()`, backend URL — через `getAppUrl()` / `getBackendUrl()`.

---

## 16. Минимальная реализация frontend route

Задача:

```text
/catalog/
/catalog/123/
```

Приложение:

```text
wa-apps/myapp/
```

### 16.1. `app.php`

```php
<?php

return array(
    'name'     => 'My app',
    'frontend' => true,
    'themes'   => true,
    'csrf'     => true,
    'version'  => '1.0.0',
    'vendor'   => 'myvendor',
);
```

Файл:

```text
wa-apps/myapp/lib/config/app.php
```

### 16.2. Global settlement

```php
<?php

return array(
    'example.com' => array(
        array(
            'url'   => 'catalog/*',
            'app'   => 'myapp',
            'theme' => 'default',
            'locale'=> 'ru_RU',
        ),
    ),
);
```

Файл:

```text
wa-config/routing.php
```

Обычно этот файл редактируется через приложение «Сайт», а не руками.

### 16.3. App routing

```php
<?php

return array(
    ''              => 'frontend/',
    '<id:\d+>/?'   => 'frontend/view',
);
```

Файл:

```text
wa-apps/myapp/lib/config/routing.php
```

### 16.4. Default frontend action

```php
<?php

class myappFrontendAction extends waViewAction
{
    public function execute()
    {
        $model = new myappItemModel();
        $items = $model->order('id DESC')->fetchAll();

        $this->view->assign('items', $items);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/frontend/myappFrontend.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/frontend/Frontend.html
```

### 16.5. View action

```php
<?php

class myappFrontendViewAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

        if ($id <= 0) {
            throw new waException('Page not found', 404);
        }

        $model = new myappItemModel();
        $item = $model->getById($id);

        if (!$item) {
            throw new waException('Page not found', 404);
        }

        $this->view->assign('item', $item);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/frontend/myappFrontendView.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/frontend/FrontendView.html
```

---

## 17. Secure frontend route

Задача:

```text
/account/orders/
```

Route:

```php
<?php

return array(
    'account/orders/?' => array(
        'module' => 'frontend',
        'action' => 'orders',
        'secure' => true,
    ),
);
```

Action:

```php
<?php

class myappFrontendOrdersAction extends waViewAction
{
    public function execute()
    {
        $contact_id = wa()->getUser()->getId();

        if (!$contact_id) {
            throw new waRightsException(_ws('Access denied.'));
        }

        $model = new myappOrderModel();
        $orders = $model->getByField('contact_id', $contact_id, true);

        $this->view->assign('orders', $orders);
    }
}
```

Важно:

- `secure => true` заставит frontend dispatcher отправить гостя в login flow;
- если app config содержит `csrf => true`, secure POST-запросы будут проверяться на CSRF;
- в action всё равно нужно проверять права/владение данными, если показываются личные данные.

---

## 18. Расширенная реализация: controller + layout/theme

Если frontend page должна собрать несколько блоков, можно использовать `waViewController`.

Route:

```php
<?php

return array(
    'dashboard/?' => 'frontend/dashboard',
);
```

Controller:

```php
<?php

class myappFrontendDashboardController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappFrontendLayout());

        $this->executeAction(new myappFrontendDashboardHeaderAction(), 'header');
        $this->executeAction(new myappFrontendDashboardContentAction(), 'content');
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/frontend/myappFrontendDashboard.controller.php
```

Такой подход оправдан, если controller действительно оркестрирует страницу. Если страница — один шаблон и одна выборка данных, достаточно `waViewAction`.

---

## 19. Типовые ошибки

### Ошибка 1. Считать `routing.php` самостоятельным router-controller

Неправильно:

```text
routing.php сам запускает действие
```

Правильно:

```text
routing.php только задаёт module/action/plugin/placeholders. Класс запускает waFrontController.
```

### Ошибка 2. Читать placeholder через GET

Неправильно:

```php
$id = waRequest::get('id', 0, 'int');
```

Правильно:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

### Ошибка 3. Хардкодить домен или settlement

Неправильно:

```php
$url = 'https://example.com/shop/tag/sony/';
```

Правильно:

```php
$url = wa()->getRouteUrl('shop/frontend/tag', array(
    'tag' => 'sony',
), true);
```

### Ошибка 4. Путать global route и app route

Неправильно:

```php
// wa-apps/myapp/lib/config/routing.php
return array(
    'catalog/*' => 'myapp/frontend',
);
```

Правильно:

```php
// wa-config/routing.php
array('url' => 'catalog/*', 'app' => 'myapp')

// wa-apps/myapp/lib/config/routing.php
return array(
    '' => 'frontend/',
    '<id:\d+>/?' => 'frontend/view',
);
```

### Ошибка 5. Делать слишком широкий wildcard первым

Неправильно:

```php
return array(
    '*' => 'frontend/page',
    'cart/' => 'frontend/cart',
);
```

Правильно:

```php
return array(
    'cart/' => 'frontend/cart',
    '*' => 'frontend/page',
);
```

### Ошибка 6. Не учитывать app-specific `getRouting()`

В Shop-Script нельзя анализировать только `lib/config/routing.php`, потому что `shopConfig::getRouting()` выбирает route-набор по `url_type`, добавляет category routes и plugin routes.

Перед выводами нужно открыть app config class.

### Ошибка 7. Считать `secure => true` полной проверкой доступа

`secure => true` решает auth/login и CSRF-ветку для secure POST, но не проверяет владение конкретной сущностью.

Action всё равно должен проверять:

- права;
- принадлежность заказа/профиля текущему пользователю;
- валидность hash/code;
- доступность записи.

### Ошибка 8. Создавать frontend route без `frontend => true`

Если app config не объявляет frontend capability, публичный route будет архитектурно ошибочным.

---

## 20. Чеклист разработчика

Перед commit frontend route проверить:

### App config

- [ ] В `wa-apps/{app_id}/lib/config/app.php` есть `frontend => true`.
- [ ] Понятно, нужны ли `themes`, `pages`, `auth`, `csrf`, `my_account`.
- [ ] Проверен app-specific config class: `{app_id}Config.class.php`.
- [ ] Нет скрытой логики выбора route-набора, как `url_type` в Shop-Script.

### Global routing

- [ ] Есть settlement в `wa-config/routing.php` или оно создаётся через приложение «Сайт».
- [ ] Settlement не конфликтует с другими приложениями.
- [ ] Более точные settlement rules стоят выше wildcard.
- [ ] Route params settlement понятны action/model слою.
- [ ] Не захардкожен домен.

### App routing

- [ ] Есть `wa-apps/{app_id}/lib/config/routing.php`.
- [ ] Routes отсортированы от точных к широким.
- [ ] Placeholder names понятны.
- [ ] Regex placeholders не слишком широкие.
- [ ] Для secure routes указан массивный формат.
- [ ] Для page routes проверен `pages => true`.

### Dispatch

- [ ] Понятно, какие `module` и `action` получатся.
- [ ] Класс назван по Webasyst naming.
- [ ] Файл лежит в `lib/actions/{module}/`.
- [ ] Для `Action` есть шаблон или `setTemplate()`.
- [ ] Для `Controller` понятно, нужен ли layout.
- [ ] Для `Actions` существует `{action}Action()`.

### Request/security

- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] GET читается через `waRequest::get()`.
- [ ] POST читается через `waRequest::post()`.
- [ ] Secure/account data дополнительно проверяют владельца/права.
- [ ] POST формы используют CSRF.
- [ ] Нет SQL в template.
- [ ] Нет прямого вывода пользовательских данных без escaping.

### URL generation

- [ ] Frontend URL строятся через `wa()->getRouteUrl()`.
- [ ] Backend URL не строятся через `getRouteUrl()`.
- [ ] В multi-domain окружении явно учитывается domain/route context.
- [ ] Нет хардкода `/shop/`, `/blog/`, домена или settlement.

---

## 21. Чеклист ИИ-агента

Перед ответом на задачу по frontend routing ИИ-агент обязан:

1. Определить приложение: `site`, `blog`, `shop`, `crm` или пользовательское.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить `frontend`, `themes`, `pages`, `auth`, `csrf`, `my_account`, `routing_params`.
4. Открыть `wa-apps/{app_id}/lib/config/routing.php`.
5. Открыть app-specific config class: `wa-apps/{app_id}/lib/config/{app_id}Config.class.php`, если есть.
6. Проверить global settlement в `wa-config/routing.php` или явно указать, что он не был открыт.
7. Определить, какой остаток URL попадёт в app routing.
8. Определить итоговые `module/action/plugin/placeholders`.
9. Открыть `lib/actions/{module}/`.
10. Найти похожий controller/action/actions в этом приложении.
11. Открыть соответствующий template/theme template.
12. Проверить, не используются ли page routes.
13. Проверить secure/auth/CSRF/pрава.
14. Проверить URL generation через `wa()->getRouteUrl()`.
15. Только после этого писать route или код.

ИИ-агенту запрещено:

- писать frontend route без проверки `app.php`;
- путать `wa-config/routing.php` и `wa-apps/{app_id}/lib/config/routing.php`;
- читать placeholders через `waRequest::get()`;
- хардкодить домены, settlement и публичные URL;
- придумывать plugin routing без проверки app-specific config;
- подменять Webasyst routing Laravel/Symfony-style роутером;
- писать PHP в Smarty;
- игнорировать `secure`, `auth`, `csrf` и права доступа.

---

## 22. Мини-сводка

Frontend routing Webasyst — это двухуровневый механизм:

```text
https://example.com/shop/product/iphone/
→ wa-config/routing.php
→ settlement: url = shop/*, app = shop
→ internal app URL: product/iphone/
→ shop/lib/config/routing.php
→ route: product/<product_url>/ = frontend/product
→ waRequest::param('app') = shop
→ waRequest::param('module') = frontend
→ waRequest::param('action') = product
→ waRequest::param('product_url') = iphone
→ waDispatch::dispatchFrontend()
→ wa('shop', 1)
→ shop front controller
→ shopFrontendProductController
   или shopFrontendProductAction
   или shopFrontendActions::productAction()
→ template/theme rendering
```

Правильная разработка frontend route начинается не с класса, а с вопроса:

```text
Какой settlement выберет global routing и какой internal URL попадёт в app routing?
```

Только после этого можно правильно назвать route, class, file и template.
