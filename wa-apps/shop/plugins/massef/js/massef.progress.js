/*!
 * massef-batch.js — Массовое сохранение характеристик (batch + прогресс) для Webasyst Shop-Script
 *
 * Что делает:
 *   • Перехватывает submit диалога #js-massef-dialog и крутит батчи POST → ?plugin=massef&module=saveBatch (offset/limit).
 *   • Показывает прогресс (%, «обработано N из M» с корректными окончаниями слова «товар»).
 *   • На время выполнения блокирует форму/поиск/фильтры (кроме #redirect_active и кнопки закрытия).
 *   • Завершение:
 *       – если #redirect_active включён — мгновенно показывает «Обновляю список…» и обновляет список товаров AJAX-ом
 *         (при недоступности контейнера/скриптов — быстрый GET через window.location.replace).
 *       – если выключен — кнопка «Готово» с плавной галочкой; по клику запускается тот же мягкий апдейт (с фолбэком).
 *   • Прерывание: по «Прервать» — подтверждение, AbortController, затем немедленный быстрый GET.
 *
 * Точки интеграции/DOM:
 *   #js-massef-progress, #js-massef-progress-bar, #js-massef-progress-text, #js-massef-status,
 *   #js-massef-save-btn (.js-save-spinner, .js-button-text), .js-close-dialog, #redirect_active,
 *   .js-field-clear-switch, #js-massef-search, #js-filter-group.
 *
 * Требования:
 *   jQuery, событие wa_dialog_ready; серверный saveBatch возвращает JSON с полями:
 *   { total, done, done_count, progress, next_offset|processed } (поддерживается и формат {status:"ok", data:{...}}).
 *
 * Автор: Petrosian Vagram
 * Дата: 2025-11-02
 */

