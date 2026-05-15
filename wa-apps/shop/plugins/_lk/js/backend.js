(function($) {
    function normalizeRoute(route) {
        route = $.trim(route || '');
        route = route.replace(/^\/+|\/+$/g, '');
        return route;
    }

    function ensureSlash(url) {
        url = $.trim(url || '');
        return url && url.slice(-1) !== '/' ? url + '/' : url;
    }

    function buildUrl($row) {
        var base = ensureSlash($row.data('storefront-url') || '');
        var b2b = $row.find('.js-lk-b2b').prop('checked');
        var route = normalizeRoute($row.find('.js-lk-route-input').val());

        if (b2b) {
            return base;
        }

        if (!route) {
            route = 'my';
        }

        return base + route + '/';
    }

    function updateRow($row) {
        var enabled = $row.find('.js-lk-enabled').prop('checked');
        var b2b = $row.find('.js-lk-b2b').prop('checked');
        var $route = $row.find('.js-lk-route-input');
        var route = normalizeRoute($route.val());

        if (b2b) {
            if (route) {
                $route.data('previous-route', route);
            }
            $route.val('').prop('disabled', true).attr('placeholder', 'от корня');
            $row.find('.js-lk-route-hint').text('B2B включен: кабинет забирает всю витрину от корня поселения.');
        } else {
            $route.prop('disabled', false).attr('placeholder', 'my');
            if (!route) {
                route = normalizeRoute($route.data('previous-route')) || 'my';
                $route.val(route);
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
        $('.js-lk-storefront-row').each(function() {
            updateRow($(this));
        });
    }

    function openModal(id) {
        var $modal = $('#' + id);
        if (!$modal.length) {
            return;
        }
        $modal.show().attr('aria-hidden', 'false').addClass('is-open');
        $('body').addClass('lk-modal-open');
        setTimeout(function() {
            $modal.find('input, select, textarea, button').filter(':visible').first().trigger('focus');
        }, 0);
    }

    function closeModal(id) {
        var $modal = $('#' + id);
        if (!$modal.length) {
            return;
        }
        $modal.removeClass('is-open').attr('aria-hidden', 'true').hide();
        if (!$('.js-lk-modal.is-open').length) {
            $('body').removeClass('lk-modal-open');
        }
    }

    function beforeSubmit() {
        // Disabled inputs are not submitted. Keep route disabled in B2B mode, because server
        // intentionally treats b2b_mode=1 as root ownership and ignores route value.
        $('.js-lk-storefront-row').each(function() {
            var $row = $(this);
            if (!$row.find('.js-lk-b2b').prop('checked')) {
                var $route = $row.find('.js-lk-route-input');
                if (!normalizeRoute($route.val())) {
                    $route.val('my');
                }
            }
        });
    }

    $(document)
        .on('change', '.js-lk-enabled, .js-lk-b2b', function() {
            updateRow($(this).closest('.js-lk-storefront-row'));
        })
        .on('input change', '.js-lk-route-input', function() {
            updateRow($(this).closest('.js-lk-storefront-row'));
        })
        .on('click', '.js-lk-open-modal', function(e) {
            e.preventDefault();
            openModal($(this).data('modal'));
        })
        .on('click', '.js-lk-close-modal', function(e) {
            e.preventDefault();
            closeModal($(this).data('modal'));
        })
        .on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.js-lk-modal.is-open').each(function() {
                    closeModal(this.id);
                });
            }
        })
        .on('submit', '.js-lk-settings-form', beforeSubmit);

    $(initRows);
})(jQuery);
