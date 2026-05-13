# 19. Паттерны официальных приложений Webasyst

**Статус:** опубликован v3  
**Назначение:** дать прикладную карту официальных приложений Webasyst как набор архитектурных паттернов, а не как пересказ исходников.  
**Главный принцип:** при разработке нового app/plugin ИИ-агент и разработчик должны сначала определить, на какой официальный pattern похожа задача, и только затем проектировать routing, actions, layout, models, templates, events и rights.

---

## 1. Назначение главы

Официальные приложения Webasyst полезны не тем, что их можно копировать целиком, а тем, что они показывают устойчивые варианты архитектуры:

| Приложение | Основной pattern |
|---|---|
| `site` | settlement/page/theme pattern |
| `blog` | compact content app pattern |
| `shop` | large commerce app pattern |
| `crm` | backend-first/private frontend endpoint pattern |

Эта глава фиксирует:

1. какие capabilities объявляет приложение в `lib/config/app.php`;
2. как устроен frontend/backend routing;
3. какие controller/action/layout классы принимают управление;
4. где находятся модели и схемы БД;
5. как используются Smarty/theme templates;
6. где подключаются events/hooks;
7. как устроены rights;
8. как приложение интегрируется с plugin ecosystem.

---

## 2. Как пользоваться этой главой

Перед разработкой новой функции нужно выбрать ближайший официальный pattern.

### Если задача про страницы, поселения, темы, статический контент

Смотреть `site`.

### Если задача про компактный контентный app с frontend-лентой и записями

Смотреть `blog`.

### Если задача про сложное приложение с commerce domain, множеством routes, backend tabs, API, платежами, доставкой, заказами, plugin ecosystem

Смотреть `shop`.

### Если задача про backend-first приложение с frontend endpoint-ами для форм, callback-ов, invoice, private URLs

Смотреть `crm`.

---

## 3. `site` — settlement/page/theme pattern

### 3.1. Назначение pattern

`site` — системное приложение для управления доменами, поселениями, страницами, темами и публичной структурой сайта.

Его pattern нужен, когда задача связана с:

- routing settlements;
- frontend pages;
- theme rendering;
- site blocks/variables/snippets;
- domain-level config;
- app pages through `pages => true`.

### 3.2. App config

`site/lib/config/app.php` показывает приложение с полным набором site-oriented capabilities:

```php
'frontend'   => true,
'system'     => true,
'rights'     => true,
'plugins'    => true,
'themes'     => true,
'pages'      => true,
'auth'       => true,
'csrf'       => true,
'my_account' => true,
'routing_params' => array(
    'priority_settlement' => true,
),
'ui' => '2.0,1.3',
```

Смысл:

| Capability | Роль |
|---|---|
| `frontend` | приложение может быть поселено на домене |
| `themes` | приложение работает с design themes |
| `pages` | приложение поддерживает page routes |
| `routing_params.priority_settlement` | приложение может влиять на выбор settlement |
| `system` | системная роль приложения |
| `auth`, `my_account` | участие в пользовательском frontend auth/my-account контуре |

### 3.3. Frontend routing

`site/lib/config/routing.php` минимален:

```php
'login/'          => 'login/',
'forgotpassword/' => 'forgotpassword/',
'signup/'         => 'signup/',
'data/regions/'   => 'frontend/regions',
'my/'             => array(
    'module' => 'frontend',
    'action' => 'my',
    'secure' => true,
),
'<url>' => 'frontend/',
```

Главная идея: почти любой frontend URL отдаётся в `frontend/`, а конкретная страница выбирается через page/theme механизм.

### 3.4. Action pattern

`siteFrontendAction extends waPageAction`.

Он:

1. получает `$page` из `$this->params`;
2. назначает page vars;
3. рендерит page content через Smarty;
4. выставляет title/meta/OG;
5. назначает breadcrumbs;
6. вызывает `setThemeTemplate('page.html')`;
7. при ошибке использует `error.html`.

Это page-first pattern, а не controller-first pattern.

### 3.5. Template pattern

Основные шаблоны лежат в теме:

```text
wa-apps/site/themes/{theme_id}/
wa-data/public/site/themes/{theme_id}/
```

Ключевые файлы:

```text
theme.xml
index.html
page.html
error.html
```

`setThemeTemplate('page.html')` переключает Smarty template dir на текущую тему и наполняет theme variables.

