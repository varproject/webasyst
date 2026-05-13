# 10. Настройки и конфиги в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** разделить статическую конфигурацию приложения, пользовательские config overrides, runtime settings в БД и plugin settings, чтобы разработчик и ИИ-агент не смешивали разные уровни хранения данных.

---

## 1. Назначение механизма

В Webasyst есть несколько разных механизмов хранения конфигурационных данных. Они похожи внешне, потому что часто представлены PHP-массивами или key-value значениями, но решают разные задачи.

Главное разделение:

```text
lib/config/app.php
→ декларация возможностей приложения

lib/config/config.php
→ дефолтные технические опции приложения

wa-config/apps/{app_id}/config.php
→ пользовательское переопределение технических опций

wa_app_settings через waAppSettingsModel
→ runtime settings приложения/плагина в базе данных

plugins/{plugin_id}/lib/config/settings.php
→ декларация формы настроек plugin-а

waPlugin::getSettings()/saveSettings()
→ чтение/сохранение plugin settings через waAppSettingsModel
```

Ошибка в выборе уровня хранения приводит к плохой архитектуре:

- route/settlement начинают хранить как обычную настройку;
- runtime state попадает в `lib/config/*.php`;
- plugin settings пишутся вручную в `wa_app_settings` в обход `waPlugin`;
- пользовательские настройки кладутся в `wa-apps`, хотя этот каталог должен оставаться кодом приложения;
- security-sensitive значения оказываются в шаблонах или frontend JS.

