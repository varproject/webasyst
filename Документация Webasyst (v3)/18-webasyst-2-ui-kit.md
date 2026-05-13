# 18. Webasyst 2 UI-KIT

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить Webasyst 2 UI-KIT как нативный backend UI-слой Webasyst, а не как набор случайных CSS-классов.

---

## 1. Назначение механизма

Webasyst 2 UI-KIT — это системная дизайн-система для backend-интерфейсов Webasyst.

Она решает четыре задачи:

1. **Единый внешний вид backend-приложений** — меню, формы, таблицы, карточки, диалоги, уведомления и layout выглядят как часть Webasyst, а не как внешний admin-шаблон.
2. **Совместимость с текущим backend shell** — CSS/JS подключаются через системный `$wa->css()` и `$wa->js()`.
3. **Поддержка светлого/тёмного режима** — приложение должно использовать системные CSS-переменные и классы, а не фиксированные цвета.
4. **Миграция с UI 1.3** — Webasyst умеет выбирать UI-версию приложения по `app.php`, cookie/настройкам backend UI и runtime-флагам.

Главное правило: если приложение заявляет поддержку UI 2.0, backend-интерфейс должен строиться на нативных классах Webasyst 2. Нельзя брать Bootstrap/AdminLTE/Tailwind как основу backend-страницы, если задача — нативный интерфейс Webasyst.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/waSystem.class.php` | Определяет текущую UI-версию через `whichUI()`. |
| `wa-system/view/waViewHelper.class.php` | Формирует `$wa->css()`, `$wa->js()`, подключает `wa-2.0.css` для backend UI 2.0. |
| `wa-system/controller/waActionTemplatePathBuilder.trait.php` | Выбирает `templates/actions/` или `templates/actions-legacy/` в зависимости от UI. |
| `wa-system/config/waRightConfig.class.php` | Генерирует HTML прав для UI 1.3 и UI 2.0. |
| `wa-system/layout/waLayout.class.php` | Рендерит layout-шаблон и передаёт blocks во view. |
| `wa-system/view/waSmarty3View.class.php` | Smarty-view, через который рендерятся UI-шаблоны. |

### 2.2. Файлы приложения

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/config/app.php` | Объявляет поддержку UI через ключ `ui`. |
| `wa-apps/{app_id}/lib/layouts/{app}Backend.layout.php` | Backend layout приложения. |
| `wa-apps/{app_id}/templates/layouts/{Backend}.html` | Layout-шаблон для UI 2.0. |
| `wa-apps/{app_id}/templates/layouts-legacy/{Backend}.html` | Legacy layout для UI 1.3, если приложение поддерживает оба режима. |
| `wa-apps/{app_id}/templates/actions/{module}/...` | Backend action templates для UI 2.0. |
| `wa-apps/{app_id}/templates/actions-legacy/{module}/...` | Legacy action templates для UI 1.3. |
| `wa-apps/{app_id}/js/...` | JS приложения. |
| `wa-apps/{app_id}/css/...` | CSS приложения, если нужен поверх системного UI. |

### 2.3. Локальный UI-KIT источник

Источник: архив `Дизайн-система Webasyst 2.zip`.

Ключевые группы UI-KIT:

| Группа | Компоненты |
|---|---|
| Основы | Webasyst 2, основы стиля, требования, текущий режим интерфейса, тёмный режим, сценарии перевода на `wa-2.0.css`. |
| Layout | `.sidebar`, `.content`, `.article`, `.box`, `.flexbox`, `.inlinebox`, `.bottombar`, структура приложения и адаптивность. |
| Навигация | `.menu`, `.tabs`, `.breadcrumbs`, `.paging`, меню приложений. |
| Формы | `.fields`, элементы форм, чекбоксы, радио, селекты, `.waAutocomplete()`, `.waUpload()`, `.waSwitch()`, `.waToggle()`, `.waSlider()`. |
| Данные | `.tablebox`, таблицы, `.badge`, `.chips`, `.card`, `.list`, `.bricks`, `.thumbs`, `.userpic`. |
| Feedback | `.alert`, `.banner`, `.spinner`, `.skeleton`, `.pulsar`, `.waLoading()`, `.waProgressbar()`, `.waTooltip`. |
| JS widgets | `.waDialog()`, `.waDrawer()`, `.waDropdown()`. |
| Typography/style | Типографика, цвета, отступы, `.align-left`, `.bold`, `.hint`, `.strike`, `.heading`, `.icon`, анимации, шпаргалка. |

