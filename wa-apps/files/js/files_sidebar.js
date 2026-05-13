var Sidebar = ( function($) {

    Sidebar = function(options) {
        var that = this;

        if (!$.isPlainObject(options)) {
            options = {
                '$sidebar': options || $()
            };
        }

        // DOM
        that.$sidebar = ( options['$sidebar'] || false );

        // VARS

        // DYNAMIC VARS

        // INIT
        that.initSidebar();
    };

    Sidebar.prototype.initSidebar = function() {
        var that = this;

        that.bindEvents();
    };

    Sidebar.prototype.bindEvents = function() {
        var that = this,
            $sidebar = that.$sidebar;

        // Selected active link
        $sidebar.on("click", "ul li a", function() {
            $sidebar.find(".selected").removeClass("selected");
            $(this).closest("li").addClass("selected");
        });
    };

    return Sidebar;

})(jQuery);
