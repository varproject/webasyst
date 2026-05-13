# 16. JSON, AJAX, long actions и CLI в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить механизмы Webasyst для нестраничных запросов: JSON-ответов, AJAX/HTML partials, долгих операций, CLI-команд и cron-задач.

---

## 1. Назначение механизма

Не каждый request в Webasyst должен рендерить полноценную HTML-страницу через `waViewAction` и layout.

Для разных типов задач в Webasyst есть разные runtime-механизмы:

| Задача | Базовый механизм |
|---|---|
| JSON-ответ на один endpoint | `waJsonController` |
| JSON-ответы с несколькими action-методами | `waJsonActions` |
| HTML-фрагмент для AJAX/htmx | `waViewAction` или `waViewController` без полного layout |
| Redirect/POST command/file response | `waController` |
| Долгая операция с progress polling | `waLongActionController` |
| CLI-команда | `waCliController` |
| Cron-запуск | CLI через `waDispatch::dispatchCli()` и cron config |

Главный принцип: выбирать controller по формату ответа и длительности операции, а не превращать каждый запрос в `waViewAction`.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/controller/waJsonController.class.php` | JSON controller с одним `execute()`. |
| `wa-system/controller/waJsonActions.class.php` | JSON multi-action controller. |
| `wa-system/controller/waLongActionController.class.php` | Базовый controller для долгих операций. |
| `wa-system/controller/waCliController.class.php` | Базовый CLI controller. |
| `wa-system/controller/waDispatch.class.php` | Dispatch HTTP/CLI и запуск CLI/cron. |
| `wa-system/request/waRequest.class.php` | Request method, AJAX detection, GET/POST/param typing. |
| `wa-system/response/waResponse.class.php` | Headers, JSON content type, redirects, assets. |
| `wa-system/database/waModel.class.php` | Работа с БД внутри JSON/long/CLI logic. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/actions/{module}/{app}{Module}{Action}.controller.php` | Single controller, включая JSON/POST/file/long action. |
| `wa-apps/{app_id}/lib/actions/{module}/{app}{Module}.actions.php` | Multi-action controller, включая `waJsonActions`. |
| `wa-apps/{app_id}/lib/cli/{app}{Slug}.cli.php` или `wa-apps/{app_id}/lib/classes/{app}{Slug}.cli.php` | CLI class, если он попадает в autoload. |
| `wa-apps/{app_id}/lib/config/cron.php` | Cron jobs приложения. |
| `wa-apps/{app_id}/lib/config/routing.php` | Frontend routes для JSON/API endpoints. |
| `wa-apps/{app_id}/lib/config/routing.backend.php` | Backend routes для красивых backend endpoints. |
| `wa-apps/{app_id}/templates/actions/{module}/...` | HTML partial templates, если AJAX возвращает HTML. |

### 2.3. Файлы plugin-а

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/...` | Plugin backend/frontend controllers/actions. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php` | Frontend routing plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/cron.php` | Cron jobs plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/...` | HTML partial templates plugin-а. |

---

## 3. Системная цепочка выполнения

### 3.1. JSON endpoint через обычный dispatch

Для HTTP-запроса JSON controller запускается тем же `waFrontController`, что и HTML action:

```text
URL
→ global routing/backend dispatch
→ waFrontController::getDispatchParams()
→ waFrontController::getController()
→ {app}{Module}{Action}Controller
→ waJsonController::run()
→ execute()
→ display()
→ JSON {status,data} или {status,errors}
```

`waJsonController` наследует `waController`, поэтому его `run()` вызывает `preExecute()`, `execute()`, `afterExecute()`, а затем `display()`.

### 3.2. Multi-action JSON endpoint

```text
module/action
→ {app}{Module}Actions extends waJsonActions
→ waJsonActions::run($action)
→ {action}Action()
→ JSON response
```

Если `action` пустой, вызывается `defaultAction()`.

### 3.3. HTML partial для AJAX/htmx

AJAX не обязан возвращать JSON.

Для HTML partial нормальный Webasyst-подход:

```text
route or ?module/action
→ waViewAction
→ assign data
→ template fragment
→ HTML response
```

Такой подход особенно удобен, если frontend-код ожидает готовый HTML: htmx, jQuery `.load()`, popup/dialog content, table fragment, toolbar fragment.

### 3.4. Long action

`waLongActionController` разделяет долгую операцию на Runner и Messenger.

