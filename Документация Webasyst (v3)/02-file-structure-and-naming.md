# 02. Структура файлов и именование в Webasyst

**Статус:** опубликован v3
**Язык:** русский  
**Назначение:** объяснить, как Webasyst находит PHP-классы, templates, модели, plugins и themes через структуру каталогов и соглашения об именовании.

---

## 1. Назначение механизма

В Webasyst структура файлов и именование — это часть runtime-механизма, а не только стиль проекта.

От имени файла и класса зависит:

- попадёт ли класс в app autoload map;
- найдёт ли `waFrontController` нужный controller/action/actions-класс;
- найдёт ли `waViewAction` или `waViewActions` HTML-шаблон;
- будет ли модель связана с правильной таблицей;
- сможет ли plugin загрузить свои actions, models, helpers и templates;
- не возникнет ли конфликт имён между приложениями, плагинами и системными классами.

Ключевое правило:

```text
app_id/plugin_id/module/action/table/template должны быть согласованы между собой
```

Нельзя проектировать route отдельно от:

```text
имени класса
пути файла
имени шаблона
имени таблицы
app/plugin prefix
```

Если нарушить naming, ошибка обычно проявится не там, где был создан файл, а позже:

- autoload не найдёт класс;
- `waFrontController` вернёт 404;
- `waViewAction` не найдёт template;
- `waModel` будет работать не с той таблицей;
- plugin action не запустится через стандартный dispatch.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-config/SystemConfig.class.php` | Подключает `waAutoload` и регистрирует autoload при старте. |
| `wa-system/autoload/waAutoload.class.php` | Загружает системные и app/plugin/widget/API классы по autoload map. |
| `wa-system/config/waAppConfig.class.php` | Инициализирует app config, сканирует `lib/`, plugins, widgets, API и добавляет классы в autoload. |
| `wa-system/controller/waFrontController.class.php` | Строит имена dispatch-классов из `app prefix + plugin + module + action + suffix`. |
| `wa-system/controller/waViewAction.class.php` | Определяет template для одиночного action по имени класса или через `setTemplate()`. |
| `wa-system/controller/waViewActions.class.php` | Определяет template для multi-action controller по имени класса и action-методу. |
| `wa-system/routing/waRouting.class.php` | Превращает route rules в `module`, `action`, placeholders и route params. |
| `wa-system/model/waModel.class.php` | Базовый DB-layer; ожидает корректные `$table` и model naming. |

### 2.2. Файлы приложения

Базовый app skeleton:

```text
wa-apps/{app_id}/
  lib/
    config/
      app.php
      routing.php
      routing.backend.php
      db.php
      factories.php
      rights.php
      settings.php
    actions/
      {module}/
        {app_id}{Module}{Action}.action.php
        {app_id}{Module}{Action}.controller.php
        {app_id}{Module}.actions.php
    layouts/
      {app_id}{Name}.layout.php
    models/
      {app_id}{Entity}.model.php
    classes/
      {app_id}{Name}.class.php
  templates/
    actions/
      {module}/
        {Module}{Action}.html
  themes/
    {theme_id}/
      theme.xml
      index.html
      *.html
  plugins/
    {plugin_id}/
      lib/
      templates/
  locale/
  img/
  css/
  js/
```

Минимально обязательные для приложения:

```text
wa-apps/{app_id}/lib/config/app.php
```

Для frontend:

```text
wa-apps/{app_id}/lib/config/routing.php
wa-apps/{app_id}/lib/actions/frontend/{app_id}Frontend.action.php
```

Для backend:

```text
wa-apps/{app_id}/lib/actions/backend/{app_id}Backend.action.php
```

Или современный backend routing:

```text
wa-apps/{app_id}/lib/config/routing.backend.php
wa-apps/{app_id}/lib/actions/{module}/...
```

### 2.3. Файлы plugin

Plugin находится внутри приложения:

```text
wa-apps/{app_id}/plugins/{plugin_id}/
  lib/
    config/
      plugin.php
      settings.php
      routing.php
      db.php
    actions/
      {module}/
        {app_id}{Plugin}Plugin{Module}{Action}.action.php
    models/
      {app_id}{Plugin}Plugin{Name}.model.php
    classes/
      {app_id}{Plugin}Plugin{Name}.class.php
  templates/
    actions/
      {module}/
        {Module}{Action}.html
  img/
  css/
  js/
  locale/
