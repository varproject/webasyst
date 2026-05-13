# 21. Skeletons Webasyst

**Статус:** опубликован v3  
**Назначение:** дать набор минимальных рабочих skeletons для типовых задач Webasyst-разработки.  
**Принцип:** skeleton должен быть минимальным, но полным: путь файла → имя класса → dispatch-связь → шаблон/модель/route, если они нужны.

---

## 1. Назначение главы

Эта глава не заменяет подробные механические главы `01–20`. Она служит практической шпаргалкой: какой минимальный набор файлов создать, чтобы Webasyst смог найти класс, route, шаблон, модель или plugin.

Skeleton — это не архитектура приложения целиком. Это стартовая точка, которую нужно адаптировать под текущий проект после проверки:

1. `lib/config/app.php`;
2. `routing.php` или `routing.backend.php`;
3. существующих `lib/actions/{module}/`;
4. существующих `templates/actions/{module}/`;
5. моделей и сервисов;
6. UI-версии;
7. прав и CSRF.

---

## 2. Общие правила для всех skeletons

### 2.1. Классы и файлы

Webasyst находит классы по имени файла и имени класса. Поэтому имя файла должно соответствовать классу:

```text
myappBackendAction
→ wa-apps/myapp/lib/actions/backend/myappBackend.action.php

myappOrdersSaveController
→ wa-apps/myapp/lib/actions/orders/myappOrdersSave.controller.php

myappOrderModel
→ wa-apps/myapp/lib/models/myappOrder.model.php
```

### 2.2. Шаблоны actions

Для `waViewAction`:

```text
myappOrdersListAction
→ wa-apps/myapp/templates/actions/orders/OrdersList.html
```

Для `waViewActions::listAction()`:

```text
myappOrdersActions::listAction()
→ wa-apps/myapp/templates/actions/orders/OrdersList.html
```

### 2.3. Request

- route params: `waRequest::param()`;
- GET: `waRequest::get()`;
- POST: `waRequest::post()`;
- mixed GET/POST: `waRequest::request()`;
- server: `waRequest::server()`;
- cookie: `waRequest::cookie()`.

### 2.4. Security baseline

Любой изменяющий backend-запрос должен проверять:

```php
$this->checkRights();
```

и, если это форма/POST, в шаблоне должен быть:

```smarty
{$wa->csrf()}
```

Если приложение имеет `'csrf' => true`, backend/front dispatch проверит `_csrf` на POST-запросах в соответствующем контексте, но это не отменяет необходимости передавать токен в форме.

---

# 3. Минимальное приложение

## 3.1. Структура

```text
wa-apps/myapp/
  img/
    myapp.svg
  lib/
    config/
      app.php
      routing.php
      routing.backend.php
      db.php
    actions/
      backend/
        myappBackend.action.php
      frontend/
        myappFrontend.action.php
    models/
      myappItem.model.php
  templates/
    actions/
      backend/
        Backend.html
      frontend/
        Frontend.html
```

---

# 4. `app.php`

## Файл

```text
wa-apps/myapp/lib/config/app.php
```

## Skeleton

```php
<?php

return array(
    'name'        => 'My App',
    'description' => 'My Webasyst app.',
    'icon'        => 'img/myapp.svg',
    'sash_color'  => '#3b82f6',

    'version'  => '1.0.0',
    'critical' => '1.0.0',
    'vendor'   => 'mycompany',

    'frontend' => true,
    'plugins'  => true,
    'rights'   => true,
    'csrf'     => true,

    'ui' => '2.0',
);
```

## Когда убрать параметры

| Параметр | Убрать, если |
|---|---|
| `frontend` | приложение не имеет frontend-раздела |
| `plugins` | приложение не поддерживает плагины |
| `rights` | приложению не нужна отдельная настройка прав |
| `csrf` | почти никогда не убирать для backend-приложений |
| `ui` | лучше не убирать; явно указать поддерживаемую UI-версию |

---

# 5. `routing.php`

## Файл

```text
wa-apps/myapp/lib/config/routing.php
```

## Минимальный frontend route

```php
<?php

return array(
    '' => 'frontend/',
);
```

## Route с action

