# 01. Жизненный цикл Webasyst-запроса

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить, как Webasyst принимает HTTP/CLI-запрос, определяет окружение, выбирает приложение и передаёт управление в app-level controller/action.

---

## 1. Назначение механизма

Жизненный цикл Webasyst-запроса — это системная цепочка от входного файла до конкретного controller/action-класса приложения.

Эта глава нужна, чтобы разработчик и ИИ-агент понимали:

1. где начинается Webasyst;
2. где определяется окружение `frontend`, `backend` или `cli`;
3. где загружается системная конфигурация;
4. где загружается конфигурация приложения;
5. где работает global routing;
6. где начинается app routing;
7. где начинается `waFrontController`;
8. почему нельзя перепрыгивать через системный flow самописными entrypoint/rewrite/front-controller решениями.

Главный принцип:

```text
index.php / wa.php / cli.php
→ SystemConfig / waSystemConfig
→ waSystem
→ waDispatch
→ frontend/backend/cli branch
→ global routing или backend app detection
→ waAppConfig
→ waFrontController
→ Controller / Action / Actions
```

`waSystem` и `waDispatch` не являются “просто bootstrap”. Они определяют активное приложение, окружение, routing-параметры, права, CSRF и только потом передают запрос в прикладной dispatch.

---

## 2. Какие файлы участвуют

### 2.1. HTTP entrypoint

| Файл | Роль |
|---|---|
| `index.php` | Основная точка входа HTTP-запросов. Подключает `wa-config/SystemConfig.class.php`, создаёт `waSystem` и вызывает `dispatch()`. |
| `wa-config/SystemConfig.class.php` | Проектная системная конфигурация. Подключает autoload и наследуется от `waSystemConfig`. |
| `wa-system/config/waSystemConfig.class.php` | Базовая системная конфигурация: пути, окружение, system options, request URL, backend URL. |
| `wa-system/waSystem.class.php` | Главный системный контейнер: instances, factories, app switching, routing, request, response, view, dispatch. |
| `wa-system/controller/waDispatch.class.php` | Верхнеуровневый dispatcher: разделяет frontend/backend/cli и запускает нужную ветку. |

### 2.2. CLI entrypoints

| Файл | Роль |
|---|---|
| `wa.php` | CLI entrypoint для команды `wa`. Создаёт `SystemConfig('cli')`, подставляет `webasyst` в argv и вызывает `dispatchCli()`. |
| `cli.php` | Legacy/прямой CLI entrypoint. Подключает `wa-system/cli.php`. |
| `wa-system/cli.php` | Проверяет `PHP_SAPI`, создаёт `SystemConfig('cli')`, вызывает `dispatchCli()`. |

### 2.3. Routing и app dispatch

| Файл | Роль |
|---|---|
| `wa-config/routing.php` | Глобальная карта frontend settlements по доменам. |
| `wa-system/routing/waRouting.class.php` | Матчит global route, вычисляет `root_url`, загружает app routes, пишет параметры в `waRequest::param()`. |
| `wa-apps/{app_id}/lib/config/app.php` | Декларация приложения: frontend, plugins, themes, pages, rights, csrf, auth, ui и др. |
| `wa-apps/{app_id}/lib/config/{app_id}Config.class.php` | Опциональный app config class, если приложение переопределяет `waAppConfig`. |
| `wa-system/config/waAppConfig.class.php` | Базовая конфигурация приложения: app info, autoload, factories, routing, install/update. |
| `wa-apps/{app_id}/lib/config/routing.php` | Frontend app-level routes. |
| `wa-apps/{app_id}/lib/config/routing.backend.php` | Backend app-level routes. |
| `wa-system/controller/waFrontController.class.php` | Прикладной dispatcher внутри выбранного приложения. |
| `wa-system/controller/waController.class.php` | Базовый controller lifecycle. |
| `wa-system/controller/waViewAction.class.php` | HTML action с шаблоном. |
| `wa-system/controller/waViewController.class.php` | Controller с layout и blocks. |
| `wa-system/controller/waViewActions.class.php` | Multi-action controller. |

---

## 3. HTTP lifecycle: от `index.php` до `waDispatch`

### 3.1. `index.php`

HTTP-запрос Webasyst начинается с корневого файла:

```php
$path = dirname(__FILE__) . '/wa-config/SystemConfig.class.php';

if (file_exists($path)) {
    require_once($path);
    waSystem::getInstance(null, new SystemConfig())->dispatch();
} else {
    $path = dirname(__FILE__) . '/wa-installer/install.php';
    if (file_exists($path)) {
        require_once($path);
    } else {
        //404
    }
}
```

