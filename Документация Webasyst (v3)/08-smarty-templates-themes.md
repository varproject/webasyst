# 08. Smarty, app templates и theme templates в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить, как Webasyst выбирает и рендерит Smarty-шаблоны приложения, backend layout/action templates, frontend theme templates и plugin templates.

---

## 1. Назначение механизма

В Webasyst шаблон — это не самостоятельная точка входа и не место для бизнес-логики. Шаблон является последним слоем render-flow:

```text
routing
→ waFrontController
→ Controller / Action / Actions
→ waView / waSmarty3View
→ app template или theme template
→ waResponse headers
→ HTML
```

Шаблон получает уже подготовленные данные через:

```php
$this->view->assign('name', $value);
```

или через layout blocks:

```php
$this->setBlock('content', $html);
$this->layout->assign('sidebar', $sidebar);
```

Задача шаблона:

- вывести данные;
- собрать HTML-разметку;
- вызвать разрешённые Smarty/helper-функции;
- подключить уже зарегистрированные CSS/JS через `$wa->css()`, `$wa->js()`, `$wa->head()`;
- использовать theme settings и route params, если они были системно переданы.

Шаблон не должен:

- читать напрямую из БД;
- выполнять сложную бизнес-логику;
- обрабатывать POST;
- проверять права как единственный слой безопасности;
- создавать собственный routing;
- содержать PHP-код.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/view/waView.class.php` | Абстрактный view; назначает глобальные переменные `$wa`, `$wa_url`, `$wa_app_url`, `$wa_backend_url`; управляет theme template context. |
| `wa-system/view/waSmarty3View.class.php` | Реализация view на Smarty 3; `assign()`, `fetch()`, `display()`, cache, compile dir, template dir. |
| `wa-system/view/waViewHelper.class.php` | Объект `$wa` в Smarty: URL, assets, user, app, route, request, head/css/js и др. |
| `wa-system/controller/waViewAction.class.php` | HTML action; автоматически ищет template по имени класса или использует `setTemplate()`/`setThemeTemplate()`. |
| `wa-system/controller/waViewActions.class.php` | Multi-action controller с template lookup по action method. |
| `wa-system/layout/waLayout.class.php` | Layout; собирает blocks и рендерит layout template или theme `index.html`. |
| `wa-system/controller/waActionTemplatePathBuilder.trait.php` | Строит пути к templates/actions, templates/layouts и legacy variants с учётом UI версии. |

### 2.2. Файлы приложения

| Файл/директория | Роль |
|---|---|
| `wa-apps/{app_id}/templates/actions/{module}/...` | Backend/app templates для `waViewAction` и `waViewActions`. |
| `wa-apps/{app_id}/templates/actions-legacy/{module}/...` | Legacy backend templates для UI 1.3, если приложение поддерживает старый UI. |
| `wa-apps/{app_id}/templates/layouts/...` | App layout templates. |
| `wa-apps/{app_id}/templates/layouts-legacy/...` | Legacy layout templates. |
| `wa-apps/{app_id}/themes/{theme_id}/...` | Frontend theme templates: `index.html`, `page.html`, `error.html`, `home.html`, `product.html` и др. |
| `wa-apps/{app_id}/themes/{theme_id}/theme.xml` | Описание темы, файлов, настроек, локалей, parent theme. |
| `wa-apps/{app_id}/lib/actions/...` | Actions/controllers, которые назначают данные в view и выбирают template. |
| `wa-apps/{app_id}/lib/layouts/...` | Layout-классы приложения. |

### 2.3. Файлы plugin-а

| Файл/директория | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/...` | Plugin actions/controllers. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/...` | Plugin action templates. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions-legacy/...` | Legacy plugin templates. |
| `wa-apps/{app_id}/plugins/{plugin_id}/templates/layouts/...` | Plugin layouts, если plugin использует собственный layout. |

---

## 3. Системная цепочка выполнения

### 3.1. Backend/app template flow

Пример route/dispatch:

```text
/webasyst/myapp/orders/123/
→ routing.backend.php
→ module=orders, action=view, id=123
→ waFrontController::getController()
→ myappOrdersViewAction
→ waDefaultViewController
→ waViewAction::display()
→ templates/actions/orders/OrdersView.html
```

Action:

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
<h1>{$order.id|escape}</h1>
<p>{$order.name|escape}</p>
```

### 3.2. Frontend theme template flow

Пример frontend route:

```text
/catalog/product-slug/
→ waRouting::dispatch()
→ app=shop, module=frontend, action=product, product_url=product-slug
→ shopFrontendProductAction
→ setLayout(new shopFrontendLayout())
→ setThemeTemplate('product.html')
→ shopFrontendLayout::setThemeTemplate('index.html')
→ theme index.html receives content block
```

Во frontend обычно два template-уровня:

1. **Action theme template** — например `product.html`, `stream.html`, `page.html`.
2. **Layout theme template** — обычно `index.html`.

Action template формирует основной контент. Layout template оборачивает его в общую страницу темы.

### 3.3. Layout block flow

Для `waViewController`:

```text
Controller::execute()
→ setLayout(new myappBackendLayout())
→ executeAction(new myappSidebarAction(), 'sidebar')
→ executeAction(new myappContentAction(), 'content')
→ waViewController::display()
→ layout->setBlock('sidebar', ...)
→ layout->setBlock('content', ...)
→ layout->display()
→ templates/layouts/Backend.html
```

Для layout block в Smarty:

```smarty
<aside>{$sidebar}</aside>
<main>{$content}</main>
```

---

## 4. Ключевые классы и методы

## 4.1. `waView`

`waView` — общий абстрактный слой view.

Ключевые обязанности:

- хранить template extension `.html`;
- создавать `$wa` helper через `waViewHelper`;
- назначать глобальные переменные;
- задавать theme template context через `setThemeTemplate()`.

Глобальные переменные назначаются системно:

```php
$this->assign(array(
    'wa_url'                 => $wa->getRootUrl(),
    'wa_static_url'          => $this->getStaticUrl($wa->getRootUrl()),
    'wa_backend_url'         => waSystem::getInstance()->getConfig()->getBackendUrl(true),
    'wa_app'                 => $wa->getApp(),
    'wa_app_url'             => $wa->getAppUrl(null, true),
    'wa_app_static_url'      => $this->getStaticUrl($wa->getAppStaticUrl()),
    'wa_real_app_static_url' => $wa->getAppStaticUrl(),
    'wa'                     => $this->getHelper()
));
```

Практический вывод: в шаблонах не нужно вручную собирать базовые URL и helper object.

---

## 4.2. `waSmarty3View`

`waSmarty3View` — стандартная реализация view на Smarty 3.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `assign($name, $value = null, $escape = false)` | Передаёт данные в Smarty. |
| `fetch($template, $cache_id = null)` | Возвращает HTML как строку. |
| `display($template, $cache_id = null)` | Выводит HTML напрямую. |
| `templateExists($template)` | Проверяет наличие template. |
| `setTemplateDir($path)` | Задаёт template directory. |
| `cache($lifetime)` | Управляет Smarty cache. |
| `autoescape($value = null)` | Управляет Smarty autoescape. |

Важно: `assign(..., true)` применяет `htmlspecialchars()` на PHP-стороне. Но чаще в Webasyst-коде escaping делают в Smarty через `|escape`, особенно если данные выводятся в разных контекстах.

---

## 4.3. `waViewHelper`

`waViewHelper` — объект `$wa` в Smarty.

Частые методы и переменные:

| В Smarty | Назначение |
|---|---|
| `{$wa->css()}` | Вывод CSS, зарегистрированный через response; в backend также подключает Webasyst UI CSS. |
| `{$wa->js()}` | Вывод JS, зарегистрированный через response. |
| `{$wa->head()}` | Frontend head: canonical, OG, favicon, domain head JS, events и др. |
| `{$wa->getUrl('app/module/action', $params)}` | Генерация route URL. |
| `{$wa->param('name')}` | Доступ к `waRequest::param()`. |
| `{$wa->get('name')}` | Доступ к GET. |
| `{$wa->post('name')}` | Доступ к POST. Использовать осторожно, не вместо action/controller. |
| `{$wa->user()}` | Текущий пользователь. |
| `{$wa->userId()}` | ID пользователя. |
| `{$wa->app()}` | Текущий app id. |
| `{$wa->appName()}` | Название приложения. |
| `{$wa->version()}` | Версия приложения для asset versioning. |

Практический вывод: `$wa` — это view helper, а не замена action/controller/model. Он удобен для URL, assets, meta/head, текущего пользователя и легких presentation helpers.

---

## 4.4. `waViewAction`

`waViewAction` рендерит один template.

Если template не задан явно, Webasyst строит template name по классу.

Класс:

```php
myappOrdersViewAction
```

После удаления app prefix и суффикса `Action` остаётся:

```text
OrdersView
```

Путь:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

Ключевые методы:

| Метод | Назначение |
|---|---|
| `execute()` | Подготовить данные. |
| `setTemplate($template, $is_relative = false)` | Задать app/plugin template вручную. |
| `setThemeTemplate($template)` | Задать frontend theme template. |
| `display($clear_assign = true)` | Выполнить action и вернуть HTML. |
| `setLayout(waLayout $layout = null)` | Подключить layout. |

---

## 4.5. `waViewActions`

`waViewActions` — multi-action controller.

Класс:

```php
class myappOrdersActions extends waViewActions
{
    public function defaultAction()
    {
    }

