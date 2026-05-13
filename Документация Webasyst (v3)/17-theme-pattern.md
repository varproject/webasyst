# 17. Theme pattern в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить тему дизайна Webasyst как связку `theme.xml`, файлов темы, настроек, локализации, наследования и frontend templates.

---

## 1. Назначение механизма

Тема дизайна в Webasyst отвечает за внешний HTML/CSS/JS слой frontend-поселения.

Тема не заменяет приложение и не должна брать на себя обязанности action/model/controller. Правильное разделение такое:

```text
routing.php
→ frontend action
→ model/service
→ view assign
→ setThemeTemplate()
→ theme template
→ Smarty rendering
```

Тема должна:

- оформлять данные, которые подготовил action;
- использовать `$wa` helper;
- подключать CSS/JS через шаблоны и response assets;
- хранить настройки отображения в `theme.xml`;
- поддерживать локализацию theme strings;
- наследовать общие layout-файлы от parent theme, если это заявлено.

Тема не должна:

- писать в БД;
- выполнять сложную бизнес-логику;
- делать собственный routing;
- читать POST и сохранять данные;
- писать пользовательские файлы в `wa-apps`;
- обходить `wa()->getRouteUrl()` и `$wa` helpers.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/file/waTheme.class.php` | Читает `theme.xml`, определяет original/custom/trial theme, настройки, файлы, parent theme, locales. |
| `wa-system/view/waView.class.php` | Назначает глобальные переменные view и подключает theme template через `setThemeTemplate()`. |
| `wa-system/view/waSmarty3View.class.php` | Реализует Smarty rendering, compile/cache dirs, `fetch()`, `display()`. |
| `wa-system/controller/waViewAction.class.php` | Frontend action вызывает `setThemeTemplate()`. |
| `wa-system/layout/waLayout.class.php` | Frontend layout может вызывать `setThemeTemplate('index.html')`. |
| `wa-system/request/waRequest.class.php` | Определяет текущую тему через `waRequest::getTheme()`. |

### 2.2. Файлы темы

Обычная структура:

```text
wa-apps/{app_id}/themes/{theme_id}/
  theme.xml
  index.html
  error.html
  page.html
  default.css
  default.js
  ...
