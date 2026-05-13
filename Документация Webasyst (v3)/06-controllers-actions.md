# 06. Controllers и actions в Webasyst

**Статус:** опубликован v3
**Язык:** русский  
**Назначение:** объяснить, какой PHP-класс принимает управление после routing, как он выполняется, где рендерится шаблон и когда выбирать `Controller`, `Action` или `Actions`.

---

## 1. Назначение механизма

Routing в Webasyst не выполняет бизнес-логику напрямую. Он только приводит URL к dispatch-параметрам:

```text
module
action
plugin
widget
дополнительные route params
```

После этого `waFrontController` ищет PHP-класс, который должен обработать запрос.

В Webasyst есть четыре базовых сценария обработки:

| Сценарий | Базовый класс | Назначение |
|---|---|---|
| Command controller | `waController` | Redirect, POST-команда, файл, служебный ответ, операция без HTML-шаблона. |
| Page/block action | `waViewAction` | Один HTML-сценарий с одним шаблоном. |
| View controller | `waViewController` | Сборка страницы из layout и нескольких action-блоков. |
| Multi-action controller | `waViewActions` | Один класс на несколько близких action-методов `*Action()`. |

Ключевое правило:

```text
routing определяет module/action
waFrontController выбирает класс
класс выполняет execute()/actionAction()
view/layout рендерит шаблон
```

Поэтому нельзя проектировать action отдельно от route, имени класса, расположения файла и шаблона.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/controller/waFrontController.class.php` | Главный прикладной dispatcher. Определяет `plugin`, `module`, `action` и ищет класс. |
| `wa-system/controller/waController.class.php` | Базовый controller lifecycle: `preExecute() → execute() → afterExecute()`. |
| `wa-system/controller/waViewController.class.php` | Controller с layout и blocks. |
| `wa-system/controller/waDefaultViewController.class.php` | Обёртка, через которую запускается одиночный `waViewAction`. |
| `wa-system/controller/waViewAction.class.php` | HTML action: `execute()` + template fetch. |
| `wa-system/controller/waViewActions.class.php` | Multi-action controller: `defaultAction()`, `{action}Action()`, mobile variant. |
| `wa-system/controller/waJsonController.class.php` | JSON controller. Подробно разбирается в `16-json-ajax-long-cli.md`. |
| `wa-system/controller/waJsonActions.class.php` | Multi-action JSON controller. |
| `wa-system/controller/waLongActionController.class.php` | Long action controller. |
| `wa-system/controller/waCliController.class.php` | CLI controller. |
| `wa-system/layout/waLayout.class.php` | Layout, куда попадают blocks. |
| `wa-system/view/waView.class.php` | Базовый view layer. |
| `wa-system/view/waSmarty3View.class.php` | Smarty view implementation. |

### 2.2. Файлы приложения

| Файл/директория | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | App capabilities: `frontend`, `rights`, `csrf`, `ui`, `themes`, `pages`. |
| `wa-apps/{app_id}/lib/config/routing.php` | Frontend route rules. |
| `wa-apps/{app_id}/lib/config/routing.backend.php` | Backend route rules. |
| `wa-apps/{app_id}/lib/config/factories.php` | Редкое место для переопределения `front_controller`, `default_controller`, `view`. |
| `wa-apps/{app_id}/lib/actions/{module}/` | Controller/action/actions classes модуля. |
| `wa-apps/{app_id}/lib/layouts/` | Layout classes приложения. |
| `wa-apps/{app_id}/templates/actions/{module}/` | Шаблоны actions. |
| `wa-apps/{app_id}/templates/actions-legacy/{module}/` | Legacy-шаблоны, если проект поддерживает отдельный legacy view. |
| `wa-apps/{app_id}/themes/{theme_id}/` | Theme templates для frontend. |

### 2.3. Файлы плагина

| Файл/директория | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Plugin config, handlers, rights. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/{module}/` | Plugin controllers/actions. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/{module}/` | Plugin templates. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions-legacy/{module}/` | Legacy plugin templates. |

---

## 3. Системная цепочка выполнения

### 3.1. Общая цепочка

Для backend и frontend после routing цепочка сходится в `waFrontController`:

