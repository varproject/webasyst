# 22. Checklists для разработки Webasyst

**Статус:** опубликован v3  
**Назначение:** дать короткие, прикладные чеклисты для проверки Webasyst-разработки перед commit, review или ответом ИИ-агента.

---

## 1. Назначение главы

Эта глава не заменяет глубокие разделы `01–21`. Она нужна как быстрый контроль качества.

Чеклист должен отвечать на вопрос:

```text
Что обязательно проверить, чтобы изменение не сломало Webasyst-flow?
```

Главный принцип:

```text
routing → class naming → request params → rights/CSRF → model/service → template → UI → assets → cache/update
```

---

## 2. Общий чеклист любой Webasyst-задачи

Перед тем как писать или принимать код, проверить:

### Контекст

- [ ] Понятно, это app, plugin, theme, widget, CLI или системная интеграция.
- [ ] Понятно, это backend, frontend, API/JSON, CLI или cron.
- [ ] Открыт текущий `app.php` или `plugin.php`.
- [ ] Открыт текущий routing-файл: `routing.php` или `routing.backend.php`.
- [ ] Открыты похожие существующие actions/controllers/models/templates в этом app/plugin.
- [ ] Проверена версия UI: `1.3`, `2.0` или `1.3,2.0`.
- [ ] Проверены права и CSRF для изменяющих действий.

### Naming

- [ ] Имена классов соответствуют Webasyst autoload.
- [ ] Имена файлов соответствуют suffix convention: `.action.php`, `.controller.php`, `.actions.php`, `.model.php`, `.class.php`, `.layout.php`.
- [ ] Module/action из route однозначно соответствуют классу.
- [ ] Template лежит в правильной директории.
- [ ] Нет случайных имён вроде `Manager`, `Processor`, `Handler`, если они не отражают точную ответственность.

### Безопасность

- [ ] Входные данные читаются через `waRequest`.
- [ ] Route placeholders читаются через `waRequest::param()`.
- [ ] GET читается через `waRequest::get()`.
- [ ] POST читается через `waRequest::post()`.
- [ ] У всех входных данных задан тип.
- [ ] Для POST/save/delete проверены права.
- [ ] Для POST есть CSRF.
- [ ] SQL использует `waModel`, helpers или placeholders.
- [ ] Пользовательские данные не пишутся в `wa-apps`.
- [ ] Redirect безопасен и не открывает open redirect.
- [ ] Вывод в Smarty экранирован.

### Архитектура

- [ ] Business logic не живёт в Smarty.
- [ ] SQL не живёт в Smarty.
- [ ] Action не превращён в god class.
- [ ] Модель не рендерит HTML.
- [ ] Template не принимает архитектурные решения.
- [ ] Новый service/helper создан только если логика повторяется или выходит за ответственность action/model.
- [ ] Нет лишних фабрик, интерфейсов и абстракций без причины.

### UI

- [ ] Backend UI соответствует заявленной версии app.
- [ ] UI 2.0 использует Webasyst classes, а не чужой CSS-framework как основу.
- [ ] Legacy UI 1.3 поддержан отдельно, если app/plugin работает в обоих режимах.
- [ ] Assets подключаются через `waResponse` или `$wa->css()` / `$wa->js()`.
- [ ] Нет hardcoded `/webasyst/`.

---

## 3. Создание приложения

### Файлы

- [ ] `wa-apps/{app_id}/lib/config/app.php`.
- [ ] `wa-apps/{app_id}/lib/config/routing.php`, если нужен frontend.
- [ ] `wa-apps/{app_id}/lib/config/routing.backend.php`, если нужны красивые backend URL.
- [ ] `wa-apps/{app_id}/lib/config/db.php`, если app создаёт таблицы.
- [ ] `wa-apps/{app_id}/lib/actions/...`.
- [ ] `wa-apps/{app_id}/templates/actions/...`.
- [ ] `wa-apps/{app_id}/templates/layouts/...`, если нужен layout.
- [ ] `wa-apps/{app_id}/lib/models/...`, если есть БД.

### `app.php`

