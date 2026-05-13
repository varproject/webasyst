# 12. Plugin helpers и app helpers в Webasyst

**Статус:** опубликован v3  
**Язык:** русский  
**Назначение:** объяснить, как безопасно отдавать plugin/app-функции в Smarty-шаблоны через Webasyst helper-слой, не превращая шаблон в PHP-код и не создавая fatal error при отключении plugin-а.

---

## 1. Назначение механизма

Helper в Webasyst — это объект, доступный из Smarty через переменную `$wa`.

Типовые вызовы:

```smarty
{$wa->shop->products('set/bestsellers')}
{$wa->shop->productUrl($product)}
{$wa->shop->myPlugin->method()}
```

Helper нужен, когда шаблону требуется получить небольшую прикладную функцию:

- построить URL;
- получить публичный список данных;
- отрендерить небольшой HTML-фрагмент;
- проверить наличие plugin-а;
- вызвать безопасный frontend helper plugin-а.

Helper не должен заменять action, controller, model или service.

Если логика:

- меняет данные;
- требует POST/CSRF;
- зависит от прав доступа;
- выполняет сложный workflow;
- содержит тяжёлые SQL-запросы;
- должна возвращать JSON/redirect/file response;

то это не helper, а action/controller/service/model.

---

## 2. Какие файлы участвуют

### 2.1. Системные файлы

| Файл | Роль |
|---|---|
| `wa-system/view/waViewHelper.class.php` | Базовый `$wa` helper: `$wa->css()`, `$wa->js()`, `$wa->head()`, `$wa->getUrl()`, `$wa->param()` и app-helper доступ. |
| `wa-system/view/waPluginViewHelper.class.php` | Базовый helper для plugin-а, безопасный для вызова из Smarty. |
| `wa-system/plugin/waPlugin.class.php` | Основной plugin class: settings, install/update, assets, routing, rights. |
| `wa-system/view/waView.class.php` | Назначает глобальные Smarty-переменные, включая `$wa`. |
| `wa-system/view/waSmarty3View.class.php` | Реализация view на Smarty 3. |

