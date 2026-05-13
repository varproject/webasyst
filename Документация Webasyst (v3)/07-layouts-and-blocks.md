# 07. Layouts и blocks в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить, как Webasyst собирает HTML-страницу из action-блоков, layout-шаблона, response headers/assets и theme template.

---

## 1. Назначение механизма

`layout` в Webasyst — это не просто общий HTML-шаблон. Это controller-слой, который:

1. принимает HTML-блоки от `waViewController` или `waViewAction`;
2. выполняет собственный `execute()`;
3. назначает переменные во view;
4. выбирает layout-шаблон;
5. отправляет HTTP headers через `waResponse`;
6. выводит итоговый HTML.

Типовая цепочка:

```text
route
→ waFrontController
→ action/controller
→ action->setLayout(new appLayout())
→ action->display()
→ waDefaultViewController / waViewController
→ layout->setBlock('content', $html)
→ layout->execute()
→ layout template
→ response headers
→ итоговый HTML
```

Layout нужен, когда страница имеет общий shell:

- frontend theme shell `index.html`;
- backend shell приложения;
- общие меню, header, sidebar, footer;
- подключение CSS/JS;
- события layout-level;
- несколько action-блоков на одной странице.

Если action возвращает один HTML-фрагмент без общего shell, layout может быть не нужен.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/layout/waLayout.class.php` | Базовый layout-controller. Хранит blocks, выбирает шаблон, отправляет headers. |
| `wa-system/controller/waViewController.class.php` | Controller, который собирает `waViewAction`-блоки и передаёт их в layout. |
| `wa-system/controller/waViewAction.class.php` | Action, который может установить layout и вернуть HTML своего шаблона. |
| `wa-system/controller/waDefaultViewController.class.php` | Обёртка для одиночного `waViewAction`; передаёт результат action в блок `content`. |
| `wa-system/view/waView.class.php` | Базовый view-слой. |
| `wa-system/view/waSmarty3View.class.php` | Smarty view-реализация. |
| `wa-system/response/waResponse.class.php` | Headers, title, meta, CSS/JS, redirect. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/layouts/{app_id}{Name}.layout.php` | Layout class приложения. |
| `wa-apps/{app_id}/templates/layouts/{Name}.html` | Backend/app layout template. |
| `wa-apps/{app_id}/templates/layouts-legacy/{Name}.html` | Legacy layout template, если используется старый UI. |
| `wa-apps/{app_id}/lib/actions/{module}/...` | Controllers/actions, которые устанавливают layout или собирают blocks. |
| `wa-apps/{app_id}/templates/actions/{module}/...` | Action templates, HTML которых попадает в layout block. |

