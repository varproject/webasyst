; (function ($) {
    'use strict';

    var selector = '.massda-delete-skus-legacy';

    function getSelectedProducts() {
        if (!$.product_list || typeof $.product_list.getSelectedProducts !== 'function') {
            return {
                count: 0,
                serialized: []
            };
        }

        return $.product_list.getSelectedProducts(true);
    }

    function getProductsHash(products) {
        var ids = [];
        var hash = '';

        $.each(products || [], function (i, product) {
            if (!product || !product.name) {
                return;
            }

            if (product.name === 'product_id[]') {
                ids.push(parseInt(product.value, 10));
            } else if (product.name === 'hash') {
                hash = product.value;
            }
        });

        if (!hash && ids.length) {
            hash = 'id/' + ids.join(',');
        }

        return hash;
    }

    function extractWrapper(html) {
        var $html = $('<div>').html($.trim(html));
        var $wrapper = $html.find('#massda-wrapper-container').first();

        return $wrapper.length ? $wrapper : $html;
    }

    function initDialog($dialog) {
        $dialog.off('.massdaLegacy');

        $dialog.on('submit.massdaLegacy', '#massda-run-form', function (e) {
            e.preventDefault();
            runProcess($dialog, $(this));
        });

        $dialog.on('click.massdaLegacy', '.js-massda-reload', function (e) {
            e.preventDefault();
            window.location.reload();
        });

        scheduleTick($dialog);
    }

    function runProcess($dialog, $form) {
        if (!$form.length || $form.data('locked')) {
            return;
        }

        var $loading = $dialog.find('.dialog-buttons i.loading:first');

        $form.data('locked', true);
        $loading.show();

        $.post($form.attr('action'), $form.serialize(), function (html) {
            if ($dialog.data('massda-closed')) {
                return;
            }

            var $wrapper = extractWrapper(html);

            $dialog.find('#massda-wrapper-container:first').replaceWith($wrapper);
            initDialog($dialog);
        }, 'html').always(function () {
            $loading.hide();
            $form.removeData('locked');
        });
    }

    function scheduleTick($dialog) {
        var $form = $dialog.find('#massda-process-form:first');
        if (!$form.length) {
            return;
        }

        if (String($form.data('is-finished')) === '1') {
            return;
        }

        window.setTimeout(function () {
            processTick($dialog);
        }, 50);
    }

    function processTick($dialog) {
        var $form;

        if (!$dialog.length || $dialog.data('massda-closed')) {
            return;
        }

        $form = $dialog.find('#massda-process-form:first');

        if (!$form.length || $form.data('locked')) {
            return;
        }

        if (String($form.data('is-finished')) === '1') {
            return;
        }

        $form.data('locked', true);

        $.post($form.attr('action'), $form.serialize(), function (html) {
            if ($dialog.data('massda-closed')) {
                return;
            }

            var $wrapper = extractWrapper(html);

            $dialog.find('#massda-wrapper-container:first').replaceWith($wrapper);
            initDialog($dialog);
        }, 'html').always(function () {
            $form.removeData('locked');
        });
    }

    function openDialog(url) {
        var products = getSelectedProducts();
        var products_hash = getProductsHash(products.serialized || []);

        if (!products_hash) {
            alert('Сначала выберите товары.');
            return;
        }

        if (!$.fn.waDialog) {
            alert('Не подключен wa.dialog.js');
            return;
        }

        $.post(url, { products_hash: products_hash }, function (html) {
            var $dialog = $(html);

            $dialog.waDialog({
                disableButtonsOnSubmit: false,
                onLoad: function () {
                    initDialog($(this));
                },
                onCancel: function () {
                    $(this).data('massda-closed', true);
                    $(this).remove();
                },
                onClose: function () {
                    $(this).data('massda-closed', true);
                    $(this).remove();
                }
            });
        }, 'html');
    }

    $(document).on('click', selector, function (e) {
        e.preventDefault();

        var url = $(this).data('dialogUrl') || $(this).attr('data-dialog-url');
        if (!url) {
            return;
        }

        openDialog(url);
    });

})(jQuery);