```text
HTTP request
→ waDispatch
→ frontend/backend routing
→ waRequest::param('app/module/action/plugin/...')
→ wa($app)->getFrontController()->dispatch()
→ waFrontController::getDispatchParams()
→ waFrontController::execute($plugin, $module, $action)
→ waFrontController::getController()
→ найденный controller/action/actions
→ run()
→ HTML/JSON/redirect/file response
```

### 3.2. Как `waFrontController` выбирает класс

`waFrontController::getController()` проверяет варианты строго по порядку.

#### Шаг 1. Single Controller

```text
{app_id}{Module}{Action}Controller
```

Если `action` пустой:

```text
{app_id}{Module}Controller
```

Пример:

```text
app_id = shop
module = frontend
action = product
```

Первым будет проверен класс:

```php
shopFrontendProductController
```

#### Шаг 2. Single Action

```text
{app_id}{Module}{Action}Action
```

Пример:

```php
shopFrontendProductAction
```

Если такой класс найден, он не запускается напрямую. `waFrontController` берёт default controller:

```text
waDefaultViewController
```

и вызывает:

```php
$controller->setAction($class_name);
```

Дальше `waDefaultViewController` создаёт экземпляр action и выполняет его как блок `content`.

#### Шаг 3. Multi Actions

```text
{app_id}{Module}Actions
```

Пример:

```php
myappOrdersActions
```

При `action = delete` будет вызван метод:

```php
public function deleteAction()
```

Если `action` пустой, `waViewActions` использует:

```php
public function defaultAction()
```

#### Шаг 4. Default fallback

Если вызов был сделан с разрешённым `$try_default`, `waFrontController` может повторить поиск без `action`.

#### Шаг 5. 404

Если ни один класс не найден, выбрасывается `waException` с кодом `404` и списком проверенных классов.

---

## 4. Ключевые классы и методы

### 4.1. `waController`

`waController` — минимальный базовый controller.

Lifecycle:

```php
public function run($params = null)
{
    $this->preExecute();
    $this->execute();
    $this->afterExecute();
}
```

Методы, важные для прикладного кода:

| Метод | Назначение |
|---|---|
| `preExecute()` | Подготовка до основной логики. Можно переопределять. |
| `execute()` | Основная логика controller. |
| `afterExecute()` | Завершение после основной логики. |
| `getUser()` | Текущий пользователь. |
| `getUserId()` | ID текущего пользователя. |
| `getApp()` / `getAppId()` | Текущее приложение. |
| `getRights($name = null, $assoc = true)` | Права пользователя в текущем приложении. |
| `getRequest()` | `waRequest`. |
| `getResponse()` | `waResponse`. |
| `getStorage()` | Session storage. |
| `redirect()` | Redirect через `waResponse`. |
| `logAction()` | Запись в `wa_log`. |
| `appSettings()` | Чтение app settings через `waAppSettingsModel`. |

Использовать `waController`, когда результатом не является HTML-страница:

- POST save/delete command;
- redirect;
- download/file response;
- служебное действие;
- callback;
- endpoint, который сам управляет выводом.

### 4.2. `waViewAction`

`waViewAction` наследует `waController`, но предназначен для HTML-rendering.

Ключевая цепочка `display()`:

```text
cache setup
→ preExecute()
→ execute()
→ afterExecute()
→ view->fetch(template)
→ clear assigned vars
→ return HTML string
```

В `execute()` action обычно:

1. читает `waRequest::param()/get()/post()`;
2. проверяет входные данные;
3. вызывает model/service/helper;
4. назначает переменные в `$this->view`;
5. при необходимости задаёт meta/canonical/status/headers через `$this->getResponse()`;
6. при необходимости задаёт layout или theme template.

Типовой action:

```php
<?php

class myappOrdersListAction extends waViewAction
{
    public function execute()
    {
        $model = new myappOrderModel();
        $orders = $model->order('id DESC')->fetchAll();

        $this->view->assign('orders', $orders);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersList.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersList.html
```

### 4.3. `waDefaultViewController`

`waDefaultViewController` — системная обёртка для одиночного `waViewAction`.

Когда `waFrontController` находит класс `myappOrdersListAction`, он делает не так:

```text
new myappOrdersListAction()->run()
```

а так:

```text
waDefaultViewController
→ setAction('myappOrdersListAction')
→ execute()
→ executeAction($action)
→ блок content
→ display()
```