### 2.3. Файлы темы frontend

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/themes/{theme_id}/index.html` | Основной frontend layout-шаблон темы. |
| `wa-apps/{app_id}/themes/{theme_id}/home.html` | Action template для homepage или другого frontend action. |
| `wa-apps/{app_id}/themes/{theme_id}/page.html` | Theme template для pages. |
| `wa-apps/{app_id}/themes/{theme_id}/error.html` | Theme template для ошибок. |

Во frontend часто используется `setThemeTemplate('index.html')` внутри layout и `setThemeTemplate('home.html')`, `setThemeTemplate('post.html')`, `setThemeTemplate('page.html')` внутри action.

---

## 3. Системная цепочка выполнения

### 3.1. Одиночный `waViewAction` с layout

Route ведёт к action:

```text
module = frontend
action = product
```

`waFrontController` находит:

```text
shopFrontendProductAction
```

Если найден одиночный action, он запускается через `waDefaultViewController`:

```text
waDefaultViewController
→ getAction()
→ executeAction($action, 'content')
→ action->display()
→ layout->setBlock('content', $html)
→ layout->display()
```

Action может установить layout в constructor или `execute()`:

```php
$this->setLayout(new shopFrontendLayout());
```

### 3.2. `waViewController` с несколькими blocks

Controller сам собирает страницу:

```php
class myappBackendController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());

        $this->executeAction(new myappBackendSidebarAction(), 'sidebar');
        $this->executeAction(new myappBackendContentAction(), 'content');
    }
}
```

Цепочка:

```text
controller->execute()
→ executeAction(sidebar)
→ executeAction(content)
→ controller->display()
→ layout->setBlock('sidebar', ...)
→ layout->setBlock('content', ...)
→ layout->display()
```

### 3.3. Layout display

`waLayout::display()` делает:

1. вызывает `layout->execute()`;
2. назначает `$this->blocks` во view;
3. выбирает template через `getTemplate()`;
4. fetch’ит HTML;
5. отправляет headers;
6. выводит HTML.

То есть layout — это исполняемый controller, а не пассивный include.

---

## 4. Ключевые классы и методы

## 4.1. `waLayout`

Базовый layout class:

```php
class waLayout extends waController
```

Ключевые поля:

| Поле | Назначение |
|---|---|
| `$blocks` | Массив HTML-блоков и layout-переменных. |
| `$template` | Явно заданный template или `null` для автопоиска. |
| `$view` | View instance. Обычно `waSmarty3View`. |
| `$theme` | Theme instance для frontend theme templates. |

Ключевые методы:

| Метод | Назначение |
|---|---|
| `setBlock($name, $content)` | Добавляет HTML в block. Повторный block дописывается. |
| `assign($name, $value)` | Назначает значение в `$blocks` без дописывания. |
| `executeAction($name, $action, $decorator = null)` | Выполняет action и кладёт результат в block. |
| `execute()` | Место для подготовки layout-переменных, assets, events. |
| `display()` | Выполняет layout, назначает blocks во view, fetch’ит template и отправляет headers. |
| `setThemeTemplate($template)` | Использует template из текущей frontend theme. |
| `getTemplateDir()` | Возвращает `templates/layouts/`. |
| `getLegacyTemplateDir()` | Возвращает `templates/layouts-legacy/`. |

### 4.2. `waViewController`

`waViewController` нужен для page orchestration:

```php
abstract class waViewController extends waController
```

Ключевые методы:

| Метод | Назначение |
|---|---|
| `setLayout(waLayout $layout = null)` | Устанавливает layout. |
| `executeAction(waViewAction $action, $name = 'content', waDecorator $decorator = null)` | Выполняет action и сохраняет HTML в `$this->blocks[$name]`. |
| `display()` | Если layout есть — передаёт blocks в layout; если layout нет — выводит blocks напрямую. |

### 4.3. `waViewAction`

`waViewAction` может работать с layout напрямую:

```php
$this->setLayout(new blogFrontendLayout());
```

Action render-flow:

```text
preExecute()
→ execute()
→ afterExecute()
→ view->fetch(action template)
```

Если action установлен через `waDefaultViewController`, итоговый HTML action попадает в layout block `content`.

---

## 5. Параметры и источники данных

Layout обычно получает данные из нескольких источников.

| Источник | Как читать | Пример использования |
|---|---|---|
| Route params | `waRequest::param()` | `action`, `theme`, `currency`, `url_type`. |
| GET | `waRequest::get()` | Переключение валюты/локали, фильтры. |
| Current route | `wa()->getRouting()->getRoute()` | Определение settlement/theme/storefront. |
| App config | `$this->getConfig()` | Options, currencies, app-specific methods. |
| Response | `$this->getResponse()` | CSS/JS/title/meta/headers. |
| Events | `wa()->event()` | Вставки plugin HTML в frontend/backend layout. |
| Blocks | `$this->blocks` | `content`, `sidebar`, `toolbar`, `footer`, `links`. |

Важно: layout не должен превращаться в место бизнес-логики. Он готовит shell и общие page-level данные. Запрос к модели допустим, если это layout-level информация: меню, счётчики, состояние sidebar, frontend events.

---

## 6. Паттерны официального Webasyst-кода

### 6.1. `blogFrontendLayout`

`blogFrontendLayout`:

- подключает frontend JS через response;
- назначает `site_theme_url`;
- нормализует `action`;
- вызывает событие `frontend_action_{action}`;
- выбирает theme layout `index.html`.

Паттерн:

```php
class blogFrontendLayout extends waLayout
{
    public function execute()
    {
        $this->getResponse()->addJs("js/jquery.pageless2.js?v=".wa()->getVersion(), true);
        $this->view->assign('action', $action);
        $this->view->assign('frontend_action', wa()->event('frontend_action_'.$action, $params, $fields));
        $this->setThemeTemplate('index.html');
    }
}
```

Смысл: frontend layout не выбирает конкретный post/list template. Он выбирает общий theme shell, а action выбирает `stream.html`, `post.html`, `page.html`.

### 6.2. `shopFrontendLayout`

`shopFrontendLayout`:

- обрабатывает frontend-level переключение currency/locale;
- назначает `action`;
- выбирает `index.html`;
- вызывает события `frontend_head`, `frontend_header`, `frontend_nav`, `frontend_footer`;
- назначает currencies;
- отдаёт route params в Smarty globals.

Это пример layout как storefront shell.

### 6.3. `shopBackendLayout`

`shopBackendLayout`:

- проверяет welcome/tutorial state;
- определяет текущую backend page;
- строит frontend URL;
- получает счётчики заказов;
- вызывает `backend_menu` event;
- назначает данные для backend layout template.

Это пример backend layout как shell приложения.

### 6.4. `blogFrontendAction` + layout

`blogFrontendAction` в constructor устанавливает layout, если это не lazyloading-фрагмент:

```php
if (!$this->is_lazyloading) {
    $this->setLayout(new blogFrontendLayout());
}
$this->setThemeTemplate('stream.html');
```

Смысл: один и тот же action может вернуть полноценную страницу с layout или частичный HTML без layout.

---

## 7. Минимальная реализация

Задача: сделать backend-страницу `/webasyst/myapp/orders/` с общим layout и одним content block.

### 7.1. Route

```php
<?php

