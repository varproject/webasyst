# 09. Модели и база данных в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить работу с базой данных в Webasyst через `waModel`, схемы `db.php`, install/update-механизмы и безопасные SQL-паттерны.

---

## 1. Назначение механизма

В Webasyst модель — это основной слой доступа к базе данных. Она связывает PHP-класс с таблицей, знает структуру полей, умеет выполнять типовые CRUD-операции и предоставляет безопасные механизмы для SQL-запросов.

Модель не является “ORM” в стиле ActiveRecord. Это тонкий и практичный database abstraction layer:

```text
model class
→ waModel
→ db adapter
→ table metadata
→ CRUD helpers / query / exec / select builder
→ waDbResult*
```

Главные задачи модели:

1. Изолировать SQL от controller/action/template.
2. Централизовать имя таблицы и первичный ключ.
3. Использовать metadata таблицы для безопасной записи полей.
4. Давать типовые методы `getById()`, `getByField()`, `insert()`, `updateById()`, `deleteById()`.
5. Позволять писать сложные запросы через `query()`, `exec()` и `select()->where()`.
6. Защищать SQL от инъекций через placeholders.
7. Держать бизнес-правила данных ближе к данным, а не в Smarty-шаблоне.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/database/waModel.class.php` | Базовый класс модели: metadata, CRUD, SQL, placeholders, cache. |
| `wa-system/database/waDbQuery.class.php` | Fluent query builder: `select()`, `where()`, `order()`, `limit()`, `fetchAll()`. |
| `wa-system/database/waDbResultSelect.class.php` | Результат SELECT-запроса: `fetch()`, `fetchAll()`, `fetchField()`, `count()`. |
| `wa-system/database/waDbStatement.class.php` | Подготовка запросов с placeholders. |
| `wa-system/database/waDbConnector.class.php` | Создание соединения с БД из `wa-config/db.php`. |
| `wa-system/webasyst/lib/models/waAppSettings.model.php` | Системная модель настроек приложений. |
| `wa-system/config/waAppConfig.class.php` | Установка/обновление app, чтение `db.php`, запуск install/update. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/models/{app_id}{Name}.model.php` | Модель приложения. |
| `wa-apps/{app_id}/lib/classes/...` | Сервисы/классы, которые могут использовать модели. |
| `wa-apps/{app_id}/lib/actions/...` | Actions/controllers вызывают модели, но не должны держать сложный SQL. |
| `wa-apps/{app_id}/lib/config/db.php` | Декларативная схема таблиц приложения. |
| `wa-apps/{app_id}/lib/config/install.php` | Логика установки приложения, если нужна сверх `db.php`. |
| `wa-apps/{app_id}/lib/config/uninstall.php` | Логика удаления приложения. |
| `wa-apps/{app_id}/lib/updates/*.php` | Миграции/обновления схемы и данных. |