Практическое следствие:

- одиночный `waViewAction` фактически исполняется внутри `waViewController`;
- если action задаёт layout через `$this->setLayout()`, default controller использует этот layout;
- action может быть самостоятельной страницей без собственного controller-класса.

### 4.4. `waViewController`

`waViewController` нужен, когда controller собирает HTML-страницу из нескольких блоков.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `setLayout(waLayout $layout = null)` | Назначить layout. |
| `executeAction(waViewAction $action, $name = 'content', waDecorator $decorator = null)` | Выполнить action и положить результат в block. |
| `getLayout()` | Получить текущий layout. |
| `display()` | Передать blocks в layout или вывести blocks напрямую. |

Минимальный controller:

```php
<?php

class myappOrdersController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());

        $this->executeAction(new myappOrdersToolbarAction(), 'toolbar');
        $this->executeAction(new myappOrdersListAction(), 'content');
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.controller.php
```

Такой controller оправдан, если он действительно оркестрирует страницу. Если страница состоит из одного шаблона, обычно достаточно `waViewAction`.

### 4.5. `waViewActions`

`waViewActions` — один controller-класс с несколькими action-методами.

Lifecycle:

```text
run($params)
→ action = $params ?: 'default'
→ если mobile и есть {action}MobileAction(), используется mobile variant
→ preExecute()
→ execute($action)
→ {action}Action()
→ postExecute()
→ display()
```

Пример:

