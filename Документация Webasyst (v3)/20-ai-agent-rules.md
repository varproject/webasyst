# 20. Правила для ИИ-агентов при разработке на Webasyst

**Статус:** опубликован v3  
**Назначение:** зафиксировать обязательный рабочий протокол для ИИ-агента, который отвечает на задачи по Webasyst: приложениям, плагинам, routing, controllers/actions, models, templates, UI, безопасности и интеграциям.  
**Главный принцип:** ИИ-агент не должен угадывать Webasyst API и архитектуру. Сначала проверяются текущие файлы проекта, системные классы и официальные patterns, затем даётся решение.

---

## 1. Назначение главы

Эта глава нужна не разработчику как справочник API, а ИИ-агенту как обязательный порядок действий перед ответом.

Проблема, которую решает глава: ИИ часто пытается применить абстрактный PHP/Laravel/Symfony/MVC-подход там, где Webasyst имеет собственный механизм:

- `waSystem` lifecycle;
- global routing и app routing;
- backend `module/action/plugin` dispatch;
- naming-based autoload;
- `waViewAction`, `waViewController`, `waViewActions`;
- `waLayout` blocks;
- Smarty/theme templates;
- `waModel` и placeholders;
- plugin lifecycle;
- `waRightConfig`;
- Webasyst 2 UI-KIT.

Поэтому ИИ-агент обязан работать как проверяющий инженер, а не как генератор кода по памяти.

---

## 2. Главный workflow ИИ-агента

Перед любым техническим ответом по Webasyst агент обязан пройти базовый workflow.

```text
1. Определить тип задачи:
   app / plugin / theme / backend / frontend / model / UI / security / CLI / cron.

2. Найти текущий app/plugin/theme в проекте.

3. Открыть главный config:
   app:    wa-apps/{app_id}/lib/config/app.php
   plugin: wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php
   theme:  wa-apps/{app_id}/themes/{theme_id}/theme.xml

4. Проверить routing:
   frontend: wa-apps/{app_id}/lib/config/routing.php
   backend:  wa-apps/{app_id}/lib/config/routing.backend.php
   plugin:   wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php

5. Определить итоговые dispatch params:
   module / action / plugin / route placeholders.

6. Открыть текущий controller/action/actions/layout.

7. Открыть model/service/helper, если задача касается данных или бизнес-логики.

8. Открыть template/theme template, если задача касается HTML/UI.

9. Найти похожую реализацию в этом же проекте.

10. Найти официальный Webasyst pattern:
    site / blog / shop / crm / plugin.

11. Проверить naming:
    class / file / template / table / route.

12. Проверить security:
    rights / CSRF / input typing / escaping / SQL placeholders / upload path.

13. Только после этого писать ответ или код.
```

Если хотя бы один критичный файл недоступен, агент обязан прямо сказать, что ответ даётся без проверки этого файла.

---

## 3. Приоритет источников

ИИ-агент использует источники в строгом порядке.

| Приоритет | Источник | Как использовать |
|---|---|---|
| 1 | Текущий GitHub-репозиторий проекта | Источник истины для конкретной задачи. |
| 2 | Системные классы Webasyst | Источник истины для lifecycle, routing, dispatch, models, security. |
| 3 | Официальные приложения `site`, `blog`, `shop`, `crm` | Источник patterns. |
| 4 | Официальные plugins | Источник plugin lifecycle, settings, handlers, backend actions. |
| 5 | Загруженная старая документация Webasyst | Справочный источник, сверяется с текущим ядром. |
| 6 | Webasyst 2 UI-KIT | Источник UI 2.0 markup/classes/widgets. |
| 7 | Общие знания PHP/JS/CSS | Использовать только после проверки Webasyst-подхода. |

### Важное правило

Если старая документация и текущее ядро расходятся, приоритет имеет текущее ядро.

Пример: порядок поиска backend classes должен фиксироваться по текущему `waFrontController::getController()`:

```text
Controller → Action → Actions
```

