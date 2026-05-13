# 14. Права доступа в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить права доступа Webasyst как рабочий механизм: декларация приложения → конфигурация формы прав → хранение значений → runtime-проверка → plugin rights.

---

## 1. Назначение механизма

Права доступа в Webasyst решают две разные задачи:

1. **Настройка доступа** — администратор видит форму прав приложения или плагина и задаёт, что разрешено пользователю или группе.
2. **Runtime-проверка доступа** — backend/frontend code проверяет права перед выполнением действия, показом UI-блока, сохранением, удалением или выполнением бизнес-операции.

Важно: наличие пункта в UI или скрытие кнопки не является защитой. Защита возникает только тогда, когда action/controller/model/service проверяет права на сервере.

Минимальная цепочка:

```text
wa-apps/{app_id}/lib/config/app.php
→ 'rights' => true
→ {app_id}RightConfig extends waRightConfig
→ addItem(...)
→ contacts/access UI показывает форму прав
→ значения сохраняются в системном хранилище прав
→ runtime code вызывает getRights() / wa()->getUser()->getRights(...)
→ waFrontController/checkRights/controller/model допускает или запрещает действие
```

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/config/waRightConfig.class.php` | Базовый класс конфигурации прав приложения. |
| `wa-system/controller/waFrontController.class.php` | Перед запуском app controller вызывает `checkRights($module, $action)`. |
| `wa-system/webasyst/lib/models/waContactRights.model.php` | Системное хранилище прав контактов и групп. |
| `wa-system/plugin/waPlugin.class.php` | Plugin rights, `rightsConfig()`, `getRights()`. |
| `wa-system/waSystem.class.php` | Доступ к текущему пользователю через `wa()->getUser()`. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | Включает поддержку прав через `'rights' => true`. |
| `wa-apps/{app_id}/lib/config/{app_id}RightConfig.class.php` | Описывает права приложения через `addItem()`. |
| `wa-apps/{app_id}/lib/config/{app_id}Config.class.php` | Может переопределять `checkRights($module, $action)`. |
| `wa-apps/{app_id}/lib/actions/...` | Проверяет права в actions/controllers. |
| `wa-apps/{app_id}/lib/models/...` | Может проверять права в write-операциях, если логика чувствительная. |
| `wa-apps/{app_id}/templates/...` | Может скрывать кнопки, но не должен быть единственным уровнем защиты. |

### 2.3. Файлы плагина

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Включает plugin rights через `'rights' => true`. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/{app}{Plugin}Plugin.php` | Может переопределить `rightsConfig()` и использовать `getRights()`. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/...` | Проверяет plugin/app права в backend actions. |

---

## 3. Системная цепочка выполнения

### 3.1. Включение прав в `app.php`

Приложение объявляет поддержку прав:

```php
<?php

return array(
    'name'   => 'My app',
    'rights' => true,
);
```

После этого Webasyst ожидает app-specific конфигурацию прав:

```text
wa-apps/myapp/lib/config/myappRightConfig.class.php
```

Класс:

```php
<?php

class myappRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('settings', _w('Can manage settings'));
    }
}
```

### 3.2. Построение формы прав

`waRightConfig` создаётся для приложения. В `init()` приложение добавляет права через `addItem()`:

```php
$this->addItem('orders', _w('Can manage orders'));
$this->addItem('settings', _w('Can manage settings'));
```

Затем системная UI-форма прав вызывает:

```php
getHTML($rights, $inherited)
```

или для UI 2.0:

```php
getUI20HTML($rights, $inherited)
```

### 3.3. Runtime-проверка перед запуском controller

`waFrontController::execute()` перед поиском controller/action проверяет права приложения:

```php
if (!$plugin && !$this->system->getConfig()->checkRights($module, $action)) {
    throw new waRightsException(_ws("Access denied."));
}
```

Это app-level gate. Он полезен, но его недостаточно для всех операций, потому что:

- один module может содержать разные actions с разной опасностью;
- один action может выполнять разные операции по POST;
- модель или сервис может быть вызван из разных мест;
- UI может быть скрыт, но endpoint всё равно доступен напрямую.

### 3.4. Проверка внутри controller/action

Для точечной проверки внутри controller/action используется:

```php
if (!$this->getRights('settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

или напрямую:

```php
if (!wa()->getUser()->getRights('myapp', 'settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

---

## 4. Ключевые классы и методы

## 4.1. `waRightConfig`

Базовый класс для описания формы прав приложения.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `init()` | Переопределяется в приложении; здесь добавляются права. |
| `addItem($name, $label, $type, $params)` | Добавляет право в форму. |
| `getItems()` | Возвращает список добавленных items. |
| `getHTML($rights, $inherited)` | Генерирует HTML для UI 1.3. |
| `getUI20HTML($rights, $inherited)` | Генерирует HTML для UI 2.0. |
| `getRights($contact_id)` | Hook для custom-хранилища прав. |
| `setRights($contact_id, $right, $value)` | Hook для сохранения custom-прав. |
| `clearRights($contact_id)` | Hook для очистки custom-прав. |
| `getDefaultRights($contact_id)` | Дефолтные права при выдаче доступа. |
| `setDefaultRights($contact_id)` | Установка дефолтных прав. |

### 4.2. `waController::getRights()`

Внутри controller/action можно вызывать:

```php
$this->getRights('settings')
```

Это сокращение к правам текущего пользователя для текущего приложения.

### 4.3. `waUser::getRights()`

Для явной проверки:

```php
wa()->getUser()->getRights('shop', 'orders')
```

или:

```php
wa()->getUser()->getRights('shop', 'plugin.redirect')
```

### 4.4. `waPlugin::getRights()`

Внутри plugin class:

```php
$this->getRights()
$this->getRights('settings')
```

Системно plugin rights names имеют вид:

```text
plugin.{plugin_id}
plugin.{plugin_id}.{right_name}
```

### 4.5. `{app_id}Config::checkRights($module, $action)`

Приложение может переопределить `checkRights()` в `{app_id}Config.class.php`, чтобы централизованно связать backend modules/actions с правами.

Примерная задача:

```text
module=orders      → право orders
module=settings    → право settings
module=reports     → право reports
```

Но даже при наличии `checkRights()` опасные save/delete endpoints должны дополнительно проверять права внутри action/controller/service.

---

## 5. Типы controls в `waRightConfig`

### 5.1. `checkbox`

Один простой флаг.

```php
$this->addItem('settings', _w('Can manage settings'));
```

или явно:

```php
$this->addItem('settings', _w('Can manage settings'), 'checkbox');
```

Значение обычно читается как truthy/falsy:

```php
if (!wa()->getUser()->getRights('myapp', 'settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### 5.2. `select`

Одно право с несколькими уровнями.

```php
$this->addItem('orders', _w('Can manage orders'), 'select', array(
    'options' => array(
        0   => _w('No access'),
        1   => _w('Limited access'),
        100 => _w('Full access'),
    ),
));
```

Проверка:

```php
$level = wa()->getUser()->getRights('myapp', 'orders');

if ($level < 100) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### 5.3. `list`

Группа checkbox-прав.

```php
$this->addItem('workflow_actions', _w('Can perform actions'), 'list', array(
    'items' => array(
        'process' => _w('Process'),
        'delete'  => _w('Delete'),
    ),
    'hint1' => 'all_checkbox',
));
```

Проверка:

```php
if (!wa()->getUser()->getRights('myapp', 'workflow_actions.delete')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### 5.4. `selectlist`

Группа элементов, у каждого из которых свой уровень доступа.

Пример: права на типы товаров или блоги.

```php
$this->addItem('type', _w('Product types'), 'selectlist', array(
    'items' => $types,
    'options' => array(
        0 => _w('Read only'),
        1 => _w('Edit'),
        2 => _w('Full access'),
    ),
    'hint1' => 'all_select',
));
```

Проверка:

```php
$type_right = wa()->getUser()->getRights('shop', 'type.'.$type_id);

if ($type_right < shopRightConfig::RIGHT_EDIT) {
    throw new waRightsException(_ws('Access denied.'));
}
```

---

## 6. Паттерн официального Webasyst-кода

## 6.1. Shop-Script: многоуровневые права

`shopRightConfig` показывает большой app-first пример:

- `orders` через `select`;
- простые checkbox-права: `customers`, `products`, `services`, `settings`;
- `type` через `selectlist`;
- `workflow_actions` через `list`;
- событие `rights.config` для расширения прав plugin-ами.

Смысл: права Shop-Script не сводятся к `backend=true`. Они описывают конкретные области ответственности.

### Runtime-связка в `shopConfig::checkRights()`

`shopConfig::checkRights($module, $action)` связывает backend module/action с правами:

```text
order/coupons/workflow → orders
marketing*             → marketing
reports*               → reports
settings*              → settings
service*               → services
customers*             → customers
prod sets/categories   → setscategories
```

Это central gate на уровне app config.

### Дополнительная проверка в коде

В сложных операциях Shop-Script проверяет права глубже: в actions, services, workflow, model-level helpers. Это нужно, потому что один общий module-level gate не всегда знает конкретный объект, тип товара, workflow action или состояние заказа.

---

## 6.2. Blog: права на сущности

`blogRightConfig` показывает другой pattern:

- общие права: `add_blog`, `pages`, `design`;
- права на каждый blog через `selectlist`;
- уровни: no access, read only, read/write, full.

Это пример object-level access:

```text
blog.{blog_id} → уровень доступа к конкретному блогу
```

Такой подход нужен, когда доступ зависит не только от типа операции, но и от конкретной сущности.

---

## 6.3. Plugin rights

Plugin может включить права в `plugin.php`:

```php
<?php

return array(
    'name'   => 'My plugin',
    'rights' => true,
);
```

По умолчанию `waPlugin::rightsConfig()` добавляет checkbox:

```php
plugin.{plugin_id}
```

Если plugin-у нужны под-права, он может переопределить `rightsConfig()`:

```php
public function rightsConfig(waRightConfig $rights_config)
{
    $rights_config->addItem('plugin.'.$this->id, $this->info['name'], 'checkbox');
    $rights_config->addItem('plugin.'.$this->id.'.settings', _wp('Can manage settings'), 'checkbox');
}
```

Проверка:

```php
if (!$this->getRights('settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

---

## 7. Минимальная реализация: права приложения

### 7.1. `app.php`

```php
<?php

return array(
    'name'    => 'My app',
    'version' => '1.0.0',
    'vendor'  => 'myvendor',
    'rights'  => true,
    'csrf'    => true,
);
```

Файл:

```text
wa-apps/myapp/lib/config/app.php
```

### 7.2. `myappRightConfig.class.php`

```php
<?php

class myappRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('orders', _w('Can manage orders'));
        $this->addItem('settings', _w('Can manage settings'));
    }
}
```

Файл:

```text
wa-apps/myapp/lib/config/myappRightConfig.class.php
```

### 7.3. Проверка в action

```php
<?php

class myappSettingsSaveController extends waController
{
    public function execute()
    {
        if (!$this->getRights('settings')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);

        $model = new waAppSettingsModel();
        $model->set($this->getAppId(), 'name', $name);

        $this->redirect(wa()->getAppUrl($this->getAppId()).'?module=settings');
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/settings/myappSettingsSave.controller.php
```

---

## 8. Расширенная реализация: `checkRights()` в app config

Если приложение имеет много backend modules, можно централизовать module-level gate.

```php
<?php

class myappConfig extends waAppConfig
{
    public function checkRights($module, $action)
    {
        if ($module == 'settings') {
            return wa()->getUser()->getRights($this->getApplication(), 'settings');
        }

        if ($module == 'orders') {
            return wa()->getUser()->getRights($this->getApplication(), 'orders');
        }

        return true;
    }
}
```

Файл:

```text
wa-apps/myapp/lib/config/myappConfig.class.php
```

Но это не отменяет точечные проверки внутри save/delete operations.

---

## 9. Расширенная реализация: object-level rights

Если приложение управляет сущностями, доступ может зависеть от конкретного ID.

Пример pattern:

```php
$catalog_id = waRequest::param('catalog_id', 0, waRequest::TYPE_INT);
$right = wa()->getUser()->getRights('myapp', 'catalog.'.$catalog_id);

if (!$right) {
    throw new waRightsException(_ws('Access denied.'));
}
```

Соответствующая форма прав:

```php
$this->addItem('catalog', _w('Catalogs'), 'selectlist', array(
    'items' => $catalogs,
    'options' => array(
        0 => _w('No access'),
        1 => _w('Read only'),
        2 => _w('Full access'),
    ),
    'hint1' => 'all_select',
));
```

В таком подходе важно:

- не проверять только общий `backend`;
- не доверять ID из URL без проверки;
- не показывать объект в списке, если нет права;
- не разрешать save/delete только потому, что форма была скрыта.

---

## 10. Plugin rights skeleton

### 10.1. `plugin.php`

```php
<?php

return array(
    'name'    => 'My plugin',
    'version' => '1.0.0',
    'vendor'  => 'myvendor',
    'rights'  => true,
);
```

Файл:

```text
wa-apps/shop/plugins/myplugin/lib/config/plugin.php
```

### 10.2. Main plugin class

```php
<?php

class shopMypluginPlugin extends shopPlugin
{
    public function rightsConfig(waRightConfig $rights_config)
    {
        $rights_config->addItem('plugin.'.$this->id, $this->info['name'], 'checkbox');
        $rights_config->addItem('plugin.'.$this->id.'.settings', _wp('Can manage settings'), 'checkbox');
    }
}
```

Файл:

```text
wa-apps/shop/plugins/myplugin/lib/shopMyplugin.plugin.php
```

### 10.3. Backend plugin action

```php
<?php

class shopMypluginPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('myplugin');

        if (!$plugin->getRights('settings')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        $this->view->assign('settings', $plugin->getSettings());
    }
}
```

Файл:

```text
wa-apps/shop/plugins/myplugin/lib/actions/settings/shopMypluginPluginSettings.action.php
```

---

## 11. UI 1.3 и UI 2.0

`waRightConfig` содержит две ветки генерации HTML:

```php
getHTML()
getUI20HTML()
```

Практическое правило:

- приложение описывает права через `addItem()` один раз;
- системный UI выбирает нужный HTML renderer;
- custom HTML прав нужен редко;
- если custom renderer всё же нужен, он должен учитывать обе UI-версии, если приложение поддерживает `ui => '1.3,2.0'`.

Ошибочный подход:

```text
сгенерировать HTML прав только под старую таблицу UI 1.3 в приложении, которое заявляет UI 2.0
```

Правильный подход:

```text
использовать addItem() и дать waRightConfig построить HTML самостоятельно
```

---

## 12. Где проверять права

### 12.1. В UI

Можно скрывать кнопки:

```smarty
{if $wa->userRights('settings')}
    <a href="{$wa_app_url}?module=settings">Settings</a>
{/if}
```

Но это только UX.

### 12.2. В controller/action

Обязательно проверять перед изменением данных:

```php
if (!$this->getRights('settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### 12.3. В service/model layer

Нужно проверять, если метод может быть вызван из разных endpoints.

```php
class myappOrderDeleteService
{
    public function delete($order_id)
    {
        if (!wa()->getUser()->getRights('myapp', 'orders')) {
            throw new waRightsException(_ws('Access denied.'));
        }

        $model = new myappOrderModel();
        $model->deleteById($order_id);
    }
}
```

### 12.4. В `checkRights()`

Полезно для module-level gate, но не заменяет object-level checks.

---

## 13. Типовые ошибки

### Ошибка 1. Считать `'rights' => true` полной защитой

Неправильно:

```text
app.php содержит rights=true, значит endpoint защищён
```

Правильно:

```text
rights=true включает механизм настройки прав. Runtime-код всё равно должен проверять права.
```

### Ошибка 2. Проверять права только в Smarty

Неправильно:

```smarty
{if $wa->userRights('settings')}
    <button>Save</button>
{/if}
```

и больше нигде.

Правильно:

```php
if (!$this->getRights('settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### Ошибка 3. Использовать одно право `backend` для всех операций

Неправильно:

```php
if (!wa()->getUser()->getRights('myapp', 'backend')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

для удаления, настроек, импорта, финансовых операций.

Правильно:

```php
if (!wa()->getUser()->getRights('myapp', 'settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### Ошибка 4. Не проверять object-level access

Неправильно:

```php
$blog_id = waRequest::post('blog_id', 0, waRequest::TYPE_INT);
$model->insert(array('blog_id' => $blog_id, ...));
```

Правильно:

```php
$right = wa()->getUser()->getRights('blog', 'blog.'.$blog_id);

if ($right < blogRightConfig::RIGHT_READ_WRITE) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### Ошибка 5. Давать plugin backend action без plugin rights

Неправильно:

```php
class shopMypluginPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        // settings shown to any backend user with app access
    }
}
```

Правильно:

```php
$plugin = wa('shop')->getPlugin('myplugin');

if (!$plugin->getRights('settings')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

### Ошибка 6. Путать group inherited rights и personal rights

Webasyst учитывает наследование от групп. Код проверки должен использовать системный `getRights()`, а не читать только личные записи напрямую из таблицы прав.

### Ошибка 7. Дублировать логику прав в JS

JS может улучшить UX, но не является уровнем безопасности.

---

## 14. Чеклист разработчика

Перед commit проверить:

### App config

- [ ] В `app.php` есть `'rights' => true`, если приложение требует управляемые права.
- [ ] Создан `{app_id}RightConfig.class.php`, если есть custom-права.
- [ ] Имена прав стабильные и не завязаны на тексты UI.
- [ ] Для object-level прав выбран понятный prefix: `blog.{id}`, `type.{id}`, `catalog.{id}`.

### Rights config

- [ ] `init()` использует `addItem()`.
- [ ] Для простых флагов используется `checkbox`.
- [ ] Для уровней доступа используется `select`.
- [ ] Для наборов флагов используется `list`.
- [ ] Для object-level уровней используется `selectlist`.
- [ ] Если приложение поддерживает UI 2.0, не написан UI 1.3-only custom HTML.

### Runtime checks

- [ ] Backend save/delete/import/export actions проверяют права на сервере.
- [ ] Object-level operations проверяют права на конкретный объект.
- [ ] `checkRights()` не заменяет точечные проверки для опасных операций.
- [ ] Model/service methods не позволяют обойти проверку через другой endpoint.
- [ ] UI скрывает недоступные кнопки, но это не единственный уровень защиты.

### Plugin rights

- [ ] В `plugin.php` есть `'rights' => true`, если plugin имеет управляемые backend endpoints.
- [ ] Plugin settings/action проверяет `plugin.{plugin_id}` или под-права.
- [ ] `rightsConfig()` не ломает app rights config.
- [ ] Plugin не выдаёт себе права через `backend_rights` без строгой причины.

---

## 15. Чеклист ИИ-агента

Перед ответом на задачу по правам ИИ-агент обязан:

1. Открыть `wa-apps/{app_id}/lib/config/app.php`.
2. Проверить, включено ли `'rights' => true`.
3. Открыть `{app_id}RightConfig.class.php`, если он есть.
4. Открыть `{app_id}Config.class.php`, если он есть, и проверить `checkRights()`.
5. Найти текущий action/controller, который выполняет операцию.
6. Проверить, есть ли серверная проверка прав перед save/delete/import/export.
7. Если операция object-level — найти, как в проекте проверяются права на объект.
8. Если задача касается plugin-а — открыть `plugin.php` и main plugin class.
9. Проверить, используются ли plugin rights: `plugin.{plugin_id}`.
10. Проверить UI 1.3/2.0, если правится форма прав.
11. Только после этого писать код.

ИИ-агенту запрещено:

- считать скрытие кнопки в Smarty защитой;
- выдавать endpoint без проверки прав;
- использовать только `backend` как универсальное право;
- придумывать имена прав без проверки текущего `RightConfig`;
- писать custom HTML формы прав без проверки UI 1.3/2.0;
- игнорировать object-level rights;
- читать/писать таблицу прав напрямую, если есть системный API.

---

## 16. Мини-сводка

Права Webasyst состоят из двух частей:

```text
RightConfig описывает, какие права можно настроить.
Runtime-код проверяет, разрешено ли конкретное действие.
```

Правильная цепочка:

```text
app.php: rights => true
→ {app}RightConfig::init()
→ addItem(...)
→ contacts/access UI
→ системное хранилище прав
→ waFrontController/checkRights()
→ action/controller/service getRights()
→ выполнение или waRightsException
```

Plugin rights добавляются поверх app rights и используют namespace:

```text
plugin.{plugin_id}
plugin.{plugin_id}.{right_name}
```

Главное правило: **права должны проверяться на сервере там, где реально выполняется действие**.