```php
<?php

class myappOrdersActions extends waViewActions
{
    public function defaultAction()
    {
        $model = new myappOrderModel();
        $this->view->assign('orders', $model->order('id DESC')->fetchAll());
    }

    public function stateAction()
    {
        $state = waRequest::post('state', '', waRequest::TYPE_STRING_TRIM);
        $this->view->assign('state', $state);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

Шаблоны:

```text
wa-apps/myapp/templates/actions/orders/Orders.html
wa-apps/myapp/templates/actions/orders/OrdersState.html
```

`waViewActions` удобен для компактных групп близких действий. Но если класс начинает содержать десятки разнородных методов, лучше разнести логику по отдельным `Action`/`Controller` классам.

---

## 5. Параметры routing/request/config и приоритет

### 5.1. Откуда берутся `module` и `action`

#### Frontend

Во frontend `waDispatch::dispatchFrontend()` сначала запускает global routing и app routing. После этого в `waRequest::param()` появляются:

```text
app
module
action
secure
theme
locale
url
placeholders
route params
```

Затем активное приложение запускает свой `waFrontController`.

Внутри frontend `getDispatchParams()` базово ставит:

```text
module = frontend
action = null
plugin = null
```

После этого route params переопределяют значения:

```php
$module = waRequest::param('module', $module, 'string');
$action = waRequest::param('action', $action, 'string');
$plugin = waRequest::param('plugin', $plugin, 'string');
```

#### Backend

В backend `module`, `action`, `plugin`, `widget` могут прийти из GET:

```text
?module=orders&action=state
?plugin=redirect&module=settings
```

Если есть `routing.backend.php` и GET `module/plugin` пустые, backend route может выставить `waRequest::param('module')` и `waRequest::param('action')`.

### 5.2. Как читать входные данные

| Источник | Метод |
|---|---|
| Route placeholders и route params | `waRequest::param()` |
| Query string | `waRequest::get()` |
| POST body | `waRequest::post()` |
| GET или POST | `waRequest::request()` |
| Server vars | `waRequest::server()` |

Пример route:

```php
'orders/<id:\d+>/edit/?' => 'orders/edit',
```

Правильно:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

Неправильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

### 5.3. Что проверять в app config

Перед созданием controller/action нужно открыть:

```text
wa-apps/{app_id}/lib/config/app.php
```

Особенно важны поля:

| Поле | Почему важно |
|---|---|
| `frontend` | Есть ли frontend routing. |
| `rights` | Нужны ли права в backend. |
| `csrf` | Включена ли системная CSRF-проверка POST. |
| `themes` | Есть ли theme templates. |
| `pages` | Может ли app использовать page routes. |
| `auth` | Может ли app участвовать в auth flow. |
| `ui` | Какой backend UI поддерживается. |
| `plugins` | Может ли app иметь plugins. |

---

## 6. Паттерны официального Webasyst-кода

### 6.1. `blogFrontendAction`: frontend page action + layout + theme template

`blogFrontendAction` наследует app-specific базовый action `blogViewAction`, в constructor:

- читает GET `page`;
- читает route params через `$this->getRequest()->param()`;
- при обычном запросе назначает `blogFrontendLayout`;
- задаёт theme template `stream.html`.

В `execute()` он:

- проверяет route params;
- работает с `blogPostModel`;
- назначает данные во view;
- задаёт canonical URL через `wa()->getRouteUrl()`;
- передаёт дополнительные данные в layout.

Это хороший пример page-level `waViewAction`, который сам задаёт frontend layout и theme template.

### 6.2. `blogFrontendPostAction`: один action, разные режимы frontend/backend

`blogFrontendPostAction` показывает, что один action может иметь внутреннее разделение, если это оправдано задачей:

```text
execute()
→ если frontend: setLayout(new blogFrontendLayout()) + frontendExecute()
→ иначе: backendExecute()
```

Такой подход допустим для preview/просмотра одной сущности в разных окружениях, но его нельзя превращать в универсальный God-action.

### 6.3. `blogFrontendLayout`: layout как место для theme index и frontend hooks

`blogFrontendLayout` наследует `waLayout` и в `execute()`:

- подключает JS;
- назначает переменные темы;
- нормализует `action` route param;
- вызывает frontend events;
- задаёт theme template `index.html`.

Это пример правильного разделения:

```text
Action готовит контент страницы
Layout готовит оболочку, theme index и общие frontend hooks
```

### 6.4. `siteFrontendAction` + `waPageAction`: page pattern

`siteFrontendAction` наследует системный `waPageAction`.

`waPageAction`:

- читает `page_id` из `waRequest::param()`;
- загружает страницу через page model;
- задаёт status/title/meta/canonical;
- рендерит page content через Smarty;
- выбирает `page.html` или `error.html`.

`siteFrontendAction` расширяет это поведение под приложение `site`: назначает breadcrumbs, theme URL, page data и корректный error template.

Это эталонный пример того, что page routes не нужно реализовывать вручную, если app уже поддерживает pages.

### 6.5. `shopFrontendAction`: базовый frontend action для большого приложения

`shopFrontendAction` наследует `waViewAction` и в constructor назначает `shopFrontendLayout`, если запрос не XHR.

В `execute()` он:

- проверяет, что текущий app URL пустой для homepage;
- задаёт title/meta/OG/canonical;
- вызывает frontend events;
- назначает данные магазина;
- выбирает theme template `home.html`.

В `display()` дополнительно:

- назначает navigation events;
- передаёт route params в Smarty globals;
- обрабатывает 404 и fallback на `error.html`.

Это пример большого app-level frontend action, где базовый action содержит общую frontend-логику, а конкретные actions могут наследоваться от него.

### 6.6. `shopFrontendLayout`: layout как frontend shell

`shopFrontendLayout` в `execute()`:

- обрабатывает currency/locale redirect;
- назначает текущий action;
- задаёт theme template `index.html`;
- вызывает frontend hooks `frontend_head`, `frontend_header`, `frontend_nav`, `frontend_footer`;
- назначает currencies и route params в Smarty globals.

Это пример layout, который не загружает сущность страницы. Сущность страницы остаётся в action.

---

## 7. Минимальная реализация

Задача: добавить backend-раздел заказов с URL:

```text
/{backend_url}/myapp/orders/
/{backend_url}/myapp/orders/123/
/{backend_url}/myapp/orders/123/delete/
```

### 7.1. Route

```php
<?php

return array(
    'orders/?'               => 'orders/list',
    'orders/<id:\d+>/?'      => 'orders/view',
    'orders/<id:\d+>/delete/' => 'orders/delete',
    ''                       => 'backend',
);
```

Файл:

```text
wa-apps/myapp/lib/config/routing.backend.php
```

### 7.2. HTML action для списка

```php
<?php