### 3.6. Model pattern

`site` опирается на page model layer:

```text
sitePageModel
siteBlockModel
siteVariableModel
```

Для page rendering используется `waPageAction` и наследники.

### 3.7. Events/hooks pattern

`site` часто выступает инфраструктурой, через которую другие apps получают:

- frontend apps list;
- blocks;
- variables;
- domain config;
- theme rendering.

Для app-level задач нужно учитывать, что Site может быть не просто “ещё одно приложение”, а системная инфраструктура frontend.

### 3.8. Когда брать `site` за образец

Использовать этот pattern, если нужно:

- добавить публичные страницы;
- работать с settlement/domain/theme;
- внедрить page-like контент;
- добавить site block/helper;
- сделать frontend route, похожий на статическую страницу.

Не использовать как образец для:

- сложного backend-модуля;
- commerce-логики;
- JSON API;
- массовых операций;
- plugin settings.

---

## 4. `blog` — compact content app pattern

### 4.1. Назначение pattern

`blog` — компактное content-приложение с frontend-лентой, posts, comments, timeline, авторами, blog-level rights и theme rendering.

Его pattern нужен, когда задача связана с:

- публикациями;
- лентой записей;
- slug-based frontend URLs;
- компактной моделью данных;
- content events;
- frontend theme templates.

### 4.2. App config

`blog/lib/config/app.php` включает:

```php
'rights'     => true,
'frontend'   => true,
'auth'       => true,
'themes'     => true,
'plugins'    => true,
'pages'      => true,
'mobile'     => true,
'csrf'       => true,
'my_account' => true,
'routing_params' => array(
    'blog_url_type' => 1,
),
'ui' => '1.3,2.0',
```

Особенность: `blog_url_type` влияет на набор app routes и построение frontend URL.

### 4.3. Frontend routing

`blog/lib/config/routing.php` возвращает несколько route sets:

```php
return array(
    0 => array(...),
    1 => array(...),
    2 => array(...),
);
```

Это pattern “routing зависит от настройки URL-структуры”.

Примеры:

```php
'<blog_url>/<post_url>/' => 'frontend/post',
'<post_url>/'            => 'frontend/post',
'author/<contact_id:(\d+)>/' => 'frontend/',
'rss/' => 'frontend/rss',
''     => 'frontend/',
```

### 4.4. Action pattern

`blogFrontendAction`:

1. читает page из GET;
2. берёт route params через `waRequest::param()`;
3. собирает search options;
4. использует `blogPostModel`;
5. назначает layout `blogFrontendLayout`;
6. назначает theme template `stream.html`;
7. выставляет canonical/pagination links.

`blogFrontendPostAction`:

1. читает `post_url` из route params;
2. ищет post через model;
3. проверяет preview/status/rights;
4. выставляет title/meta/Last-Modified/canonical;
5. назначает theme template `post.html`.

### 4.5. Layout pattern

`blogFrontendLayout`:

- подключает frontend JS;
- назначает `site_theme_url`;
- нормализует `action`;
- вызывает event `frontend_action_{action}`;
- назначает `links`;
- использует `setThemeTemplate('index.html')`.

Это compact frontend layout pattern: один layout для frontend shell + action-specific theme template внутри.

### 4.6. Model pattern

`blogPostModel` показывает плотный domain model:

- `$table = 'blog_post'`;
- domain constants;
- search builder;
- timeline queries;
- event points `search_posts_{env}`, `prepare_posts_{env}`;
- data preparation before template.

`blog/lib/config/db.php` показывает типовую схему app:

```php
'blog_post' => array(
    'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
    ...
    ':keys' => array(
        'PRIMARY' => 'id',
        'routing' => array('status', 'url', 'blog_id'),
    ),
),
```

### 4.7. Rights pattern

`blogRightConfig`:

- добавляет общие права `add_blog`, `pages`, `design`;
- добавляет per-blog права через `selectlist`;
- использует уровни `RIGHT_NONE`, `RIGHT_READ`, `RIGHT_READ_WRITE`, `RIGHT_FULL`;
- вызывает event `rights.config`.

Это object-level rights pattern для набора сущностей.

### 4.8. Plugin integration pattern

`blog` активно использует events:

