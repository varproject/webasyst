# 00. Webasyst Documentation for AI v3 — карта документации

**Статус:** точка входа v3  
**Назначение:** быстро определить, какой файл открыть под конкретную задачу разработки Webasyst.

---

## 1. Назначение документации v3

Эта документация предназначена для разработчиков и ИИ-агентов, которые пишут, проверяют или сопровождают код Webasyst.

Формат v3 отличается от обзорной документации:

```text
не “что такое Webasyst”
а “как конкретный механизм работает в файлах, классах, routing, templates и runtime”
```

Каждая глава раскрывает механизм по схеме:

```text
назначение
→ файлы
→ системная цепочка выполнения
→ классы и методы
→ параметры config/routing/request
→ официальный pattern
→ skeleton
→ типовые ошибки
→ чеклист разработчика
→ чеклист ИИ-агента
```

---

## 2. Быстрый старт

### Если задача про routing

Открыть:

1. `01-core-lifecycle.md`
2. `04-frontend-routing.md` или `05-backend-routing.md`
3. `06-controllers-actions.md`
4. `02-file-structure-and-naming.md`
5. `22-checklists.md`

### Если задача про action/controller/template

Открыть:

1. `06-controllers-actions.md`
2. `07-layouts-and-blocks.md`
3. `08-smarty-templates-themes.md`
4. `02-file-structure-and-naming.md`
5. `15-security.md`

### Если задача про БД

Открыть:

1. `09-models-database.md`
2. `15-security.md`
3. `21-skeletons.md`
4. `22-checklists.md`

### Если задача про plugin

Открыть:

1. `11-plugins-architecture.md`
2. `12-plugin-helpers.md`
3. `13-events-hooks.md`
4. `10-settings-config.md`
5. `14-rights-access.md`
6. `15-security.md`

### Если задача про UI 2.0

Открыть:

1. `18-webasyst-2-ui-kit.md`
2. `07-layouts-and-blocks.md`
3. `08-smarty-templates-themes.md`
4. `15-security.md`
5. `22-checklists.md`

### Если задача про тему дизайна

Открыть:

1. `17-theme-pattern.md`
2. `08-smarty-templates-themes.md`
3. `04-frontend-routing.md`
4. `15-security.md`

### Если задача для ИИ-агента

Открыть:

1. `20-ai-agent-rules.md`
2. профильную главу по механизму
3. `22-checklists.md`

---

## 3. Порядок чтения документации

### Базовый маршрут

Для нового разработчика:

```text
01-core-lifecycle.md
02-file-structure-and-naming.md
03-application-config.md
04-frontend-routing.md
05-backend-routing.md
06-controllers-actions.md
07-layouts-and-blocks.md
08-smarty-templates-themes.md
09-models-database.md
15-security.md
22-checklists.md
```

### Маршрут для backend-разработки

```text
01-core-lifecycle.md
02-file-structure-and-naming.md
03-application-config.md
05-backend-routing.md
06-controllers-actions.md
07-layouts-and-blocks.md
08-smarty-templates-themes.md
09-models-database.md
10-settings-config.md
14-rights-access.md
15-security.md
16-json-ajax-long-cli.md
18-webasyst-2-ui-kit.md
22-checklists.md
```

### Маршрут для frontend/theme-разработки

```text
01-core-lifecycle.md
04-frontend-routing.md
06-controllers-actions.md
07-layouts-and-blocks.md
08-smarty-templates-themes.md
09-models-database.md
13-events-hooks.md
15-security.md
17-theme-pattern.md
22-checklists.md
```

### Маршрут для plugin-разработки

```text
02-file-structure-and-naming.md
03-application-config.md
04-frontend-routing.md
05-backend-routing.md
06-controllers-actions.md
09-models-database.md
10-settings-config.md
11-plugins-architecture.md
12-plugin-helpers.md
13-events-hooks.md
14-rights-access.md
15-security.md
16-json-ajax-long-cli.md
22-checklists.md
```

