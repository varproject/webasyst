# 05. Backend routing в Webasyst

**Статус:** опубликован v3
**Язык:** русский  
**Назначение:** объяснить backend routing Webasyst как рабочий механизм, а не как поверхностное описание URL.

---

## 1. Назначение механизма

Backend routing отвечает за то, как HTTP-запрос внутри бекенда Webasyst превращается в конкретный PHP-класс приложения или плагина.

Backend-запрос проходит две разные стадии:

1. **Системная стадия** — Webasyst определяет, что запрос относится к бекенду и какому приложению он принадлежит.
2. **Прикладная стадия** — внутри выбранного приложения `waFrontController` определяет `module`, `action`, `plugin` и запускает соответствующий controller/action/actions-класс.

В старом стиле прикладная стадия управлялась GET-параметрами:

```text
/{backend_url}/{app_id}/?module=backend&action=default
/{backend_url}/{app_id}/?module=settings&action=save
```

В современном стиле приложение может описать человекочитаемые backend URL в файле:

```text
wa-apps/{app_id}/lib/config/routing.backend.php
```

Например:

```php
<?php

return array(
    'products/?'                       => 'prod/list',
    'products/<id:\d+|new>/general/?' => 'prod/general',
    ''                                => 'backend',
);
```

Такой файл не отменяет `module/action`; он задаёт правила, по которым URL превращается в `waRequest::param('module')` и `waRequest::param('action')`.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `index.php` | Точка входа HTTP-запросов. |
| `wa-system/waSystem.class.php` | Системный bootstrap и dispatch приложения. |
| `wa-system/routing/waRouting.class.php` | Матчит URL, заполняет `waRequest::param()`, строит URL. |
| `wa-system/controller/waFrontController.class.php` | Главный прикладной dispatch внутри приложения. |
| `wa-system/controller/waController.class.php` | Базовый lifecycle controller-классов. |
| `wa-system/controller/waViewController.class.php` | Controller с layout/blocks. |
| `wa-system/controller/waViewAction.class.php` | Action, который рендерит HTML-шаблон. |
| `wa-system/controller/waViewActions.class.php` | Multi-action controller: один класс, много методов `*Action()`. |
| `wa-system/controller/waDefaultViewController.class.php` | Обёртка для одиночного `waViewAction`. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | Объявляет приложение, права, UI, frontend/backend capabilities. |
| `wa-apps/{app_id}/lib/config/routing.backend.php` | Современные backend route rules. |
| `wa-apps/{app_id}/lib/actions/{module}/...` | Backend controller/action/actions classes. |
| `wa-apps/{app_id}/templates/actions/{module}/...` | Шаблоны `waViewAction`/`waViewActions`. |
| `wa-apps/{app_id}/lib/classes/...` | Общие классы приложения, включая кастомный front controller при необходимости. |
| `wa-apps/{app_id}/lib/config/factories.php` | Редкое место для переопределения `front_controller` или `default_controller`. |

### 2.3. Файлы плагина

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Конфиг plugin, handlers, version, rights. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/...` | Backend actions plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/...` | Шаблоны backend actions plugin-а. |

---

## 3. Адресное пространство backend

Backend всех приложений находится внутри системного backend URL:

```text
/{backend_url}/
```

`backend_url` не обязан быть `/webasyst/`. В целях безопасности он может быть изменён в системной конфигурации. Поэтому в коде и шаблонах нельзя жёстко писать `/webasyst/`.

Backend конкретного приложения находится внутри:

```text
/{backend_url}/{app_id}/
```

Примеры:

```text
/webasyst/shop/
/webasyst/blog/
/admin/shop/
/backend/crm/
```

Старый GET-вариант:

```text
/webasyst/shop/?module=orders&action=state
```

Современный route-вариант:

```text
/webasyst/shop/products/
/webasyst/shop/products/123/general/
/webasyst/site/map/overview/
```

Но в обоих случаях внутри `waFrontController` всё сводится к `module`, `action`, `plugin`, `widget`.

---

## 4. Старый GET-подход

Старый backend dispatch работает через GET-параметры:

```text
?module={module}&action={action}
```

Примеры:

```text
/webasyst/myapp/
/webasyst/myapp/?module=mail
/webasyst/myapp/?module=mail&action=test
```

Если `module` не указан, Webasyst использует:

```text
module = backend
```

Если `action` не указан, логика класса определяет default-сценарий:

- для одиночного `Action` имя класса будет без суффикса action;
- для `Actions` будет вызван метод `defaultAction()`.