Разбор:

1. Webasyst ищет `wa-config/SystemConfig.class.php`.
2. Если файл есть — подключает системную конфигурацию.
3. Создаёт системный instance:

```php
waSystem::getInstance(null, new SystemConfig())
```

4. Запускает системный dispatch:

```php
->dispatch()
```

5. Если конфигурации нет — пытается передать управление installer-у.

### Практическое следствие

`index.php` не должен содержать прикладную логику приложения.

Нельзя добавлять туда:

- app routing;
- plugin routing;
- проверки конкретных моделей;
- кастомные redirects для одного приложения;
- подключение конкретных templates.

Если нужно изменить routing — это делается через:

```text
wa-config/routing.php
wa-apps/{app_id}/lib/config/routing.php
wa-apps/{app_id}/lib/config/routing.backend.php
```

а не через `index.php`.

---

## 4. `SystemConfig` и `waSystemConfig`

### 4.1. Проектный `SystemConfig`

Файл:

```text
wa-config/SystemConfig.class.php
```

Минимальный:

```php
require_once dirname(__FILE__).'/../wa-system/autoload/waAutoload.class.php';
waAutoload::register();

class SystemConfig extends waSystemConfig
{
}
```

Его задача:

1. зарегистрировать autoload;
2. дать проекту точку расширения системной конфигурации;
3. унаследовать базовое поведение `waSystemConfig`.

### 4.2. Что делает `waSystemConfig`

В конструкторе `waSystemConfig`:

1. фиксирует время старта;
2. определяет root path;
3. регистрирует основные пути через `waConfig::add()`:
   - `wa_path_root`;
   - `wa_path_apps`;
   - `wa_path_system`;
   - `wa_path_log`;
   - `wa_path_data`;
   - `wa_path_config`;
   - `wa_path_cache`;
   - `wa_path_plugins`;
   - `wa_path_installer`;
   - `wa_path_widgets`;
4. подключает системные helpers;
5. сохраняет окружение, если оно передано явно;
6. вызывает `configure()`;
7. вызывает `init()`;
8. если окружение не передано — определяет его по первому сегменту URL.

Ключевая логика определения env:

```php
if ($this->environment === null) {
    $url = explode("/", $this->getRequestUrl(true, true));
    $url = $url[0];
    $this->environment = $url === $this->getSystemOption('backend_url') ? 'backend' : 'frontend';
}
```

То есть:

```text
/{backend_url}/...
→ backend

любой другой HTTP URL
→ frontend
```

Если CLI entrypoint создаёт:

```php
new SystemConfig('cli')
```

то env сразу равен:

```text
cli
```

### 4.3. System options

В `waSystemConfig` есть базовые system options:

```php
protected static $system_options = array(
    'backend_url' => 'webasyst',
    'mod_rewrite' => true,
    'cache_versioning' => true,
);
```

Они могут быть переопределены в:

```text
wa-config/config.php
```

Критически важный параметр:

```text
backend_url
```

Он определяет backend-префикс. Поэтому нельзя хардкодить `/webasyst/`.

Правильно:

```php
wa()->getConfig()->getBackendUrl(true)
```

или для app URL в backend:

```php
wa()->getAppUrl($app_id)
```

---

## 5. `waSystem`: системный контейнер и переключение приложений

`waSystem` хранит instances по приложениям:

```php
protected static $instances = array();
protected static $current = 'wa-system';
```

Главный метод:

```php
waSystem::getInstance($name = null, ?waSystemConfig $config = null, $set_current = false)
```

Он используется в двух режимах.

### 5.1. Системный instance

На старте HTTP:

```php
waSystem::getInstance(null, new SystemConfig())
```

создаёт системный instance с конфигурацией `SystemConfig`.

### 5.2. App instance

Когда нужно загрузить приложение:

```php
wa($app, 1)
```

или внутри ядра:

```php
waSystem::getInstance($app, null, true)
```

Webasyst создаёт app config через:

```php
SystemConfig::getAppConfig($name, $system->getEnv(), $system->config->getRootPath(), $locale)
```

Дальше возможны два варианта:

1. Если существует:

```text
wa-apps/{app_id}/lib/config/{app_id}Config.class.php
```

будет создан app-specific config class, например:

```text
shopConfig
```

2. Если custom config class нет, но есть:

```text
wa-apps/{app_id}/lib/config/app.php
```

будет создан стандартный:

```php
new waAppConfig(...)
```

### 5.3. Factories

`waSystem` создаёт основные runtime-объекты через factories:

| Метод | Что возвращает |
|---|---|
| `getFrontController()` | `waFrontController` или переопределённый app front controller. |
| `getDefaultController()` | `waDefaultViewController` или переопределённый default controller. |
| `getView()` | `waSmarty3View` или другой view factory. |
| `getRouting()` | `waRouting`. |
| `getRequest()` | `waRequest`. |
| `getResponse()` | `waResponse`. |
| `getStorage()` | `waSessionStorage`. |

Практический вывод:

если приложение переопределяет dispatch-level поведение, сначала нужно проверить:

```text
wa-apps/{app_id}/lib/config/factories.php
```

Но создавать собственный front controller без необходимости нельзя: это ломает ожидаемый Webasyst-flow.

---

## 6. `wa()` как короткий доступ к `waSystem`

В документации и коде Webasyst функция `wa()` используется как короткий способ получить текущий или конкретный `waSystem`.

Типовые вызовы:

```php
wa()
wa('shop')
wa('shop', 1)
```

Практическое значение:

| Вызов | Смысл |
|---|---|
| `wa()` | Текущий активный system/app instance. |
| `wa('shop')` | Получить instance приложения `shop` без обязательного переключения active context. |
| `wa('shop', 1)` | Получить и сделать `shop` активным приложением. |

Именно поэтому в системном коде часто видно:

```php
$app_system = wa($app, 1);
$app_system->getFrontController()->dispatch();
```

Это означает:

1. загрузить приложение;
2. сделать его активным;
3. перейти в прикладной dispatch.

---

## 7. `waDispatch`: верхний dispatcher

Метод:

```php
waSystem::dispatch()
```

создаёт:

```php
$dispatch = new waDispatch($this);
$dispatch->dispatch();
```

`waDispatch::dispatch()`:

1. получает request URL без query string;
2. определяет environment через `$this->system->getEnv()`;
3. обрабатывает статические файлы (`robots.txt`, `favicon.ico`, `apple-touch-icon.png`, `site.webmanifest`);
4. если env `backend` — вызывает `dispatchBackend($request_url)`;
5. иначе — вызывает `dispatchFrontend($request_url)`;
6. ловит исключения, логирует 500/не 403/404 и отправляет response code.

Схема:

```text
waSystem::dispatch()
→ new waDispatch($system)
→ waDispatch::dispatch()
→ env?
   ├─ backend  → dispatchBackend()
   └─ frontend → dispatchFrontend()
```

CLI идёт отдельно через:

```php
waSystem::dispatchCli($argv)
→ waDispatch::dispatchCli($argv)
```

---

## 8. Frontend lifecycle

### 8.1. До routing

`waDispatch::dispatchFrontend()` сначала проверяет специальные системные URL:

| URL | Что происходит |
|---|---|
| `payments.php/...` | Платёжный callback, dispatch в `webasyst/payments`. |
| `sitemap*.xml` | Sitemap config приложения. |
| `captcha.php` или `{app}/captcha.php` | Captcha output. |
| `oauth.php` | OAuth controller. |
| `push.php/...` | Push adapter dispatch. |
| `link.php/...` | One-time app token. |

Если это не системный специальный URL, начинается обычный frontend pageview.

### 8.2. Global routing

Ключевой вызов:

```php
$route_found = $this->system->getRouting()->dispatch();
```

Это старт global routing.

`waRouting` берёт routes из:

```text
wa-config/routing.php
```

и матчится по текущему домену/root URL/request URL.

Если global route не найден:

1. при стандартном `backend_url = webasyst` Webasyst может redirect-нуть в backend;
2. если backend URL изменён — отдаёт 404, чтобы не раскрывать backend URL.

### 8.3. Что делает `waRouting::dispatch()`

`waRouting::dispatch()`:

1. получает request URL без query string;
2. выбирает routes для текущего домена;
3. матчится по global settlement;
4. записывает параметры global route в `waRequest::param()`;
5. если route содержит `app`, загружает app routes;
6. вычисляет `root_url`;
7. берёт внутренний URL внутри settlement;
8. матчится по `wa-apps/{app_id}/lib/config/routing.php`;
9. записывает app route params в `waRequest::param()`.

Упрощённая схема:

```text
/request/url/
→ wa-config/routing.php
→ settlement:
   app = shop
   url = shop/*
   theme = default
   locale = ru_RU
→ root_url = shop/
→ internal app url = category/tools/
→ wa-apps/shop/lib/config/routing.php
→ module = frontend
→ action = category
→ category_url = tools
```

### 8.4. Инициализация активного приложения

После global routing:

```php
$app = waRequest::param('app', null, 'string');
if (!$app) {
    $app = 'webasyst';
}
$app_system = wa($app, 1);
```

То есть `waRequest::param('app')` после routing определяет, какое приложение станет активным.

### 8.5. Secure frontend

Если пользователь не авторизован, а route содержит:

```php
'secure' => true
```

или:

```php
'auth' => true
```

то Webasyst вызывает login flow:

```php
$app_system->login();
```

Для secure POST при включённом app `csrf` проверяется CSRF:

```php
if (waRequest::param('secure') && waRequest::method() == 'post' && $app_system->getConfig()->getInfo('csrf')) {
    if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
        throw new waException('CSRF Protection', 403);
    }
}
```

### 8.6. Передача в app front controller

Если все проверки пройдены:

```php
$app_system->getFrontController()->dispatch();
```

Дальше начинается прикладной dispatch выбранного приложения:

```text
waFrontController
→ getDispatchParams()
→ getController()
→ Controller / Action / Actions
```

Подробно это раскрыто в главах:

```text
04-frontend-routing.md
05-backend-routing.md
06-controllers-actions.md
```

---

## 9. Backend lifecycle

### 9.1. Определение backend env

Backend env определяется в `waSystemConfig`, если первый сегмент URL совпадает с:

```php
backend_url
```

По умолчанию:

```text
webasyst
```

То есть:

```text
/webasyst/...
→ backend
```

Но фактическое значение может быть другим:

```text
/admin/...
/backend/...
/secret-admin/...
```

### 9.2. `dispatchBackend()`

`waDispatch::dispatchBackend($request_url)`:

1. redirect-ит на HTTPS, если включён `ssl_all`;
2. обрабатывает публичный dashboard-случай;
3. обрабатывает help action для Webasyst ID;
4. если пользователь не авторизован — запускает login form;
5. определяет app по URL:

```php
$url = explode("/", $request_url);
$app = isset($url[1]) && ($url[1] != 'index.php') ? $url[1] : 'webasyst';
```

Для URL:

```text
/webasyst/shop/products/
```

получается:

```text
app = shop
```

6. проверяет, что app существует;
7. проверяет права пользователя на backend app;
8. инициализирует `webasyst`;
9. инициализирует активное app:

```php
$wa_app = wa($app, 1);
```

10. проверяет CSRF для POST, если приложение включило `csrf`;
11. передаёт управление:

```php
$wa_app->getFrontController()->dispatch();
```

### 9.3. Где начинается backend routing приложения

Backend routing не начинается в `waDispatch`.

`waDispatch` только выбирает приложение по URL:

```text
/{backend_url}/{app_id}/...
```

Дальше управление получает:

```php
waFrontController::dispatch()
```

И уже там, если:

1. env = `backend`;
2. есть `wa-apps/{app_id}/lib/config/routing.backend.php`;
3. GET `module` пустой;
4. GET `plugin` пустой;

запускается app-level backend routing.

Это подробно раскрыто в главе:

```text
05-backend-routing.md
```

---

## 10. CLI lifecycle

CLI запускается через:

```text
wa.php
cli.php
```

### 10.1. `wa.php`

`wa.php`:

1. проверяет `PHP_SAPI === 'cli'`;
2. подключает `wa-config/SystemConfig.class.php`;
3. создаёт `SystemConfig('cli')`;
4. создаёт `waSystem`;
5. добавляет `webasyst` в argv;
6. вызывает:

```php
$wa->dispatchCli($_SERVER['argv']);
```

### 10.2. `cli.php`

`cli.php` подключает:

```text
wa-system/cli.php
```

А `wa-system/cli.php`:

1. проверяет CLI;
2. требует минимум 3 аргумента;
3. подключает `SystemConfig`;
4. создаёт `SystemConfig('cli')`;
5. вызывает:

```php
waSystem::getInstance(null, $config)->dispatchCli($_SERVER['argv']);
```

### 10.3. `waDispatch::dispatchCli()`

`dispatchCli()`:

1. читает app из argv;
2. поддерживает `--cron`;
3. читает slug команды;
4. собирает параметры в `waRequest::param()`;
5. загружает `webasyst`;
6. проверяет существование app;
7. для cron запускает `waCronController`;
8. для обычной CLI-команды загружает app;
9. ищет класс:

```text
{app_id}{Slug}Cli
```

Пример:

```text
php cli.php shop import --file products.csv
```

может вести к классу:

```php
shopImportCli
```

---

## 11. App config lifecycle

Когда Webasyst загружает приложение, он создаёт `waAppConfig` или app-specific config class.