### Маршрут для ИИ-агента

```text
20-ai-agent-rules.md
01-core-lifecycle.md
02-file-structure-and-naming.md
04-frontend-routing.md
05-backend-routing.md
06-controllers-actions.md
15-security.md
22-checklists.md
```

---

## 4. Карта файлов

| Файл | Назначение |
|---|---|
| `00-index.md` | Карта документации и быстрые маршруты чтения. |
| `01-core-lifecycle.md` | Жизненный цикл Webasyst-запроса: entrypoint, env, dispatch, routing, app initialization. |
| `02-file-structure-and-naming.md` | Структура файлов и naming, от которых зависит autoload, dispatch и template lookup. |
| `03-application-config.md` | `lib/config/app.php` как декларация возможностей приложения. |
| `04-frontend-routing.md` | Frontend routing: settlements, app routes, placeholders, `waRequest::param()`, URL generation. |
| `05-backend-routing.md` | Backend routing: old GET dispatch, `routing.backend.php`, поиск классов. |
| `06-controllers-actions.md` | `waController`, `waViewAction`, `waViewController`, `waViewActions`, `waDefaultViewController`. |
| `07-layouts-and-blocks.md` | `waLayout`, blocks, `executeAction()`, frontend/backend layouts. |
| `08-smarty-templates-themes.md` | Smarty templates, app templates, theme templates, plugin templates, `$wa`. |
| `09-models-database.md` | `waModel`, CRUD, query/exec, placeholders, schema, updates. |
| `10-settings-config.md` | App config, user config, `waAppSettingsModel`, plugin settings. |
| `11-plugins-architecture.md` | Plugin structure, lifecycle, handlers, settings, routing, backend actions. |
| `12-plugin-helpers.md` | `waPluginViewHelper`, app helpers, safe Smarty calls. |
| `13-events-hooks.md` | Events/hooks, plugin handlers, return formats, examples. |
| `14-rights-access.md` | `waRightConfig`, app/plugin rights, runtime checks. |
| `15-security.md` | Request typing, CSRF, XSS, SQL injection, upload, redirects, paths. |
| `16-json-ajax-long-cli.md` | JSON controllers, AJAX/partials, long actions, CLI, cron. |
| `17-theme-pattern.md` | Themes, `theme.xml`, theme settings, parent themes, template flow. |
| `18-webasyst-2-ui-kit.md` | Webasyst 2 UI-KIT for backend pages and migration from UI 1.3. |
| `19-official-app-patterns.md` | Patterns of `site`, `blog`, `shop`, `crm`. |
| `20-ai-agent-rules.md` | Mandatory workflow and prohibitions for AI agents. |
| `21-skeletons.md` | Ready skeletons for app/plugin/theme/model/controller/JSON/CLI/UI. |
| `22-checklists.md` | Checklists for development, security, review, publication and AI answers. |

---

## 5. Как выбрать главу под задачу

### Создать приложение

Открыть:

```text
01-core-lifecycle.md
02-file-structure-and-naming.md
03-application-config.md
21-skeletons.md
22-checklists.md
```

### Добавить backend страницу

Открыть:

```text
05-backend-routing.md
06-controllers-actions.md
07-layouts-and-blocks.md
08-smarty-templates-themes.md
14-rights-access.md
15-security.md
18-webasyst-2-ui-kit.md
```

### Добавить frontend страницу

Открыть:

```text
04-frontend-routing.md
06-controllers-actions.md
07-layouts-and-blocks.md
08-smarty-templates-themes.md
17-theme-pattern.md
15-security.md
```

### Добавить JSON endpoint

Открыть:

```text
05-backend-routing.md
06-controllers-actions.md
15-security.md
16-json-ajax-long-cli.md
21-skeletons.md
```

### Добавить HTML partial для htmx/AJAX

Открыть:

```text
06-controllers-actions.md
08-smarty-templates-themes.md
15-security.md
16-json-ajax-long-cli.md
18-webasyst-2-ui-kit.md
```