- `frontend_action_{action}`;
- `search_posts_backend`;
- `search_posts_frontend`;
- `prepare_posts_backend`;
- `prepare_posts_frontend`;
- `frontend_post`;
- `rights.config`.

### 4.9. Когда брать `blog` за образец

Использовать, если нужно:

- компактное frontend-приложение;
- CRUD-контент;
- slug routing;
- frontend theme templates;
- model-level search;
- object-level rights.

Не использовать как образец для:

- payment/shipping integrations;
- heavy backend-first app;
- multi-step checkout;
- large API groups.

---

## 5. `shop` — large commerce app pattern

### 5.1. Назначение pattern

`shop` — большое commerce-приложение с backend modules, frontend storefront, API routes, checkout, payment/shipping plugins, orders, products, categories, reports, marketing, rights, themes and plugin ecosystem.

Его pattern нужен, когда задача связана с:

- большим domain model;
- множеством backend/frontend routes;
- payment/shipping/sms plugins;
- сложными правами;
- backend UI 1.3/2.0 compatibility;
- API routes;
- checkout/account flows;
- category/product routing;
- plugin extensibility.

### 5.2. App config

`shop/lib/config/app.php` включает почти все major capabilities:

```php
'rights'           => true,
'frontend'         => true,
'auth'             => true,
'themes'           => true,
'plugins'          => true,
'pages'            => true,
'mobile'           => true,
'my_account'       => true,
'csrf'             => true,
'payment_plugins'  => array(...),
'shipping_plugins' => array(...),
'sms_plugins'      => true,
'routing_params'   => array(
    'checkout_version'       => 2,
    'checkout_storefront_id' => ...,
),
'ui' => '1.3,2.0',
```

Это pattern “приложение как платформа для экосистемы”.

### 5.3. Frontend routing

`shop/lib/config/routing.php` показывает несколько URL-типов:

```php
0 => $api_rules + array(...),
1 => $api_rules + array(...),
2 => $api_rules + array(...),
3 => $api_rules + array(...),
```

Внутри:

- API routes;
- login/signup/regions;
- search;
- cart;
- checkout;
- order;
- compare;
- tag;
- category;
- product;
- customer my account;
- payment/shipping callbacks.

Примеры:

```php
'api/v1/product/<id>/?' => 'frontend/apiProduct',
'cart/' => 'frontend/cart',
'checkout/<step:[^/]+>/?' => 'frontend/checkout',
'my/order/<id>/?' => array(
    'module' => 'frontend',
    'action' => 'myOrder',
    'secure' => true,
),
'<product_url:[^/]+>/' => 'frontend/product',
```

### 5.4. Backend routing

`shop/lib/config/routing.backend.php` показывает modern backend routing:

```php
'products/?'                      => 'prod/list',
'products/<id:\d+|new>/general/?' => 'prod/general',
'marketing/promos/?'              => 'marketingPromos',
''                                => 'backend',
```

Pattern: красивые backend URLs внутри `/backend_url/shop/`, но итог всё равно сводится к `module/action`.

### 5.5. Config subclass pattern

`shopConfig extends waAppConfig` переопределяет:

- `checkRights($module, $action)`;
- `getRouting($route, $dispatch)`;
- `getRoutingRules()`;
- storefront schedule/settings methods;
- plugin route injection;
- domain/storefront logic.

Это pattern для большого приложения, где обычного `waAppConfig` недостаточно.

### 5.6. Frontend action/layout pattern

`shopFrontendAction`:

- ставит `shopFrontendLayout`, если запрос не XHR;
- проверяет storefront mode;
- выставляет title/meta/OG/canonical;
- назначает `home.html`;
- в `display()` назначает frontend events and globals;
- обрабатывает frontend 404 через `frontend_error`.

`shopFrontendLayout`:

- обрабатывает currency/locale redirects;
- назначает `action`;
- вызывает `frontend_head`, `frontend_header`, `frontend_nav`, `frontend_nav_aux`, `frontend_footer`;
- назначает currencies;
- использует theme `index.html`.

### 5.7. Backend layout pattern

`shopBackendLayout`:

- управляет welcome/tutorial behavior;
- определяет active page based on module/action/plugin;
- назначает menu counters;
- вызывает `backend_menu`;
- назначает frontend URL;
- отдаёт переменные backend template.

Pattern: backend layout знает навигационную shell-структуру приложения, но не должен содержать business logic конкретного action.

