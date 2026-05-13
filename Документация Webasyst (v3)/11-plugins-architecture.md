# 11. Архитектура plugin в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить plugin Webasyst как расширение приложения: config → основной класс → lifecycle → settings → events → actions → routing → install/update → assets → rights.

---

## 1. Назначение механизма

Plugin в Webasyst — это расширение конкретного приложения, а не самостоятельное приложение.

Plugin может:

- подписываться на события приложения и системы;
- добавлять настройки;
- добавлять backend actions;
- добавлять frontend routes;
- добавлять helper для Smarty;
- добавлять JS/CSS assets;
- создавать собственные таблицы;
- запускать install/update/uninstall scripts;
- добавлять права доступа;
- расширять cron config.

Базовая точка входа plugin-а — класс:

```php
{app_id}{PluginId}Plugin extends waPlugin
```

или app-specific base class, если приложение его предоставляет. Например в Shop-Script:

```php
class shopRedirectPlugin extends shopPlugin
{
}
```

Plugin живёт внутри приложения:

```text
wa-apps/{app_id}/plugins/{plugin_id}/
```

Поэтому почти все имена plugin-классов начинаются с app prefix.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/plugin/waPlugin.class.php` | Базовый plugin lifecycle: install/update/settings/routing/assets/rights. |
| `wa-system/controller/waFrontController.class.php` | Dispatch plugin backend actions через GET `plugin`. |
| `wa-system/config/waAppConfig.class.php` | Сканирует plugin classes в `plugins/{plugin_id}/lib/`. |
| `wa-system/webasyst/lib/models/waAppSettings.model.php` | Хранит plugin settings под ключом `{app_id}.{plugin_id}`. |
| `wa-system/routing/waRouting.class.php` | Использует plugin routes, если app config их добавляет через событие `routing`. |

### 2.2. Минимальные файлы plugin-а

```text
wa-apps/{app_id}/plugins/{plugin_id}/
  lib/
    {app_id}{PluginId}.plugin.php
    config/
      plugin.php
```

### 2.3. Расширенные файлы plugin-а

```text
wa-apps/{app_id}/plugins/{plugin_id}/
  lib/
    {app_id}{PluginId}.plugin.php
    actions/
      {module}/
        {app_id}{PluginId}Plugin{Module}{Action}.action.php
        {app_id}{PluginId}Plugin{Module}{Action}.controller.php
        {app_id}{PluginId}Plugin{Module}.actions.php
    classes/
      {app_id}{PluginId}PluginSomeClass.class.php
    config/
      plugin.php
      settings.php
      routing.php
      db.php
      install.php
      uninstall.php
      cron.php
    updates/
      1700000000.php
  templates/
    actions/
      {module}/
        {Module}{Action}.html
    actions-legacy/
      {module}/
        {Module}{Action}.html
  js/
  css/
  img/
  locale/