return array(
    'orders/?' => 'orders/list',
    ''         => 'backend',
);
```

Файл:

```text
wa-apps/myapp/lib/config/routing.backend.php
```

### 7.2. Layout class

```php
<?php

class myappBackendLayout extends waLayout
{
    public function execute()
    {
        $this->getResponse()->setTitle(_w('My app'));
        $this->getResponse()->addCss('css/myapp.css?v=' . wa()->getVersion());
        $this->getResponse()->addJs('js/myapp.js?v=' . wa()->getVersion());

        $this->view->assign('app_url', wa()->getAppUrl('myapp'));
    }
}
```

Файл:

```text
wa-apps/myapp/lib/layouts/myappBackend.layout.php
```

Шаблон:

```text
wa-apps/myapp/templates/layouts/Backend.html
```

### 7.3. Layout template

```smarty
<div class="myapp-layout">
    <header class="myapp-header">
        <h1>[`My app`]</h1>
    </header>

    <main class="myapp-content">
        {$content|default:''}
    </main>
</div>
```

Файл:

```text
wa-apps/myapp/templates/layouts/Backend.html
```

### 7.4. Action

```php
<?php

class myappOrdersListAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setLayout(new myappBackendLayout());
    }

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

### 7.5. Action template

```smarty
<table class="zebra">
    <thead>
        <tr>
            <th>ID</th>
            <th>[`Date`]</th>
        </tr>
    </thead>
    <tbody>
        {foreach $orders as $order}
            <tr>
                <td>{$order.id|escape}</td>
                <td>{$order.create_datetime|escape}</td>
            </tr>
        {foreachelse}
            <tr>
                <td colspan="2">[`No orders.`]</td>
            </tr>
        {/foreach}
    </tbody>
</table>
```

---

## 8. Расширенная реализация: несколько blocks

Если страница состоит из sidebar, toolbar и content, лучше использовать `waViewController`.

### 8.1. Controller