### 5.8. Model pattern

`shop` использует множество domain-specific models:

```text
shopProductModel
shopCategoryModel
shopOrderModel
shopCurrencyModel
shopFeatureModel
shopProductImagesModel
shopProductReviewsModel
...
```

Большая логика часто вынесена в:

```text
collections
workflow
helper
config subclass
services/domain classes
```

Pattern: не перегружать action прямым SQL.

### 5.9. Rights pattern

`shopRightConfig` показывает сложную матрицу прав:

- `orders` как select;
- отдельные checkbox права;
- `type` как selectlist по product types;
- `workflow_actions` как list;
- event `rights.config`.

`shopConfig::checkRights()` мапит `module/action` на права:

```php
orders → orders
marketing* → marketing
reports* → reports
settings* → settings
service* → services
customers* → customers
prod sets/categories → setscategories
```

Pattern: права проверяются не только в UI, но и на runtime dispatch level.

### 5.10. Plugin integration pattern

`shop` — главный образец plugin ecosystem:

- `payment_plugins`;
- `shipping_plugins`;
- `sms_plugins`;
- app plugin settings;
- plugin backend actions;
- frontend plugin routing;
- events:
  - `routing`;
  - `backend_rights`;
  - `backend_menu`;
  - `frontend_head`;
  - `frontend_nav`;
  - `frontend_error`;
  - `rights.config`;
  - domain-specific hooks.

`shop/plugins/redirect` показывает типовой plugin:

```php
'handlers' => array(
    'frontend_error' => 'frontendError',
    'frontend_search' => 'frontendSearch'
),
```

Основной class `shopRedirectPlugin` extends `shopPlugin`, читает settings, обрабатывает 404 and redirect rules.

### 5.11. Когда брать `shop` за образец

Использовать, если нужно:

- крупное приложение;
- сложные backend modules;
- собственная app config subclass;
- domain-level settings/routing;
- plugin ecosystem;
- complex rights;
- API routes;
- multi-mode frontend routing.

Не использовать как образец, если задача простая и помещается в `waViewAction + model`.

---

## 6. `crm` — backend-first/private frontend endpoint pattern

### 6.1. Назначение pattern

`crm` — backend-first приложение, у которого frontend включён не ради публичной theme storefront, а ради служебных endpoints:

- form submit;
- iframe/headless form;
- invoice;
- payment plugin endpoint;
- email verification;
- private frontend routes.

### 6.2. App config

`crm/lib/config/app.php`:

```php
'plugins' => true,
'rights'  => true,
'csrf'    => true,
'routing_params' => array(
    'private' => true,
),
'payment_plugins' => array(
    'taxes' => true,
),
'sms_plugins' => true,
'frontend' => true,
'ui' => '2.0',
```

Ключевая особенность:

```php
'routing_params' => array(
    'private' => true,
),
```

Это значит: frontend есть, но route по умолчанию не является публичной витриной.

### 6.3. Frontend routing

`crm/lib/config/routing.php`:

```php
'form/?'                                => 'frontend/formSubmit',
'form/regions/?'                        => 'frontend/formRegions',
'form/iframe/<id:\d+>/?'                => 'frontend/formIframe',
'form/headless/<id:\d+>/?'              => 'frontend/formHeadless',
'form/<hash>/?'                         => 'frontend/form',
'confirm_email/<hash>/?'                => 'frontend/confirmEmail',
'invoice/<hash>/?'                      => 'frontend/invoice',
'data/payment/<plugin_id>/<action_id>/' => 'frontend/paymentPlugin',
'verification' => array(
    'url' => 'verification/<verification_key>/<message_id>/<hash>/?',
    'module' => 'frontend',
    'action' => 'verification',
    'secure' => true,
),
```

Pattern: frontend endpoints are explicit and narrow.

### 6.4. UI pattern

`crm` declares:

```php
'ui' => '2.0'
```

Это означает, что backend templates должны проектироваться под Webasyst UI 2.0, без fallback на legacy UI 1.3 unless explicitly needed for compatibility.

### 6.5. Payment/SMS integration pattern

CRM не является магазином, но подключает:

```php
'payment_plugins' => array(
    'taxes' => true,
),
'sms_plugins' => true,
```

Pattern: app может использовать отдельные plugin categories без полной commerce архитектуры `shop`.