---

## 3. Как Webasyst выбирает UI-версию

UI-версия приложения задаётся в `wa-apps/{app_id}/lib/config/app.php`.

Примеры:

```php
'ui' => '2.0',
```

```php
'ui' => '1.3,2.0',
```

```php
'ui' => '2.0,1.3',
```

### 3.1. Что означает `ui => '2.0'`

Приложение поддерживает только UI 2.0.

Практические следствия:

- `$wa->css()` подключит `wa-2.0.css` в backend;
- templates должны быть в `templates/actions/` и `templates/layouts/`;
- legacy-шаблоны не нужны;
- нельзя ориентироваться на классы UI 1.3 как основную разметку.

Пример: CRM использует `ui => '2.0'`.

### 3.2. Что означает `ui => '1.3,2.0'`

Приложение поддерживает оба режима.

Практические следствия:

- Webasyst может выбрать UI 1.3 или UI 2.0 в зависимости от системного режима;
- для backend templates может понадобиться пара директорий:
  - `templates/actions/` — UI 2.0;
  - `templates/actions-legacy/` — UI 1.3;
- тот же принцип работает для layouts:
  - `templates/layouts/`;
  - `templates/layouts-legacy/`;
- action/controller должен быть осторожен с переменными, которые используются в обоих шаблонах.

Примеры: Shop-Script и Blog поддерживают оба режима.

### 3.3. Что делает `wa()->whichUI()`

`waSystem::whichUI($app_id)` возвращает фактическую UI-версию: `1.3` или `2.0`.

Упрощённая логика:

1. Если включён single app mode — возвращается `2.0`.
2. Если есть `force_ui_version`, он может принудительно задать версию.
3. Если app поддерживает `1.3,2.0`, используется текущая backend UI-настройка.
4. Если app поддерживает только одну версию, возвращается она.

Использовать `wa()->whichUI()` нужно в PHP-коде, где действительно нужен runtime branch. В шаблонах лучше разделять UI через template directories, а не через огромные `{if $wa->whichUI()}`.

---

## 4. Как подключается UI 2.0

Backend UI 2.0 подключается не руками через `<link>`.

Правильный путь:

```smarty
{$wa->css()}
{$wa->js()}
```

В backend при UI 2.0 `$wa->css()` добавляет:

```html
<link href=".../wa-content/css/wa/wa-2.0.css" rel="stylesheet">
```

Также подключаются служебные скрипты, viewport, Font Awesome и дополнительные CSS, добавленные через response.

### 4.1. Неправильно

```smarty
<link rel="stylesheet" href="/wa-content/css/wa/wa-2.0.css">
```

Проблемы:

- hardcode root URL;
- не учитывается cache/versioning;
- не учитывается UI 1.3 fallback;
- можно продублировать системные CSS;
- ломается нестандартная установка Webasyst в подпапке.

### 4.2. Правильно

```smarty
<!DOCTYPE html>
<html>
<head>
    <title>{$wa->title()|escape}</title>
    {$wa->css()}
    {$wa->js()}
</head>
<body>
    {$content}
</body>
</html>
```

Если приложение использует общий backend shell Webasyst, `$wa->header()` и системный backend wrapper подключаются в layout приложения по принятому в нём pattern.

---

## 5. Базовая структура backend UI 2.0

Типичная backend-страница UI 2.0 строится вокруг двух зон:

```html
<div class="sidebar">
    ...
</div>
<div class="content">
    ...
</div>
```

Внутри `content` обычно используется `.article`:

```html
<div class="content">
    <div class="article">
        <div class="article-body">
            ...
        </div>
    </div>
</div>
```

Для страниц без sidebar может быть только `.content` + `.article`.

### 5.1. `.sidebar`

`sidebar` — навигационная или вспомогательная колонка.

Используется для:

- разделов приложения;
- фильтров;
- дерева категорий;
- списка сущностей;
- вторичной навигации.

Типовой skeleton:

```smarty
<div class="sidebar">
    <div class="sidebar-header">
        <h5 class="heading">[`Sections`]</h5>
    </div>

    <div class="sidebar-body">
        <ul class="menu">
            <li class="selected"><a href="{$wa_app_url}">[`Dashboard`]</a></li>
            <li><a href="{$wa_app_url}settings/">[`Settings`]</a></li>
        </ul>
    </div>
</div>
```

### 5.2. `.content`

`content` — основная область.

```smarty
<div class="content">
    <div class="article">
        <div class="article-body">
            <h1>[`Orders`]</h1>
            ...
        </div>
    </div>
</div>
```

### 5.3. `.article`

`.article` задаёт внутреннюю структуру страницы.

Частый pattern:

```smarty
<div class="article">
    <div class="article-header">
        <div class="flexbox middle space-12">
            <h1 class="custom-m-0">[`Page title`]</h1>
            <button class="button green">[`Create`]</button>
        </div>
    </div>

    <div class="article-body">
        ...
    </div>
</div>
```

---

## 6. Layout primitives

### 6.1. `.box`

`.box` — базовый контейнер с внутренними отступами.

```smarty
<div class="box">
    <p>[`Content`]</p>
</div>
```

Использовать для небольших групп содержимого, а не как универсальный wrapper для всей страницы.

### 6.2. `.flexbox`

`.flexbox` — основной utility для горизонтального layout.

```smarty
<div class="flexbox middle space-12">
    <h1 class="custom-m-0">[`Products`]</h1>
    <div class="wide"></div>
    <button class="button green">[`Add product`]</button>
</div>
```

Частые модификаторы:

| Класс | Назначение |
|---|---|
| `middle` | Вертикальное выравнивание по центру. |
| `space-8`, `space-12`, `space-16` | Расстояние между элементами. |
| `wide` | Растягивающийся элемент-разделитель. |

### 6.3. `.inlinebox`

`inlinebox` — inline-группировка элементов.

Подходит для:

- компактных кнопок;
- badges;
- переключателей;
- коротких control-групп.

### 6.4. `.bottombar`

`bottombar` используется для закреплённой нижней панели действий.

```smarty
<div class="bottombar sticky">
    <button class="button green">[`Save`]</button>
    <a class="button light-gray" href="{$wa_app_url}">[`Cancel`]</a>
</div>
```

Не нужно делать собственный `position: fixed` без необходимости.

---

## 7. Навигация

### 7.1. `.menu`

Главный список навигации.

```smarty
<ul class="menu">
    <li class="selected">
        <a href="{$wa_app_url}">
            <i class="fas fa-home"></i>
            <span>[`Dashboard`]</span>
        </a>
    </li>
    <li>
        <a href="{$wa_app_url}settings/">
            <i class="fas fa-cog"></i>
            <span>[`Settings`]</span>
        </a>
    </li>
</ul>
```

Правила:

- активный пункт — `li.selected`;
- URL строить через `{$wa_app_url}` или `wa()->getAppUrl()`;
- иконки использовать как декоративное усиление, не как единственный смысл.

### 7.2. `.tabs`

Для переключения разделов внутри одной сущности.

```smarty
<div class="tabs">
    <ul>
        <li class="selected"><a href="{$wa_app_url}product/1/">[`General`]</a></li>
        <li><a href="{$wa_app_url}product/1/seo/">[`SEO`]</a></li>
    </ul>
</div>
```

Не использовать tabs как основную левую навигацию приложения.

### 7.3. `.breadcrumbs`

Для иерархического пути.

```smarty
<div class="breadcrumbs">
    <a href="{$wa_app_url}">[`Home`]</a>
    <span class="separator">/</span>
    <span>[`Current page`]</span>
</div>
```

### 7.4. `.paging`

Для pagination.

```smarty
<div class="paging">
    <a href="?page=1">1</a>
    <span class="selected">2</span>
    <a href="?page=3">3</a>
</div>
```

---

## 8. Формы

### 8.1. `.fields`

Базовая структура формы.

```smarty
<form method="post" action="{$wa_app_url}settings/save/">
    {$wa->csrf()}

    <div class="fields">
        <div class="field">
            <div class="name">[`Name`]</div>
            <div class="value">
                <input type="text" name="name" value="{$name|escape}">
                <div class="hint">[`Visible in backend only.`]</div>
            </div>
        </div>

        <div class="field">
            <div class="name">[`Enabled`]</div>
            <div class="value">
                <label>
                    <span class="wa-checkbox">
                        <input type="checkbox" name="enabled" value="1"{if $enabled} checked{/if}>
                        <span><span class="icon"><i class="fas fa-check"></i></span></span>
                    </span>
                    [`Enable feature`]
                </label>
            </div>
        </div>
    </div>

    <div class="bottombar sticky">
        <button class="button green" type="submit">[`Save`]</button>
    </div>
</form>
```