### Добавить модель и таблицу

Открыть:

```text
02-file-structure-and-naming.md
09-models-database.md
15-security.md
21-skeletons.md
22-checklists.md
```

### Добавить plugin

Открыть:

```text
11-plugins-architecture.md
12-plugin-helpers.md
13-events-hooks.md
14-rights-access.md
15-security.md
21-skeletons.md
22-checklists.md
```

### Добавить настройки plugin-а

Открыть:

```text
10-settings-config.md
11-plugins-architecture.md
15-security.md
21-skeletons.md
```

### Добавить hook/event

Открыть:

```text
13-events-hooks.md
11-plugins-architecture.md
15-security.md
```

### Добавить права

Открыть:

```text
03-application-config.md
14-rights-access.md
15-security.md
22-checklists.md
```

### Изменить тему дизайна

Открыть:

```text
17-theme-pattern.md
08-smarty-templates-themes.md
04-frontend-routing.md
15-security.md
```

### Перевести backend page на UI 2.0

Открыть:

```text
18-webasyst-2-ui-kit.md
07-layouts-and-blocks.md
08-smarty-templates-themes.md
14-rights-access.md
15-security.md
```

---

## 6. Минимальный workflow разработки

Для любой задачи:

```text
1. Определить тип задачи.
2. Открыть app.php/plugin.php/theme.xml.
3. Открыть routing.php или routing.backend.php.
4. Определить module/action/plugin params.
5. Открыть текущий controller/action/actions.
6. Открыть model/service.
7. Открыть template/layout.
8. Проверить official app pattern.
9. Проверить naming.
10. Проверить rights/security/CSRF.
11. Проверить UI version.
12. Только потом писать код.
```

---

## 7. Минимальный workflow ИИ-агента

ИИ-агент не должен начинать с генерации кода.

Правильный порядок:

```text
1. Найти текущую часть проекта.
2. Открыть реальные файлы.
3. Сопоставить задачу с главой v3.
4. Проверить официальный Webasyst pattern.
5. Проверить naming и dispatch.
6. Проверить права и CSRF.
7. Проверить template/UI.
8. Назвать допущения.
9. Дать решение.
10. Назвать риски.
```

---

## 8. Главные запреты

Запрещено:

- придумывать API Webasyst;
- писать routing без объяснения, какой класс будет вызван;
- читать route placeholders через `waRequest::get()`;
- хардкодить `/webasyst/`;
- писать PHP-код в Smarty;
- писать SQL в template;
- писать пользовательские файлы в `wa-apps`;
- создавать собственный CSRF вместо Webasyst CSRF;
- заменять Webasyst routing на Laravel/Symfony-style routing;
- использовать чужой CSS-framework как основу backend UI 2.0;
- игнорировать `ui => '1.3,2.0'`;
- делать plugin settings напрямую через SQL, если есть `waPlugin::getSettings()` / `saveSettings()`;
- считать UI hiding проверкой прав;
- отвечать “проверено по проекту”, если файлы не были открыты.

---

## 9. Главная цепочка Webasyst

Для понимания любой задачи держать в голове цепочку:

```text
HTTP/CLI entrypoint
→ SystemConfig / waSystemConfig
→ waSystem
→ waDispatch
→ global routing или backend app detection
→ waAppConfig
→ app routing
→ waFrontController
→ module/action/plugin/widget
→ Controller / Action / Actions
→ Model / Service
→ View / Smarty
→ Layout / Theme
→ Response
```

Если задача ломает эту цепочку, решение почти наверняка неверное.

---

## 10. Мини-сводка

`00-index.md` — это навигационная карта.  
Глубокие правила находятся в профильных главах.  
Быстрая проверка — в `22-checklists.md`.  
Практические шаблоны — в `21-skeletons.md`.  
Для ИИ-агента обязательная глава — `20-ai-agent-rules.md`.