### 11.1. `waAppConfig::init()`

`waAppConfig::init()`:

1. загружает default config:

```text
wa-apps/{app_id}/lib/config/config.php
```

2. загружает custom config:

```text
wa-config/apps/{app_id}/config.php
```

3. загружает app info:

```text
wa-apps/{app_id}/lib/config/app.php
```

4. регистрирует autoload для classes приложения:

```php
waAutoload::getInstance()->add($this->getClasses());
```

5. подключает classes для payment/shipping plugins, если приложение их поддерживает;
6. загружает factories:

```text
wa-apps/{app_id}/lib/config/factories.php
```

и custom factories из config.

### 11.2. `app.php`

`app.php` участвует в lifecycle не как “информационная карточка”, а как декларация возможностей.

Примерные поля, которые влияют на runtime:

| Поле | Влияние |
|---|---|
| `frontend` | Приложение может иметь frontend routing. |
| `plugins` | Приложение поддерживает plugins. |
| `themes` | Приложение поддерживает themes. |
| `pages` | Приложение поддерживает page routes. |
| `rights` | У приложения есть права доступа. |
| `csrf` | Включается системная CSRF-проверка в backend и secure frontend POST. |
| `auth` | Приложение участвует в auth-сценариях. |
| `my_account` | Приложение может участвовать в customer portal. |
| `ui` | Версия backend UI. |
| `routing_params` | Дополнительные параметры routing UI/settlements. |

### 11.3. App-specific config

Если приложение содержит:

```text
wa-apps/shop/lib/config/shopConfig.class.php
```

то используется он, а не голый `waAppConfig`.

Например, `shopConfig` переопределяет:

```php
public function getRouting($route = array(), $dispatch = false)
```

и выбирает набор frontend routes по `url_type`, добавляет динамические category routes и plugin routes.

Практический вывод:

если routing ведёт себя “не как в стандартном `waAppConfig`”, нужно открыть:

```text
wa-apps/{app_id}/lib/config/{app_id}Config.class.php
```

---

## 12. Где начинается global routing, app routing и controller dispatch

### 12.1. Global routing

Начинается здесь:

```php
$route_found = $this->system->getRouting()->dispatch();
```

Файл:

```text
wa-system/controller/waDispatch.class.php
```

Метод:

```text
dispatchFrontend()
```

Источник правил:

```text
wa-config/routing.php
```

Результат:

```php
waRequest::param('app')
waRequest::param('theme')
waRequest::param('locale')
waRequest::param('url')
...
```

### 12.2. App routing

Начинается внутри:

```php
waRouting::dispatch()
```

После того как найден global settlement с `app`.

Источник правил:

```text
wa-apps/{app_id}/lib/config/routing.php
```

или для backend:

```text
wa-apps/{app_id}/lib/config/routing.backend.php
```

Результат:

```php
waRequest::param('module')
waRequest::param('action')
waRequest::param('plugin')
waRequest::param('id')
...
```

### 12.3. Controller dispatch

Начинается здесь:

```php
$app_system->getFrontController()->dispatch();
```

Файл:

```text
wa-system/controller/waFrontController.class.php
```

Дальше:

```text
waFrontController::dispatch()
→ getDispatchParams()
→ getController()
→ execute()
→ runController()
```

Порядок поиска классов:

```text
{app}{Module}{Action}Controller
{app}{Module}{Action}Action
{app}{Module}Actions
```

---

## 13. Параметры request/routing/config

### 13.1. Источники параметров

| Источник | Где читать |
|---|---|
| URL path placeholders | `waRequest::param()` |
| Global route params | `waRequest::param()` |
| App route params | `waRequest::param()` |
| Query string | `waRequest::get()` |
| POST body | `waRequest::post()` |
| GET/POST mixed | `waRequest::request()` |
| Server data | `waRequest::server()` |
| App config | `wa()->getConfig()->getInfo()` |
| System config | `wa()->getConfig()->systemOption()` / `getBackendUrl()` |

### 13.2. Типовой приоритет в runtime

Для frontend:

```text
wa-config/routing.php
→ app routing.php
→ waRequest::param()
→ waFrontController::getDispatchParams()
```

Для backend:

```text
/{backend_url}/{app_id}/
→ GET module/action/plugin
→ optionally routing.backend.php
→ waRequest::param()
→ waFrontController::getDispatchParams()
```

### 13.3. Ошибка: читать route placeholder из GET

Если route:

```php
'product/<product_url:[^/]+>/' => 'frontend/product'
```

то:

```php
$product_url = waRequest::param('product_url');
```

