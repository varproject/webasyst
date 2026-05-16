/**
 * apanel.select2.js — МОДУЛЬ: SELECT2
 *
 * НАЗНАЧЕНИЕ
 * - Инициализирует плагин Select2 с темой Bootstrap-5.
 * - Работает по селектору: .form-select.select2
 * - Корректно обрабатывает работу внутри модальных окон (dropdownParent).
 *
 * ПУБЛИЧНЫЙ API
 * - apanel.select2.init([root]) — инициализация внутри контейнера root (по умолчанию document).
 *
 * ПРАВИЛА
 * - init(root) идемпотентный:
 * - Проверяет класс .select2-hidden-accessible перед инициализацией.
 * - Должен вызываться повторно для нового контента (например, после htmx swap).
 *
 * ЗАВИСИМОСТИ
 * - jQuery
 * - Select2 Plugin (4.1.0+)
 *
 * ПОДКЛЮЧЕНИЕ
 * - Скрипт подключается в шаблоне.
 * - Запуск выполняется из js/backend/apanel.ui.init.js
 */
; (function (apanel, window, document, $) {
    'use strict';

    var select2Module = apanel.select2 = apanel.select2 || {};

    var language_ru = {
        errorLoading: function () { return 'Невозможно загрузить результаты'; },
        inputTooLong: function (args) { return 'Удалите ' + (args.input.length - args.maximum) + ' символ(ов)'; },
        inputTooShort: function (args) { return 'Введите ещё хотя бы ' + (args.minimum - args.input.length) + ' символ(ов)'; },
        loadingMore: function () { return 'Загрузка данных…'; },
        maximumSelected: function (args) { return 'Можно выбрать не более ' + args.maximum + ' элемент(ов)'; },
        noResults: function () { return 'Совпадений не найдено'; },
        searching: function () { return 'Поиск…'; },
        removeAllItems: function () { return 'Удалить все элементы'; }
    };

    select2Module.init = function (root) {
        if (!$ || typeof $.fn !== 'object' || typeof $.fn.select2 !== 'function') {
            return;
        }

        if (root && root.querySelector && !root.querySelector('.form-select.select2')) {
            return;
        }

        var $root = root ? $(root) : $(document);

        $root.find('.form-select.select2').each(function () {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            var el = $select[0];
            var $modal = $select.closest('.modal');
            var isMultiple = (el.dataset.type === 'multiple');
            var max = el.dataset.max ? parseInt(el.dataset.max, 10) : null;

            $select.select2({
                placeholder: isMultiple
                    ? 'Множественный выбор, до ' + max + ' элементов'
                    : 'Выберите значение',
                width: '100%',
                allowClear: true,
                dropdownParent: $modal.length ? $modal : $(document.body),
                theme: 'bootstrap-5',
                closeOnSelect: !isMultiple,
                maximumSelectionLength: max,
                language: language_ru
            });
        });
    };

})(window.apanel = window.apanel || {}, window, document, window.jQuery);