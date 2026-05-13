(function($) {
    $.files = $.files || {};
    $.files.config = {
        init: function(options) {
            var opts = (options || {});
            for (var k in opts) {
                if (opts.hasOwnProperty(k)) {
                    this['__' + k] = opts[k];
                    var parts = k.split('_');
                    var getter = 'get';
                    for (var i = 0; i < parts.length; i += 1) {
                        getter += parts[i].slice(0, 1).toUpperCase() + parts[i].slice(1);
                    }
                    if (!this[getter]) {
                        (function(k, self) {
                            self[getter] = function() {
                                return this['__' + k];
                            };
                        })(k, this);
                    }
                }
            }
        },
        set: function(key, value) {
            if ($.isPlainObject(key)) {
                this.init(key);
            } else {
                var opts = {};
                opts[key] = value;
                this.init(opts);
            }
        },
        getLocaleStorage: function() {
            this.__storage = this.__storage || new $.store();
            return this.__storage;
        },
        getLocale: function () {
            this.__locale = this.__locale || 'en_US';
            return this.__locale;
        },
        getSidebar: function() {
            return $("#" + this.__sidebar_id);
        },
        getMainContainer: function() {
            return $("#" + this.__main_container_id);
        },
        getFileList: function() {
            return $("#" + this.__file_list_id);
        },
        getStatisticsContainer: function() {
            return $('#' + this.__statistics_container_id);
        },
        setSourceId: function (source_id) {
            this.__source_id = source_id;
        },
        getSourceId: function () {
            return this.__source_id || 0;
        },
        getSourceCount: function () {
            return this.__source_count || 0;
        },
        setSourceCount: function (source_count) {
            this.__source_count = parseInt(source_count, 10) || 0;
        },
        canRunCopyFiles: function (can) {

            // by default this option is true
            if (this.__can_run_copy_files === undefined) {
                this.__can_run_copy_files = true;
            }

            // if arguments is empty - this method is setter, otherwise getter
            var is_setter_mode = can !== undefined;
            if (is_setter_mode) {
                this.__can_run_copy_files = can;
            }

            return this.__can_run_copy_files;
        },
        canRunSyncWithSources: function (can) {

            // by default this option is true
            if (this.__can_run_sync_with_sources === undefined) {
                this.__can_run_sync_with_sources = true;
            }

            // if arguments is empty - this method is setter, otherwise getter
            var is_setter_mode = can !== undefined;
            if (is_setter_mode) {
                this.__can_run_sync_with_sources = can;
            }

            return this.__can_run_sync_with_sources;
        },
        setPageInfo: function (hash, info) {
            this['_list_info'] = this['_list_info'] || {};
            this['_list_info'][hash] = this['_list_info'][hash] || {};
            this['_list_info'][hash] = $.extend(this['_list_info'][hash], info);
        },
        getPageInfo: function (hash) {
            this['_list_info'] = this['_list_info'] || {};
            this['_list_info'][hash] = this['_list_info'][hash] || {};
            return this['_list_info'][hash];
        }
    };
})(jQuery);
