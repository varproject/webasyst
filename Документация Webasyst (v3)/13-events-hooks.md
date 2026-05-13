# 13. Events и hooks в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить события Webasyst как расширяемый механизм приложения и plugin-экосистемы: где событие объявляется, как plugin подписывается на него, что получает обработчик, что должен вернуть и где результат используется.

---

## 1. Назначение механизма

Events/hooks в Webasyst нужны для расширения поведения приложения без изменения его исходного кода.

Типичный сценарий:

```text
приложение объявляет событие
→ plugin подписывается на событие в lib/config/plugin.php
→ Webasyst вызывает метод plugin-класса
→ plugin возвращает HTML, параметры запроса, ошибки, условия SQL или изменяет переданные данные
→ приложение использует результат события
```

Событие не является route, controller или action. Оно не запускает страницу само по себе. Событие — это точка расширения внутри уже выполняющегося request-flow.

Примеры:

| Где объявляется | Что расширяется |
|---|---|
| `shopFrontendLayout` | HTML-блоки темы: `frontend_head`, `frontend_header`, `frontend_nav`, `frontend_footer`. |
| `blogFrontendLayout` | Блоки страницы блога: `nav_before`, `footer`, `head`, `sidebar`. |
| `blogPostModel` | Поиск, подготовка и сохранение постов. |
| `shopRedirectPlugin` | Реакция plugin-а на `frontend_error` и `frontend_search`. |
| `shopConfig::getRouting()` | Расширение frontend routing через plugin routes. |

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/waSystem.class.php` | Основной фасад `wa()`, через который вызывается `event()`. |
| `wa-system/event/waEvent.class.php` | Системный класс обработки событий. |
| `wa-system/plugin/waPlugin.class.php` | Загружает plugin, settings, routing, install/update lifecycle. |
| `wa-system/config/waAppConfig.class.php` | Загружает app config, plugin config, routing, cron. |
| `wa-system/controller/waFrontController.class.php` | Загружает plugin при dispatch через `plugin` и проверяет plugin rights. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | Должен разрешать `plugins => true`, если приложение поддерживает plugins. |
| `wa-apps/{app_id}/lib/config/routing.php` | Может быть расширен plugin routes через событие `routing`. |
| `wa-apps/{app_id}/lib/actions/...` | Может объявлять события перед/после выполнения логики. |
| `wa-apps/{app_id}/lib/layouts/...` | Часто объявляет HTML-события для theme/backend layout. |
| `wa-apps/{app_id}/lib/models/...` | Часто объявляет data events: search, save, prepare, validate. |

### 2.3. Файлы plugin-а

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Основной config plugin-а, включая `handlers`. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/{app}{Plugin}.plugin.php` | Основной plugin-класс с методами-обработчиками. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php` | Frontend routes plugin-а, если plugin расширяет routing. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php` | Settings plugin-а, если обработчики настраиваются пользователем. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/db.php` | Таблицы plugin-а, если обработчик хранит данные. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/install.php` | Инициализация plugin-а. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/updates/*.php` | Обновления plugin-а. |

---

## 3. Системная цепочка выполнения

### 3.1. Объявление события в app-коде

Приложение вызывает событие:

```php
$result = wa()->event('frontend_head');
```

или с app-id:

```php
$result = wa('shop')->event('frontend_head');
```

или с массивом:

```php
$result = wa()->event(array('shop', 'routing'), $route);
```

Практическая интерпретация:

| Вызов | Смысл |
|---|---|
| `wa()->event('event_name')` | Событие текущего активного приложения. |
| `wa('shop')->event('event_name')` | Событие приложения `shop`. |
| `wa()->event(array('shop', 'routing'), $params)` | Явное событие приложения `shop`, даже если активный app может быть другим. |

### 3.2. Поиск handlers

Plugin подписывается на событие в:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php
```

Пример:

```php
<?php

return array(
    'name' => 'My plugin',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'frontend_head' => 'frontendHead',
    ),
);
```

Системный смысл:

```text
event frontend_head
→ вызвать public method frontendHead() основного plugin-класса
```

### 3.3. Загрузка plugin-а

Когда событие запускается, Webasyst должен:

1. Найти включённые plugins приложения.
2. Прочитать `plugin.php`.
3. Найти `handlers`.
4. Загрузить основной plugin-класс.
5. Вызвать соответствующий метод.
6. Собрать результаты всех обработчиков в массив.

Основной plugin-класс:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/{app_id}{PluginId}.plugin.php
```

Пример для `shop/plugins/redirect`:

```text
wa-apps/shop/plugins/redirect/lib/shopRedirect.plugin.php
```

Класс:

```php
class shopRedirectPlugin extends shopPlugin
{
}
```

---

## 4. Ключевые классы и методы

### 4.1. `waSystem::event()`

Основная точка вызова события:

```php
wa()->event('event_name', $params);
wa('shop')->event('event_name', $params);
```

Для документации v3 важно не скрывать контекст приложения. Если событие относится к конкретному app, лучше явно показывать app-id:

```php
wa('shop')->event('frontend_head');
```

или:

```php
wa()->event(array('shop', 'routing'), $route);
```

### 4.2. `waEvent`

`waEvent` — системный класс обработки событий. Старшая документация описывает его как класс, который используется фреймворком для обработки программных событий, а также позволяет вручную создать событие и запустить обработчики через `run()`.

Пример из документации:

```php
$event_class = new waEvent($event_app_id, $name, $options);
$result = $event_class->run($params);
```

Также `waEvent::addCustomHandler()` позволяет подключить динамический обработчик с ключами `event`, `event_app_id`, `object`, `method`.

### 4.3. `waPlugin`

`waPlugin` отвечает не только за settings. Он также участвует в plugin lifecycle:

- `checkUpdates()`;
- `install()`;
- `uninstall()`;
- `routing()`;
- `cron()`;
- `getSettings()`;
- `saveSettings()`;
- `getControls()`;
- `addJs()`;
- `addCss()`;
- `rightsConfig()`.

Для events важны две вещи:

1. `plugin.php` описывает `handlers`.
2. Основной plugin class содержит методы-обработчики.

### 4.4. `plugin.php`

Минимальный event plugin config:

```php
<?php

return array(
    'name' => 'My event plugin',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'frontend_head' => 'frontendHead',
    ),
);
```

---

## 5. Параметры events и формат результата

### 5.1. Событие без параметров

```php
$this->view->assign('frontend_head', wa()->event('frontend_head'));
```

Обработчик:

```php
class shopMypluginPlugin extends shopPlugin
{
    public function frontendHead()
    {
        return '<meta name="my-plugin" content="1">';
    }
}
```

Результат:

```php
array(
    'myplugin-plugin' => '<meta name="my-plugin" content="1">',
)
```

В Smarty результат обычно выводится так:

```smarty
{foreach $frontend_head as $_html}
    {$_html}
{/foreach}
```

Если в шаблоне включён autoescape, HTML нужно выводить с учётом правил конкретной темы/шаблона. В существующих официальных шаблонах обычно используется контролируемый HTML-output из доверенных plugin handlers.

### 5.2. Событие с параметрами

```php
$params = waRequest::param();
$result = wa()->event('frontend_action_'.$action, $params, $fields);
```

Обработчик:

```php
public function frontendActionPost($params)
{
    return array(
        'footer' => '<div>Extra footer</div>',
    );
}
```

### 5.3. Событие, возвращающее массив блоков

`blogFrontendLayout` использует поля:

```php
$fields = array('nav_before', 'footer', 'head', 'sidebar');
$this->view->assign('frontend_action', wa()->event('frontend_action_'.$action, $params, $fields));
```

Практический результат для plugin-а:

```php
public function frontendActionPost($params)
{
    return array(
        'sidebar' => '<aside>Plugin sidebar block</aside>',
        'footer'  => '<div>Plugin footer block</div>',
    );
}
```

### 5.4. Событие, изменяющее данные по ссылке

`blogPostModel::prepareView()` вызывает:

```php
wa()->event('prepare_posts_'.wa()->getEnv(), $items);
```

Здесь `$items` передаётся как изменяемая структура. Plugin может дописать данные внутрь элементов:

```php
public function preparePostsFrontend(&$items)
{
    foreach ($items as &$item) {
        $item['plugins']['after']['myplugin'] = '<div>Extra block</div>';
    }
    unset($item);
}
```

Такой pattern требует осторожности:

- не удалять чужие ключи;
- не менять структуру, которую ожидает core/app;
- не делать тяжёлые запросы внутри цикла без кеширования;
- не выводить HTML без escaping, если данные не доверены.

### 5.5. Событие, возвращающее ошибки

`blogPostModel::updateItem()` вызывает presave/prepublish events и ожидает массив ошибок:

```php
$errors = wa()->event(array_shift($events), $data);
```

Обработчик может вернуть:

```php
public function postPresave($data)
{
    if (empty($data['title'])) {
        return array(
            'title' => 'Title is required',
        );
    }

    return null;
}
```

---

## 6. Паттерны официального Webasyst-кода

### 6.1. `shopFrontendLayout`: HTML hooks темы

`shopFrontendLayout` объявляет несколько frontend events:

```php
$this->view->assign('frontend_head', wa()->event('frontend_head'));
$this->view->assign('frontend_header', wa()->event('frontend_header'));
$this->view->assign('frontend_nav', wa()->event('frontend_nav'));
$this->view->assign('frontend_footer', wa()->event('frontend_footer'));
```

Это классический pattern: layout собирает HTML-блоки от plugin-ов и передаёт их в theme template.

### 6.2. `blogFrontendLayout`: action-specific hooks

`blogFrontendLayout` строит имя события от текущего action:

```php
$action = (string)waRequest::param('action');
$this->view->assign('frontend_action', wa()->event('frontend_action_'.$action, $params, $fields));
```

Практический смысл:

```text
action = post
→ event frontend_action_post

action = page
→ event frontend_action_page

action = error
→ event frontend_action_error
```

Такой pattern подходит, когда plugin должен встраиваться не во все страницы, а в конкретный тип страницы.

### 6.3. `blogPostModel`: data hooks

`blogPostModel` показывает несколько типов events:

| Event | Смысл |
|---|---|
| `search_posts_frontend/backend` | Plugin может расширить query-параметры поиска. |
| `prepare_posts_frontend/backend` | Plugin может дописать данные в уже найденные post items. |
| `post_presave`, `post_prepublish`, `post_preshedule` | Plugin может проверить данные перед сохранением. |
| `post_save`, `post_publish`, `post_shedule` | Plugin может выполнить действия после сохранения. |

Это пример правильного model-level hook: событие объявлено в модели, потому что речь идёт о данных, а не о шаблоне.

### 6.4. `shopRedirectPlugin`: plugin handlers в `plugin.php`

`shop/plugins/redirect/lib/config/plugin.php`:

```php
'handlers' => array(
    'frontend_error' => 'frontendError',
    'frontend_search' => 'frontendSearch'
),
```

Основной класс:

```php
class shopRedirectPlugin extends shopPlugin
{
    public function frontendError($params)
    {
    }

    public function frontendSearch()
    {
    }
}
```

Это минимальный официальный pattern:

```text
plugin.php handlers
→ method основного plugin class
→ обработка события
```

### 6.5. Routing как event

В `shopConfig::getRouting()` приложение расширяет app routes через событие `routing`.

Pattern:

```php
$result = wa()->event(array($this->application, 'routing'), $route);
```

Plugin может вернуть routes из `lib/config/routing.php`. `waPlugin::routing()` по умолчанию включает файл:

```text
wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php
```

---

## 7. Минимальная реализация

### 7.1. Plugin с HTML hook

Задача: добавить HTML в `frontend_head`.

#### Файл: `wa-apps/shop/plugins/example/lib/config/plugin.php`

```php
<?php

return array(
    'name' => 'Example',
    'description' => 'Adds frontend head markup.',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'frontend_head' => 'frontendHead',
    ),
);
```

#### Файл: `wa-apps/shop/plugins/example/lib/shopExample.plugin.php`

```php
<?php

class shopExamplePlugin extends shopPlugin
{
    public function frontendHead()
    {
        return '<meta name="shop-example-plugin" content="1">';
    }
}
```

#### Использование в layout/theme

Если приложение уже делает:

```php
$this->view->assign('frontend_head', wa()->event('frontend_head'));
```

то в шаблоне можно вывести:

```smarty
{foreach $frontend_head as $_html}
    {$_html}
{/foreach}
```

В конкретном проекте нужно проверить, используется ли `nofilter`/autoescape в шаблоне.

---

### 7.2. Plugin с обработкой 404

#### Файл: `wa-apps/shop/plugins/example/lib/config/plugin.php`

```php
<?php

return array(
    'name' => 'Example',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'frontend_error' => 'frontendError',
    ),
);
```

#### Файл: `wa-apps/shop/plugins/example/lib/shopExample.plugin.php`

```php
<?php

class shopExamplePlugin extends shopPlugin
{
    public function frontendError($e)
    {
        if ($e instanceof waException && $e->getCode() == 404) {
            wa()->getResponse()->addHeader('X-Example-Plugin', '404');
        }
    }
}
```

---

### 7.3. Plugin с изменением данных по ссылке

```php
<?php

class blogExamplePlugin extends blogPlugin
{
    public function preparePostsFrontend(&$items)
    {
        foreach ($items as &$item) {
            $item['plugins']['after']['example'] = '<div class="hint">Example plugin block</div>';
        }
        unset($item);
    }
}
```

`plugin.php`:

```php
<?php

return array(
    'name' => 'Example',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'prepare_posts_frontend' => 'preparePostsFrontend',
    ),
);
```

---

## 8. Расширенная реализация

### 8.1. Один метод на несколько событий

Если две точки расширения делают однотипную работу:

```php
<?php

return array(
    'name' => 'Example',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'frontend_head' => 'renderFrontendBlock',
        'frontend_footer' => 'renderFrontendBlock',
    ),
);
```

```php
class shopExamplePlugin extends shopPlugin
{
    public function renderFrontendBlock($params = null)
    {
        return '<div class="example-plugin-block"></div>';
    }
}
```

Плюс: меньше дублирования.  
Минус: метод должен понимать, из какого event он вызван, если логика отличается. Если это важно, лучше сделать два явных метода.

### 8.2. Несколько обработчиков в одном plugin-е

```php
<?php

return array(
    'name' => 'Example',
    'version' => '1.0.0',
    'vendor' => 'myvendor',
    'handlers' => array(
        'frontend_head' => 'frontendHead',
        'frontend_footer' => 'frontendFooter',
        'frontend_error' => 'frontendError',
    ),
);
```

Такой plugin остаётся нормальным, если методы короткие и связаны одной ответственностью.

Если plugin начинает обрабатывать десятки разных областей — лучше выделить внутренние service/model классы, а обработчики оставить тонкими.

### 8.3. Cross-app handlers

Событие может быть вызвано явно от имени другого приложения:

```php
wa()->event(array('shop', 'routing'), $route);
```

В plugin handler нельзя полагаться только на текущий active app. Для cross-app случаев проверяй:

```php
wa()->getApp();
wa()->getEnv();
waRequest::param();
```

И не делай предположение, что событие всегда вызвано из backend или frontend.

### 8.4. Dynamic handlers через `waEvent::addCustomHandler()`

Старая документация описывает `waEvent::addCustomHandler()` как способ подключить динамический обработчик с ключами:

```php
$handler = array(
    'object' => new someCustomClass(),
    'method' => 'eventHandler',
    'event' => 'some_event',
    'event_app_id' => 'some_app',
);

waEvent::addCustomHandler($handler);
```

Это не основной plugin pattern. В обычной разработке приложения/плагина предпочтительнее `handlers` в `plugin.php`, потому что такой обработчик декларативен, виден в структуре plugin-а и проходит стандартный lifecycle.

---

## 9. Типовые ошибки

### Ошибка 1. Считать event самостоятельным endpoint

Неправильно:

```text
event frontend_head открывается по URL
```

Правильно:

```text
event вызывается только из уже выполняющегося PHP-кода приложения/layout/model/action.
```

### Ошибка 2. Подписаться на событие, которого приложение не вызывает

`plugin.php`:

```php
'handlers' => array(
    'frontend_sidebar' => 'frontendSidebar',
),
```

Но если в приложении нет:

```php
wa()->event('frontend_sidebar')
```

обработчик не будет вызван.

Перед реализацией нужно открыть app action/layout/model и найти реальный `wa()->event()`.

### Ошибка 3. Вернуть HTML там, где приложение ожидает массив

Неправильно:

```php
public function frontendActionPost($params)
{
    return '<div>HTML</div>';
}
```

Если app ожидает поля:

```php
$fields = array('nav_before', 'footer', 'head', 'sidebar');
```

правильно:

```php
public function frontendActionPost($params)
{
    return array(
        'sidebar' => '<div>HTML</div>',
    );
}
```

### Ошибка 4. Изменить данные по ссылке разрушительно

Неправильно:

```php
public function preparePostsFrontend(&$items)
{
    $items = array();
}
```

Правильно:

```php
public function preparePostsFrontend(&$items)
{
    foreach ($items as &$item) {
        $item['plugins']['after']['example'] = '<div>Extra</div>';
    }
    unset($item);
}
```

### Ошибка 5. Делать тяжёлые SQL-запросы внутри цикла обработчика

Неправильно:

```php
foreach ($items as &$item) {
    $row = $model->getByField('post_id', $item['id']);
}
```

Лучше:

```php
$ids = array_keys($items);
$rows = $model->getByField('post_id', $ids, 'post_id');
```

### Ошибка 6. Игнорировать env

События часто имеют варианты:

```text
prepare_posts_frontend
prepare_posts_backend
search_posts_frontend
search_posts_backend
```

Не смешивай backend и frontend логику в одном обработчике без проверки `wa()->getEnv()`.

### Ошибка 7. Дублировать core lifecycle

Неправильно:

```php
public function frontendHead()
{
    include 'some-plugin.php';
    new SomePluginBootstrap();
}
```

Правильно:

```php
public function frontendHead()
{
    return $this->renderHeadHtml();
}
```

Plugin уже загружен Webasyst. Не нужно писать свой bootstrap.

### Ошибка 8. Использовать event для прямой замены routing/controller

Если задача — создать endpoint, нужен route/controller/action.  
Если задача — встроиться в существующую страницу или процесс, нужен event.

### Ошибка 9. Не проверять plugin settings

Если обработчик управляется настройками:

```php
if (!$this->getSettings('enabled')) {
    return null;
}
```

Не заставляй plugin работать всегда, если у него есть настройки включения/выключения.

---

## 10. Чеклист разработчика

Перед commit event/plugin handler проверить:

### Событие

- [ ] Реальное событие найдено в app-коде через `wa()->event()` или `wa('app')->event()`.
- [ ] Понятно, где событие вызывается: action, layout, model, config.
- [ ] Понятен формат `$params`.
- [ ] Понятен ожидаемый return type.
- [ ] Понятно, изменяются ли данные по ссылке.
- [ ] Проверен env: frontend/backend/cli.

### Plugin config

- [ ] В `plugin.php` есть корректный `handlers`.
- [ ] Имя event совпадает с тем, что вызывает app.
- [ ] Метод-обработчик существует в основном plugin-классе.
- [ ] Plugin class назван по Webasyst naming.
- [ ] Если plugin требует settings, есть `settings.php`.

### Handler

- [ ] Метод public.
- [ ] Метод короткий.
- [ ] Нет тяжёлых запросов в цикле.
- [ ] Нет прямого SQL вне model/service.
- [ ] HTML-вывод безопасен.
- [ ] Входные данные валидируются.
- [ ] Для backend/изменяющих действий проверены права.
- [ ] Для POST/save используется CSRF на соответствующем уровне.

### Совместимость

- [ ] Не сломан backend/frontend env.
- [ ] Не нарушена структура данных приложения.
- [ ] Не удаляются чужие plugin blocks.
- [ ] Не используется статический вызов plugin helper из Smarty вместо безопасного `$wa`.
- [ ] Нет хардкода URL/домена/settlement.

---

## 11. Чеклист ИИ-агента

Перед ответом на задачу по events/hooks ИИ-агент обязан:

1. Определить app/plugin, к которому относится задача.
2. Открыть `wa-apps/{app_id}/lib/config/app.php`.
3. Проверить `plugins => true`.
4. Открыть место, где ожидается расширение: action/layout/model/config.
5. Найти реальный вызов `wa()->event()` или `wa('app')->event()`.
6. Зафиксировать имя события.
7. Понять формат параметров.
8. Понять ожидаемый результат: HTML, массив блоков, ошибки, изменение по ссылке.
9. Открыть `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php`.
10. Проверить `handlers`.
11. Открыть основной plugin class.
12. Проверить существующие methods/settings/models.
13. Если события нет — не придумывать его, а предложить точку добавления в app-код.
14. Если нужен endpoint — выбрать routing/controller/action, а не event.
15. Если нужен вывод в Smarty — проверить escaping и существующие theme blocks.

ИИ-агенту запрещено:

- придумывать hook name без поиска в коде;
- утверждать, что plugin сработает, если event не найден;
- возвращать HTML там, где app ожидает массив;
- изменять данные по ссылке разрушительно;
- подменять app routing event-ом;
- использовать Laravel/Symfony event listener pattern вместо Webasyst `plugin.php` handlers;
- писать обработчик без проверки прав/settings/env, если задача изменяющая или контекст неоднозначен.

---

## 12. Мини-сводка

Events/hooks в Webasyst — это договор между приложением и plugin-ами.

Правильная цепочка:

```text
app/layout/model/action
→ wa()->event('event_name', $params)
→ enabled plugins
→ lib/config/plugin.php handlers
→ {app}{Plugin}Plugin::{handlerMethod}()
→ return array/html/errors или изменение &$params
→ app использует результат
```

Правильный подход:

- сначала найти событие в app-коде;
- затем понять формат параметров и результата;
- только потом писать `handlers` и метод plugin-а.

Если события нет, plugin сам по себе его не создаст. Нужно либо добавить событие в app-код, либо использовать другой механизм Webasyst: routing, controller/action, helper, layout, model или settings.