class myappOrdersListAction extends waViewAction
{
    public function execute()
    {
        $model = new myappOrderModel();
        $orders = $model->order('id DESC')->fetchAll();

        $this->view->assign('orders', $orders);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersList.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersList.html
```

### 7.3. HTML action для просмотра

```php
<?php

class myappOrdersViewAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

        if ($id <= 0) {
            throw new waException('Order not found', 404);
        }

        $model = new myappOrderModel();
        $order = $model->getById($id);

        if (!$order) {
            throw new waException('Order not found', 404);
        }

        $this->view->assign('order', $order);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersView.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

### 7.4. Command controller для удаления

```php
<?php

class myappOrdersDeleteController extends waController
{
    public function execute()
    {
        $this->checkRights();

        if (waRequest::method() !== 'post') {
            throw new waException('Method not allowed', 405);
        }

        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

        if ($id <= 0) {
            throw new waException('Order not found', 404);
        }

        $model = new myappOrderModel();
        $model->deleteById($id);

        $this->redirect(wa()->getAppUrl('myapp') . 'orders/');
    }

    protected function checkRights()
    {
        if (!$this->getRights('delete')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersDelete.controller.php
```

Шаблон не нужен, потому что это command controller.

---

## 8. Расширенная реализация

### 8.1. Когда нужен `waViewController`

Использовать `waViewController`, если одна страница должна собираться из нескольких независимых блоков:

```text
layout
├── sidebar
├── toolbar
└── content
```

Пример:

```php
<?php

class myappOrdersController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());

        $this->executeAction(new myappOrdersSidebarAction(), 'sidebar');
        $this->executeAction(new myappOrdersToolbarAction(), 'toolbar');
        $this->executeAction(new myappOrdersListAction(), 'content');
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.controller.php
```

### 8.2. Когда нужен app-specific базовый action

Если у frontend actions приложения повторяются:

- общий layout;
- общие events;
- общий error handling;
- общие Smarty globals;
- общая подготовка theme variables;
- общая canonical/meta логика;

можно создать базовый action:

```php
<?php

class myappFrontendAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new myappFrontendLayout());
        }
    }

    public function display($clear_assign = true)
    {
        $params = waRequest::param();
        unset($params['module'], $params['action'], $params['url']);
        $this->view->getHelper()->globals($params);

        return parent::display($clear_assign);
    }
}
```

Тогда конкретная страница наследуется от него:

```php
<?php

class myappFrontendCatalogAction extends myappFrontendAction
{
    public function execute()
    {
        $this->setThemeTemplate('catalog.html');
    }
}
```

Такой базовый класс оправдан только при реальном повторении. Не нужно создавать его заранее «на всякий случай».

### 8.3. Когда нужен `waViewActions`

`waViewActions` уместен для компактных групп:

```text
settings/default
settings/save
settings/reset
```

Пример:

```php
<?php

class myappSettingsActions extends waViewActions
{
    public function defaultAction()
    {
        $this->view->assign('settings', wa()->getSetting(null, null, 'myapp'));
    }

    public function saveAction()
    {
        if (waRequest::method() !== 'post') {
            throw new waException('Method not allowed', 405);
        }

        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        wa()->getConfig()->setCount(false);

        $this->view->assign('saved', true);
        $this->view->assign('name', $name);
    }
}
```

Но для сложных операций сохранения лучше использовать отдельный `waController` или `waJsonController`, чтобы не смешивать HTML-view и command logic.

### 8.4. Когда нужен JSON controller

Если endpoint должен вернуть JSON, не нужно вручную печатать `json_encode()` в `waController`.

Использовать:

```text
waJsonController
waJsonActions
```

Это отдельно раскрывается в главе `16-json-ajax-long-cli.md`.

### 8.5. Когда нужен long action или CLI

Если операция долгая или запускается не из HTTP page request:

| Сценарий | Класс |
|---|---|
| Долгий backend-процесс с progress | `waLongActionController` |
| CLI command | `waCliController` |
| Cron | app/plugin cron config + CLI controller |

Не нужно делать долгую операцию внутри обычного `waViewAction`.

---

## 9. Где лежат файлы и шаблоны

### 9.1. Single Controller

Route:

```php
'orders/<id:\d+>/delete/?' => 'orders/delete',
```

Класс:

```php
myappOrdersDeleteController
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersDelete.controller.php
```

Шаблон:

```text
не нужен, если controller сам делает redirect/response
```

### 9.2. Single Action

Route:

```php
'orders/<id:\d+>/?' => 'orders/view',
```

Класс:

```php
myappOrdersViewAction
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersView.action.php
```

Автошаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

### 9.3. Default action

Route:

```php
'orders/?' => 'orders/',
```

или backend GET:

```text
?module=orders
```

Класс одиночного action:

```php
myappOrdersAction
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/Orders.html
```

### 9.4. Multi Actions

Route:

```php
'orders/<id:\d+>/state/?' => 'orders/state',
```

Класс:

```php
myappOrdersActions
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

Метод:

```php
public function stateAction()
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersState.html
```

### 9.5. Plugin action

Backend plugin URL:

```text
/{backend_url}/shop/?plugin=redirect&module=settings
```

Класс:

```php
shopRedirectPluginSettingsAction
```

Файл:

```text
wa-apps/shop/plugins/redirect/lib/actions/settings/shopRedirectPluginSettings.action.php
```

Шаблон:

```text
wa-apps/shop/plugins/redirect/templates/actions/settings/Settings.html
```

---

## 10. Как ищется шаблон

### 10.1. `waViewAction`

Если `setTemplate()` не вызван, `waViewAction` строит template name из имени класса.

Класс:

```php
myappOrdersViewAction
```

После удаления app-prefix и суффикса `Action` остаётся:

```text
OrdersView
```

Дальше template path определяется как:

```text
templates/actions/orders/OrdersView.html
```

где `orders` — lower-case prefix имени template.

### 10.2. `waViewActions`

Для `waViewActions` шаблон строится из имени класса и action-метода.

Класс:

```php
myappOrdersActions
```

Action:

```text
state
```

Шаблон:

```text
templates/actions/orders/OrdersState.html
```

Для `defaultAction()`:

```text
templates/actions/orders/Orders.html
```

### 10.3. `setTemplate()`

Если нужен нестандартный шаблон:

```php
$this->setTemplate('orders/Custom.html', true);
```

`true` означает путь относительно template-директории приложения или плагина.

### 10.4. `setThemeTemplate()`

Для frontend theme templates:

```php
$this->setThemeTemplate('product.html');
```

Тогда шаблон берётся из текущей темы, а не из `templates/actions/`.

Типичный frontend pattern:

```text
Action: setThemeTemplate('product.html')
Layout: setThemeTemplate('index.html')
```

---

## 11. Как выбрать правильный класс

### 11.1. Выбрать `waViewAction`, если

- это одна HTML-страница;
- это один HTML-фрагмент;
- есть один основной шаблон;
- не нужно собирать несколько блоков;
- не нужен собственный controller-level orchestration.

Плюсы:

- простой класс;
- стандартный template auto-discovery;
- хорошо соответствует Webasyst action pattern.

Минусы:

- плохо подходит для сложной композиции страницы;
- легко перегрузить бизнес-логикой, если не выносить model/service.

### 11.2. Выбрать `waController`, если

- HTML-шаблон не нужен;
- нужно выполнить command;
- нужен redirect;
- нужен файл/download;
- endpoint сам управляет response.

Плюсы:

- минимум view-логики;
- чистый command-style код;
- удобно для save/delete operations.

Минусы:

- нельзя автоматически рендерить шаблон;
- при HTML-ответе придётся делать лишнюю ручную работу.

### 11.3. Выбрать `waViewController`, если

- нужна layout-композиция;
- страница состоит из нескольких action-блоков;
- нужно оркестрировать sidebar/toolbar/content/footer;
- есть общий layout для backend-раздела.

Плюсы:

- явная композиция страницы;
- хорошо подходит для backend UI;
- блоки можно переиспользовать.

Минусы:

- избыточен для простой страницы;
- легко превратить в монолитный coordinator.

### 11.4. Выбрать `waViewActions`, если

- есть несколько близких HTML-actions в одном модуле;
- они используют общий layout/view state;
- логика небольшая.

Плюсы:

- меньше файлов;
- удобно для компактных settings/modules.

Минусы:

- быстро превращается в God class;
- хуже читается при большом количестве методов;
- command и page logic легко смешиваются.

### 11.5. Выбрать app-specific base action/layout, если

- несколько frontend actions повторяют layout/theme/events/globals;
- нужно единообразное поведение 404/meta/canonical;
- это уже видно в официальном приложении или текущем проекте.

Плюсы:

- меньше дублирования;
- единая frontend shell-логика.

Минусы:

- лишний слой, если повторения нет;
- может скрыть важную page-specific логику.

---

## 12. Типовые ошибки

### Ошибка 1. Назвать класс не по dispatch params

Route:

```php
'orders/<id:\d+>/?' => 'orders/view',
```

Ожидаемые варианты:

```text
myappOrdersViewController
myappOrdersViewAction
myappOrdersActions::viewAction()
```

Ошибочно:

```text
myappOrderViewAction
myappBackendOrdersViewAction
myappOrdersController::viewAction()
```

### Ошибка 2. Положить файл не в директорию module

Route:

```php
'orders/view/' => 'orders/view',
```

Правильно:

```text
lib/actions/orders/myappOrdersView.action.php
```

Неправильно:

```text
lib/actions/backend/myappOrdersView.action.php
lib/actions/order/myappOrdersView.action.php
lib/classes/myappOrdersView.action.php
```

### Ошибка 3. Использовать `waViewAction` для POST delete/save без прав и CSRF

Неправильно:

```php
class myappOrdersDeleteAction extends waViewAction
{
    public function execute()
    {
        (new myappOrderModel())->deleteById(waRequest::param('id'));
    }
}
```

Правильно:

- использовать `waController` или `waJsonController`;
- проверять method;
- проверять права;
- учитывать CSRF;
- нормализовать ID.

### Ошибка 4. Писать бизнес-логику в Smarty

Неправильно:

```smarty
{foreach $orders as $order}
    {if $order.status == 'new' && $order.total > 1000 && ...}
```

Если условие становится бизнес-правилом, его нужно подготовить в action/model/service, а в шаблон передать готовое поле.

### Ошибка 5. Собирать URL вручную

Неправильно:

```php
$url = '/webasyst/myapp/?module=orders&action=view&id=' . $id;
```

Правильно для backend app URL:

```php
$url = wa()->getAppUrl('myapp') . 'orders/' . $id . '/';
```

Правильно для frontend route URL:

```php
$url = wa()->getRouteUrl('myapp/frontend/view', array('id' => $id), true);
```

### Ошибка 6. Читать route placeholder через GET

Неправильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

Правильно:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

### Ошибка 7. Создавать `waViewController` без причины

Если страница состоит из одного шаблона, controller вида:

```php
class myappOrdersController extends waViewController
{
    public function execute()
    {
        $this->executeAction(new myappOrdersAction());
    }
}
```

обычно избыточен. Достаточно `myappOrdersAction`.

### Ошибка 8. Делать один `Actions`-класс на весь backend

Неправильно:

```text
myappBackendActions
├── ordersAction()
├── orderDeleteAction()
├── settingsAction()
├── reportsAction()
├── exportAction()
└── importAction()
```

Правильно делить по module/ответственности:

```text
myappOrdersAction
myappOrdersDeleteController
myappSettingsActions
myappReportsAction
```

### Ошибка 9. Переопределять front controller без необходимости

`front_controller` в `factories.php` нужен редко. Для обычной задачи достаточно route + action/controller.

Переопределение `waFrontController` усложняет dispatch и может сломать стандартный поиск классов.

### Ошибка 10. Игнорировать layout в frontend

Frontend action может вернуть HTML, но без layout страница потеряет `index.html`, `$wa->head()`, CSS/JS, hooks и общую структуру темы.

Для theme page обычно нужно:

```php
$this->setLayout(new myappFrontendLayout());
$this->setThemeTemplate('page.html');
```

или наследоваться от app-specific frontend action, который делает это централизованно.

---

## 13. Чеклист разработчика

Перед commit нового controller/action проверить:

### Dispatch

- [ ] Понятно, какой route или GET-параметры ведут в этот класс.
- [ ] Определены итоговые `module` и `action`.
- [ ] Имя класса соответствует `{app}{Module}{Action}{Controller|Action}` или `{app}{Module}Actions`.
- [ ] Файл лежит в `lib/actions/{module}/`.
- [ ] Для plugin action учтён segment `{Plugin}Plugin` в имени класса.
- [ ] Не создан лишний `waViewController`, если достаточно `waViewAction`.

### Request

- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] Query string читается через `waRequest::get()`.
- [ ] POST читается через `waRequest::post()`.
- [ ] Все входные данные типизированы.
- [ ] Для `new|id` route предусмотрена отдельная логика.

### View/template

- [ ] Для `waViewAction` существует ожидаемый шаблон или вызван `setTemplate()`/`setThemeTemplate()`.
- [ ] Для `waViewActions` существует шаблон под конкретный method action.
- [ ] Шаблон лежит в `templates/actions/{module}/` или в theme directory.
- [ ] В Smarty нет PHP-кода.
- [ ] В шаблоне нет тяжёлой бизнес-логики.

### Layout

- [ ] Frontend page использует нужный theme layout.
- [ ] Backend page использует нужный backend layout, если он нужен.
- [ ] Blocks передаются через `executeAction()` только если страница реально составная.
- [ ] Layout не загружает сущность страницы вместо action.

### Security

- [ ] Save/delete проверяет права.
- [ ] POST защищён CSRF, если app config это требует.
- [ ] Нет доверия к request без проверки.
- [ ] Нет SQL в action, если его можно вынести в model/service.
- [ ] Redirect безопасен и не строится из непроверенного внешнего URL.

### URL

- [ ] Нет хардкода `/webasyst/`.
- [ ] Backend URL строится через `wa()->getAppUrl()` или `{$wa_app_url}`.
- [ ] Frontend URL строится через `wa()->getRouteUrl()`.
- [ ] Canonical/meta задаются через `waResponse`, если нужны.

---

## 14. Чеклист ИИ-агента

Перед ответом по controller/action ИИ-агент обязан:

1. Определить, это frontend, backend, plugin, JSON, long action или CLI.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить `frontend`, `rights`, `csrf`, `ui`, `themes`, `pages`, `plugins`.
4. Открыть `routing.php` или `routing.backend.php`.
5. Определить итоговые `module/action/plugin`.
6. Открыть существующую директорию `lib/actions/{module}/`.
7. Найти похожий `Controller`, `Action` или `Actions` в этом app/plugin.
8. Открыть соответствующий шаблон в `templates/actions/{module}/` или theme template.
9. Проверить, используется ли layout и где он лежит.
10. Проверить model/service/helper, который уже решает нужную бизнес-задачу.
11. Проверить rights и CSRF для изменяющих операций.
12. Только после этого писать код.

ИИ-агенту запрещено:

- придумывать имена классов без route/module/action;
- писать action без указания файла и шаблона;
- использовать Laravel/Symfony-style controller conventions;
- читать route params через GET;
- писать POST delete/save как обычную HTML-страницу без security checks;
- хардкодить backend URL;
- создавать кастомный front controller без крайней необходимости;
- писать PHP в Smarty;
- переносить бизнес-логику в шаблон;
- утверждать совместимость с проектом без просмотра текущих файлов.

---

## 15. Мини-сводка

Controllers/actions в Webasyst — это не абстрактный MVC-слой, а строгий dispatch-механизм, завязанный на `module`, `action`, `plugin`, имя класса, файл и шаблон.

Правильная цепочка для одиночного HTML action:

```text
route: 'orders/<id:\d+>/?' => 'orders/view'
→ waRequest::param('module') = 'orders'
→ waRequest::param('action') = 'view'
→ waFrontController::getController()
→ myappOrdersViewController? нет
→ myappOrdersViewAction? да
→ waDefaultViewController::setAction('myappOrdersViewAction')
→ waDefaultViewController::execute()
→ executeAction(new myappOrdersViewAction(), 'content')
→ myappOrdersViewAction::display()
→ preExecute()
→ execute()
→ afterExecute()
→ templates/actions/orders/OrdersView.html
→ HTML response
```

Правильная цепочка для command controller:

```text
route: 'orders/<id:\d+>/delete/?' => 'orders/delete'
→ myappOrdersDeleteController
→ waController::run()
→ preExecute()
→ execute()
→ afterExecute()
→ redirect / JSON / file / custom response
```

Главное практическое правило:

```text
один HTML-сценарий → waViewAction
команда без HTML → waController / waJsonController
составная страница → waViewController + executeAction()
несколько близких HTML-actions → waViewActions
```

Перед написанием любого кода нужно сначала определить route, затем `module/action`, затем имя класса, путь файла и шаблон. Иначе Webasyst просто не найдёт класс или найдёт не тот сценарий.