- [ ] Указаны `name`, `icon`, `version`, `vendor`.
- [ ] Указан `frontend => true`, если app имеет frontend.
- [ ] Указан `plugins => true`, если app поддерживает plugins.
- [ ] Указан `themes => true`, если app поддерживает themes.
- [ ] Указан `pages => true`, если app поддерживает pages.
- [ ] Указан `rights => true`, если app имеет backend access control.
- [ ] Указан `csrf => true`, если app принимает POST.
- [ ] Указан `ui`.

### Проверка

- [ ] App открывается в backend.
- [ ] Frontend settlement работает, если app frontend.
- [ ] Нет ошибок autoload.
- [ ] Нет 404 из-за неправильного class/file naming.
- [ ] Первый install создаёт схему БД.

---

## 4. Создание backend route

### Routing

- [ ] Открыт `wa-apps/{app_id}/lib/config/routing.backend.php`.
- [ ] Route не конфликтует с существующими.
- [ ] Route value правильно превращается в `module/action`.
- [ ] Placeholder names понятны.
- [ ] Regex placeholders не слишком широкие.
- [ ] Есть fallback `'' => 'backend'` или осознанный другой fallback.

### Dispatch

- [ ] Понятно, какой класс будет искаться первым.
- [ ] Если route `'orders/<id:\d+>/' => 'orders/view'`, ожидаемые варианты:
  - `{app}OrdersViewController`;
  - `{app}OrdersViewAction`;
  - `{app}OrdersActions::viewAction()`.
- [ ] Файл лежит в `lib/actions/orders/`.
- [ ] Template лежит в `templates/actions/orders/`.

### Request

- [ ] `id` читается через `waRequest::param('id', 0, waRequest::TYPE_INT)`.
- [ ] GET filters читаются через `waRequest::get()`.
- [ ] POST data читается через `waRequest::post()`.

### Безопасность

- [ ] Изменяющий route не выполнится без прав.
- [ ] POST/delete/save не работает через GET.
- [ ] Есть CSRF.
- [ ] Redirect строится через `wa()->getAppUrl()` или route helper.

---

## 5. Создание frontend route

### Routing

- [ ] Открыт `wa-config/routing.php` или известен settlement.
- [ ] Открыт `wa-apps/{app_id}/lib/config/routing.php`.
- [ ] App имеет `frontend => true`.
- [ ] Route расположен внутри app URL, а не пытается заменить global routing.
- [ ] Route params будут доступны через `waRequest::param()`.

### Theme

- [ ] Если route рендерит страницу сайта, используется `setThemeTemplate()`.
- [ ] Template существует в текущей теме.
- [ ] Для theme settings используется `$theme_settings`.
- [ ] URL строится через `wa()->getRouteUrl()` или `$wa->getUrl()`.

### Security

- [ ] Secure route помечен `secure => true`, если требуется auth.
- [ ] POST на secure frontend route защищён CSRF.
- [ ] Пользовательский slug проверяется.
- [ ] 404 отдаётся через `waException(..., 404)` или response status.

---

## 6. Создание plugin

### Структура

- [ ] `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/plugin.php`.
- [ ] `wa-apps/{app_id}/plugins/{plugin_id}/lib/{app}{Plugin}Plugin.plugin.php`.
- [ ] `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/settings.php`, если нужны настройки.
- [ ] `wa-apps/{app_id}/plugins/{plugin_id}/lib/actions/...`, если есть backend UI.
- [ ] `wa-apps/{app_id}/plugins/{plugin_id}/templates/actions/...`, если есть backend templates.
- [ ] `wa-apps/{app_id}/plugins/{plugin_id}/lib/config/routing.php`, если plugin добавляет frontend routes.
- [ ] `db.php`, `install.php`, `uninstall.php`, `lib/updates/*.php`, если plugin создаёт БД или миграции.

### `plugin.php`

- [ ] Указаны `name`, `description`, `vendor`, `version`.
- [ ] Указаны `handlers`, если plugin слушает events.
- [ ] Указан settings flag app-specific формата, если нужен settings UI.
- [ ] Указан `rights => true`, если plugin имеет отдельные права.

### Main class