```

---

## 3. Системная цепочка выполнения

### 3.1. Инициализация plugin-а

Plugin создаётся через приложение:

```php
wa('{app_id}')->getPlugin('{plugin_id}')
```

При создании `waPlugin`:

1. получает `$info` из `lib/config/plugin.php`;
2. определяет plugin id;
3. определяет app id;
4. строит путь `wa-apps/{app_id}/plugins/{plugin_id}`;
5. запускает `checkUpdates()`;
6. при первом запуске вызывает `install()`;
7. при новых update files вызывает `lib/updates/*.php`.

### 3.2. Подключение классов plugin-а

`waAppConfig::getClasses()` сканирует:

```text
wa-apps/{app_id}/lib/
wa-apps/{app_id}/plugins/{plugin_id}/lib/
wa-apps/{app_id}/widgets/{widget_id}/lib/
wa-apps/{app_id}/api/v{n}/
```

Поэтому классы plugin-а должны лежать внутри `plugins/{plugin_id}/lib/` и называться по Webasyst naming convention.

### 3.3. Backend action plugin-а

Backend plugin action обычно вызывается старым GET-способом:

```text
/{backend_url}/{app_id}/?plugin={plugin_id}&module={module}&action={action}
```

`waFrontController`:

1. читает GET `plugin`, `module`, `action`;
2. проверяет `plugins.php`, что plugin включён;
3. проверяет plugin rights, если в `plugin.php` указано `rights => true`;
4. загружает plugin;
5. ищет класс с plugin-сегментом в имени.

Форматы классов:

```text
{app_id}{PluginId}Plugin{Module}{Action}Controller
{app_id}{PluginId}Plugin{Module}{Action}Action
{app_id}{PluginId}Plugin{Module}Actions::{action}Action()
```

Пример:

```text
app_id = shop
plugin_id = redirect
module = settings
action = default
```

Возможный класс:

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

### 3.4. Event handler plugin-а

Plugin может подписываться на события через `lib/config/plugin.php`:

```php
return array(
    'handlers' => array(
        'frontend_error'  => 'frontendError',
        'frontend_search' => 'frontendSearch',
    ),
);
```

Методы должны быть в основном plugin class:

```php
class shopRedirectPlugin extends shopPlugin
{
    public function frontendError($params)
    {
    }

    public function frontendSearch($params = null)
    {
    }
}
```

---

## 4. Ключевые классы и методы

### 4.1. `waPlugin`

Ключевые свойства:

| Свойство | Назначение |
|---|---|
| `$id` | plugin id. |
| `$app_id` | приложение-владелец. |
| `$info` | данные из `plugin.php`. |
| `$path` | путь к plugin root. |
| `$settings` | загруженные settings. |
| `$settings_config` | данные из `settings.php`. |

Ключевые методы:

| Метод | Назначение |
|---|---|
| `getId()` | Возвращает plugin id. |
| `getName()` | Возвращает имя plugin-а. |
| `getVersion()` | Возвращает версию. |
| `getInfo()` | Возвращает config из `plugin.php`. |
| `checkUpdates()` | Проверяет install/update lifecycle. |
| `install()` | Создаёт schema из `db.php`, запускает `install.php`. |
| `uninstall()` | Запускает `uninstall.php`, удаляет schema/settings/rights. |
| `getSettings()` | Возвращает plugin settings. |
| `saveSettings()` | Сохраняет plugin settings. |
| `getControls()` | Строит settings controls. |
| `routing()` | Возвращает frontend routes из `lib/config/routing.php`. |
| `cron()` | Возвращает cron jobs из `lib/config/cron.php`. |
| `addJs()` | Добавляет plugin JS в response. |
| `addCss()` | Добавляет plugin CSS в response. |
| `getPluginStaticUrl()` | Возвращает static URL plugin-а. |
| `rightsConfig()` | Добавляет rights item. |
| `getRights()` | Проверяет plugin rights. |

### 4.2. App-specific plugin base class

Некоторые приложения имеют собственный plugin base class. Например Shop-Script использует `shopPlugin`.

Если app-specific base class существует, plugin должен наследоваться от него, потому что приложение может добавлять свои helper methods, events, settings conventions или API.

Пример:

```php
class shopRedirectPlugin extends shopPlugin
{
}
```

Для собственного приложения без app-specific base обычно достаточно:

```php
class myappExamplePlugin extends waPlugin
{
}
```

---

## 5. `lib/config/plugin.php`

`plugin.php` — декларация plugin-а.

Минимальный пример:

```php
<?php

return array(
    'name'        => /*_wp*/('Example'),
    'description' => /*_wp*/('Example plugin.'),
    'vendor'      => 'your_vendor',
    'version'     => '1.0.0',
);
```

Расширенный пример:

```php
<?php

return array(
    'name'        => /*_wp*/('Example'),
    'description' => /*_wp*/('Example plugin.'),
    'vendor'      => 'your_vendor',
    'version'     => '1.0.0',
    'img'         => 'img/example.png',
    'icons'       => array(
        16 => 'img/example.png',
    ),
    'rights'      => true,
    'shop_settings' => true,
    'handlers'    => array(
        'frontend_error' => 'frontendError',
    ),
);
```

Частые поля:

| Поле | Назначение |
|---|---|
| `name` | Название plugin-а. |
| `description` | Описание. |
| `vendor` | Vendor id. |
| `version` | Версия. |
| `img`, `icons` | Иконки. |
| `rights` | Включение plugin rights. |
| `handlers` | Подписки на события. |
| App-specific flags | Например `shop_settings`. |

---

## 6. Основной plugin class

Файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/{app_id}{PluginId}.plugin.php
```

Класс:

```php
<?php

class myappExamplePlugin extends waPlugin
{
    public function backendSomeEvent($params)
    {
    }
}
```

Для Shop-Script:

```php
<?php

class shopExamplePlugin extends shopPlugin
{
}
```

### 6.1. Назначение основного класса

Основной plugin class хранит:

- event handlers;
- settings controls overrides;
- frontend routing helpers;
- install/update utility methods;
- small plugin-specific orchestration.

Он не должен превращаться в God class. Если логика большая — выносить в classes/models внутри plugin-а.

---

## 7. Settings plugin-а

### 7.1. Config

Файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php
```

Пример:

```php
<?php

return array(
    'enabled' => array(
        'value'        => 0,
        'title'        => /*_wp*/('Enabled'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'title' => array(
        'value'        => '',
        'title'        => /*_wp*/('Title'),
        'control_type' => waHtmlControl::INPUT,
    ),
);
```

### 7.2. Чтение settings

```php
$enabled = $this->getSettings('enabled');
```

или:

```php
$settings = $this->getSettings();
```

### 7.3. Сохранение settings

Стандартно:

```php
$this->saveSettings($settings);
```

Если нужно нормализовать данные:

```php
public function saveSettings($settings = array())
{
    $settings['enabled'] = !empty($settings['enabled']) ? 1 : 0;
    parent::saveSettings($settings);
}
```

### 7.4. Custom controls

Если нужен custom control:

```php
public function getControls($params = array())
{
    waHtmlControl::registerControl('ExampleCustomControl', array($this, 'exampleCustomControl'));
    return parent::getControls($params);
}

public function exampleCustomControl($name, $params = array())
{
    return '<input type="text" name="'.$name.'" value="'.htmlspecialchars(ifset($params, 'value', '')).'">';
}
```

Официальный `shopRedirectPlugin` использует именно такой паттерн: регистрирует `RedirectControl`, затем делегирует построение остальной формы в `parent::getControls()`.

---

## 8. Backend actions plugin-а

Plugin backend action вызывается обычно так:

```text
/{backend_url}/{app_id}/?plugin={plugin_id}&module=settings&action=default
```

Action class:

```php
<?php

class myappExamplePluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('{app_id}')->getPlugin('example');
        $this->view->assign('settings', $plugin->getSettings());
    }
}
```

Файл:

```text
wa-apps/myapp/plugins/example/lib/actions/settings/myappExamplePluginSettings.action.php
```

Шаблон:

```text
wa-apps/myapp/plugins/example/templates/actions/settings/Settings.html
```

Если action изменяет данные, использовать controller:

```php
<?php

class myappExamplePluginSettingsSaveController extends waController
{
    public function execute()
    {
        $this->checkRights();

        $plugin = wa('myapp')->getPlugin('example');
        $plugin->saveSettings(waRequest::post('settings', array(), waRequest::TYPE_ARRAY));

        $this->redirect(wa()->getConfig()->getBackendUrl(true).'myapp/?plugin=example&module=settings');
    }