```text
first request without processId
→ init new process
→ return processId via info()

next requests with processId
→ one Runner obtains lock and performs step()
→ other requests become Messengers and return info()
→ when isDone() === true
→ finish()
→ cleanup or infoReady()
```

Критически важно: `step()` должен делать маленький кусок работы. Это не место для одного огромного цикла на тысячи записей без сохранения состояния.

### 3.5. CLI

CLI-запуск идёт через `waDispatch::dispatchCli()`:

```text
php cli.php app slug [params]
→ SystemConfig('cli')
→ waSystem::dispatchCli()
→ waDispatch::dispatchCli()
→ waSystem::getInstance(app, null, true)
→ class {app}{Slug}Cli
→ waCliController::run()
→ execute()
```

Пример:

```bash
php cli.php shop import --limit 100
```

Ожидаемый класс:

```php
shopImportCli
```

---

## 4. Ключевые классы и методы

### 4.1. `waJsonController`

Назначение: один JSON endpoint.

Ключевые поля:

```php
protected $response = array();
protected $errors = array();
```

Формат успешного ответа:

```json
{
  "status": "ok",
  "data": {}
}
```

Формат ошибки:

```json
{
  "status": "fail",
  "errors": []
}
```

`setError($message, $data = array())` добавляет элемент в `$errors`.

### 4.2. `waJsonActions`

Назначение: один controller class для нескольких JSON action-методов.

```php
class myappSettingsActions extends waJsonActions
{
    public function saveAction()
    {
    }

    public function deleteAction()
    {
    }
}
```

Минус: легко превратить класс в God object. Для больших разных операций лучше использовать отдельные controllers.

### 4.3. `waLongActionController`

Обязательные методы:

```php
protected function init()
protected function isDone()
protected function step()
protected function finish($filename)
protected function info()
```

Дополнительные методы:

```php
protected function preInit()
protected function restore()
protected function infoReady($filename)
protected function uncleanShutdown()
protected function save()
```

Что хранить в `$this->data`:

- progress;
- offset;
- total;
- counters;
- ids queue;
- ошибки, которые можно сериализовать.

Что не хранить:

- DB connection;
- model object;
- closure;
- resource;
- огромные массивы, которые лучше держать в таблице/файле.

### 4.4. `waCliController`

Минимальный class:

```php
class myappReindexCli extends waCliController
{
    public function execute()
    {
    }
}
```

CLI class не рендерит HTML и не должен рассчитывать на browser/session/UI.

### 4.5. `waDispatch::dispatchCli()`

Системный CLI dispatch:

1. читает app id;
2. читает slug;
3. собирает параметры из `--key value`;
4. записывает их в `waRequest::param()`;
5. грузит приложение;
6. ищет `{app}{Slug}Cli`;
7. запускает controller.

---

## 5. Параметры request/config/routing

### 5.1. JSON controller params

В JSON controller использовать те же источники request, что и в обычном controller:

```php
$id = waRequest::post('id', 0, waRequest::TYPE_INT);
$query = waRequest::get('query', '', waRequest::TYPE_STRING_TRIM);
$route_id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

Правило:

- route placeholders — через `waRequest::param()`;
- query string — через `waRequest::get()`;
- POST body — через `waRequest::post()`;
- GET/POST fallback — через `waRequest::request()` только осознанно.

### 5.2. AJAX detection

```php
if (waRequest::isXMLHttpRequest()) {
}
```

Не нужно строить архитектуру только на этом признаке. Лучше явно понимать, какой endpoint возвращает JSON, а какой HTML.

### 5.3. CSRF

Если приложение включает:

```php
'csrf' => true,
```

то backend POST проверяется системно в `waDispatch::dispatchBackend()` перед входом в app front controller.

Для frontend secure-зон CSRF проверяется, если route имеет `secure` и app config включает `csrf`.

Формы должны содержать:

```smarty
{$wa->csrf()}
```

### 5.4. Long action `processId`

`waLongActionController` ищет:

```text
processId
processid
```

в GET/POST через `waRequest::request()`.

Первый request обычно не содержит `processId`, создаёт новый процесс и должен вернуть `processId` в `info()`.

### 5.5. CLI params

`waDispatch::dispatchCli()` превращает параметры командной строки в `waRequest::param()`.

Пример:

```bash
php cli.php myapp reindex --limit 100 --dry-run 1
```

В CLI:

```php
$limit = waRequest::param('limit', 100, waRequest::TYPE_INT);
$dry_run = waRequest::param('dry-run', 0, waRequest::TYPE_INT);
```

---

## 6. Паттерны официального Webasyst-кода

### 6.1. `waJsonController`

Системный class сам формирует envelope:

```php
array('status' => 'ok', 'data' => $this->response)
array('status' => 'fail', 'errors' => $this->errors)
```

Значит action не должен вручную делать `echo json_encode()` и `exit`, если он уже наследует `waJsonController`.

### 6.2. `waJsonActions`

`waJsonActions` вызывает метод по имени action:

```php
$method = $action.'Action';
```

Это совпадает с общим Webasyst-паттерном `Actions`, но результат всегда JSON.

### 6.3. `waLongActionController`

Системный long action явно документирует Runner/Messenger:

- один Runner делает работу;
- Messengers читают status;
- `step()` вызывается в цикле;
- данные процесса сохраняются между HTTP-запросами;
- `step()` должен быть коротким.

### 6.4. CLI dispatch

`waDispatch::dispatchCli()` ожидает class:

```text
{app}{Slug}Cli
```

Например:

```bash
php cli.php shop updateProducts
```

ожидает:

```php
shopUpdateProductsCli
```

### 6.5. HTML partials

В Webasyst AJAX не равен JSON.

Если endpoint возвращает HTML-фрагмент, используется обычный `waViewAction`:

```php
class myappBackendDialogAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('item', $item);
    }
}
```

Шаблон:

```text
wa-apps/myapp/templates/actions/backend/BackendDialog.html
```

---

## 7. Минимальная реализация: JSON controller

### 7.1. Route

```php
// wa-apps/myapp/lib/config/routing.backend.php
return array(
    'orders/<id:\d+>/state/?' => 'orders/state',
    '' => 'backend',
);
```

### 7.2. Controller

```php
<?php

class myappOrdersStateController extends waJsonController
{
    public function execute()
    {
        $this->checkRights();

        $id = waRequest::param('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            $this->setError('Order not found');
            return;
        }

        $state = waRequest::post('state', '', waRequest::TYPE_STRING_TRIM);
        if ($state === '') {
            $this->setError('State is required');
            return;
        }

        $model = new myappOrderModel();
        $order = $model->getById($id);
        if (!$order) {
            $this->setError('Order not found');
            return;
        }

        $model->updateById($id, array(
            'state' => $state,
        ));

        $this->response = array(
            'id' => $id,
            'state' => $state,
        );
    }