- [ ] Класс называется `{app}{Plugin}Plugin`.
- [ ] Класс наследует app-specific plugin base или `waPlugin`.
- [ ] Event handler methods существуют.
- [ ] `getSettings()` используется вместо прямого чтения `wa_app_settings`.
- [ ] `saveSettings()` нормализует данные до сохранения.

### Проверка

- [ ] Plugin включается/выключается без fatal errors.
- [ ] При uninstall удаляются settings и plugin rights.
- [ ] Plugin не ломает app без включённых settings.
- [ ] Assets подключаются через `addJs()` / `addCss()`.

---

## 7. Создание settings page

### App settings

- [ ] Понятно, это runtime settings, user config или route/system config.
- [ ] Runtime settings хранятся через `waAppSettingsModel`.
- [ ] Static options живут в `lib/config/config.php`.
- [ ] User-overrides живут в `wa-config/apps/{app_id}/config.php`.

### Plugin settings

- [ ] Settings описаны в `lib/config/settings.php`.
- [ ] Используются `control_type`, `title`, `description`, `value`.
- [ ] Arrays сохраняются через `waPlugin::saveSettings()`.
- [ ] Custom controls зарегистрированы через `waHtmlControl::registerControl()`.
- [ ] Uploaded files сохраняются в `wa-data`, не в plugin directory.

### Security

- [ ] Settings save проверяет права.
- [ ] Settings save работает через POST.
- [ ] Есть CSRF.
- [ ] Settings values нормализованы.

---

## 8. Создание model/table

### Model

- [ ] Model class называется `{app}{Entity}Model`.
- [ ] File: `lib/models/{app}{Entity}.model.php`.
- [ ] Model наследует `waModel` или app-specific base model.
- [ ] Указан `$table`.
- [ ] Указан `$id`, если primary key не `id`.
- [ ] Все DB operations находятся в model/service, не в template.

### Table schema

- [ ] Таблица описана в `lib/config/db.php`.
- [ ] Имя таблицы начинается с app/plugin prefix.
- [ ] Есть primary key.
- [ ] Есть индексы под частые выборки.
- [ ] Есть update migration, если таблица добавляется в существующий app/plugin.
- [ ] После изменения схемы очищается metadata cache, если нужно.

### SQL

- [ ] Используются `insert`, `updateById`, `deleteById`, `getById`, `getByField`, если достаточно.
- [ ] `query()` используется, если нужен result.
- [ ] `exec()` используется для запросов без чтения result.
- [ ] Используются placeholders.
- [ ] Нет конкатенации request data в SQL.

---

## 9. Создание UI 2.0 backend page

### App/UI

- [ ] В `app.php` указано `ui => '2.0'` или `ui => '1.3,2.0'`.
- [ ] Проверено `wa()->whichUI()`.
- [ ] Template лежит в `templates/actions/...`.
- [ ] Для legacy при необходимости есть `templates/actions-legacy/...`.

### Markup

- [ ] Используются Webasyst UI classes: `.article`, `.box`, `.fields`, `.tablebox`, `.menu`, `.tabs`, `.button`.
- [ ] Не подключён чужой CSS-framework как основа backend UI.
- [ ] Есть адаптивность.
- [ ] Dark mode не ломается.
- [ ] Empty/loading/error states предусмотрены.

### Forms

- [ ] Все POST forms имеют `{$wa->csrf()}`.
- [ ] Save action проверяет права.
- [ ] Errors отображаются рядом с полями.
- [ ] Success feedback есть.

---

## 10. Security review

### Request

- [ ] Каждый входной параметр имеет источник: `param`, `get`, `post`, `request`, `server`, `cookie`.
- [ ] Каждый входной параметр типизирован.
- [ ] Не используется `waRequest::request()` там, где источник должен быть строго GET или POST.
- [ ] File uploads читаются через `waRequest::file()`.

### Access

- [ ] Есть app-level rights.
- [ ] Есть object-level rights, если объект принадлежит пользователю/проекту/разделу.
- [ ] Save/delete недоступны без прав.
- [ ] UI hiding не считается защитой.

### CSRF

