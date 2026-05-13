( function($) {
    $.files = $.extend($.files || {}, {

        init: function(options) {

            // init app config
            this.config.init(options.config || {});

            // run background tasks
            this.runBackgroundTasks();

            // init sidebar
            new Sidebar($("#f-sidebar"));

            // init app controller
            $.files.controller.init();

            // global click on back list link
            if (history.back) {
                $('body').on('click', '.f-back-to-list', function() {
                    history.back();
                });
            }

            // kill wa-global ajax error handler for 403 errors
            $.wa.errorHandler = function(xhr) {
                if ($.files.getXWaFilesLocation(xhr) || $.files.getXWaFilesSourceStatus(xhr)) {
                    $.wa.dialogHide();
                    return false;
                }
                if (xhr.status === 403) {
                    $.files.alert403Error(xhr.responseText);
                    return false;
                }
            };

            $(document).ajaxComplete(function (event, xhr) {
                if ($.files.getXWaFilesLocation(xhr)) {
                    window.location.href = $.files.getXWaFilesLocation(xhr);
                    $('.dialog').hide();
                    return false;
                }
                var status = $.files.getXWaFilesSourceStatus(xhr);
                if (status) {
                    $('.dialog').hide();
                    if (status === 'in-pause') {
                        $.files.alertError($_('Too many requests to external source service. Please try again in a few minutes.'));
                    } else {
                        $.files.alertError($_('Server error'));
                    }

                    return false;
                }
            });

        },

        load: function (url, callback, options) {
            options = options || {};
            var load_protector = $.files.load_protector = Math.random();
            var container = $.files.config.getMainContainer();
            if (options.container) {
                if ($.type(options.container) === 'string' && $(options.container).length) {
                    container = $(options.container);
                } else if (options.container.length) {
                    container = $(options.container);
                }
            }
            var loading = $('.f-loading', container).show();

            var render = function(result) {

                var $content = $('.f-load-block', container).first();

                loading.hide();

                if (load_protector !== $.files.load_protector) {
                    // too late!
                    return;
                }
                var tmp = $('<div></div>').html(result);
                var title = tmp.find('.h-main-title').text();
                if (title) {
                    $.files.setBrowserTitle(title);
                }
                tmp.remove();
                $content.html(result);

                if (callback) {
                    try {
                        callback.call(this);
                    } catch (e) {

                    }
                }

                $(window).trigger($.Event('wa_loaded'));

            };

            var onSuccess = $.files.load.onSuccess || render;
            var onError = $.files.load.onError || function(result) { render(result.responseText); }
            $.get(url)
                .success(function (r, s, x) {
                    if ($.files.getXWaFilesLocation(x) || $.files.getXWaFilesSourceStatus(x)) {
                        return;
                    }
                    onSuccess && onSuccess(r);
                })
                .error(function (r, s, x) {
                    if ($.files.getXWaFilesLocation(r) || $.files.getXWaFilesSourceStatus(r)) {
                        return;
                    }
                    onError && onError(r);
                })
                .always(function (r, s, x) {
                    if ($.files.getXWaFilesLocation(x) || $.files.getXWaFilesSourceStatus(x)) {
                        return;
                    }
                });
        },

        alert403Error: function(html) {
            $.waDialog.alert({
                text: html,
                button_title: 'Ok',
                button_class: 'warning',
            });
        },

        alertError: function(error_msg, log_msg) {
            $.waDialog.alert({
                text: error_msg,
                button_title: 'Ok',
                button_class: 'warning',
            });

            if (log_msg) {
                this.logError(log_msg);
            }
        },

        alertInfo: function(info_html) {
            $.waDialog.alert({
                text: info_html,
                button_title: 'Ok',
                button_class: 'warning',
            });
        },

        getXWaFilesLocation: function (xhr) {
            return (xhr && xhr.getResponseHeader && xhr.getResponseHeader('X-Wa-Files-Location')) || '';
        },

        getXWaFilesSourceStatus: function (xhr) {
            return (xhr && xhr.getResponseHeader && xhr.getResponseHeader('X-Wa-Files-Source-Status')) || '';
        },

        confirmDelete: function (confirm_msg, ok, cancel) {

            if ($.isPlainObject(confirm_msg)) {
                confirm_msg.btn_text = $_('Delete');
            }

            this.confirm(confirm_msg, ok, cancel);
        },

        confirm: function(confirm_msg, ok, cancel) {

            let confirm_title = '';
            let confirm_btn_text = $_('Yes');
            if ($.isPlainObject(confirm_msg)) {
                confirm_title = confirm_msg.title || '';
                confirm_btn_text = confirm_msg.btn_text || $_('Yes');
                confirm_msg = confirm_msg.msg || '';
            }
            //
            // dialog.filesDialog({
            //     onLoad: function() {
            //         var dialog = $(this);
            //         dialog.find('.f-title').html(confirm_title);
            //         dialog.find('.f-text').html(confirm_msg);
            //
            //         var title_height = dialog.find('.f-title').height();
            //         var content_height = dialog.find('.f-text').height();
            //         var buttons_height = dialog.find('.dialog-buttons').height();
            //         var total_height = content_height + buttons_height + title_height;
            //         var max_height = 300;
            //         var min_height = 150;
            //         var height = Math.min(Math.max(total_height, max_height), min_height);
            //
            //         dialog.filesDialog('height', height);
            //
            //         dialog.find('.f-confirm-ok').unbind('.files')
            //             .bind('click.files', function() {
            //                 dialog.trigger('close');
            //                 ok && ok.apply($(this));
            //             });
            //         dialog.find('.f-confirm-cancel').unbind('.files')
            //             .bind('click.files', function() {
            //                 cancel && cancel.apply($(this));
            //             });
            //     }
            // });
            //return dialog

            return $.waDialog.confirm({
                title: confirm_title,
                text: confirm_msg,
                success_button_title: confirm_btn_text,
                success_button_class: 'danger',
                cancel_button_title: $_('Cancel'),
                cancel_button_class: 'light-gray',
                onSuccess(dialog) {
                    ok && ok.apply($(this));
                },
                onCancel(dialog) {
                    cancel && cancel.apply($(this));
                }
            });
        },

        jsonPost: function f(url, data, onSuccess, onError, onAlways) {
            if (arguments.length === 1 && $.isPlainObject(url)) {
                var options = url;
                url = options.url || '';
                data = options.data || '';
                onSuccess = options.onSuccess;
                onError = options.onError || options.onFailure || options.onFail;
                onAlways = options.onAlways;
            }
            if ($.isEmptyObject(data)) {
                data = {};
            }
            data['_timestamp'] = Date.now();
            f.run = f.run || {};
            if (f.run[url]) {
                return f.run[url];
            }
            var xhr = $.post(url, data,
                function(r, s, x) {
                    if ($.files.getXWaFilesLocation(x) || $.files.getXWaFilesSourceStatus(x)) {
                        return;
                    }
                    if (!r) {
                        onError && onError();
                    } else {
                        onSuccess && onSuccess(r);
                    }
                }, 'json')
                .error(function(r) {
                    if ($.files.getXWaFilesLocation(r) || $.files.getXWaFilesSourceStatus(r)) {
                        return;
                    }
                    if (r.status == 403) {
                        return;
                    } else {
                        onError && onError(r);
                    }
                })
                .always(function(r, s, x) {
                    delete f.run[url];
                    if ($.files.getXWaFilesLocation(x) || $.files.getXWaFilesSourceStatus(x)) {
                        return;
                    }
                    onAlways && onAlways(r);
                });
            f.run[url] = xhr;
            return f.run[url];
        },

        showValidateErrors: function(errors, form) {
            $.each(errors, function(i, er) {
                const el = form.find('[name="' + er.name + '"]').addClass('state-error');
                el.parent().append('<span class="state-error-hint">' + er.msg + '</span>');
            });
        },

        onFormChange: function(form, handler) {
            var all_inputs = form.find(':input');

            all_inputs.on('change.files_form_change', function() {
                handler.apply(form, [this]);
            });

            all_inputs.filter('select').on('click.files_form_change', function() {
                handler.apply(form, [this]);
            });

            var delay = 250;
            var timer_id = null;
            all_inputs.filter('textarea,input[type=text]').on('keydown.files_form_change', function() {
                if (timer_id) {
                    clearTimeout(timer_id);
                    timer_id = null;
                }
                var self = this;
                timer_id = setTimeout(function() {
                    handler.apply(form, [self]);
                }, delay);
            });

        },

        offFormChange: function(form) {
            form.find(':input,:select').off('.file_form_change');
        },

        clearValidateErrors: function(form) {
            form.find('.state-error-hint').remove()
                .end().find('.state-error').removeClass('state-error');
        },

        initLazyLoad: function(options) {
            var count = options.count;
            var offset = count;
            var total_count = options.total_count;
            var url = options.url;
            var container = $(options.container);
            var auto = typeof options.auto === 'undefined' ? true : options.auto;
            var win = $(window);
            win.lazyLoad('stop'); // stop previous lazy-load implementation

            if (offset < total_count) {
                win.lazyLoad({
                    container: container,
                    state: auto ? 'wake' : 'stop',
                    load: function() {
                        win.lazyLoad('sleep');
                        $('.f-lazyloading-link').hide();
                        $('.f-lazyloading-progress').show();
                        $.get(url + '&lazy=1&offset=' + offset + '&total_count=' + total_count, function(data) {
                            var html = $('<div></div>').html(data);
                            var list = html.find('.f-catalog-item');
                            if (list.length) {
                                offset += list.length;
                                $('.f-catalog-wrapper', container).append(list);
                                if (offset >= total_count) {
                                    win.lazyLoad('stop');
                                    $('.f-lazyloading-progress').hide();
                                } else {
                                    win.lazyLoad('wake');
                                    $('.f-lazyloading-link').show();
                                    if (!auto) {
                                        $('.f-lazyloading-progress').hide();
                                    }
                                }
                            } else {
                                win.lazyLoad('stop');
                                $('.f-lazyloading-progress').hide();
                            }

                            $('.f-lazyloading-progress-string', container).
                                    replaceWith(
                                        $('.f-lazyloading-progress-string', html)
                                    );
                            $('.f-lazyloading-chunk', container).
                                    replaceWith(
                                        $('.f-lazyloading-chunk', html)
                                    );

                            html.remove();

                            options.onLoad && options.onLoad();
                            $.files.retina();

                        });
                    }
                });
                container.on('click', '.lazyloading-link', function() {
                    $(window).lazyLoad('force');
                    return false;
                });
            }
        },

        refreshSidebar: function f() {
            if (f.load) {
                return;
            }
            f.load = true;
            var sidebar = $.files.config.getSidebar();
            var li = sidebar.find('.selected');
            var selector = (li.attr('class') || '').split(/\s+/).join('.');
            var xhr = $.get('?module=backend&action=sidebar&is_reload=1', function(html) {
                const $toggle = sidebar.find('.sidebar-mobile-toggle').detach();
                sidebar.html(html)
                sidebar.prepend($toggle);

                sidebar.find('.sidebar-header').css('display', '');
                sidebar.find('.sidebar-body').css('display', '');
                sidebar.find('.sidebar-footer').css('display', '');

                sidebar.find('a')
                    .not('.sidebar-mobile-toggle a')
                    .on('click', function () {
                        $('.sidebar-mobile-toggle').trigger('click');
                    })
            }).always(function() {
                f.load = false;
                li = selector ? sidebar.find(selector) : $();
                if (li.length) {
                    li.addClass('selected');
                } else {
                    $.files.config.getSidebar().trigger('notify', {
                        hash: $.files.controller.getCurrentHash()
                    });
                }
            });
            return xhr;
        },

        setBrowserTitle: function(title, ignore_default_suffix) {
            var suffix = !ignore_default_suffix ? (' — ' + this.config.getAccountName()) : '';
            document.title = title + suffix;
        },

        iButton: function(elem, options) {
            var ibutton_options = $.extend({
                labelOn: '',
                labelOff: '',
                className: 'mini'
            }, options || {});
            elem.iButton(ibutton_options);
            return elem;
        },

        moveFiles: function(options) {

            var file_ids = options.file_ids || [];
            var storage_id = options.storage_id || 0;
            var folder_id = options.folder_id || 0;
            var is_restore = options.is_restore || '';
            var onFailure = (options.onFailure || options.onError || options.onFail);
            var onAlways = options.onAlways;
            var onSuccess = options.onSuccess;
            var beforeMoveFiles = options.beforeMoveFiles;

            // source place is the same as destination place
            if (
                (storage_id && $.files.controller.getCurrentStorageId() === storage_id)
                    ||
                (folder_id && $.files.controller.getCurrentFolderId() === folder_id)
            )
            {
                onFailure && onFailure({
                    same_place: true
                });
                return false;
            }

            beforeMoveFiles && beforeMoveFiles();

            $.files.jsonPost({
                url: '?module=move&action=files' + (is_restore ? '&restore=1' : ''),
                data: { folder_id: folder_id, storage_id: storage_id, file_id: file_ids },
                onSuccess: function(r) {

                    if (r && r.status === 'ok' && r.data && r.data.success) {
                        onSuccess && onSuccess(r);
                    } else if (r && r.data && r.data.unallowed_exist) {
                        var file_id_str = $.map(file_ids, function(v) {
                            return 'file_id[]=' + v;
                        }).join('&');
                        $.files.loadDialog('?module=move&' + file_id_str +
                            '&storage_id=' + storage_id + '&folder_id=' + folder_id);
                    } else if (r && r.data && r.data.files_conflict) {
                        var file_id_str = $.map(file_ids, function(v) {
                            return 'file_id[]=' + v;
                        }).join('&');
                        $.files.loadDialog('?module=move&action=nameConflict&' + file_id_str +
                            '&storage_id=' + storage_id + '&folder_id=' + folder_id + (is_restore ? '&restore=1' : ''));
                    } else {
                        if (r && r.data) {
                            onFailure(r.data);
                        } else if (r && r.errors) {
                            onFailure(r.errors);
                        } else {
                            onFailure();
                        }
                    }
                },
                onFailure: function(r) {
                    onFailure(r);
                },
                onAlways: function() {
                    onAlways && onAlways();
                }
            });
        },

        filterSettingsFormSubmit: function(form, options) {
            $.files.clearValidateErrors(form);

            options = options || {};
            const onSuccess = options.onSuccess;
            const onAlways = options.onAlways || null;
            const error = function(r)  {
                $.files.alertError($_("Couldn't save filter"), r);
            };

            // make post request
            $.files.jsonPost({
                url: '?module=filter&action=save',
                data: form.serialize(),
                onSuccess: function(r) {
                    if (r.status !== 'ok') {
                        $.files.showValidateErrors(r.errors, form);
                    } else if (!r.data.filter) {
                        error(r.data);
                    } else {
                        $.files.controller.gotoFilterPage(r.data.filter.id);
                        $.files.refreshSidebar();
                        onSuccess && onSuccess();
                    }
                },
                onFailure: error,
                onAlways: function() {
                    onAlways && onAlways();
                }
            });
        },

        storageSettingsFormSubmit: function(form, options) {
            if (form.data('loading')) {
                return false;
            }
            form.data('loading', 1);
            form.find('.fa-spinner').removeClass('hidden');
            form.find('[type="submit"]').attr('disabled', true);
            $.files.clearValidateErrors(form);

            options = options || {};
            const onSuccess = options.onSuccess;
            const onAlways = options.onAlways;

            const error = r => {
                if (r.status !== 403) {
                    $.files.alertError($_("Couldn't create storage"), r);
                }
            };

            // make post request
            $.files.jsonPost({
                url: '?module=storage&action=save',
                data: form.serialize(),
                onSuccess: function(r) {
                    if (r.status !== 'ok') {
                        $.files.showValidateErrors(r.errors, form);
                    } else if (!r.data.storage) {
                        error(r.data);
                    } else {
                        if (options.reload !== false) {
                            $.files.controller.gotoStoragePage(r.data.storage.id);
                        }
                        $.files.refreshSidebar();
                        onSuccess && onSuccess();
                    }
                },
                onFailure: error,
                onAlways: function() {
                    form.data('loading', 0);
                    form.find('.fa-spinner').addClass('hidden');
                    form.find('[type="submit"]').attr('disabled', false);
                    onAlways && onAlways();
                }
            });
        },

        showNotificationMessage: function(message, color) {
            var height = 20;
            var width = 200;
            var hideDown = function(block) {
                block.animate({
                    height: 0
                }, 500, function() {
                    $(this).remove();
                });
            };

            if ($('.f-notification-message').length) {
                hideDown($('.f-notification-message'));
            }

            var block = $('<div>').addClass('f-notification-message block double-padded')
                .css({
                    display: 'none',
                    position: 'fixed',
                    width: width,
                    bottom: 0,
                    right: 0,
                    height: 0,
                    backgroundColor: color || '#FEF49C',
                    border: '2px solid #DDD'
                });
            block.append('<p>' + message + '</p>');
            block.appendTo($('body'));
            block.show().animate({
                height: height
            }, 500);

            setTimeout(function() {
                hideDown(block);
            }, 1500);

        },

        loadDialog: function f(url, options) {
            f.load = f.load || {};
            if (f.load[url]) {
                return;
            }

            f.load[url] = true;
            $.get(url, function(html) {
                f.load[url] = false;
                $(html).filesDialog(options || {});
            }).error(function(r) {
                if (r.status === 403) {
                    $.files.alert403Error(r.responseText);
                }
            });
        },

        parseFileName: function(filename) {
            var ext = '';
            var pos = filename.lastIndexOf('.');
            if (pos > 0) {
                ext = filename.slice(pos + 1);
                filename = filename.slice(0, pos);
            }
            return { filename: filename, ext: ext };
        },

        retina: function() {
            $.Retina && $('#wa-app').find('img:not(.f-retina-off)').addClass('f-retina-off').retina();
        },

        loc: function(p1, p2) {
            var res = $_(p1, p2) || '';
            if (p2) {
                res = res.replace('%d', p1);
            }
            return res;
        },

        ucfirst: function(str) {
            str = str || '';
            if (!str) {
                return '';
            }
            return str.slice(0, 1).toUpperCase() + str.slice(1);
        },

        lcfirst: function(str) {
            str = str || '';
            if (!str) {
                return '';
            }
            return str.slice(0, 1).toLowerCase() + str.slice(1);
        },

        logError: function(er) {
            if (console) {
                if (console.error) {
                    console.error(er);
                } else {
                    console.log(er);
                }
            }
        },

        runCopyFiles: function () {
            if (!$.files.config.canRunCopyFiles()) {
                return;
            }

            var coef = Math.floor(Math.random() * 100) / 100;
            var settings = {
                id: ('' + Math.random()).slice(2),
                delay: 10000 + coef * 5000,
                timer: null,
                xhr: null
            };

            var process = function () {
                try {

                    settings.timer && clearTimeout(settings.timer);
                    settings.xhr && settings.xhr.abort();

                    var run = function () {
                        if (!$.files.config.canRunCopyFiles()) {
                            settings.timer && clearTimeout(settings.timer);
                            settings.xhr && settings.xhr.abort();
                            return;
                        }
                        console.log('copytask [' + settings.id + '] start');
                        settings.xhr = $.files.jsonPost('?module=copy&action=task&background_process=1&process_id=' + settings.id);
                        settings.xhr.always(function () {
                            settings.xhr = null;
                            settings.timer = setTimeout(run, settings.delay);
                            console.log('copytask [' + settings.id + '] end');
                        });
                        settings.xhr.error(function () {
                            return false;
                        });
                    };

                    run();
                    settings.timer = setTimeout(run, settings.delay);
                } catch (Error) {
                    console.error(['runCopyFiles fail', Error]);
                }
            };

            process();


        },

        checkBackendChanges: function (background) {
            var current_hash = $.files.controller.getCurrentHash(2),
                current_hash_ar = current_hash.split('/');

            var hashes = $.files.config.getHashesForChangeListener();
            var allowed = false;
            $.each(hashes, function (i, hash) {
                if (current_hash_ar[0] == hash) {
                    allowed = true;
                    return false;
                }
            });

            if (!allowed) {
                return;
            }

            let checkChangesRequestUrl = '?module=backend&action=changes';

            if (background) {
                checkChangesRequestUrl += '&background_process=1';
            }

            return $.files.jsonPost(checkChangesRequestUrl,  { hash: current_hash })
                .done(function (r) {

                    if (r.status !== 'ok') {
                        return;
                    }

                    var getTime = function (datetime) {
                        return new Date(datetime || '1970-01-01').getTime();
                    };

                    var itemUpdate = function (type, data) {

                        var list_info = $.files.config.getPageInfo(current_hash);

                        var reload = false;

                        if ((list_info === null && data !== null) || (list_info !== null && data === null)) {
                            reload = true;
                        }

                        list_info = list_info || {};
                        data = data || {};

                        if (!reload) {
                            var cur_update_ts = getTime(data.update_datetime);
                            var prev_update_ts = getTime(list_info['update_datetime']);
                            if (cur_update_ts > prev_update_ts) {
                                reload = true;
                            }
                        }
                        if (!reload) {
                            var cur_count = data.count;
                            var prev_count = list_info['count'];
                            if (cur_count != prev_count) {
                                reload = true;
                            }
                        }
                        if (!reload) {
                            var cur_size = data.size;
                            var prev_size = list_info['size'];
                            if (cur_size != prev_size) {
                                reload = true;
                            }
                        }

                        $.files.config.setPageInfo(current_hash, data);
                        return reload;
                    };

                    // check changes for list
                    var data = r.data;
                    if (itemUpdate(current_hash, data.info) && data.info?.count > 0) {
                        $.files.controller.reloadPage();
                        $.files.refreshSidebar();
                    }

                })
                .error(function () {
                    return false;
                });
        },

        peformTasks: function () {
            return $.files.jsonPost('?module=backend&action=tasksPerform&background_process=1');
        },

        runTasksPerformer: function () {
            try {
                var coef = Math.floor(Math.random() * 100) / 100;
                var delay = 10000 + coef * 5000;
                var method = this.peformTasks;
                if (method.timer) {
                    clearTimeout(method.timer);
                    method.xhr.abort();
                }
                method.delay = delay;
                var run = function () {
                    if (method.xhr) {
                        return;
                    }
                    method.xhr = $.files.peformTasks()
                        .always(function () {
                            method.xhr = null;
                            method.timer = setTimeout(run, method.delay);
                        });
                };
                run();
            } catch (Error) {
                console.error(['runTasksPerformer fail', Error]);
            }
        },

        runBackendChanges: function () {
            try {
                var coef = Math.floor(Math.random() * 100) / 100;
                var delay = 30000 + coef * 5000;
                var method = this.runBackendChanges;
                if (method.timer) {
                    clearTimeout(method.timer);
                    method.xhr.abort();
                }
                method.delay = delay;
                var run = function () {

                    if (method.xhr) {
                        return;
                    }

                    method.xhr = $.files.checkBackendChanges(true);
                    if (method.xhr) {
                        method.xhr.always(function () {
                            method.xhr = null;
                            method.timer = setTimeout(run, method.delay);
                        })
                    } else {
                        method.xhr = null;
                        method.timer = setTimeout(run, method.delay);
                    }

                };
                run();
            } catch (Error) {
                console.error(['runBackendChanges fail', Error]);
            }
        },

        runSyncWithSources: function () {
            if (this.config.getSourceCount() <= 0 && this.runSyncWithSources.work) {
                return;
            }

            if (!$.files.config.canRunSyncWithSources()) {
                return;
            }

            this.runSyncWithSources.work = true;
            try {
                var coef = Math.floor(Math.random() * 100) / 100;
                var delay = 10000 + coef * 5000;
                var method = this.runSyncWithSources;
                if (method.timer) {
                    clearTimeout(method.timer);
                    method.xhr && method.xhr.abort();
                }
                method.delay = delay;
                var run = function () {

                    var hash = location.hash;
                    hash = (hash || '' ).replace(/(^[^#]*#\/*|\/$)/g, '');
                    if (hash.indexOf('source/') >= 0) {
                        // not running sync on source page (prevent race condition when first data pulling)
                        method.xhr = null;
                        method.timer = setTimeout(run, method.delay);
                        return;
                    }

                    if (method.paused) {
                        return;
                    }
                    if (method.xhr) {
                        return;
                    }

                    if (!$.files.config.canRunSyncWithSources()) {
                        method.timer && clearTimeout(method.timer);
                        method.xhr && method.xhr.abort();
                        return;
                    }

                    var folder_id = parseInt($.files.controller.getCurrentFolderId(), 10) || 0;
                    var storage_id = parseInt($.files.controller.getCurrentStorageId(), 10) || 0;
                    method.xhr = $.files.jsonPost('?module=source&action=sync&background_process=1', {
                        folder_id: folder_id,
                        storage_id: storage_id
                    });
                    method.xhr
                        .done(function (r) {
                            if (r.status === 'ok' && r.data && r.data.hasOwnProperty('source_id')) {
                                var source_id = $.files.config.getSourceId();
                                if (source_id > 0 && r.data.source_id === source_id) {
                                    $.files.controller.reloadPage();
                                }
                            }
                        })
                        .always(function () {
                            method.xhr = null;
                            method.timer = setTimeout(run, method.delay);
                        })
                        .error(function () {
                            return false;
                        });
                };

                run();

                method.pause = function () {
                    method.paused = true;
                };

                method.unpause = function () {
                    method.paused = false;
                };

            } catch (Error) {
                this.runSyncWithSources.work = false;
                console.error(['runSyncWithSources fail', Error]);
            }
        },

        runBackgroundTasks: function () {

            var tasks = [
                this.runTasksPerformer,
                this.runCopyFiles,
                this.runSyncWithSources,
                this.runBackendChanges
            ];

            var flatted = [];

            for (var i = 0; i < tasks.length; i += 1) {
                if ($.isArray(tasks[i])) {
                    var task = tasks[i][0];
                    var times = tasks[i][1];
                    for (var t = 0; t < times; t += 1) {
                        flatted.push(task);
                    }
                } else {
                    flatted.push(tasks[i]);
                }
            }

            tasks = flatted;
            tasks = tasks.sort(function () {
                return Math.random() - Math.random();
            });

            for (var i = 0; i < tasks.length; i += 1) {
                (function(task) {
                    var coef = Math.floor(Math.random() * 100) / 100;
                    var delay = coef * 500;
                    setTimeout(function () {
                        task.apply($.files);
                    }, delay);
                })(tasks[i]);
            }
        },

        deleteSource: function(id) {
            return $.files.jsonPost({
                url: '?module=source&action=delete',
                data: { id: id },
                onSuccess: function(r) {
                    if (r.status === 'ok') {
                        $.files.refreshSidebar();
                        $.files.controller.gotoDefaultPage();
                    }
                }
            });
        },

        formatFileSize: function (bytes) {
            if (typeof bytes !== 'number') {
                return '';
            }
            if (bytes >= 1000000000) {
                return $.files.formatNumber(bytes / 1000000000, 2) + ' ' + $_('GB');
            }
            if (bytes >= 1000000) {
                return $.files.formatNumber(bytes / 1000000, 2) + ' ' + $_('MB');
            }
            return $.files.formatNumber(bytes / 1000, 2) + ' ' + $_('KB');
        },

        /**
         * Convert Number to string representation with local dependent decimal point ('.', ',' ... )
         *
         * @param {Number} number
         * @param {Number} | {undefined} If input number suppose to be decimal it is digits after point
         *
         * @see Number.prototype.toFixed
         *
         * @returns {String}
         */
        formatNumber: function (number, digits) {
            if (!(typeof number === 'number')) {
                return '';
            }

            var str_number = '';
            if (typeof digits === 'number') {
                str_number = number.toFixed(digits);
            } else {
                str_number = '' + number;
            }

            var locale = $.files.config.getLocale();
            if (locale === 'ru_RU') {
                str_number = str_number.replace('.', ',');
            }

            return str_number;
        },

        detectFlexWrap(itemsWrapperSelector, wrappedClass= 'is-wrapped'){
            const assignRows = (items) => {
                let row = 0;
                let odd = true;

                [...items.children].forEach((el) => {
                    if (!el.previousElementSibling || el.offsetLeft < el.previousElementSibling.offsetLeft) {
                        row++;
                        odd = !odd;
                    }

                    el.classList.toggle(wrappedClass, odd);
                });

                items.classList.toggle('has-wrapped', row > 1);
            };

            const observer = new ResizeObserver((entries) => {
                entries.forEach((entry) => {
                    assignRows(entry.target);
                });
            });

            const items = document.querySelector(itemsWrapperSelector);
            observer.observe(items);
            assignRows(items);
        }

    });
})(jQuery);
