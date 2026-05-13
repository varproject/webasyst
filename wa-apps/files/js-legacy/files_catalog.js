var FilesCatalog = ( function($) {

    FilesCatalog = function(options) {
        var that = this;

        // DOM
        that.$catalog = options['$catalog'];
        that.$operationMenu = options['$operationMenu'];
        that.$catalogWrapper = that.$catalog.closest(".f-catalog-content");
        that.$catalogCheckAll = that.$catalog.find(".f-catalog-header .check-all-files");
        that.$menuCheckAll = that.$operationMenu.find(".f-checkbox");

        // VARS
        that.storage = {
            shown_class: "is-shown",
            selected_class: "selected"
        };
        that.storage_id = ( options['storage_id'] || false );
        that.folder_id = ( options['folder_id'] || false );

        // DYNAMIC VARS
        that.is_fixed_menu_init = false;
        that.$lastSelectedFile = false;
        that.selectedFilesCount = 0;
        that.selectedFilesTypeCount = {
            file: 0,
            folder: 0
        };

        // INIT
        that.initCatalog();
    };

    FilesCatalog.prototype.initCatalog = function() {
        var that = this;

        that.bindEvents();

        that.highlightFiles();
    };

    FilesCatalog.prototype.bindEvents = function() {
        var that = this,
            $catalog = that.$catalog,
            $catalogCheckAll = that.$catalogCheckAll,
            $menuCheckAll = that.$menuCheckAll;

        // CATALOG

        $catalog.on("click", ".f-catalog-item .f-select-file", function(event) {
            event.stopPropagation();
            that.onSelectFile(event, $(this));
        });

        // click on catalog-item is like click on select-file checkbox
        // make sure links are in ignoring
        $catalog.on("click", ".f-catalog-item", function (event) {
            var target = $(event.target),
                is_link = false;

            while (!target.is('.f-catalog-item')) {
                if (target.is('a')) {
                    is_link = true;
                    break;
                }
                target = target.parent();
            }

            // ignore links
            if (!is_link) {
                var checkbox = $(this).find('.f-select-file');
                if (!checkbox.is(':disabled')) {
                    checkbox.attr('checked', !checkbox.is(':checked'));
                    that.onSelectFile(event, checkbox);
                }
            }
        });

        $catalogCheckAll.on("click", function() {
            that.onSelectAll();
        });

        $catalog.on("selectAll", function() {
            that.onSelectAll(true);
        });

        $catalog.on("unSelectAll", function() {
            that.onSelectAll(false);
        });

        $menuCheckAll.on("click", function() {
            $catalogCheckAll.click();
        });

        // OPERATIONS MENU

        $('.f-in-sync').on('click', function () {
            alert($_('Sync is in process. No changes available until synchronization finish.'));
            return false;
        });

        $("#f-copy-link:not(.f-in-sync)").on("click", function () {
            that.showDialog("?module=copy", {
                is_copy: true
            });
            return false;
        });

        $("#f-move-link:not(.f-in-sync)").on("click", function () {
            that.showDialog("?module=move", {
                is_move: true
            });

            return false;
        });

        $("#f-move-to-trash-link:not(.f-in-sync)").on("click", function () {
            that.showDialog("?module=delete", {
                is_trash: true
            });
            return false;
        });

        $("#f-restore-link:not(.f-in-sync)").on("click", function () {
            that.showDialog("?module=move&restore=1", {
                is_restore: true
            });
            return false;
        });

        $("#f-mark-link").on("click", function () {
            that.showDialog("?module=mark", {
                is_mark: true
            });
            return false;
        });

        $("#f-delete-link:not(.f-in-sync)").on("click", function () {
            that.showDialog("?module=delete&permanently=1", {
                is_delete: true
            });
            return false;
        });

        $("#f-rename-link:not(.f-in-sync)").on("click", function () {
            that.showDialog("?module=rename&id=", {
                is_rename: true
            });
            return false;
        });

        $('#f-send-link').on('click', function () {
            that.showDialog('?module=send&from=fileinfo', {
                is_send: true
            });
            return true;
        });

        $('#f-trash-clear').on("click", function() {
            var $link = $(this),
                confirm_text = $link.data("confirm-text");

            $.files.confirm(confirm_text, function() {
                $.files.jsonPost({
                    url: '?module=trash&action=clear',
                    onSuccess: function () {
                        $.files.refreshSidebar();
                        $.files.controller.reloadPage();
                    }
                });
            });
        });

        $('#f-tags-link').on("click", function () {
            that.showDialog("?module=tag", {
                is_tag: true
            });
            return false;
        });

        // PAGE HEADER

        $("#f-create-folder-link:not(.f-in-sync)").on("click", function() {
            var create_folder_href = "?module=folder&action=create";
            if (that.storage_id) {
                create_folder_href += "&storage_id=" + that.storage_id;
            }
            if (that.folder_id) {
                create_folder_href += "&folder_id=" + that.folder_id;
            }
            $.files.loadDialog(create_folder_href);
        });

        $("#f-share-link").on("click", function() {
            if (that.folder_id) {
                $.files.loadDialog("?module=share&file_id=" + that.folder_id);
            }
        });

        $("#f-public-link").on("click", function() {
            if (that.folder_id) {
                $.files.loadDialog("?module=share&public_link=1&file_id=" + that.folder_id);
            }
        });

        // copy/move dialog info
        this.$catalog.on('click', '.js-show-copyprocess-info', function () {
            $.files.alertInfo($(this).closest('.js-copytask-info-wrapper').find('.js-copytask-info').html());
        })
    };

    FilesCatalog.prototype.getFiles = function() {
        return this.$catalog.find(".f-catalog-item");
    };

    FilesCatalog.prototype.highlightFiles = function () {
       var that = this,
           storage_id = that.storage_id,
           local_storage = $.files.config.getLocaleStorage();
       if (storage_id > 0) {
           var all_counters_update_times = local_storage.get('files/all_counters_update_times') || {};
           all_counters_update_times.storage = all_counters_update_times.storage || {};
           var storage_update_time = parseInt(all_counters_update_times.storage[storage_id], 10);
           this.getFiles().each(function () {
               var item = $(this);
               var update_time = item.data('update-time');
               if (update_time - storage_update_time > 0) {
                   item.addClass('highlighted');
               }
           });
       }
    };

    FilesCatalog.prototype.onSelectFile = function(event, $checkbox) {
        var that = this,
            is_shift_pressed = event.shiftKey,
            is_active = ( $checkbox.attr("checked") == "checked" ),
            $currentFile = $checkbox.closest(".f-catalog-item"),
            $lastSelectedFile = ( that.$lastSelectedFile.length ) ? that.$lastSelectedFile : false,
            selected_class = "selected";

        if (is_active && is_shift_pressed && $lastSelectedFile) {
            var $files = that.getFiles(),
                do_selection = false;

            $files.each( function() {
                var $file = $(this),
                    is_selected = $file.hasClass(selected_class),
                    is_current = ($file[0] == $currentFile[0]),
                    is_last = ($file[0] == $lastSelectedFile[0]);

                if (is_current || is_last) {
                    do_selection = !do_selection;

                    if (!is_selected) {
                        that.selectFile(is_active, $file);
                        that.$lastSelectedFile = $file;
                    }

                    // Stop after end
                    if (!do_selection) {
                        return false;
                    }
                } else if (do_selection && !is_selected) {
                    $file.find(".f-select-file").click();
                }
            });

        } else {
            that.selectFile(is_active, $currentFile);
        }

        // Save data
        that.$lastSelectedFile = (is_active) ? $currentFile : false;
    };

    FilesCatalog.prototype.selectFile = function(is_active, $file) {
        var that = this,
            $operationMenu = that.$operationMenu,
            $selectAllInput = that.$catalogCheckAll,
            $menuCheckAll = that.$menuCheckAll,
            $renameLink = $operationMenu.find(".f-operation-link.is-rename"),
            $sendLink = $operationMenu.find('.f-operation-link.is-send'),
            file_type = $file.data('type');

        // Render
        if (is_active) {
            that.selectedFilesCount++;
            that.selectedFilesTypeCount[file_type]++;
            $file.addClass("selected");
            if ($file.hasClass("highlighted")) {
                $file.removeClass("highlighted").addClass("f-used-to-be-highlighted");
            }
        } else {
            that.selectedFilesCount--;
            that.selectedFilesTypeCount[file_type]--;
            $file.removeClass("selected");
            if ($file.hasClass("f-used-to-be-highlighted")) {
                $file.removeClass("f-used-to-be-highlighted").addClass("highlighted");
            }
        }

        // Show menu
        if (that.selectedFilesCount == 0) {
            $operationMenu.removeClass(that.storage.shown_class);
        }
        if (that.selectedFilesCount == 1) {
            $operationMenu.addClass(that.storage.shown_class);
            $renameLink.closest("li").show();
        }
        if (that.selectedFilesCount > 1) {
            $renameLink.closest("li").hide();
        }

        // send link action available only if there is not selected folder
        if (that.selectedFilesTypeCount.folder > 0) {
            $sendLink.closest('li').hide();
        } else {
            $sendLink.closest('li').show();
        }

        // Change counter
        var $counts = $operationMenu.find(".f-operation-link .f-count");
        $counts.text("(" + that.selectedFilesCount + ")");

        // Toggle selectAllInput
        var is_full_checked = (that.selectedFilesCount == that.getFiles().length);
        if (is_full_checked) {
            $selectAllInput.attr("checked", true);
            $menuCheckAll.attr("checked", true);
        } else {
            $selectAllInput.attr("checked", false);
            $menuCheckAll.attr("checked", false);
        }

        //
        if (!that.is_fixed_menu_init) {
            that.initFixedMenu();
            that.is_fixed_menu_init = true;
        }
    };

    FilesCatalog.prototype.onSelectAll = function(do_check_all ) {
        var that = this,
            $files = that.getFiles(),
            $input = that.$catalogCheckAll;

        do_check_all = (typeof do_check_all !== "undefined") ? do_check_all : ( $input.attr("checked") == "checked" );

        // set check-data to input at menu
        that.$menuCheckAll.attr("checked", do_check_all );

        $files.each( function() {
            var $input = $(this).find(".f-select-file"),
                file_is_active = ( $input.attr("checked") == "checked");

            if (!file_is_active && do_check_all) {
                $input.click();
            }
            if (file_is_active && !do_check_all) {
                $input.click();
            }
        });
    };

    FilesCatalog.prototype.showDialog = function(href, options) {
        var that = this,
            $catalog = that.$catalog,
            $checkedInputs = $catalog.find(".f-select-file:checked"),
            serializeList = "",
            dialog_href;

        options = ( options || {} );

        var is_rename = ( options['is_rename'] || false );
        if (is_rename) {
            serializeList = $checkedInputs.first().val();
        } else {
            $checkedInputs.each( function() {
                serializeList += "&file_id[]=" + $(this).val();
            });
        }

        dialog_href = href + serializeList;

        $.files.loadDialog(dialog_href);
    };

    FilesCatalog.prototype.initFixedMenu = function() {
        var that = this,
            $wrapper = that.$catalogWrapper,
            $header = that.$operationMenu,
            $window = $(window),
            headerOffset = $header.offset(),
            fixed_class = "is-fixed",
            is_absolute = ( $header.css("position") == "absolute"),
            is_active = false;

        var headerArea = {
            top: headerOffset.top,
            left: headerOffset.left,
            width: $header.outerWidth(),
            height: $header.outerHeight()
        };

        $window.on("scroll resize", onScroll);

        $window.trigger("scroll");

        function onScroll() {
            var unset_fixed_scroll = !( $.contains(document, $header[0]) && $header.hasClass(that.storage.shown_class) );
            if (unset_fixed_scroll) {
                that.is_fixed_menu_init = false;
                $window.off("scroll resize", onScroll);
                return false;
            }

            // Update header fixed/static state
            var should_be_active = ( $window.scrollTop() > headerArea.top );
            if (!is_active && should_be_active) {
                setFixed();
            } else if (is_active && !should_be_active) {
                unsetFixed();
            }

            // Keep the sticky header 100% width
            if (is_active) {
                $header.width($wrapper.width());
            }
        }

        function setFixed() {
            is_active = true;
            if (!is_absolute) {
                $wrapper.css("padding-top", headerArea.height);
            }
            $header.addClass(fixed_class);
        }

        function unsetFixed() {
            is_active = false;
            if (!is_absolute) {
                $wrapper.removeAttr("style");
            }
            $header
                .removeAttr("style")
                .removeClass(fixed_class);
        }
    };

    return FilesCatalog;

})(jQuery);