### 2.3. Файлы плагина

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/models/...` | Модели плагина. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/db.php` | Схема таблиц плагина, если plugin хранит свои данные. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/install.php` | Install logic plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/uninstall.php` | Uninstall logic plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/updates/*.php` | Plugin updates. |

---

## 3. Системная цепочка выполнения

### 3.1. Установка таблиц

При первом запуске app config выполняет install/update lifecycle.

Упрощённая цепочка:

```text
waSystem::getInstance($app)
→ SystemConfig::getAppConfig($app)
→ waAppConfig::__construct()
→ waAppConfig::init()
→ include wa-apps/{app}/lib/config/app.php
→ register app classes in autoload
→ waAppConfig::checkUpdates()
→ waAppConfig::install(), если приложение запускается впервые
→ include lib/config/db.php
→ waModel::createSchema($schema)
→ include lib/config/install.php, если файл существует
```

`db.php` — основной декларативный источник схемы таблиц. `install.php` нужен только для дополнительной логики: заполнить начальные данные, создать системные настройки, подготовить файлы и т.д.

### 3.2. Обычный запрос страницы

```text
HTTP request
→ waDispatch / waRouting
→ waFrontController
→ Action/Controller
→ new {app}{Name}Model()
→ waModel::__construct()
→ DB connection
→ metadata table cache
→ model method
→ result array/object
→ action assigns result to view
→ Smarty template renders data
```

Важная граница:

```text
template не должен ходить в БД
```

Шаблон получает уже подготовленные данные от action/controller.

### 3.3. Сохранение данных

```text
POST request
→ controller/action
→ rights + CSRF + request normalization
→ model/service validation
→ waModel::insert/update/delete/query/exec
→ redirect / JSON / HTML partial
```

Права и CSRF проверяются на уровне controller/action или выше. Модель не должна полагаться на то, что UI “не покажет кнопку”.

---

## 4. Ключевые классы и методы

## 4.1. `waModel`

Базовая модель:

```php
class myappOrderModel extends waModel
{
    protected $table = 'myapp_order';
}
```

Ключевые свойства:

| Свойство | Назначение |
|---|---|
| `$table` | Имя таблицы. Например `blog_post`, `shop_order`, `myapp_order`. |
| `$id` | Primary key. По умолчанию `id`. Может быть массивом для составного ключа. |
| `$fields` | Metadata полей таблицы. Заполняется из `describe()`. |
| `$adapter` | DB adapter, созданный через `waDbConnector`. |
| `$type` | DB connection id из `wa-config/db.php`, по умолчанию `default`. |

### 4.2. Metadata

При создании модели Webasyst загружает описание таблицы:

```php
public function __construct($type = null, $writable = false)
{
    $this->writable = $writable;
    $this->type = $type ? $type : 'default';
    $this->adapter = waDbConnector::getConnection($this->type, $this->writable);
    if ($this->table && !$this->fields) {
        $this->getMetadata();
    }
}
```

Metadata используется для:

- проверки, какие поля реально есть в таблице;
- приведения значений к типам;
- safe insert/update;
- формирования `getEmptyRow()`;
- определения auto increment primary key.

Если схема таблицы изменилась, а metadata закэширована, можно вызвать:

```php
$model->clearMetadataCache();
```

Но в нормальном update-файле после изменения структуры лучше явно очистить cache, а не ждать случайной ошибки.

### 4.3. CRUD helpers

Типовые методы `waModel`:

| Метод | Назначение |
|---|---|
| `getById($id)` | Получить запись по primary key. |
| `getByField($field, $value = null, $all = false)` | Получить запись/записи по полю или набору полей. |
| `countByField($field, $value = null)` | Посчитать записи по условию. |
| `insert($data, $type = 0)` | Вставить запись. |
| `replace($data)` | `REPLACE INTO`. |
| `multipleInsert($data, $mode = null)` | Массовая вставка. |
| `updateById($id, $data)` | Обновить запись по primary key. |
| `updateByField($field, $value, $data)` | Обновить записи по условию. |
| `deleteById($id)` | Удалить запись по primary key. |
| `deleteByField($field, $value = null)` | Удалить записи по условию. |
| `select($select = '*')` | Создать `waDbQuery`. |
| `query($sql, $params = null)` | Выполнить SQL и получить result object. |
| `exec($sql, $params = null)` | Выполнить SQL без работы с result object. |

---

## 5. `query()` vs `exec()`

### 5.1. `query()`

`query()` используется, когда нужен результат запроса:

```php
$rows = $this->query(
    'SELECT * FROM '.$this->table.' WHERE status = s:status',
    array('status' => 'published')
)->fetchAll('id');
```

Для SELECT возвращается `waDbResultSelect`, у которого есть:

```php
fetch()
fetchAssoc()
fetchArray()
fetchField()
fetchAll($key = null, $normalize = false)
count()
```

### 5.2. `exec()`

`exec()` используется, когда результат не нужен:

```php
$this->exec(
    'UPDATE '.$this->table.' SET status = s:status WHERE id = i:id',
    array(
        'status' => 'published',
        'id'     => $id,
    )
);
```

Для `INSERT`, `UPDATE`, `DELETE`, `DROP`, `REPLACE` `exec()` очищает зарегистрированные cache cleaners.

### 5.3. Правило выбора

| Задача | Метод |
|---|---|
| Получить строки | `query()` или `select()` |
| Получить одно поле | `query()->fetchField()` |
| Вставить через helper | `insert()` |
| Обновить через helper | `updateById()` / `updateByField()` |
| Удалить через helper | `deleteById()` / `deleteByField()` |
| Выполнить произвольный UPDATE/DELETE | `exec()` |
| Выполнить произвольный SELECT | `query()` |

---

## 6. Placeholders и безопасный SQL

`waModel::exec()` и `waModel::query()` поддерживают placeholders.

### 6.1. Простые placeholders

```php
$this->exec(
    'UPDATE '.$this->table.' SET name = ? WHERE id = ?',
    $name,
    $id
);
```

Простые `?` зависят от порядка аргументов.

### 6.2. Named placeholders

```php
$this->query(
    'SELECT * FROM '.$this->table.' WHERE id = i:id AND status = s:status',
    array(
        'id'     => $id,
        'status' => $status,
    )
)->fetch();
```

Поддерживаемые типы:

| Placeholder | Тип | Поведение |
|---|---|---|
| `i:name` | integer / array of integers | Приведение к int, массив превращается в список. |
| `s:name` | string / array of strings | Экранирование строк и кавычки. |
| `b:name` | boolean | Превращается в `1` или `0`. |
| `f:name` | float/decimal | Запятая заменяется на точку, значение приводится к числу. |
| `l:name` | LIKE string | Экранирует `%` и `_` для LIKE. |

### 6.3. LIKE-поиск

Правильно:

```php
$this->query(
    'SELECT * FROM '.$this->table.' WHERE name LIKE l:query',
    array('query' => '%'.$query.'%')
)->fetchAll();
```

Или явно собрать `%` в SQL:

```php
$this->query(
    "SELECT * FROM {$this->table} WHERE name LIKE CONCAT('%', l:query, '%')",
    array('query' => $query)
)->fetchAll();
```

Неправильно:

```php
$sql = "SELECT * FROM {$this->table} WHERE name LIKE '%{$query}%'";
```

### 6.4. IN-запрос

```php
$this->query(
    'SELECT * FROM '.$this->table.' WHERE id IN (i:ids)',
    array('ids' => $ids)
)->fetchAll('id');
```

---

## 7. Fluent query builder

`waModel::select()` возвращает `waDbQuery`.

Пример:

```php
$rows = $this->select('*')
    ->where('status = s:status', array('status' => 'published'))
    ->order('datetime DESC')
    ->limit('0, 20')
    ->fetchAll('id');
```

`waDbQuery` поддерживает:

```php
select($select)
where($where, $params = null)
order($order)
limit($limit)
fetch()
fetchAssoc()
fetchField()
fetchAll($key = null, $normalize = false)
query()
getQuery()
```

Когда использовать:

| Сценарий | Подход |
|---|---|
| Простой SELECT по одной таблице | `select()->where()->fetchAll()` |
| Сложный JOIN/GROUP BY/HAVING | `query($sql, $params)` |
| Простая запись | `insert()`, `updateById()`, `deleteById()` |
| Сложное массовое обновление | `exec($sql, $params)` |

---

## 8. Паттерн официального Webasyst-кода

### 8.1. `blogPostModel`

`blogPostModel` показывает типичный app model pattern:

```php
class blogPostModel extends blogItemModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_DEADLINE = 'deadline';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';

    protected $table = 'blog_post';
}
```

Что важно:

1. Таблица задана через `$table`.
2. Статусы вынесены в constants.
3. Сложные выборки находятся в модели, а не в action.
4. Модель использует `query()`, `getWhereByField()`, `select()->where()->fetch()`.
5. Модель содержит доменные методы: `getTimeline()`, `getBlogPost()`, `search()`, `updateItem()`.

### 8.2. `blog/lib/config/db.php`

Схема приложения описывает таблицы декларативно:

```php
return array(
    'blog_post' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'blog_id' => array('int', 11, 'null' => 0, 'default' => '1'),
        'title' => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'routing' => array('status', 'url', 'blog_id'),
        ),
    ),
);
```

`db.php` должен быть источником структуры таблиц при установке, но runtime metadata в `waModel` берётся через adapter schema/describe и кэшируется.

### 8.3. `waAppSettingsModel`

Системная модель настроек показывает отдельный pattern:

- таблица `wa_app_settings`;
- собственный runtime/file cache;
- методы `get()`, `set()`, `del()`;
- `multipleInsert()` для upsert-like сохранения настроек.

Настройки приложения не нужно хранить в своей таблице, если подходит `waAppSettingsModel`.

---

## 9. Минимальная реализация модели

Задача: таблица заказов приложения `myapp`.

### 9.1. `db.php`

```php
<?php

return array(
    'myapp_order' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'status' => array('varchar', 32, 'null' => 0, 'default' => 'new'),
        'total' => array('decimal', '15,4', 'null' => 0, 'default' => '0.0000'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact' => 'contact_id',
            'status' => 'status',
        ),
    ),
);
```

Файл:

```text
wa-apps/myapp/lib/config/db.php
```

### 9.2. Model

```php
<?php

class myappOrderModel extends waModel
{
    const STATUS_NEW = 'new';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELED = 'canceled';

    protected $table = 'myapp_order';

    public function getRecent($limit = 50)
    {
        $limit = max(1, min(500, (int) $limit));

        return $this->select('*')
            ->order('id DESC')
            ->limit($limit)
            ->fetchAll('id');
    }

    public function getByContact($contact_id)
    {
        return $this->select('*')
            ->where('contact_id = i:contact_id', array('contact_id' => $contact_id))
            ->order('id DESC')
            ->fetchAll('id');
    }

    public function createOrder($data)
    {
        $data['status'] = ifempty($data, 'status', self::STATUS_NEW);
        $data['create_datetime'] = date('Y-m-d H:i:s');
        $data['update_datetime'] = null;

        return $this->insert($data);
    }

    public function updateStatus($id, $status)
    {
        if (!in_array($status, array(self::STATUS_NEW, self::STATUS_PAID, self::STATUS_CANCELED))) {
            throw new waException('Invalid order status');
        }

        return $this->updateById($id, array(
            'status' => $status,
            'update_datetime' => date('Y-m-d H:i:s'),
        ));
    }
}
```

Файл:

```text
wa-apps/myapp/lib/models/myappOrder.model.php
```

### 9.3. Action usage

```php
<?php

class myappOrdersAction extends waViewAction
{
    public function execute()
    {
        $model = new myappOrderModel();
        $orders = $model->getRecent(50);

        $this->view->assign('orders', $orders);
    }
}
```

Шаблон только выводит `$orders`; SQL в Smarty не пишется.

---

## 10. Расширенная реализация: model + service

Если операция сложная и затрагивает несколько таблиц, action не должен превращаться в transaction script.

Плохой вариант:

```php
class myappOrdersPayController extends waController
{
    public function execute()
    {
        // много SQL, статусы, логирование, события, проверки
    }
}
```

Лучше:

```php
class myappOrderPaymentService
{
    public function pay($order_id, $contact_id)
    {
        $order_model = new myappOrderModel();
        $order = $order_model->getById($order_id);

        if (!$order) {
            throw new waException('Order not found', 404);
        }

        if ($order['status'] !== myappOrderModel::STATUS_NEW) {
            throw new waException('Invalid order status');
        }

        $order_model->updateById($order_id, array(
            'status' => myappOrderModel::STATUS_PAID,
            'update_datetime' => date('Y-m-d H:i:s'),
        ));

        return $order_model->getById($order_id);
    }
}
```

Controller остаётся thin:

```php
class myappOrdersPayController extends waController
{
    public function execute()
    {
        $this->checkRights();

        $order_id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if ($order_id <= 0) {
            throw new waException('Order not found', 404);
        }

        $service = new myappOrderPaymentService();
        $service->pay($order_id, $this->getUserId());

        $this->redirect(wa()->getAppUrl('myapp').'orders/');
    }

    protected function checkRights()
    {
        if (!$this->getRights('orders')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
```

---

## 11. `db.php`, install, uninstall, updates

### 11.1. `db.php`

`db.php` описывает таблицы приложения. При установке `waAppConfig::install()` вызывает:

```php
$file_db = $this->getAppPath('lib/config/db.php');
if (file_exists($file_db)) {
    $schema = include($file_db);
    $model = new waModel();
    $model->createSchema($schema);
}
```

Правила:

- таблицы приложения начинаются с `{app_id}_`;
- таблицы plugin-а обычно начинаются с `{app_id}_{plugin_id}_` или другого принятого в app стиля;
- primary key задаётся в `:keys`;
- индексы задаются в `:keys`;
- `db.php` не должен выполнять SQL сам — он возвращает array schema.

### 11.2. `install.php`

Используется для дополнительной логики:

```text
wa-apps/{app_id}/lib/config/install.php
```

Примеры задач:

- записать начальные настройки через `waAppSettingsModel`;
- заполнить справочники;
- создать дефолтные данные;
- выполнить app-specific initialization.

### 11.3. `uninstall.php`

Используется для дополнительной очистки:

```text
wa-apps/{app_id}/lib/config/uninstall.php
```

Базовое удаление таблиц из `db.php` делает `waAppConfig::uninstall()`, но если приложение создавало дополнительные данные, их нужно чистить явно.

### 11.4. Updates

Update-файлы лежат здесь:

```text
wa-apps/{app_id}/lib/updates/{timestamp}.php
```

Пример:

```php
<?php

$model = new waModel();
$model->exec('ALTER TABLE `myapp_order` ADD `comment` TEXT NULL');

$order_model = new myappOrderModel();
$order_model->clearMetadataCache();
```

Правила update-файлов:

1. Файл должен быть идемпотентным или безопасным при уже применённой структуре.
2. После изменения структуры таблицы нужно учитывать metadata cache.
3. Не выполнять тяжёлые миграции без необходимости в одном HTTP-запросе.
4. Не удалять пользовательские данные без явной причины.

---

## 12. Cache в моделях

`waModel` поддерживает:

```php
setCache(?waiCache $cache = null)
addCacheCleaner(waiCache $cache)
```

`setCache()` применим к SELECT-запросу:

```php
$cache = new waVarExportCache('myapp/recent_orders', 600, 'myapp');
$this->setCache($cache);
$orders = $this->query('SELECT * FROM '.$this->table.' ORDER BY id DESC LIMIT 50')->fetchAll('id');
```

`addCacheCleaner()` регистрирует cache, который нужно очистить после insert/update/delete:

```php
$cache = new waVarExportCache('myapp/recent_orders', 600, 'myapp');
$this->addCacheCleaner($cache);
$this->insert($data);
```

Практически чаще применяют app/service-level cache, но важно знать системный механизм.

---

## 13. Что должно быть в модели, а что нет

### 13.1. В модели должно быть

- имя таблицы;
- constants статусов/типов;
- CRUD-методы предметной области;
- сложные SELECT-запросы;
- подготовка данных для view, если это data-level preparation;
- проверки консистентности данных;
- методы для массовых операций;
- SQL, если без него нельзя.

### 13.2. В модели не должно быть

- HTML;
- Smarty;
- прямой работы с `waRequest`, кроме очень редких системных моделей;
- frontend/backend redirect;
- UI-логики;
- проверки “показать кнопку или нет”;
- хардкода текущего URL/route;
- бизнес-процесса на много сущностей, если лучше вынести в service.

### 13.3. В action/controller должно быть

- чтение request;
- типизация input;
- проверка прав;
- CSRF, если это изменяющий POST и системная проверка не покрывает сценарий;
- вызов модели/сервиса;
- assign/redirect/JSON response.

### 13.4. В template должно быть

- вывод данных;
- простые условия отображения;
- циклы по уже подготовленным массивам;
- escaping.

---

## 14. Типовые ошибки

### Ошибка 1. SQL в Smarty

Неправильно:

```smarty
{php}
$model = new myappOrderModel();
{/php}
```

Правильно:

```php
$orders = $model->getRecent();
$this->view->assign('orders', $orders);
```

### Ошибка 2. SQL в action вместо модели

Неправильно:

```php
$model = new waModel();
$orders = $model->query('SELECT * FROM myapp_order')->fetchAll();
```

Правильно:

```php
$model = new myappOrderModel();
$orders = $model->getRecent();
```

### Ошибка 3. Конкатенация пользовательского input в SQL

Неправильно:

```php
$sql = "SELECT * FROM {$this->table} WHERE id = {$id}";
```

Правильно:

```php
$this->query(
    'SELECT * FROM '.$this->table.' WHERE id = i:id',
    array('id' => $id)
)->fetch();
```

### Ошибка 4. Не задавать `$table`

Неправильно:

```php
class myappOrderModel extends waModel
{
}
```

Правильно:

```php
class myappOrderModel extends waModel
{
    protected $table = 'myapp_order';
}
```

### Ошибка 5. Назвать файл не по autoload convention

Класс:

```php
myappOrderModel
```

Файл:

```text
wa-apps/myapp/lib/models/myappOrder.model.php
```

Ошибочно:

```text
OrderModel.php
myappOrdersModel.php
order.model.php
```

### Ошибка 6. Изменить таблицу и забыть metadata cache

После ALTER TABLE модель может не увидеть новое поле, пока metadata cache не обновится.

В update-файле:

```php
$model = new waModel();
$model->exec('ALTER TABLE `myapp_order` ADD `comment` TEXT NULL');

$order_model = new myappOrderModel();
$order_model->clearMetadataCache();
```

### Ошибка 7. Использовать `waAppSettingsModel` для больших данных

`waAppSettingsModel` подходит для настроек, но не для сущностей вроде заказов, товаров, логов, связей many-to-many.

### Ошибка 8. Не проверять права при записи

Модель может обновить данные, но controller обязан проверить, имеет ли пользователь право на операцию.

### Ошибка 9. Считать `insert()` полной валидацией

`insert()` фильтрует неизвестные поля и приводит значения к типам, но не заменяет бизнес-валидацию.

---

## 15. Чеклист разработчика

### Model class

- [ ] Класс лежит в `wa-apps/{app_id}/lib/models/`.
- [ ] Имя файла соответствует классу: `{app}{Name}.model.php`.
- [ ] Класс называется `{app}{Name}Model`.
- [ ] Класс наследует `waModel` или app-specific base model.
- [ ] Указан `$table`.
- [ ] Если primary key не `id`, указан `$id`.
- [ ] Статусы/типы вынесены в constants.
- [ ] Сложные SQL-запросы не дублируются в actions.

### SQL safety

- [ ] Пользовательский input не конкатенируется в SQL.
- [ ] Используются placeholders `i:`, `s:`, `b:`, `f:`, `l:`.
- [ ] Для `IN (...)` используются массивы с typed placeholders.
- [ ] Для LIKE учтено экранирование `%` и `_`.
- [ ] Для простых CRUD используются helpers `insert/update/delete/getBy...`.

### Schema

- [ ] Таблица описана в `lib/config/db.php`.
- [ ] Имя таблицы начинается с `{app_id}_`.
- [ ] `:keys` содержит primary key и нужные индексы.
- [ ] Update-файлы лежат в `lib/updates/*.php`.
- [ ] После ALTER TABLE учтён metadata cache.
- [ ] Нет опасного удаления пользовательских данных.

### Architecture

- [ ] Controller/action не содержит большой SQL.
- [ ] Template не содержит SQL и PHP.
- [ ] Service используется только если операция действительно выходит за одну модель.
- [ ] Права и CSRF проверены в изменяющих сценариях.
- [ ] Модель не зависит от frontend/backend UI.

---

## 16. Чеклист ИИ-агента

Перед ответом на задачу по моделям и БД ИИ-агент обязан:

1. Открыть `wa-apps/{app_id}/lib/config/app.php`.
2. Открыть `wa-apps/{app_id}/lib/config/db.php`.
3. Найти существующую модель в `lib/models/`.
4. Проверить `$table` и `$id`.
5. Проверить, есть ли app-specific base model.
6. Найти похожий метод в официальном или текущем app-коде.
7. Проверить, где вызывается модель: action/controller/service.
8. Проверить request source: GET, POST, route params.
9. Проверить права и CSRF для write operations.
10. Проверить, не нужен ли update-файл для изменения схемы.
11. Проверить naming файла и класса.
12. Только потом писать SQL или model method.

ИИ-агенту запрещено:

- писать SQL в Smarty;
- предлагать raw PDO/MySQLi вместо `waModel`, если нет веской причины;
- конкатенировать input в SQL;
- придумывать таблицу без проверки `db.php`;
- писать новую модель, если подходящая уже есть;
- делать update схемы без `lib/updates/*.php`;
- хранить большие сущности в `waAppSettingsModel`;
- утверждать, что поле существует, если не проверен `db.php` или metadata/модель.

---

## 17. Мини-сводка

Правильная работа с БД в Webasyst строится вокруг `waModel`:

```text
lib/config/db.php
→ waAppConfig::install()
→ waModel::createSchema()
→ {app}{Name}Model extends waModel
→ protected $table = '{app}_table'
→ model methods
→ action/controller/service
→ view assign
→ Smarty output
```

Главное правило:

```text
SQL живёт в модели или сервисе, request — в action/controller, HTML — в Smarty.
```

Если это правило нарушается, приложение быстро получает дублирование, SQL injection risk, неуправляемые templates и сложные для поддержки actions.
