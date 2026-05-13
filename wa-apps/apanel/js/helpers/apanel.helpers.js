/**
 * apanel.helpers.js — МОДУЛЬ: HELPERS
 *
 * НАЗНАЧЕНИЕ
 * - Глобальные утилиты и хуки, общие для всего приложения.
 * - Автоматическая вставка CSRF-токена в HTMX запросы.
 * - Генератор слага (транслитерация) для полей ввода.
 *
 * ПУБЛИЧНЫЙ API
 * - apanel.helpers.getCsrfToken() — получение CSRF токена.
 * - apanel.helpers.initSlugGenerator(src, target) — привязка генератора к инпутам.
 *
 * ПРАВИЛА
 * - Глобальная подписка на события (htmx:configRequest) происходит при загрузке скрипта.
 * - Функции утилит безопасны для многократного вызова.
 *
 * ЗАВИСИМОСТИ
 * - Нет (HTMX опционально — слушает события, если они есть).
 *
 * ПОДКЛЮЧЕНИЕ
 * - Скрипт подключается в шаблоне ПЕРЕД компонентами (т.к. они используют helpers).
 */
; (function (window, document) {
    'use strict';

    var apanel = (window.apanel = window.apanel || {});
    apanel.helpers = apanel.helpers || {};

    /**
     * Получает CSRF токен из всех доступных источников Webasyst
     * @returns {string}
     */
    apanel.helpers.getCsrfToken = function () {
        if (typeof window.wa_csrf === 'function') {
            return window.wa_csrf();
        }
        if (window.wa && window.wa.csrf) {
            return window.wa.csrf;
        }
        var match = document.cookie.match(new RegExp('(^|;)\\s*_csrf=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : '';
    };

    // =================================================================
    // 1. GLOBAL: Настройка HTMX (Автоматический CSRF)
    // =================================================================
    document.addEventListener('htmx:configRequest', function (evt) {
        if (!evt || !evt.detail) return;

        var verb = (evt.detail.verb || '').toLowerCase();
        if (verb === 'get') return;

        var csrfToken = apanel.helpers.getCsrfToken();
        if (!csrfToken) return;

        evt.detail.parameters = evt.detail.parameters || {};
        evt.detail.parameters['_csrf'] = csrfToken;
    });

    // =================================================================
    // 2. UTILITY: Генератор слага (Slug Generator)
    // =================================================================
    apanel.helpers.initSlugGenerator = function (sourceSelector, targetSelector) {
        var sourceInput = document.querySelector(sourceSelector);
        var targetInput = document.querySelector(targetSelector);

        if (!sourceInput || !targetInput) return;

        var isManuallyEdited = false;
        var map = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'j',
            'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
            'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c',
            'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya'
        };

        function generateSlug(text) {
            var val = String(text || '').toLowerCase();
            val = val.split('').map(function (char) {
                return map[char] || char;
            }).join('');
            val = val.replace(/[\s\-]+/g, '_');
            val = val.replace(/[^a-z0-9_]/g, '');
            val = val.replace(/_+/g, '_');
            val = val.replace(/^_/, '');
            return val;
        }

        // Кнопка (защита от дубля)
        if (!targetInput.parentNode.querySelector('.btn-slug-refresh')) {
            var wrapper = document.createElement('div');
            wrapper.className = 'input-group';
            targetInput.parentNode.insertBefore(wrapper, targetInput);
            wrapper.appendChild(targetInput);

            var btn = document.createElement('button');
            btn.className = 'btn btn-outline-secondary btn-slug-refresh';
            btn.type = 'button';
            btn.title = 'Сгенерировать из названия заново';
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';

            btn.addEventListener('click', function () {
                isManuallyEdited = false;
                targetInput.value = generateSlug(sourceInput.value);
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.style.transition = 'transform 0.5s';
                    icon.style.transform = 'rotate(180deg)';
                    setTimeout(function () { icon.style.transform = ''; }, 500);
                }
            });
            wrapper.appendChild(btn);
        }

        sourceInput.addEventListener('input', function () {
            if (!isManuallyEdited) targetInput.value = generateSlug(sourceInput.value);
        });
        targetInput.addEventListener('input', function () {
            isManuallyEdited = (this.value !== '');
        });
    };

})(window, document);