а не по устаревшему описанию.

---

## 4. Классификация задачи перед работой

ИИ-агент должен сначала классифицировать задачу.

| Тип задачи | Что открыть первым |
|---|---|
| Backend page | `app.php`, `routing.backend.php`, `lib/actions/{module}/`, `templates/actions/{module}/` |
| Frontend page | `app.php`, global settlement, `routing.php`, frontend action, theme template |
| Plugin | `plugin.php`, main plugin class, settings/routing/actions/templates |
| Model/DB | model class, `db.php`, install/update files, похожие model methods |
| Settings | `app.php`, `config.php`, `wa-config/apps/{app}/config.php`, `waAppSettingsModel`, plugin `settings.php` |
| Rights | `app.php`, `{app}RightConfig.class.php`, `checkRights()`, controller checks |
| UI 2.0 | `app.php` `ui`, current template, UI-KIT pattern, legacy fallback |
| Theme | `theme.xml`, theme template, `waTheme`, action `setThemeTemplate()` |
| AJAX/JSON | route, controller type, `waJsonController`/`waJsonActions` or HTML partial |
| CLI/cron | `cron.php`, CLI class, `waDispatch::dispatchCli()` pattern |
| Security | request source, rights, CSRF, escaping, SQL, upload/redirect paths |

Если тип задачи нельзя определить по запросу пользователя, агент задаёт уточняющие вопросы. Если можно сделать безопасное предположение, оно должно быть явно указано.

---

## 5. Проверка `app.php`

Для app-задач первым делом открывается:

```text
wa-apps/{app_id}/lib/config/app.php
```

Проверить:

| Параметр | Зачем |
|---|---|
| `frontend` | Можно ли делать frontend routes/actions. |
| `plugins` | Поддерживает ли app plugins. |
| `themes` | Есть ли theme rendering. |
| `pages` | Есть ли системные page routes. |
| `rights` | Нужно ли искать `{app}RightConfig`. |
| `csrf` | Будет ли системная CSRF-проверка POST. |
| `auth` | Связан ли app с auth flow. |
| `my_account` | Есть ли personal account routes/nav. |
| `routing_params` | Какие параметры settlement попадают в `waRequest::param()`. |
| `ui` | Какая версия backend UI используется. |

### Типовая ошибка ИИ

Нельзя предлагать frontend route, если `frontend` не проверен. Нельзя рисовать UI 2.0, если app поддерживает только UI 1.3 или имеет legacy fallback, который нужно сохранить.

---

## 6. Проверка routing

### 6.1. Frontend routing

Открыть:

```text
wa-apps/{app_id}/lib/config/routing.php
```

Проверить:

- какой route pattern уже есть;
- какой `module/action` получится;
- какие placeholders попадут в `waRequest::param()`;
- есть ли `secure => true`;
- есть ли wildcard route;
- есть ли app-specific routing logic в `{app}Config::getRouting()`.

### 6.2. Backend routing

Открыть:

```text
wa-apps/{app_id}/lib/config/routing.backend.php
```

Если файла нет, учитывать старый GET-подход:

```text
/{backend_url}/{app_id}/?module={module}&action={action}
```

Если файл есть, помнить: `routing.backend.php` не запускает class напрямую. Он только выставляет `module/action` в route params.

### 6.3. Plugin routing