### 2.2. Файлы app helper-а

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/lib/classes/{app_id}ViewHelper.class.php` | App helper, доступный как `$wa->{app_id}`. |

Пример:

```text
wa-apps/shop/lib/classes/shopViewHelper.class.php
```

В шаблоне:

```smarty
{$wa->shop->products('set/bestsellers')}
```

### 2.3. Файлы plugin helper-а

| Файл | Роль |
|---|---|
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/{app_id}{Plugin}PluginViewHelper.class.php` | Plugin helper, доступный как `$wa->{app_id}->{plugin_id}Plugin`. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/{app_id}{Plugin}.plugin.php` | Основной plugin class. |
| `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php` | Plugin metadata, handlers, settings flags. |

Пример для plugin `debug` в app `shop`:

```text
wa-apps/shop/plugins/debug/lib/shopDebugPluginViewHelper.class.php
```

Класс:

```php
class shopDebugPluginViewHelper extends waPluginViewHelper
{
}
```

В шаблоне:

```smarty
{$wa->shop->debugPlugin->method()}
```

---

## 3. Системная цепочка выполнения

### 3.1. Как появляется `$wa`

1. Action или layout вызывает `wa()->getView()`.
2. Системный view создаёт `waViewHelper`.
3. `waView::prepare()` назначает в Smarty глобальные переменные:

```text
wa_url
wa_static_url
wa_backend_url
wa_app
wa_app_url
wa_app_static_url
wa
```

4. В шаблоне становится доступна переменная:

```smarty
{$wa}
```

5. Через `$wa` вызываются системные методы, app helper-ы и plugin helper-ы.

### 3.2. Как вызывается app helper

Пример:

```smarty
{$wa->shop->products('set/bestsellers')}
```

Логическая цепочка:

```text
Smarty template
→ $wa
→ waViewHelper
→ app helper shopViewHelper
→ method products()
→ model/collection/service
→ return array/string/html
```

App helper — это публичный API приложения для шаблонов.

### 3.3. Как вызывается plugin helper

Пример:

```smarty
{$wa->shop->redirectPlugin->version()}
```

Логическая цепочка:

```text
Smarty template
→ $wa
→ waViewHelper
→ app helper layer
→ plugin helper shopRedirectPluginViewHelper
→ waPluginViewHelper::version()
→ plugin main class getVersion()
```

Если plugin отключён или удалён, helper должен безопасно вернуть пустую строку там, где это предусмотрено системным helper-контрактом.

---

## 4. Ключевые классы и методы

## 4.1. `waViewHelper`

`waViewHelper` — основной helper-объект, который доступен в Smarty как `$wa`.

Частые методы:

```smarty
{$wa->css()}
{$wa->js()}
{$wa->head()}
{$wa->getUrl('shop/frontend/product', ['product_url' => $product.url])}
{$wa->param('category_url')}
{$wa->userId()}
{$wa->setting('name', '', 'shop')}
```

`waViewHelper` — системный фасад. Его не нужно создавать вручную в шаблоне.

## 4.2. App helper

App helper — прикладной helper приложения.

Пример из Shop-Script:

```php
class shopViewHelper extends waAppViewHelper
{
    public function products($hash = '', $offset = null, $limit = null, $options = array())
    {
        $collection = new shopProductsCollection($hash, $options);
        return $collection->getProducts('*', $offset, $limit, true);
    }
}
```

В шаблоне:

```smarty
{$_products = $wa->shop->products('set/bestsellers')}
```

App helper должен быть стабильным публичным API для templates/themes.

## 4.3. `waPluginViewHelper`

`waPluginViewHelper` — базовый класс plugin helper-а.

Он решает три задачи:

1. Даёт безопасный объект для вызова из Smarty.
2. Позволяет проверить наличие plugin-а через `version()`.
3. Даёт защищённый доступ к main plugin class через `plugin()`.

Пример системного комментария описывает такой template-вызов:

```smarty
{$wa->shop->debugPlugin->whatever()}
```

И безопасную проверку:

```smarty
{if $wa->shop->debugPlugin->version()}
    ...
{/if}
```

## 4.4. `version()`

Метод `version()` возвращает версию plugin-а, если plugin установлен и включён.

Если plugin недоступен, возвращается пустая строка.

Правильно:

```smarty
{if $wa->shop->myPlugin->version()}
    {$wa->shop->myPlugin->renderBadge($product)}
{/if}
```

Неправильно:

```smarty
{if class_exists('shopMyPlugin')}
    {shopMyPlugin::renderBadge($product)}
{/if}
```

## 4.5. `plugin()`

Метод `plugin()` внутри helper-а возвращает основной plugin object.

Использовать его нужно только внутри helper-класса:

```php
class shopMyPluginViewHelper extends waPluginViewHelper
{
    public function setting($name)
    {
        return $this->plugin()->getSettings($name);
    }
}
```

В Smarty не нужно вызывать `plugin()` напрямую.

---

## 5. Параметры config/settings/request

### 5.1. Источники данных helper-а

Helper может читать:

| Источник | Через что читать | Когда допустимо |
|---|---|---|
| Route params | `waRequest::param()` или `$wa->param()` | Для URL-контекста: category/product/page. |
| GET | `waRequest::get()` или `$wa->get()` | Для фильтров, page, sort. |
| Settings app | `wa()->getSetting()` / `waAppSettingsModel` | Для конфигурации приложения. |
| Plugin settings | `$this->plugin()->getSettings()` | Для настроек plugin-а. |
| Models/collections | model/service/collection | Для публичного чтения данных. |

Helper не должен напрямую доверять request-данным.

Даже если helper вызывается из шаблона, он всё равно работает в PHP-контексте приложения и обязан типизировать входные данные.

### 5.2. Plugin settings внутри helper-а

Правильно:

```php
$value = $this->plugin()->getSettings('enabled');
```

Неправильно:

```php
$model = new waAppSettingsModel();
$value = $model->get('shop.myplugin', 'enabled');
```

Причина: plugin main class уже знает свой settings key, default values из `settings.php`, JSON-декодирование и стандартную логику Webasyst.

---

## 6. Паттерн официального Webasyst-кода

### 6.1. `waPluginViewHelper`

Системный класс прямо описывает рекомендуемый template API:

```smarty
{$wa->shop->debugPlugin->whatever()}
```

И объясняет преимущество перед static call: если plugin удалён, вызов не ломает шаблон fatal error-ом, а безопасно возвращает пустую строку.

### 6.2. `shopViewHelper`

`shopViewHelper` — пример крупного app helper-а.

Он содержит методы для frontend templates:

- `products()`;
- `productsCount()`;
- `productSet()`;
- `skus()`;
- `images()`;
- `settings()`;
- `productUrl()`;
- `productImgHtml()`;
- `productImgUrl()`;
- `currency()`;
- `cart()`;
- `customer()`.

Важно: helper не просто отдаёт HTML. Он выступает frontend API приложения для тем.

### 6.3. `shopRedirectPlugin`

`shopRedirectPlugin` показывает основной plugin class:

- handlers в `plugin.php`;
- `getControls()`;
- `saveSettings()`;
- custom control для settings;
- чтение settings через `$this->getSettings()`;
- построение URL через `wa()->getRouteUrl()`.

Это main plugin class, а не Smarty helper. Для публичного вызова из темы лучше делать отдельный `*PluginViewHelper`, если plugin должен отдавать template API.

---

## 7. Минимальная реализация plugin helper-а

Задача: plugin `badge` приложения `shop` должен отдавать публичный HTML бейджа товара в теме.

### 7.1. Plugin config

```php
<?php

