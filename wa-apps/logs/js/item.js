$(function () {
    const SEARCH_SCROLL_ITEM_LINE_MARGIN = 8;

    var self = {
        init: function () {
            self.fixFirstPageLink();
            self.positionSearchNavigation();
            self.events();

            if ((new URL(location.href).searchParams.get('query')?.length ?? 0) > 0) {
                $('.pagination .paging').addClass('disabled');
            } else {
                prettyPrint();
            }

            // use timeout to ensure that elements' offsets have been finalized
            setTimeout(() => {
                self.scrollOnPageLoad();
            }, 100);
        },

        fixFirstPageLink: function () {
            $('.pagination a').each(function () {
                var link = $(this);
                var href = link.attr('href');

                if (href.indexOf('page=') < 0) {
                    if (href.indexOf('?') < 0) {
                        href = href + '?page=1';
                    } else {
                        href = href + '&page=1';
                    }

                    link.attr('href', href);
                }
            });
        },

        positionSearchNavigation: function () {
            const $search_navigation = $('#search-navigation');

            if ($search_navigation.length) {
                $search_navigation.insertAfter('#breadcrumbs-wrapper').removeClass('hidden');
            }
        },

        getDelayDeferred: function () {
            var deferred = $.Deferred();
            var timeout = 500;

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

        events: function () {
            $(document).on('click', '.item-lines', function getMoreLines() {
                var $button = $(this);

                if ($button.hasClass('disabled')) {
                    return;
                }

                $button.addClass('disabled');

                var $arrow = $button.find('.arrow');
                $arrow.addClass('hidden');

                var $spinner = $('<span><i class="fas fa-spinner fa-spin"></i></span>');
                $button.append($spinner);

                var $form = $('.item-lines-form:first');
                var direction = $(this).hasClass('previous') ? 'previous' : 'next';

                $form.find('[name="direction"]').val(direction);

                $.when(
                    $.ajax({
                        type: 'POST',
                        url: location.href,
                        data: $form.serialize(),
                        dataType: 'json'
                    }),
                    self.getDelayDeferred()
                ).then(function (post_response) {
                    var response = post_response[0];

                    $spinner.remove();

                    if (response.status == 'fail') {
                        $arrow.removeClass('hidden');

                        var errors_occurred = response.errors !== undefined && response.errors.length;

                        if (errors_occurred) {
                            $.waDialog({
                                html: self.getTemplateHtml($('.dialog-template-error')),
                                esc: false,
                                onOpen: function ($dialog, dialog) {
                                    $dialog.find('.state-error').html(response.errors.join(' '));
                                    dialog.resize();

                                    $dialog.on('click', '.js-submit', function errorDialogSubmit() {
                                        var $dialog_footer = $dialog.find('.dialog-footer');
                                        var $spinner = $('<i class="fas fa-spinner fa-spin"></i>')

                                        $dialog_footer.append($spinner);
                                        location.href = '?action=files&mode=updatetime';
                                    });
                                }
                            });
                        } else {
                            (function loadMoreLinesFailedReload() {
                                if (response.data.return_url !== undefined && response.data.return_url.length) {
                                    location.href = response.data.return_url;
                                } else {
                                    location.reload();
                                }
                            })();
                        }
                    } else {
                        //status = ok
                        const $contents = $('.item-contents');
                        const new_content = (response?.data?.contents ?? '').trim();

                        if (new_content.length) {
                            if (direction == 'previous' && response.data.first_line == 0) {
                                $button.attr('title', '');
                            } else {
                                $button.removeClass('disabled');
                            }

                            $arrow.removeClass('hidden');

                            if (direction == 'previous') {
                                $contents.prepend(new_content);
                                $('[name="first_line"]').val(response.data.first_line);
                            } else {
                                $contents.append(new_content);
                                $('[name="last_line"]').val(response.data.last_line);

                                if (response.data.last_eol !== undefined) {
                                    $form.find('[name="last_eol"]').val(response.data.last_eol);
                                }

                                if (response.data.file_end_eol !== undefined) {
                                    $form.find('[name="file_end_eol"]').val(response.data.file_end_eol);
                                }
                            }

                            if (response.data.file_size !== undefined) {
                                $('.total-size-file').text(response.data.file_size);
                            }

                            // preserve line breaks
                            $contents.html($contents.html().split('<br>').join("\n"));
                            $contents.text($contents.text());

                            prettyPrint();

                            if (direction == 'next') {
                                $('html').animate({ scrollTop: $(document).height() }, 'slow');
                            }
                        } else {
                            $('<span class="hint message">' + $('.item-data').data('item-lines-empty-message') + '</span>')
                                .appendTo($button)
                                .animate({ opacity: 0 }, 1000, function () {
                                    $(this).remove();
                                    $arrow.removeClass('hidden');
                                    $button.removeClass('disabled');
                                });
                        }
                    }
                });
            });

            $(document).on('click', '.search-navigation-button', async function (event) {
                event.preventDefault();

                const $search_navigation = $('#search-navigation');

                const scrolled_without_premium = !Boolean($search_navigation.data('premium'))
                    && Boolean($search_navigation.data('scrolled'));

                if (scrolled_without_premium) {
                    $.waDialog({
                        html: await $.get('?module=dialog&action=premiumPromo', {
                            feature: 'search-file-contents-scroll',
                            show_always: true,
                            close_button: true,
                        })
                    });

                    return;
                }

                const $button = $(this);

                const direction = $button.data('direction');
                const window_scroll_top = $(window).scrollTop();

                const $hidden_elements = self.getHiddenSearchResultsElements(direction);

                const $target_element = $hidden_elements.length
                    ? $hidden_elements.filter(function () {
                        const $element = $(this);

                        return direction == 'previous'
                            ? $element.is($hidden_elements.filter(':last'))
                            : $element.is($hidden_elements.filter(':first'));
                    })
                    : null;

                let scrolled = false;

                if ($target_element) {
                    const old_scroll_top = window_scroll_top;
                    await self.scrollToElement(direction, $target_element);
                    const new_scroll_top = $(window).scrollTop();

                    if (new_scroll_top != old_scroll_top) {
                        scrolled = true;
                    }
                }

                if (scrolled) {
                    if (!Boolean($search_navigation.data('premium'))) {
                        $search_navigation.data('scrolled', true);
                    }

                    self.updateSearchButtonsAvailability();
                } else {
                    if (Boolean($search_navigation.data('premium'))) {
                        $button.find('.icon').html('<i class="fas fa-spinner wa-animation-spin"/>');
                        location.href = $button.data('url');
                    } else {
                        $.waDialog({
                            html: await $.get('?module=dialog&action=premiumPromo', {
                                feature: 'search-file-contents-pagination',
                                show_always: true,
                                close_button: true,
                            })
                        });

                        return;
                    }
                }
            });

            $(document).on('click', '.logs-finish-search', function () {
                $(this).html('<i class="fas fa-spinner wa-animation-spin"/>');
            });
        },

        scrollOnPageLoad: async () => {
            const url_params = new URL(location.href).searchParams;
            const query = url_params.get('query') ?? '';
            const page = url_params.get('page');

            if (query.length) {
                const direction = url_params.get('direction') == 'next' ? 'next' : 'previous';

                const $target_element = direction == 'previous'
                    ? $('.item-contents mark:last')
                    : $('.item-contents mark:first');

                if ($target_element.length) {
                    await self.scrollToElement(direction, $target_element);
                    self.updateSearchButtonsAvailability();
                }
            } else {
                if (!page) {
                    const $last_element = $('.item-contents *:last');

                    if ($last_element.length) {
                        self.scrollToElement('previous', $last_element);
                    }

                }
            }
        },

        updateSearchButtonsAvailability: () => {
            $('.search-navigation-button').each(function () {
                const $button = $(this);
                const direction = $button.data('direction');

                if ($button.data('disabled')) {
                    if (self.getHiddenSearchResultsElements(direction).length) {
                        $button.removeClass('disabled');
                    } else {
                        $button.addClass('disabled');
                    }
                }
            });
        },

        getHiddenSearchResultsElements: (direction) => {
            const $found_elements = $('.item-contents mark');
            const window_scroll_top = $(window).scrollTop();

            return $found_elements.filter(function () {
                const $element = $(this);

                if (direction == 'previous') {
                    return $element.offset().top
                        - window_scroll_top
                        - $('#wa-app > .navigation').outerHeight()
                        - $('#wa-header').outerHeight() < 0;
                } else {
                    return $element.offset().top
                        - window_scroll_top
                        - $(window).height()
                        + $element.outerHeight() > 0;
                }
            });
        },

        scrollToElement: (direction, $element) => {
            const window_height = $(window).height();
            const wa_header_height = $('#wa-header').outerHeight();
            const navigation_height = $('#wa-app > .navigation').outerHeight();

            const scroll_top_value = direction == 'previous'
                ? (
                    $element.offset().top
                    - navigation_height
                    - wa_header_height
                    - SEARCH_SCROLL_ITEM_LINE_MARGIN
                )
                : (
                    $element.offset().top
                    - window_height
                    + $element.height()
                    + SEARCH_SCROLL_ITEM_LINE_MARGIN
                );

            return $('html').animate({
                scrollTop: scroll_top_value
            }, 'slow').promise();
        }
    };

    self.init();
});
