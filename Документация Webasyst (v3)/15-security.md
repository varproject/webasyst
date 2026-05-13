# 15. Security в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** описать безопасность Webasyst как прикладной механизм: входные данные → route params → права → CSRF → SQL placeholders → escaping → upload → response/redirect.

---

## 1. Назначение механизма

Security в Webasyst не сводится к одному классу. Это набор правил, которые проходят через весь lifecycle запроса:

```text
HTTP/CLI request
→ waRequest
→ routing params
→ waDispatch
→ waFrontController
→ rights check
→ controller/action
→ model/service
→ template
→ waResponse
```

Главный принцип:

```text
не доверять request, route params, template data, upload files и redirect URL
```

В Webasyst для этого уже есть системные механизмы:

| Задача | Системный механизм |
|---|---|
| Чтение GET/POST/route/server/cookie | `waRequest::get()`, `post()`, `request()`, `param()`, `server()`, `cookie()` |
| Типизация входных данных | `waRequest::TYPE_INT`, `TYPE_STRING_TRIM`, `TYPE_ARRAY_INT`, `TYPE_ARRAY_TRIM` |
| CSRF | `csrf => true` в `app.php`, `{$wa->csrf()}`, проверка в `waDispatch` |
| Права | `rights => true`, `waRightConfig`, `checkRights()`, `getRights()` |
| SQL safety | `waModel`, placeholders, `getWhereByField()`, `select()->where()` |
| Output escaping | `htmlspecialchars()`, Smarty escaping, безопасный assign |
| Upload | `waRequest::file()`, `waRequestFile`, `wa-data` |
| Redirect | `waResponse::redirect()`, проверка локальности URL там, где URL приходит от пользователя |
| Cookie | `waResponse::setCookie()` с `httponly`, `secure`, `samesite` |

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/request/waRequest.class.php` | Чтение и типизация request data. |
| `wa-system/request/waRequestFile.class.php` | Работа с загруженными файлами. |
| `wa-system/controller/waDispatch.class.php` | Backend/frontend dispatch, auth, CSRF. |
| `wa-system/controller/waFrontController.class.php` | Dispatch в app/plugin и проверка app rights. |
| `wa-system/controller/waController.class.php` | `getRights()`, `redirect()`, доступ к request/response/storage. |
| `wa-system/config/waRightConfig.class.php` | Конфиг прав доступа. |
| `wa-system/database/waModel.class.php` | DB layer, placeholders, CRUD, escaping. |
| `wa-system/response/waResponse.class.php` | Headers, status, cookies, redirects, assets. |
| `wa-system/view/waSmarty3View.class.php` | Smarty rendering, assign, escaping. |
| `wa-system/view/waViewHelper.class.php` | `$wa` helper в шаблонах. |
| `wa-system/view/waPluginViewHelper.class.php` | Безопасный plugin helper в Smarty. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | `csrf`, `rights`, `auth`, `frontend`, `routing_params`. |
| `wa-apps/{app_id}/lib/config/{app_id}RightConfig.class.php` | Детальная схема прав. |
| `wa-apps/{app_id}/lib/config/{app_id}Config.class.php` | App-level `checkRights()`, кастомная логика config. |
| `wa-apps/{app_id}/lib/actions/...` | Controllers/actions, где проверяются права, входные данные, CSRF-aware POST. |
| `wa-apps/{app_id}/lib/models/...` | DB logic. |
| `wa-apps/{app_id}/templates/...` | Smarty output и forms. |

### 2.3. Файлы plugin-а

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | `rights`, `handlers`, plugin metadata. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php` | Settings controls. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/...` | Plugin backend actions/controllers. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/...` | Plugin templates. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/db.php` | Plugin schema. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/install.php` | Install script. |

---

## 3. Системная цепочка выполнения

### 3.1. Frontend

```text
index.php
→ waSystem::dispatch()
→ waDispatch::dispatchFrontend()
→ waRouting::dispatch()
→ waRequest::param('app/module/action/secure/auth/...')
→ wa($app, 1)
→ if secure/auth and user is guest: login()
→ if secure POST and app csrf enabled: CSRF check
→ app FrontController
→ controller/action
→ model/service/template
```