```

Для пользовательских изменений тема может существовать не только в `wa-apps`, но и в `wa-data`:

```text
wa-data/public/{app_id}/themes/{theme_id}/
```

`waTheme` различает несколько типов:

| Тип | Где лежит | Смысл |
|---|---|---|
| original | `wa-apps/{app}/themes/{theme}` | исходная тема приложения; |
| custom | `wa-data/public/{app}/themes/{theme}` | пользовательская тема; |
| overridden | есть и original, и custom | пользовательская копия перекрывает исходную; |
| trial | trial-путь | пробная тема; |
| none | тема не найдена | fallback/ошибка. |

---

## 3. Системная цепочка выполнения

### 3.1. Frontend routing выбирает приложение и тему

Frontend request проходит через global routing:

```text
wa-config/routing.php
→ waRouting::dispatch()
→ waRequest::param('app')
→ waRequest::param('theme')
→ waRequest::param('theme_mobile')
```

После этого Webasyst запускает приложение:

```text
wa($app, 1)
→ waFrontController
→ frontend action
```

### 3.2. Action выбирает конкретный template

Frontend action обычно делает:

```php
$this->setLayout(new shopFrontendLayout());
$this->setThemeTemplate('home.html');
```

или:

```php
$this->setThemeTemplate('post.html');
```

`setThemeTemplate()` не ищет файл в `templates/actions/`. Он переключает rendering на active theme directory.

### 3.3. Layout выбирает shell темы

Frontend layout обычно выбирает общий shell:

```php
$this->setThemeTemplate('index.html');
```

Например, `shopFrontendLayout` и `blogFrontendLayout` используют `index.html` как общий frontend layout темы.

### 3.4. `waView::setThemeTemplate()` назначает theme variables

При подключении theme template Webasyst назначает переменные:

```text
$wa_active_theme_path
$wa_active_theme_url
$wa_real_active_theme_url
$wa_theme_version
$theme_settings
$theme_settings_config
$wa_theme_url
$wa_real_theme_url
$wa_parent_theme_url
$wa_parent_theme_path
```

Значит, тема не должна вручную вычислять пути до себя.

---

## 4. Ключевые классы и методы

### 4.1. `waTheme`

`waTheme` создаётся так:

```php
$theme = new waTheme(waRequest::getTheme());
```

или с app id:

```php
$theme = new waTheme($theme_id, 'shop');
```

Ключевые обязанности:

- проверить ID темы;
- найти original/custom/trial path;
- прочитать `theme.xml`;
- прочитать `<files>`, `<settings>`, `<locales>`, `<thumbs>`, `<requirements>`;
- определить parent theme;
- отдать settings через `getSettings()`;
- проверить существование темы через `waTheme::exists()`.

### 4.2. `waView::setThemeTemplate()`

Метод:

```php
$this->view->setThemeTemplate($theme, $template);
```

делает несколько важных вещей:

1. Назначает theme paths/URLs.
2. Загружает theme settings.
3. Учитывает parent theme.
4. Загружает locales.
5. Устанавливает template dir в путь темы.
6. Проверяет, существует ли файл template.

### 4.3. `waViewAction::setThemeTemplate()`

Во frontend action используется protected-метод:

```php
protected function setThemeTemplate($template)
```

Он:

- создаёт/использует текущую theme instance;
- вызывает `waView::setThemeTemplate()`;
- выставляет template как `file:{template}`.

### 4.4. `waLayout::setThemeTemplate()`

Frontend layout использует тот же подход, но обычно для общего shell:

```php
$this->setThemeTemplate('index.html');
```

---

## 5. `theme.xml`

`theme.xml` — главный manifest темы.

Минимальный skeleton:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE theme PUBLIC "wa-app-theme" "http://www.webasyst.com/wa-content/xml/wa-app-theme.dtd">
<theme id="default" system="0" vendor="webasyst" author="Webasyst" app="shop" version="1.0.0" parent_theme_id="">
    <name locale="en_US">Default</name>
    <name locale="ru_RU">Дефолт</name>

    <files>
        <file path="index.html">
            <description locale="en_US">Main layout</description>
            <description locale="ru_RU">Основной макет</description>
        </file>
        <file path="home.html">
            <description locale="en_US">Homepage</description>
            <description locale="ru_RU">Главная страница</description>
        </file>
        <file path="error.html">
            <description locale="en_US">Error page</description>
            <description locale="ru_RU">Страница ошибки</description>
        </file>
        <file path="default.css" />
        <file path="default.js" />
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

### 5.1. Атрибуты `<theme>`

| Атрибут | Назначение |
|---|---|
| `id` | ID темы. |
| `system` | Системная/обычная тема. |
| `vendor` | Vendor ID. |
| `author` | Автор темы. |
| `app` | Приложение темы. |
| `version` | Версия темы. |
| `parent_theme_id` | Родительская тема, например `site:default`. |

### 5.2. `<files>`

`<files>` описывает файлы, которые тема предоставляет редактору дизайна и runtime:

```xml
<file path="product.html">
    <description locale="en_US">Dedicated product page</description>
    <description locale="ru_RU">Шаблон основной страницы товара</description>
</file>
```

В официальной теме Shop видно, что app-specific тема может наследовать часть файлов от Site parent theme:

```xml
<file path="index.html" parent="1" />
<file path="default.css" parent="1" />
```

Это означает: файл берётся из parent theme, если текущая тема его не перекрывает или если файл помечен как parent file.

### 5.3. `<settings>`

Настройки темы доступны в шаблоне как:

```smarty
{$theme_settings.show_sidebar}
```

Пример:

```xml
<setting var="homepage_blogposts" control_type="checkbox">
    <value>1</value>
    <name locale="en_US">Show latest blog posts on storefront homepage</name>
    <name locale="ru_RU">Показывать последние записи блога на главной странице интернет-магазина</name>