а не:

```php
$product_url = waRequest::get('product_url');
```

---

## 14. Паттерны официального Webasyst-кода

### 14.1. `site`

`site` показывает минимальный frontend lifecycle:

```text
wa-config/routing.php
→ app = site
→ site/lib/config/routing.php
→ '<url>' => 'frontend/'
→ siteFrontendAction
→ waPageAction
→ theme template page.html / error.html
```

`site/lib/config/app.php` включает:

```php
'frontend' => true,
'plugins' => true,
'themes' => true,
'pages' => true,
'auth' => true,
'csrf' => true,
'my_account' => true,
```

То есть `site` — базовый пример app, который связан с pages, themes, routing и frontend rendering.

### 14.2. `blog`

`blog/lib/config/routing.php` содержит несколько наборов rules по типу URL. Это значит, что app config может выбирать нужный набор routes в зависимости от settlement params.

Типовой flow:

```text
blog route
→ module = frontend
→ action = post/default/comment/rss
→ blogFrontendAction / blogFrontendPostAction
→ blogFrontendLayout
→ theme template index.html + stream.html/post.html
```

`blogFrontendAction` в конструкторе ставит layout и theme template:

```php
$this->setLayout(new blogFrontendLayout());
$this->setThemeTemplate('stream.html');
```

### 14.3. `shop`

`shop` показывает сложный app config lifecycle:

1. `shop/lib/config/routing.php` содержит наборы routes по `url_type`.
2. `shopConfig::getRouting()` выбирает набор routes.
3. При dispatch может добавлять динамические category routes.
4. Через event `shop.routing` добавляются plugin routes.
5. Secure my-account routes ставят:

```php
'secure' => true
```

6. `shopFrontendAction` ставит `shopFrontendLayout`, если запрос не XHR.

### 14.4. `crm`

`crm/lib/config/routing.php` показывает frontend endpoints, которые не обязательно являются “витринными страницами”:

```php
'form/?' => 'frontend/formSubmit',
'form/iframe/<id:\d+>/?' => 'frontend/formIframe',
'invoice/<hash>/?' => 'frontend/invoice',
'verification' => [
    'url' => 'verification/<verification_key>/<message_id>/<hash>/?',
    'module' => 'frontend',
    'action' => 'verification',
    'secure' => true,
],
```

Это важный паттерн: frontend routing может обслуживать публичные формы, iframe, invoice, verification и callbacks, а не только theme pages.

---

## 15. Минимальная реализация: правильный frontend request flow

Задача:

```text
/example/
```

должен открыть frontend action приложения `myapp`.

### 15.1. Глобальное поселение

Файл:

```text
wa-config/routing.php
```

Пример settlement:

```php
return array(
    'example.com' => array(
        array(
            'url' => 'example/*',
            'app' => 'myapp',
            'theme' => 'default',
            'locale' => 'ru_RU',
        ),
    ),
);
```

### 15.2. App config

Файл:

```text
wa-apps/myapp/lib/config/app.php
```

Минимально для frontend:

```php
<?php

return array(
    'name'     => 'My app',
    'frontend' => true,
    'version'  => '1.0.0',
    'vendor'   => 'myvendor',
);
```

### 15.3. App routing

Файл:

```text
wa-apps/myapp/lib/config/routing.php
```

```php
<?php

return array(
    '' => 'frontend/',
    'item/<id:\d+>/' => 'frontend/item',
);
```

### 15.4. Frontend action

Файл:

```text
wa-apps/myapp/lib/actions/frontend/myappFrontend.action.php
```

```php
<?php

class myappFrontendAction extends waViewAction
{
    public function execute()
    {
        $this->getResponse()->setTitle('My app');
        $this->view->assign('items', array());
    }
}
```

Шаблон:

```text
wa-apps/myapp/templates/actions/frontend/Frontend.html
```

```smarty
<h1>{$wa->title()|escape}</h1>
```

### 15.5. Frontend item action

Файл:

```text
wa-apps/myapp/lib/actions/frontend/myappFrontendItem.action.php
```

```php
<?php

class myappFrontendItemAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

        if ($id <= 0) {
            throw new waException('Item not found', 404);
        }

        $this->view->assign('id', $id);
    }
}
```

Шаблон:

```text
wa-apps/myapp/templates/actions/frontend/FrontendItem.html
```

```smarty
<div class="item-page">
    Item ID: {$id|escape}
</div>
```

### 15.6. Что произойдёт