    protected function checkRights()
    {
        if (!wa()->getUser()->getRights('myapp', 'plugin.example')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
```

Файл:

```text
wa-apps/myapp/plugins/example/lib/actions/settings/myappExamplePluginSettingsSave.controller.php
```

---

## 9. Frontend routing plugin-а

Plugin может иметь:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php
```

`waPlugin::routing()` включает этот файл и возвращает array routes.

Пример:

```php
<?php

return array(
    'example/<id:\d+>/?' => 'frontend/view',
);
```

Для корректного подключения app config должен собирать plugin routes через event `routing`. Например `shopConfig::getRouting()` вызывает `wa()->event(array($this->application, 'routing'), $route)` и добавляет plugin routes в общий app routing.

Итоговый class naming:

```text
{app_id}{PluginId}PluginFrontendViewAction
```

Файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/frontend/{app_id}{PluginId}PluginFrontendView.action.php
```

---

## 10. Install/update/uninstall/db

### 10.1. `db.php`

Файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/db.php
```

Пример:

```php
<?php

return array(
    'myapp_example_item' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
```

При первом запуске plugin-а `waPlugin::install()` создаёт таблицы из `db.php`.

### 10.2. `install.php`

Файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/install.php
```

Используется для начальных данных, миграции старых данных, создания файлов в `wa-data`, инициализации settings.

Не использовать для runtime-логики.

### 10.3. `lib/updates/*.php`

Файлы:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/updates/1700000000.php
```

`waPlugin::checkUpdates()` выполняет update files по timestamp, если `update_time` plugin-а меньше timestamp файла.

### 10.4. `uninstall.php`

Файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/uninstall.php
```

Используется для очистки данных, которые не покрываются автоматическим удалением schema из `db.php`.

`waPlugin::uninstall()` дополнительно:

- удаляет таблицы из `db.php`;
- удаляет settings из `wa_app_settings`;
- удаляет plugin rights, если они включены;
- очищает app cache.

---

## 11. Plugin rights

Если в `plugin.php` указано:

```php
'rights' => true,
```

то `waFrontController` проверит право:

```text
plugin.{plugin_id}
```

Перед запуском plugin action.

Базовый `waPlugin::rightsConfig()` добавляет checkbox:

```php
public function rightsConfig(waRightConfig $rights_config)
{
    $rights_config->addItem('plugin.'.$this->id, $this->info['name'], 'checkbox');
}
```

Если нужны детальные права plugin-а, метод можно переопределить:

```php
public function rightsConfig(waRightConfig $rights_config)
{
    $rights_config->addItem('plugin.'.$this->id, $this->info['name'], 'checkbox');
    $rights_config->addItem('plugin.'.$this->id.'.edit', _wp('Edit'), 'checkbox');
}
```

Проверка:

```php
if (!$this->getRights('edit')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

или вне plugin class:

```php
if (!wa()->getUser()->getRights('myapp', 'plugin.example.edit')) {
    throw new waRightsException(_ws('Access denied.'));
}
```

---

## 12. Plugin assets

Внутри plugin class:

```php
$this->addJs('js/example.js');
$this->addCss('css/example.css');
```

`waPlugin::addJs()` и `waPlugin::addCss()` добавляют версию plugin-а к URL и регистрируют assets в `waResponse`.

Static URL plugin-а:

```php
$url = $this->getPluginStaticUrl();
```

Нельзя хардкодить:

```text
/wa-apps/shop/plugins/example/js/example.js
```

Правильно использовать plugin static URL или `addJs()`/`addCss()`.

---

## 13. Plugin helper

Plugin helper обычно нужен для безопасного вызова из Smarty:

```smarty
{$wa->shop->example->method()}
```

Стандартный helper class:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/classes/{app_id}{PluginId}PluginViewHelper.class.php
```

Подробно helper-механизм раскрывается в главе:

```text
12-plugin-helpers.md
```

В этой главе важно зафиксировать правило: если plugin должен отдавать данные в тему или шаблон, лучше делать helper, а не заставлять пользователя вызывать статические классы или SQL из Smarty.

---

## 14. Паттерн официального Webasyst-кода: `shop/plugins/redirect`

Plugin `shopRedirectPlugin` показывает несколько важных паттернов:

1. `plugin.php` объявляет имя, vendor, version, icon, app-specific settings flag и handlers.
2. Основной класс наследуется от `shopPlugin`.
3. Событие `frontend_error` используется для обработки 404 и поиска redirect.
4. Событие `frontend_search` используется для нормализации старого search query.
5. Settings читаются через `$this->getSettings()`.
6. Custom settings control регистрируется через `waHtmlControl::registerControl()`.
7. `saveSettings()` нормализует массив custom rules перед сохранением.
8. URL строятся через `wa()->getRouteUrl()`, а не через hardcode.

Это хороший пример plugin-а, который расширяет frontend-поведение приложения, но не подменяет router или core dispatch.

---

## 15. Минимальная реализация plugin-а с event handler

### 15.1. `plugin.php`

```text
wa-apps/myapp/plugins/example/lib/config/plugin.php
```

```php
<?php

return array(
    'name'        => /*_wp*/('Example'),
    'description' => /*_wp*/('Example plugin.'),
    'vendor'      => 'your_vendor',
    'version'     => '1.0.0',
    'handlers'    => array(
        'backend_menu' => 'backendMenu',
    ),
);
```

### 15.2. Main class

```text
wa-apps/myapp/plugins/example/lib/myappExample.plugin.php
```

```php
<?php

class myappExamplePlugin extends waPlugin
{
    public function backendMenu($params = null)
    {
        return array(
            'core_li' => '<li><a href="'.wa()->getAppUrl('myapp').'/?plugin=example&module=settings">Example</a></li>',
        );
    }
}
```

---

## 16. Минимальная реализация plugin-а с settings

### 16.1. `settings.php`

```text
wa-apps/myapp/plugins/example/lib/config/settings.php
```

```php
<?php

return array(
    'enabled' => array(
        'value'        => 0,
        'title'        => /*_wp*/('Enabled'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'label' => array(
        'value'        => '',
        'title'        => /*_wp*/('Label'),
        'control_type' => waHtmlControl::INPUT,
    ),
);
```

### 16.2. Использование settings

```php
<?php

class myappExamplePlugin extends waPlugin
{
    public function someEvent($params)
    {
        if (!$this->getSettings('enabled')) {
            return null;
        }

        return $this->getSettings('label');
    }
}
```

---

## 17. Минимальная реализация frontend routing plugin-а

### 17.1. `plugin.php`

```php
<?php

return array(
    'name'     => /*_wp*/('Example'),
    'vendor'   => 'your_vendor',
    'version'  => '1.0.0',
    'handlers' => array(
        'routing' => 'routing',
    ),
);
```

### 17.2. Main class

```php
<?php

class myappExamplePlugin extends waPlugin
{
    public function routing($route = array())
    {
        return parent::routing($route);
    }
}
```

### 17.3. `routing.php`

```text
wa-apps/myapp/plugins/example/lib/config/routing.php
```

```php
<?php

return array(
    'example/<id:\d+>/?' => 'frontend/view',
);
```

### 17.4. Action

```text
wa-apps/myapp/plugins/example/lib/actions/frontend/myappExamplePluginFrontendView.action.php
```

```php
<?php

class myappExamplePluginFrontendViewAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            throw new waException(_ws('Page not found'), 404);
        }

        $this->view->assign('id', $id);
    }
}
```

### 17.5. Template

```text
wa-apps/myapp/plugins/example/templates/actions/frontend/FrontendView.html
```

```smarty
<div class="example-plugin-page">
    ID: {$id|escape}
</div>
```

---

## 18. Расширенная реализация: plugin with DB model

### 18.1. `db.php`

```php
<?php

return array(
    'myapp_example_item' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
```

### 18.2. Model

```text
wa-apps/myapp/plugins/example/lib/models/myappExamplePluginItem.model.php
```

```php
<?php

class myappExamplePluginItemModel extends waModel
{
    protected $table = 'myapp_example_item';
}
```

### 18.3. Action usage

```php
$model = new myappExamplePluginItemModel();
$items = $model->order('id DESC')->fetchAll();
```

---

## 19. Типовые ошибки

### Ошибка 1. Считать plugin самостоятельным app

Неправильно:

```text
/plugin/example/settings/
```

Правильно:

```text
/{backend_url}/{app_id}/?plugin=example&module=settings
```

или frontend route внутри settlement приложения, если app routing поддерживает plugin routes.

### Ошибка 2. Неправильное имя основного класса

Неправильно:

```php
class ExamplePlugin extends waPlugin
```

Правильно:

```php
class myappExamplePlugin extends waPlugin
```

Для Shop-Script:

```php
class shopExamplePlugin extends shopPlugin
```

### Ошибка 3. Писать settings напрямую в `wa_app_settings`

Неправильно:

```php
$model = new waModel();
$model->exec('INSERT INTO wa_app_settings ...');
```

Правильно:

```php
$this->saveSettings($settings);
$this->getSettings('name');
```

или напрямую через `waAppSettingsModel`, если это app-level setting, а не plugin setting.

### Ошибка 4. Хранить runtime route data в settings

Settings — для управляемых конфигурационных значений. Route params и request state должны идти через routing/request/session/model, а не в plugin settings.

### Ошибка 5. Не проверять права в save/delete actions

`rights => true` защищает запуск plugin action, но изменяющие операции всё равно должны явно проверять нужное право, если внутри plugin есть несколько уровней доступа.

### Ошибка 6. Писать PHP в Smarty plugin template

Неправильно:

```smarty
{php}...{/php}
```

Правильно:

- подготовить данные в action/helper;
- отдать в template через `$this->view->assign()`;
- вывести через Smarty.

### Ошибка 7. Хардкодить plugin asset URL

Неправильно:

```php
$url = '/wa-apps/shop/plugins/example/js/example.js';
```

Правильно:

```php
$this->addJs('js/example.js');
```

или:

```php
$url = $this->getPluginStaticUrl().'js/example.js';
```

### Ошибка 8. Создавать frontend routing plugin-а без проверки app support

Не каждое приложение одинаково собирает plugin routes. Перед реализацией нужно открыть app config class и проверить, как приложение вызывает event `routing`.

---

## 20. Чеклист разработчика

### Структура

- [ ] Plugin лежит в `wa-apps/{app_id}/plugins/{plugin_id}/`.
- [ ] Есть `lib/config/plugin.php`.
- [ ] Основной класс назван `{app_id}{PluginId}Plugin`.
- [ ] Файл основного класса назван `{app_id}{PluginId}.plugin.php`.
- [ ] Классы лежат внутри `lib/`.
- [ ] Шаблоны лежат в `templates/actions/...` или `templates/actions-legacy/...`.

### Config

- [ ] В `plugin.php` указаны `name`, `vendor`, `version`.
- [ ] `handlers` указывают на существующие методы.
- [ ] Если нужны права, указан `rights => true`.
- [ ] Если нужны settings, есть `lib/config/settings.php`.

### Settings

- [ ] Settings читаются через `$this->getSettings()`.
- [ ] Settings сохраняются через `$this->saveSettings()`.
- [ ] Массивы сохраняются осознанно, с JSON-логикой `waPlugin`.
- [ ] Upload settings сохраняют файлы в `wa-data`, а не в `wa-apps`.

### Actions

- [ ] Backend URL использует `?plugin=...&module=...&action=...`.
- [ ] Class name учитывает app prefix и plugin segment.
- [ ] Save/delete actions проверяют права.
- [ ] POST actions учитывают CSRF приложения.

### Routing

- [ ] Если нужен frontend route, есть `lib/config/routing.php`.
- [ ] Проверено, что приложение собирает plugin routes через event `routing`.
- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] URL строятся через `wa()->getRouteUrl()`.

### Install/update/db

- [ ] Схема таблиц описана в `lib/config/db.php`.
- [ ] Update files лежат в `lib/updates/*.php`.
- [ ] Install/update scripts не выполняют runtime-логику.
- [ ] Uninstall script очищает только то, что действительно нужно очистить дополнительно.

### Assets

- [ ] JS/CSS подключаются через `addJs()`/`addCss()` или plugin static URL.
- [ ] Нет hardcode `/wa-apps/...`.
- [ ] Assets имеют версионирование через plugin version.

---

## 21. Чеклист ИИ-агента

Перед ответом по plugin-задаче ИИ-агент обязан:

1. Определить app id и plugin id.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить, поддерживает ли приложение plugins.
4. Открыть `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php`.
5. Открыть основной class `{app_id}{PluginId}.plugin.php`.
6. Проверить `handlers` и соответствующие методы.
7. Если задача про settings — открыть `lib/config/settings.php`.
8. Если задача про backend action — открыть `lib/actions/{module}/` и `templates/actions/{module}/`.
9. Если задача про frontend route — открыть `lib/config/routing.php` plugin-а и app config class, где собираются plugin routes.
10. Если задача про БД — открыть plugin `db.php`, models и `lib/updates/`.
11. Проверить rights и CSRF для изменяющих операций.
12. Проверить текущий UI mode и наличие `templates/actions-legacy`.
13. Только после этого писать код.

ИИ-агенту запрещено:

- придумывать plugin class name без проверки app/plugin naming;
- писать plugin как самостоятельное приложение;
- использовать Laravel/Symfony-style middleware вместо Webasyst events/actions;
- хардкодить backend/frontend URL;
- писать SQL в Smarty;
- хранить файлы plugin-а в `wa-apps` после upload;
- игнорировать `rights => true` и plugin rights;
- добавлять frontend routes, не проверив app support for plugin routing.

---

## 22. Мини-сводка

Plugin architecture Webasyst держится на пяти слоях:

```text
plugin.php
→ waPlugin lifecycle
→ main plugin class
→ handlers/settings/actions/routing/assets/db
→ app-specific dispatch and events
```

Plugin не заменяет приложение и не создаёт собственный runtime поверх Webasyst. Он расширяет приложение через стандартные точки:

- `handlers`;
- `settings.php`;
- backend actions через GET `plugin`;
- frontend routes через plugin `routing.php` и app event `routing`;
- install/update/db через `waPlugin::checkUpdates()`;
- assets через `addJs()`/`addCss()`;
- rights через `plugin.{plugin_id}`.

Правильная цепочка backend plugin action:

```text
/{backend_url}/{app_id}/?plugin=example&module=settings&action=save
→ waFrontController
→ getDispatchParams()
→ plugin config check
→ plugin rights check
→ wa()->getPlugin('example')
→ getController(plugin, module, action)
→ {app_id}ExamplePluginSettingsSaveController
→ execute()
```

Именно эту цепочку нужно понимать перед любой правкой plugin-а.