Цель этой главы — дать рабочую схему выбора места хранения.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/config/waAppConfig.class.php` | Загружает `app.php`, дефолтный `config.php`, пользовательский `wa-config/apps/{app}/config.php`, routing, cron, install/update. |
| `wa-system/webasyst/lib/models/waAppSettings.model.php` | Key-value модель настроек приложений и плагинов в таблице `wa_app_settings`. |
| `wa-system/plugin/waPlugin.class.php` | Plugin lifecycle, `getSettings()`, `saveSettings()`, `getControls()`, settings config, install/update/uninstall. |
| `wa-system/helper/waHtmlControl.class.php` | Системные controls для генерации HTML-полей настроек. Используется через `waHtmlControl::getControl()`. |
| `wa-system/controller/waDispatch.class.php` | Проверяет `csrf` и frontend/backend access с учётом app config. |
| `wa-system/routing/waRouting.class.php` | Использует route params и app routes; routing не должен подменяться settings. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | App info и декларация возможностей: `frontend`, `plugins`, `themes`, `pages`, `rights`, `csrf`, `ui`. |
| `wa-apps/{app_id}/lib/config/config.php` | Дефолтные технические опции приложения. |
| `wa-config/apps/{app_id}/config.php` | Пользовательские overrides опций приложения. |
| `wa-apps/{app_id}/lib/config/routing.php` | Frontend routes приложения. |
| `wa-apps/{app_id}/lib/config/routing.backend.php` | Backend routes приложения. |
| `wa-apps/{app_id}/lib/config/db.php` | Схема таблиц приложения. |
| `wa-apps/{app_id}/lib/config/install.php` | Дополнительная install-логика. |
| `wa-apps/{app_id}/lib/config/uninstall.php` | Дополнительная uninstall-логика. |
| `wa-apps/{app_id}/lib/updates/*.php` | Миграции схемы и данных. |

### 2.3. Файлы плагина

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Plugin info, handlers, rights, settings flags. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php` | Декларация controls и default values для настроек plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/db.php` | Таблицы plugin-а, если они нужны. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/install.php` | Дополнительная install-логика plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/uninstall.php` | Дополнительная uninstall-логика plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/updates/*.php` | Plugin migrations. |

---

## 3. Системная цепочка выполнения

### 3.1. Загрузка app config

При инициализации приложения Webasyst создаёт app config:

```text
waSystem::getInstance($app_id)
→ SystemConfig::getAppConfig($app_id, $env)
→ new {app_id}Config(...) или new waAppConfig(...)
→ waAppConfig::__construct()
→ waSystemConfig::__construct()
→ configure()
→ init()
```

Внутри `waAppConfig::init()` происходит несколько важных действий:

```text
1. include wa-apps/{app_id}/lib/config/config.php, если файл есть
2. include wa-config/apps/{app_id}/config.php, если файл есть
3. include wa-apps/{app_id}/lib/config/app.php
4. register app/plugin/widget/api classes in autoload
5. include lib/config/factories.php, если файл есть
```

Смысл:

- `config.php` — опции приложения;
- `wa-config/apps/{app_id}/config.php` — пользовательские переопределения этих опций;
- `app.php` — app info, capabilities и системные флаги;
- `factories.php` — редкое переопределение системных factories.

### 3.2. Чтение app options

В коде приложения app options читаются через app config:

```php
$value = wa()->getConfig()->getOption('option_name');
```

или из controller/action:

```php
$value = $this->getConfig()->getOption('option_name');
```

Это применимо к данным, которые были загружены из:

```text
wa-apps/{app_id}/lib/config/config.php
wa-config/apps/{app_id}/config.php
```

Но это не то же самое, что settings из `wa_app_settings`.

### 3.3. Чтение runtime settings приложения

Runtime settings приложения хранятся в таблице:

```text
wa_app_settings
```

Системная модель:

```php
$model = new waAppSettingsModel();
$value = $model->get('myapp', 'setting_name', $default);
```

Запись:

```php
$model = new waAppSettingsModel();
$model->set('myapp', 'setting_name', $value);
```

Удаление:

```php
$model = new waAppSettingsModel();
$model->del('myapp', 'setting_name');
```

Все settings одного app:

```php
$settings = $model->get('myapp');
```

### 3.4. Чтение plugin settings

Plugin settings хранятся в той же `wa_app_settings`, но под составным app key:

```text
{app_id}.{plugin_id}
```

Для plugin-а не нужно вручную собирать этот ключ. Стандартный путь:

```php
$plugin = wa('shop')->getPlugin('myplugin');
$value = $plugin->getSettings('setting_name');
```

Сохранение внутри plugin-а:

```php
$this->saveSettings($settings);
```

`waPlugin` сам:

- читает `plugins/{plugin_id}/lib/config/settings.php`;
- подставляет default values;
- декодирует JSON для массивов;
- сохраняет массивы как JSON;
- хранит значения через `waAppSettingsModel`;
- использует ключ `array($app_id, $plugin_id)`.

---

## 4. Ключевые классы и методы

### 4.1. `waAppConfig`

`waAppConfig` отвечает за конфигурацию приложения.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `init()` | Загружает app config files, app info, autoload classes, factories. |
| `getOption($name = null)` | Возвращает app options из `config.php`/`wa-config/apps/{app}/config.php`. |
| `getInfo($name = null)` | Возвращает значения из `app.php`. |
| `getRouting($route = array())` | Возвращает routing rules приложения. |
| `getRoutingPath($type)` | Выбирает `routing.php` или `routing.backend.php`, сначала из `wa-config`, затем из `wa-apps`. |
| `getAppConfigPath($name)` | Возвращает путь к `lib/config/{name}.php`. |
| `install()` | Создаёт схему из `db.php`, включает `install.php`. |
| `uninstall()` | Включает `uninstall.php`, удаляет таблицы из `db.php`, чистит settings/rights/log/cache. |

### 4.2. `waAppSettingsModel`

`waAppSettingsModel` — системная модель key-value настроек.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `get($app_id, $name = null, $default = '')` | Читает одно значение или все settings приложения/plugin-а. |
| `set($app_id, $name, $value)` | Сохраняет значение через upsert. |
| `del($app_id, $name = null)` | Удаляет одно значение или все settings по ключу app/plugin. |
| `clearCache($app_id, $only_file = false)` | Очищает cache settings. |

Особенность ключей:

```php
$model->get('shop', 'currency');
$model->get(array('shop', 'myplugin'), 'enabled');
$model->get('shop.myplugin', 'enabled');
```

Для plugin-а лучше использовать `waPlugin::getSettings()`, а не напрямую `waAppSettingsModel`, если только нет системной причины.

### 4.3. `waPlugin`

`waPlugin` закрывает lifecycle и settings plugin-а.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `getSettings($name = null)` | Читает settings plugin-а, слияние БД + defaults из `settings.php`. |
| `saveSettings($settings = array())` | Сохраняет settings, обрабатывает missing checkbox/groupbox/file values. |
| `getControls($params = array())` | Генерирует HTML controls по `settings.php`. |
| `getSettingsConfig()` | Загружает `plugins/{plugin}/lib/config/settings.php`. |
| `getSettingsKey()` | Возвращает key `array($app_id, $plugin_id)` для `waAppSettingsModel`. |
| `routing($route = array())` | Возвращает plugin frontend routes из `lib/config/routing.php`. |
| `cron($param = array())` | Возвращает plugin cron rules из `lib/config/cron.php`. |
| `addJs()`, `addCss()` | Добавляет assets plugin-а в response. |

### 4.4. `waHtmlControl`

`waPlugin::getControls()` использует:

```php
waHtmlControl::getControl($row['control_type'], $name, $row);
```

`settings.php` plugin-а обычно описывает поля через параметры control-а:

```php
return array(
    'enabled' => array(
        'title'        => _wp('Enabled'),
        'description'  => _wp('Enable plugin feature.'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1,
    ),
);
```

Если нужно кастомное поле, стандартный Webasyst-подход — зарегистрировать custom control через `waHtmlControl::registerControl()` и использовать его в `settings.php`.

---

## 5. Параметры config/settings/routing/request

### 5.1. `app.php`

`app.php` содержит декларацию возможностей приложения:

```php
return array(
    'name'       => 'My app',
    'icon'       => 'img/myapp.svg',
    'version'    => '1.0.0',
    'vendor'     => 'myvendor',
    'frontend'   => true,
    'plugins'    => true,
    'themes'     => true,
    'pages'      => true,
    'rights'     => true,
    'csrf'       => true,
    'auth'       => true,
    'my_account' => true,
    'ui'         => '1.3,2.0',
);
```

Эти значения читаются как app info:

```php
$csrf = wa()->getConfig()->getInfo('csrf');
$ui = wa()->getAppInfo('myapp')['ui'];
```

Не нужно использовать `app.php` для пользовательских runtime settings.

### 5.2. `config.php`

`lib/config/config.php` — дефолтные технические опции приложения.

Пример:

```php
<?php

return array(
    'items_per_page' => 50,
    'cache_time'     => 3600,
);
```

Чтение:

```php
$items_per_page = wa()->getConfig()->getOption('items_per_page');
```

Пользователь может переопределить это в:

```text
wa-config/apps/{app_id}/config.php
```

### 5.3. `wa-config/apps/{app_id}/config.php`

Это пользовательский override, который не должен лежать в репозитории приложения как исходный код.

Цепочка приоритетов:

```text
1. wa-apps/{app_id}/lib/config/config.php
2. wa-config/apps/{app_id}/config.php
```

Если оба файла задают один ключ, второй перезаписывает первый.

### 5.4. `waAppSettingsModel`

`waAppSettingsModel` используется для данных, которые меняются через UI/backend/API/runtime:

```php
$model = new waAppSettingsModel();
$model->set('myapp', 'enabled', 1);
```

Подходит для:

- включено/выключено;
- выбранный режим;
- числовые лимиты;
- email/телефон/ключ интеграции;
- настройки UI пользователя/приложения;
- plugin settings;
- update markers вроде `update_time`.

Не подходит для:

- routing settlements;
- структуры таблиц;
- class factories;
- больших списков данных;
- пользовательского контента;
- данных, требующих поиска/индексов/связей.

### 5.5. `routing.php` и `routing.backend.php`

Routing — отдельный слой. Его нельзя подменять settings.

Frontend routing:

```text
wa-apps/{app_id}/lib/config/routing.php
```

Backend routing:

```text
wa-apps/{app_id}/lib/config/routing.backend.php
```

Route params попадают в:

```php
waRequest::param()
```

Settings не должны определять dispatch напрямую, если Webasyst уже имеет routing-механизм.

### 5.6. Request values

Request values — это входные данные конкретного запроса:

```php
$get = waRequest::get('name', '', waRequest::TYPE_STRING_TRIM);
$post = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
$param = waRequest::param('id', 0, waRequest::TYPE_INT);
```

Request values не становятся settings автоматически. Их нужно:

1. проверить;
2. нормализовать;
3. проверить права/CSRF;
4. сохранить через нужный слой.

---

## 6. Паттерн официального Webasyst-кода

### 6.1. `waAppConfig::init()`

Официальный app config загружает сначала дефолтный app config, затем пользовательский override:

```text
wa-apps/{app_id}/lib/config/config.php
wa-config/apps/{app_id}/config.php
```

После этого загружается:

```text
wa-apps/{app_id}/lib/config/app.php
```

И только потом app classes регистрируются в autoload.

Практический вывод:

- `config.php` — для опций;
- `app.php` — для app metadata/capabilities;
- `wa-config/apps/.../config.php` — для overrides;
- нельзя ожидать, что runtime settings из БД автоматически окажутся в `getOption()`.

### 6.2. `waAppSettingsModel`

`waAppSettingsModel` хранит key-value settings в `wa_app_settings` и кэширует их через `waVarExportCache`.

Важный паттерн:

```php
$model->get('shop', 'currency', 'USD');
$model->set('shop', 'currency', 'RUB');
$model->del('shop', 'currency');
```

Для plugin-а:

```php
$model->get(array('shop', 'myplugin'), 'enabled');
```

Но лучше:

```php
$enabled = $this->getSettings('enabled');
```

внутри класса plugin-а.

### 6.3. `waPlugin::getSettings()`

`waPlugin::getSettings()`:

1. читает настройки из `waAppSettingsModel`;
2. загружает `lib/config/settings.php`;
3. подставляет значения по умолчанию;
4. декодирует JSON для массивов;
5. возвращает одно значение или весь массив settings.

Это делает plugin settings более безопасным и предсказуемым слоем, чем прямой доступ к `wa_app_settings`.

### 6.4. `waPlugin::saveSettings()`

`saveSettings()` учитывает особенности HTML-форм:

- unchecked checkbox может не прийти в POST;
- groupbox может не прийти в POST;
- file control требует обработки upload;
- массивы сохраняются JSON-строкой;
- значения пишутся через `waAppSettingsModel`.

Поэтому для plugin settings не нужно писать собственный save-controller без необходимости.

### 6.5. Официальные app patterns

| App | Config pattern |
|---|---|
| `site` | `frontend`, `themes`, `pages`, `plugins`, `auth`, `csrf`, `my_account`, `routing_params.priority_settlement`. |
| `blog` | `frontend`, `themes`, `pages`, `plugins`, `my_account`, `routing_params.blog_url_type`. |
| `shop` | Большой config: `frontend`, `themes`, `pages`, `plugins`, `payment_plugins`, `shipping_plugins`, `sms_plugins`, checkout routing params. |
| `crm` | Backend-first/private endpoints: `plugins`, `rights`, `csrf`, `routing_params.private`, `frontend`, `ui => 2.0`. |

---

## 7. Минимальная реализация: app settings через `waAppSettingsModel`

Задача: добавить настройку приложения `items_per_page`, которую можно менять из backend UI.

### 7.1. Чтение настройки

```php
<?php

class myappSettings
{
    public static function getItemsPerPage()
    {
        $model = new waAppSettingsModel();
        $value = $model->get('myapp', 'items_per_page', 50);

        $value = (int) $value;
        if ($value <= 0) {
            $value = 50;
        }

        return $value;
    }
}
```

Файл:

```text
wa-apps/myapp/lib/classes/myappSettings.class.php
```

### 7.2. Сохранение настройки

```php
<?php

class myappSettingsSaveController extends waController
{
    public function execute()
    {
        $this->checkRights();

        $value = waRequest::post('items_per_page', 50, waRequest::TYPE_INT);
        if ($value <= 0) {
            $value = 50;
        }

        $model = new waAppSettingsModel();
        $model->set('myapp', 'items_per_page', $value);

        $this->redirect(wa()->getAppUrl('myapp') . '?module=settings');
    }

    protected function checkRights()
    {
        if (!$this->getRights('settings')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/settings/myappSettingsSave.controller.php
```

### 7.3. Форма настроек

```smarty
<form method="post" action="{$wa_app_url}?module=settings&action=save">
    {$wa->csrf()}

    <div class="fields">
        <div class="field">
            <div class="name">[`Items per page`]</div>
            <div class="value">
                <input type="number" name="items_per_page" value="{$items_per_page|escape}" min="1">
            </div>
        </div>
    </div>

    <button type="submit" class="button green">[`Save`]</button>
</form>
```

Файл:

```text
wa-apps/myapp/templates/actions/settings/Settings.html
```

---

## 8. Минимальная реализация: app `config.php`

Если значение не должно меняться через UI, а является технической дефолтной опцией, используйте `config.php`.

### 8.1. Дефолтный config

```php
<?php

return array(
    'items_per_page' => 50,
    'cache_time'     => 3600,
);
```

Файл:

```text
wa-apps/myapp/lib/config/config.php
```

### 8.2. Пользовательский override

```php
<?php

return array(
    'items_per_page' => 100,
);
```

Файл:

```text
wa-config/apps/myapp/config.php
```

### 8.3. Чтение

```php
$items_per_page = (int) wa()->getConfig()->getOption('items_per_page');
```

или:

```php
$items_per_page = (int) $this->getConfig()->getOption('items_per_page');
```

---

## 9. Минимальная реализация: plugin settings

### 9.1. `plugin.php`

```php
<?php

return array(
    'name'        => _wp('My plugin'),
    'description' => _wp('Adds custom feature.'),
    'vendor'      => 'myvendor',
    'version'     => '1.0.0',
    'shop_settings' => true,
);
```

Файл:

```text
wa-apps/shop/plugins/myplugin/lib/config/plugin.php
```

### 9.2. `settings.php`

```php
<?php

return array(
    'enabled' => array(
        'title'        => _wp('Enabled'),
        'description'  => _wp('Enable plugin feature.'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1,
    ),
    'mode' => array(
        'title'        => _wp('Mode'),
        'control_type' => waHtmlControl::SELECT,
        'value'        => 'default',
        'options'      => array(
            array('value' => 'default', 'title' => _wp('Default')),
            array('value' => 'advanced', 'title' => _wp('Advanced')),
        ),
    ),
);
```

Файл:

```text
wa-apps/shop/plugins/myplugin/lib/config/settings.php
```

### 9.3. Чтение в plugin class

```php
<?php

class shopMypluginPlugin extends shopPlugin
{
    public function isEnabled()
    {
        return (bool) $this->getSettings('enabled');
    }

    public function getMode()
    {
        return (string) $this->getSettings('mode');
    }
}
```

Файл:

```text
wa-apps/shop/plugins/myplugin/lib/shopMyplugin.plugin.php
```

---

## 10. Расширенная реализация

### 10.1. Settings class вместо прямого `waAppSettingsModel` везде

Если приложение активно использует много settings, лучше сделать небольшой wrapper-класс.

```php
<?php

class myappSettings
{
    protected static $model;

    public static function get($name, $default = null)
    {
        return self::getModel()->get('myapp', $name, $default);
    }

    public static function set($name, $value)
    {
        return self::getModel()->set('myapp', $name, $value);
    }

    public static function del($name = null)
    {
        return self::getModel()->del('myapp', $name);
    }

    protected static function getModel()
    {
        if (!self::$model) {
            self::$model = new waAppSettingsModel();
        }

        return self::$model;
    }
}
```

Плюсы:

- единая точка дефолтов;
- меньше хардкода `app_id`;
- проще валидировать значения;
- проще заменить хранение, если settings станет сложнее.

Минусы:

- лишний слой для маленького приложения;
- не стоит превращать wrapper в “бог-класс”.

### 10.2. Когда нужна отдельная таблица

Если settings перестают быть key-value, нужна модель и таблица.

Признаки:

- много записей одного типа;
- нужна сортировка;
- нужен поиск;
- нужны связи с пользователями/товарами/заказами;
- нужны индексы;
- нужна история изменений;
- значение не помещается в простую key-value модель.

Пример: список правил, каталогов, интеграционных маппингов — это не `wa_app_settings`, а отдельная таблица.

### 10.3. Когда нужен user config file

`wa-config/apps/{app_id}/config.php` подходит для devops/admin overrides:

- локальный backend/API endpoint;
- включение debug mode app-level;
- дефолтный размер страницы на конкретной установке;
- технические значения, которые не редактируются через UI.

Не подходит:

- настройки, которые меняются пользователем каждый день;
- UI-form settings;
- route state;
- данные интеграций с большим количеством записей.

---

## 11. Где хранить разные типы данных

| Данные | Где хранить | Почему |
|---|---|---|
| App capabilities: `frontend`, `plugins`, `themes`, `csrf`, `ui` | `lib/config/app.php` | Это декларация приложения. |
| Дефолтные технические app options | `lib/config/config.php` | Это кодовая конфигурация приложения. |
| User override технических options | `wa-config/apps/{app_id}/config.php` | Это конфигурация конкретной установки. |
| Настройки, меняемые через UI | `wa_app_settings` через `waAppSettingsModel` | Runtime key-value settings. |
| Plugin settings controls | `plugins/{plugin}/lib/config/settings.php` | Декларация формы настроек plugin-а. |
| Plugin settings values | `waPlugin::getSettings()/saveSettings()` | Стандартный plugin settings lifecycle. |
| Frontend routes | `lib/config/routing.php` или Site routing UI | Routing — отдельный механизм. |
| Backend routes | `lib/config/routing.backend.php` | Backend dispatch rules. |
| Таблицы | `lib/config/db.php` + updates | Schema lifecycle. |
| Много однотипных пользовательских записей | отдельная model/table | Нужны индексы, связи, CRUD. |
| Временное состояние запроса | `waRequest`, session/storage при необходимости | Не settings. |
| Пользовательский контент | отдельные таблицы/модели/wa-data | Не config. |

---

## 12. Типовые ошибки

### Ошибка 1. Хранить runtime settings в `app.php`

Неправильно:

```php
// lib/config/app.php
return array(
    'items_per_page' => 100,
);
```

Если значение меняется через UI, это не app info.

Правильно:

```php
$model = new waAppSettingsModel();
$model->set('myapp', 'items_per_page', 100);
```

### Ошибка 2. Хранить route в `waAppSettingsModel`

Неправильно:

```php
$model->set('myapp', 'route_orders', 'orders/<id>/');
```

Правильно:

```php
// wa-apps/myapp/lib/config/routing.backend.php
return array(
    'orders/<id:\d+>/' => 'orders/view',
);
```

Routing должен оставаться routing-механизмом.

### Ошибка 3. Писать plugin settings напрямую в `wa_app_settings`

Неправильно:

```php
$model = new waAppSettingsModel();
$model->set('shop.myplugin', 'enabled', 1);
```

Допустимо технически, но обычно неправильно в plugin-коде.

Правильно внутри plugin-а:

```php
$this->saveSettings(array(
    'enabled' => 1,
));
```

или чтение:

```php
$enabled = $this->getSettings('enabled');
```

### Ошибка 4. Хранить большие списки в `waAppSettingsModel`

Неправильно:

```php
$model->set('myapp', 'rules', json_encode($large_rules));
```

Если rules нужно искать, сортировать, включать/выключать, редактировать по одному — нужна отдельная таблица.

### Ошибка 5. Делать settings save без прав и CSRF

Неправильно:

```php
$model->set('myapp', 'enabled', waRequest::post('enabled'));
```

Правильно:

```php
if (!$this->getRights('settings')) {
    throw new waRightsException(_ws('Access denied.'));
}

$value = waRequest::post('enabled', 0, waRequest::TYPE_INT);
$model->set('myapp', 'enabled', $value ? 1 : 0);
```

CSRF для POST проверяется системно, если `csrf => true`, но форма всё равно должна выводить:

```smarty
{$wa->csrf()}
```

### Ошибка 6. Считать `getOption()` и `getSettings()` одним и тем же

Неправильно:

```php
$value = wa()->getConfig()->getOption('enabled');
```

если `enabled` сохраняется через `waAppSettingsModel`.

Правильно:

```php
$value = (new waAppSettingsModel())->get('myapp', 'enabled', 0);
```

или для plugin-а:

```php
$value = $this->getSettings('enabled');
```

### Ошибка 7. Записывать пользовательские настройки в `wa-apps`

Неправильно:

```text
wa-apps/myapp/lib/config/user_settings.php
```

`wa-apps` — код приложения. Runtime пользовательские данные должны быть в БД, `wa-config`, `wa-data` или отдельной модели в зависимости от типа данных.

---

## 13. Чеклист разработчика

Перед добавлением настройки проверить:

### Выбор места хранения

- [ ] Это app capability? Тогда `app.php`.
- [ ] Это дефолтная техническая опция? Тогда `lib/config/config.php`.
- [ ] Это override конкретной установки? Тогда `wa-config/apps/{app_id}/config.php`.
- [ ] Это значение, меняемое через UI? Тогда `waAppSettingsModel`.
- [ ] Это plugin setting? Тогда `settings.php` + `waPlugin::getSettings()/saveSettings()`.
- [ ] Это список записей? Тогда отдельная model/table.
- [ ] Это route? Тогда `routing.php` или `routing.backend.php`, не settings.

### App settings

- [ ] Есть дефолтное значение.
- [ ] Значение нормализуется после чтения.
- [ ] Значение валидируется перед сохранением.
- [ ] Save action проверяет права.
- [ ] POST форма содержит `{$wa->csrf()}`.
- [ ] Нет записи в `wa-apps` на runtime.

### Plugin settings

- [ ] Есть `lib/config/settings.php`.
- [ ] Controls описаны через `control_type`.
- [ ] Default values заданы в settings config.
- [ ] Чтение идёт через `$this->getSettings()`.
- [ ] Сохранение идёт через `$this->saveSettings()`.
- [ ] File upload settings пишутся в `wa-data`, а не в `wa-apps`.

### Config files

- [ ] `config.php` содержит только технические опции.
- [ ] `wa-config/apps/{app}/config.php` не коммитится как часть app source.
- [ ] `app.php` не используется как storage для runtime values.
- [ ] `routing.php` не подменяется settings-логикой.

---

## 14. Чеклист ИИ-агента

Перед ответом на задачу по настройкам ИИ-агент обязан:

1. Определить тип данных: capability, config option, runtime setting, plugin setting, route, schema, user content.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить `csrf`, `rights`, `plugins`, `frontend`, `ui`.
4. Открыть `wa-apps/{app_id}/lib/config/config.php`, если задача про app options.
5. Проверить наличие `wa-config/apps/{app_id}/config.php`, если задача про override.
6. Если задача про plugin — открыть `plugins/{plugin_id}/lib/config/plugin.php`.
7. Если задача про plugin settings — открыть `plugins/{plugin_id}/lib/config/settings.php`.
8. Найти существующий settings action/controller/template.
9. Проверить, как в проекте читаются settings: wrapper class, `waAppSettingsModel`, plugin API.
10. Проверить права и CSRF для сохранения.
11. Проверить, не нужна ли отдельная таблица вместо key-value settings.
12. Только после этого писать код.

ИИ-агенту запрещено:

- хранить runtime settings в `app.php`;
- хранить routes в `waAppSettingsModel`;
- писать пользовательские данные в `wa-apps`;
- напрямую писать plugin settings в БД, если можно использовать `waPlugin::saveSettings()`;
- создавать собственный settings storage без причины;
- писать save action без rights/CSRF;
- смешивать config override и runtime settings;
- сохранять большие структурированные списки JSON-строкой без анализа, нужна ли отдельная таблица.

---

## 15. Мини-сводка

В Webasyst настройки — это не один механизм, а несколько уровней:

```text
app.php
→ что приложение умеет

config.php
→ дефолтные технические опции приложения

wa-config/apps/{app}/config.php
→ override конкретной установки

waAppSettingsModel
→ runtime key-value settings приложения

plugin settings.php
→ декларация формы настроек plugin-а

waPlugin::getSettings()/saveSettings()
→ lifecycle plugin settings
```

Правильный выбор уровня хранения важен так же, как правильный выбор `Controller`/`Action` или `routing.php`/`routing.backend.php`. Настройка должна жить там, где Webasyst ожидает её найти.
