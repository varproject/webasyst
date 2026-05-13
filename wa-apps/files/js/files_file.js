var File = ( function($) {

    File = function(options) {
        var that = this;

        // DOM
        that.$file = options["$file"];

        // VARS
        that.file_id = options["file_id"];
        that.can_rename = options["can_rename"];
        that.can_edit_tags = options["can_edit_tags"];
        that.can_comment = options["can_comment"];
        that.full_access = options['full_access'];
        that.marks = options['marks']?.filter(Boolean);
        that.locale = options['locale'];

        // DYNAMIC VARS

        // INIT
        that.initFile();
    };

    File.prototype.initFile = function() {
        var that = this;

        that.bindEvents();

        that.initTags();

        if (that.can_rename) {
            that.initHeaderRename();
        }

        if (that.can_comment) {
            that.initComments();
        }

        if (that.full_access) {
            /* setTimeout - hack for await fontawesome */
            setTimeout(that.initMarkControl.bind(that), 500)
        }

        that.initFrontUrl();
    };

    File.prototype.bindEvents = function() {
        var that = this,
            $file = that.$file;

        $('.f-in-sync').on('click', function () {
            alert($_('Sync is in process. No changes available until synchronization finish.'));
            return false;
        });

        $("#f-share-link").on("click", function() {
            $.files.loadDialog("?module=share&file_id=" + that.file_id);
        });

        $('#f-public-link').on('click', function () {
            $.files.loadDialog("?module=share&public_link=1&file_id=" + that.file_id);
        });

        $file.on("click", ".f-delete-file:not(.f-in-sync)", function() {
            $.files.loadDialog("?module=delete&file_id[]=" + that.file_id + "&from=fileinfo");
        });

        $file.on("click", ".f-send-file", function() {
            $.files.loadDialog("?module=send&file_id[]=" + that.file_id + "&from=fileinfo");
        });
    };

    File.prototype.initTags = function() {
        var that = this,
            $wrapper = that.$file.find(".f-tags-wrapper"),
            $input = $wrapper.find("#f-file-tags"),
            $popular_tags = that.$file.find(".f-popular-tags"),
            $link = $wrapper.find(".saved-link"),
            default_text = ( $wrapper.data("add-tag-text") || "enter tags"),
            can_edit = that.can_edit_tags,
            active_class = "is-shown",
            options = {
                autocomplete_url: '',
                interactive: can_edit,
                removeWithBackspace: false,
                defaultText: ''
            };

        // EVENT
        $popular_tags.on("click", "a", function () {
            var name = $(this).text();
            $input.removeTag(name);
            $input.addTag(name);
        });

        // SHOW SAVED PROCESS
        function showSavedIcon() {
            if ( $link.hasOwnProperty("timeout") && $link.timeout ) {
                clearTimeout($link.timeout);
            }

            $link.addClass(active_class);

            $link.timeout = setTimeout( function() {
                $link.removeClass(active_class);
                $link.timeout = false;
            }, 500);
        }

        if (can_edit) {
            options = $.extend(options, {
                defaultText: default_text,
                autocomplete: {
                    source: function(request, response) {
                        $.getJSON('?module=tag&action=autocomplete&term=' + request.term, function(r) {
                            if (r.status === 'ok') {
                                response(r.data.tags || []);
                            } else {
                                response([]);
                            }
                        });
                    }
                },
                onAddTag: function() {
                    $.files.jsonPost({
                        url: '?module=tag&action=save&file_id=' + that.file_id,
                        data: { tags: $input.val() }
                    });
                    showSavedIcon();
                },
                onRemoveTag: function() {
                    $.files.jsonPost({
                        url: '?module=tag&action=save&file_id=' + that.file_id + '&delete=1',
                        data: { tags: $input.val() }
                    });
                    showSavedIcon();
                }
            });
        } else {
            var id = $input.attr('id') + '_tagsinput';
            $('#' + id).addClass('f-readonly');
        }

        // INIT
        $input.tagsInput(options);

        // on-blur add tag
        if (can_edit) {
            // current "tag input"
            $('#' + $input.attr('id') + '_tag').bind('blur', function() {
                var tag = $(this).val();
                if (tag && tag !== default_text) {
                    $input.addTag(tag);
                }
            });
        }

        // placeholder hack
        $wrapper.find("#f-file-tags_tag").val("").attr("placeholder", default_text);
    };

    File.prototype.initMarkControl = function() {
        const that = this;
        const $settings_items = that.$file.find('.f-settings-colorbox .f-settings-item');
        const $header = that.$file.find(".f-editable-header");
        that.marks.push('highlighted');

        $settings_items.on('click', function() {
            const $check_icons = that.$file.find('.f-settings-colorbox [data-icon]')
            $check_icons.addClass('hidden');
            $settings_items.removeClass('selected');
            const $btn = $(this);
            const val = $btn.data('mark');
            $.files.jsonPost({
                url: '?module=mark&action=save',
                data: {
                    file_id: [that.file_id],
                    mark: val
                },
                onSuccess: function (r) {
                    if (r.status === 'ok') {
                        $btn.addClass('selected').find('[data-icon]').removeClass('hidden');
                        if ($header.length) {
                            $header.removeClass(that.marks).addClass(val ? 'highlighted ' + val : '');
                        }
                    }
                }
            });
        });
    };

    File.prototype.initHeaderRename = function() {
        var that = this,
            $header = that.$file.find(".f-editable-header"),
            fileInfo = $.files.parseFileName($header.text());

        $header.after('<a href="javascript:void(0);" class="button nobutton circle js-edit-title" style="font-size: 1rem;"><i class="fas fa-edit"></i></a>');
        $.files.config.getMainContainer().find('.js-edit-title').on('click', function() {
            $header.trigger('click');
        });

        $header.inlineEditable({
            minSize: {
                width: $header.width()
            },
            maxSize: {
                width: 600
            },
            size: {
                height: 18
            },
            inputClass: 'bold',
            afterMakeEditable: function(input) {
                input.selection('setPos', {
                    start: 0,
                    end: fileInfo.filename.length
                });
            },
            afterBackReadable: function (input, data) {
                if (!data.changed) {
                    return false;
                }

                var currentFileInfo = $.files.parseFileName($(input).val()),
                    isExtChanged = (currentFileInfo.ext !== fileInfo.ext);

                if (isExtChanged) {
                    $.files.confirm($header.data("confirm-text"),
                        function() { post(); },
                        function() { $header.text(data.old_text); }
                    );
                } else {
                    post();
                }

                function post() {
                    $.files.jsonPost({
                        url: '?module=rename&action=file',
                        data: {
                            id: that.file_id,
                            name: $(input).val()
                        },
                        onSuccess: function(r) {
                            if (r.status !== 'ok' || !r.data.file) {
                                $header.text(data.old_text);
                                if (r.errors && r.errors.sync) {
                                    $.files.alertError(r.errors.sync.msg);
                                }
                            } else {
                                $.files.controller.reloadPage();
                            }
                        }
                    });
                }
            }
        });
    };

    File.prototype.initComments = function() {
        var that = this,
            $file = that.$file,
            $comment_form = $file.find("#f-add-comment-form"),
            $textArea = $comment_form.find('.f-text'),
            $button = $comment_form.find('.f-button');

        // EVENT
        $button.on("click", function() {
            sendComment();
        });

        $file.on("click", ".f-comment-delete", function() {
            deleteComment( $(this) );
            return false;
        });

        $textArea.on("keydown", function(e) {
            if (e.keyCode === 13 && !e.altKey && e.ctrlKey && !e.shiftKey) {
                sendComment();
            }
        });

        var blockPageReloading = function () {
            // when textarea of comment is not empty not allow reload page
            var $win = $(window),
                ns = 'f-file-comment',
                event = 'wa_before_reload_page',
                event_ns = event + '.' + ns;
            $win.off(event_ns).
                on(event_ns, function (e) {
                        if ($.trim($textArea.val()).length > 0) {
                            // not allowed reload page
                            e.preventDefault();
                        }
                    }
                );
        };

        var unblockReloadingPage = function () {
            $(window).off('.f-file-comment')
        };

        blockPageReloading();   // block reloading page when user in progress of writing comment

        //
        var sendComment = function() {
            $.files.clearValidateErrors($comment_form);
            $.files.jsonPost({
                url: '?module=comment&action=save&file_id=' + that.file_id,
                data: $comment_form.serialize(),
                onSuccess: function(r) {
                    if (r && r.status === 'ok' && r.data.comment_id) {
                        unblockReloadingPage(); //  before reload page we need unblock reloading :)
                        $.files.controller.doRedispatch();
                    } else if (r && r.status !== 'ok') {
                        $.files.showValidateErrors(r.errors, $comment_form);
                    }
                }
            });
        };

        var deleteComment = function($link) {
            var $comment = $link.closest(".f-comment-item");

            $link.html("<i class=\"icon16 loading\"></i>");

            $.get('?module=comment&action=delete&id=' + $comment.data("id"), function() {
                if (that.$file.find(".f-comment-item").length <= 1) {
                    $comment.closest(".field").hide();
                }
                $comment.remove();
            });
        };

        //
        $.files.onFormChange($comment_form, function() {
            $.files.clearValidateErrors($comment_form);
        });
    };

    /**
     * Function set width to "input.#f-frontend-url"
     * */
    File.prototype.initFrontUrl = function() {
        const that = this;
        const $frontUrl = $("#f-frontend-url");

        if ($frontUrl.length) {
            $frontUrl.on("click", async function () {
                await $.wa.copyToClipboard($(this).val());
                $.wa.notify({
                    class: 'success',
                    content: `<i class="fas fa-check-circle text-green custom-mr-8"></i> ${that.locale.copied}`,
                    isCloseable: false,
                    timeout: 2000
                });
            });
        }
    };

    return File;

})(jQuery);