    public function viewAction()
    {
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
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

`defaultAction()` ищет template `Orders.html`.  
`viewAction()` ищет template `OrdersView.html`.

---

## 4.6. `waLayout`

`waLayout` рендерит layout template и получает blocks.

Ключевые методы:

| Метод | Назначение |
|---|---|
| `setBlock($name, $content)` | Добавляет HTML block. Если block уже есть, дописывает HTML. |
| `assign($name, $value)` | Назначает значение в blocks. |
| `executeAction($name, $action, $decorator = null)` | Выполняет action и кладёт результат в block. |
| `setThemeTemplate($template)` | Для frontend theme layout, обычно `index.html`. |
| `display()` | Выполняет layout, назначает blocks в view, fetch template, отправляет headers, echo HTML. |

Layout templates лежат в:

```text
wa-apps/{app_id}/templates/layouts/{Name}.html
```

или во frontend theme:

```text
wa-apps/{app_id}/themes/{theme_id}/index.html
```

---

## 5. Где лежат templates

### 5.1. Backend/app action templates

Для `waViewAction`:

```text
wa-apps/{app_id}/templates/actions/{module}/{Module}{Action}.html
```

Пример:

```text
class myappOrdersViewAction
→ wa-apps/myapp/templates/actions/orders/OrdersView.html
```

Для default action:

```text
class myappOrdersAction
→ wa-apps/myapp/templates/actions/orders/Orders.html
```

### 5.2. Backend/app layout templates

```text
wa-apps/{app_id}/templates/layouts/{LayoutName}.html
```

Пример:

```text
class myappBackendLayout
→ wa-apps/myapp/templates/layouts/Backend.html
```

### 5.3. Legacy templates

Если backend UI выбран как `1.3`, template path builder может сначала искать legacy templates:

```text
wa-apps/{app_id}/templates/actions-legacy/{module}/...
wa-apps/{app_id}/templates/layouts-legacy/...
```

Если legacy template не найден, используется обычный `templates/actions/` или `templates/layouts/`.

Это важно для приложений и plugin-ов, которые поддерживают оба UI режима.

### 5.4. Frontend theme templates

Frontend action может выбрать theme template:

```php
$this->setThemeTemplate('product.html');
```

Layout может выбрать общий theme wrapper:

```php
$this->setThemeTemplate('index.html');
```

Файлы лежат в active theme:

```text
wa-apps/{app_id}/themes/{theme_id}/index.html
wa-apps/{app_id}/themes/{theme_id}/product.html
wa-apps/{app_id}/themes/{theme_id}/page.html
wa-apps/{app_id}/themes/{theme_id}/error.html
```

### 5.5. Plugin templates

Plugin action:

```php
class shopRedirectPluginSettingsAction extends waViewAction
{
}
```

Файл:

```text
wa-apps/shop/plugins/redirect/lib/actions/settings/shopRedirectPluginSettings.action.php
```

Template:

```text
wa-apps/shop/plugins/redirect/templates/actions/settings/Settings.html
```

Для plugin-а Webasyst удаляет prefix до `Plugin` и строит template name из части после plugin id.

---

## 6. Автоопределение template по классу

### 6.1. `waViewAction`

Класс:

```php
class myappCatalogProductEditAction extends waViewAction
{
}
```

Dispatch params:

```text
module=catalog
action=productEdit
```

Файл класса:

```text
wa-apps/myapp/lib/actions/catalog/myappCatalogProductEdit.action.php
```

Template:

```text
wa-apps/myapp/templates/actions/catalog/CatalogProductEdit.html
```

### 6.2. `waViewActions`

Класс:

```php
class myappCatalogActions extends waViewActions
{
    public function productEditAction()
    {
    }
}
```

Файл класса:

```text
wa-apps/myapp/lib/actions/catalog/myappCatalog.actions.php
```

Template:

```text
wa-apps/myapp/templates/actions/catalog/CatalogProductEdit.html
```

### 6.3. `waLayout`

Класс:

```php
class myappBackendLayout extends waLayout
{
}
```

Файл класса:

```text
wa-apps/myapp/lib/layouts/myappBackend.layout.php
```

Template:

```text
wa-apps/myapp/templates/layouts/Backend.html
```

---

## 7. `setTemplate()`

`setTemplate()` нужен, когда template name не должен вычисляться из имени класса.

Пример:

```php
<?php

class myappOrdersPrintAction extends waViewAction
{
    public function execute()
    {
        $this->setTemplate('orders/Print.html', true);
    }
}
```

Параметр `true` означает, что путь относительный от templates directory текущего app/plugin.

Итоговый путь:

```text
wa-apps/myapp/templates/actions/orders/Print.html
```

Использовать `setTemplate()` стоит точечно:

- когда несколько actions используют один template;
- когда template name намеренно отличается от class name;
- когда нужен partial template;
- когда сохраняется обратная совместимость.

Не нужно использовать `setTemplate()` для каждого action, если автопоиск по naming работает.

---

## 8. `setThemeTemplate()`

`setThemeTemplate()` переключает view на template из активной frontend theme.

Action:

```php
class myappFrontendProductAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new myappFrontendLayout());
        $this->setThemeTemplate('product.html');
    }
}
```

Layout:

```php
class myappFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->setThemeTemplate('index.html');
    }
}
```

`waView::setThemeTemplate()` дополнительно назначает в Smarty:

```text
wa_active_theme_path
wa_active_theme_url
wa_real_active_theme_url
wa_theme_version
theme_settings
theme_settings_config
wa_theme_url
wa_real_theme_url
wa_parent_theme_url
wa_parent_theme_path
```

Практический вывод: theme template должен использовать эти переменные, а не собирать путь к теме вручную.

---

## 9. Глобальные переменные Smarty

Системно доступны:

| Переменная | Назначение |
|---|---|
| `$wa` | `waViewHelper`. |
| `$wa_url` | Root URL Webasyst. |
| `$wa_static_url` | CDN-aware static root URL. |
| `$wa_backend_url` | Backend URL с root prefix. |
| `$wa_app` | Текущий app id. |
| `$wa_app_url` | URL текущего приложения. |
| `$wa_app_static_url` | CDN-aware URL статики приложения. |
| `$wa_real_app_static_url` | Реальный URL статики приложения без CDN-wrapper. |

Для theme templates дополнительно:

| Переменная | Назначение |
|---|---|
| `$wa_active_theme_path` | Абсолютный путь к активной теме. |
| `$wa_active_theme_url` | CDN-aware URL активной темы. |
| `$wa_real_active_theme_url` | Реальный URL активной темы. |
| `$wa_theme_url` | URL темы, из которой берётся текущий template. |
| `$wa_real_theme_url` | Реальный URL темы. |
| `$wa_theme_version` | Версия темы для asset cache busting. |
| `$theme_settings` | Значения настроек темы. |
| `$theme_settings_config` | Конфигурация настроек темы. |
| `$wa_parent_theme_url` | URL parent theme, если используется наследование. |
| `$wa_parent_theme_path` | Path parent theme. |

---

## 10. Assets в templates

### 10.1. Backend/app templates

CSS/JS лучше регистрировать в action/layout:

```php
$this->getResponse()->addCss('css/backend.css', true);
$this->getResponse()->addJs('js/backend.js', true);
```

А в layout/template выводить:

```smarty
{$wa->css()}
{$wa->js()}
```

Для backend UI 2.0 `$wa->css()` также подключает `wa-2.0.css` и базовые системные assets. Если текущий UI не 2.0, helper переключается на legacy CSS.

### 10.2. Frontend theme templates

В `index.html` темы обычно нужны:

```smarty
{$wa->head()}
{$wa->css()}
{$wa->js()}
```

`$wa->head()` выводит meta/canonical/OG/domain head JS/frontend head events и другое содержимое, которое накоплено в response и domain config.

### 10.3. Asset versioning

Для theme assets:

```smarty
<link rel="stylesheet" href="{$wa_theme_url}css/style.css?v{$wa_theme_version}">
<script src="{$wa_theme_url}js/app.js?v{$wa_theme_version}"></script>
```

Для app assets:

```smarty
<script src="{$wa_app_static_url}js/backend.js?v{$wa->version()}"></script>
```

Но если asset уже зарегистрирован через response, дублировать его вручную в template не нужно.

---

## 11. Escaping

### 11.1. Базовое правило

Любые пользовательские данные выводить с escaping:

```smarty
{$order.name|escape}
{$customer.email|escape}
```

Для HTML-атрибутов:

```smarty
<a href="{$url|escape}">{$label|escape}</a>
```

Для уже безопасного HTML использовать осознанно:

```smarty
{$content nofilter}
```

`nofilter` допустим только если HTML сформирован доверенным источником или уже очищен/валидирован.

### 11.2. Где лучше делать escaping

Варианты:

| Вариант | Плюсы | Минусы |
|---|---|---|
| Escape в Smarty через `|escape` | Видно контекст вывода; удобно для HTML/атрибутов. | Нужно дисциплинированно ставить в каждом месте. |
| Escape в PHP через `$this->view->assign($name, $value, true)` | Можно быстро обезопасить простое значение. | Потеря контекста; может быть неудобно для массивов и разных типов вывода. |
| Escape в model/service | Иногда удобно для prepared view data. | Модель начинает знать о presentation layer; использовать осторожно. |

Рекомендация: по умолчанию escaping в Smarty, а в PHP — только если action формирует уже presentation-specific данные.

---

## 12. `{include}` и partial templates

Для повторяемых фрагментов можно использовать Smarty include:

```smarty
{include file="./includes/filter.html"}
```

или app-relative template path, если template dir настроен соответствующим образом.

Правила:

- partial не должен выполнять бизнес-логику;
- partial получает уже назначенные переменные;
- partial не должен делать SQL/request-side effects;
- partial должен использовать escaping так же строго, как основной template.

Если partial нужен в нескольких actions, лучше:

1. подготовить данные в action/service;
2. вынести HTML в include;
3. не копировать одинаковую разметку по нескольким templates.

---

## 13. Паттерн официального Webasyst-кода

### 13.1. Blog: frontend stream

`blogFrontendAction`:

- наследуется от app-specific `blogViewAction`;
- в constructor выбирает layout `blogFrontendLayout`;
- вызывает `setThemeTemplate('stream.html')`;
- в `execute()` читает route params и GET;
- получает данные через `blogPostModel`;
- назначает данные в view;
- выставляет canonical через response.

`blogFrontendLayout`:

- добавляет JS через response;
- назначает данные для layout;
- вызывает frontend events;
- выбирает theme `index.html`.

Pattern:

```text
Action → stream.html
Layout → index.html
```

### 13.2. Shop: frontend homepage

`shopFrontendAction`:

- в constructor подключает `shopFrontendLayout`, если запрос не AJAX;
- в `execute()` назначает meta, OG, homepage data;
- вызывает `setThemeTemplate('home.html')`;
- в `display()` назначает frontend nav events и globals.

`shopFrontendLayout`:

- обрабатывает currency/locale redirect;
- вызывает `setThemeTemplate('index.html')`;
- назначает frontend events: head/header/nav/footer;
- назначает currencies и globals.

Pattern:

```text
Action отвечает за page-specific data.
Layout отвечает за общий shell, events, global UI data.
Theme template отвечает за HTML.
```

### 13.3. Site: pages

`siteFrontendAction` наследуется от `waPageAction` и рендерит страницы из Site:

- получает page data;
- назначает `$page`;
- рендерит content;
- назначает breadcrumbs;
- выбирает `page.html` или `error.html`.

`waPageAction` — системный pattern для apps с `pages => true`.

### 13.4. Backend layouts

`shopBackendLayout` показывает backend pattern:

- layout может читать settings;
- определять активную страницу;
- назначать backend menu events;
- назначать counters и layout flags;
- но не должен подменять action-логику конкретной страницы.

---

## 14. Минимальная реализация: backend action + template

### 14.1. Action

```php
<?php

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

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.action.php
```

### 14.2. Template

```text
wa-apps/myapp/templates/actions/orders/Orders.html
```

```smarty
<div class="article">
    <div class="article-body">
        <h1>Orders</h1>

        {if $orders}
            <table class="zebra">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $orders as $order}
                        <tr>
                            <td>{$order.id|escape}</td>
                            <td>{$order.name|escape}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <p class="hint">No orders yet.</p>
        {/if}
    </div>
</div>
```

---

## 15. Минимальная реализация: frontend action + theme template

### 15.1. Action

```php
<?php

class myappFrontendArticleAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setLayout(new myappFrontendLayout());
        $this->setThemeTemplate('article.html');
    }

    public function execute()
    {
        $url = waRequest::param('url', '', waRequest::TYPE_STRING_TRIM);

        if (!$url) {
            throw new waException('Article not found', 404);
        }

        $model = new myappArticleModel();
        $article = $model->getByField('url', $url);

        if (!$article) {
            throw new waException('Article not found', 404);
        }

        $this->getResponse()->setTitle($article['title']);
        $this->view->assign('article', $article);
    }
}
```

### 15.2. Layout

```php
<?php

class myappFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->setThemeTemplate('index.html');
    }
}
```

### 15.3. Theme template

```text
wa-apps/myapp/themes/default/article.html
```

```smarty
<article class="article">
    <h1>{$article.title|escape}</h1>
    <div class="article-content">
        {$article.content nofilter}
    </div>
</article>
```

`nofilter` здесь допустим только если `article.content` хранится как доверенный HTML или проходит sanitization.

---

## 16. Расширенная реализация: layout + blocks

### 16.1. Controller

```php
<?php

class myappDashboardController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());

        $this->executeAction(new myappDashboardSidebarAction(), 'sidebar');
        $this->executeAction(new myappDashboardContentAction(), 'content');
    }
}
```

### 16.2. Layout

```php
<?php

class myappBackendLayout extends waLayout
{
    public function execute()
    {
        $this->getResponse()->addCss('css/backend.css', true);
        $this->getResponse()->addJs('js/backend.js', true);

        $this->assign('app_url', wa()->getAppUrl('myapp'));
    }
}
```

### 16.3. Layout template

```text
wa-apps/myapp/templates/layouts/Backend.html
```

```smarty
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$wa->title()|escape}</title>
    {$wa->css()}
</head>
<body>
    <div class="sidebar">
        {$sidebar|default:''}
    </div>
    <main class="content">
        {$content|default:''}
    </main>
    {$wa->js()}
</body>
</html>
```

---

## 17. Типовые ошибки

### Ошибка 1. Писать PHP в Smarty

Неправильно:

```smarty
{php}
$model = new myappOrderModel();
{/php}
```

Правильно:

```php
$orders = $model->order('id DESC')->fetchAll();
$this->view->assign('orders', $orders);
```

```smarty
{foreach $orders as $order}
    {$order.name|escape}
{/foreach}
```

### Ошибка 2. Читать БД из template через helper без необходимости

Неправильно:

```smarty
{$wa->myapp->orders()}
```

если этот helper внутри делает сложный SQL для текущей страницы.

Правильно:

```text
Action/service/model готовит данные → template выводит.
```

Helper допустим для маленьких reusable presentation helpers, но не как замена action/model layer.

### Ошибка 3. Хардкодить `/webasyst/` или `/wa-apps/`

Неправильно:

```smarty
<a href="/webasyst/myapp/orders/">Orders</a>
<script src="/wa-apps/myapp/js/backend.js"></script>
```

Правильно:

```smarty
<a href="{$wa_app_url}orders/">Orders</a>
<script src="{$wa_app_static_url}js/backend.js?v{$wa->version()}"></script>
```

Или лучше зарегистрировать asset через response и вывести `{$wa->js()}`.

### Ошибка 4. Использовать `nofilter` без причины

Неправильно:

```smarty
{$customer.name nofilter}
```

Правильно:

```smarty
{$customer.name|escape}
```

### Ошибка 5. Назначать route params в template

Неправильно:

```smarty
{assign var=id value=$wa->get('id')}
```

Правильно:

```php
$id = waRequest::param('id', 0, waRequest::TYPE_INT);
$this->view->assign('id', $id);
```

### Ошибка 6. Делать форму без CSRF

Неправильно:

```smarty
<form method="post">
    <input name="name">
</form>
```

Правильно:

```smarty
<form method="post">
    {$wa->csrf()}
    <input name="name" value="{$name|escape}">
</form>
```

Для `{$wa->csrf()}` нужно использовать системный helper, а не собственный token.

### Ошибка 7. Путать app template и theme template

Неправильно:

```php
$this->setTemplate('product.html');
```

если нужен frontend theme file.

Правильно:

```php
$this->setThemeTemplate('product.html');
```

### Ошибка 8. Создавать template с неправильным именем

Класс:

```php
myappOrdersViewAction
```

Неправильный template:

```text
templates/actions/orders/View.html
```

Правильный template:

```text
templates/actions/orders/OrdersView.html
```

### Ошибка 9. Игнорировать UI 1.3/2.0 variants

Если приложение поддерживает оба UI режима, нельзя удалять legacy templates без проверки.

Система может искать:

```text
templates/actions-legacy/...
templates/actions/...
```

в зависимости от `wa()->whichUI()` и backend environment.

### Ошибка 10. Переносить page-specific логику в layout

Неправильно:

```text
Layout сам выбирает заказ, товар, форму редактирования и валидирует POST.
```

Правильно:

```text
Action/controller отвечает за конкретную страницу.
Layout отвечает за общий shell, assets, global blocks, events.
```

---

## 18. Чеклист разработчика

Перед commit template/action проверить:

### Template path

- [ ] Класс action/controller назван по Webasyst naming.
- [ ] Template лежит в `templates/actions/{module}/`.
- [ ] Имя template соответствует классу или явно задано через `setTemplate()`.
- [ ] Для frontend theme используется `setThemeTemplate()`.
- [ ] Для layout template путь соответствует `templates/layouts/{Name}.html`.
- [ ] Для plugin template путь находится внутри `plugins/{plugin_id}/templates/...`.

### Data flow

- [ ] Все данные подготовлены в action/controller/model/service.
- [ ] Template не читает БД.
- [ ] Template не обрабатывает POST.
- [ ] Template не проверяет права как единственный слой защиты.
- [ ] Route params читаются в PHP через `waRequest::param()`.
- [ ] GET/POST читаются в PHP через `waRequest::get()` / `waRequest::post()`.

### Escaping/security

- [ ] Пользовательские данные выводятся через `|escape`.
- [ ] `nofilter` используется только для доверенного HTML.
- [ ] Формы POST содержат `{$wa->csrf()}`.
- [ ] Backend save/delete всё равно проверяет CSRF и права на PHP-стороне.
- [ ] URL не собраны hardcode-строками.

### Assets

- [ ] CSS/JS зарегистрированы через response или используют системные URL-переменные.
- [ ] В layout есть `{$wa->css()}` и `{$wa->js()}`, если используются response assets.
- [ ] Во frontend theme есть `{$wa->head()}`, если нужны canonical/meta/OG/events.
- [ ] Asset versioning использует `$wa->version()` или `$wa_theme_version`.

### UI

- [ ] Если backend UI 2.0 — template использует корректную UI 2.0 разметку.
- [ ] Если поддерживается UI 1.3 — legacy templates не сломаны.
- [ ] Layout не смешивает несовместимые UI frameworks без необходимости.

---

## 19. Чеклист ИИ-агента

Перед ответом по Smarty/templates/themes ИИ-агент обязан:

1. Определить context: backend app template, frontend theme template или plugin template.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить `ui`, `themes`, `pages`, `csrf`, `plugins`.
4. Открыть route: `routing.php` или `routing.backend.php`.
5. Определить `module/action`.
6. Открыть action/controller/actions класс.
7. Проверить, используется ли `setTemplate()` или `setThemeTemplate()`.
8. Открыть layout, если action вызывает `setLayout()`.
9. Открыть фактический template path.
10. Проверить, какие переменные назначаются через `$this->view->assign()` и `$layout->assign()`.
11. Проверить escaping в Smarty.
12. Проверить CSRF в POST-формах.
13. Проверить URL/assets: нет ли hardcode `/webasyst/`, `/wa-apps/`, domain/settlement.
14. Проверить UI 1.3/2.0 template variants.
15. Только после этого писать template или PHP-код.

ИИ-агенту запрещено:

- писать PHP-код в Smarty;
- читать БД из template;
- создавать собственный CSRF;
- использовать raw `nofilter` без объяснения источника HTML;
- придумывать template path без проверки class naming;
- смешивать app templates и theme templates;
- хардкодить backend URL, app URL, theme URL;
- игнорировать `setThemeTemplate()` во frontend;
- игнорировать legacy templates при UI 1.3 support.

---

## 20. Мини-сводка

Smarty/templates в Webasyst — это render layer, который подключается после routing и controller/action layer.

Правильная цепочка backend action template:

```text
module/action
→ myappOrdersViewAction
→ execute()
→ $this->view->assign('order', $order)
→ templates/actions/orders/OrdersView.html
→ HTML
```

Правильная цепочка frontend theme template:

```text
frontend route
→ myappFrontendProductAction
→ setLayout(new myappFrontendLayout())
→ setThemeTemplate('product.html')
→ myappFrontendLayout::setThemeTemplate('index.html')
→ themes/default/product.html
→ themes/default/index.html
→ HTML
```

Правильная ответственность:

```text
Model/service → данные
Action/controller → сценарий страницы, request, response, assign
Layout → shell, blocks, global assets/events
Smarty template → безопасный HTML-output
```

Если template начинает делать routing, SQL, POST-processing или security checks вместо PHP-слоя — архитектура нарушена.
