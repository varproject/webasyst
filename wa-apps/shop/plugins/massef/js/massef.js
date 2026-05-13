/*!
 * massef-ui.bootstrap.js — Инициализация UI диалога массового редактирования характеристик (Webasyst Shop-Script)
 *
 * Что делает:
 *   • Ждёт готовности DOM и наличия jQuery; инициализируется при появлении диалога #js-massef-dialog
 *     (через событие wa_dialog_ready и/или MutationObserver onAppear).
 *   • Ведёт «черновик» (draft) изменений в localStorage, ключ: massef_<products_hash>_draft.
 *     Структура: { features: { <code>: {value, unit, touched, changed, clear} }, counter:{all-field,changed,not-changed} }.
 *   • Подсчитывает и показывает счётчики: «Изменено: N из M характеристик, для X товар/товара/товаров»
 *     (корректные окончания слова «товар»).
 *   • Управляет состояниями полей:
 *       – помечает .field классами .changed и .to-clear,
 *       – при включённом «удалить значение» (.js-field-clear-switch) блокирует инпуты значения, оставляя только переключатель.
 *   • Поиск и фильтрация:
 *       – фильтр: Все / Будут обновлены / Не затронуто,
 *       – поиск по имени и коду характеристики с подсветкой <mark>,
 *       – счётчики возле радиокнопок и индикатор результата поиска.
 *   • События полей (делегирование на .fields):
 *       – input[type="text"], textarea — фиксируются по blur;
 *       – select (кроме .unit), radio, checkbox — по change;
 *       – .js-field-clear-switch (удалить значение) — по change (без потери текущего флага changed);
 *       – select.unit — по change (обновляет unit без автопометки changed).
 *   • При открытии диалога очищает все старые черновики massef_*_draft, сбрасывает UI и кеширует исходные заголовки
 *     для корректной де-подсветки (restore из data('orig-html')).
 *   • На submit валидирует наличие изменений; добавляет скрытые поля clear[code]=1 для отмеченных «удалить значение».
 *
 * Точки интеграции/DOM:
 *   #js-massef-dialog, #js-massef-dialog-form, .fields .field,
 *   .name[data-field-name], .hint[data-field-code],
 *   .js-field-clear-switch, select.unit,
 *   #changed-counter, #js-massef-search, #js-massef-search-clear, #js-massef-search-counter,
 *   [name="show-filter"] (all/changed/unchanged),
 *   data-атрибуты диалога: data-products-hash, data-features-total, data-products-count.
 *
 * Требования:
 *   • jQuery (делегирование событий), событие wa_dialog_ready,
 *   • браузер с поддержкой localStorage и MutationObserver,
 *   • верстка диалога согласно указанным селекторам.
 *
 * Взаимодействие:
 *   • Скрипт независим от massef-progress.js; отвечает за UX/черновик и корректную подготовку данных формы.
 *     Батч-скрипт обрабатывает отправку, прогресс и post-completion логику.
 *
 * Автор: Petrosian Vagram
 * Дата: 2025-11-02
 */