Документация Webasyst описывает этот базовый подход как жёсткое backend-пространство, где URL приложения обрабатывается системным `waFrontController`, а `module` и `action` управляют поиском класса.

---

## 5. Современный `routing.backend.php`

Современный подход не заставляет пользователя и разработчика работать с query string. Приложение описывает backend routes в файле:

```text
wa-apps/{app_id}/lib/config/routing.backend.php
```

### 5.1. Пример из Shop-Script

```php
<?php

return array(
    'products/?'                               => 'prod/list',
    'products/categories/?'                    => 'prod/categories',
    'products/<id:\d+|new>/general/?'          => 'prod/general',
    'products/<id:\d+|new>/sku/?'              => 'prod/sku',
    'products/<id:\d+|new>/?'                  => 'prod/',

    // everything else uses old routing via ?module=&action=
    '' => 'backend',
);
```

Разбор:

| URL после `/webasyst/shop/` | Значение route | Итоговые params |
|---|---|---|
| `products/` | `prod/list` | `module=prod`, `action=list` |
| `products/categories/` | `prod/categories` | `module=prod`, `action=categories` |
| `products/123/general/` | `prod/general` + `id=123` | `module=prod`, `action=general`, `id=123` |
| `products/new/sku/` | `prod/sku` + `id=new` | `module=prod`, `action=sku`, `id=new` |
| empty URL | `backend` | `module=backend` |

### 5.2. Пример из Site

```php
<?php

return array(
    'map/overview/?'                 => 'map/overview',
    'settings/?'                     => 'configure/',
    'themes/?'                       => 'themes/',
    'plugins/?'                      => 'extensions/',
    'files/?'                        => 'filemanager/',
    'files/<files_path>/?'           => 'filemanager/',
    'htmleditor/page/<page_id:\d+>/' => 'htmleditor/',
    'editor/page/<page_id:\d+>/'     => 'editor/',
    ''                               => 'backend/',
);
```

Разбор:

| URL после `/webasyst/site/` | Значение route | Итоговые params |
|---|---|---|
| `map/overview/` | `map/overview` | `module=map`, `action=overview` |
| `settings/` | `configure/` | `module=configure`, `action` пустой/default |
| `files/path/to/file/` | `filemanager/` + `files_path=path/to/file` | `module=filemanager`, `files_path=...` |
| `editor/page/12/` | `editor/` + `page_id=12` | `module=editor`, `page_id=12` |
| empty URL | `backend/` | `module=backend` |

---

## 6. Когда `routing.backend.php` вообще включается

Ключевой момент: `routing.backend.php` не всегда участвует в dispatch.

В текущем `waFrontController` логика такая:

1. Определяется текущее приложение:

```php
$app = $this->system->getApp();
```

2. Определяется окружение:

```php
$env = $this->system->getEnv();
```

3. Строится путь:

```php
$backend_routing_path = wa()->getAppPath('lib/config/routing.backend.php', $app);
```

4. Если окружение `backend` и файл существует, фронт-контроллер проверяет GET:

```php
$module = waRequest::get($this->options['module']);
$plugin = waRequest::get('plugin', null, 'string');
```

5. Backend routing запускается только если **GET `module` пустой и GET `plugin` пустой**:

```php
if (empty($module) && empty($plugin)) {
    ...
    $routing->dispatch();
}
```

### Практическое следствие

Если запрос такой:

```text
/webasyst/shop/products/
```

и в GET нет `module`/`plugin`, то `routing.backend.php` будет применён.

Если запрос такой:

```text
/webasyst/shop/?module=prod&action=list
```

то `routing.backend.php` не нужен: GET уже явно задал dispatch.

Если запрос plugin-а:

```text
/webasyst/shop/?plugin=redirect&module=settings
```

то backend routing приложения не должен перехватывать этот запрос, потому что есть `plugin`.

---

## 7. Как `routing.backend.php` превращается в `waRequest::param()`

Backend routing использует тот же `waRouting`, что и frontend, но создаётся с искусственным правилом поселения:

```php
$routing = new waRouting($this->system, array(
    'default' => array(
        array(
            'is_backend_route' => true,
            'url' => wa()->getConfig()->systemOption('backend_url').'/'.$app.'/*',
            'app' => $app,
        ),
    ),
));
```

Дальше вызывается:

```php
$routing->dispatch();
```

`waRouting::dispatch()`:

