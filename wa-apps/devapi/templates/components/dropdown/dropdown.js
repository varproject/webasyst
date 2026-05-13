wrlDropdown = {
    props: ["options", "search", "hover", "id", "name", "ddstyle", "ddclass", "value", "trigger", "update_title", "is_remote", "main_style"],
    emits: ['update'],
    data: function () {
        return {
            filter: ''
        };
    },
    watch: {
        trigger: function () {
            $('#' + this.id).find('.dropdown-trigger').trigger('change');
        }
    },
    mounted: function () {
        this.$nextTick( () => {
            let that = this;
            $('#' + this.id).waDropdown({
                hover: that.hover,
                update_title: that.update_title,
                items: ".menu > li > a",
                ready: function(dropdown) {
                    if (that.modelValue) {
                        let el = $('div#' + that.id).find('a[data-id="' + that.modelValue + '"]');
                        if (el.length) $(el).trigger('click');
                        else that.$emit('update:modelValue', '');
                    } else if (that.value !== undefined) {
                        dropdown.$button.text(dropdown.$menu.find("a[data-id='" + that.value + "']").text());
                    }
                    $('#' + that.id).on('change', '.dropdown-trigger', function () {
                        if (that.value !== undefined) {
                            dropdown.$button.text(dropdown.$menu.find("a[data-id='" + that.value + "']").text());
                        }
                    });
                },
                change: function(event, target, dropdown) {
                    let value = $(target).data('id');
                    that.$emit('update', value);
                },
                close: function (event, active_item, dropdown_instance) {
                    //let sleep = setTimeout( () => { that.filter = '';}, 1000);
                }
            });
        });
    },
    computed: {
        getOptions: function () {
            let options = [];
            if (typeof this.options === 'Array') {
                this.options.forEach((op, idx) => {
                    if (typeof op === 'String') options.push({ id: idx, name: op});
                    else options.push(op);
                });
            } else {
                Object.keys(this.options).forEach((op, idx) => {
                    if (typeof this.options[op] === 'string') options.push({ id: op, name: this.options[op]});
                    else options.push(this.options[op]);
                });
            }
            if (!this.filter) return options;
            return options.filter( el => {
                return !el.hasOwnProperty('id') || el.name.toLowerCase().indexOf(this.filter.toLowerCase()) >= 0;
            });
        },
        getClass: function () {
            return 'dropdown ' + this.ddclass;
        },
        getStyle: function () {
            return 'text-align: left !important;' + this.ddstyle;
        },
        getMainStyle: function () {
            let value = 'margin-bottom: 20px;';
            if (this.main_style) value = this.main_style;
            return value;
        }
    },
    methods: {
        checkOption: function (id) {
            let result = true;
            if (id === 'info' && this.is_remote !== 1) result = false;
            if (id === 'partner' && this.is_remote === 1) result = false;
            return result;
        }
    },
    template: {$wrlDropdown}
};