(function ($) {
    "use strict";

    var NS = '.massef-batch';
    var BATCH_LIMIT = 300;
    var MASSEF_TOTAL_CACHE = null;

    // --- стили для галочки (однократно) ---
    function ensureDoneIconStyles() {
        if (document.getElementById('massef-done-icon-style')) return;
        var css = ''
            + '.massef-done-icon{opacity:0;background:#fff;border-radius:30px;padding:2px;color:#22d13d;'
            + 'width:10px!important;height:10px!important;display:inline-flex;align-items:center;justify-content:center;'
            + 'box-sizing:content-box;font-size:10px;line-height:10px;margin-left:4px;transition:opacity .2s ease;}'
            + '.massef-done-icon.is-visible{opacity:1!important;}';
        var style = document.createElement('style');
        style.id = 'massef-done-icon-style';
        style.type = 'text/css';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    }

    // --- склонение слова по числу ---
    var TOVAR_FORMS = ['товар', 'товара', 'товаров'];   // 1, 2-4, 5+
    var TOVAR_FORMS2 = ['товара', 'товаров', 'товаров']; // для «из N …»
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

    function updateProgress($dlg, percent, done, total) {
        percent = Math.max(0, Math.min(100, Math.round(percent || 0)));
        $dlg.find('#js-massef-progress-bar').css('width', percent + '%');
        $dlg.find('#js-massef-progress-text').text(percent + '%');

        if (total > 0) {
            var doneStr = pluralize(done, TOVAR_FORMS, true);
            var totalStr = pluralize(total, TOVAR_FORMS2, true);
            $dlg.find('#js-massef-status').text(percent + '% (обработано ' + doneStr + ' из ' + totalStr + ')');
        }
    }

    function collectClearMap($dlg) {
        var clear = {};
        $dlg.find('.js-field-clear-switch:checked').each(function () {
            var $field = $(this).closest('.field');
            var code = ($field.find('[data-field-code]').data('field-code') || $field.find('.hint').text() || '').trim();
            if (code) clear[code] = 1;
        });
        return clear;
    }

    // ---------- UI lock/unlock ----------
    function lockUI($dlg) {
        var $content = $dlg.find('.dialog-content');
        var $toDisable = $content.find('input, select, textarea, button')
            .not('#redirect_active')
            .not('.js-close-dialog');
        $toDisable.addClass('massef-disabled-during-run').prop('disabled', true);
        $content.addClass('massef-locked');

        $dlg.find('#js-massef-search, #js-filter-group input[type=radio]')
            .addClass('massef-disabled-during-run')
            .prop('disabled', true);

        $dlg.find('.js-close-dialog').removeClass('light-gray').addClass('yellow').text('Прервать');
    }

    function unlockUI($dlg, resetCloseToDefault) {
        $dlg.find('.massef-disabled-during-run').prop('disabled', false).removeClass('massef-disabled-during-run');
        $dlg.find('.dialog-content').removeClass('massef-locked');
        if (resetCloseToDefault) {
            $dlg.find('.js-close-dialog').removeClass('green yellow').addClass('light-gray').text('Закрыть');
        }
    }

    // -------- быстрый «мягкий» GET без лишних таймеров/анимаций --------
    function hardReload() {
        // replace — без лишней записи в history, быстрее чем навигация через ссылки
        window.location.replace(window.location.href);
    }

    // ---------- Batch loop ----------
    async function runBatchLoop($dlg, baseFormData, limit, controller) {
        var offset = 0;

        var total = MASSEF_TOTAL_CACHE
            || parseInt($dlg.attr('data-products-count') || $dlg.data('products-count'), 10)
            || 0;

        var $save = $dlg.find('#js-massef-save-btn');
        var $spinner = $dlg.find('.js-save-spinner');

        $dlg.data('massef-running', true);
        $dlg.data('massef-done', false);

        $dlg.find('#js-massef-progress').show();
        updateProgress($dlg, 1, 0, total);
        lockUI($dlg);

        while (true) {
            if (!$dlg.closest('body').length || controller.signal.aborted) {
                $dlg.find('#js-massef-status').text('Операция прервана пользователем');
                $spinner.hide();
                $save.prop('disabled', false).show().find('.js-button-text').text('Сохранить');
                unlockUI($dlg, true);
                $dlg.data('massef-running', false);
                break;
            }

            var fd = new FormData();
            for (var pair of baseFormData.entries()) {
                fd.append(pair[0], pair[1]); // csrf, products_hash, features[]
            }

            var clear = collectClearMap($dlg);
            Object.keys(clear).forEach(function (code) {
                fd.append('clear[' + code + ']', '1');
            });

            if (total > 0) fd.append('total', String(total));

            fd.append('offset', offset);
            fd.append('limit', limit);
            fd.append('ajax', '1');

            try {
                var res = await fetch('?plugin=massef&module=saveBatch', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                var data;
                var ct = res.headers.get('content-type') || '';
                if (ct.indexOf('application/json') !== -1) {
                    var json = await res.json();
                    data = (json && typeof json === 'object' && 'data' in json) ? json.data : json;
                } else {
                    var text = await res.text();
                    throw new Error('Некорректный ответ сервера: ' + (text || 'empty'));
                }

                if (!data || typeof data.total === 'undefined') {
                    throw new Error('Некорректный ответ сервера');
                }

                if (data.total > 0 && data.total !== total) {
                    total = data.total;
                    MASSEF_TOTAL_CACHE = total;
                }

                updateProgress($dlg, data.progress, data.done_count, total);

                if (data.done) {
                    $dlg.data('massef-running', false);
                    $dlg.data('massef-done', true);

                    var doneStr = pluralize(data.done_count, TOVAR_FORMS, true);
                    var totalStr = pluralize(total, TOVAR_FORMS2, true);
                    $dlg.find('#js-massef-status').text('Готово! Обработано ' + doneStr + ' из ' + totalStr + '.');

                    $spinner.hide();
                    $save.hide();

                    var auto = !!$dlg.find('#redirect_active').prop('checked');
                    var $close = $dlg.find('.js-close-dialog').removeClass('yellow light-gray').addClass('green');

                    if (auto) {
                        // авто: показываем индикатор и сразу делаем быстрый GET
                        $close.html('<span class="js-button-text">Обновляю список…</span><i class="fas fa-spinner wa-animation-spin speed-1000" style="margin-left:6px;"></i>');
                        // минимальная задержка, чтобы DOM успел перерисовать кнопку со спиннером
                        setTimeout(hardReload, 10);
                    } else {
                        // ручной режим: галочка, без промежуточного текста при клике
                        ensureDoneIconStyles();
                        $close.html('<span class="js-button-text">Готово</span><span class="massef-done-icon" aria-hidden="true"><i class="fas fa-check"></i></span>');
                        requestAnimationFrame(function () {
                            $close.find('.massef-done-icon').addClass('is-visible');
                        });
                    }
                    break;
                }

                offset = data.next_offset || (offset + (data.processed || limit));
                await new Promise(function (r) { setTimeout(r, 100); });

            } catch (e) {
                if (controller.signal.aborted) { continue; }
                $dlg.find('#js-massef-status').text('Ошибка: ' + (e && e.message ? e.message : e));
                $spinner.hide();
                $save.prop('disabled', false).show().find('.js-button-text').text('Сохранить');
                unlockUI($dlg, true);
                $dlg.data('massef-running', false);
                break;
            }
        }
    }

    // ---------- Init ----------
    $(document).on('wa_dialog_ready', '#js-massef-dialog', function (e, dialog_instance) {
        var $dlg = $(this);
        var $save = $dlg.find('#js-massef-save-btn');
        var $spin = $dlg.find('.js-save-spinner');
        var abortController = null;

        // «Закрыть» / «Прервать» / «Готово»
        $dlg.off('click' + NS, '.js-close-dialog').on('click' + NS, '.js-close-dialog', function () {
            var running = !!$dlg.data('massef-running');
            var done = !!$dlg.data('massef-done');

            if (running) {
                if (confirm('Процесс ещё не завершён. Прервать операцию? Внесенные изменения до эго момента остануться!')) {
                    if (abortController) abortController.abort();
                    hardReload(); // немедленный быстрый GET
                }
                return;
            }

            if (done) {
                // ручной режим: сразу быстрый GET без смены текста/иконок
                hardReload();
                return;
            }

            dialog_instance.close();
        });

        // submit => батчи + прогресс
        $(document)
            .off('submit' + NS)
            .on('submit' + NS, '#js-massef-dialog-form', function (ev) {
                ev.preventDefault();
                if ($dlg.data('massef-running')) return false;

                var fd = new FormData(this);
                $save.prop('disabled', true);
                $spin.show();
                $save.find('.js-button-text').text('В процессе');

                abortController = new AbortController();
                runBatchLoop($dlg, fd, BATCH_LIMIT, abortController);
            });

        // Сброс кэша total при закрытии диалога
        $(document).one('wa_dialog_close.massef-batch', function () {
            MASSEF_TOTAL_CACHE = null;
        });
    });

})(jQuery);

