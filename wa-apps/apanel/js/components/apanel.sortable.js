/**
 * apanel.sortable.js — МОДУЛЬ: SORTABLE
 *
 * НАЗНАЧЕНИЕ
 * - Инициализирует SortableJS для списков с перетаскиванием.
 * - Работает по атрибуту: [data-sortable-url]
 * - Читает идентификаторы элементов из атрибута: [data-id]
 * - После изменения порядка отправляет новый список ID на сервер.
 * - При наличии data-sortable-update обновляет указанные DOM-блоки из HTML-ответа.
 * - После успешного сохранения подсвечивает перетащенный элемент.
 *
 * ПУБЛИЧНЫЙ API
 * - apanel.sortable.init([root]) — инициализация внутри контейнера root (по умолчанию document).
 *
 * МИНИМАЛЬНО ЧТО НУЖНО В ШАБЛОНЕ
 * - На контейнере должен быть атрибут: data-sortable-url="..."
 * - У каждого сортируемого элемента должен быть атрибут: data-id="..."
 * - Внутри сортируемого элемента должна быть ручка с классом: .js-sort-handle
 *
 * МИНИМАЛЬНЫЙ ПРИМЕР
 * - <ul data-sortable-url="?module=sort">
 * -     <li data-id="1">
 * -         <span class="js-sort-handle"></span>
 * -     </li>
 * -     <li data-id="2">
 * -         <span class="js-sort-handle"></span>
 * -     </li>
 * - </ul>
 *
 * ЧТО ОТПРАВЛЯЕТСЯ НА СЕРВЕР
 * - Отправляется POST-запрос на URL из data-sortable-url
 * - В запрос передаётся массив ID в порядке после сортировки
 * - По умолчанию имя массива: ids[]
 *
 * ТРЕБОВАНИЯ К КОНТРОЛЛЕРУ
 * - Контроллер должен принять POST-массив ids.
 * - Контроллер должен сохранить новый порядок элементов.
 * - Если в шаблоне указан data-sortable-update, контроллер должен вернуть HTML,
 *   в котором присутствуют блоки с теми же селекторами.
 * - Пример:
 * - data-sortable-update="#header_bot_left"
 * - значит в ответе сервера должен быть элемент с id="header_bot_left".
 *
 * КАК РАБОТАЕТ ОБНОВЛЕНИЕ БЛОКОВ
 * - Скрипт получает HTML-ответ сервера.
 * - Ищет в этом HTML блоки по селекторам из data-sortable-update.
 * - Заменяет соответствующие блоки в текущем DOM.
 * - Если после замены внутри блока есть htmx-атрибуты, скрипт повторно запускает их обработку через htmx.process().
 *
 * ПРАВИЛА
 * - init(root) идемпотентный.
 * - Один и тот же контейнер инициализируется только один раз.
 * - Должен вызываться повторно для нового контента.
 * - Если SortableJS не подключен, модуль тихо завершает работу.
 *
 * DATA-АТРИБУТЫ
 * - data-sortable-url="..."           — URL для сохранения порядка; обязательный атрибут
 * - data-sortable-update="#id1, #id2" — селекторы блоков для замены из HTML-ответа
 * - data-sortable-name="ids"          — имя массива ID; по умолчанию ids
 * - data-sortable-method="post"       — HTTP-метод; по умолчанию post
 * - data-sortable-handle=".selector"  — селектор ручки; по умолчанию .js-sort-handle
 * - data-sortable-draggable=".item"   — селектор сортируемых элементов; по умолчанию [data-id]
 * - data-sortable-animation="150"     — длительность анимации; по умолчанию 150
 *
 * ЧТО МОЖНО НЕ УКАЗЫВАТЬ
 * - data-sortable-name
 * - data-sortable-method
 * - data-sortable-handle
 * - data-sortable-draggable
 * - data-sortable-animation
 * - Эти атрибуты можно не писать, если подходят значения по умолчанию.
 *
 * ДЕФОЛТНОЕ ПОВЕДЕНИЕ
 * - Ручка перетаскивания: .js-sort-handle
 * - Сортируемые элементы: [data-id]
 * - Имя массива ID: ids
 * - Метод запроса: post
 * - Длительность анимации: 150
 *
 * ЗАВИСИМОСТИ
 * - SortableJS
 * - apanel.helpers (опционально, для getCsrfToken)
 * - htmx (опционально, только для повторной обработки заменённых блоков)
 *
 * ПОДКЛЮЧЕНИЕ
 * - Скрипт подключается в шаблоне после SortableJS.
 * - Запуск выполняется из js/backend/apanel.ui.init.js
 */