В frontend `waDispatch` делает несколько security-sensitive операций:

1. проверяет специальные callback endpoints;
2. запускает global routing;
3. определяет active app из route params;
4. проверяет `secure`/`auth` route params;
5. для secure POST проверяет `_csrf`, если app config содержит `csrf => true`;
6. только потом передаёт запрос в app `waFrontController`.

### 3.2. Backend

```text
index.php
→ waSystem::dispatch()
→ waDispatch::dispatchBackend()
→ проверка авторизации backend-пользователя
→ определение app из /{backend_url}/{app_id}/
→ проверка backend-доступа к app
→ wa($app, 1)
→ если app csrf enabled and POST: CSRF check
→ app FrontController
→ routing.backend.php или GET module/action
→ checkRights()
→ controller/action
```

Backend имеет более строгий вход:

- пользователь должен быть авторизован как backend user;
- у пользователя должен быть app-level right `backend`;
- если app включает `csrf`, POST проверяется на уровне `waDispatch`;
- затем `waFrontController` вызывает `checkRights($module, $action)` перед запуском controller/action.

---

## 4. Входные данные через `waRequest`

### 4.1. Не читать superglobals напрямую

Неправильно:

```php
$id = $_GET['id'];
$name = $_POST['name'];
```

Правильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
$name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
```

`waRequest` поддерживает типы:

```php
waRequest::TYPE_INT
waRequest::TYPE_STRING
waRequest::TYPE_STRING_TRIM
waRequest::TYPE_ARRAY
waRequest::TYPE_ARRAY_INT
waRequest::TYPE_ARRAY_TRIM
```

### 4.2. GET, POST, REQUEST, PARAM

| Метод | Источник | Использование |
|---|---|---|
| `waRequest::get()` | `$_GET` | Query string, фильтры, page, sort. |
| `waRequest::post()` | `$_POST` без `_csrf` при чтении всего массива | Изменяющие формы. |
| `waRequest::request()` | POST + GET | Только когда осознанно нужен fallback. |
| `waRequest::param()` | routing/system params | route placeholders, app/module/action, settlement params. |
| `waRequest::server()` | `$_SERVER` | Заголовки/окружение. |
| `waRequest::cookie()` | `$_COOKIE` | Cookies. |
| `waRequest::file()` | `$_FILES` | Upload. |

### 4.3. Route placeholders читать через `param()`

Route:

```php
'orders/<id:\d+>/' => 'orders/view',
```

Action:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

Неправильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

`id` из route не является GET-параметром.

### 4.4. Не доверять `waRequest::request()`

`request()` удобен, но опасен для изменяющих операций, потому что смешивает POST и GET.

Неправильно:

```php
$id = waRequest::request('id', 0, waRequest::TYPE_INT);
```

Для POST command:

```php
$id = waRequest::post('id', 0, waRequest::TYPE_INT);
```

Для route:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

Для фильтра списка:

```php
$page = waRequest::get('page', 1, waRequest::TYPE_INT);
```

---

## 5. CSRF

### 5.1. App-level включение

В `wa-apps/{app_id}/lib/config/app.php`:

```php
return array(
    'csrf' => true,
);
```

Если `csrf => true`, backend POST проверяется системно в `waDispatch`.

Frontend secure POST также проверяется, если route/app context secure и app config включает csrf.

### 5.2. Form token

В Smarty-формах:

```smarty
<form method="post" action="{$wa_app_url}orders/save/">
    {$wa->csrf()}
    ...