```php
<?php

class myappOrdersController extends waViewController
{
    public function execute()
    {
        $this->setLayout(new myappBackendLayout());

        $this->executeAction(new myappOrdersSidebarAction(), 'sidebar');
        $this->executeAction(new myappOrdersToolbarAction(), 'toolbar');
        $this->executeAction(new myappOrdersListAction(), 'content');
    }
}
```

Файл:

```text
wa-apps/myapp/lib/actions/orders/myappOrders.controller.php
```

Route:

```php
'orders/?' => 'orders',
```

### 8.2. Layout template

```smarty
<div class="myapp-layout">
    <aside class="myapp-sidebar">
        {$sidebar|default:''}
    </aside>

    <section class="myapp-main">
        <div class="myapp-toolbar">
            {$toolbar|default:''}
        </div>

        <div class="myapp-content">
            {$content|default:''}
        </div>
    </section>
</div>
```

Такой вариант нужен, когда controller действительно orchestrates page composition. Если блок один, достаточно `waViewAction` с layout.

---

## 9. Frontend layout через theme

Frontend action обычно выбирает content template темы:

```php
$this->setThemeTemplate('home.html');
```

Frontend layout выбирает общий shell темы:

```php
$this->setThemeTemplate('index.html');
```

Итог:

```text
action template: home.html / post.html / page.html
→ block content
→ layout template: index.html
```

В `index.html` темы обычно выводится:

```smarty
{$wa->head()}
{$wa->css()}
{$wa->js()}
{$content}
```

Конкретная тема может иметь свою структуру, но принцип остаётся: action отвечает за content, layout — за shell.

---

## 10. Backend layout через app templates

Backend layout обычно использует app template:

```text
wa-apps/{app_id}/templates/layouts/{Name}.html
```

Автоопределение имени:

```php
class myappBackendLayout extends waLayout
```

После удаления app-prefix `myapp` и суффикса `Layout` остаётся:

```text
Backend
```

Шаблон:

```text
templates/layouts/Backend.html
```

Если нужно явно задать template:

```php
$this->setTemplate('BackendCustom.html');
```

Но обычно лучше соблюдать naming convention.

---

## 11. Events в layout

Официальные layout-классы часто вызывают events:

```php
$this->view->assign('frontend_head', wa()->event('frontend_head'));
$this->view->assign('frontend_header', wa()->event('frontend_header'));
$this->view->assign('frontend_footer', wa()->event('frontend_footer'));
```

Для backend:

```php
$this->view->assign('backend_menu', wa()->event('backend_menu'));
```

Правило:

- события layout-level подходят для расширения shell: head/header/footer/menu/sidebar;
- события action-level подходят для расширения конкретной страницы или entity;
- нельзя заменять простую передачу переменных событием без причины.

---

## 12. CSS/JS через response

Layout — правильное место для общих assets страницы:

```php
$this->getResponse()->addCss('css/backend.css?v=' . wa()->getVersion());
$this->getResponse()->addJs('js/backend.js?v=' . wa()->getVersion());
```

Action — место для assets, нужных только конкретному action.

Нельзя подключать backend assets через hardcoded `<script src="/webasyst/...">`, потому что backend URL и root URL могут отличаться.

---

## 13. Типовые ошибки

### Ошибка 1. Делать layout пассивным include

Неправильно:

```smarty
{include file="layout.html"}
```

Правильно: использовать `waLayout`, `setLayout()` и blocks.

### Ошибка 2. Писать бизнес-логику в layout

Неправильно:

```php
class myappBackendLayout extends waLayout
{
    public function execute()
    {
        // создание заказа, пересчёт остатков, сохранение формы
    }
}
```

Правильно: layout готовит shell, меню, events, counters, assets. Изменение данных должно быть в controller/action/service/model.

### Ошибка 3. Использовать layout для partial response

Если htmx/AJAX ждёт только фрагмент, layout может быть лишним.

Правильно:

```php
if (!waRequest::isXMLHttpRequest()) {
    $this->setLayout(new myappBackendLayout());
}
```

### Ошибка 4. Не учитывать block name

Controller кладёт блок:

```php
$this->executeAction(new myappToolbarAction(), 'toolbar');
```

А template ждёт:

```smarty
{$topbar}
```

Результат: блок не выводится.

### Ошибка 5. Затирать block через `assign()` вместо дописывания

`setBlock()` дописывает, если block уже существует.  
`assign()` заменяет значение.

Использовать осознанно.

### Ошибка 6. Хардкодить theme template path

Неправильно:

```php
$this->setTemplate('/wa-apps/shop/themes/default/index.html');
```

Правильно:

```php
$this->setThemeTemplate('index.html');
```

### Ошибка 7. Подключать CSS/JS в шаблоне без необходимости

Если asset общий для shell, подключать через response в layout:

```php
$this->getResponse()->addCss(...);
$this->getResponse()->addJs(...);
```

### Ошибка 8. Создавать layout для одного маленького POST-controller

Для delete/save/redirect command controller layout не нужен.

---

## 14. Чеклист разработчика

### Layout class

- [ ] Layout class лежит в `wa-apps/{app_id}/lib/layouts/`.
- [ ] Имя класса соответствует `{app_id}{Name}Layout`.
- [ ] Файл называется `{app_id}{Name}.layout.php`.
- [ ] Layout наследуется от `waLayout`.
- [ ] В `execute()` нет бизнес-логики изменения данных.
- [ ] Общие CSS/JS подключаются через `waResponse`.
- [ ] Events используются только там, где layout должен быть расширяемым.

### Layout template

- [ ] Шаблон лежит в `templates/layouts/{Name}.html` или theme `index.html`.
- [ ] В шаблоне выводятся правильные block names.
- [ ] Для optional blocks используется `|default:''`.
- [ ] Нет PHP-кода в Smarty.
- [ ] Вывод пользовательских данных экранируется.

### Controller/action

- [ ] Если нужен один content block — используется `waViewAction` с `setLayout()`.
- [ ] Если нужны несколько blocks — используется `waViewController`.
- [ ] Для partial/AJAX/htmx layout отключается осознанно.
- [ ] `executeAction()` передаёт block names, которые есть в template.
- [ ] POST command controller не использует layout без причины.

### Frontend/theme

- [ ] Layout выбирает `index.html` через `setThemeTemplate()`.
- [ ] Action выбирает content template темы через `setThemeTemplate()`.
- [ ] URL/assets не хардкодятся от `/webasyst/`.
- [ ] `wa()->getRouteUrl()` и `$wa` helpers используются для URL.

---

## 15. Чеклист ИИ-агента

Перед ответом по layout/blocks ИИ-агент обязан:

1. Определить env: backend или frontend.
2. Открыть route, который ведёт в action/controller.
3. Определить найденный класс: `Controller`, `Action` или `Actions`.
4. Открыть action/controller class.
5. Проверить, есть ли `setLayout()`.
6. Открыть layout class в `lib/layouts/`.
7. Открыть layout template в `templates/layouts/` или theme `index.html`.
8. Сопоставить block names: `content`, `sidebar`, `toolbar`, `footer`, etc.
9. Проверить, не нужен ли partial response без layout.
10. Проверить CSS/JS подключения через response.
11. Проверить events, если layout расширяется plugin-ами.
12. Проверить escaping в Smarty.

ИИ-агенту запрещено:

- создавать layout без проверки текущего app pattern;
- использовать PHP include вместо `waLayout`;
- хардкодить `/webasyst/` или theme path;
- писать бизнес-логику сохранения данных в layout;
- путать app layout template и theme template;
- предлагать block name, которого нет в template;
- подключать assets напрямую в HTML, если проект использует response assets.

---

## 16. Мини-сводка

`waLayout` — это controller-слой сборки страницы.

Правильная модель:

```text
Action отвечает за данные и content template.
ViewController отвечает за orchestration blocks.
Layout отвечает за shell, общие переменные, assets, events и итоговый template.
Theme отвечает за frontend HTML-шаблоны.
Response отвечает за headers, title, meta, CSS/JS.
```

Главное правило: layout не заменяет routing, action или model. Он получает подготовленные blocks и собирает страницу в единый shell.