```php
<?php

return array(
    ''                         => 'frontend/',
    'item/<id:\d+>/'           => 'frontend/item',
    'item/<id:\d+>/download/'  => 'frontend/itemDownload',
);
```

## Какой класс будет найден

```text
'item/<id:\d+>/' => 'frontend/item'

module = frontend
action = item

myappFrontendItemController
myappFrontendItemAction
myappFrontendActions::itemAction()
```

---

# 6. `routing.backend.php`

## Файл

```text
wa-apps/myapp/lib/config/routing.backend.php
```

## Минимальный backend routing

```php
<?php

return array(
    '' => 'backend',
);
```

## Красивые backend URL

```php
<?php

return array(
    'orders/?'               => 'orders/list',
    'orders/<id:\d+>/?'      => 'orders/view',
    'orders/<id:\d+>/edit/?' => 'orders/edit',
    ''                       => 'backend',
);
```

## Какой класс будет найден

```text
/orders/123/edit/

module = orders
action = edit
id = 123

myappOrdersEditController
myappOrdersEditAction
myappOrdersActions::editAction()
```

---

# 7. Backend action

## Файл

```text
wa-apps/myapp/lib/actions/backend/myappBackend.action.php
```

## Класс

```php
<?php

class myappBackendAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'title' => _w('Dashboard'),
        ));
    }
}
```

## Шаблон

```text
wa-apps/myapp/templates/actions/backend/Backend.html
```

```smarty
<div class="article">
    <div class="article-body">
        <h1>{$title|escape}</h1>
    </div>
</div>
```

## Dispatch

```text
/backend_url/myapp/
→ routing.backend.php
→ module = backend
→ myappBackendAction
→ templates/actions/backend/Backend.html
```

---

# 8. Backend controller для POST-команды

## Файл

```text
wa-apps/myapp/lib/actions/orders/myappOrdersDelete.controller.php
```

## Route

```php
<?php

return array(
    'orders/<id:\d+>/delete/?' => 'orders/delete',
    'orders/?'                 => 'orders/list',
    ''                         => 'backend',
);
```

## Класс

```php
<?php

class myappOrdersDeleteController extends waController
{
    public function execute()
    {
        $this->checkRights();

        $id = waRequest::param('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            throw new waException(_w('Item not found'), 404);
        }

        $model = new myappOrderModel();
        if (!$model->getById($id)) {
            throw new waException(_w('Item not found'), 404);
        }

        $model->deleteById($id);

        $this->redirect(wa()->getAppUrl('myapp') . 'orders/');
    }

    protected function checkRights()
    {
        if (!$this->getRights('orders')) {
            throw new waRightsException(_ws('Access denied.'));
        }
    }
}
```

## Примечание

Это `waController`, а не `waViewAction`, потому что запрос не рендерит HTML.

---

# 9. Frontend action

## Файл

```text
wa-apps/myapp/lib/actions/frontend/myappFrontend.action.php
```

## Класс

```php
<?php

class myappFrontendAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setThemeTemplate('index.html');
    }

    public function execute()
    {
        $this->getResponse()->setTitle(_w('My App'));

        $this->view->assign(array(
            'items' => array(),
        ));
    }
}
```

## Route

```php
<?php

return array(
    '' => 'frontend/',
);
```

## Theme template

```text
wa-apps/myapp/themes/default/index.html
```

```smarty
<h1>{$wa->title()|escape}</h1>

{if !empty($items)}
    <ul>
        {foreach $items as $item}
            <li>{$item.name|escape}</li>
        {/foreach}
    </ul>
{/if}
```

---

# 10. Layout

## Файл

```text
wa-apps/myapp/lib/layouts/myappBackend.layout.php
```

## Класс

```php
<?php

class myappBackendLayout extends waLayout
{
    public function execute()
    {
        $this->view->assign(array(
            'app_url' => wa()->getAppUrl('myapp'),
            'page'    => waRequest::param('module', 'backend'),
        ));
    }
}
```

## Шаблон

```text
wa-apps/myapp/templates/layouts/Backend.html
```

```smarty
<div class="sidebar">
    <div class="sidebar-body">
        <ul class="menu">
            <li{if $page == 'backend'} class="selected"{/if}>
                <a href="{$app_url}">Dashboard</a>
            </li>
        </ul>
    </div>
</div>

<div class="content">
    {$content}
</div>
```