</form>
```

Правило:

```text
любая POST-форма, которая изменяет данные, должна содержать {$wa->csrf()}
```

### 5.3. CSRF не заменяет права

CSRF отвечает только на вопрос:

```text
форма отправлена из корректной сессии?
```

Он не отвечает на вопрос:

```text
имеет ли пользователь право выполнить действие?
```

Поэтому POST action должен проверять оба слоя:

```php
class myappOrdersDeleteController extends waController
{
    public function execute()
    {
        if (!$this->getRights('delete')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            throw new waException('Order not found', 404);
        }

        $model = new myappOrderModel();
        $model->deleteById($id);

        $this->redirect(wa()->getAppUrl('myapp') . 'orders/');
    }
}
```

---

## 6. Права доступа

### 6.1. App-level rights

В `app.php`:

```php
'rights' => true,
```

Дальше app может иметь:

```text
wa-apps/{app_id}/lib/config/{app_id}RightConfig.class.php
```

Пример:

```php
class myappRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('orders', 'Can manage orders');
        $this->addItem('settings', 'Can manage settings');
    }
}
```

### 6.2. Runtime check

В controller/action:

```php
if (!$this->getRights('orders')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

Или через user:

```php
if (!wa()->getUser()->getRights('myapp', 'orders')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### 6.3. `checkRights($module, $action)`

App config class может централизовать module/action rights:

```php
class myappConfig extends waAppConfig
{
    public function checkRights($module, $action)
    {
        if ($module == 'settings') {
            return wa()->getUser()->getRights('myapp', 'settings');
        }

        if ($module == 'orders') {
            return wa()->getUser()->getRights('myapp', 'orders');
        }

        return true;
    }
}
```

Но `checkRights()` не должен быть единственным барьером. Object-level checks всё равно должны быть в service/model/action layer.

### 6.4. Plugin rights

В `plugin.php`:

```php
return array(
    'rights' => true,
);
```

Runtime:

```php
if (!$this->getRights()) {
    throw new waRightsException(_ws('Access denied.'));
}
```

Plugin right key:

```text
plugin.{plugin_id}
plugin.{plugin_id}.{name}
```

---

## 7. SQL injection и модели

### 7.1. Не вставлять request в SQL строкой

Неправильно:

```php
$id = waRequest::get('id');
$sql = "SELECT * FROM myapp_order WHERE id = $id";
$row = $model->query($sql)->fetch();
```

Правильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
$row = $model->getById($id);
```

Или:

```php
$row = $model
    ->select('*')
    ->where('id = i:id', array('id' => $id))
    ->fetch();
```

### 7.2. Placeholders

`waModel::exec()` и `waModel::query()` поддерживают placeholders:

| Placeholder | Значение |
|---|---|
| `i:name` | integer или array of integers |
| `s:name` | string или array of strings |
| `b:name` | boolean |
| `f:name` | float/decimal |
| `l:name` | LIKE string with escaped `%` and `_` |
| `?` | positional placeholder |

Пример:

```php
$rows = $this->query(
    "SELECT * FROM myapp_order WHERE contact_id = i:contact_id AND name LIKE l:query",
    array(
        'contact_id' => $contact_id,
        'query'      => '%' . $query . '%',
    )
)->fetchAll();
```

### 7.3. Использовать model methods

Предпочтительно:

```php
$model->getById($id);
$model->getByField('code', $code);
$model->insert($data);
$model->updateById($id, $data);
$model->deleteById($id);
```

Прямой SQL нужен, когда:

- запрос сложнее стандартного CRUD;
- есть агрегаты;
- нужен join;
- нужна оптимизация;
- официальный app делает аналогично.

### 7.4. SQL не должен быть в шаблоне

Неправильно:

```smarty
{php}
$model = new myappOrderModel();
...
{/php}
```

Правильно:

```php
// action
$orders = $model->getRecentOrders();
$this->view->assign('orders', $orders);
```

```smarty
{foreach $orders as $order}
    {$order.name|escape}
{/foreach}
```

---

## 8. XSS и escaping

### 8.1. PHP escaping

Если action формирует HTML руками:

```php
$name = htmlspecialchars($row['name'], ENT_QUOTES, 'utf-8');
```

Но лучше передавать данные в шаблон и экранировать там.

### 8.2. Smarty escaping

В шаблоне:

```smarty
{$order.name|escape}
{$order.description|escape}
```

Для URL attribute:

```smarty
<a href="{$order_url|escape}">{$order.name|escape}</a>
```

Для HTML, который уже безопасно сформирован системным компонентом или trusted helper:

```smarty
{$control_html}
```

`nofilter` применять только осознанно:

```smarty
{$trusted_html nofilter}
```

### 8.3. Не выводить request напрямую

Неправильно:

```smarty
<input value="{$wa->get('query')}">
```

Правильно:

```smarty
<input value="{$query|escape}">
```

Где `$query` подготовлен в action:

```php
$query = waRequest::get('query', '', waRequest::TYPE_STRING_TRIM);
$this->view->assign('query', $query);
```

### 8.4. Event HTML

Многие events возвращают HTML:

```php
$this->view->assign('frontend_footer', wa()->event('frontend_footer'));
```

Такой HTML считается output plugin-ов и обычно вставляется как trusted extension point. Но нельзя смешивать его с пользовательским вводом без escaping.

---

## 9. Upload

### 9.1. Использовать `waRequest::file()`

```php
foreach (waRequest::file('image') as $file) {
    if (!$file->uploaded()) {
        continue;
    }

    if (!in_array(strtolower($file->extension), array('jpg', 'jpeg', 'png', 'webp'))) {
        throw new waException('Unsupported file type', 400);
    }

    $path = wa()->getDataPath('uploads/', true, 'myapp');
    $filename = time() . '.' . $file->extension;

    if (!$file->moveTo($path, $filename)) {
        throw new waException('Failed to upload file', 500);
    }
}
```

### 9.2. Пользовательские файлы — в `wa-data`

Неправильно:

```text
wa-apps/myapp/img/uploads/
```

Правильно:

```text
wa-data/public/myapp/...
wa-data/protected/myapp/...
```

Использовать:

```php
wa()->getDataPath($path, $public, $app_id);
wa()->getDataUrl($path, $public, $app_id);
```

### 9.3. Не доверять имени файла

Неправильно:

```php
$file->moveTo($path, $file->name);
```

Правильно:

```php
$filename = $name . '.' . strtolower($file->extension);
$file->moveTo($path, $filename);
```

Проверить:

- extension;
- MIME/тип, если критично;
- размер;
- destination path;
- права доступа;
- public/protected режим.

---

## 10. Redirect safety

### 10.1. Не редиректить на внешний URL из request без проверки

Неправильно:

```php
$url = waRequest::get('return_url');
$this->redirect($url);
```

Правильно:

```php
$url = waRequest::get('return_url', '', waRequest::TYPE_STRING_TRIM);

if (!$url || preg_match('~^https?://~i', $url) || strpos($url, '//') === 0) {
    $url = wa()->getAppUrl('myapp');
}

$this->redirect($url);
```

### 10.2. Для внутренних URL использовать helpers

```php
$this->redirect(wa()->getAppUrl('myapp') . 'orders/');
```

Frontend:

```php
$url = wa()->getRouteUrl('shop/frontend/product', array(
    'product_url' => $product['url'],
));
```

Не хардкодить:

```php
'/webasyst/myapp/'
'/shop/product/'
'https://domain/...'
```

---

## 11. Template path и file path validation

### 11.1. Не принимать template path из request

Неправильно:

```php
$template = waRequest::get('template');
$this->setTemplate($template);
```

Правильно:

```php
$type = waRequest::get('type', 'list', waRequest::TYPE_STRING_TRIM);

switch ($type) {
    case 'card':
        $this->setTemplate('orders/OrdersCard.html', true);
        break;

    case 'list':
    default:
        $this->setTemplate('orders/OrdersList.html', true);
        break;
}
```

### 11.2. Не принимать filesystem path из request

Неправильно:

```php
$file = waRequest::get('file');
readfile($file);
```

Правильно:

- хранить file id в БД;
- искать путь по id;
- проверять права;
- проверять, что путь внутри разрешённой директории;
- использовать `wa()->getDataPath()`.

---

## 12. `waConfig::get('is_template')`

Во время рендера Smarty Webasyst выставляет template context. Некоторые системные участки временно сбрасывают `is_template`, чтобы безопасно выполнить runtime-логику.

Практическое правило:

```text
не делать опасные публичные helper-методы, которые из шаблона могут менять состояние без проверки
```

Если helper вызывается из Smarty:

- он должен быть read-only;
- не должен писать в БД без явного POST/action;
- не должен удалять файлы;
- не должен выполнять произвольный путь/класс;
- не должен обходить права.

---

## 13. Response headers, cookies, status

### 13.1. Headers

```php
wa()->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
wa()->getResponse()->sendHeaders();
```

### 13.2. Status

```php
wa()->getResponse()->setStatus(404);
throw new waException('Not found', 404);
```

### 13.3. Cookies

```php
wa()->getResponse()->setCookie('my_cookie', $value, array(
    'expires'  => time() + 86400,
    'path'     => wa()->getRootUrl(),
    'secure'   => waRequest::isHttps(),
    'httponly' => true,
    'samesite' => 'Lax',
));
```

Не хранить в cookie:

- пароли;
- токены без необходимости;
- секреты;
- персональные данные без причины.

---

## 14. Минимальная реализация: безопасный POST controller

Задача:

```text
POST /webasyst/myapp/orders/123/delete/
```

### 14.1. Route

```php
<?php

return array(
    'orders/<id:\d+>/delete/?' => 'orders/delete',
    'orders/?'                 => 'orders/list',
    ''                         => 'backend',
);
```

### 14.2. Controller

```php
<?php

class myappOrdersDeleteController extends waController
{
    public function execute()
    {
        if (!$this->getRights('orders')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }

        $id = waRequest::param('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            throw new waException('Order not found', 404);
        }

        $model = new myappOrderModel();
        $order = $model->getById($id);

        if (!$order) {
            throw new waException('Order not found', 404);
        }

        $model->deleteById($id);

        $this->redirect(wa()->getAppUrl('myapp') . 'orders/');
    }
}
```

### 14.3. Template

```smarty
<form method="post" action="{$wa_app_url}orders/{$order.id|escape}/delete/">
    {$wa->csrf()}
    <button type="submit" class="button red">Delete</button>
</form>
```

---

## 15. Расширенная реализация: безопасный upload

```php
<?php

class myappFilesUploadController extends waController
{
    public function execute()
    {
        if (!$this->getRights('files')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        if (waRequest::method() !== waRequest::METHOD_POST) {
            throw new waException('Method not allowed', 405);
        }

        $allowed = array('jpg', 'jpeg', 'png', 'webp', 'pdf');
        $saved = array();

        foreach (waRequest::file('file') as $file) {
            if (!$file->uploaded()) {
                continue;
            }

            $extension = strtolower($file->extension);
            if (!in_array($extension, $allowed)) {
                throw new waException('Unsupported file type', 400);
            }

            $filename = uniqid('file_', true) . '.' . $extension;
            $path = wa()->getDataPath('uploads/', false, 'myapp');

            if (!$file->moveTo($path, $filename)) {
                throw new waException('Failed to upload file', 500);
            }

            $saved[] = $filename;
        }

        $this->redirect(wa()->getAppUrl('myapp') . 'files/');
    }
}
```

---

## 16. Типовые ошибки

### Ошибка 1. Читать request без типа

Неправильно:

```php
$id = waRequest::get('id');
```

Правильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

### Ошибка 2. Читать route params из GET

Неправильно:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

Для route placeholders:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

### Ошибка 3. Считать CSRF достаточной защитой

CSRF не проверяет права. Для save/delete нужны:

- CSRF;
- method check;
- rights check;
- object-level validation;
- model/service validation.

### Ошибка 4. SQL в action/template без placeholders

Неправильно:

```php
$model->query("SELECT * FROM table WHERE name = '$name'");
```

Правильно:

```php
$model->query("SELECT * FROM table WHERE name = s:name", array('name' => $name));
```

### Ошибка 5. Хранить upload в `wa-apps`

`wa-apps` — код приложения. Пользовательские файлы должны жить в `wa-data`.

### Ошибка 6. Выводить пользовательский ввод без escaping

Неправильно:

```smarty
{$query}
```

Правильно:

```smarty
{$query|escape}
```

### Ошибка 7. Принимать template path из request

Нельзя передавать `waRequest::get('template')` в `setTemplate()`.

### Ошибка 8. Редиректить на внешний URL из request

Проверять локальность `return_url`, `back_url`, `next`.

### Ошибка 9. Проверять права только в меню

Скрытая кнопка в UI не является защитой. Проверка должна быть на сервере.

### Ошибка 10. Писать state-changing helper для Smarty

Smarty helper должен быть read-only, если нет очень жёсткой системной причины.

---

## 17. Чеклист разработчика

### Request

- [ ] Все GET параметры читаются через `waRequest::get()` с типом.
- [ ] Все POST параметры читаются через `waRequest::post()` с типом.
- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] `waRequest::request()` не используется для save/delete без причины.
- [ ] Массивы типизированы через `TYPE_ARRAY_INT` или `TYPE_ARRAY_TRIM`.