### 8.2. POST формы

Любая изменяющая форма должна содержать:

```smarty
{$wa->csrf()}
```

И backend-код обязан проверять:

- права;
- типы входных данных;
- существование сущности;
- принадлежность сущности текущему app/plugin/route;
- корректный redirect или JSON response.

### 8.3. Чекбоксы, radio, select

UI 2.0 имеет собственную разметку для красивых checkbox/radio. Не нужно заменять их Bootstrap-компонентами.

Для простых форм допустим обычный `<input>`, но если страница претендует на нативный вид, использовать UI-KIT wrappers.

---

## 9. Кнопки

Основной класс:

```html
<button class="button">...</button>
```

Типовые варианты:

```html
<button class="button green">Save</button>
<button class="button red">Delete</button>
<button class="button light-gray">Cancel</button>
<a class="button" href="...">Open</a>
```

Правила:

- primary action обычно `green`;
- destructive action — `red`;
- secondary/cancel — `light-gray` или обычная ссылка;
- не делать custom `.btn-primary`, если приложение работает на UI 2.0.

---

## 10. Таблицы и data display

### 10.1. `.tablebox`

`tablebox` используется для таблиц и табличных списков.

```smarty
<div class="tablebox">
    <table class="zebra">
        <thead>
            <tr>
                <th>[`ID`]</th>
                <th>[`Name`]</th>
                <th>[`Status`]</th>
            </tr>
        </thead>
        <tbody>
            {foreach $items as $item}
                <tr>
                    <td>{$item.id}</td>
                    <td>{$item.name|escape}</td>
                    <td><span class="badge">{$item.status|escape}</span></td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
```

### 10.2. `.badge`

Для коротких статусов.

```html
<span class="badge green">Active</span>
<span class="badge yellow">Draft</span>
<span class="badge red">Error</span>
```

Не использовать badge для длинных сообщений.

### 10.3. `.chips`

Для наборов тегов/фильтров.

```smarty
<div class="chips">
    <span class="chip">[`New`]</span>
    <span class="chip">[`Paid`]</span>
</div>
```

### 10.4. `.card`

Для самостоятельных смысловых блоков.

```smarty
<div class="card">
    <div class="card-body">
        <h3>[`Total`]</h3>
        <p class="large">{$total}</p>
    </div>
</div>
```

### 10.5. `.list`, `.bricks`, `.thumbs`, `.userpic`

| Компонент | Где использовать |
|---|---|
| `.list` | Вертикальные списки сущностей. |
| `.bricks` | Сетка небольших блоков/плиток. |
| `.thumbs` | Списки с изображениями. |
| `.userpic` | Аватары пользователей/контактов. |

---

## 11. Feedback-компоненты

### 11.1. `.alert`

Для важных сообщений.

```smarty
<div class="alert success">
    [`Settings saved.`]
</div>
```

Варианты: `success`, `warning`, `danger`, `info`.

### 11.2. `.banner`

Для крупных информационных блоков.

```smarty
<div class="banner">
    <h2>[`Welcome`]</h2>
    <p>[`Configure your app before start.`]</p>
</div>
```

### 11.3. `.spinner`

Для ожидания.

```html
<span class="spinner"></span>
```

### 11.4. `.skeleton`

Для placeholder-состояний.

```html
<div class="skeleton height-40"></div>
```

### 11.5. `.pulsar`

Для привлечения внимания к новому элементу или подсказке.

---

## 12. JS widgets

### 12.1. `.waDialog()`

Используется для модальных окон.

Общий pattern:

```javascript
$.waDialog({
    html: $('#dialog-template').html(),
    onOpen: function ($dialog, dialog) {
        // init controls
    }
});
```

Не нужно подключать Bootstrap Modal поверх UI 2.0.

### 12.2. `.waDrawer()`

Для боковых выезжающих панелей.

Подходит для:

- подробной карточки;
- быстрого редактирования;
- preview;
- secondary flow без полной смены страницы.