```text
/example/item/123/
→ index.php
→ SystemConfig
→ waSystem
→ waDispatch::dispatchFrontend()
→ waRouting::dispatch()
→ global route app=myapp, root_url=example/
→ myapp/lib/config/routing.php
→ module=frontend, action=item, id=123
→ wa('myapp', 1)
→ myapp FrontController
→ myappFrontendItemAction
→ templates/actions/frontend/FrontendItem.html
```

---

## 16. Минимальная реализация: CLI command

Задача:

```text
php cli.php myapp rebuild
```

### 16.1. CLI class

Файл:

```text
wa-apps/myapp/lib/cli/myappRebuild.cli.php
```

```php
<?php

class myappRebuildCli extends waCliController
{
    public function execute()
    {
        $model = new myappItemModel();
        $model->rebuild();
    }
}
```

### 16.2. Что произойдёт

```text
cli.php
→ wa-system/cli.php
→ SystemConfig('cli')
→ waSystem
→ waDispatch::dispatchCli()
→ app = myapp
→ slug = rebuild
→ class = myappRebuildCli
→ execute()
```

---

## 17. Расширенная реализация: когда участвует app-specific config

Если стандартного `waAppConfig` недостаточно, приложение может иметь:

```text
wa-apps/myapp/lib/config/myappConfig.class.php
```

Например, когда нужно:

- выбирать разные наборы frontend routes по settlement params;
- добавлять динамические routes из БД;
- подключать plugin routes;
- централизованно проверять права;
- изменять factory behavior;
- влиять на app-specific runtime.

Skeleton:

```php
<?php

class myappConfig extends waAppConfig
{
    public function getRouting($route = array(), $dispatch = false)
    {
        $routes = parent::getRouting($route);

        if ($dispatch) {
            // Добавлять только то, что действительно нужно для dispatch.
        }

        return $routes;
    }
}
```

Ограничение:

не нужно создавать app config class только ради одной страницы или одного controller. Для обычных routes достаточно:

```text
lib/config/routing.php
lib/config/routing.backend.php
```

---

## 18. Типовые ошибки

### Ошибка 1. Писать прикладную логику в `index.php`

Неправильно:

```php
if ($_SERVER['REQUEST_URI'] == '/shop/custom/') {
    require 'custom.php';
}
```

Правильно:

```text
wa-config/routing.php
wa-apps/{app_id}/lib/config/routing.php
```

### Ошибка 2. Считать `wa-config/routing.php` app routing

Неправильно:

```text
В wa-config/routing.php надо описать все product/category routes.
```

Правильно:

```text
wa-config/routing.php выбирает settlement и app.
wa-apps/{app_id}/lib/config/routing.php выбирает module/action внутри приложения.
```

### Ошибка 3. Хардкодить backend URL

Неправильно:

```php
$url = '/webasyst/shop/';
```

Правильно:

```php
$url = wa()->getConfig()->getBackendUrl(true) . 'shop/';
```

или внутри backend app:

```php
$url = wa()->getAppUrl('shop');
```

### Ошибка 4. Читать route params через GET

Неправильно:

```php
$id = waRequest::get('id');
```

Если `id` пришёл из route:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

### Ошибка 5. Создавать собственный front controller без крайней причины

Неправильно:

```text
Сделаем myappFrontController и обойдём waFrontController.
```

Правильно:

```text
Оставить waFrontController, описать routes и классы по naming convention.
```

Переопределять `front_controller` через `factories.php` можно только если понятно, какой системный этап нужно изменить и почему стандартный flow не подходит.

### Ошибка 6. Игнорировать `csrf` в app config

Если app config содержит:

```php
'csrf' => true
```

Webasyst проверяет CSRF в backend POST и secure frontend POST. Нельзя придумывать параллельный CSRF-механизм без причины.

### Ошибка 7. Путать frontend secure route и backend rights

`secure => true` во frontend route означает “требуется авторизация пользователя”.

Это не то же самое, что backend права:

```php
wa()->getUser()->getRights($app_id, 'backend')
```

Для изменяющих действий всё равно нужны собственные проверки прав/владения объектом.

### Ошибка 8. Не проверять app-specific config

Если приложение ведёт себя нестандартно, нужно открыть:

```text
wa-apps/{app_id}/lib/config/{app_id}Config.class.php
```

Например, `shopConfig` меняет routing behavior. Смотреть только `routing.php` недостаточно.

---

## 19. Чеклист разработчика

Перед изменением lifecycle/routing/controller flow проверить:

### Entry point

- [ ] Не требуется ли изменить `index.php`. В норме — не требуется.
- [ ] Не используется ли hardcode `/webasyst/`.
- [ ] Понятно ли, frontend это, backend или CLI.