### CSRF

- [ ] В app config включён `csrf => true`.
- [ ] Все POST-формы содержат `{$wa->csrf()}`.
- [ ] AJAX/htmx POST также передаёт CSRF.
- [ ] CSRF не используется вместо прав.

### Rights

- [ ] App имеет `rights => true`, если есть backend-доступ.
- [ ] Есть `waRightConfig`, если нужны детальные права.
- [ ] Save/delete проверяют права сервером.
- [ ] Object-level rights проверяются отдельно.
- [ ] Plugin actions проверяют `plugin.{plugin_id}` rights, если plugin их включает.

### SQL

- [ ] Нет SQL с конкатенацией request values.
- [ ] Используются model methods или placeholders.
- [ ] SQL находится в model/service layer, не в template.
- [ ] `LIKE` использует безопасную обработку.
- [ ] `IN` использует `i:ids` или валидированный int array.

### Output

- [ ] User data экранируется в Smarty через `|escape`.
- [ ] `nofilter` применяется только для trusted HTML.
- [ ] Event HTML не смешивается с сырым user input.
- [ ] JSON responses не содержат необработанные exception traces в production.

### Upload

- [ ] Upload идёт через `waRequest::file()`.
- [ ] Проверяются extension/type/size.
- [ ] Файлы сохраняются в `wa-data`, не в `wa-apps`.
- [ ] Имя файла генерируется сервером.
- [ ] Есть проверка прав на upload/download/delete.