### 12.3. `.waDropdown()`

Для dropdown controls.

```javascript
$('.js-dropdown').waDropdown();
```

Backend action должен отдавать корректную HTML-разметку; JS не должен компенсировать хаос в шаблоне.

### 12.4. `.waSwitch()`, `.waToggle()`, `.waUpload()`, `.waAutocomplete()`, `.waSlider()`

Эти виджеты использовать вместо кастомных реализаций, если задача совпадает с их назначением.

| Виджет | Назначение |
|---|---|
| `.waSwitch()` | Переключатель on/off. |
| `.waToggle()` | Группа переключаемых вариантов. |
| `.waUpload()` | Upload control. |
| `.waAutocomplete()` | Autocomplete input. |
| `.waSlider()` | Slider/range input. |

---

## 13. Typography и utilities

### 13.1. Заголовки

Использовать стандартные `h1`–`h6` и `.heading`, если нужен служебный заголовок в UI.

```smarty
<h1>[`Settings`]</h1>
<h5 class="heading">[`Section`]</h5>
```

### 13.2. Utility classes

| Класс | Назначение |
|---|---|
| `.align-left` | Выравнивание влево. |
| `.align-center` | Выравнивание по центру. |
| `.align-right` | Выравнивание вправо. |
| `.bold` | Полужирный текст. |
| `.hint` | Вторичный текст/подсказка. |
| `.strike` | Зачёркнутый текст. |
| `.gray` | Серая подсказочная окраска. |

### 13.3. Отступы

UI 2.0 активно использует utility-классы для spacing.

Примеры:

```html
<div class="custom-mt-16"></div>
<div class="custom-mb-24"></div>
<div class="custom-p-16"></div>
```

Не создавать десятки локальных CSS-классов только ради margin/padding, если есть системные utilities.

---

## 14. Dark mode

UI 2.0 поддерживает тёмный режим.

Практические правила:

1. Не писать фиксированные цвета без необходимости.
2. Использовать системные CSS-переменные.
3. Не задавать белый фон `#fff` для cards/containers, если это ломает dark mode.
4. Не задавать чёрный текст `#000` поверх системного background.
5. Проверять icons, borders, hints, disabled controls в тёмном режиме.

Неправильно:

```css
.my-box {
    background: #fff;
    color: #000;
    border: 1px solid #ddd;
}
```

Лучше:

```css
.my-box {
    background: var(--background-color-blank);
    color: var(--text-color);
    border: 1px solid var(--border-color-soft);
}
```

Если точные переменные неизвестны, нужно проверить актуальный `wa-2.0.css` в текущем ядре, а не придумывать имена.

---

## 15. Responsive behavior

UI 2.0 рассчитан на адаптивный backend.

Проверять:

- sidebar на узких экранах;
- таблицы и `.tablebox`;
- bottombar на мобильной ширине;
- forms `.fields`;
- dropdown/dialog/drawer overflow;
- длинные названия сущностей;
- кнопки в `.flexbox`.

Не полагаться на фиксированную ширину вроде:

```css
.my-page {
    width: 1280px;
}
```

Лучше использовать гибкие системные containers:

```smarty
<div class="article wider">
    <div class="article-body">
        ...
    </div>
</div>
```

---

## 16. Миграция с UI 1.3 на UI 2.0

### 16.1. Когда приложение поддерживает оба режима

`app.php`:

```php
'ui' => '1.3,2.0',
```

Файлы:

```text
wa-apps/myapp/templates/actions/backend/Backend.html
wa-apps/myapp/templates/actions-legacy/backend/Backend.html
```

`waActionTemplatePathBuilder` сам выберет директорию:

- UI 2.0 → `templates/actions/`;
- UI 1.3 backend → сначала `templates/actions-legacy/`, затем fallback на `templates/actions/`.

### 16.2. Что делать при миграции

1. Открыть `app.php` и проверить `ui`.
2. Найти текущий layout и action templates.
3. Проверить, есть ли `templates/actions-legacy/`.
4. Перенести layout на `.sidebar + .content + .article`.
5. Заменить старые form/table/menu classes на UI 2.0 equivalents.
6. Проверить `$wa->css()` и `$wa->js()`.
7. Проверить rights UI, если приложение использует `waRightConfig`.
8. Проверить dark mode.
9. Проверить mobile/narrow width.

