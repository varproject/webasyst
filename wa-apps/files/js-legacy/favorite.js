(function($) {

    $.fn.favorite = function(selector, options, ext) {
        var context = this;

        var settings = context.data('favoriteSettings') ||
            $.extend({
                url: '',
                onSuccess: function(r) {},
                onFailure: function(r) {},
                serialize: function(el) {}
            }, options || {});
        context.data('favoriteSettings', settings);


        var star_clz = 'star-empty star-hover star';

        var prefix = context.data('favoriteSettingsPrefix') || ('favorite-' + ('' + Math.random()).slice(2));
        context.data('favoriteSettingsPrefix', prefix);

        var getIcon = function(el) {
            return el.find('.icon16');
        };

        var initIcon = context.data('favoriteSettingsInitIcon')  ||
                function(el) {
                    var icon = getIcon(el);
                    icon.removeClass(star_clz + ' loading');
                    if (el.hasClass(prefix + '-loading')) {
                        icon.addClass('loading');
                    } else if (el.hasClass(prefix + '-favorite')) {
                        icon.addClass('star');
                    } else {
                        icon.addClass('star-empty');
                    }
                };
        context.data('favoriteSettingsInitIcon', initIcon);

        if (options === 'init') {
            context.find(selector).each(function() {
                var el = $(this);
                var icon = getIcon(el);
                if (icon.hasClass('star')) {
                    el.addClass(prefix + '-favorite');
                }
                initIcon($(this));
            });
            return;
        }

        var setStatus = function(el, status) {
            el.removeClass(prefix + '-loading ' + prefix + '-favorite');
            if (status === 'loading') {
                el.addClass(prefix + '-loading');
            } else if (status === 'favorite') {
                el.addClass(prefix + '-favorite');
            }
            initIcon(el);
        };

        var getStatus = function(el) {
            if (el.hasClass(prefix + '-loading')) {
                return 'loading';
            } else if (el.hasClass(prefix + '-favorite')) {
                return 'favorite';
            } else {
                return;
            }
        };

        var sendRequest = function(el, onSuccess, onFail) {
            $.get((settings.url || ''), settings.serialize(el), 'json')
                .success(function(r) {
                    if (r && r.status === 'ok') {
                        onSuccess && onSuccess(r);
                    } else {
                        onFail && onFail(r);
                    }
                })
                .fail(function(r) {
                    onFail && onFail(r);
                });
        };

        context
            .on('mouseenter', selector, function() {
                var el = $(this);
                var icon = getIcon(el);
                if (el.hasClass(prefix + '-loading')) return;
                icon.removeClass(star_clz).addClass('star-hover');
            })
            .on('mouseleave', selector, function() {
                var el = $(this);
                var icon = getIcon(el);
                if (el.hasClass(prefix + '-loading')) return;
                icon.removeClass(star_clz).addClass(el.hasClass(prefix + '-favorite') ? 'star' : 'star-empty');
            })
            .on('click', selector, function() {
                var el = $(this);
                if (getStatus(el) === 'loading') {
                    return;
                }
                setStatus(el, 'loading');
                sendRequest(
                    el,
                    function(r) {
                        var status = null;
                        if (settings.onSuccess) {
                            status = settings.onSuccess.apply(el, [r]);
                        }
                        if (typeof status === 'string') {
                            setStatus(el, status);
                        } else {
                            setStatus(el, 'favorite');
                        }
                    },
                    function(r) {
                        if (settings.onFailure) {
                            settings.onFailure.apply(el, [r]);
                        }
                    });
            });

        return context;

    };
})(jQuery);
