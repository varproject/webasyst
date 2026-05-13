(function () {
    $.fn.filesDialog = function (options) {
        options = options || {};

        if (typeof options.onFirstLoad === 'function') {
            const onOpen = options.onOpen || function () {};
            const onFirstLoad = options.onFirstLoad;
            options.onOpen = function () {
                const args = Array.prototype.slice.call(arguments);
                onOpen.counter = onOpen.counter || 0;
                if (!onOpen.counter) {
                    onFirstLoad.apply(this, args);
                }
                onOpen.apply(this, args);
                onOpen.counter += 1;
            };
        }

        $.waDialog({$wrapper: this, ...options})
    };
})(jQuery);