</setting>
```

### 5.4. `<locales>`

`<locales>` хранит строки темы, которые Webasyst загружает вместе с темой.

### 5.5. `<thumbs>`

`<thumbs>` описывает размеры изображений, используемые темой:

```xml
<thumbs>
    <thumb>96x96</thumb>
    <thumb>200</thumb>
    <thumb>750</thumb>
</thumbs>
```

---

## 6. Паттерны официальных тем

### 6.1. Shop theme

Официальная тема Shop показывает app-specific pattern:

```text
wa-apps/shop/themes/default/
  theme.xml
  home.html
  category.html
  product.html
  product.cart.html
  reviews.html
  my.orders.html
  my.order.html
  order.html
  checkout.success.html
  default.shop.css
  default.shop.js
```

Ключевой паттерн:

- `index.html` и базовый CSS могут идти от parent `site:default`;
- shop-specific templates отвечают за товар, категорию, корзину, оформление заказа;
- `theme_settings` управляют отображением блоков витрины;
- данные товаров готовит `shopFrontendAction`/дочерние actions и helpers.

### 6.2. Blog theme

Blog использует другой frontend pattern:

```text
stream.html
post.html
page.html
error.html
```

Action выбирает:

```php
$this->setThemeTemplate('stream.html');
```

или:

```php
$this->setThemeTemplate('post.html');
```

Layout выбирает:

```php
$this->setThemeTemplate('index.html');
```

### 6.3. Site theme

Site theme чаще всего является parent-shell:

```text
index.html
page.html
error.html
default.css
default.js
```

Site app также отвечает за страницы, blocks, variables и общие frontend элементы.

---

## 7. Минимальная реализация темы

### 7.1. Структура

```text
wa-apps/myapp/themes/default/
  theme.xml
  index.html
  home.html
  page.html
  error.html
  default.css
  default.js
```

### 7.2. `theme.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE theme PUBLIC "wa-app-theme" "http://www.webasyst.com/wa-content/xml/wa-app-theme.dtd">
<theme id="default" system="0" vendor="myvendor" author="My Vendor" app="myapp" version="1.0.0">
    <name locale="en_US">Default</name>
    <name locale="ru_RU">Дефолт</name>

    <files>
        <file path="index.html">
            <description locale="en_US">Main layout</description>
            <description locale="ru_RU">Основной макет</description>
        </file>
        <file path="home.html">
            <description locale="en_US">Homepage</description>
            <description locale="ru_RU">Главная страница</description>
        </file>
        <file path="page.html">
            <description locale="en_US">Static page</description>
            <description locale="ru_RU">Информационная страница</description>
        </file>
        <file path="error.html">
            <description locale="en_US">Error page</description>
            <description locale="ru_RU">Страница ошибки</description>
        </file>
        <file path="default.css" />
        <file path="default.js" />
    </files>

    <settings>
        <setting var="show_title" control_type="checkbox">
            <value>1</value>
            <name locale="en_US">Show page title</name>
            <name locale="ru_RU">Показывать заголовок страницы</name>
        </setting>
    </settings>
</theme>
```

### 7.3. `index.html`

```smarty
<!DOCTYPE html>
<html lang="{$wa->locale()}">
<head>
    <meta charset="utf-8">
    <title>{$wa->title()|escape}</title>
    {$wa->head()}
    <link rel="stylesheet" href="{$wa_theme_url}default.css?v={$wa_theme_version}">
</head>
<body>

<header class="site-header">
    <a href="{$wa_url}">{$wa->accountName()|escape}</a>
</header>

<main class="site-main">
    {$content}
</main>

<footer class="site-footer">
    {$wa->block('footer')}
</footer>

<script src="{$wa_theme_url}default.js?v={$wa_theme_version}"></script>
{$wa->js()}
</body>
</html>
```

### 7.4. `home.html`

```smarty
{if !empty($theme_settings.show_title)}
    <h1>{$wa->title()|escape}</h1>
{/if}

