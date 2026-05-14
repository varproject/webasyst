(function($) {
    function normalizeRoute(route) {
        route = $.trim(route || '');
        route = route.replace(/^\/+|\/+$/g, '');
        return route;
    }

    function buildUrl($row) {
        var raw = $row.data('base-url') || '';
        var parts = raw.split('/');
        var domain = parts.shift() || '';
        var shopUrl = parts.join('/');
        var scheme = window.location.protocol + '//';
        var root = window.location.pathname.replace(/\/webasyst\/.*/, '/');
        if (root === window.location.pathname) {
            root = '/';
        }
        var base = scheme + domain + root + (shopUrl ? shopUrl.replace(/^\/+|\/+$/g, '') + '/' : '');
        var b2b = $row.find('.js-lk-b2b').prop('checked');
        var route = normalizeRoute($row.find('.js-lk-route-input').val());
        if (b2b || !route) {
            return b2b ? base : base + 'my/';
        }
        return base + route + '/';
    }

    function updateRow($row) {
        var enabled = $row.find('.js-lk-enabled').prop('checked');
        var b2b = $row.find('.js-lk-b2b').prop('checked');
        var $route = $row.find('.js-lk-route-input');
        var route = normalizeRoute($route.val());

        if (b2b) {
            $route.val('').prop('disabled', true).attr('placeholder', 'от корня');
            $row.find('.js-lk-route-hint').text('B2B включен: кабинет забирает всю витрину от корня поселения.');
        } else {
            $route.prop('disabled', false).attr('placeholder', 'my');
            if (!route) {
                $route.val('my');
            }
            $row.find('.js-lk-route-hint').text('Обычный магазин работает от корня, кабинет — по указанному адресу.');
        }

        $row.toggleClass('is-enabled', enabled);
        $row.toggleClass('is-disabled', !enabled);
        $row.toggleClass('is-b2b', enabled && b2b);
        $row.find('.js-lk-mode').text(b2b ? 'Вся витрина' : 'Только ЛК');
        $row.find('.js-lk-status').text(enabled ? (b2b ? 'B2B' : 'ЛК') : 'Выкл');
        $row.find('.js-lk-front-link').attr('href', buildUrl($row));
    }

    function initRows() {
        $('.js-lk-storefront-row').each(function() { updateRow($(this)); });
    }

    $(document)
        .on('change', '.js-lk-enabled, .js-lk-b2b', function() {
            updateRow($(this).closest('.js-lk-storefront-row'));
        })
        .on('input change', '.js-lk-route-input', function() {
            updateRow($(this).closest('.js-lk-storefront-row'));
        })
        .on('click', '.js-lk-toggle-details', function(e) {
            e.preventDefault();
            $('#' + $(this).data('target')).toggle();
        });

    $(initRows);
})(jQuery);