## Controller, который использует layout

```text
wa-apps/myapp/lib/actions/backend/myappBackend.controller.php
```

```php
<?php

class myappBackendController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());
        $this->executeAction(new myappBackendDashboardAction(), 'content');
    }
}
```

---

# 11. Model

## Файл

```text
wa-apps/myapp/lib/models/myappItem.model.php
```

## Класс

```php
<?php

class myappItemModel extends waModel
{
    protected $table = 'myapp_item';

    public function getEnabled()
    {
        return $this->select('*')
            ->where('enabled = i:enabled', array(
                'enabled' => 1,
            ))
            ->order('sort ASC, id ASC')
            ->fetchAll('id');
    }

    public function saveSort($ids)
    {
        $sort = 0;
        foreach ((array) $ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $this->updateById($id, array(
                    'sort' => $sort++,
                ));
            }
        }
    }
}
```

## Schema

```text
wa-apps/myapp/lib/config/db.php
```

```php
<?php

return array(
    'myapp_item' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'enabled' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'enabled_sort' => array('enabled', 'sort'),
        ),
    ),
);
```

---

# 12. JSON controller

## Файл

```text
wa-apps/myapp/lib/actions/orders/myappOrdersSave.controller.php
```

## Класс

```php
<?php

class myappOrdersSaveController extends waJsonController
{
    public function execute()
    {
        $this->checkRights();

        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);

        if ($name === '') {
            $this->errors[] = array(
                'field' => 'name',
                'message' => _w('Name is required.'),
            );
            return;
        }

        $model = new myappOrderModel();

        $data = array(
            'name' => $name,
            'update_datetime' => date('Y-m-d H:i:s'),
        );

        if ($id > 0) {
            $model->updateById($id, $data);
        } else {
            $data['create_datetime'] = date('Y-m-d H:i:s');
            $id = $model->insert($data);
        }

        $this->response = array(
            'id' => $id,
            'name' => $name,
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

## Response format

Success:

```json
{"status":"ok","data":{"id":1,"name":"Order"}}
```

Fail:

```json
{"status":"fail","errors":[{"field":"name","message":"Name is required."}]}
```

---

# 13. JSON actions

## Файл

```text
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

## Класс

```php
<?php

class myappOrdersActions extends waJsonActions
{
    public function defaultAction()
    {
        $this->response = array(
            'message' => 'ok',
        );
    }

    public function statusAction()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            $this->errors[] = _w('Invalid id.');
            return;
        }

        $this->response = array(
            'id' => $id,
            'status' => 'ready',
        );
    }
}
```

## Dispatch

```text
module = orders
action = status

myappOrdersActions::statusAction()
```

---

# 14. Long action

## Файл

```text
wa-apps/myapp/lib/actions/import/myappImportRun.controller.php
```

## Класс

```php
<?php

class myappImportRunController extends waLongActionController
{
    protected function init()
    {
        $this->data['offset'] = 0;
        $this->data['total'] = 0;
        $this->data['done'] = false;
    }

    protected function isDone()
    {
        return !empty($this->data['done']);
    }

    protected function step()
    {
        $limit = 50;

        $items = $this->getItems($this->data['offset'], $limit);
        foreach ($items as $item) {
            $this->processItem($item);
        }

        $this->data['offset'] += count($items);
        $this->data['total'] += count($items);

        if (count($items) < $limit) {
            $this->data['done'] = true;
        }

        return true;
    }

    protected function finish($filename)
    {
        $this->infoReady($filename);
        return true;
    }

    protected function info()
    {
        echo json_encode(array(
            'processId' => $this->processId,
            'ready' => false,
            'offset' => $this->data['offset'],
            'total' => $this->data['total'],
        ));
    }

    protected function infoReady($filename)
    {
        echo json_encode(array(
            'processId' => $this->processId,
            'ready' => true,
            'total' => $this->data['total'],
        ));
    }

    private function getItems($offset, $limit)
    {
        return array();
    }

    private function processItem($item)
    {
    }
}
```

## Правило

