(function ($) {
    'use strict';

    $(document).on('change', '.js-lk-storefront-select', function () {
        var $option = $(this).find(':selected');

        $('.js-lk-new-domain').val($option.data('domain') || '');
        $('.js-lk-new-shop-url').val($option.data('shop-url') || '');
    });
})(jQuery);