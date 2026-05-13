(function() {
    $.fn.filesDialog = function (options, val) {
        var d = $(this);

        if (options === 'height' || options === 'width') {
            if ($.isNumeric(val)) {
                d.find('.dialog-window').css(
                    options === 'height' ?
                        {
                            height: val,
                            minHeight: val
                        } :
                        {
                            width: val,
                            minWidth: val
                        }
                );
                return;
            } else {
                return options === 'height' ? d.find('.dialog-window').height() : d.find('.dialog-window').width();
            }
        }

        options = options || {};
        if (typeof options.onFirstLoad === 'function')
        {
            var onLoad = options.onLoad || function() {};
            var onFirstLoad = options.onFirstLoad;
            options.onLoad = function() {
                var args = Array.prototype.slice.call(arguments);
                onLoad.counter = onLoad.counter || 0;
                if (!onLoad.counter) {
                    onFirstLoad.apply(this, args);
                }
                onLoad.apply(this, args);
                onLoad.counter += 1;
            };
        }
        var open = function() {
            d.waDialog(options);
        };
        d.bind('open', open);
        open();
    };
})(jQuery);