`step()` должен быть коротким. Не делать в одном `step()` весь импорт.

---

# 15. CLI command

## Файл

```text
wa-apps/myapp/lib/cli/myappSync.cli.php
```

## Класс

```php
<?php

class myappSyncCli extends waCliController
{
    public function execute()
    {
        $model = new myappItemModel();

        $count = $model->countAll();

        echo sprintf("Items: %d\n", $count);
    }
}
```

## Запуск

```bash
php cli.php myapp sync
```

## Dispatch

```text
app = myapp
slug = sync
class = myappSyncCli
```

---

# 16. Cron config

## Файл

```text
wa-apps/myapp/lib/config/cron.php
```

## Skeleton

```php
<?php

return array(
    'sync' => array(
        'expression' => '0 * * * *',
        'command' => 'php cli.php myapp sync',
    ),
);
```

## Примечание

Cron config описывает задачу. Реальную работу выполняет CLI-класс.

---

# 17. Plugin

## Структура

```text
wa-apps/shop/plugins/myplugin/
  img/
    myplugin.png
  lib/
    config/
      plugin.php
      settings.php
      routing.php
      db.php
      install.php
      uninstall.php
    actions/
      settings/
        shopMypluginPluginSettings.action.php
    shopMyplugin.plugin.php
    shopMypluginPluginViewHelper.class.php
  templates/
    actions/
      settings/
        Settings.html
```

---

## `plugin.php`

```text
wa-apps/shop/plugins/myplugin/lib/config/plugin.php
```

```php
<?php

return array(
    'name' => _wp('My plugin'),
    'description' => _wp('Plugin description.'),
    'vendor' => 'mycompany',
    'version' => '1.0.0',
    'img' => 'img/myplugin.png',
    'shop_settings' => true,
    'rights' => true,
    'handlers' => array(
        'frontend_head' => 'frontendHead',
    ),
);
```

---

## Main plugin class

```text
wa-apps/shop/plugins/myplugin/lib/shopMyplugin.plugin.php
```

```php
<?php

class shopMypluginPlugin extends shopPlugin
{
    public function frontendHead()
    {
        if (!$this->getSettings('enabled')) {
            return '';
        }

        return '<!-- myplugin -->';
    }
}
```

---

# 18. Plugin settings

## `settings.php`

```text
wa-apps/shop/plugins/myplugin/lib/config/settings.php
```

```php
<?php

return array(
    'enabled' => array(
        'title' => _wp('Enabled'),
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'title' => array(
        'title' => _wp('Title'),
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
);
```

## Использование

```php
$enabled = $this->getSettings('enabled');
```

## Custom save

```php
<?php

class shopMypluginPlugin extends shopPlugin
{
    public function saveSettings($settings = array())
    {
        $settings['title'] = trim(ifset($settings['title'], ''));

        parent::saveSettings($settings);
    }
}
```

---

# 19. Plugin backend action

## Файл

```text
wa-apps/shop/plugins/myplugin/lib/actions/settings/shopMypluginPluginSettings.action.php
```

## Класс

```php
<?php

class shopMypluginPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('myplugin');

        $this->view->assign(array(
            'settings' => $plugin->getSettings(),
        ));
    }
}
```

## Шаблон

```text
wa-apps/shop/plugins/myplugin/templates/actions/settings/Settings.html
```

```smarty
<div class="article">
    <div class="article-body">
        <h1>[`My plugin`]</h1>
    </div>
</div>
```

## URL

```text
/webasyst/shop/?plugin=myplugin&module=settings
```

---

# 20. Plugin frontend routing

## Файл

```text
wa-apps/shop/plugins/myplugin/lib/config/routing.php
```

```php
<?php

return array(
    'myplugin/<hash>/' => 'frontend/view',
);
```

## Action

```text
wa-apps/shop/plugins/myplugin/lib/actions/frontend/shopMypluginPluginFrontendView.action.php
```

```php
<?php

class shopMypluginPluginFrontendViewAction extends waViewAction
{
    public function execute()
    {
        $hash = waRequest::param('hash', '', waRequest::TYPE_STRING_TRIM);

        if ($hash === '') {
            throw new waException(_ws('Page not found'), 404);
        }

        $this->view->assign('hash', $hash);
    }
}
```