1. Матчит системный backend URL приложения.
2. Определяет `root_url`.
3. Берёт внутренний app URL после `/{backend_url}/{app_id}/`.
4. Загружает routes приложения через `getAppRoutes()`.
5. Матчит `routing.backend.php`.
6. Записывает найденные параметры в `waRequest::param()`.

`waRouting::dispatchRoutes()` при совпадении route:

- записывает placeholder-параметры, например `id`, `page_id`, `files_path`;
- записывает все поля route, кроме `url`;
- в результате появляются `module`, `action` и дополнительные параметры.

---

## 8. Приоритет GET и route params

После backend routing `waFrontController` вызывает:

```php
list($plugin, $module, $action, $is_widget) = $this->getDispatchParams();
```

Внутри `getDispatchParams()` сначала читаются GET-параметры:

```php
$module = waRequest::get($this->options['module'], 'backend', 'string');
$action = waRequest::get($this->options['action'], null, 'string');
$is_widget = waRequest::get('widget', null, 'string');
$plugin = waRequest::get('plugin', null, 'string');
```

Затем route params переопределяют GET:

```php
$plugin = waRequest::param('plugin', $plugin, 'string');
$module = waRequest::param('module', $module, 'string');
$action = waRequest::param('action', $action, 'string');
```

И есть важная поправка:

```php
if (waRequest::param('module') && $action === null) {
    $action = waRequest::get($this->options['action'], null, 'string');
}
```

### Практическая интерпретация

| Источник | Что задаёт | Приоритет |
|---|---|---|
| GET `?module=...` | Старый dispatch | Базовый |
| GET `?action=...` | Старый dispatch | Базовый |
| GET `?plugin=...` | Plugin dispatch | Базовый, но блокирует backend routing |
| `routing.backend.php` | `waRequest::param('module')`, `action`, placeholders | Выше GET |
| `waRequest::param()` | Итоговые dispatch params | Используются перед поиском класса |

---

## 9. Формат rules в `routing.backend.php`

### 9.1. Строковый формат

```php
'products/?' => 'prod/list',
```

Означает:

```php
[
    'module' => 'prod',
    'action' => 'list',
]
```

Если action не указан:

```php
'settings/?' => 'configure/',
```

Означает:

```php
[
    'module' => 'configure',
]
```

Дальше будет использован default action logic.

### 9.2. Placeholder

```php
'products/<id:\d+|new>/general/?' => 'prod/general',
```

Означает:

```php
[
    'module' => 'prod',
    'action' => 'general',
    'id'     => '123' // или 'new'
]
```

Получить значение в action/controller:

```php
$id = waRequest::param('id');
```

Если значение должно быть целым:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

Но если route допускает `new`, нельзя сразу читать только как int. Нужно сначала разобрать логику:

```php
$id = waRequest::param('id', '', waRequest::TYPE_STRING_TRIM);

if ($id !== 'new') {
    $id = (int) $id;
}
```

### 9.3. Fallback

Shop-Script использует:

```php
'' => 'backend',
```

Site использует:

```php
'' => 'backend/',
```

Это означает: если путь пустой, открыть default backend module.

Для wildcard fallback можно использовать:

```php
'*' => 'backend',
```

Но wildcard нужно применять осторожно: он может скрыть 404 и направить неизвестные URL в default controller.

---

## 10. Валидация dispatch params

После определения `plugin`, `module`, `action` текущий `waFrontController` валидирует каждый параметр:

```php
protected function isDispatchParamValid($v)
{
    return preg_match('~^[a-z_][a-z0-9_]*$~i', $v);
}
```

Допустимо:

```text
backend
prod
prodList
settings
catalog_switch
```

Недопустимо:

```text
../file
prod/list
prod-list
module?action=x
```

### Важное следствие

`routing.backend.php` может содержать route value `'prod/list'`, но это не значит, что `module` будет равен `prod/list`.

`waRouting` разбирает строку на:

```text
module = prod
action = list
```

И уже эти отдельные значения проходят валидацию.

---

## 11. Порядок поиска классов

После определения params вызывается:

```php
list($controller, $params) = $this->getController($plugin, $module, $action, $default);
```

Для текущего ядра порядок поиска такой:

1. **Single Controller**
2. **Single Action**
3. **Controller Multi Actions**
4. Если разрешён default fallback — повтор без action
5. Иначе 404

### 11.1. Single Controller

Формат класса:

```text
{app_id}{Module}{Action}Controller
```

Если `action` пустой:

```text
{app_id}{Module}Controller
```

Пример:

```text
module = prod
action = list
app_id = shop
```

Потенциальный класс:

```php
shopProdListController
```

Файл по naming convention:

```text
wa-apps/shop/lib/actions/prod/shopProdList.controller.php
```

Метод:

```php
public function execute()
{
}
```

### 11.2. Single Action

Формат класса:

```text
{app_id}{Module}{Action}Action
```

Если `action` пустой:

```text
{app_id}{Module}Action
```

Пример:

```php
shopProdListAction
```

Файл:

```text
wa-apps/shop/lib/actions/prod/shopProdList.action.php
```

При найденном action-классе `waFrontController` не запускает его напрямую. Он берёт default controller:

```php
$controller = $this->system->getDefaultController();
$controller->setAction($class_name);
```

То есть фактически action будет выполнен через `waDefaultViewController`.

### 11.3. Multi Actions

Формат класса:

```text
{app_id}{Module}Actions
```

Пример:

```php
shopProdActions
```

Файл:

```text
wa-apps/shop/lib/actions/prod/shopProd.actions.php
```

Метод:

```php
public function listAction()
{
}
```

Если `action` пустой, будет вызван:

```php
public function defaultAction()
{
}
```

---

## 12. Как выбрать Controller, Action или Actions

### 12.1. Использовать `waViewAction`, если

Страница или фрагмент:

- имеет один сценарий;
- рендерит один шаблон;
- не собирает несколько блоков;
- не требует собственного dispatch внутри класса.

Пример:

```php
class myappOrdersAction extends waViewAction
{
    public function execute()
    {
        $model = new myappOrderModel();
        $orders = $model->order('id DESC')->fetchAll();

        $this->view->assign('orders', $orders);
    }
}
```

### 12.2. Использовать `waViewController`, если

Нужно:

- задать layout;
- собрать несколько `waViewAction` в разные блоки;
- выполнить orchestration страницы;
- вернуть составную страницу.

Пример:

```php
class myappOrdersController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());

        $this->executeAction(new myappOrdersSidebarAction(), 'sidebar');
        $this->executeAction(new myappOrdersListAction(), 'content');
    }
}
```

### 12.3. Использовать `waViewActions`, если

У модуля много простых операций и удобно держать их в одном классе:

```php
class myappOrdersActions extends waViewActions
{
    public function defaultAction()
    {
    }

    public function stateAction()
    {
    }

    public function deleteAction()
    {
    }
}
```

Но для больших backend-разделов с разной логикой лучше не превращать `Actions` в огромный God class.

### 12.4. Использовать обычный `waController`, если

Запрос не должен рендерить HTML:

- redirect;
- POST command;
- file response;
- служебная операция.

---

## 13. Где должны лежать файлы

### 13.1. Controller

Класс:

```php
class myappOrdersViewController extends waViewController
{
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersView.controller.php
```

### 13.2. Action

Класс:

```php
class myappOrdersViewAction extends waViewAction
{
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

### 13.3. Actions

Класс:

```php
class myappOrdersActions extends waViewActions
{
    public function viewAction()
    {
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

Шаблон для `viewAction()`:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

Для `defaultAction()`:

```text
wa-apps/myapp/templates/actions/orders/Orders.html
```

---

## 14. Как ищется шаблон

Для `waViewAction` шаблон определяется по имени класса, если не вызван `setTemplate()`.

Класс:

```php
myappOrdersViewAction
```

После удаления app-prefix и суффикса `Action` остаётся:

```text
OrdersView
```

Шаблон:

```text
templates/actions/orders/OrdersView.html
```

Документация Webasyst указывает, что для `waViewAction` основной код обработки запроса должен находиться в `execute()`, а HTML-шаблоны экшенов размещаются в `templates/actions/` с поддиректориями по имени модуля и именем вида `[Module][Action].html`.

Если используется `setTemplate()`:

```php
$this->setTemplate('orders/Custom.html', true);
```

то автопоиск по имени класса не используется.

---

## 15. Plugin backend routing

Backend plugin обычно вызывается старым GET-способом:

```text
/webasyst/shop/?plugin=redirect&module=settings
```

При наличии `plugin` в GET `waFrontController`:

1. Не запускает `routing.backend.php` приложения.
2. Проверяет наличие plugin config.
3. Проверяет plugin rights, если они включены.
4. Загружает plugin.
5. Добавляет plugin-сегмент в имя класса.

Формат класса plugin action:

```text
{app_id}{Plugin}Plugin{Module}{Action}Action
```

Пример для plugin `redirect` приложения `shop`:

```php
shopRedirectPluginSettingsAction
```

Путь:

```text
wa-apps/shop/plugins/redirect/lib/actions/settings/shopRedirectPluginSettings.action.php
```

Шаблон:

```text
wa-apps/shop/plugins/redirect/templates/actions/settings/Settings.html
```

Если нужен plugin backend route без GET `plugin=...`, нужно отдельно проектировать routing на уровне приложения. Но стандартный Webasyst-подход для backend plugin-интерфейса — query string с `plugin`, `module`, `action`.

---

## 16. Минимальная реализация: красивый backend URL

Задача:

```text
/webasyst/myapp/orders/
/webasyst/myapp/orders/123/
/webasyst/myapp/orders/123/edit/
```

### 16.1. routing.backend.php

```php
<?php

return array(
    'orders/?'                    => 'orders/list',
    'orders/<id:\d+>/?'           => 'orders/view',
    'orders/<id:\d+>/edit/?'      => 'orders/edit',
    ''                            => 'backend',
);
```

### 16.2. Action list

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

### 16.3. Action view

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

### 16.4. Action edit

```php
<?php

class myappOrdersEditAction extends waViewAction
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
wa-apps/myapp/lib/actions/orders/myappOrdersEdit.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersEdit.html
```

---

## 17. Минимальная реализация: command controller для POST

Задача:

```text
/webasyst/myapp/orders/123/delete/
```

Этот route не должен рендерить HTML. Он удаляет запись и делает redirect.

### 17.1. Route

```php
<?php

return array(
    'orders/<id:\d+>/delete/?' => 'orders/delete',
    'orders/?'                 => 'orders/list',
    ''                         => 'backend',
);
```

### 17.2. Controller

```php
<?php

class myappOrdersDeleteController extends waController
{
    public function execute()
    {
        $this->checkRights();

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

Здесь не нужен шаблон.

---

## 18. Backend URL generation

Не нужно собирать backend URL так:

```php
$url = '/webasyst/myapp/orders/';
```

Правильно:

```php
$url = wa()->getAppUrl('myapp') . 'orders/';
```

Или в шаблоне:

```smarty
<a href="{$wa_app_url}orders/">Orders</a>
```

Если нужно учитывать нестандартный backend URL:

```smarty
{$wa_backend_url}
```

Но внутри backend app обычно достаточно `wa()->getAppUrl($app_id)` или переменной `{$wa_app_url}`.

---

## 19. Расширенная реализация: controller + layout + action blocks

Для сложной backend-страницы route может вести в controller, который собирает несколько action-блоков.

### 19.1. Route

```php
<?php

return array(
    'orders/?' => 'orders',
    ''         => 'backend',
);
```

### 19.2. Controller

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

### 19.3. Toolbar action

```php
<?php

class myappOrdersToolbarAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('create_url', wa()->getAppUrl('myapp') . 'orders/new/');
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersToolbar.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersToolbar.html
```

### 19.4. List action

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

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersList.html
```

Такой подход нужен, когда controller действительно оркестрирует страницу. Если страница состоит из одного блока, достаточно одиночного `waViewAction`.

---

## 20. Типовые ошибки

### Ошибка 1. Считать `routing.backend.php` заменой `module/action`

Неправильно:

```text
routing.backend.php сам запускает класс
```

Правильно:

```text
routing.backend.php только выставляет route params. Класс запускает waFrontController по module/action.
```

### Ошибка 2. Читать placeholder из GET

Неправильно:

```php
$id = waRequest::get('id', 0, 'int');
```

Если `id` пришёл из route:

```text
orders/<id:\d+>/
```

правильно:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

### Ошибка 3. Хардкодить `/webasyst/`

Неправильно:

```php
$url = '/webasyst/myapp/orders/';
```

Правильно:

```php
$url = wa()->getAppUrl('myapp') . 'orders/';
```

### Ошибка 4. Делать красивый backend URL через `.htaccess`

Неправильно:

```text
писать свои rewrite rules для backend-раздела приложения
```

Правильно:

```text
использовать lib/config/routing.backend.php
```

### Ошибка 5. Создавать класс, который не соответствует dispatch params

Route:

```php
'orders/<id:\d+>/' => 'orders/view',
```

Ожидаемые классы:

```text
myappOrdersViewController
myappOrdersViewAction
myappOrdersActions::viewAction()
```

Ошибочные имена:

```text
myappOrderViewAction
myappBackendOrdersViewAction
myappOrdersController::viewAction()
```

### Ошибка 6. Путать `waRequest::param()` и `waRequest::get()`

- `waRequest::get()` — query string.
- `waRequest::post()` — POST.
- `waRequest::request()` — GET/POST.
- `waRequest::param()` — routing params и системные params.

Для backend routing placeholders использовать `param()`.

### Ошибка 7. Делать POST-изменения через `waViewAction` без CSRF/rights

Если action сохраняет/удаляет данные, он обязан:

- проверить права;
- проверить CSRF, если это не сделано системно;
- нормализовать входные данные;
- не доверять route/POST без проверки.

---

## 21. Чеклист разработчика

Перед commit backend route проверить:

### Routing

- [ ] Есть ли `wa-apps/{app_id}/lib/config/routing.backend.php`.
- [ ] Не конфликтует ли новый route с существующими.
- [ ] Есть ли fallback `'' => 'backend'` или осознанный другой fallback.
- [ ] Все placeholders имеют понятные имена.
- [ ] Regex в placeholders не слишком широкий.
- [ ] Для `new|id` предусмотрена отдельная обработка.

### Dispatch

- [ ] Понятно, какие `module` и `action` получатся из route.
- [ ] Класс назван по Webasyst naming.
- [ ] Файл лежит в правильной директории.
- [ ] Если это `Action`, есть шаблон или вызван `setTemplate()`.
- [ ] Если это `Controller`, шаблон не нужен, если controller не рендерит view напрямую.
- [ ] Если это `Actions`, метод `{action}Action()` существует.

### Request

- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] GET читается через `waRequest::get()`.
- [ ] POST читается через `waRequest::post()`.
- [ ] Все входные данные типизированы.

### Security

- [ ] Для backend save/delete проверены права.
- [ ] Для POST есть CSRF.
- [ ] Нет хардкода `/webasyst/`.
- [ ] Нет прямого вывода входных данных без escaping.
- [ ] Нет SQL в action, если его можно вынести в model/service.

### UI

- [ ] Ссылки строятся от `wa()->getAppUrl()` или `{$wa_app_url}`.
- [ ] Шаблон соответствует UI-версии приложения.
- [ ] Если приложение UI 2.0, не внедряется чужой UI-framework без необходимости.

---

## 22. Чеклист ИИ-агента

Перед ответом на задачу по backend routing ИИ-агент обязан:

1. Открыть `wa-apps/{app_id}/lib/config/app.php`.
2. Проверить `ui`, `csrf`, `rights`, `plugins`.
3. Открыть `wa-apps/{app_id}/lib/config/routing.backend.php`.
4. Если файла нет — учитывать старый GET-подход или предложить создать файл.
5. Определить, какие `module/action` должны получиться.
6. Открыть существующую директорию `lib/actions/{module}/`.
7. Найти похожий controller/action/actions в этом приложении.
8. Открыть соответствующий шаблон в `templates/actions/{module}/`.
9. Проверить, используется ли `waViewAction`, `waViewController`, `waViewActions` или `waController`.
10. Проверить, откуда должны читаться параметры: `param`, `get`, `post`.
11. Проверить права и CSRF для изменяющих операций.
12. Только после этого писать код.

ИИ-агенту запрещено:

- придумывать имена классов без проверки naming;
- писать route и не объяснять, какой класс он вызовет;
- использовать `/webasyst/` в URL;
- читать route placeholders через `waRequest::get()`;
- писать PHP-код в Smarty;
- предлагать Laravel/Symfony-style routing вместо Webasyst routing;
- создавать новый front controller без крайней необходимости.

---

## 23. Мини-сводка

Backend routing Webasyst — это не отдельный router поверх приложения, а слой, который преобразует красивый URL внутри backend-пространства приложения в стандартные `module/action/plugin` параметры.

Правильная цепочка:

```text
/{backend_url}/{app_id}/products/123/general/
→ waFrontController
→ routing.backend.php
→ waRouting::dispatch()
→ waRequest::param('module') = 'prod'
→ waRequest::param('action') = 'general'
→ waRequest::param('id') = '123'
→ waFrontController::getDispatchParams()
→ waFrontController::getController()
→ {app_id}ProdGeneralController
   или {app_id}ProdGeneralAction
   или {app_id}ProdActions::generalAction()
→ шаблон templates/actions/prod/ProdGeneral.html
```

Именно эту цепочку должен понимать разработчик и ИИ-агент перед любой правкой backend-раздела.
