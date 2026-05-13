/**
 * lk.confirm.js — МОДУЛЬ: CONFIRM
 *
 * НАЗНАЧЕНИЕ
 * - Перехватывает клики и отправку форм для подтверждения действия.
 * - Работает по атрибуту: [data-confirm="Текст вопроса"]
 * - Поддерживает защиту от ухода со страницы (Guard) через [data-confirm-guard].
 * - Использует Bootstrap Modal, если доступен, иначе нативный window.confirm.
 *
 * ПУБЛИЧНЫЙ API
 * - lk.confirm.init() — инициализация (регистрация обработчиков).
 *
 * ПРАВИЛА
 * - init() идемпотентный:
 * - Использует внутренний флаг _inited для запуска ровно один раз.
 * - Делегирование событий на document (поддержка динамических DOM вставок/htmx).
 *
 * ЗАВИСИМОСТИ
 * - Bootstrap 5 (Modal) — опционально (рекомендуется).
 * - lk.helpers (для getCsrfToken).
 *
 * ПОДКЛЮЧЕНИЕ
 * - Скрипт подключается в шаблоне.
 * - Запуск выполняется из js/frontend/lk.ui.init.js
 */
; (function (lk, window, document) {
    'use strict';

    var confirmModule = lk.confirm = lk.confirm || {};
    var MODAL_ID = 'lk-confirm-modal';
    var lastSubmitterByForm = new WeakMap();
    var pending = null;
    var allowUnloadOnce = false;

    var guard = {
        enabled: false,
        form: null,
        formId: '',
        opt: null,
        baseline: ''
    };

    function hasBootstrap() {
        return typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal;
    }

    function getDs(el, key, fallback) {
        if (!el || !el.dataset) return fallback;
        var v = el.dataset[key];
        return (v === undefined || v === null || v === '') ? fallback : v;
    }

    function normalizeSize(size) {
        if (size === 'sm' || size === 'lg' || size === 'xl') return size;
        return '';
    }

    function ensureModal() {
        var modalEl = document.getElementById(MODAL_ID);
        if (modalEl) return modalEl;

        var wrap = document.createElement('div');
        wrap.innerHTML =
            '<div class="modal fade" id="' + MODAL_ID + '" tabindex="-1" aria-hidden="true">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title"></h5></div>' +
            '<div class="modal-body"></div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-danger" data-role="ok"></button>' +
            '<button type="button" class="btn btn-secondary" data-role="cancel" data-bs-dismiss="modal"></button>' +
            '</div></div></div></div>';

        document.body.appendChild(wrap.firstChild);
        return document.getElementById(MODAL_ID);
    }

    function setModalContent(modalEl, opt) {
        var titleEl = modalEl.querySelector('.modal-title');
        var bodyEl = modalEl.querySelector('.modal-body');
        var okBtn = modalEl.querySelector('[data-role="ok"]');
        var cancelBtn = modalEl.querySelector('[data-role="cancel"]');
        var dialogEl = modalEl.querySelector('.modal-dialog');

        titleEl.textContent = opt.title;
        if (opt.isHtml) bodyEl.innerHTML = opt.text;
        else bodyEl.textContent = opt.text;

        okBtn.textContent = opt.okText;
        cancelBtn.textContent = opt.cancelText;
        okBtn.className = opt.okClass;
        cancelBtn.className = opt.cancelClass;

        var size = normalizeSize(opt.size);
        dialogEl.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
        if (size) dialogEl.classList.add('modal-' + size);
    }

    function readOptionsFromElement(el) {
        var text = getDs(el, 'confirm', '');
        if (!text) return null;
        return {
            title: getDs(el, 'confirmTitle', 'Подтверждение'),
            text: text,
            okText: getDs(el, 'confirmOk', 'Ок'),
            cancelText: getDs(el, 'confirmCancel', 'Отмена'),
            okClass: getDs(el, 'confirmOkClass', 'btn btn-danger'),
            cancelClass: getDs(el, 'confirmCancelClass', 'btn btn-secondary'),
            size: getDs(el, 'confirmSize', ''),
            isHtml: getDs(el, 'confirmHtml', '') ? true : false,
            confirmForm: getDs(el, 'confirmForm', ''),
            confirmGuard: getDs(el, 'confirmGuard', '') ? true : false
        };
    }

    function runFallbackNativeConfirm(opt, done) {
        if (window.confirm(opt.text)) done(true);
        else done(false);
    }

    function serializeForm(form) {
        var fd = new FormData(form);
        var pairs = [];
        fd.forEach(function (value, key) {
            if (value instanceof File) {
                var file_sig = 'file:' + (value.name || '') + '|' + (value.size || 0) + '|' + (value.lastModified || 0);
                pairs.push(key + '=' + file_sig);
            } else {
                pairs.push(key + '=' + String(value));
            }
        });
        pairs.sort();
        return pairs.join('\n');
    }

    function isGuardDirty() {
        if (!guard.enabled || !guard.form) return false;
        return serializeForm(guard.form) !== guard.baseline;
    }

    function markLeavingApproved() { allowUnloadOnce = true; }

    function performPendingAction() {
        if (!pending) return;
        markLeavingApproved();

        // Получаем CSRF через хелпер
        var csrfToken = (lk.helpers && lk.helpers.getCsrfToken)
            ? lk.helpers.getCsrfToken()
            : '';

        if (pending.href) {
            var method = 'get';
            if (pending.el) {
                var m = getDs(pending.el, 'method', null);
                if (!m && pending.el.getAttribute) m = pending.el.getAttribute('data-method');
                if (m) method = m.toLowerCase();
            }

            if (method === 'post') {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = pending.href;
                form.style.display = 'none';

                if (csrfToken) {
                    var inputCsrf = document.createElement('input');
                    inputCsrf.type = 'hidden';
                    inputCsrf.name = '_csrf';
                    inputCsrf.value = csrfToken;
                    form.appendChild(inputCsrf);
                }
                document.body.appendChild(form);
                form.submit();
            } else {
                window.location.href = pending.href;
            }
            pending = null;
            return;
        }

        if (pending.form) {
            if (!pending.form.querySelector('input[name="_csrf"]')) {
                if (csrfToken) {
                    var inputCsrf2 = document.createElement('input');
                    inputCsrf2.type = 'hidden';
                    inputCsrf2.name = '_csrf';
                    inputCsrf2.value = csrfToken;
                    pending.form.appendChild(inputCsrf2);
                }
            }
            if (typeof pending.form.requestSubmit === 'function') {
                if (pending.submitter) pending.form.requestSubmit(pending.submitter);
                else pending.form.requestSubmit();
            } else {
                pending.form.submit();
            }
            pending = null;
            return;
        }

        if (pending.el) {
            pending.el.setAttribute('data-confirm-bypass', '1');
            pending.el.click();
            pending = null;
            return;
        }
        pending = null;
    }

    function openConfirm(el, opt, ctx) {
        pending = {
            el: el || null,
            form: ctx && ctx.form ? ctx.form : null,
            submitter: ctx && ctx.submitter ? ctx.submitter : null,
            href: ctx && ctx.href ? ctx.href : null
        };

        if (!hasBootstrap()) {
            runFallbackNativeConfirm(opt, function (ok) {
                if (ok) performPendingAction();
                else pending = null;
            });
            return;
        }

        var modalEl = ensureModal();
        setModalContent(modalEl, opt);
        var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        var okBtn = modalEl.querySelector('[data-role="ok"]');

        var onOk = function () {
            okBtn.removeEventListener('click', onOk);
            modal.hide();
            performPendingAction();
        };

        okBtn.addEventListener('click', onOk);
        modal.show();
    }

    function initGuardFromPage() {
        var guardEl = document.querySelector('[data-confirm-guard="1"][data-confirm-form]');
        if (!guardEl) return;
        var opt = readOptionsFromElement(guardEl);
        if (!opt || !opt.confirmForm) return;
        var form = document.getElementById(opt.confirmForm);
        if (!form) return;

        guard.enabled = true;
        guard.form = form;
        guard.formId = opt.confirmForm;
        guard.opt = opt;
        guard.baseline = serializeForm(form);

        window.addEventListener('beforeunload', function (e) {
            if (allowUnloadOnce) return;
            if (!isGuardDirty()) return;
            e.preventDefault();
            e.returnValue = '';
            return '';
        });

        form.addEventListener('submit', function () { markLeavingApproved(); }, true);
    }

    function init() {
        if (confirmModule._inited) return;
        confirmModule._inited = true;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGuardFromPage);
        } else {
            initGuardFromPage();
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('button, input[type="submit"]');
            if (!btn) return;
            var form = btn.form;
            if (form) lastSubmitterByForm.set(form, btn);
        }, true);

        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) return;

            if (el.getAttribute('data-confirm-bypass') === '1') {
                el.removeAttribute('data-confirm-bypass');
                return;
            }

            var opt = readOptionsFromElement(el);
            if (!opt) return;

            if (el.tagName === 'BUTTON' && (el.type === 'submit' || el.getAttribute('type') === 'submit')) return;
            if (el.tagName === 'INPUT' && el.type === 'submit') return;

            if (opt.confirmGuard && opt.confirmForm && guard.enabled && guard.formId === opt.confirmForm) {
                if (!isGuardDirty()) return;
            }

            e.preventDefault();

            if (el.tagName === 'A' && el.href) {
                openConfirm(el, opt, { href: el.href });
                return;
            }
            openConfirm(el, opt, {});
        });

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form) return;

            var submitter = e.submitter || null;
            if (!submitter) submitter = lastSubmitterByForm.get(form) || null;

            var elWithConfirm = null;
            if (submitter && submitter.hasAttribute('data-confirm')) elWithConfirm = submitter;
            else if (form.hasAttribute('data-confirm')) elWithConfirm = form;

            if (!elWithConfirm) return;
            var opt = readOptionsFromElement(elWithConfirm);
            if (!opt) return;

            e.preventDefault();
            openConfirm(elWithConfirm, opt, { form: form, submitter: submitter });
        });

        document.addEventListener('click', function (e) {
            if (!guard.enabled || !guard.opt) return;
            if (e.target.closest('[data-confirm]')) return;
            if (!isGuardDirty()) return;

            var a = e.target.closest('a[href]');
            if (!a) return;
            var hrefAttr = a.getAttribute('href') || '';
            if (!hrefAttr || hrefAttr.charAt(0) === '#' || hrefAttr.indexOf('javascript:') === 0) return;

            var target = a.getAttribute('target');
            if (target && target !== '_self') return;
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

            e.preventDefault();
            openConfirm(a, guard.opt, { href: a.href });
        }, true);
    }

    confirmModule.init = init;

})(window.lk = window.lk || {}, window, document);