## Шаблон

```text
wa-apps/shop/plugins/myplugin/templates/actions/frontend/FrontendView.html
```

```smarty
<div>
    {$hash|escape}
</div>
```

---

# 21. Plugin helper

## Файл

```text
wa-apps/shop/plugins/myplugin/lib/shopMypluginPluginViewHelper.class.php
```

## Класс

```php
<?php

class shopMypluginPluginViewHelper extends waPluginViewHelper
{
    public function badge($text)
    {
        if (!$this->version()) {
            return '';
        }

        $text = htmlspecialchars((string) $text, ENT_QUOTES, 'utf-8');

        return '<span class="badge">' . $text . '</span>';
    }

    public function enabled()
    {
        return (bool) $this->plugin()->getSettings('enabled');
    }
}
```

## Smarty

```smarty
{if $wa->shop->mypluginPlugin->version()}
    {$wa->shop->mypluginPlugin->badge('New') nofilter}
{/if}
```

---

# 22. Theme skeleton

## Структура

```text
wa-apps/myapp/themes/default/
  theme.xml
  index.html
  page.html
  error.html
  default.css
  default.js
```

## `theme.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE theme PUBLIC "wa-app-theme" "http://www.webasyst.com/wa-content/xml/wa-app-theme.dtd">
<theme id="default" system="0" vendor="mycompany" author="My Company" app="myapp" version="1.0.0">
    <name locale="en_US">Default</name>
    <name locale="ru_RU">Дефолт</name>

    <files>
        <file path="index.html">
            <description locale="en_US">Main layout</description>
            <description locale="ru_RU">Основной макет</description>
        </file>
        <file path="page.html">
            <description locale="en_US">Page template</description>
            <description locale="ru_RU">Шаблон страницы</description>
        </file>
        <file path="error.html">
            <description locale="en_US">Error page</description>
            <description locale="ru_RU">Страница ошибки</description>
        </file>
        <file path="default.css"/>
        <file path="default.js"/>
    </files>

    <settings>
        <setting var="show_sidebar" control_type="checkbox">
            <value>1</value>
            <name locale="en_US">Show sidebar</name>
            <name locale="ru_RU">Показывать боковую панель</name>
        </setting>
    </settings>
</theme>
```

## `index.html`

```smarty
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$wa->title()|escape}</title>
    {$wa->css()}
    <link rel="stylesheet" href="{$wa_theme_url}default.css?v{$wa_theme_version}">
    {$wa->head()}
</head>
<body>
    {$content}
    {$wa->js()}
    <script src="{$wa_theme_url}default.js?v{$wa_theme_version}"></script>
</body>
</html>
```

---

# 23. UI 2.0 backend page skeleton

## Action

```text
wa-apps/myapp/lib/actions/backend/myappBackend.action.php
```

```php
<?php

class myappBackendAction extends waViewAction
{
    public function execute()
    {
        $this->getResponse()->setTitle(_w('Dashboard'));

        $this->view->assign(array(
            'items' => array(),
        ));
    }
}
```

## Template

```text
wa-apps/myapp/templates/actions/backend/Backend.html
```

```smarty
<div class="article">
    <div class="article-body">
        <div class="flexbox middle space-16">
            <h1 class="custom-mb-0">[`Dashboard`]</h1>

            <div class="wide"></div>

            <a href="{$wa_app_url}settings/" class="button light-gray">
                <i class="fas fa-cog"></i>
                [`Settings`]
            </a>
        </div>

        <div class="box custom-mt-24">
            <div class="alert">
                [`This is a minimal UI 2.0 backend page.`]
            </div>
        </div>

        <div class="tablebox custom-mt-24">
            <table class="zebra">
                <thead>
                    <tr>
                        <th>[`Name`]</th>
                        <th>[`Status`]</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $items as $item}
                        <tr>
                            <td>{$item.name|escape}</td>
                            <td>
                                {if !empty($item.enabled)}
                                    <span class="badge green">[`Enabled`]</span>
                                {else}
                                    <span class="badge gray">[`Disabled`]</span>
                                {/if}
                            </td>
                        </tr>
                    {foreachelse}
                        <tr>
                            <td colspan="2">
                                <div class="align-center gray custom-py-32">
                                    [`No items yet.`]
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
```

---

# 24. AI-agent skeleton for code task

Перед тем как предложить код, агент должен пройти такой внутренний skeleton:

```text
1. Определить тип задачи:
   app / plugin / theme / backend / frontend / model / UI / security.