### 6.6. Когда брать `crm` за образец

Использовать, если нужно:

- backend-first приложение;
- private frontend endpoint;
- form endpoint;
- iframe/headless endpoint;
- invoice/payment callback;
- UI 2.0 backend only;
- integration-heavy app without public theme storefront.

Не использовать как образец для:

- публичного theme-driven frontend;
- blog-like content app;
- large product/category storefront.

---

## 7. Сравнительная таблица app config

| Capability | `site` | `blog` | `shop` | `crm` |
|---|---:|---:|---:|---:|
| `frontend` | yes | yes | yes | yes |
| `themes` | yes | yes | yes | no/не основной |
| `pages` | yes | yes | yes | no |
| `plugins` | yes | yes | yes | yes |
| `rights` | yes | yes | yes | yes |
| `csrf` | yes | yes | yes | yes |
| `auth` | yes | yes | yes | no explicit |
| `my_account` | yes | yes | yes | no |
| `payment_plugins` | no | no | yes | partial |
| `shipping_plugins` | no | no | yes | no |
| `sms_plugins` | no | no | yes | yes |
| `ui` | `2.0,1.3` | `1.3,2.0` | `1.3,2.0` | `2.0` |
| special routing params | `priority_settlement` | `blog_url_type` | `checkout_*` | `private` |

---

## 8. Сравнительная таблица routing

| Pattern | `site` | `blog` | `shop` | `crm` |
|---|---|---|---|---|
| Global settlement | ключевой | есть | ключевой storefront | private endpoints |
| App route sets | минимальный | несколько URL типов | много URL типов | один endpoint set |
| Pages | core | enabled | enabled | нет |
| Theme route | основной | основной | основной storefront | нет |
| Secure frontend | my/auth | my/auth | my/order/profile/checkout | verification |
| API routes | нет | минимум/rss | много API v1 | payment/form endpoints |
| Backend routing | есть | old/new mixed | активно | backend-first |

---

## 9. Сравнительная таблица classes

| Слой | `site` | `blog` | `shop` | `crm` |
|---|---|---|---|---|
| Frontend action | `siteFrontendAction extends waPageAction` | `blogFrontendAction`, `blogFrontendPostAction` | `shopFrontendAction` + many children | `crmFrontend*Action` |
| Layout | theme/page oriented | `blogFrontendLayout` | `shopFrontendLayout`, `shopBackendLayout` | backend UI 2.0 layouts/actions |
| Config subclass | system/site-specific | mostly default + right config | `shopConfig` major subclass | CRM app config/classes |
| Models | pages/blocks/variables | post/blog/comment/page | product/order/category/etc. | contacts/deals/forms/invoices |
| Rights config | app-level | object-level blogs | complex matrix | backend app-level/domain-specific |
| Plugin integration | site blocks/themes | content events | full ecosystem | payment/sms/form integrations |

---

## 10. Как выбрать pattern для новой задачи

### 10.1. Новое публичное app с темами

База:

```text
site + blog
```

Использовать:

- `frontend => true`;
- `themes => true`;
- `pages => true`, если нужны страницы;
- `routing.php`;
- `setThemeTemplate()`;
- `waViewAction`;
- frontend layout.

### 10.2. Новый backend-first app

База:

```text
crm
```

Использовать:

- `rights => true`;
- `csrf => true`;
- `ui => '2.0'`;
- backend actions/controllers;
- explicit frontend endpoints only if needed;
- no theme dependency unless app really has public frontend.

### 10.3. Новый commerce-like app

База:

```text
shop
```

Использовать осторожно:

- app config subclass only when needed;
- domain-specific models/services;
- route sets;
- rights config;
- plugin events;
- payment/shipping/sms flags only when реально нужны.

### 10.4. Новый content app

База:

```text
blog
```

Использовать:

- slug routing;
- model search methods;
- theme templates;
- layout events;
- object-level rights if есть несколько content containers.

---

## 11. Типовые ошибки

### Ошибка 1. Копировать `shop` для простой задачи

`shop` — тяжёлый pattern. Для простого backend CRUD не нужен собственный `Config` subclass, десятки events и route sets.

### Ошибка 2. Делать публичный frontend по `crm` pattern

`crm` frontend — endpoint-oriented, not theme storefront. Для публичного сайта лучше смотреть `site/blog/shop`.