Открыть:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php
```

Проверить, как plugin подключается через event `routing` и как app config собирает plugin routes.

---

## 7. Проверка dispatch и naming

После routing агент обязан определить, какой class будет искать Webasyst.

### 7.1. App class lookup

Для:

```text
app_id = myapp
module = orders
action = view
```

проверяются классы:

```text
myappOrdersViewController
myappOrdersViewAction
myappOrdersActions::viewAction()
```

Файлы:

```text
wa-apps/myapp/lib/actions/orders/myappOrdersView.controller.php
wa-apps/myapp/lib/actions/orders/myappOrdersView.action.php
wa-apps/myapp/lib/actions/orders/myappOrders.actions.php
```

### 7.2. Plugin class lookup

Для:

```text
app_id = shop
plugin = redirect
module = settings
action = default
```

проверяются классы вида:

```text
shopRedirectPluginSettingsAction
shopRedirectPluginSettingsController
shopRedirectPluginSettingsActions
```

Пути:

```text
wa-apps/shop/plugins/redirect/lib/actions/settings/...
wa-apps/shop/plugins/redirect/templates/actions/settings/...
```

### 7.3. Запрет

Нельзя придумывать имя класса без расчёта `module/action/plugin` и без проверки существующей директории `lib/actions/{module}/`.

---

## 8. Проверка templates

Для `waViewAction` агент обязан проверить auto-template path.

Класс:

```php
class myappOrdersViewAction extends waViewAction
```

Шаблон:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

Для `waViewActions::viewAction()`:

```text
wa-apps/myapp/templates/actions/orders/OrdersView.html
```

Для layout:

```text
wa-apps/myapp/templates/layouts/Backend.html
```

Для frontend theme:

```php
$this->setThemeTemplate('product.html');
```

шаблон ищется в active theme.

### Что нельзя

- не вставлять PHP в Smarty;
- не писать business logic в шаблоне;
- не собирать backend URL руками;
- не читать route placeholders через `waRequest::get()`;
- не выводить пользовательские данные без escaping.

---

## 9. Проверка models и DB

Если задача касается данных, агент обязан открыть:

```text
wa-apps/{app_id}/lib/models/*
wa-apps/{app_id}/lib/config/db.php
wa-apps/{app_id}/lib/updates/*.php
```

Проверить:

- есть ли уже model для таблицы;
- какое значение `$table`;
- какой primary key `$id`;
- есть ли похожий метод;
- какие fields есть в `db.php`;
- нужна ли миграция;
- используются ли placeholders;
- не нарушается ли metadata cache.

### Правило

SQL должен быть в model/service layer, не в template.

Action может вызывать model, но не должен превращаться в большой SQL-класс.

---

## 10. Проверка settings/config

ИИ-агент должен различать:

| Механизм | Для чего |
|---|---|
| `lib/config/app.php` | Capabilities приложения. |
| `lib/config/config.php` | Дефолтные технические опции app. |
| `wa-config/apps/{app}/config.php` | Пользовательское переопределение app options. |
| `waAppSettingsModel` | Runtime settings в БД. |
| `plugins/{plugin}/lib/config/settings.php` | Декларация plugin settings UI. |
| `wa-config/routing.php` | Settlement/routing, не settings. |

### Запрет

Нельзя хранить routing/state/runtime-структуры в `app.php`. Нельзя использовать plugin `settings.php` как runtime storage. Нельзя писать пользовательские настройки в `wa-apps`.

---

## 11. Проверка rights

Для backend/save/delete операций агент обязан проверить права.

Открыть:

```text
wa-apps/{app_id}/lib/config/{app_id}RightConfig.class.php
wa-apps/{app_id}/lib/config/{app_id}Config.class.php
```

Проверить:

- `rights => true` в `app.php`;
- какие rights keys объявлены через `addItem()`;
- есть ли `checkRights($module, $action)`;
- есть ли object-level rights в model/service;
- есть ли plugin rights `plugin.{plugin_id}`.

### Обязательное правило

Скрыть кнопку в UI недостаточно. Save/delete/controller/model layer тоже должен проверять права.

---

## 12. Проверка CSRF

Если запрос изменяет данные, агент обязан проверить:

1. `csrf => true` в `app.php`;
2. HTTP method;
3. наличие `{$wa->csrf()}` в форме;
4. формат AJAX/htmx запроса;
5. не обходит ли custom controller системную проверку.

### Шаблонное правило

```smarty
<form method="post" action="{$wa_app_url}orders/save/">
    {$wa->csrf()}
    ...
</form>
```

Для htmx POST это правило не отменяется.

---

## 13. Проверка UI 2.0

Для backend UI агент обязан открыть `app.php` и проверить:

```php
'ui' => '2.0'
'ui' => '1.3,2.0'
'ui' => '2.0,1.3'
```

Если app поддерживает UI 2.0:

- использовать native Webasyst 2 classes;
- не подменять UI Bootstrap/AdminLTE/Tailwind как основной backend UI;
- проверить legacy template fallback;
- проверить `templates/actions-legacy/`, если app поддерживает оба режима;
- не смешивать UI 1.3 и UI 2.0 в одном template без осознанной совместимости.

### Базовая структура UI 2.0 backend page

```smarty
<div class="article">
    <div class="article-body">
        <div class="box">
            ...
        </div>
    </div>
</div>
```

---

## 14. Проверка plugin-задач

Для plugin-задач агент обязан открыть:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php
wa-apps/{app_id}/plugins/{plugin_id}/lib/{app}{Plugin}.plugin.php
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php
wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/
wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/
```

Проверить:

- plugin enabled или нет;
- `handlers`;
- settings config;
- main plugin class;
- install/update/db files;
- backend actions;
- frontend routes;
- helper class;
- rights.

### Запрет

Нельзя вызывать plugin class напрямую из Smarty. Для template API использовать `waPluginViewHelper`.

---

## 15. Проверка theme-задач

Для theme-задач агент обязан открыть:

```text
wa-apps/{app_id}/themes/{theme_id}/theme.xml
```

Проверить:

- `parent_theme_id`;
- `files`;
- `settings`;
- `locales`;
- `thumbs`;
- какой action вызывает `setThemeTemplate()`;
- какие `$theme_settings` используются;
- не меняется ли original theme вместо custom copy.

### Запрет

Нельзя писать runtime/user data в theme files. Нельзя строить путь к template из request без whitelist/validation.

---

## 16. Проверка AJAX/JSON/partials

ИИ-агент должен выбрать правильный механизм.

| Нужно | Использовать |
|---|---|
| JSON command/API | `waJsonController` или `waJsonActions` |
| HTML-фрагмент | `waViewAction` partial/template |
| Полная страница | `waViewAction` + layout/theme |
| Долгий процесс | `waLongActionController` |
| CLI command | `waCliController` |

### Запрет

Нельзя отдавать HTML через JSON controller без причины. Нельзя использовать long action для короткого save. Нельзя делать тяжёлую операцию в обычном AJAX request без chunking/progress.

---

## 17. Безопасность перед финальным ответом

Перед финальным ответом агент обязан проверить:

```text
[ ] Все данные из request типизированы.
[ ] Route placeholders читаются через waRequest::param().
[ ] GET читается через waRequest::get().
[ ] POST читается через waRequest::post().
[ ] Для изменяющих действий есть rights check.
[ ] Для POST есть CSRF.
[ ] SQL использует model methods или placeholders.
[ ] Пользовательский вывод escaped.
[ ] Upload идёт в wa-data, не в wa-apps.
[ ] Redirect безопасный и локальный, если URL пришёл от пользователя.
[ ] URL строятся через wa()->getRouteUrl(), wa()->getAppUrl(), {$wa_app_url}.
[ ] Не используются несуществующие методы Webasyst.
[ ] Не смешаны слои action/model/template.
```

---

## 18. Как давать ответ по коду

Если задача требует код, агент должен отдавать цельные блоки.

### Хорошо

```text
## Файл: wa-apps/myapp/lib/actions/orders/myappOrdersSave.controller.php
```

```php
<?php

class myappOrdersSaveController extends waController
{
    public function execute()
    {
        ...
    }
}
```

### Плохо

```text
Замените строку 15, потом добавьте кусок выше, потом в другом файле поменяйте пару строк.
```

Если изменение точечное — можно дать метод целиком. Если изменение архитектурное — дать все файлы по ролям.

---

## 19. Что ИИ-агенту запрещено

ИИ-агенту запрещено:

1. Придумывать Webasyst API.
2. Утверждать, что код совместим с проектом, если файлы проекта не были открыты.
3. Подменять Webasyst routing Laravel/Symfony-style routing.
4. Хардкодить `/webasyst/`.
5. Хардкодить domain/settlement/app/plugin/table names без проверки.
6. Читать route placeholders через `waRequest::get()`.
7. Писать SQL в Smarty.
8. Писать PHP в Smarty.
9. Писать пользовательские данные в `wa-apps`.
10. Создавать свой CSRF-механизм.
11. Игнорировать `rights` и object-level access.
12. Делать backend UI на чужом framework, если нужен native Webasyst 2 UI.
13. Давать patch/diff, если пользователь просит цельный класс/метод.
14. Делать сложную архитектуру без необходимости.
15. Создавать абстрактные сервисы/factories/interfaces без подтверждённой нужды.
16. Игнорировать существующие helper/model/component в проекте.
17. Смешивать frontend theme templates и backend app templates.
18. Обещать, что код проверен, если проверялись только общие знания.

---

## 20. Когда задавать уточняющие вопросы

Агент задаёт уточняющие вопросы, если без них есть риск ошибиться в:

- app или plugin;
- backend или frontend контексте;
- UI 1.3/2.0;
- route/settlement;
- модели/таблице;
- правах доступа;
- формате ответа: HTML, JSON, redirect, htmx partial;
- необходимости legacy support;
- существующем проектном стиле.

Но если задача может быть безопасно выполнена с явно указанным предположением, агент не должен тормозить работу лишними вопросами.

---

## 21. Минимальный skeleton проверки перед ответом

Перед любым финальным ответом по Webasyst агент может использовать короткую внутреннюю проверку:

```text
Тип задачи: backend / frontend / plugin / model / UI / security
App/plugin/theme: определён
app.php/plugin.php/theme.xml: открыт
routing: открыт
module/action/plugin: рассчитаны
class/file/template: проверены
model/db: проверены, если нужны
rights: проверены
CSRF: проверен, если POST
UI version: проверена
официальный pattern: выбран
риск/альтернатива: указаны
```

---

## 22. Примеры правильного поведения ИИ-агента

### Пример 1. Backend route

Пользователь просит:

```text
Сделай страницу /orders/123/edit/ в backend.
```

Агент должен:

1. открыть `app.php`;
2. открыть `routing.backend.php`;
3. определить route `orders/<id:\d+>/edit/?`;
4. определить class `myappOrdersEditAction` или `Controller`;
5. проверить `templates/actions/orders/OrdersEdit.html`;
6. проверить права;
7. читать `id` через `waRequest::param('id', 0, waRequest::TYPE_INT)`;
8. построить URL через `wa()->getAppUrl()` или `{$wa_app_url}`.

### Пример 2. Plugin settings

Пользователь просит:

```text
Добавь настройку плагина.
```

Агент должен:

1. открыть `plugin.php`;
2. открыть main plugin class;
3. открыть `settings.php`;
4. проверить `getSettings()`/`saveSettings()` overrides;
5. добавить control config;
6. не писать ручной SQL в `wa_app_settings`.

### Пример 3. Frontend theme output

Пользователь просит:

```text
Выведи блок на странице товара.
```

Агент должен:

1. открыть frontend route;
2. открыть action;
3. проверить `setThemeTemplate('product.html')`;
4. открыть active theme template или default theme template;
5. проверить `$wa->shop` helper;
6. не писать SQL в theme template;
7. не менять app backend template вместо theme template.

---

## 23. Мини-сводка

Правильная работа ИИ-агента с Webasyst строится не на догадках, а на цепочке:

```text
задача
→ app/plugin/theme identification
→ config
→ routing
→ dispatch params
→ class lookup
→ model/service/template
→ official pattern
→ rights/security/UI
→ код или объяснение
```

Если агент не прошёл эту цепочку, его ответ считается предварительным и не должен подаваться как проверенное решение.
