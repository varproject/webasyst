$(function () {
    const self = {
        init: function () {
            self.initDropdowns();
            self.initAutocomplete();
            self.initEvents();
        },

        delayedRequest: function (deferred, callback) {
            $.when(deferred, self.getDelayDeferred()).then(callback);
        },

        initPublishedStatusSwitch: function ($switch, $icon, action, callback) {
            $switch.waSwitch({
                ready: function (wa_switch) {
                    var $label = wa_switch.$wrapper.siblings('label');

                    wa_switch.$label = $label;
                    wa_switch.active_text = $label.data('active-text');
                    wa_switch.inactive_text = $label.data('inactive-text');
                },

                change: function (active, wa_switch) {
                    var $checkbox = $switch.find('input:checkbox');
                    var $dialog = $checkbox.closest('.dialog');
                    var $hidden_fields = $dialog.find('.hidden-field');
                    var $published_status_selector = $switch.closest('.published-status-selector');
                    var $url_container = $dialog.find('.published-url-container');
                    var $spinner = $('<i class="fas fa-spinner wa-animation-spin custom-ml-8 logs-spinner"></i>');
                    var $error_message = $dialog.find('.dialog-error-message .state-error');

                    wa_switch.$label.text(active ? wa_switch.active_text : wa_switch.inactive_text);

                    $dialog.find('.fa-spinner').remove();
                    $error_message.empty();
                    $published_status_selector.append($spinner);

                    self.fixElementDataPath($checkbox);

                    self.delayedRequest($.post('?module=dialog&action=' + action, {
                        path: self.getElementDataPath($checkbox),
                        status: active ? 1 : 0
                    }), function (post_response) {
                        var response = post_response[0];

                        $('.logs-spinner').remove();

                        if (response.status == 'ok') {
                            var $icon_parent = $icon.parent();

                            if (active) {
                                $icon_parent.addClass('enabled-action-container');

                                if ($icon_parent.data('title-enabled')) {
                                    $icon_parent.attr('title', $icon_parent.data('title-enabled'));
                                }
                            } else {
                                $icon_parent.removeClass('enabled-action-container');

                                if ($icon_parent.data('title-simple')) {
                                    $icon_parent.attr('title', $icon_parent.data('title-simple'));
                                }
                            }

                            $dialog.find('.published-url').val(response.data.url);
                            $dialog.find('.published-url-container a').attr('href', response.data.url);
                            $dialog.find('.published-password-value').val(response.data.password);

                            $dialog.find('.published-password-empty').hide();
                            $dialog.find('.published-password-exists').show();

                            if (active) {
                                $url_container.slideDown(200, callback);
                                $hidden_fields.slideDown(200);
                            } else {
                                $url_container.slideUp(callback);
                                $hidden_fields.slideUp();
                            }
                        } else {
                            $error_message.html(response.errors.join(' '));
                        }
                    });
                }
            });
        },

        initTrackedStatusSwitch: function ($switch, $icon) {
            $switch.waSwitch({
                ready: function (wa_switch) {
                    var $label = wa_switch.$wrapper.siblings('label');

                    wa_switch.$label = $label;
                    wa_switch.active_text = $label.data('active-text');
                    wa_switch.inactive_text = $label.data('inactive-text');
                },

                change: function (active, wa_switch) {
                    var $checkbox = $switch.find('input:checkbox');
                    var $dialog = $checkbox.closest('.dialog');
                    var $tracked_status_selector = $switch.closest('.tracked-status-selector');
                    var $spinner = $('<i class="fas fa-spinner wa-animation-spin custom-ml-8 logs-spinner"></i>');
                    var $error_message = $dialog.find('.dialog-error-message .state-error');

                    wa_switch.$label.text(active ? wa_switch.active_text : wa_switch.inactive_text);

                    $dialog.find('.fa-spinner').remove();
                    $error_message.empty();
                    $tracked_status_selector.append($spinner);

                    self.fixElementDataPath($checkbox);

                    self.delayedRequest($.post('?module=dialog&action=trackedFileUpdateStatus', {
                        path: self.getElementDataPath($checkbox),
                        status: active ? 1 : 0
                    }), function (post_response) {
                        var response = post_response[0];

                        $('.logs-spinner').remove();

                        if (response.status == 'fail') {
                            $error_message.html(response.errors.join(' '));
                        } else {
                            var $icon_parent = $icon.parent();
                            var $hint_field = $switch.closest('.dialog').find('.hint-field');

                            if (active) {
                                $icon_parent.addClass('enabled-action-container');

                                if ($icon_parent.data('title-enabled')) {
                                    $icon_parent.attr('title', $icon_parent.data('title-enabled'));
                                }

                                $hint_field.slideDown();
                            } else {
                                $icon_parent.removeClass('enabled-action-container');

                                if ($icon_parent.data('title-simple')) {
                                    $icon_parent.attr('title', $icon_parent.data('title-simple'));
                                }

                                $hint_field.slideUp();
                            }
                        }
                    });
                }
            });
        },

        initViewModesDropdown: function () {
            const $view_modes_dropdown = $('#view-modes-dropdown');

            if ($view_modes_dropdown.length) {
                const config = {};

                if ($('#wa-app').hasClass('mobile')) {
                    config.hover = false;
                }

                $view_modes_dropdown.waDropdown(config);
            }
        },

        initMobileMenuDropdown: function () {
            const $mobile_menu_dropdown = $('#mobile-menu-dropdown');

            if ($mobile_menu_dropdown.length) {
                $mobile_menu_dropdown.waDropdown({
                    hover: false,
                    hide: false
                });
            }
        },

        initDropdowns: function () {
            self.initViewModesDropdown();
            self.initMobileMenuDropdown();
        },

        initFileNameAutocomplete: function () {
            $('#file-name-search-field')
                .on('focus', function () {
                    $(this).val('');
                })
                .waAutocomplete({
                    source: '?module=backend&action=autocompleteFiles',
                    minLength: 0,
                    delay: 500,
                    autoFocus: true,
                    // hack to use HTML formatting in autocomplete items
                    open: function () {
                        const $item_wrappers = $('ul.ui-autocomplete:visible li .ui-menu-item-wrapper');

                        $item_wrappers.each(function () {
                            const $wrapper = $(this);
                            $wrapper.html($wrapper.text());
                        });
                    },
                    focus: function () {
                        return false;
                    },
                    select: function (event, ui) {
                        if (ui.item.value) {
                            location.href = ui.item.value;
                        }

                        return false;
                    }
                });
        },

        initProductAutocomplete: function () {
            $('#product-search-field')
                .on('focus', function () {
                    $(this).val('');
                })
                .waAutocomplete({
                    source: '?module=backend&action=autocompleteProducts',
                    minLength: 0,
                    delay: 500,
                    autoFocus: true,
                    // hack to use HTML formatting in autocomplete items
                    open: function () {
                        const $item_wrappers = $('ul.ui-autocomplete:visible li .ui-menu-item-wrapper');

                        $item_wrappers.each(function () {
                            const $wrapper = $(this);
                            $wrapper.html($wrapper.text());
                        });
                    },
                    focus: function () {
                        return false;
                    },
                    select: function (event, ui) {
                        if (ui.item.value) {
                            location.href = ui.item.value;
                        }

                        return false;
                    }
                });
        },

        initAutocomplete: function () {
            self.initFileNameAutocomplete();
            self.initProductAutocomplete();
        },

        getDelayDeferred: function () {
            const deferred = $.Deferred();
            const timeout = 500;

            setTimeout(function () {
                deferred.resolve();
            }, timeout);

            return deferred;
        },

        getTemplateHtml: function ($template) {
            var $clone = $template.clone();
            var html = $clone.html();

            $clone.remove();
            return html;
        },

        getUrlParamValue: function (param_name) {
            const url_params = location.search.replace(/^[^a-z]+/, '').split('&');

            return url_params.reduce(function (result, item) {
                const parts = item.split('=');

                if (parts.length == 2) {
                    const name = parts[0];
                    const value = parts[1];

                    if (name == param_name) {
                        result = value;
                    }
                }

                return result;
            }, '');
        },

        isFilePage: function () {
            return self.getUrlParamValue('action') == 'file';
        },

        updateDialogSubmitStatus: function ($dialog, $submit) {
            $submit.prop('disabled', $dialog.find('.state-error').text().trim().length > 0);
        },

        fixElementDataPath: function ($element) {
            //hack to work around a bug in waHtmlControl
            $element.data('path', self.getElementDataPath($element).split('\\/').join('/'));
        },

        getElementDataPath: function ($element) {
            // do not use .data('path') to support JSON-like file names
            return $element.attr('data-path');
        },

        reloadCallback: function (html) {
            $('#wa-app').replaceWith(html);
            self.initDropdowns();
            self.initAutocomplete();
        },

        showDeleteDialog: function (action, post_data, callback) {
            $.waDialog({
                html: self.getTemplateHtml($('.dialog-template-delete')),
                onOpen: ($dialog, dialog) => {
                    const $submit = $dialog.find('.js-submit');

                    $submit.prop('disabled', true);

                    $.post(
                        '?module=dialog&action=' + action,
                        post_data
                    ).then(html => {
                        const $response = $(html);
                        const $header = $response.find('.dialog-header');
                        const $content = $response.find('.dialog-content');

                        $dialog.find('.dialog-header').replaceWith($header);
                        $dialog.find('.dialog-content').replaceWith($content);
                        $dialog.find('.alert-close').data('dialog', dialog);

                        $dialog.find('.dialog-content').css({
                            'overflow-y': 'auto',
                            // 200 here is approximately enough to fit the dialog into the browser screen
                            // without complex calculations
                            'max-height': ($(window).height() - 200) + 'px'
                        });

                        self.updateDialogSubmitStatus($dialog, $submit);
                        dialog.resize();
                    });

                    $dialog.on('click', '.js-submit', function () {
                        const $submit = $(this);
                        const $form = $dialog.find('form');
                        const $spinner = $('<span><i class="fas fa-spinner wa-animation-spin custom-ml-8"></i></span>');

                        $dialog.find('.state-error').empty();
                        $submit.parent().append($spinner);
                        $dialog.find('[data-item-path]').removeClass('text-red');

                        self.delayedRequest(
                            $.ajax({
                                type: 'POST',
                                url: '?module=backend&action=delete',
                                data: $form.serialize(),
                                dataType: 'json'
                            }),
                            post_response => {
                                const response = post_response[0];

                                if (response.status == 'fail') {
                                    $spinner.remove();
                                    $dialog.find('.state-error').html('<i class="fas fa-exclamation-triangle"></i> '
                                        + response.errors.join('<br>'));
                                } else {
                                    if (response.data.message) {
                                        $spinner.remove();
                                        $dialog.find('.state-error').html(response.data.message);

                                        if (response.data.error_paths && Array.isArray(response.data.error_paths)) {
                                            response.data.error_paths.forEach((path) => {
                                                // do not use 'state-error' class instead of 'text-red' here
                                                // to separate this logic from the error message element
                                                $dialog.find('[data-item-path="' + path + '"]').addClass('text-red');
                                            });

                                            $submit.prop('disabled', true);
                                        }

                                        dialog.resize();
                                    } else {
                                        if (typeof callback == 'function') {
                                            callback.call()
                                        } else {
                                            dialog.close();
                                        }
                                    }

                                    const deleted_items = [];

                                    response.data.deleted_paths.forEach(path => {
                                        const $deleted_path_item = $('.item-list-item[data-path="' + path + '"]');

                                        if ($deleted_path_item.length) {
                                            deleted_items.push($deleted_path_item);
                                        }
                                    });

                                    $(deleted_items).each(function () {
                                        const $item = $(this);

                                        $item.slideUp(function () {
                                            $item.remove();
                                        });
                                    }).promise().done(() => {
                                        if ($('.item-list li').length) {
                                            if (response.data.total_size !== undefined) {
                                                $('.total-size').show().text(response.data.total_size);
                                            }

                                            if (response.data.total_size_class !== undefined) {
                                                $('.total-size').attr('class', response.data.total_size_class);
                                            }

                                            if (response.data.is_large !== undefined && !response.data.is_large) {
                                                $('#wa-app-logs .badge').remove();
                                            }
                                        } else {
                                            $('.item-list').hide();
                                            $('.select-all').slideUp();
                                            $('.no-logs-message').removeClass('hidden');

                                            if (!self.getUrlParamValue('path').length) {
                                                $('.total-size').hide().attr('class', 'total-size');
                                                $('#wa-app-logs .badge').remove();
                                            }
                                        }
                                    });
                                }
                            }
                        );
                    });
                }
            });
        },

        initEvents: function () {
            // delete
            $(document).on('click', '.logs-action-delete', function () {
                const $icon = $(this).find('svg');
                const $list_items = $icon.closest('li.item-list-item');
                const callback = $list_items.length ?
                    null :
                    () => {
                        if ($icon.data('return-url')) {
                            location.href = $icon.data('return-url');
                        }
                    };

                self.showDeleteDialog(
                    'delete',
                    {
                        path: self.getElementDataPath($icon)
                    },
                    callback
                );
            });

            // rename
            $(document).on('click', '.logs-action-rename', function () {
                var $icon = $(this).find('svg');
                var path = self.getElementDataPath($icon);
                var redirect = $icon.data('redirect');

                $.waDialog({
                    html: self.getTemplateHtml($('.dialog-template-rename')),
                    onOpen: function ($dialog, dialog) {
                        var $submit = $dialog.find('.js-submit');

                        $submit.attr('disabled', true);
                        dialog.resize();

                        $.get('?module=dialog&action=rename&path=' + path).then(function (html) {
                            var $response = $(html);
                            var $response_header = $response.find('.dialog-header');
                            var $response_content = $response.find('.dialog-content');

                            if ($response_header.length) {
                                $dialog.find('.dialog-header').replaceWith($response_header);
                            }

                            $dialog.find('.dialog-content').replaceWith($response_content);
                            $dialog.find('[name="name"]').focus();
                            $submit.attr('disabled', false);
                            dialog.resize();
                        });

                        $dialog.on('submit', 'form', function (event) {
                            event.preventDefault();

                            var $error = $dialog.find('.state-error');
                            var $spinner = $('<span><i class="fas fa-spinner fa-spin"></i></span>');
                            var $dialog_footer = $dialog.find('.dialog-footer');
                            var $form = $dialog.find('form');

                            $error.empty();
                            $dialog_footer.append($spinner);

                            self.delayedRequest(
                                $.ajax({
                                    type: 'POST',
                                    url: '?module=backend&action=rename',
                                    data: $form.serialize(),
                                    dataType: 'json'
                                }),
                                function (post_response) {
                                    var response = post_response[0];

                                    if (response.status == 'fail') {
                                        $spinner.remove();
                                        $error.html(response.errors.join(' '));
                                        dialog.resize();
                                    } else {
                                        if (redirect && response.data.redirect_url !== undefined) {
                                            location.href = response.data.redirect_url;
                                        } else {
                                            $.get(location.href).then(function (html) {
                                                self.reloadCallback(html);
                                                dialog.close();
                                            });
                                        }
                                    }
                                }
                            );
                        });

                        $dialog.on('click', '.js-submit', function () {
                            $dialog.find('form').submit();
                        });
                    }
                });
            });

            // file contents search
            $(document).on('click', '.logs-action-search', function () {
                const $icon = $(this).find('svg');

                $.waDialog({
                    html: self.getTemplateHtml($('.dialog-template-search')),
                    onOpen: async $dialog => {
                        const $content = $(await $.get('?module=dialog&action=fileContentsSearch', {
                            path: $icon.data('path'),
                            query: $icon.data('query'),
                            search_cancel_url: $icon.data('search-cancel-url')
                        }));

                        $dialog.find('.dialog-content').replaceWith($content.find('.dialog-content'));
                        $dialog.find('.dialog-footer').replaceWith($content.find('.dialog-footer'));

                        const $query_field = $dialog.find('[name="query"]');

                        $query_field.trigger('focus');

                        $dialog.find('form').on('submit', function (event) {
                            event.preventDefault();
                            $dialog.find('.js-submit').trigger('click');
                        });

                        $dialog.find('.icon-button').on('click', function () {
                            const $button = $(this);
                            $button.find('.icon').addClass('hidden');
                            $button.append($('<span class="spinner-wrapper"><i class="fas fa-spinner wa-animation-spin"/></span>'));
                        });

                        $dialog.find('.js-submit').on('click', function () {
                            const query = $query_field.val();

                            if (!query.trim().length) {
                                $.waDialog({
                                    html: $('.dialog-template-error').clone().html(),
                                    onOpen: ($dialog, dialog) => {
                                        $dialog.find('.state-error').html($query_field.data('loc-empty-field-message'));
                                        dialog.resize();

                                        $dialog.on('click', '.js-submit', () => {
                                            dialog.close();
                                        });
                                    }
                                });

                                const $icon_button_spinner_wrapper = $dialog.find('.icon-button .spinner-wrapper');
                                $icon_button_spinner_wrapper.closest('.icon-button').find('.icon').removeClass('hidden');
                                $icon_button_spinner_wrapper.remove();

                                return;
                            }

                            const url = new URL(location.href);

                            url.searchParams.set('query', query);

                            Array.from(url.searchParams.keys()).forEach(param => {
                                if (['action', 'path', 'query'].indexOf(param) == -1) {
                                    url.searchParams.delete(param);
                                }
                            });

                            location.href = url.href;
                        });
                    }
                });
            });

            // reload item list
            $(document).on('click', '.js-list-update-action', function (event) {
                event.preventDefault();

                const $icon = $(this).find('svg');
                const $spinner = $('<i class="fas fa-spinner fa-spin"></i>');

                $icon.replaceWith($spinner);

                self.delayedRequest(
                    $.get(location.href),
                    function (get_result) {
                        const html = get_result[0];
                        self.reloadCallback(html);
                    }
                );
            });

            // publishing dialog
            $(document).on('click', '.logs-action-published', function () {
                var $icon = $(this).find('svg');
                var path = self.getElementDataPath($icon);

                $.waDialog({
                    html: $('.dialog-template-file-publish').clone().html(),
                    onOpen: function ($dialog, dialog) {
                        $.get('?module=dialog&action=publishedFile&path=' + path).then(function (html) {
                            $dialog.find('.dialog-content').html(html);
                            dialog.resize();
                            self.initPublishedStatusSwitch(
                                $dialog.find('.published-status-switch'),
                                $icon,
                                'publishedFileUpdateStatus',
                                dialog.resize.bind(dialog)
                            );
                        });
                    }
                });
            });

            // phpinfo
            $(document).on('click', '.logs-action-phpinfo', function () {
                var $icon = $(this).find('svg');

                $.waDialog({
                    html: $('.dialog-template-phpinfo').clone().html(),
                    onOpen: function ($dialog, dialog) {
                        $.get('?module=dialog&action=phpinfo').then(function (html) {
                            $dialog.find('.dialog-content').html(html);
                            dialog.resize();
                            self.initPublishedStatusSwitch(
                                $dialog.find('.published-status-switch'),
                                $icon,
                                'publishedPhpinfoUpdateStatus',
                                dialog.resize.bind(dialog)
                            );
                        });
                    }
                });
            });

            // track
            $(document).on('click', '.logs-action-track', function () {
                var $icon = $(this).find('svg');
                var path = self.getElementDataPath($icon);

                $.waDialog({
                    html: self.getTemplateHtml($('.dialog-template-tracked-file')),
                    onOpen: function ($dialog, dialog) {
                        $.get('?module=dialog&action=trackedFile&path=' + path).then(function (html) {
                            $dialog.find('.dialog-content').html(html);
                            $dialog.find('.alert-close').data('dialog', dialog);
                            dialog.resize();
                            self.initTrackedStatusSwitch(
                                $dialog.find('.tracked-status-switch'),
                                $icon
                            );
                        });
                    }
                });
            });

            // auto-copy text in input on click
            $(document).on('click', '.auto-copy', function () {
                const $input = $(this);

                $.wa.copyToClipboard($(this).val()).then(() => {
                    $.wa.notify({
                        isCloseable: false,
                        timeout: 1000,
                        content: $input.data('loc-copied'),
                        appendTo: $('.dialog-body:visible').addClass('logs-notify-container')[0]
                    });
                });
            })

            // prevent users from submitting the form manually
            $(document).on('submit', '.published-password-protection', function (event) {
                event.preventDefault();
            });

            // set password
            $(document).on('click', '.published-set-password-link', function (event) {
                event.preventDefault();

                var $link = $(this);
                var $spinner = $('<span><i class="fas fa-spinner wa-animation-spin custom-ml-8"></i></span>');
                var $dialog = $link.closest('.dialog');
                var $form = $link.closest('form');
                var $error_message = $dialog.find('.dialog-error-message .state-error');
                var $empty_password_container = $form.find('.published-password-empty');
                var $existing_password_container = $form.find('.published-password-exists');

                $error_message.empty();
                $spinner.insertAfter($link);

                self.delayedRequest(
                    $.post('?module=dialog&action=' + $form.data('setPasswordAction'), $form.serialize()),
                    function (post_response) {
                        var response = post_response[0];

                        $spinner.remove();

                        if (response.status == 'fail') {
                            $error_message.html(response.errors.join(' '));
                            $dialog.trigger('resize');
                        } else {
                            $form.find('.published-password-value').val(response.data.password);
                            $empty_password_container.slideUp();
                            $existing_password_container.slideDown();
                        }
                    }
                );
            });

            // reset password
            $(document).on('click', '.published-reset-password-link', function (event) {
                event.preventDefault();

                var $link = $(this);
                var $spinner = $('<i class="fas fa-spinner wa-animation-spin custom-ml-8"></i>');
                var $dialog = $link.closest('.dialog');
                var $form = $link.closest('form');
                var $error_message = $dialog.find('.dialog-error-message .state-error');
                var $password = $form.find('.published-password-value');

                $error_message.empty();
                $spinner.insertAfter($password);

                self.delayedRequest(
                    $.post('?module=dialog&action=' + $form.data('setPasswordAction'), $form.serialize()),
                    function (post_response) {
                        var response = post_response[0];

                        $dialog.find('.fa-spinner').remove();

                        if (response.status == 'fail') {
                            $error_message.html(response.errors.join(' '));
                            $dialog.trigger('resize');
                        } else {
                            $form.find('.published-password-value').val(response.data.password);

                            $('<span class="logs-spinner"><i class="fas fa-check custom-ml-8"></i></span>').insertAfter($password);
                            $dialog.find('svg.fa-check').remove();
                            $dialog.find('.logs-spinner').animate({ opacity: 0 }, 700, function () {
                                $(this).remove();
                            });
                        }
                    }
                );
            });

            // remove password
            $(document).on('click', '.published-remove-password-link', function (event) {
                event.preventDefault();

                var $link = $(this);
                var $spinner = $('<i class="fas fa-spinner wa-animation-spin custom-ml-8"></i>');
                var $dialog = $link.closest('.dialog');
                var $form = $link.closest('form');
                var $error_message = $dialog.find('.dialog-error-message .state-error');
                var $empty_password_container = $form.find('.published-password-empty');
                var $existing_password_container = $form.find('.published-password-exists');
                var $password = $form.find('.published-password-value');

                $error_message.empty();
                $spinner.insertAfter($password);

                self.delayedRequest(
                    $.post('?module=dialog&action=publishedRemovePassword', $form.serialize()),
                    function (post_response) {
                        var response = post_response[0];

                        $dialog.find('.fa-spinner').remove();

                        if (response.status == 'fail') {
                            $error_message.html(response.errors.join(' '));
                            $dialog.trigger('resize');
                        } else {
                            $form.find('.published-password-value').val('');
                            $empty_password_container.slideDown();
                            $existing_password_container.slideUp();
                        }
                    }
                );
            });

            // settings
            $(document).on('click', '.logs-action-settings', () => {
                const $icon = $(this).find('svg');

                $.waDialog({
                    html: self.getTemplateHtml($('.dialog-template-settings')),
                    onOpen: ($dialog, dialog) => {
                        const $submit = $dialog.find('.js-submit');
                        const window_height = $(window).height();

                        $submit.attr('disabled', true);

                        $.get('?module=dialog&action=settings').then(function (html) {
                            $dialog.find('.dialog-content').html(html);
                            $submit.attr('disabled', false);

                            $dialog.find('#settings').css({
                                height: (window_height - 200) + 'px'
                            });

                            dialog.resize();
                        });

                        $dialog.on('click', '.js-submit', function () {
                            const $error = $dialog.find('.state-error');
                            const $form = $dialog.find('form');
                            const $spinner = $('<span><i class="fas fa-spinner wa-animation-spin custom-ml-8"></i></span>');

                            $dialog.find('.js-close-dialog').after($spinner);
                            $error.empty();

                            self.delayedRequest(
                                $.post('?module=dialog&action=settingsSave', $form.serialize()),
                                post_response => {
                                    const response = post_response[0];

                                    if (response.status == 'fail') {
                                        $spinner.remove();
                                        $error.html(response.errors.join(' '));
                                        dialog.resize();
                                    } else {
                                        const do_reload = settingsSaveUpdateFilePage($icon, $dialog);

                                        if (do_reload === false) {
                                            dialog.close();
                                        }
                                    }
                                }
                            );
                        });
                    }
                });

                function settingsSaveUpdateFilePage($icon, $dialog) {
                    if (self.isFilePage()) {
                        var old_hide_data = $icon.data('hide-data');

                        if (old_hide_data !== undefined) {
                            var new_hide_data = [];

                            $dialog.find('[name^="settings[hide]"]').each(function () {
                                var $checkbox = $(this);
                                var matches = $checkbox.attr('name').match(/settings\[[^\]]+\]\[([^\]]+)\]\[([^\]]+)\]/);

                                if (matches[2] == 'backend' && $checkbox.is(':checked')) {
                                    new_hide_data.push(matches[1]);
                                }
                            });

                            old_hide_data.sort();
                            new_hide_data.sort();

                            $icon.data('hide-data', new_hide_data);

                            if (JSON.stringify(old_hide_data) !== JSON.stringify(new_hide_data)) {
                                location.reload();
                            } else {
                                return false;
                            }
                        }
                    }

                    return false;
                }
            });

            // show/hide PHP errors selection setting
            $(document).on('change', '.php_log_setting', function () {
                var $checkbox = $(this);
                var $php_errors_field = $checkbox.closest('.fields').find('.field-php-log-errors');
                var enabled = $checkbox.is(':checked');

                if (enabled) {
                    $php_errors_field.slideDown(200);
                } else {
                    $php_errors_field.slideUp();
                }
            });

            // update PHP errors setting checkboxes
            $(document).on('change', '.field-php-log-errors input[type="checkbox"]', function () {
                var $checkbox = $(this);
                var $field_value_container = $checkbox.closest('.value');
                var $e_all = $field_value_container.find('[value="E_ALL"]');
                var $e_other = $field_value_container.find('[type="checkbox"]').not('[value="E_ALL"]');

                var is_checked = $checkbox.is(':checked');

                if ($checkbox.is($e_all)) {
                    $e_other.prop('checked', !is_checked);
                } else {
                    $e_all.prop('checked', !$e_other.filter(':checked').length);
                }
            });

            // show file search drop-down
            $(document).on('click', '#file-search-dropdown .button', function () {
                const $file_search_dropdown_body = $('#file-search-dropdown-body');

                if ($file_search_dropdown_body.is(':visible')) {
                    $file_search_dropdown_body.slideUp();
                } else {
                    $file_search_dropdown_body.slideDown();
                }
            });

            // search by text
            $(document).on('submit', '#file-text-search-form', function (event) {
                if (!$(this).find('[name="query"]').val().trim().length) {
                    event.preventDefault();
                }
            });

            // cron setup dialog
            $(document).on('click', '.show-cron-setup-link', function (event) {
                event.preventDefault();

                $.waDialog({
                    html: self.getTemplateHtml($('.dialog-template-cron-setup')),
                });
            });

            // show premium features
            $(document).on('click', '.logs-action-premium, .view-premium-link', function () {
                const feature = $(this).data('feature');

                $.waDialog({
                    html: self.getTemplateHtml($('.dialog-template-premium')),
                    onOpen: function ($dialog, dialog) {
                        const window_height = $(window).height();
                        $dialog.find('.dialog-content').load('?module=dialog&action=premium', () => {
                            // randomize promos' sort order
                            const $promos_container = $dialog.find('.promos-list');
                            const $promos = $promos_container.find('.promo');

                            $promos_container.empty();

                            $promos.toArray()
                                .map(promo => ({
                                    promo: promo,
                                    sort: Math.random()
                                }))
                                .sort((a, b) => {
                                    const a_feature = a.promo.getAttribute('data-feature');
                                    const b_feature = b.promo.getAttribute('data-feature');

                                    if (feature == 'search') {
                                        if (a_feature.indexOf(feature) == 0
                                            && a_feature.indexOf('search-file-contents-') < 0
                                            && b_feature.indexOf(feature) != 0
                                        ) {
                                            return -1;
                                        } else if (
                                            a_feature.indexOf(feature) != 0
                                            && b_feature.indexOf(feature) == 0
                                            && b_feature.indexOf('search-file-contents-') < 0
                                        ) {
                                            return 1;
                                        } else {
                                            return a.sort - b.sort;
                                        }
                                    } else if (feature && String(feature).indexOf('search-file-contents-') == 0) {
                                        if (a_feature == 'search-file-contents' && b_feature != 'search-file-contents') {
                                            return -1;
                                        } else if (a_feature != 'search-file-contents' && b_feature == 'search-file-contents') {
                                            return 1;
                                        } else {
                                            return a.sort - b.sort;
                                        }
                                    } else {
                                        if (a_feature == feature) {
                                            return -1;
                                        } else if (b_feature == feature) {
                                            return 1;
                                        } else {
                                            return a.sort - b.sort;
                                        }
                                    }
                                })
                                .forEach(data => $promos_container.append(data.promo));

                            $dialog.find('#promos').css({
                                height: (window_height - 200) + 'px'
                            });

                            dialog.resize();
                        });
                    }
                });
            });

            $(document).on('click', '.logs-premium-feature-alert .alert-close', async function () {
                const $button = $(this);
                const $alert = $button.closest('.logs-premium-feature-alert');
                const $spinner = $('<i class="fas fa-spinner wa-animation-spin"></i>');

                $button.empty().append($spinner).attr('disabled', true).css('color', 'inherit!important');

                await $.post('?module=backend&action=hidePremiumPromo', {
                    feature: $button.data('feature')
                });

                $alert.slideUp(() => {
                    $button.data('dialog').resize();
                });
            });

            $(document).on('click', '.logs-open-premium-feature-button', async function () {
                const $promo_button = $(this);

                const html = await $.get('?module=dialog&action=premiumPromo', {
                    feature: $promo_button.data('feature')
                });

                $.waDialog({
                    html: html,
                    onOpen: ($dialog, dialog) => {
                        $dialog.find('.js-close-dialog-forever').on('click', async function () {
                            const $close_button = $(this);
                            const $spinner = $('<i class="fas fa-spinner wa-animation-spin custom-ml-4"></i>');

                            $close_button.append($spinner);

                            $.post('?module=backend&action=hidePremiumPromo', {
                                feature: $promo_button.data('feature')
                            });

                            dialog.close();
                            $promo_button.closest('.logs-open-premium-feature-button-wrapper').remove();
                        });
                    }
                });
            });

            $(document).on('click', '.search-toggle-custom', function () {
                const $button = $(this);

                $button.toggleClass('expanded');

                if ($button.hasClass('expanded')) {
                    $button.attr('title', $button.data('title-expanded'));
                } else {
                    $button.attr('title', $button.data('title-collapsed'));
                }
            });

            $(document).on('click', '.item-actions-buttons-expander', function () {
                const $button = $(this);
                $button.parent().find('.item-actions-buttons').slideDown();
                $button.css('visibility', 'hidden');
            });

            $(document).on('change', '.item-select-checkbox', function () {
                const $checkbox = $(this);
                const checked = $checkbox.is(':checked');
                const $delete_button = $('.logs-action-delete-bulk svg');

                if (checked) {
                    $delete_button.css('width', '14px');

                    if ($('.item-select-checkbox:checked').length == $('.item-select-checkbox').length) {
                        $('.select-all :checkbox').prop('checked', true);
                    }

                    $('.item-select-checkbox').data('last-checked', null);
                    $checkbox.data('last-checked', true);
                } else {
                    $checked_checkboxes = $('.item-select-checkbox:checked');

                    if (!$checked_checkboxes.length) {
                        $delete_button.css('width', '0px');
                    }

                    $('.select-all :checkbox').prop('checked', false);

                    $checkbox.data('last-checked', null);
                }
            });

            // multi-select with Shift key
            $(document).on('click', '.item-list-item .wa-checkbox', function (event) {
                if (!event.shiftKey) {
                    return;
                }

                const $element = $(event.target);

                // this check is required because the event is triggered twice for .wa-checkbox,
                // only the one from the checkbox is useful here
                if (!$element.is(':checkbox')) {
                    return;
                }

                if (!$element.is(':checked')) {
                    return;
                }

                const $last_checked = $('.item-select-checkbox').filter(function () {
                    return Boolean($(this).data('last-checked'));
                });

                if (!$last_checked.length) {
                    return;
                }

                const $current_checkbox = $element;
                let select = false;

                $('.item-select-checkbox').each(function () {
                    const $checkbox = $(this);

                    if (select) {
                        if ($checkbox.is($current_checkbox) || $checkbox.is($last_checked)) {
                            select = false;
                        } else {
                            $checkbox.prop('checked', true).trigger('change');
                        }
                    } else {
                        if ($checkbox.is($current_checkbox) || $checkbox.is($last_checked)) {
                            select = true;
                        }
                    }
                });
            });

            $(document).on('change', '.select-all :checkbox', function () {
                const checked = $(this).is(':checked');
                const $checkboxes = $('.item-select-checkbox');

                $checkboxes.prop('checked', checked);
                $checkboxes.eq(0).trigger('change');
            });

            $(document).on('click', '.logs-action-delete-bulk', function () {
                const $selected_checkboxes = $('.item-select-checkbox:checked');

                self.showDeleteDialog(
                    'deleteBulk',
                    $selected_checkboxes.serialize()
                );
            });
        }
    };

    self.init();
});