    protected function checkRights()
    {
        if (!$this->getRights('orders')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersState.controller.php
```

Ответ:

```json
{"status":"ok","data":{"id":123,"state":"paid"}}
```

или:

```json
{"status":"fail","errors":[["Order not found",[]]]}
```

---

## 8. Минимальная реализация: `waJsonActions`

```php
<?php

class myappOrdersActions extends waJsonActions
{
    public function stateAction()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $state = waRequest::post('state', '', waRequest::TYPE_STRING_TRIM);

        if ($id <= 0 || $state === '') {
            $this->errors[] = 'Invalid request';
            return;
        }

        $model = new myappOrderModel();
        $model->updateById($id, array('state' => $state));

        $this->response = array(
            'id' => $id,
            'state' => $state,
        );
    }

    public function deleteAction()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            $this->errors[] = 'Invalid id';
            return;
        }

        $model = new myappOrderModel();
        $model->deleteById($id);

        $this->response = array('id' => $id);
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

Routes:

```php
return array(
    'orders/state/?'  => 'orders/state',
    'orders/delete/?' => 'orders/delete',
    '' => 'backend',
);
```

---

## 9. Минимальная реализация: HTML partial для AJAX/htmx

### 9.1. Action

```php
<?php

class myappOrdersDialogAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);

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
wa-apps/myapp/lib/actions/orders/myappOrdersDialog.action.php
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersDialog.html
```

### 9.2. Template

```smarty
<div class="dialog-content">
    <h1>[`Order`] #{$order.id|escape}</h1>

    <form method="post" action="{$wa_app_url}orders/{$order.id}/state/">
        {$wa->csrf()}
        <input type="text" name="state" value="{$order.state|escape}">
        <button type="submit" class="button green">[`Save`]</button>
    </form>
</div>
```

---

## 10. Минимальная реализация: long action

```php
<?php

class myappOrdersExportController extends waLongActionController
{
    protected function init()
    {
        $model = new myappOrderModel();

        $this->data['offset'] = 0;
        $this->data['limit'] = 100;
        $this->data['total'] = $model->countAll();
        $this->data['done'] = 0;
    }

    protected function isDone()
    {
        return $this->data['done'] >= $this->data['total'];
    }

    protected function step()
    {
        $model = new myappOrderModel();
        $rows = $model
            ->select('*')
            ->order('id')
            ->limit($this->data['offset'].','.$this->data['limit'])
            ->fetchAll();

        foreach ($rows as $row) {
            fwrite($this->fd, json_encode($row)."\n");
            $this->data['done']++;
        }

        $this->data['offset'] += $this->data['limit'];

        return true;
    }

    protected function finish($filename)
    {
        $target = wa()->getDataPath('exports/orders.jsonl', false, 'myapp');
        waFiles::create(dirname($target));
        waFiles::copy($filename, $target);

        echo json_encode(array(
            'ready' => true,
            'url' => wa()->getDataUrl('exports/orders.jsonl', false, 'myapp'),
        ));

        return true;
    }

    protected function info()
    {
        echo json_encode(array(
            'processId' => $this->processId,
            'ready' => false,
            'done' => $this->data['done'],
            'total' => $this->data['total'],
        ));
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersExport.controller.php
```

Route:

```php
return array(
    'orders/export/?' => 'orders/export',
    '' => 'backend',
);
```

---

## 11. Минимальная реализация: CLI command

### 11.1. CLI class

```php
<?php

class myappReindexCli extends waCliController
{
    public function execute()
    {
        $limit = waRequest::param('limit', 100, waRequest::TYPE_INT);

        $model = new myappItemModel();
        $rows = $model->select('*')->limit($limit)->fetchAll();

        foreach ($rows as $row) {
            // Do CLI-safe work here.
        }

        echo "Done\n";
    }
}
```

Файл:

```text
wa-apps/myapp/lib/classes/myappReindex.cli.php
```

или иной путь внутри `lib/`, если файл попадает в autoload и имя файла корректно мапится в class.

### 11.2. Запуск

```bash
php cli.php myapp reindex --limit 500
```

Ожидаемый class:

```text
myappReindexCli
```

---

## 12. Cron config

Cron config обычно описывает команды, которые должны запускаться регулярно.

Файл:

```text
wa-apps/myapp/lib/config/cron.php
```

Пример:

```php
<?php

return array(
    'reindex' => array(
        'command' => 'php cli.php myapp reindex',
        'expression' => '0 * * * *',
    ),
);
```

Plugin cron:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/cron.php
```

`waPlugin::cron()` подключает plugin cron config, если файл существует.

---

## 13. Когда что использовать

| Ситуация | Использовать | Почему |
|---|---|---|
| Save/delete из backend UI с JSON response | `waJsonController` | Стандартный envelope и headers. |
| Несколько мелких JSON-команд одного module | `waJsonActions` | Меньше файлов, единый response format. |
| Dialog/table fragment/htmx partial | `waViewAction` | Возвращает готовый HTML. |
| Redirect after POST | `waController` | Шаблон не нужен. |
| Export/import на много записей из браузера | `waLongActionController` | Обход max execution time через processId. |
| Регулярная серверная задача | `waCliController` + cron | Нет зависимости от browser/session. |
| Публичный API endpoint | Route + controller, часто `waJsonController` | Явный JSON contract. |

---

## 14. Типовые ошибки

### Ошибка 1. Вручную echo JSON внутри `waJsonController`

Неправильно:

```php
public function execute()
{
    echo json_encode(array('ok' => true));
    exit;
}
```

Правильно:

```php
public function execute()
{
    $this->response = array('ok' => true);
}
```

### Ошибка 2. Возвращать JSON там, где нужен HTML partial

Если frontend ждёт HTML-фрагмент для замены блока, не нужно усложнять JSON + JS-render.

Правильно использовать `waViewAction` и template.

### Ошибка 3. Читать route placeholder через GET

Неправильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

Если `id` пришёл из route:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

### Ошибка 4. Делать долгую операцию в одном JSON request

Неправильно:

```php
foreach ($all_rows as $row) {
    // thousands of rows
}
```

Правильно:

```text
waLongActionController + processId + step()
```

### Ошибка 5. Держать session lock во время long operation

`waLongActionController` сам закрывает storage/session в нужные моменты. Не нужно писать собственный long polling поверх обычного controller без понимания session locks.

### Ошибка 6. CLI class рассчитывает на browser state

В CLI нет обычного browser request, нет UI, нет текущего frontend route.

Неправильно:

```php
$url = wa()->getRouteUrl('shop/frontend/product', $params);
```

без понимания domain/route context.

### Ошибка 7. Не проверять rights/CSRF в JSON endpoint

JSON endpoint не менее опасен, чем HTML POST.

Нужно проверять:

- права;
- типы входных данных;
- CSRF для POST, если route/app не покрыты системной проверкой;
- object-level access.

### Ошибка 8. Возвращать внутренние exception traces в JSON

Для публичных/пользовательских endpoint-ов возвращать контролируемые ошибки. Трассировки — только в logs/debug.

### Ошибка 9. Писать SQL в template

AJAX не оправдывает SQL в Smarty. Данные готовятся в model/service/action.

### Ошибка 10. Хардкодить backend/frontend URL

Неправильно:

```php
'/webasyst/myapp/?module=orders&action=state'
```

Правильно:

```php
wa()->getAppUrl('myapp').'orders/state/'
```

или route URL через `wa()->getRouteUrl()` для frontend.

---

## 15. Чеклист разработчика

### JSON

- [ ] Endpoint действительно должен возвращать JSON, а не HTML partial.
- [ ] Выбран `waJsonController` или `waJsonActions`.
- [ ] Ответ пишется в `$this->response`, ошибки — в `$this->errors` или `setError()`.
- [ ] Нет ручного `echo json_encode()` внутри `waJsonController` без необходимости.
- [ ] Входные данные типизированы через `waRequest`.
- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] Есть проверка прав.
- [ ] POST покрыт CSRF.

### AJAX/HTML partial

- [ ] Если нужен HTML, используется `waViewAction` и template.
- [ ] Шаблон не содержит бизнес-логики.
- [ ] Все пользовательские данные экранируются.
- [ ] Partial не подключает повторно глобальные CSS/JS без необходимости.

### Long action

- [ ] Операция действительно может превысить max execution time.
- [ ] Используется `waLongActionController`.
- [ ] `init()` инициализирует serializable state.
- [ ] `step()` делает маленький кусок работы.
- [ ] `isDone()` корректно определяет завершение.
- [ ] `info()` возвращает `processId` и progress.
- [ ] `finish()` чистит или сохраняет результат осознанно.
- [ ] Нет хранения model/resource/closure в `$this->data`.

### CLI/cron

- [ ] CLI class называется `{app}{Slug}Cli`.
- [ ] Class наследует `waCliController`.
- [ ] Входные параметры читаются через `waRequest::param()`.
- [ ] CLI не зависит от browser/session/UI.
- [ ] Ошибки пишутся в logs или STDERR, если это нужно.
- [ ] Cron config не дублирует runtime settings.

---

## 16. Чеклист ИИ-агента

Перед ответом на задачу по JSON/AJAX/long/CLI ИИ-агент обязан:

1. Определить формат ответа: JSON, HTML partial, redirect, file, long process, CLI.
2. Открыть текущий route: `routing.php` или `routing.backend.php`.
3. Определить итоговые `module/action`.
4. Проверить существующий controller/action/actions class.
5. Если JSON — проверить, используется ли `waJsonController` или `waJsonActions`.
6. Если HTML partial — проверить template и layout behavior.
7. Если long operation — проверить, нельзя ли разбить задачу на `waLongActionController`.
8. Если CLI — проверить имя `{app}{Slug}Cli` и путь в `lib/`.
9. Проверить `app.php`: `csrf`, `rights`, `frontend`.
10. Проверить права и object-level access.
11. Проверить request typing.
12. Проверить, нет ли SQL/template/URL hardcode.

ИИ-агенту запрещено:

- отдавать JSON вручную из `waViewAction`, если есть `waJsonController`;
- делать долгий import/export одним POST request без long action или CLI;
- читать route params через GET;
- писать SQL в Smarty;
- игнорировать CSRF, потому что endpoint “только AJAX”;
- придумывать несуществующий response format;
- называть CLI class не по Webasyst naming;
- использовать Laravel/Symfony console pattern вместо Webasyst CLI dispatch.

---

## 17. Мини-сводка

Для runtime-задач Webasyst использует разные controller-классы:

```text
JSON one endpoint        → waJsonController
JSON multi-action        → waJsonActions
HTML partial             → waViewAction
POST command/redirect    → waController
Long browser operation   → waLongActionController
CLI/cron                 → waCliController
```

Правильный выбор controller-а уменьшает код, сохраняет Webasyst dispatch-flow и снижает риск ошибок с headers, CSRF, rights, session locks и max execution time.