- [ ] App/plugin имеет `csrf => true`, если принимает POST.
- [ ] В формах есть `{$wa->csrf()}`.
- [ ] AJAX/htmx POST передаёт CSRF.
- [ ] JSON endpoints не обходят CSRF без причины.

### Output

- [ ] HTML output escaped.
- [ ] `nofilter` используется только для доверенного HTML.
- [ ] User input не выводится напрямую.
- [ ] JSON строится через системный encode.

### Files

- [ ] Uploads пишутся в `wa-data`.
- [ ] Template/file path не берётся напрямую из request.
- [ ] Нет `../` path traversal.
- [ ] Расширение файла проверяется.
- [ ] Public/protected data path выбран осознанно.

---

## 11. Store publication review

- [ ] Есть корректный `vendor`.
- [ ] Есть `version`.
- [ ] Есть `critical`, если требуется.
- [ ] Нет debug output.
- [ ] Нет hardcoded domains/URLs.
- [ ] Нет hardcoded `/webasyst/`.
- [ ] Нет пользовательских данных в `wa-apps`.
- [ ] Нет прямого доступа к закрытым файлам.
- [ ] Нет SQL injection.
- [ ] Нет XSS.
- [ ] Есть install/update/uninstall для БД.
- [ ] Нет зависимости от локального окружения.
- [ ] Localization strings используются корректно.
- [ ] UI соответствует заявленной версии.
- [ ] Rights и CSRF проверены.

---

## 12. AI answer review

Перед финальным ответом ИИ-агент должен проверить:

### Sources

- [ ] Открыты файлы проекта из GitHub.
- [ ] Открыта загруженная документация или соответствующая глава v3.
- [ ] Открыт официальный пример, если задача затрагивает системный механизм.
- [ ] Если файл не открыт, не заявлять, что решение проверено.

### Specificity

- [ ] Ответ привязан к конкретному app/plugin/theme.
- [ ] Указаны точные paths.
- [ ] Указаны точные class names.
- [ ] Указаны exact route params.
- [ ] Указаны templates.

### Code

- [ ] Код можно вставить без догадок.
- [ ] Код не содержит несуществующих Webasyst methods.
- [ ] Код не использует Laravel/Symfony/WordPress-подход вместо Webasyst.
- [ ] Код не содержит лишнюю архитектуру.
- [ ] Код соответствует стилю Webasyst.
- [ ] В PHP-methods нет блочных комментариев без необходимости.
- [ ] Комментарии объясняют причину, а не очевидное действие.

### Alternatives

- [ ] Если есть несколько способов, указаны плюсы и минусы.
- [ ] Рекомендован один основной вариант.
- [ ] Риски названы явно.
- [ ] Неуверенность названа явно.

---

## 13. Быстрый чеклист перед commit

```text
1. app.php/plugin.php проверен.
2. routing.php/routing.backend.php проверен.
3. module/action → class совпадает.
4. file naming совпадает.
5. template path совпадает.
6. request params типизированы.
7. rights проверены.
8. CSRF есть.
9. SQL безопасен.
10. output escaped.
11. URL не hardcoded.
12. UI version соблюдена.
13. install/update/db проверены.
14. cache/metadata учтены.
15. похожий официальный pattern не нарушен.
```

---

## 14. Быстрый чеклист ИИ-агента

```text
1. Определи тип задачи.
2. Открой app.php/plugin.php/theme.xml.
3. Открой routing.
4. Открой существующий controller/action/layout.
5. Открой template.
6. Открой model/service.
7. Проверь official pattern.
8. Проверь naming.
9. Проверь rights.
10. Проверь CSRF.
11. Проверь UI version.
12. Проверь security.
13. Только потом пиши решение.
```

---

## 15. Мини-сводка

Чеклист в Webasyst нужен не для формальности. Он защищает от типичных ошибок:

```text
не тот route
→ не тот module/action
→ не найден class
→ не найден template
→ данные прочитаны не из того источника
→ нет прав/CSRF
→ SQL или XSS
→ UI не соответствует версии
```

Правильная Webasyst-разработка всегда начинается с проверки текущего app/plugin/theme и заканчивается проверкой dispatch, security и UI.
