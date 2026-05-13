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
        that.localStorage = $.files.config.getLocaleStorage();
        that.storage_name = "files/sidebar_width";

        // DYNAMIC VARS
        that.width = $.files.config.getSidebarWidth() || false;
        that.active_width_class = "left" + that.width + "px";

        // INIT
        that.initSidebar();
    };

    Sidebar.prototype.initSidebar = function() {
        var that = this;

        that.initWidth();

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

        $sidebar.on("click", "#s-category-list-widen-arrows .arrow", function() {
            var $arrow = $(this),
                is_right = $arrow.hasClass("right");
            that.changeWidth( is_right );
            return false;
        });
    };

    Sidebar.prototype.changeWidth = function( is_right ) {
        var that = this,
            $sidebar = that.$sidebar,
            width = that.width,
            active_width_class = that.active_width_class,
            max_width = 400,
            min_width = 200,
            new_width,
            new_class,
            step = 50;

        new_width = (is_right) ? ( width + step ) : ( width - step );
        new_width = (new_width > max_width) ? max_width : new_width;
        new_width = (new_width < min_width) ? min_width : new_width;
        new_class = "left" + new_width + "px";

        // Clear old class
        if ($sidebar.hasClass(active_width_class)) {
            $sidebar.removeClass(active_width_class);
        }

        // Render
        $sidebar.addClass(new_class);

        // Save data
        that.width = new_width;
        that.active_width_class = new_class;
        localStorage.setItem(that.storage_name, new_width);
    };

    Sidebar.prototype.initWidth = function() {
        var that = this,
            $sidebar = that.$sidebar,
            active_width_class = that.active_width_class,
            new_width = ( localStorage.getItem(that.storage_name) || false );

        if (new_width && new_width > 0 && (new_width != that.width) ) {
            var new_class = "left" + new_width + "px";

            // Clear old class
            if ($sidebar.hasClass(active_width_class)) {
                $sidebar.removeClass(active_width_class);
            }

            // Render
            $sidebar.addClass(new_class);

            // Save data
            that.width = new_width;
            that.active_width_class = new_class;
        }

        setTimeout( function() {
            $sidebar.addClass("is-animated");
        }, 100);

    };

    return Sidebar;

})(jQuery);