2. Открыть:
   wa-apps/{app}/lib/config/app.php

3. Проверить:
   ui
   csrf
   rights
   frontend
   plugins
   themes
   pages

4. Открыть routing:
   lib/config/routing.php
   lib/config/routing.backend.php

5. Определить dispatch:
   module
   action
   plugin
   route params

6. Открыть существующий код:
   lib/actions/{module}/
   templates/actions/{module}/
   lib/models/
   lib/classes/

7. Найти аналогичный pattern в текущем проекте.

8. Проверить:
   rights
   CSRF
   escaping
   SQL placeholders
   URL generation
   UI version

9. Только после этого писать код.
```

---

# 25. Типовые ошибки при использовании skeletons

## Ошибка 1. Копировать skeleton без проверки текущего app prefix

Нельзя:

```php
class myappBackendAction extends waViewAction
```

если app id — `shop` или `crm`.

## Ошибка 2. Создать action без шаблона

Если класс:

```php
myappOrdersListAction
```

то нужен:

```text
templates/actions/orders/OrdersList.html
```

или явный:

```php
$this->setTemplate(...);
```

## Ошибка 3. Читать route params через GET

Нельзя:

```php
$id = waRequest::get('id', 0, waRequest::TYPE_INT);
```

для route:

```php
'orders/<id:\d+>/' => 'orders/view'
```

Правильно:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
```

## Ошибка 4. Делать POST через `waViewAction` как страницу

Для save/delete лучше:

```php
class myappOrdersSaveController extends waJsonController
```

или:

```php
class myappOrdersDeleteController extends waController
```

## Ошибка 5. Писать SQL в Smarty

Нельзя выносить запросы в template. Данные готовятся в model/service/action.

## Ошибка 6. Писать пользовательские файлы в `wa-apps`

Для upload использовать:

```php
wa()->getDataPath(...);
```

а не:

```text
wa-apps/myapp/
```

## Ошибка 7. Хардкодить `/webasyst/`

Правильно:

```php
wa()->getAppUrl('myapp')
wa()->getRouteUrl('myapp/frontend')
```

---

# 26. Чеклист разработчика

Перед commit проверить:

- [ ] Class name соответствует app/plugin prefix.
- [ ] File name соответствует class name.
- [ ] Route даёт ожидаемые `module/action`.
- [ ] Route params читаются через `waRequest::param()`.
- [ ] GET/POST типизированы.
- [ ] Для POST есть rights и CSRF.
- [ ] Для `waViewAction` есть template.
- [ ] Для model указан `$table`.
- [ ] SQL использует placeholders.
- [ ] URLs строятся через Webasyst helpers.
- [ ] UI соответствует `app.php` → `ui`.
- [ ] Plugin settings сохраняются через `saveSettings()`.
- [ ] Upload пишет в `wa-data`.
- [ ] Smarty не содержит PHP и SQL.

---

# 27. Чеклист ИИ-агента

Перед использованием skeleton:

1. Не считать skeleton готовым решением.
2. Сначала открыть текущий app/plugin/theme.
3. Проверить существующие классы.
4. Проверить naming по текущему app id.
5. Проверить routes.
6. Проверить template path.
7. Проверить model/table.
8. Проверить rights/security.
9. Проверить UI-version.
10. Указать, какие файлы создать или изменить.
11. Дать код цельными файлами/классами/методами.
12. Не утверждать совместимость, если репозиторий не проверен.

---

## 28. Мини-сводка

Skeleton в Webasyst полезен только если он сохраняет цепочку:

```text
route
→ module/action/plugin
→ class name
→ file path
→ controller/action lifecycle
→ model/service
→ template/layout
→ rights/security
```

Если одна часть цепочки не совпадает, Webasyst либо не найдёт класс, либо откроет не тот action, либо покажет пустой/ошибочный шаблон.
