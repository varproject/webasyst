/**
 * apanel.ui.init.js — МОДУЛЬ: UI INITIALIZER
 *
 * НАЗНАЧЕНИЕ
 * - Единая точка входа для запуска JS-логики интерфейса.
 * - Управляет жизненным циклом компонентов при загрузке и htmx-навигации.
 *
 * ПУБЛИЧНЫЙ API
 * - Нет (скрипт исполняется автоматически при загрузке).
 *
 * ПРАВИЛА
 * - Разделяет инициализацию на Once (однократную) и Dynamic (многократную).
 * - Реагирует на события DOMContentLoaded и htmx:afterSwap.
 * - Использует requestAnimationFrame для тяжелых инициализаций (Select2).
 *
 * ЗАВИСИМОСТИ
 * - jQuery
 * - htmx (опционально, для реакции на swap)
 * - Все модули apanel.* (confirm, select2, boolSwitch)
 *
 * ПОДКЛЮЧЕНИЕ
 * - Скрипт подключается в шаблоне ПОСЛЕДНИМ.
 */
; (function (window, document, $) {
    'use strict';

    var apanel = (window.apanel = window.apanel || {});
    var inited_once = false;

    // Очередь для тяжелых задач (requestAnimationFrame)
    function schedule(fn) {
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                window.setTimeout(fn, 0);
            });
        } else {
            window.setTimeout(fn, 0);
        }
    }

    function initOnce() {
        if (inited_once) return;
        inited_once = true;

        // wa-apps\apanel\js\components\apanel.confirm.js
        if (apanel.confirm && typeof apanel.confirm.init === 'function') {
            apanel.confirm.init();
        }

        // wa-apps\apanel\js\components\apanel.bool-switch.js
        if (apanel.boolSwitch && typeof apanel.boolSwitch.init === 'function') {
            apanel.boolSwitch.init();
        }
    }

    function initDynamic(root) {

        // Select2 wa-apps\apanel\js\components\apanel.select2.js
        if (apanel.select2 && typeof apanel.select2.init === 'function') {
            schedule(function () {
                apanel.select2.init(root || document);
            });
        }

        // SortableJS wa-apps\apanel\js\components\apanel.sortable.js
        if (apanel.sortable && typeof apanel.sortable.init === 'function') {
            apanel.sortable.init(root || document);
        }

    }

    // 1) DOM Ready
    if ($ && typeof $.fn === 'object') {
        $(function () {
            initOnce();
            initDynamic(document);
        });
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            initOnce();
            initDynamic(document);
        });
    }

    // 2) HTMX After Swap
    document.addEventListener('htmx:afterSwap', function (e) {
        initDynamic(e.target || document);
    });

})(window, document, window.jQuery);