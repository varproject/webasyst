$(function () {
    $.waDialog({
        html: $('.dialog-template').clone().html(),
        esc: false,
        onOpen: function ($dialog, dialog) {
            const $form = $dialog.find('form');

            $form.find('[name="password"]:first').trigger('focus');

            $form.on('submit', function (event) {
                event.preventDefault();

                const $error_message = $dialog.find('.state-error');
                const $spinner = $('<span><i class="fas fa-spinner fa-spin"></i></span>');
                const $submit = $dialog.find('.js-submit');

                $error_message.css({ visibility: 'hidden' });
                $dialog.find('.dialog-footer').append($spinner);

                $.when(
                    $.ajax({
                        type: 'POST',
                        url: location.href,
                        data: $form.serialize(),
                        dataType: 'json'
                    }),
                    (function () {
                        const deferred = $.Deferred();
                        const timeout = 500;

                        setTimeout(function () {
                            deferred.resolve();
                        }, timeout);

                        return deferred;
                    })()
                ).then(function (post_response) {
                    const response = post_response[0];

                    if (response.status == 'fail') {
                        $spinner.remove();

                        if (response.errors !== undefined) {
                            $error_message.html(response.errors.join(' ')).css({ visibility: 'visible' });
                        }

                        $submit.removeAttr('disabled');
                        $dialog.find('[name="password"]').focus();
                    } else {
                        dialog.close();
                        location.reload();
                    }
                });
            });

            $dialog.find('.js-submit').on('click', function () {
                $form.trigger('submit');
            });
        }
    });
});