```

Plugin class prefix:

```text
{app_id}{Plugin}Plugin...
```

Пример:

```text
shopRedirectPluginSettingsAction
shopRedirectPluginSettings.action.php
```

### 2.4. Файлы theme

Theme находится внутри app, если приложение поддерживает темы:

```text
wa-apps/{app_id}/themes/{theme_id}/
  theme.xml
  index.html
  page.html
  error.html
  *.html
  css/
  js/
  img/
```

Theme templates не загружаются через `waAutoload`. Они выбираются через route params, `waTheme`, `setThemeTemplate()` и view layer.

---

## 3. Системная цепочка выполнения

### 3.1. Как class попадает в autoload

При инициализации приложения `waAppConfig::init()`:

```text
wa($app_id, true)
→ SystemConfig::getAppConfig($app_id)
→ waAppConfig::__construct()
→ waAppConfig::init()
→ include wa-apps/{app_id}/lib/config/app.php
→ waAppConfig::getClasses()
→ waAutoload::add($classes)
```

`waAppConfig::getClasses()` сканирует:

```text
wa-apps/{app_id}/lib/
wa-apps/{app_id}/plugins/{plugin_id}/lib/
wa-apps/{app_id}/widgets/{widget_id}/lib/
wa-apps/{app_id}/api/v{n}/
```

И строит map:

```text
class name → relative file path
```

Сканирование идёт по `.php` файлам внутри `lib/`, кроме служебных исключений вроде `lib/config/data/`.

### 3.2. Как имя файла превращается в имя класса

`waAutoload::getClassByFilename($filename, $namespace)` использует расширения имени файла.

Базовые варианты:

| Файл | Класс |
|---|---|
| `myappOrder.class.php` | `myappOrder` |
| `myappOrder.model.php` | `myappOrderModel` |
| `myappOrdersList.action.php` | `myappOrdersListAction` |
| `myappOrdersDelete.controller.php` | `myappOrdersDeleteController` |
| `myappOrders.actions.php` | `myappOrdersActions` |
| `myappBackend.layout.php` | `myappBackendLayout` |
| `some.handler.php` | `{app_id}SomeHandler` по handler-правилу. |

Механика важна: Webasyst не читает содержимое каждого файла заранее, чтобы понять, какой класс внутри. Он выводит имя класса из имени файла и затем проверяет, что этот класс действительно есть.

### 3.3. Как dispatch ищет controller/action

После routing `waFrontController` имеет:

```text
plugin
module
action
```

Дальше он строит имена классов из app prefix.

Для обычного приложения:

```text
{app_prefix}{Module}{Action}Controller
{app_prefix}{Module}{Action}Action
{app_prefix}{Module}Actions
```

Для plugin:

```text
{app_prefix}{Plugin}Plugin{Module}{Action}Controller
{app_prefix}{Plugin}Plugin{Module}{Action}Action
{app_prefix}{Plugin}Plugin{Module}Actions
```

В текущем ядре порядок поиска такой:

1. `Controller`
2. `Action`
3. `Actions`

Пример:

```text
app_id = shop
module = prod
action = list
```

Проверяемые классы:

```text
shopProdListController
shopProdListAction
shopProdActions
```

Если файл называется неверно, autoload не добавит нужный класс в map, `class_exists()` вернёт false, и dispatch пойдёт дальше или отдаст 404.

### 3.4. Как action ищет template

Для одиночного `waViewAction`:

```php
class myappOrdersViewAction extends waViewAction
{
}
```

Класс даёт template name:

```text
myappOrdersViewAction
→ OrdersView
→ templates/actions/orders/OrdersView.html
```

Логика:

1. от имени класса отрезается app prefix;
2. отрезается суффикс `Action`;
3. по первому CamelCase-сегменту определяется module folder;
4. template ищется в `templates/actions/{module}/`.

Для `myappOrdersViewAction`:

```text
OrdersView
→ first segment: Orders
→ folder: orders
→ file: OrdersView.html
```

Для `waViewActions`:

```php
class myappOrdersActions extends waViewActions
{
    public function viewAction()
    {
    }
}
```

Шаблон:

```text
templates/actions/orders/OrdersView.html
```

Для `defaultAction()`:

```text
templates/actions/orders/OrdersDefault.html
```

Для одиночного default action:

```php
class myappOrdersAction extends waViewAction
{
}
```

Шаблон:

```text
templates/actions/orders/Orders.html
```

Если вызван `setTemplate()`, автопоиск не используется или используется в относительном режиме, в зависимости от второго параметра.

---

## 4. Ключевые классы и методы

### 4.1. `waAutoload`

Главные методы:

| Метод | Роль |
|---|---|
| `register()` | Регистрирует autoload через `spl_autoload_register()`. |
| `autoload($class)` | Подключает файл класса, если путь найден. |
| `get($class)` | Возвращает путь к системному или app/plugin классу. |
| `add($class, $path = null)` | Добавляет class map. |
| `getClassByFilename($filename, $namespace)` | Выводит имя класса из имени файла. |

Практическое следствие:

```text
файл может физически существовать, но не быть найденным, если его имя не соответствует имени класса
```

### 4.2. `waAppConfig`

Главные методы для структуры:

| Метод | Роль |
|---|---|
| `init()` | Загружает app config и добавляет классы в autoload. |
| `getClasses()` | Сканирует `lib/`, plugins, widgets, API и строит class map. |
| `getPHPFiles()` | Рекурсивно находит PHP-файлы. |
| `getClassByFilename()` | Делегирует построение имени класса в `waAutoload`. |
| `getRouting()` | Возвращает app routes. |
| `getRoutingPath()` | Выбирает `routing.php` или `routing.backend.php`, сначала из `wa-config/apps/{app_id}`, потом из app source. |
| `getPrefix()` | Возвращает app prefix: `app.php['prefix']` или `app_id`. |

Важно:

```text
app prefix по умолчанию равен app_id
```

Но если в `app.php` задан `prefix`, dispatch будет строить классы от него.

В обычных приложениях лучше не задавать `prefix` без необходимости: это усложняет поиск класса разработчиком и ИИ-агентом.

### 4.3. `waFrontController`

Главные методы:

| Метод | Роль |
|---|---|
| `dispatch()` | Запускает app dispatch. Для backend может предварительно применить `routing.backend.php`. |
| `getDispatchParams()` | Читает `plugin`, `module`, `action`, `widget` из GET и route params. |
| `isDispatchParamValid()` | Валидирует dispatch params. |
| `execute()` | Проверяет rights/plugin и запускает найденный controller. |
| `getController()` | Строит имена классов и ищет `Controller`, `Action`, `Actions`. |

Валидация dispatch params допускает только:

```text
^[a-z_][a-z0-9_]*$~i
```

Поэтому `module` и `action` не должны содержать:

```text
-
/
.
?
..
```

Route value может быть строкой `orders/view`, но после разбора это должны стать отдельные безопасные значения:

```text
module = orders
action = view
```

### 4.4. `waViewAction`

Главные методы:

| Метод | Роль |
|---|---|
| `execute()` | Основная точка прикладной логики action. |
| `display()` | Выполняет lifecycle и возвращает rendered template. |
| `getTemplate()` | Вычисляет template по имени класса, если template не задан явно. |
| `setTemplate($template, $is_relative = false)` | Задаёт custom template. |
| `setThemeTemplate($template)` | Задаёт theme template для frontend. |
| `getTemplateDir()` | Возвращает `templates/actions/`. |
| `getLegacyTemplateDir()` | Возвращает `templates/actions-legacy/`. |

### 4.5. `waViewActions`

Главные методы:

| Метод | Роль |
|---|---|
| `run($params = null)` | Выбирает action: если пусто, использует `default`. |
| `execute($action, $params = null)` | Вызывает `{action}Action()`. |
| `getTemplate()` | Определяет template для текущего action. |
| `setTemplate()` | Задаёт custom template. |
| `display()` | Рендерит template или layout. |

---

## 5. Параметры config/routing/request и naming

### 5.1. `app_id`

`app_id` — базовый namespace приложения.

Правила:

```text
нижний регистр
латинские буквы
цифры
подчеркивание
без дефисов
без пробелов
```

Примеры:

```text
blog
shop
site
crm
myapp
my_app
```

Неудачно:

```text
MyApp
my-app
my app
my.app
```

Причина: `app_id` участвует в путях, class prefix, table prefix, locale domain, app settings и URL.

### 5.2. `plugin_id`

`plugin_id` — namespace plugin внутри app.

Для PHP-классов plugin id превращается в CamelCase-сегмент перед словом `Plugin`.

Пример:

```text
app_id = shop
plugin_id = redirect
module = settings
```

Класс:

```text
shopRedirectPluginSettingsAction
```

Файл:

```text
wa-apps/shop/plugins/redirect/lib/actions/settings/shopRedirectPluginSettings.action.php
```

Для сложных plugin id нужно сверять существующий стиль и то, как Webasyst преобразует имя в class prefix. Чтобы избежать ошибок, plugin id лучше делать простым:

```text
redirect
importexport
migrate
```

### 5.3. `module`

`module` — первый уровень прикладного dispatch.

Пример:

```text
module = orders
```

Файлы:

```text
wa-apps/myapp/lib/actions/orders/
wa-apps/myapp/templates/actions/orders/
```

Классы:

```text
myappOrdersAction
myappOrdersViewAction
myappOrdersActions
myappOrdersViewController
```

Template prefix:

```text
Orders
```

### 5.4. `action`

`action` — второй уровень прикладного dispatch.

Пример:

```text
module = orders
action = view
```

Ожидаемые классы:

```text
myappOrdersViewController
myappOrdersViewAction
myappOrdersActions::viewAction()
```

Ожидаемый template:

```text
templates/actions/orders/OrdersView.html
```

### 5.5. `routing.php` и `routing.backend.php`

Route должен приводить URL к тем `module/action`, для которых существуют классы и templates.

Пример:

```php
return array(
    'orders/?'              => 'orders/list',
    'orders/<id:\d+>/?'     => 'orders/view',
    'orders/<id:\d+>/edit/' => 'orders/edit',
);
```

Значит должны существовать:

```text
myappOrdersListAction
myappOrdersViewAction
myappOrdersEditAction
```

И templates:

```text
templates/actions/orders/OrdersList.html
templates/actions/orders/OrdersView.html
templates/actions/orders/OrdersEdit.html
```

Если route возвращает `orders/view`, а создан класс `myappOrderViewAction`, Webasyst его не найдёт: `Order` и `Orders` — разные CamelCase-сегменты.

### 5.6. `db.php` и `$table`

Для app tables:

```text
{app_id}_{entity}
```

Примеры:

```text
blog_post
shop_product
crm_deal
```

Для plugin tables:

```text
{app_id}_{plugin_id}_{name}
```

Примеры:

```text
shop_redirect_rule
blog_social_item
```

Model class:

```php
class myappOrderModel extends waModel
{
    protected $table = 'myapp_order';
}
```

Файл:

```text
wa-apps/myapp/lib/models/myappOrder.model.php
```

---

## 6. Паттерн официального Webasyst-кода

### 6.1. Blog: компактный frontend app pattern

`blog` показывает стандартную связку:

```text
wa-apps/blog/lib/config/app.php
wa-apps/blog/lib/config/routing.php
wa-apps/blog/lib/actions/frontend/blogFrontend.action.php
wa-apps/blog/lib/actions/frontend/blogFrontendPost.action.php
wa-apps/blog/lib/layouts/blogFrontend.layout.php
wa-apps/blog/lib/models/blogPost.model.php
wa-apps/blog/themes/default/*.html
```

Примеры naming:

| Назначение | Класс | Файл |
|---|---|---|
| Frontend stream | `blogFrontendAction` | `lib/actions/frontend/blogFrontend.action.php` |
| Frontend post | `blogFrontendPostAction` | `lib/actions/frontend/blogFrontendPost.action.php` |
| Layout | `blogFrontendLayout` | `lib/layouts/blogFrontend.layout.php` |
| Model | `blogPostModel` | `lib/models/blogPost.model.php` |

`blogPostModel` использует таблицу:

```php
protected $table = 'blog_post';
```

Это соответствует правилу `app_id + '_' + entity`.

### 6.2. Site: page/theme pattern

`site` использует:

```text
wa-apps/site/lib/config/app.php
wa-apps/site/lib/config/routing.php
wa-apps/site/lib/actions/frontend/siteFrontend.action.php
```

`siteFrontendAction` наследуется от `waPageAction`, то есть использует системный page action pattern, но имя класса и путь файла всё равно следуют app naming:

```text
siteFrontendAction
→ wa-apps/site/lib/actions/frontend/siteFrontend.action.php
```

### 6.3. Shop: большое app с разными route families

`shop` показывает крупный naming pattern:

```text
wa-apps/shop/lib/config/routing.php
wa-apps/shop/lib/config/routing.backend.php
wa-apps/shop/lib/actions/frontend/shopFrontend.action.php
wa-apps/shop/lib/layouts/shopFrontend.layout.php
wa-apps/shop/lib/models/shopProduct.model.php
wa-apps/shop/plugins/{plugin_id}/...
```

В `routing.backend.php` есть route:

```php
'products/?' => 'prod/list'
```

Он требует один из классов:

```text
shopProdListController
shopProdListAction
shopProdActions::listAction()
```

И для action template:

```text
templates/actions/prod/ProdList.html
```

### 6.4. Plugin pattern

Для plugin `redirect` приложения `shop` config лежит здесь:

```text
wa-apps/shop/plugins/redirect/lib/config/plugin.php
```

Если plugin имеет backend settings action, стандартный класс будет:

```text
shopRedirectPluginSettingsAction
```

Стандартный файл:

```text
wa-apps/shop/plugins/redirect/lib/actions/settings/shopRedirectPluginSettings.action.php
```

Стандартный template:

```text
wa-apps/shop/plugins/redirect/templates/actions/settings/Settings.html
```

---

## 7. Минимальная реализация

Задача: создать минимальный backend-раздел заказов в приложении `myapp`.

### 7.1. App config

Файл:

```text
wa-apps/myapp/lib/config/app.php
```

```php
<?php

return array(
    'name'    => 'My app',
    'icon'    => 'img/myapp.svg',
    'version' => '1.0.0',
    'vendor'  => 'myvendor',
    'rights'  => true,
    'csrf'    => true,
    'ui'      => '2.0',
);
```

### 7.2. Backend route

Файл:

```text
wa-apps/myapp/lib/config/routing.backend.php
```

```php
<?php

return array(
    'orders/?'          => 'orders/list',
    'orders/<id:\d+>/?' => 'orders/view',
    ''                  => 'backend',
);
```

Dispatch result:

| URL внутри app | module | action |
|---|---|---|
| `orders/` | `orders` | `list` |
| `orders/123/` | `orders` | `view` |

### 7.3. List action

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersList.action.php
```

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

Template:

```text
wa-apps/myapp/templates/actions/orders/OrdersList.html
```

```smarty
<h1>Orders</h1>

{if !empty($orders)}
    <ul>
        {foreach $orders as $order}
            <li>
                <a href="{$wa_app_url}orders/{$order.id}/">
                    Order #{$order.id|escape}
                </a>
            </li>
        {/foreach}
    </ul>
{else}
    <p>No orders.</p>
{/if}
```

### 7.4. View action

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersView.action.php
```

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

Template:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

```smarty
<h1>Order #{$order.id|escape}</h1>

<p>Status: {$order.status|escape}</p>
```

### 7.5. Model

Файл:

```text
wa-apps/myapp/lib/models/myappOrder.model.php
```

```php
<?php

class myappOrderModel extends waModel
{
    protected $table = 'myapp_order';
}
```

DB config:

```text
wa-apps/myapp/lib/config/db.php
```

```php
<?php

return array(
    'myapp_order' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'status' => array('varchar', 32, 'null' => 0, 'default' => 'new'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
```

---

## 8. Расширенная реализация

### 8.1. Когда нужен `Controller`

Если route должен выполнить команду без HTML:

```text
orders/<id:\d+>/delete/ → orders/delete
```

Класс:

```text
myappOrdersDeleteController
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersDelete.controller.php
```

```php
<?php

class myappOrdersDeleteController extends waController
{
    public function execute()
    {
        if (!$this->getRights('delete')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

        if ($id <= 0) {
            throw new waException('Order not found', 404);
        }

        $model = new myappOrderModel();
        $model->deleteById($id);

        $this->redirect(wa()->getAppUrl('myapp') . 'orders/');
    }
}
```

Template не нужен.

### 8.2. Когда нужен `Actions`

Если есть набор близких простых операций одного module:

```text
orders/default
orders/state
orders/delete
```

Класс:

```text
myappOrdersActions
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

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
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);
        $this->view->assign('id', $id);
    }
}
```

Templates:

```text
templates/actions/orders/OrdersDefault.html
templates/actions/orders/OrdersState.html
```

Но если logic растёт, лучше перейти к отдельным `Action`/`Controller`, а не превращать `Actions` в God class.

### 8.3. Когда нужен custom class в `lib/classes`

Если логика не является controller/action/model:

```text
wa-apps/myapp/lib/classes/myappOrderCalculator.class.php
```

```php
<?php

class myappOrderCalculator
{
    public function getTotal($items)
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }
}
```

Название файла:

```text
myappOrderCalculator.class.php
```

Класс:

```text
myappOrderCalculator
```

### 8.4. Когда нужен plugin class

Plugin class обычно находится здесь:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/{app_id}{Plugin}Plugin.class.php
```

Пример:

```text
wa-apps/shop/plugins/redirect/lib/shopRedirect.plugin.php
```

или современный/проектный стиль может отличаться. Перед созданием plugin class нужно открыть существующие plugins этого app и повторить принятый naming.

Для actions plugin стандартный dispatch naming остаётся:

```text
{app_id}{Plugin}Plugin{Module}{Action}Action
```

---

## 9. Типовые ошибки

### Ошибка 1. Файл не соответствует классу

Неправильно:

```text
class myappOrdersViewAction
file myappOrderView.action.php
```

Правильно:

```text
class myappOrdersViewAction
file myappOrdersView.action.php
```

### Ошибка 2. Route и класс используют разные module names

Route:

```php
'orders/<id:\d+>/' => 'orders/view'
```

Неправильный класс:

```text
myappOrderViewAction
```

Правильный класс:

```text
myappOrdersViewAction
```

### Ошибка 3. Класс создан вне `lib/`

Неправильно:

```text
wa-apps/myapp/actions/orders/myappOrdersView.action.php
```

Правильно:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersView.action.php
```

Autoload сканирует `lib/`, а не произвольные директории приложения.

### Ошибка 4. Template лежит не в той папке

Класс:

```text
myappOrdersViewAction
```

Неправильно:

```text
templates/actions/order/OrdersView.html
templates/actions/orders/View.html
templates/actions/orders/ordersView.html
```

Правильно:

```text
templates/actions/orders/OrdersView.html
```

### Ошибка 5. Путать backend и frontend module

Frontend route:

```php
'product/<url>/' => 'frontend/product'
```

Ожидаемый класс:

```text
myappFrontendProductAction
```

Файл:

```text
lib/actions/frontend/myappFrontendProduct.action.php
```

Не нужно создавать:

```text
myappProductFrontendAction
lib/actions/product/...
```

Если module в route — `frontend`, директория actions тоже `frontend`.

### Ошибка 6. Дефис в `module/action/plugin_id`

Неправильно:

```text
module = order-items
action = mass-delete
```

`waFrontController` валидирует dispatch params и не пропустит такие значения.

Правильно:

```text
module = orderItems
action = massDelete
```

Или проще:

```text
module = orderitems
action = massdelete
```

Но перед выбором style нужно смотреть существующий app.

### Ошибка 7. Таблица без app/plugin prefix

Неправильно:

```text
order
settings
items
```

Правильно для app:

```text
myapp_order
myapp_settings
myapp_item
```

Правильно для plugin:

```text
shop_myplugin_rule
shop_myplugin_item
```

### Ошибка 8. Plugin action без `Plugin` в имени класса

Неправильно:

```text
shopRedirectSettingsAction
```

Правильно:

```text
shopRedirectPluginSettingsAction
```

### Ошибка 9. Полагаться на старую документацию без проверки текущего ядра

Старая документация описывает порядок поиска классов иначе, чем текущий `waFrontController`.

Для v3 надо считать source of truth текущий код ядра:

```text
Controller → Action → Actions
```

И только потом сверяться с текстовой документацией.

### Ошибка 10. Создавать custom `prefix` без необходимости

Если в `app.php` задать:

```php
'prefix' => 'customPrefix'
```

то `waFrontController` будет искать:

```text
customPrefixOrdersViewAction
```

а не:

```text
myappOrdersViewAction
```

Это может быть оправдано в редких случаях, но почти всегда усложняет сопровождение.

---

## 10. Чеклист разработчика

Перед commit проверить:

### App/plugin identity

- [ ] `app_id` совпадает с директорией `wa-apps/{app_id}`.
- [ ] `app_id` написан в нижнем регистре, без дефисов и пробелов.
- [ ] Если это plugin, `plugin_id` совпадает с директорией `plugins/{plugin_id}`.
- [ ] Plugin classes содержат сегмент `{Plugin}Plugin`.
- [ ] В `app.php` нет custom `prefix`, если он не нужен осознанно.

### Autoload

- [ ] PHP-классы лежат внутри `lib/` приложения/plugin/widget/API.
- [ ] Имя файла соответствует имени класса.
- [ ] Для обычного класса используется `.class.php`.
- [ ] Для model используется `.model.php`.
- [ ] Для action используется `.action.php`.
- [ ] Для controller используется `.controller.php`.
- [ ] Для actions используется `.actions.php`.
- [ ] Для layout используется `.layout.php`.

### Routing/dispatch

- [ ] Route value даёт ожидаемые `module/action`.
- [ ] Для `module/action` существует подходящий `Controller`, `Action` или `Actions`.
- [ ] Путь класса соответствует module folder.
- [ ] `module` и `action` проходят regex dispatch validation.
- [ ] Placeholder names не конфликтуют с `module`, `action`, `plugin`, `url` без необходимости.

### Templates

- [ ] Для `waViewAction` есть template или явно вызван `setTemplate()`/`setThemeTemplate()`.
- [ ] Template лежит в `templates/actions/{module}/`.
- [ ] Template name соответствует `{Module}{Action}.html`.
- [ ] Для frontend theme action используется theme template только там, где это действительно frontend page.
- [ ] Legacy template используется только если приложение реально поддерживает legacy UI.

### Models/database

- [ ] Model class начинается с `app_id` или app+plugin prefix.
- [ ] Model file заканчивается на `.model.php`.
- [ ] `$table` задан явно, если имя таблицы не выводится безопасно.
- [ ] App table начинается с `{app_id}_`.
- [ ] Plugin table начинается с `{app_id}_{plugin_id}_`.
- [ ] `db.php` содержит ту же таблицу, что `$table` модели.

### Assets/global identifiers

- [ ] JS/CSS глобальные идентификаторы имеют app/plugin prefix или спрятаны в замыкание.
- [ ] Session keys имеют app/plugin prefix.
- [ ] Request params с глобальным эффектом имеют app/plugin prefix.

---

## 11. Чеклист ИИ-агента

Перед ответом по структуре/naming ИИ-агент обязан:

1. Определить контекст: app, plugin, theme, widget, backend, frontend, model, CLI или API.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить `app_id`, `prefix`, `frontend`, `plugins`, `themes`, `pages`, `rights`, `csrf`, `ui`.
4. Если задача про plugin — открыть `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php`.
5. Открыть relevant route file:
   - `lib/config/routing.php` для frontend;
   - `lib/config/routing.backend.php` для backend.
6. Определить итоговые `module/action/plugin`.
7. Открыть `lib/actions/{module}/` и найти похожие классы.
8. Открыть `templates/actions/{module}/` и проверить naming templates.
9. Для модели открыть `lib/models/` и `lib/config/db.php`.
10. Для layout открыть `lib/layouts/`.
11. Для frontend theme открыть `themes/{theme_id}/theme.xml` и нужные `.html` templates.
12. Сверить имя нового класса с `waFrontController::getController()` и `waAutoload::getClassByFilename()`.
13. Только после этого предлагать имя файла, имя класса и skeleton.

ИИ-агенту запрещено:

- придумывать class naming без проверки module/action;
- создавать файл вне `lib/` для PHP-класса;
- использовать дефисы в dispatch params;
- создавать template с произвольным именем без `setTemplate()`;
- игнорировать plugin prefix `Plugin`;
- создавать таблицы без app/plugin prefix;
- переносить Laravel/Symfony/WordPress naming в Webasyst;
- утверждать совместимость с проектом, если relevant files не открыты.

---

## 12. Мини-сводка

Структура и naming в Webasyst — это часть dispatch/autoload/template механизма.

Правильная связка для route:

```php
'orders/<id:\d+>/' => 'orders/view'
```

Должна давать согласованный набор:

```text
module = orders
action = view
class  = myappOrdersViewAction
file   = wa-apps/myapp/lib/actions/orders/myappOrdersView.action.php
tpl    = wa-apps/myapp/templates/actions/orders/OrdersView.html
model  = myappOrderModel
table  = myapp_order
```

Для plugin:

```text
app_id    = shop
plugin_id = redirect
module    = settings
class     = shopRedirectPluginSettingsAction
file      = wa-apps/shop/plugins/redirect/lib/actions/settings/shopRedirectPluginSettings.action.php
tpl       = wa-apps/shop/plugins/redirect/templates/actions/settings/Settings.html
```

Если один элемент этой цепочки назван иначе, Webasyst не сможет надёжно найти class/template/table стандартным способом.
