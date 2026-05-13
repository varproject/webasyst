/**
 * lk.ui.init.js — МОДУЛЬ: UI INITIALIZER
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
 * - Все модули lk.* (confirm, select2, boolSwitch)
 *
 * ПОДКЛЮЧЕНИЕ
 * - Скрипт подключается в шаблоне ПОСЛЕДНИМ.
 */
; (function (window, document, $) {
    'use strict';

    var lk = (window.lk = window.lk || {});
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

        // wa-apps\lk\js\components\lk.confirm.js
        if (lk.confirm && typeof lk.confirm.init === 'function') {
            lk.confirm.init();
        }

        // wa-apps\lk\js\components\lk.bool-switch.js
        if (lk.boolSwitch && typeof lk.boolSwitch.init === 'function') {
            lk.boolSwitch.init();
        }
    }

    function initDynamic(root) {

        // Select2 wa-apps\lk\js\components\lk.select2.js
        if (lk.select2 && typeof lk.select2.init === 'function') {
            schedule(function () {
                lk.select2.init(root || document);
            });
        }

        // SortableJS wa-apps\lk\js\components\lk.sortable.js
        if (lk.sortable && typeof lk.sortable.init === 'function') {
            lk.sortable.init(root || document);
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