; (function (apanel, window, document) {
    'use strict';

    var sortableModule = apanel.sortable = apanel.sortable || {};
    var INIT_ATTR = 'data-sortable-inited';

    function getDs(el, key, fallback) {
        if (!el || !el.dataset) {
            return fallback;
        }

        var value = el.dataset[key];
        return (value === undefined || value === null || value === '') ? fallback : value;
    }

    function toInt(value, fallback) {
        var result = parseInt(value, 10);
        return isNaN(result) ? fallback : result;
    }

    function parseSelectors(value) {
        if (!value) {
            return [];
        }

        return value.split(',').map(function (selector) {
            return selector.trim();
        }).filter(function (selector) {
            return !!selector;
        });
    }

    function emit(container, name, detail) {
        if (!container) {
            return;
        }

        var event;
        if (typeof window.CustomEvent === 'function') {
            event = new CustomEvent(name, {
                bubbles: true,
                detail: detail || {}
            });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent(name, true, false, detail || {});
        }

        container.dispatchEvent(event);
    }

    function collectIds(container, draggable) {
        var items = container.querySelectorAll(draggable);
        var ids = [];
        var i;
        var id;

        for (i = 0; i < items.length; i++) {
            id = items[i].getAttribute('data-id');
            if (!id) {
                continue;
            }

            ids.push(id);
        }

        return ids;
    }

    function createResponseRoot(html) {
        var root = document.createElement('div');
        root.innerHTML = html;
        return root;
    }

    function replaceBlocksFromResponse(html, selectors) {
        var responseRoot = createResponseRoot(html);
        var replaced = 0;
        var i;
        var selector;
        var newEl;
        var oldEl;
        var insertedEl;

        if (!selectors.length) {
            return replaced;
        }

        for (i = 0; i < selectors.length; i++) {
            selector = selectors[i];
            newEl = responseRoot.querySelector(selector);
            oldEl = document.querySelector(selector);

            if (!newEl || !oldEl) {
                continue;
            }

            oldEl.outerHTML = newEl.outerHTML;
            insertedEl = document.querySelector(selector);

            if (insertedEl && window.htmx && typeof window.htmx.process === 'function') {
                window.htmx.process(insertedEl);
            }

            replaced++;
        }

        return replaced;
    }

    function flashSuccess(el) {
        if (!el) {
            return;
        }

        var oldTransition = el.style.transition;
        var oldBackground = el.style.backgroundColor;

        el.style.transition = 'background-color 180ms ease';
        el.style.backgroundColor = 'rgba(25, 135, 84, 0.22)';

        window.setTimeout(function () {
            el.style.transition = 'background-color 600ms ease';
            el.style.backgroundColor = 'transparent';

            window.setTimeout(function () {
                el.style.transition = oldTransition;
                el.style.backgroundColor = oldBackground;
            }, 650);
        }, 120);
    }

    function saveOrder(container, ids, item) {
        var url = getDs(container, 'sortableUrl', '');
        var method = getDs(container, 'sortableMethod', 'post').toUpperCase();
        var name = getDs(container, 'sortableName', 'ids');
        var updateSelectors = parseSelectors(getDs(container, 'sortableUpdate', ''));
        var csrf = (apanel.helpers && apanel.helpers.getCsrfToken)
            ? apanel.helpers.getCsrfToken()
            : '';
        var formData = new FormData();
        var i;

        if (!url || !ids.length) {
            return;
        }

        for (i = 0; i < ids.length; i++) {
            formData.append(name + '[]', ids[i]);
        }

        if (csrf) {
            formData.append('_csrf', csrf);
        }

        window.fetch(url, {
            method: method,
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Ошибка сохранения сортировки. HTTP ' + response.status);
            }

            return response.text();
        }).then(function (html) {
            var replaced = replaceBlocksFromResponse(html, updateSelectors);

            flashSuccess(item);

            emit(container, 'apanel:sortable:saved', {
                container: container,
                ids: ids,
                item: item,
                html: html,
                replaced: replaced,
                selectors: updateSelectors
            });
        }).catch(function (error) {
            emit(container, 'apanel:sortable:error', {
                container: container,
                ids: ids,
                item: item,
                error: error
            });

            if (window.console && typeof window.console.error === 'function') {
                console.error('Sortable save error:', error);
            }
        });
    }

    function initContainer(container) {
        if (!container || container.getAttribute(INIT_ATTR) === '1') {
            return;
        }

        var handle = getDs(container, 'sortableHandle', '.js-sort-handle');
        var draggable = getDs(container, 'sortableDraggable', '[data-id]');
        var animation = toInt(getDs(container, 'sortableAnimation', '150'), 150);

        container.setAttribute(INIT_ATTR, '1');

        window.Sortable.create(container, {
            animation: animation,
            handle: handle || undefined,
            draggable: draggable,
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 3,

            onEnd: function (evt) {
                var ids = collectIds(container, draggable);
                saveOrder(container, ids, evt.item || null);
            }
        });
    }

    sortableModule.init = function (root) {
        if (typeof window.Sortable === 'undefined' || !window.Sortable) {
            return;
        }

        var context = (root && root.querySelector) ? root : document;
        var containers = context.querySelectorAll('[data-sortable-url]');
        var i;

        for (i = 0; i < containers.length; i++) {
            initContainer(containers[i]);
        }
    };

})(window.apanel = window.apanel || {}, window, document);