return array(
    'name'        => /*_wp*/('Product Badge'),
    'description' => /*_wp*/('Adds a product badge helper for storefront themes.'),
    'vendor'      => 'myvendor',
    'version'     => '1.0.0',
    'handlers'    => array(),
);
```

Файл:

```text
wa-apps/shop/plugins/badge/lib/config/plugin.php
```

### 7.2. Main plugin class

```php
<?php

class shopBadgePlugin extends shopPlugin
{
    public function getBadgeHtml($product)
    {
        if (empty($product['badge'])) {
            return '';
        }

        return '<span class="badge">'.htmlspecialchars($product['badge'], ENT_QUOTES, 'utf-8').'</span>';
    }
}
```

Файл:

```text
wa-apps/shop/plugins/badge/lib/shopBadge.plugin.php
```

### 7.3. View helper

```php
<?php

class shopBadgePluginViewHelper extends waPluginViewHelper
{
    public function html($product)
    {
        if (!$this->version()) {
            return '';
        }

        if (!is_array($product)) {
            return '';
        }

        return $this->plugin()->getBadgeHtml($product);
    }
}
```

Файл:

```text
wa-apps/shop/plugins/badge/lib/shopBadgePluginViewHelper.class.php
```

### 7.4. Вызов в теме

```smarty
{if $wa->shop->badgePlugin->version()}
    {$wa->shop->badgePlugin->html($product) nofilter}
{/if}
```

`nofilter` допустим только если helper сам гарантирует escaping и возвращает готовый HTML.

---

## 8. Расширенная реализация

### 8.1. Helper с настройками plugin-а

```php
<?php

class shopBadgePluginViewHelper extends waPluginViewHelper
{
    public function isEnabled()
    {
        return (bool)$this->plugin()->getSettings('enabled');
    }

    public function html($product)
    {
        if (!$this->version() || !$this->isEnabled()) {
            return '';
        }

        if (!is_array($product) || empty($product['badge'])) {
            return '';
        }

        $class = $this->plugin()->getSettings('css_class');
        if (!$class) {
            $class = 'badge';
        }

        return sprintf(
            '<span class="%s">%s</span>',
            htmlspecialchars($class, ENT_QUOTES, 'utf-8'),
            htmlspecialchars($product['badge'], ENT_QUOTES, 'utf-8')
        );
    }
}
```

### 8.2. Helper с model/service

Если helper читает данные из БД, лучше не держать SQL в helper-е.

Правильно:

```php
class shopBadgePluginViewHelper extends waPluginViewHelper
{
    public function getProductBadge($product_id)
    {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return '';
        }

        $model = new shopBadgePluginBadgeModel();
        $badge = $model->getByProductId($product_id);

        if (!$badge) {
            return '';
        }

        return htmlspecialchars($badge['name'], ENT_QUOTES, 'utf-8');
    }
}
```

Model:

```php
class shopBadgePluginBadgeModel extends waModel
{
    protected $table = 'shop_badge_badge';