### Ошибка 3. Игнорировать `routing_params`

`routing_params` — часть settlement behavior. Например:

- `blog_url_type`;
- `checkout_version`;
- `private`;
- `priority_settlement`.

Их нельзя переносить между apps без понимания.

### Ошибка 4. Считать `pages => true` универсальной настройкой

`pages => true` нужен, если app реально поддерживает page routes and page model integration.

### Ошибка 5. Делать rights только в UI

`shop` показывает runtime check через `checkRights()`. UI checkbox не является защитой.

### Ошибка 6. Использовать theme templates для backend UI

Theme templates — frontend/site storefront layer. Backend UI должен жить в `templates/actions/` и `templates/layouts/`.

### Ошибка 7. Использовать plugin events без документированного payload

Перед обработчиком нужно открыть место вызова event и понять:

- что передаётся;
- по ссылке или по значению;
- что должен вернуть handler;
- как результат используется.

---

## 12. Чеклист разработчика

Перед выбором architecture pattern:

### App profile

- [ ] Это public frontend app, backend-first app или plugin?
- [ ] Нужны ли themes?
- [ ] Нужны ли pages?
- [ ] Нужен ли my account?
- [ ] Нужны ли payment/shipping/sms plugins?
- [ ] Нужна ли custom app config class?

### Routing

- [ ] Есть ли global settlement?
- [ ] Есть ли app-level `routing.php`?
- [ ] Нужен ли `routing.backend.php`?
- [ ] Есть ли route params in `app.php`?
- [ ] Есть ли secure frontend routes?

### Controllers/actions

- [ ] Достаточно ли `waViewAction`?
- [ ] Нужен ли `waViewController + waLayout`?
- [ ] Нужны ли JSON/long/CLI controllers?
- [ ] Нужно ли theme rendering?

### Models/data

- [ ] Есть ли model per table?
- [ ] Есть ли `db.php`?
- [ ] Есть ли install/update scripts?
- [ ] Где должна жить business logic?

### Rights/security

- [ ] Есть ли `rights => true`?
- [ ] Есть ли `RightConfig`?
- [ ] Есть ли runtime rights checks?
- [ ] Есть ли CSRF для POST?
- [ ] Нужны ли object-level rights?

### Plugin ecosystem

- [ ] Нужны ли events?
- [ ] Есть ли payload contract?
- [ ] Нужен ли plugin settings page?
- [ ] Нужен ли plugin helper?
- [ ] Нужен ли plugin routing?

---

## 13. Чеклист ИИ-агента

Перед проектированием решения ИИ-агент обязан:

1. Определить ближайший official app pattern: `site`, `blog`, `shop`, `crm`.
2. Открыть `wa-apps/{app}/lib/config/app.php`.
3. Проверить capabilities: `frontend`, `themes`, `pages`, `plugins`, `rights`, `csrf`, `ui`, `routing_params`.
4. Открыть `routing.php` и/или `routing.backend.php`.
5. Найти action/controller/layout, который реально принимает запрос.
6. Найти model/service layer.
7. Найти templates: `templates/actions`, `templates/layouts`, `themes`.
8. Проверить rights config and runtime checks.
9. Проверить events and plugin integration.
10. Сравнить задачу с official pattern.
11. Только после этого писать skeleton or code.

ИИ-агенту запрещено:

- выбирать `shop` pattern только потому, что он самый полный;
- добавлять `themes/pages/my_account` без необходимости;
- придумывать app config flags;
- переносить `routing_params` между apps наугад;
- использовать frontend theme pattern для backend UI;
- проектировать plugin event без открытия места `wa()->event()`;
- утверждать совместимость с Webasyst, если системные классы и official examples не были проверены.

---

## 14. Мини-сводка

Официальные приложения Webasyst задают четыре базовых архитектурных ориентира:

```text
site → settlement/page/theme
blog → compact content app
shop → large commerce platform
crm  → backend-first/private frontend endpoints
```

Правильный workflow:

```text
задача
→ определить app pattern
→ открыть app.php
→ открыть routing
→ открыть action/controller/layout
→ открыть model/template/rights/events
→ выбрать минимальный Webasyst-compatible skeleton
```

Главная ошибка — копировать код из официального приложения без понимания, какой именно pattern он реализует.
