(function ($) {

    $.files = $.files || {};
    $.files.controller = $.extend($.files.controller || {}, {

        hashes: [],

        // init js controller
        init: function (options) {

            var self = this;
            self.options = $.extend(self.options, options || {});

            // Init dispatcher based on location.hash
            if (typeof($.History) != "undefined") {
                $.History.bind($.files.controller.dispatch);

                $.History.unbind = function (state, handler) {
                    if (handler) {
                        if ($.History.handlers.specific[state]) {
                            $.each($.History.handlers.specific[state], function (i, h) {
                                if (h === handler) {
                                    $.History.handlers.specific[state].splice(i, 1);
                                    return false;
                                }
                            });
                        }
                    } else {
                        // We have a generic handler
                        handler = state;
                        $.each($.History.handlers.generic, function (i, h) {
                            if (h === handler) {
                                $.History.handlers.generic.splice(i, 1);
                                return false;
                            }
                        });
                    }
                };
            }

            // if empty hash try go to last hash
            var hash = window.location.hash;
            if (hash === '#/' || !hash) {

                // get last hash from local storage
                var locale_storage = $.files.config.getLocaleStorage();
                var contact_id = $.files.config.getContactId();
                var settings = locale_storage.get('files/settings/' + contact_id) || {};
                if (settings.last_hash) {
                    $.files.load.onError = function() {
                        self.gotoDefaultPage(true);
                        delete $.files.load.onError;
                    };
                    self.gotoPage(settings.last_hash);
                } else {
                    self.dispatch();
                }

            }
        },

        // Change location hash without triggering dispatch
        forceHash: function (hash) {
            hash = $.files.controller.cleanHash(hash);
            if ($.files.controller.currentHash !== hash) {
                $.files.controller.currentHash = hash;
                $.wa.setHash(hash);
            }
        },

        // history.back() without triggering dispatch. Run callback when hash changes.
        // Used to go back after deletion so that we won't end up in non-existing page.
        backWithoutDispatch: function (callback) {
            var h;
            if (callback) {
                $.History.bind(h = function () {
                    $.History.unbind(h);
                    callback();
                });
            }

            this.skipDispatch = 1;
            history.back();
        },

        // Dispatch again based on current hash
        redispatch: function () {
            this.currentHash = null;
            this.dispatch();
        },

        /**
         * @param {Object=} options
         */
        doRedispatch: function (options) {
            this.currentHash = null;
            this.doDispatch(options);
        },

        // if this is > 0 then this.dispatch() decrements it and ignores a call
        skipDispatch: 0,

        // last hash processed by this.dispatch()
        currentHash: null,

        cleanHash: function (hash) {
            if (typeof hash == 'undefined') {
                hash = window.location.hash.toString();
            }

            if (!hash.length) {
                hash = '' + hash;
            }
            while (hash.length > 0 && hash[hash.length - 1] === '/') {
                hash = hash.substr(0, hash.length - 1);
            }
            hash += '/';

            if (hash[0] != '#') {
                if (hash[0] != '/') {
                    hash = '/' + hash;
                }
                hash = '#' + hash;
            } else if (hash[1] && hash[1] != '/') {
                hash = '#/' + hash.substr(1);
            }

            if (hash == '#/') {
                return '';
            }
            /*
            try {
                // Fixes behaviour of Safari and possibly other browsers
                hash = decodeURIComponent(hash);
            } catch (e) {
            }*/

            hash = (hash || '' ).replace(/(^[^#]*#\/*|\/$)/g, '');

            return hash;
        },

        // dispatch call method by hash
        dispatch: function (hash) {
            if (this !== $.files.controller) {
                return $.files.controller.dispatch(hash);
            }
            return this.doDispatch({
                hash: hash,
                afterActionApply: function () {
                    $("html, body").animate({scrollTop: 0}, 200);
                }
            });
        },

        /**
         * Doing dispatch itself
         * @param {Object=} options
         * @param {String=} options.hash
         * @param {Function=} options.afterActionApply
         * @return {boolean|*}
         */
        doDispatch: function (options) {
            options = $.isPlainObject(options) ? options : {};

            var hash = options.hash;

            if ($.files.controller.skipDispatch > 0) {
                $.files.controller.skipDispatch--;
                return false;
            }

            hash = $.files.controller.cleanHash(hash || undefined);
            if ($.files.controller.currentHash == hash) {
                return;
            }

            var old_hash = $.files.controller.currentHash;
            $.files.controller.currentHash = hash;

            // Fire an event allowing to prevent navigation away from current hash
            var e = new $.Event('wa_before_dispatched');
            $(window).trigger(e);
            if (e.isDefaultPrevented()) {
                $.files.controller.currentHash = old_hash;
                $.wa.setHash(old_hash);
                return false;
            }

            // save hashes history
            this.hashes.push(hash);
            var slice_threshold = 100;
            if (this.hashes.length > slice_threshold) {
                this.hashes.slice(Math.round(slice_threshold / 2));
            }

            // save last hash in local storage
            var locale_storage = $.files.config.getLocaleStorage();
            var last_hash = this.hashes[this.hashes.length - 1];
            var contact_id = $.files.config.getContactId();
            var settings = locale_storage.get('files/settings/' + contact_id) || {};
            settings.last_hash = last_hash;
            locale_storage.set('files/settings/' + contact_id, settings);

            if (hash) {
                // clear hash

                hash = hash.split('/');

                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else {
                                var checkActionName = actionName + h.substr(0, 1).toUpperCase() + h.substr(1);
                                if ($.files.controller[checkActionName + 'Action']) {
                                    actionName = checkActionName;
                                } else {
                                    attrMarker = i;
                                }
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);
                    // call action if it exists
                    if ($.files.controller[actionName + 'Action']) {
                        $.files.controller.currentAction = actionName;
                        $.files.controller.currentActionAttr = attr;
                        $.files.controller[actionName + 'Action'].apply($.files.controller, attr);

                        if (typeof options.afterActionApply === "function") {
                            options.afterActionApply();
                        }

                    } else {
                        if (console) {
                            console.log('Invalid action name:', actionName + 'Action');
                        }
                    }
                } else {
                    // call default action
                    $.files.controller.defaultAction();
                }
            } else {
                // call default action
                $.files.controller.defaultAction();
            }
        },

        getCurrentHash: function (slice) {
            var hash = this.currentHash || '';
            slice = parseInt(slice, 10);
            if (isNaN(slice) || slice <= 1) {
                return hash;
            }
            hash = hash.split('/');
            return hash.slice(0, slice).join('/');
        },

        isAllListHash: function () {
            var hash_ar = this.getCurrentHash().split('/');
            return hash_ar[0] === '' || hash_ar[0] === 'all';
        },

        getIdOfTypeFromHash: function(type, hash) {
            if (!type || typeof type !== 'string') {
                return null;
            }
            hash = hash || '';
            var reg = new RegExp('^' + type + '\\/([\\d]+)$');
            var m = hash.match(reg);
            var id = m && parseInt(m[1], 10);
            if (typeof id !== 'number' || isNaN(id)) {
                return null;
            }
            return id;
        },

        getCurrentIdOfType: function (type) {
            var hash = this.getCurrentHash();
            return this.getIdOfTypeFromHash(type, hash);
        },

        getCurrentFolderId: function () {
            return this.getCurrentIdOfType('folder');
        },

        getCurrentStorageId: function () {
            return this.getCurrentIdOfType('storage');
        },

        getCurrentFileId: function () {
            return this.getCurrentIdOfType('file');
        },

        getPreviousFolderId: function() {
            var len = this.hashes.length;
            if (len <= 1) {
                return null;
            }
            var hash = this.hashes[len - 2];
            return this.getIdOfTypeFromHash('folder', hash);
        },

        getPreviousStorageId: function() {
            var len = this.hashes.length;
            if (len <= 1) {
                return null;
            }
            var hash = this.hashes[len - 2];
            return this.getIdOfTypeFromHash('storage', hash);
        },

        load: function (url, callback, options) {
            $.files.load(url, function() {
                callback && callback();
                $.files.retina();
                $.files.controller.afterLoadOnce && $.files.controller.afterLoadOnce();
                delete $.files.controller.afterLoadOnce;
            }, $.extend({}, this.options, options));
        },

        reloadPage: function (force) {
            var e = new $.Event('wa_before_reload_page');
            $(window).trigger(e);
            if (!e.isDefaultPrevented()) {
                if (force) {
                    location.reload();
                } else {
                    this.redispatch();
                }
            }
        },

        gotoPage: function (hash) {
            hash = this.cleanHash(hash);
            if (this.getCurrentHash() === hash) {
                this.redispatch();
            } else {
                location.hash = '#/' + (hash ? hash + '/' : '');
            }
        },

        replaceHashPrefix: function(hash, slug) {
            hash = $.files.controller.cleanHash(hash);
            slug = $.files.controller.cleanHash(slug);
            var hash_parts = hash.split('/');
            var slug_parts = slug.split('/');
            var res_hash = slug_parts.join('/') + '/' + hash_parts.slice(slug_parts.length);
            return $.files.controller.cleanHash(res_hash);
        },

        prepareHashForSomePage: function(hash, pure) {
            if (!pure) {
                var current_hash = this.getCurrentHash();
                hash = this.replaceHashPrefix(current_hash, hash);
            }
            return hash;
        },

        gotoStoragePage: function (id, pure) {
            this.gotoPage(this.prepareHashForSomePage('storage/' + id + '/', pure));
        },

        gotoFolderPage: function (id, pure) {
            this.gotoPage(this.prepareHashForSomePage('folder/' + id + '/', pure));
        },

        gotoSearchPage: function (hash, pure) {
            this.gotoPage(this.prepareHashForSomePage('search/' + hash + '/', pure));
        },

        gotoFilterPage: function (id, pure) {
            this.gotoPage(this.prepareHashForSomePage('filter/' + id + '/', pure));
        },

        gotoSourcePage: function(id) {
            this.gotoPage(this.prepareHashForSomePage('source/' + id + '/', true));
        },

        gotoDefaultPage: function (pure) {
            if (pure) {
                this.gotoPage('');
            } else {
                // todo: if we redirected from storage/<id> to default page, this <id> is saved.
                // todo: fix it (but very accurate)
                this.gotoPage(this.prepareHashForSomePage('all', pure));
            }
        },

        gotoLastList: function() {
            var hashes = this.hashes.slice(0, -1);
            var len = hashes.length;
            var found_hash = '';
            for (var i = len - 1; i >= 0; i -= 1) {
                if (this.isHashOfListPage(hashes[i])) {
                    found_hash = hashes[i];
                    break;
                }
            }
            this.gotoPage(found_hash);
        },

        isHashOfListPage: function(hash) {
            hash = hash || '';
            var hash_parts = hash.split('/');
            var list_hashes = [
                '', 'all', 'storage', 'folder',
                'favorite', 'trash', 'search',
                'filter', 'tag'
            ];
            return list_hashes.indexOf(hash_parts[0]) >= 0;
        },


        // ===== CONTROLLER ACTIONS BEGIN ======

        defaultAction: function () {
            this.load('?module=backend&action=files');
        },

        allAction: function(params) {
            this.load('?module=backend&action=files' + (params ? '&' + params : ''));
        },

        storageAction: function (id, params) {
            this.load('?module=storage&action=files&id=' + id + (params ? '&' + params : ''));
        },

        storageNewAction: function() {
            this.load('?module=storage&action=create');
        },

        sourceAction: function(id, params) {
            this.load('?module=source&action=edit&id=' + id + (params ? '&' + params : ''));
        },

        sourceNewAction: function() {
            this.load('?module=source&action=edit');
        },

        folderAction: function (id, params) {
            this.load('?module=folder&action=files&id=' + id + (params ? '&' + params : ''));
        },

        fileAction: function (id) {
            this.load('?module=file&id=' + id);
        },

        favoriteAction: function (params) {
            this.load('?module=favorite&action=files' + (params ? '&' + params : ''));
        },

        trashAction: function (params) {
            this.load('?module=trash&action=files'  + (params ? '&' + params : ''));
        },

        searchAction: function (hash, params) {
            this.load('?module=search&action=files&hash=' + hash + (params ? '&' + params : ''));
        },

        filterAction: function (id, params) {
            this.load('?module=filter&action=files&id=' + id + (params ? '&' + params : ''));
        },

        filterNewAction: function() {
            this.load('?module=filter&action=create&acting_type=search');
        },

        tagAction: function(tags, params) {
            this.load('?module=tag&action=files&tags=' + tags + (params ? '&' + params : ''));
        },

        settingsAction: function () {
            this.load('?module=settings');
        },

        statisticsAction: function (action, params) {
            var that = this;

            // load helper
            var load = function(url) {
                that.load(url + (params ? '&' + params : ''),
                    null,
                    { container: $.files.config.getStatisticsContainer()}
                );
            };

            // local dispatcher
            var dispatch = function() {

                // select current li
                var menu = $.files.config.getStatisticsContainer().find('.f-statistics-menu');
                menu.find('.selected').removeClass('selected');
                var a = menu.find('a[href="#/statistics/' + action + '/"]');
                if (a.length) {
                    a.closest('li').addClass('selected');
                }

                // all menu actions
                var menu_actions = menu.find('a').map(function() {
                    var href = $(this).attr('href');
                    return href.replace('#/statistics/', '').replace('/', '');
                }).toArray();

                // if menu action exists
                if (menu_actions.indexOf(action) !== -1) {

                    // convert to camel style
                    if (action.indexOf('_') !== -1) {
                        action = $.map(action.split('_'), function(v, idx) {
                            return idx > 0 ? $.files.ucfirst(v) : v;
                        }).join('');

                    }

                    load('?module=statistics&action=' + action);
                } else {
                    $('.f-loading',  $.files.config.getStatisticsContainer()).hide();
                }
            };

            var block = $.files.config.getStatisticsContainer();
            if (!block.length) {
                that.load('?module=statistics', function() {
                    dispatch()
                });
            } else {
                dispatch();
            }

        },

        pluginsAction: function (plugin_id) {
            var $sidebar = $('#f-sidebar');
            $sidebar.find('li.selected').removeClass('selected');
            $sidebar.find('a[href="\#\/plugins\/"]:first').parent().addClass('selected');
            if (!$('#wa-plugins-content').length) {
                this.load('?module=plugins');
            }
        },

        crontasksAction: function () {
            this.load('?module=backend&action=crontasks');
        },

        sourceAction: function (id, params) {
            this.load('?module=source&action=info&id=' + id + (params ? '&' + params : ''));
        },

        sourceNewAction: function() {
            this.load('?module=source&action=create');
        }

        // ===== CONTROLLER ACTIONS END ======

    });

})(jQuery);
