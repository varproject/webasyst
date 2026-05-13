//live draggable and live droppable


$.fn.liveDraggable = function (opts) {
    this.each(function(i,el) {
        var self = $(this);
        if (self.data('init_draggable')) {
            self.off("mouseover", self.data('init_draggable'));
        }
    });
    this.off("mouseover").on("mouseover", function() {
        var self = $(this);
        if (!self.data("init_draggable")) {
            self.data("init_draggable", arguments.callee).draggable(opts);
        }
    });
};
$.fn.liveDroppable = function (opts) {
    this.each(function(i,el) {
        var self = $(this);
        if (self.data('init_droppable')) {
            self.off("mouseover", self.data('init_droppable'));
        }
    });
    var init = function() {
        var self = $(this);
        if (!self.data("init_droppable")) {
            self.data("init_droppable", arguments.callee).droppable(opts);
            self.mouseover();
        }
    };
    init.call(this);
    this.off("mouseover", init).on("mouseover", init);
};
