(function($) {

    var hover_class = "is-hover";

    $.files = $.files || {};

    $.files.dragndrop = {
        handlers: {},
        options: {},
        init: function(options) {
            this.options = options;
            this.initDragFiles();
            this.initDropFiles();
            this.initDropStorages();
            this.initDropSharedFolders();
            this.initDropTrash();
            this.initDropFilters();
            return this;
        },

        isFolder: function(type) {
            return type === 'folder';
        },

        isFile: function(type) {
            return type === 'file';
        },

        bind: function(event, handler) {
            this.handlers[event] = handler;
            return this;
        },

        trigger: function(event) {
            if (typeof this.handlers[event] === 'function') {
                return this.handlers[event].apply(this, Array.prototype.slice.call(arguments, 1));
            }
        },

        initDragFiles: function() {
            var context = $('.f-catalog-item', $.files.config.getFileList());
            context.find('.drag-handle').on('selectstart', function() {
                document.onselectstart = function() {
                    return false;
                };
                return false;
            });
            context.liveDraggable({
                opacity: 0.75,
                zIndex: 9999,
                distance: 5,
                appendTo: 'body',
                cursor: 'move',
                refreshPositions: true,
                containment: [
                      0,
                      0,
                      $(window).width(),
                      {
                          toString: function() {
                              return $(document).height();  // we have lengthened document, so make flexible calculating (use typecast method toString)
                          }
                      }
                ],
                start: function(event, ui) {
                    // prevent default-browser drag-and-drop action
                    document.ondragstart = function(event) {
                        event.preventDefault();
                    };
                    // scroll fix. See helperScroll
                    ui.helper.data('scrollTop', $(document).scrollTop());
                    $(document).on('scroll', $.files.dragndrop._scrolHelper);

                    toggleDropZones(true);
                },
                handle: '.drag-handle',
                stop: function(event, ui) {
                    document.ondragstart   = null;
                    document.onselectstart = null;

                    var self = $(this);
                    if (!self.find('input:checked').length) {
                        self.removeClass('selected');
                    }
                    hideSortHint();
                    $(document).off('scroll', $.files.dragndrop._scrolHelper);
                    toggleDropZones(false);
                },
                helper: function(event, ui) {
                    var count = 1;
                    var selected = $.files.config.getFileList().find('.f-catalog-item.selected');
                    if (selected.length) {
                        if (selected.index(this) !== -1) {
                            count = selected.length;
                        } else {
                            $(this).addClass('selected');
                            count = selected.length + 1;
                        }
                    } else {
                        $(this).addClass('selected');
                    }
                    return '<div id="products-helper"><span class="indicator red">'+count+'</span><i class="icon10 no-bw" style="display:none;"></i></div>';
                },
                drag: function(event, ui) {
                    var e = event.originalEvent;
                    ui.position.left = e.pageX - 20;
                    ui.position.top = e.pageY;
                }
            });

            function toggleDropZones(show) {
                var $oldDropZones = $(".block.drop-target"),
                    $dropZones = $(".f-drop-zone"),
                    drop_class = "is-drop";

                if (show) {
                    $oldDropZones.addClass('drag-active');
                    $dropZones.addClass(drop_class);
                } else {
                    $oldDropZones.removeClass('drag-active');
                    $dropZones.removeClass(drop_class);
                }
            }
        },

        initDropFiles: function() {
            // dropping process in photo-list itself. Dropping process is trying sorting
            $('.f-catalog-item', $.files.config.getFileList()).liveDroppable({
                disabled: false,
                greedy: true,
                tolerance: 'pointer',
                drop: function(event, ui) {
                    var drag = ui.draggable;
                    var drop = $(this);

                    if (!drag.hasClass('f-catalog-item')) {
                        return false;
                    }

                    // drop into itself is illegal
                    if (drop.hasClass('selected')) {
                        return false;
                    }

                    if (!$.files.dragndrop.isFolder(drop.data('type'))) {
                        return false;
                    }

                    var folder_id = drop.data('id');
                    var drag_files = $.files.config.getFileList().find('.f-catalog-item.selected');

                    var file_ids = drag_files.map(function() {
                        return $(this).data('id');
                    }).toArray();

                    drag_files.hide();

                    $.files.config.getFileList().trigger('move_files', [{
                        file_ids: file_ids,
                        folder_id: folder_id,
                        error: function() {
                            drag_files.show();
                        }
                    }]);

                }
            });
        },

        initDropStorages: function() {
            var sidebar = $.files.config.getSidebar();
            $('.f-storage-list > li', sidebar).liveDroppable({
                disabled: false,
                greedy: true,
                tolerance: 'pointer',
                hoverClass: hover_class,
                drop: function(event, ui) {
                    var drop = $(this);

                    var storage_id = drop.data('id');
                    var current_storage_id = $.files.controller.getCurrentStorageId();
                    if (current_storage_id === storage_id) {
                        return;
                    }
                    var drag_files = $.files.config.getFileList().find('.f-catalog-item.selected');

                    var file_ids = drag_files.map(function() {
                        return $(this).data('id');
                    }).toArray();

                    $.files.config.getFileList().trigger('move_files', [{
                        file_ids: file_ids,
                        storage_id: storage_id,
                        done: function() {
                            drag_files.hide();
                        },
                        error: function() {
                            drag_files.show();
                        }
                    }]);

                }
            });
        },

        initDropSharedFolders: function() {
            var sidebar = $.files.config.getSidebar();
            $('.f-shared-folders > li', sidebar).liveDroppable({
                disabled: false,
                greedy: true,
                tolerance: 'pointer',
                hoverClass: hover_class,
                drop: function(event, ui) {
                    var drop = $(this);

                    var folder_id = drop.data('id');
                    var drag_files = $.files.config.getFileList().find('.f-catalog-item.selected');

                    var file_ids = drag_files.map(function() {
                        return $(this).data('id');
                    }).toArray();

                    drag_files.hide();

                    $.files.config.getFileList().trigger('move_files', [{
                        file_ids: file_ids,
                        folder_id: folder_id,
                        error: function() {
                            drag_files.show();
                        }
                    }]);

                }
            });
        },

        initDropTrash: function() {
            var sidebar = $.files.config.getSidebar();
            $('.f-trash-files-wrapper', sidebar).liveDroppable({
                disabled: false,
                greedy: true,
                tolerance: 'pointer',
                hoverClass: hover_class,
                drop: function(event, ui) {
                    var drag_files = $.files.config.getFileList().find('.f-catalog-item.selected');
                    var file_ids = drag_files.map(function() {
                        return $(this).data('id');
                    }).toArray();

                    $.files.config.getFileList().trigger('move_files_to_trash', [{
                        file_ids: file_ids
                    }]);

                }
            });
        },

        initDropFilters: function() {
            var sidebar = $.files.config.getSidebar();

            $(".f-filter-list-wrapper", sidebar).liveDroppable({
                disabled: false,
                greedy: true,
                tolerance: 'pointer',
                hoverClass: hover_class,
                drop: function() {
                    var drag_files = $.files.config.getFileList().find('.f-catalog-item.selected');
                    var file_ids = drag_files.map(function() {
                        return $(this).data('id');
                    }).toArray();

                    $.files.config.getFileList().trigger('make_filter', [{
                        file_ids: file_ids
                    }]);

                    $(this).removeClass(hover_class);
                }
            });
        },

        // when scrolling page drag-n-drop helper must moving too with cursor
        _scrolHelper: function(e) {
            var helper = $('#products-helper'),
                prev_scroll_top = helper.data('scrollTop'),
                scroll_top = $(document).scrollTop(),
                shift = prev_scroll_top ? scroll_top - prev_scroll_top : 50;

            helper.css('top', helper.position().top + shift + 'px');
            helper.data('scrollTop', scroll_top);
        },

        // active/inactive drop-item both left and right
        _extDragActivate: function(e, self, className) {
            var classNameOfLast = className + '-last';
            if (!self.hasClass('last')) {
                self.addClass('drag-active');
                return;
            }
            var pageX = e.pageX,
                pageY = e.pageY,
                self_width = self.width(),
                self_height = self.height(),
                self_offset = self.offset();

            if ($.files.dragndrop.options.view == 'thumbs') {
                if (pageX > self_offset.left + self_width*0.5 && pageX <= self_offset.left + self_width) {
                    self.removeClass(className).addClass(classNameOfLast);
                    $.files.dragndrop._shiftToLeft(self);
                } else if (pageX > self_offset.left && pageX <= self_offset.left + self_width*0.5) {
                    self.removeClass(classNameOfLast).addClass(className);
                    $.files.dragndrop._shiftToRight(self);
                } else {
                    $.files.dragndrop._shiftAtPlace(self);
                }
            } else if ($.files.dragndrop.options.view == 'table') {
                if (pageY > self_offset.top + self_height*0.5) {
                    self.removeClass(className).addClass(classNameOfLast);
                } else if (pageY > self_offset.top) {
                    self.removeClass(classNameOfLast).addClass(className);
                }
            }
            if (pageY < self_offset.top || pageY > self_offset.top + self_height ||
                    pageX < self_offset.left || pageX > self_offset.left + self_width)
            {
                self.removeClass(className).removeClass(classNameOfLast);
            }
        },

        _bindExtDragActivate: function(item, className) {
            $(document).bind('mousemove.ext_drag_activate', function (e) {
                $.files.dragndrop._extDragActivate(
                    e,  item, className
                );
            });
        },

        _unbindExtDragActivate: function() {
            $(document).unbind('mousemove.ext_drag_activate');
        },

        _activatePhotoListItem: function() {
                var self = $(this);
                var sort_enable = $.files.dragndrop.trigger('is_product_sortable');
                var className = sort_enable ? 'drag-active' : 'drag-active-disabled';
                if (sort_enable && $.files.dragndrop.options.view == 'thumbs') {
                    $.files.dragndrop._shiftToRight(self);
                }
                if (self.hasClass('last')) {
                    $.files.dragndrop._bindExtDragActivate(self, className);
                } else {
                    self.addClass(className);
                }
        },

        // clear (unactive) action
        _unactivatePhotoListItem: function() {
            var self = $(this);
            var sort_enable = $.files.dragndrop.trigger('is_product_sortable');
            var className =  sort_enable ? 'drag-active' : 'drag-active-disabled';
            var classNameOfLast = className + '-last';
            if (self.hasClass('last')) {
                $.files.dragndrop._unbindExtDragActivate();
            }
            self.removeClass(className + ' ' + classNameOfLast);
            if (sort_enable && $.files.dragndrop.options.view == 'thumbs') {
                $.files.dragndrop._shiftAtPlace(self);
            }
        },

        _shiftToLeft: function(item) {
            if (item.data('shifted') !== 'left') {
                var wrapper = item.find('.p-wrapper');
                if (!wrapper.length) {
                    var children = item.children();
                    var wrapper = $("<div class='p-wrapper' style='position:relative;'></div>").appendTo(item);
                    wrapper.append(children);
                }
                wrapper.stop().animate({
                    left: -15
                }, 200);
                item.data('shifted', 'left');
            }
        },
        _shiftToRight: function(item) {
                if (item.data('shifted') !== 'right') {
                    var wrapper = item.find('.p-wrapper');
                    if (!wrapper.length) {
                        var children = item.children();
                        var wrapper = $("<div class='p-wrapper' style='position:relative;'></div>").appendTo(item);
                        wrapper.append(children);
                    }
                    wrapper.stop().animate({
                        left: 15
                    }, 200);
                    item.data('shifted', 'right');
                }
        },
        _shiftAtPlace: function(item) {
            if (item.data('shifted')) {
                var wrapper = item.find('.p-wrapper');
                if (wrapper.length) {
                    var children = wrapper.children();
                    wrapper.stop().css({
                        left: 0
                    });
                    item.append(children);
                    wrapper.remove();
                }
                item.data('shifted', '');
            }
        }

    };

    function showSortHint() {
        var sort_method = $.files.dragndrop.options.sort;
        if (sort_method) {
            var block = $('#hint-menu-block').show();
            block.children().hide();
            block.find('.' + sort_method).show();
        }
    }

    function hideSortHint() {
            var block = $('#hint-menu-block').hide();
            block.children().hide();
    }

})(jQuery);