### 16.3. Что нельзя делать

- переносить UI 1.3 template в `templates/actions/` без адаптации;
- оставлять старые `.block`, `.fields.form`, `.value.submit` patterns как основу UI 2.0;
- добавлять Bootstrap только для кнопок/forms;
- подключать AdminLTE как backend shell;
- hardcode `wa-2.0.css` вручную;
- делать UI branch в каждом шаблоне вместо разделения `templates/actions` и `templates/actions-legacy`.

---

## 17. Минимальная реализация UI 2.0 backend page

### 17.1. app.php

```php
<?php

return array(
    'name'     => 'My app',
    'icon'     => 'img/myapp.svg',
    'version'  => '1.0.0',
    'vendor'   => 'myvendor',
    'rights'   => true,
    'csrf'     => true,
    'ui'       => '2.0',
);
```

### 17.2. routing.backend.php

```php
<?php

return array(
    '' => 'backend',
);
```

### 17.3. Action

Файл:

```text
wa-apps/myapp/lib/actions/backend/myappBackend.action.php
```

```php
<?php

class myappBackendAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'items' => array(),
        ));
    }
}
```

### 17.4. Template

Файл:

```text
wa-apps/myapp/templates/actions/backend/Backend.html
```

```smarty
<div class="sidebar">
    <div class="sidebar-body">
        <ul class="menu">
            <li class="selected">
                <a href="{$wa_app_url}">
                    <i class="fas fa-home"></i>
                    <span>[`Dashboard`]</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="content">
    <div class="article">
        <div class="article-header">
            <div class="flexbox middle space-12">
                <h1 class="custom-m-0">[`Dashboard`]</h1>
                <div class="wide"></div>
                <button class="button green" type="button">[`Create`]</button>
            </div>
        </div>

        <div class="article-body">
            <div class="alert info">
                [`This is a native Webasyst 2 backend page.`]
            </div>
        </div>
    </div>
</div>
```

---

## 18. Расширенная реализация: form + table + bottombar

```smarty
<div class="content">
    <div class="article">
        <div class="article-header">
            <h1>[`Settings`]</h1>
        </div>

        <div class="article-body">
            <form method="post" action="{$wa_app_url}settings/save/">
                {$wa->csrf()}

                <div class="fields">
                    <div class="field">
                        <div class="name">[`Title`]</div>
                        <div class="value">
                            <input type="text" name="title" value="{$title|escape}">
                            <div class="hint">[`Shown in backend only.`]</div>
                        </div>
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
                                    <td><span class="badge green">[`Active`]</span></td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                <div class="bottombar sticky">
                    <button class="button green" type="submit">[`Save`]</button>
                    <a class="button light-gray" href="{$wa_app_url}">[`Cancel`]</a>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

## 19. Типовые ошибки

### Ошибка 1. Подключать `wa-2.0.css` вручную

Неправильно:

```smarty
<link rel="stylesheet" href="/wa-content/css/wa/wa-2.0.css">
```

Правильно:

```smarty
{$wa->css()}
```

### Ошибка 2. Заявить `ui => '2.0'`, но оставить UI 1.3 markup

Неправильно:

```smarty
<div class="block double-padded">
    <div class="fields form">
        ...
    </div>
</div>
```

Правильно:

```smarty
<div class="article">
    <div class="article-body">
        <div class="fields">
            ...
        </div>
    </div>
</div>
```

### Ошибка 3. Использовать Bootstrap/AdminLTE как backend shell

Неправильно:

```smarty
<div class="container-fluid">
    <div class="card shadow-sm">
        <button class="btn btn-primary">Save</button>
    </div>
</div>
```

Правильно:

```smarty
<div class="article">
    <div class="article-body">
        <div class="card">
            <button class="button green">[`Save`]</button>
        </div>
    </div>