### Redirect/path

- [ ] Нет redirect на внешний URL из request без проверки.
- [ ] URL строятся через `wa()->getAppUrl()` или `wa()->getRouteUrl()`.
- [ ] Нет template path из request.
- [ ] Нет filesystem path из request.
- [ ] Нет hardcode `/webasyst/`.

---

## 18. Чеклист ИИ-агента

Перед ответом по security-задаче ИИ-агент обязан:

1. Открыть `app.php` и проверить `csrf`, `rights`, `auth`, `frontend`.
2. Открыть текущий route/routing.backend.php.
3. Определить источник каждого параметра: GET, POST, route param, server, cookie.
4. Проверить текущий action/controller.
5. Проверить model/service, где идёт БД.
6. Проверить template/form и наличие `{$wa->csrf()}`.
7. Проверить escaping вывода.
8. Проверить rights layer: app rights, plugin rights, object rights.
9. Проверить upload/download paths, если есть файлы.
10. Проверить redirect URL, если есть возврат назад.
11. Проверить, не записываются ли пользовательские данные в `wa-apps`.
12. Только после этого писать код.

ИИ-агенту запрещено:

- читать request без типа;
- использовать `$_GET`, `$_POST`, `$_FILES` напрямую;
- читать route params через `get()`;
- писать SQL в Smarty;
- писать SQL без placeholders;
- добавлять POST-форму без `{$wa->csrf()}`;
- проверять права только в UI;
- сохранять uploads в `wa-apps`;
- принимать template/file path из request;
- редиректить на request URL без проверки;
- создавать собственный CSRF-механизм.

---

## 19. Мини-сводка

Security в Webasyst — это цепочка, а не один метод.

Правильная модель:

```text
waRequest typed input
→ routing params через waRequest::param()
→ CSRF на POST
→ rights в waFrontController/checkRights/action/service
→ DB через waModel/placeholders
→ output через Smarty escaping
→ upload только через waRequestFile + wa-data
→ redirect только на проверенный URL
```

Если хотя бы один слой пропущен, решение не считается production-safe.
