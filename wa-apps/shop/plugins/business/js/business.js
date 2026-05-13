$.shop.business = {
    app_fields: {}
};
$.shop.business = {
    fillFormPrefills: function () {
        $.get('?plugin=business&action=getFormPrefills', function (r) {
            if (r.status !== 'ok' || typeof r.data !== 'object') {
                return;
            }

            $.shop.business.app_fields = r.data;
            for(const id in r.data) {
                const val = r.data[id];
                $(':input[data-app-field="'+id+'"]').val(val);
            }
            $(document).trigger('loaded_form_prefills.business');
        });
    },
    updateFormPrefills: function ($form) {
        const d = $.Deferred();
        const data = {};
        $form.find(':input[data-app-field]').each(function () {
            const val = $(this).val();
            const prop = $(this).data('app-field');
            if (val !== $.shop.business.app_fields[prop]) {
                data[prop] = val;
            }
        });

        if (Object.keys(data).length > 0) {
            return $.post('?plugin=business&action=saveFormPrefills', data, function () {
                d.resolve(true);
                if ($('.s-business-lead-form').length > 1) {
                    $.shop.business.fillFormPrefills();
                }
            });
        } else {
            d.resolve(false);
        }

        return d.promise();
    }
};

$(function () {
    $.shop.business.fillFormPrefills();

    // EVENTS
    $('.js-hide-business-lead-form').on('click', function () {
        $(this).closest('.s-business-lead-form').hide();
    });
    $('.js-collapse-business-lead-form').on('click', function () {
        $(this).closest('.s-business-lead-form').toggleClass('is-collapsed')
            .find('.s-business-lead-form__content').toggle();
        const $svg = $(this).children('svg');
        $svg.toggleClass('fa-chevron-up fa-chevron-down');
    });

    $('.js-toggle-collasible').on('click', function () {
        const $container = $(this).closest('.js-collasible-container');
        const $content = $container.children('.js-collasible-content');
        $content.slideToggle();
        $container.find('.js-collapsable-toggle-caret svg').toggleClass('fa-caret-right fa-caret-down');
        return false;
    });
});