</div>
```

### Ошибка 4. Не учитывать dark mode

Неправильно:

```css
.panel {
    background: white;
    color: black;
}
```

Правильно:

```css
.panel {
    background: var(--background-color-blank);
    color: var(--text-color);
}
```

### Ошибка 5. Делать один шаблон с огромным UI branch

Неправильно:

```smarty
{if $wa->whichUI() == '2.0'}
    ...1000 строк...
{else}
    ...1000 строк...
{/if}
```

Правильно:

```text
templates/actions/backend/Backend.html
templates/actions-legacy/backend/Backend.html
```

### Ошибка 6. Заменять системные widgets самописными

Если нужен dialog, drawer, dropdown, switch, upload, autocomplete — сначала проверить UI-KIT.

### Ошибка 7. Делать POST форму без CSRF

UI-красота не заменяет безопасность.

```smarty
{$wa->csrf()}
```

### Ошибка 8. Выносить business logic в Smarty

Шаблон должен отображать данные. Получение данных, права, фильтры, сортировка и сохранение — PHP action/model/service.

---

## 20. Чеклист разработчика

### App config

- [ ] В `app.php` указан корректный `ui`.
- [ ] Если поддерживается только UI 2.0, нет зависимости от legacy templates.
- [ ] Если поддерживаются оба режима, есть `templates/actions-legacy/` там, где нужно.
- [ ] Проверен `wa()->whichUI()` только там, где runtime branch действительно нужен.

### Layout

- [ ] Backend layout подключает `{$wa->css()}` и `{$wa->js()}`.
- [ ] Нет ручного hardcode `wa-2.0.css`.
- [ ] Страница использует `.sidebar`, `.content`, `.article` по назначению.
- [ ] Нет Bootstrap/AdminLTE/Tailwind как основы backend shell.

### Forms

- [ ] Формы построены через `.fields`.
- [ ] POST формы содержат `{$wa->csrf()}`.
- [ ] Backend проверяет права и типизирует request.
- [ ] Ошибки выводятся через `.alert` или нативный pattern приложения.

### Data display

- [ ] Таблицы используют `.tablebox` и системные table classes.
- [ ] Статусы используют `.badge`, `.chips` или понятный UI-KIT component.
- [ ] Empty/loading states используют `.alert`, `.skeleton`, `.spinner`.

### JS

- [ ] Dialog/dropdown/drawer используют нативные UI widgets.
- [ ] JS инициализируется после AJAX/htmx swap, если страница динамическая.
- [ ] JS не содержит бизнес-логики, которую должен выполнить backend.

### Dark mode/responsive

- [ ] Нет жёстких `#fff`, `#000`, `#ddd` без необходимости.
- [ ] Используются системные CSS-переменные.
- [ ] Проверены sidebar/content/table/form на узкой ширине.

---

## 21. Чеклист ИИ-агента

Перед ответом по UI 2.0 ИИ-агент обязан:

1. Открыть `wa-apps/{app_id}/lib/config/app.php`.
2. Проверить значение `ui`.
3. Открыть текущий layout.
4. Открыть текущий action template.
5. Проверить наличие `templates/actions-legacy/` и `templates/layouts-legacy/`.
6. Проверить, как приложение подключает `$wa->css()` и `$wa->js()`.
7. Проверить существующий UI pattern в этом приложении.
8. Проверить, нужны ли формы, CSRF и права.
9. Проверить, нужен ли компонент UI-KIT вместо самописного JS/CSS.
10. Проверить dark mode и responsive риски.
11. Только после этого писать HTML/CSS/JS.

ИИ-агенту запрещено:

- предлагать Bootstrap/AdminLTE/Tailwind как основу backend UI 2.0;
- hardcode `wa-2.0.css`;
- смешивать UI 1.3 и UI 2.0 без проверки `app.php`;
- писать POST форму без `{$wa->csrf()}`;
- делать самописный modal/dropdown/upload, если есть UI-KIT widget;
- использовать фиксированные цвета, ломающие dark mode;
- писать PHP в Smarty;
- переносить бизнес-логику из action/model в шаблон.

---

## 22. Мини-сводка

Webasyst 2 UI-KIT — это нативный backend UI-слой Webasyst.

Правильная цепочка:

```text
app.php ui
→ wa()->whichUI()
→ $wa->css()
→ wa-2.0.css
→ templates/actions/ или templates/actions-legacy/
→ layout: sidebar/content/article
→ UI-KIT components
→ dark mode/responsive compatible backend page
```

Для разработчика и ИИ-агента главный вывод: UI 2.0 — не внешняя библиотека и не “стили по вкусу”. Это часть системного Webasyst runtime, связанная с `app.php`, `$wa->css()`, template lookup, rights UI и backend layout.