    public function getByProductId($product_id)
    {
        return $this->getByField('product_id', (int)$product_id);
    }
}
```

---

## 9. Типовые ошибки

### Ошибка 1. Статический вызов plugin-а из Smarty

Неправильно:

```smarty
{shopBadgePlugin::html($product)}
```

Правильно:

```smarty
{$wa->shop->badgePlugin->html($product)}
```

Статический вызов может сломать тему, если plugin удалён или отключён.

### Ошибка 2. Main plugin class использовать как template API

Неправильно:

```smarty
{$wa->shop->getPlugin('badge')->getBadgeHtml($product)}
```

Правильно:

```smarty
{$wa->shop->badgePlugin->html($product)}
```

Main plugin class — lifecycle, handlers, settings, routing, install/update. Template API лучше держать в `*PluginViewHelper`.

### Ошибка 3. Не проверять наличие plugin-а

Неправильно:

```smarty
{$wa->shop->badgePlugin->html($product) nofilter}
```

Лучше:

```smarty
{if $wa->shop->badgePlugin->version()}
    {$wa->shop->badgePlugin->html($product) nofilter}
{/if}
```

### Ошибка 4. Возвращать HTML без escaping

Неправильно:

```php
return '<span>'.$product['badge'].'</span>';
```

Правильно:

```php
return '<span>'.htmlspecialchars($product['badge'], ENT_QUOTES, 'utf-8').'</span>';
```

Если helper возвращает HTML, он отвечает за безопасность этого HTML.

### Ошибка 5. Делать save/delete через helper

Неправильно:

```smarty
{$wa->shop->badgePlugin->deleteBadge($product.id)}
```

Правильно:

```text
POST action/controller + CSRF + rights + model/service
```

Helper — не command endpoint.

### Ошибка 6. Писать SQL в Smarty

Неправильно:

```smarty
{php} ... SQL ... {/php}
```

Правильно:

```smarty
{$wa->shop->badgePlugin->getProductBadge($product.id)}
```

А SQL — в model/service.

### Ошибка 7. Возвращать тяжёлые данные без лимитов

Неправильно:

```php
public function allProducts()
{
    return (new shopProductModel())->getAll();
}
```

Правильно:

```php
public function products($limit = 20)
{
    $limit = max(1, min(100, (int)$limit));
    return (new shopProductModel())->select('*')->limit($limit)->fetchAll();
}
```

---

## 10. Чеклист разработчика

Перед commit helper-а проверить:

### Назначение

- [ ] Helper действительно нужен шаблону.
- [ ] Это не POST/save/delete workflow.
- [ ] Сложная бизнес-логика вынесена в model/service/plugin main class.
- [ ] Helper не дублирует существующий app helper.

### Naming

- [ ] Plugin helper назван `{app}{Plugin}PluginViewHelper`.
- [ ] Файл лежит в `plugins/{plugin}/lib/`.
- [ ] Класс extends `waPluginViewHelper`.
- [ ] Main plugin class назван `{app}{Plugin}Plugin`.

### Template API

- [ ] В Smarty используется `$wa->{app}->{plugin}Plugin->method()`.
- [ ] Есть проверка `version()`, если plugin опционален.
- [ ] Нет static calls.
- [ ] Нет прямого `getPlugin()` из Smarty.

### Security

- [ ] Все входные параметры типизированы.
- [ ] Пользовательские строки экранируются.
- [ ] `nofilter` используется только для уже безопасного HTML.
- [ ] Нет SQL в helper-е, если нужна model/service.
- [ ] Нет write operations без action/controller/CSRF/rights.

### Performance

- [ ] Нет неограниченных выборок.
- [ ] Есть лимиты для списков.
- [ ] Повторяемые данные кешируются там, где это оправдано.

---

## 11. Чеклист ИИ-агента

Перед ответом по helper-ам ИИ-агент обязан:

1. Определить, это app helper или plugin helper.
2. Открыть `lib/config/app.php` или `plugins/{plugin}/lib/config/plugin.php`.
3. Проверить, включены ли plugins у app.
4. Открыть основной plugin class `{app}{Plugin}.plugin.php`, если задача про plugin.
5. Проверить наличие `*PluginViewHelper.class.php`.
6. Проверить существующий app helper `{app}ViewHelper.class.php`.
7. Проверить, вызывается ли helper из Smarty, frontend theme или backend template.
8. Проверить, не должен ли код быть action/controller вместо helper-а.
9. Проверить escaping и необходимость `nofilter`.
10. Проверить, нет ли write operation без CSRF/rights.
11. Только после этого писать helper-код.

ИИ-агенту запрещено:

- предлагать static вызовы plugin-а из Smarty;
- писать `{php}` в Smarty;
- делать save/delete через helper;
- возвращать HTML без escaping;
- придумывать helper path без проверки naming;
- смешивать plugin settings, app settings и route params;
- утверждать, что plugin helper безопасен, если не проверен `waPluginViewHelper`-контракт.

---

## 12. Мини-сводка

Правильный plugin helper flow:

```text
Smarty theme/template
→ {$wa->shop->badgePlugin->html($product)}
→ shopBadgePluginViewHelper extends waPluginViewHelper
→ version() проверяет доступность plugin-а
→ plugin() даёт основной shopBadgePlugin
→ main plugin/model/service готовит данные
→ helper возвращает безопасную строку или HTML
```

Главное правило: **шаблон вызывает helper, helper вызывает plugin/model/service, а изменяющие операции остаются в controller/action с правами и CSRF**.