{if !empty($items)}
    <ul>
        {foreach $items as $item}
            <li>
                <a href="{$item.url|escape}">{$item.name|escape}</a>
            </li>
        {/foreach}
    </ul>
{else}
    <p>[`No items yet.`]</p>
{/if}
```

### 7.5. Frontend action

```php
<?php

class myappFrontendAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setLayout(new myappFrontendLayout());
        $this->setThemeTemplate('home.html');
    }

    public function execute()
    {
        $model = new myappItemModel();
        $items = $model->select('*')->order('sort')->fetchAll();

        $this->view->assign('items', $items);
        $this->getResponse()->setTitle(wa()->getAppInfo('name'));
    }
}
```

### 7.6. Frontend layout

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

---

## 8. Расширенная реализация

### 8.1. Parent theme

Если app-specific theme должна использовать общий shell Site:

```xml
<theme id="default" app="shop" parent_theme_id="site:default" version="1.0.0">
```

Тогда можно помечать файлы как parent:

```xml
<file path="index.html" parent="1" />
<file path="default.css" parent="1" />
```

Практический смысл:

- общий layout и базовые стили живут в parent theme;
- app-specific theme хранит только приложение-специфичные templates;
- обновления parent theme могут применяться к дочерней теме.

### 8.2. App-specific templates

Для Shop:

```text
home.html
category.html
product.html
product.cart.html
my.orders.html
my.order.html
checkout.success.html
```

Для Blog:

```text
stream.html
post.html
```

Для Site:

```text
index.html
page.html
error.html
```

### 8.3. Theme settings

Правильное использование:

```smarty
{if !empty($theme_settings.homepage_blogposts)}
    ...
{/if}
```

Неправильно:

```smarty
{* Не читать настройки напрямую через SQL или waAppSettingsModel в шаблоне. *}
```

### 8.4. Blocks and variables

В теме можно использовать Site helpers:

```smarty
{$wa->block('footer')}
{$wa->variable('phone')}
```

Но нужно помнить: блок может содержать Smarty, а значит должен считаться динамическим и потенциально ошибочным в debug/runtime.

---

## 9. Что нельзя делать в теме

### 9.1. Писать бизнес-логику

Неправильно:

```smarty
{php}
    $model = new shopProductModel();
{/php}
```

Правильно:

```php
$this->view->assign('products', $products);
```

и в шаблоне:

```smarty
{foreach $products as $product}
    {$product.name|escape}
{/foreach}
```

### 9.2. Формировать URL вручную

Неправильно:

```smarty
<a href="/shop/product/{$product.url}/">
```

Правильно:

```smarty
<a href="{$wa->shop->productUrl($product)|escape}">
```

или для app route:

```smarty
<a href="{$wa->getUrl('shop/frontend/product', ['product_url' => $product.url])|escape}">
```

### 9.3. Полагаться на hardcoded theme path

Неправильно:

```smarty
<link rel="stylesheet" href="/wa-apps/shop/themes/default/default.css">
```

Правильно:

```smarty
<link rel="stylesheet" href="{$wa_theme_url}default.css?v={$wa_theme_version}">
```

### 9.4. Сохранять файлы в `wa-apps`

Пользовательские изменения темы должны жить в `wa-data/public/{app}/themes/{theme}/`, а не в исходниках приложения.

### 9.5. Использовать нестандартные PHP-включения

Неправильно:

```smarty
{include file="../../../../wa-config/db.php"}
```

Правильно:

```smarty
{include file="product.cart.html"}
```

и только для файлов текущей темы/ожидаемого шаблонного контекста.

---

## 10. Типовые ошибки

### Ошибка 1. Думать, что theme template ищется в `templates/actions`

`setThemeTemplate('home.html')` ищет файл в активной теме, а не в `wa-apps/{app}/templates/actions`.

### Ошибка 2. Не указывать файл в `theme.xml`

Если файл темы должен быть управляемым через дизайн-редактор, его нужно описать в `<files>`.

### Ошибка 3. Хардкодить `/wa-apps/.../themes/...`

У темы может быть custom-copy в `wa-data`, CDN URL, parent theme и versioned assets.

### Ошибка 4. Писать SQL в Smarty

Тема должна получать готовые данные из action/helper.

### Ошибка 5. Игнорировать parent theme

Если файл помечен `parent="1"`, источник может быть не текущая тема, а родительская.

### Ошибка 6. Не экранировать пользовательские данные

Неправильно:

```smarty
{$user.name}
```

Правильно:

```smarty
{$user.name|escape}
```

### Ошибка 7. Смешивать theme settings и app settings

`theme_settings` управляют отображением темы. Настройки приложения, бизнес-правила и runtime state не должны храниться в `theme.xml`.

---

## 11. Чеклист разработчика

Перед commit темы проверить:

### Manifest

- [ ] Есть `theme.xml`.
- [ ] `id` темы соответствует директории.
- [ ] `app` соответствует приложению.
- [ ] `version` обновлён при изменениях.
- [ ] Все важные templates описаны в `<files>`.
- [ ] `parent_theme_id` указан осознанно.
- [ ] Parent files помечены `parent="1"` только если действительно берутся из родительской темы.

### Templates

- [ ] `index.html` содержит `{$content}`.
- [ ] В `<head>` есть `{$wa->head()}`.
- [ ] CSS/JS подключаются через `{$wa_theme_url}` и `{$wa_theme_version}` или response assets.
- [ ] Нет PHP-кода в Smarty.
- [ ] Нет SQL в Smarty.
- [ ] Нет hardcoded `/wa-apps/`.
- [ ] Все пользовательские данные экранируются.

### Settings

- [ ] Theme settings описаны в `<settings>`.
- [ ] `var` уникальны.
- [ ] Есть дефолтные `<value>`.
- [ ] Есть локализованные `<name>`.
- [ ] Настройки используются через `$theme_settings`.

### Routing and URLs

- [ ] URL строятся через `$wa->getUrl()`, app helper или route helpers.
- [ ] Нет hardcoded settlements.
- [ ] Theme корректно работает на разных доменах/поселениях.

### Security

- [ ] Нет прямого include системных файлов.
- [ ] Нет записи файлов в `wa-apps`.
- [ ] Нет вывода raw HTML без причины.
- [ ] `nofilter` используется только для доверенного HTML.

---

## 12. Чеклист ИИ-агента

Перед ответом по теме дизайна ИИ-агент обязан:

1. Открыть `wa-apps/{app_id}/lib/config/app.php`.
2. Проверить, включены ли `themes` и `pages`.
3. Открыть `wa-config/routing.php` или settlement, если задача зависит от домена/темы.
4. Открыть `wa-apps/{app_id}/themes/{theme_id}/theme.xml`.
5. Проверить `parent_theme_id`.
6. Открыть нужный theme template.
7. Открыть action, который вызывает `setThemeTemplate()`.
8. Открыть layout, который вызывает `setThemeTemplate('index.html')`.
9. Проверить, какие переменные action назначает в `$this->view->assign()`.
10. Проверить, какие `$theme_settings` доступны.
11. Проверить escaping.
12. Только потом предлагать правку.

ИИ-агенту запрещено:

- придумывать theme file без проверки `theme.xml`;
- писать PHP в Smarty;
- добавлять SQL в template;
- хардкодить theme URL;
- игнорировать parent theme;
- менять app logic в теме;
- сохранять пользовательские данные в `wa-apps`.

---

## 13. Мини-сводка

Theme pattern Webasyst — это не самостоятельный MVC-слой. Это frontend presentation layer, который подключается через:

```text
frontend route
→ frontend action
→ setLayout()
→ setThemeTemplate('content-template.html')
→ layout setThemeTemplate('index.html')
→ waView::setThemeTemplate()
→ theme.xml + theme settings + parent theme
→ Smarty rendering
```

Правильная тема:

- описана в `theme.xml`;
- использует `$wa` helpers;
- читает `theme_settings`;
- не содержит бизнес-логики;
- не хардкодит URL;
- уважает parent theme;
- безопасно экранирует данные;
- получает подготовленные данные из action/model/service.