### System config

- [ ] Проверен `backend_url`.
- [ ] Понятно, как определяется env.
- [ ] Не сломан ли `mod_rewrite`/root URL.
- [ ] Не добавлена ли прикладная логика в системный bootstrap.

### App config

- [ ] Открыт `wa-apps/{app_id}/lib/config/app.php`.
- [ ] Проверены `frontend`, `plugins`, `themes`, `pages`, `rights`, `csrf`, `auth`, `ui`.
- [ ] Проверено наличие `{app_id}Config.class.php`.
- [ ] Проверено наличие `factories.php`, если поведение controller/view/routing нестандартное.

### Routing

- [ ] Для frontend проверен `wa-config/routing.php`.
- [ ] Для frontend проверен `lib/config/routing.php`.
- [ ] Для backend проверен `lib/config/routing.backend.php`.
- [ ] Понятно, какие `module/action` получаются.
- [ ] Route placeholders читаются через `waRequest::param()`.

### Controller dispatch

- [ ] Понятно, какой класс ищет `waFrontController`.
- [ ] Проверен порядок `Controller → Action → Actions`.
- [ ] Класс и файл названы по Webasyst naming.
- [ ] Шаблон лежит в правильном месте.

### Security

- [ ] Backend доступ проверяется через app rights.
- [ ] Secure frontend routes не считаются полноценной бизнес-авторизацией.
- [ ] POST учитывает CSRF.
- [ ] Redirect не раскрывает скрытый backend URL.

---

## 20. Чеклист ИИ-агента

Перед ответом по lifecycle/routing/dispatch ИИ-агент обязан:

1. Определить тип запроса: HTTP frontend, HTTP backend или CLI.
2. Открыть entrypoint:
   - `index.php`;
   - `wa.php`;
   - `cli.php` / `wa-system/cli.php`.
3. Открыть `wa-config/SystemConfig.class.php`.
4. Открыть `wa-system/config/waSystemConfig.class.php`.
5. Проверить, как определяется env.
6. Открыть `wa-system/waSystem.class.php`.
7. Открыть `wa-system/controller/waDispatch.class.php`.
8. Для frontend:
   - открыть `wa-config/routing.php`;
   - открыть `wa-apps/{app_id}/lib/config/routing.php`;
   - проверить `{app_id}Config.class.php`.
9. Для backend:
   - определить app из `/{backend_url}/{app_id}/`;
   - открыть `wa-apps/{app_id}/lib/config/routing.backend.php`;
   - проверить GET `module/plugin`.
10. Открыть `wa-system/controller/waFrontController.class.php`.
11. Определить итоговые `module/action/plugin`.
12. Найти controller/action/actions class.
13. Проверить template/layout.
14. Проверить rights, CSRF, auth/secure.
15. Только после этого писать код или архитектурное решение.

ИИ-агенту запрещено:

- объяснять lifecycle без системных классов;
- пропускать `waDispatch`;
- считать `routing.php` единственным routing-уровнем;
- придумывать отдельный router поверх Webasyst;
- хардкодить backend URL;
- читать route placeholders через GET;
- менять `index.php` для прикладной задачи;
- игнорировать app-specific config;
- игнорировать `csrf`, `rights`, `secure`, `auth`.

---

## 21. Мини-сводка

Жизненный цикл HTTP-запроса Webasyst:

```text
index.php
→ wa-config/SystemConfig.class.php
→ waSystem::getInstance(null, new SystemConfig())
→ waSystem::dispatch()
→ waDispatch::dispatch()
→ env?
   ├─ backend
   │  → dispatchBackend()
   │  → определить app из /{backend_url}/{app_id}/
   │  → проверить auth/backend rights/CSRF
   │  → wa($app, 1)
   │  → app FrontController
   │  → routing.backend.php или GET module/action
   │  → Controller / Action / Actions
   │
   └─ frontend
      → dispatchFrontend()
      → waRouting::dispatch()
      → wa-config/routing.php
      → app routing.php
      → waRequest::param()
      → secure/auth/CSRF checks
      → wa($app, 1)
      → app FrontController
      → Controller / Action / Actions
```

Жизненный цикл CLI-запроса:

```text
wa.php / cli.php
→ SystemConfig('cli')
→ waSystem
→ waDispatch::dispatchCli()
→ app
→ slug
→ {app}{Slug}Cli
→ execute()
```

Главное правило: Webasyst already has a dispatch pipeline. Правильная разработка не обходит его, а подключается в нужной точке: config, routing, app config, controller/action, model, template.