(function bootstrap() {
    function onDOMReady(fn) {
        if (document.readyState === 'interactive' || document.readyState === 'complete') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        }
    }
    function withJQuery(fn) {
        if (window.jQuery) return fn(window.jQuery);
        var attempts = 0;
        (function tick() {
            if (window.jQuery) return fn(window.jQuery);
            if (attempts++ < 400) return setTimeout(tick, 25); // до ~10с
        })();
    }

    onDOMReady(function () {
        withJQuery(function ($) {

            // Ловим появление #js-massef-dialog
            function onAppear(selector, cb) {
                var $el = $(selector);
                if ($el.length) cb($el.eq(0));
                var root = document.body || document.documentElement;
                var mo = new MutationObserver(function (mutations) {
                    for (var i = 0; i < mutations.length; i++) {
                        var added = mutations[i].addedNodes;
                        for (var j = 0; j < added.length; j++) {
                            var n = added[j];
                            if (n.nodeType !== 1) continue;
                            if (n.matches && n.matches(selector)) { cb($(n)); return; }
                            if (n.querySelector) {
                                var found = n.querySelector(selector);
                                if (found) { cb($(found)); return; }
                            }
                        }
                    }
                });
                mo.observe(root, { childList: true, subtree: true });
            }

            $(document).on('wa_dialog_ready', '#js-massef-dialog', function () {
                initDialog($(this));
            });

            var $maybe = $('#js-massef-dialog');
            if ($maybe.length) { initDialog($maybe.eq(0)); } else { onAppear('#js-massef-dialog', initDialog); }

            // ---------------- ИНИЦИАЛИЗАЦИЯ КОНКРЕТНОГО ДИАЛОГА ----------------
            function initDialog($dlg) {
                if (!$dlg || !$dlg.length) return;
                if ($dlg.data('massef-init') === 1) return;
                $dlg.data('massef-init', 1);

                var products_hash = $dlg.data('products-hash');
                var features_total = parseInt($dlg.data('features-total'), 10) || 0;
                var products_count = parseInt($dlg.data('products-count'), 10) || 0;
                var DRAFT_KEY = 'massef_' + products_hash + '_draft';

                // --- склонение слова "товар" ---
                var TOVAR_FORMS = ['товара', 'товаров', 'товаров'];
                function pluralize(number, forms, withNumber) {
                    var arr = Array.isArray(forms) ? forms.slice() : String(forms).split(',');
                    arr = arr.map(function (s) { return String(s).trim(); });
                    while (arr.length < 3) arr.push(arr[arr.length - 1] || '');
                    var n = Math.abs(parseInt(number, 10)) || 0;
                    var n100 = n % 100, n10 = n % 10, word;
                    if (n100 >= 11 && n100 <= 14) word = arr[2];
                    else if (n10 === 1) word = arr[0];
                    else if (n10 >= 2 && n10 <= 4) word = arr[1];
                    else word = arr[2];
                    return (withNumber === false) ? word : (number + ' ' + word);
                }

                // --- draft ---
                function getDraft() {
                    var raw = localStorage.getItem(DRAFT_KEY);
                    if (raw) return JSON.parse(raw);
                    return { features: {}, counter: { "all-field": features_total, "changed": 0, "not-changed": features_total } };
                }
                function countDraft(draft) {
                    var changed = 0;
                    for (var code in draft.features) {
                        if (!draft.features.hasOwnProperty(code)) continue;
                        if (draft.features[code].touched) changed++;
                    }
                    var notChanged = features_total - changed;
                    return { "all-field": features_total, "changed": changed, "not-changed": (notChanged >= 0 ? notChanged : 0) };
                }
                function setDraft(draft) { draft.counter = countDraft(draft); localStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); }
                function removeDraft() { localStorage.removeItem(DRAFT_KEY); }
                function purgeAllMassefDrafts() {
                    try {
                        var del = [];
                        for (var i = 0; i < localStorage.length; i++) {
                            var k = localStorage.key(i);
                            if (k && k.indexOf('massef_') === 0 && k.endsWith('_draft')) del.push(k);
                        }
                        del.forEach(function (k) { localStorage.removeItem(k); });
                    } catch (e) { }
                }

                // --- helpers ---
                function isEqual(val1, val2) {
                    if ((val1 === "" || val1 === null || typeof val1 === 'undefined') &&
                        (val2 === "" || val2 === null || typeof val2 === 'undefined')) return true;
                    if (typeof val1 !== typeof val2) return false;
                    if (Array.isArray(val1) && Array.isArray(val2)) {
                        if (val1.length !== val2.length) return false;
                        for (var i = 0; i < val1.length; i++) if (val1[i] != val2[i]) return false;
                        return true;
                    }
                    if (typeof val1 === 'object' && val1 !== null && val2 !== null) {
                        var k1 = Object.keys(val1), k2 = Object.keys(val2);
                        if (k1.length !== k2.length) return false;
                        for (var k in val1) if (!isEqual(val1[k], val2[k])) return false;
                        return true;
                    }
                    return val1 == val2;
                }
                function getInitialValue($field, code) {
                    if ($field.find('input[name^="features[' + code + '_from"]').length ||
                        $field.find('input[name^="features[' + code + '_to"]').length) {
                        return { from: '', to: '' };
                    } else if ($field.find('input[type="checkbox"]:not(.js-field-clear-switch)').length) {
                        return [];
                    } else if ($field.find('input[type="radio"]').length) {
                        return '';
                    } else if ($field.find('select').length) {
                        return '';
                    } else {
                        return '';
                    }
                }
                function getFieldCode($field) {
                    var name = $field.find('[name^="features["]').not('[name$="_unit]"]').attr('name');
                    var m = name && name.match(/\[(.*?)\]/);
                    return m ? m[1].replace(/(_from|_to)$/, '') : null;
                }
                function updateDraftField(code, value, clear, $field, opts) {
                    opts = opts || {};
                    var draft = getDraft();
                    var prev = draft.features[code] || {};
                    var initialValue = getInitialValue($field, code);

                    var changedCalculated = !isEqual(value, initialValue);
                    var changed = (opts.fromClearToggle && prev.hasOwnProperty('changed')) ? prev.changed : changedCalculated;

                    clear = !!clear;
                    var touched = changed || clear;

                    if (touched) {
                        var unit = (typeof opts.unit !== 'undefined') ? opts.unit : (prev.unit || '');
                        draft.features[code] = { value: value, unit: unit, touched: true, changed: changed, clear: clear };
                    } else {
                        if (draft.features.hasOwnProperty(code)) delete draft.features[code];
                    }
                    setDraft(draft);
                }

                // --- состояние полей ---
                function updateAllFieldsState() {
                    var draft = getDraft();
                    $dlg.find('.fields .field').each(function () {
                        var $field = $(this);
                        var code = getFieldCode($field);
                        $field.removeClass('changed to-clear');
                        var feature = draft.features[code];

                        var $inputs = $field.find('input, select, textarea').not('.js-field-clear-switch');
                        if (feature && feature.clear) {
                            $field.addClass('changed to-clear'); $inputs.prop('disabled', true);
                        } else if (feature && feature.changed) {
                            $field.addClass('changed'); $inputs.prop('disabled', false);
                        } else {
                            $inputs.prop('disabled', false);
                        }
                    });
                }

                // --- поиск/подсветка/фильтры ---
                function clearHighlights($el) { if ($el.data('orig-html')) $el.html($el.data('orig-html')); }
                function highlightText($el, s) {
                    if (!s) return;
                    clearHighlights($el);
                    $el.contents().filter(function () { return this.nodeType === 3; }).each(function () {
                        var re = new RegExp("(" + s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "gi");
                        var replaced = $(this).text().replace(re, '<mark>$1</mark>');
                        $(this).replaceWith(replaced);
                    });
                }
                function updateCountersAndFilters() {
                    var draft = getDraft();

                    // Изменено: N из M характеристик, для X товаров.  → корректное окончание для "товар"
                    var productsWord = pluralize(products_count, TOVAR_FORMS, false);
                    $dlg.find('#changed-counter').html(
                        'Изменено: <b class="changed">' + draft.counter.changed +
                        '</b> из <span class="features-total">' + draft.counter["all-field"] +
                        '</span> характеристик, для <b class="products-count">' + products_count +
                        '</b> ' + productsWord + '.'
                    );

                    var fmt = function (n) { return '(' + n + ')'; };
                    $dlg.find('[data-filter-count="all"]').text(fmt(draft.counter["all-field"]));
                    $dlg.find('[data-filter-count="changed"]').text(fmt(draft.counter.changed));
                    $dlg.find('[data-filter-count="unchanged"]').text(fmt(draft.counter["not-changed"]));

                    var filter = $dlg.find('[name="show-filter"]:checked').val();
                    var search = ($dlg.find('#js-massef-search').val() || '').trim().toLowerCase();

                    $dlg.find('.fields .field').each(function () {
                        var $field = $(this);
                        var showByFilter = true, showBySearch = true;

                        var code = getFieldCode($field);
                        var feature = draft.features[code];
                        if (filter === 'changed' && !(feature && feature.touched)) showByFilter = false;
                        if (filter === 'unchanged' && (feature && feature.touched)) showByFilter = false;

                        var $name = $field.find('.name[data-field-name]');
                        var name = $name.data('field-name') ? String($name.data('field-name')).toLowerCase() : '';
                        var codeValue = $name.data('field-code') ? String($name.data('field-code')).toLowerCase() : '';
                        if (!codeValue) {
                            var $hint = $field.find('.hint[data-field-code]');
                            codeValue = $hint.length ? String($hint.data('field-code')).toLowerCase() : '';
                        }

                        clearHighlights($name);
                        $field.find('.hint').each(function () { clearHighlights($(this)); });

                        if (search.length > 0) {
                            showBySearch = (name.indexOf(search) !== -1) || (codeValue.indexOf(search) !== -1);
                            if (showBySearch) {
                                highlightText($name, search);
                                $field.find('.hint').each(function () { highlightText($(this), search); });
                            }
                        }

                        $field.toggle(showByFilter && showBySearch);
                    });

                    var total = $dlg.find('.fields .field').length;
                    var shown = $dlg.find('.fields .field:visible').length;
                    if (search.length > 0) {
                        $dlg.find('#js-massef-search-counter').text('Найдено ' + shown + ' из ' + total);
                        $dlg.find('#js-massef-search-clear').show();
                    } else {
                        $dlg.find('#js-massef-search-counter').text('');
                        $dlg.find('#js-massef-search-clear').hide();
                    }
                }
                function bindSearch() {
                    var $input = $dlg.find('#js-massef-search');
                    var $clear = $dlg.find('#js-massef-search-clear');
                    function doSearch() { updateCountersAndFilters(); }
                    $input.off('input.massef').on('input.massef', doSearch);
                    $clear.off('click.massef').on('click.massef', function () { $input.val(''); doSearch(); });
                    $input.off('keydown.massef').on('keydown.massef', function (e) {
                        if (e.key === 'Escape') { $input.val(''); doSearch(); }
                    });
                }

                // --- события полей (текст/textarea — по blur; остальное — по change) ---
                function bindFieldsEvents() {
                    $dlg.find('.fields')
                        .off('blur.massef.text')
                        .on('blur.massef.text', 'input[type="text"], textarea', function () {
                            var $field = $(this).closest('.field');
                            var code = getFieldCode($field);
                            if (!code) return;

                            if ($(this).attr('name').indexOf('_from') > -1 || $(this).attr('name').indexOf('_to') > -1) {
                                var codeBase = code.replace(/(_from|_to)$/, '');
                                var from = $field.find('input[name^="features[' + codeBase + '_from"]').val();
                                var to = $field.find('input[name^="features[' + codeBase + '_to"]').val();
                                var clear = $field.find('.js-field-clear-switch').prop('checked');
                                var unitVal = $field.find('select.unit').val();
                                updateDraftField(codeBase, { from: from, to: to }, clear, $field, { unit: unitVal });
                            } else {
                                var value = $(this).val();
                                var clear = $field.find('.js-field-clear-switch').prop('checked');
                                var unitVal = $field.find('select.unit').val();
                                updateDraftField(code, value, clear, $field, { unit: unitVal });
                            }
                            updateAllFieldsState();
                            updateCountersAndFilters();
                        });

                    $dlg.find('.fields')
                        .off('change.massef.select')
                        .on('change.massef.select', 'select:not(.unit)', function () {
                            var $field = $(this).closest('.field');
                            var code = getFieldCode($field);
                            if (!code) return;

                            var value = $(this).val();
                            var clear = $field.find('.js-field-clear-switch').prop('checked');
                            var unitVal = $field.find('select.unit').val();
                            updateDraftField(code, value, clear, $field, { unit: unitVal });

                            updateAllFieldsState();
                            updateCountersAndFilters();
                        });

                    $dlg.find('.fields')
                        .off('change.massef.check')
                        .on('change.massef.check', 'input[type="checkbox"]:not(.js-field-clear-switch)', function () {
                            var $field = $(this).closest('.field');
                            var code = getFieldCode($field);
                            if (!code) return;

                            var values = [];
                            $field.find('input[type="checkbox"]:not(.js-field-clear-switch)').each(function () {
                                if ($(this).prop('checked')) values.push($(this).val());
                            });
                            var clear = $field.find('.js-field-clear-switch').prop('checked');
                            var unitVal = $field.find('select.unit').val();
                            updateDraftField(code, values, clear, $field, { unit: unitVal });
                            updateAllFieldsState();
                            updateCountersAndFilters();
                        });

                    $dlg.find('.fields')
                        .off('change.massef.radio')
                        .on('change.massef.radio', 'input[type="radio"]', function () {
                            var $field = $(this).closest('.field');
                            var code = getFieldCode($field);
                            if (!code) return;

                            var value = $field.find('input[type="radio"]:checked').val();
                            var clear = $field.find('.js-field-clear-switch').prop('checked');
                            var unitVal = $field.find('select.unit').val();
                            updateDraftField(code, value, clear, $field, { unit: unitVal });
                            updateAllFieldsState();
                            updateCountersAndFilters();
                        });

                    $dlg.find('.fields')
                        .off('change.massef.clear')
                        .on('change.massef.clear', '.js-field-clear-switch', function () {
                            var $field = $(this).closest('.field');
                            var code = getFieldCode($field);
                            if (!code) return;

                            var clear = $(this).prop('checked');
                            var value;

                            if ($field.find('input[name^="features[' + code + '_from"]').length ||
                                $field.find('input[name^="features[' + code + '_to"]').length) {
                                var from = $field.find('input[name^="features[' + code + '_from"]').val();
                                var to = $field.find('input[name^="features[' + code + '_to"]').val();
                                value = { from: from, to: to };
                            } else if ($field.find('input[type="checkbox"]:not(.js-field-clear-switch)').length) {
                                value = [];
                                $field.find('input[type="checkbox"]:not(.js-field-clear-switch)').each(function () {
                                    if ($(this).prop('checked')) value.push($(this).val());
                                });
                            } else if ($field.find('input[type="radio"]').length) {
                                value = $field.find('input[type="radio"]:checked').val();
                            } else if ($field.find('select').not('.unit').length) {
                                value = $field.find('select').not('.unit').val();
                            } else if ($field.find('textarea').length) {
                                value = $field.find('textarea').val();
                            } else {
                                value = $field.find('input[type="text"]').val();
                            }

                            var unitVal = $field.find('select.unit').val();
                            updateDraftField(code, value, clear, $field, { unit: unitVal, fromClearToggle: true });
                            updateAllFieldsState();
                            updateCountersAndFilters();
                        });

                    $dlg.find('.fields')
                        .off('change.massef.unit')
                        .on('change.massef.unit', 'select.unit', function () {
                            var $field = $(this).closest('.field');

                            var code = (function () {
                                var name = $field.find('[name^="features["]').not('[name$="_unit]"]').attr('name');
                                var m = name && name.match(/\[(.*?)\]/);
                                return m ? m[1].replace(/(_from|_to)$/, '') : null;
                            })();
                            if (!code) return;

                            var clear = $field.find('.js-field-clear-switch').prop('checked');
                            var unitVal = $(this).val();

                            var value;
                            if ($field.find('input[name^="features[' + code + '_from"]').length ||
                                $field.find('input[name^="features[' + code + '_to"]').length) {
                                var from = $field.find('input[name^="features[' + code + '_from"]').val();
                                var to = $field.find('input[name^="features[' + code + '_to"]').val();
                                value = { from: from, to: to };
                            } else if ($field.find('input[type="checkbox"]:not(.js-field-clear-switch)').length) {
                                value = [];
                                $field.find('input[type="checkbox"]:not(.js-field-clear-switch)').each(function () {
                                    if ($(this).prop('checked')) value.push($(this).val());
                                });
                            } else if ($field.find('input[type="radio"]').length) {
                                value = $field.find('input[type="radio"]:checked').val();
                            } else if ($field.find('select').not('.unit').length) {
                                value = $field.find('select').not('.unit').val();
                            } else if ($field.find('textarea').length) {
                                value = $field.find('textarea').val();
                            } else {
                                value = $field.find('input[type="text"]').val();
                            }

                            updateDraftField(code, value, clear, $field, { unit: unitVal });
                            updateAllFieldsState();
                            updateCountersAndFilters();
                        });
                }

                function bindFilterEvents() {
                    $dlg.find('[name="show-filter"]').off('change.massef').on('change.massef', function () {
                        updateCountersAndFilters();
                    });
                }

                // --- очистка при открытии ---
                function resetUIAfterPurge() {
                    $dlg.find('.fields .field .name[data-field-name]').each(function () {
                        var $el = $(this); if ($el.data('orig-html')) $el.html($el.data('orig-html'));
                    });
                    $dlg.find('.fields .field .hint').each(function () {
                        var $el = $(this); if ($el.data('orig-html')) $el.html($el.data('orig-html'));
                    });

                    $dlg.find('#js-massef-search').val('');
                    $dlg.find('#js-massef-search-counter').text('');
                    $dlg.find('#js-massef-search-clear').hide();
                    $dlg.find('[name="show-filter"][value="all"]').prop('checked', true);

                    $dlg.find('.fields .field').removeClass('changed to-clear').each(function () {
                        var $f = $(this);
                        $f.find('input, select, textarea').not('.js-field-clear-switch').prop('disabled', false);
                        $f.find('.js-field-clear-switch').prop('checked', false);
                    });

                    setDraft({ features: {}, counter: { "all-field": features_total, "changed": 0, "not-changed": features_total } });
                    updateAllFieldsState();
                    updateCountersAndFilters();
                }
                function purgeOnDialogOpen() { purgeAllMassefDrafts(); removeDraft(); resetUIAfterPurge(); }
                function attachOpenObserver() {
                    purgeOnDialogOpen();
                    var target = $dlg[0];
                    var observer = new MutationObserver(function () {
                        if ($dlg.is(':visible')) purgeOnDialogOpen();
                    });
                    observer.observe(target, { attributes: true, attributeFilter: ['style', 'class', 'open'] });
                }

                function cacheOriginalTitles() {
                    $dlg.find('.fields .field').each(function () {
                        var $field = $(this);
                        var $name = $field.find('.name[data-field-name]');
                        if ($name.length && !$name.data('orig-html')) $name.data('orig-html', $name.html());
                        $field.find('.hint').each(function () {
                            var $hint = $(this); if (!$hint.data('orig-html')) $hint.data('orig-html', $hint.html());
                        });
                    });
                }

                // запуск
                bindFieldsEvents();
                bindFilterEvents();
                bindSearch();
                attachOpenObserver();
                cacheOriginalTitles();

                // сабмит
                $dlg.find('#js-massef-dialog-form').off('submit.massef').on('submit.massef', function (e) {
                    var draft = getDraft();
                    if (!draft.counter.changed) {
                        alert('Не выбрано ни одной характеристики для изменения!');
                        e.preventDefault();
                        return false;
                    }
                    var $form = $(this);
                    var $saveBtn = $dlg.find('#js-massef-save-btn');
                    var $spinner = $saveBtn.find('.js-save-spinner');

                    if ($saveBtn.prop('disabled')) { e.preventDefault(); return false; }

                    $saveBtn.prop('disabled', true);
                    $spinner.show();

                    $form.find('input[name^="clear["]').remove();
                    $.each(draft.features, function (code, obj) {
                        if (obj && obj.touched && obj.clear) {
                            $('<input>', { type: 'hidden', name: 'clear[' + code + ']', value: '1' }).appendTo($form);
                        }
                    });
                });
            }
        });
    });
})();
