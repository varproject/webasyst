reseller = Vue.createApp({
    data() {
        return {
            filter: '',
            types: ['APP', 'THEME', 'PLUGIN', 'WIDGET'],
            resellers: {$resales},
            runAction: false,
            errorText: false,
            waUrl: "{$wa_backend_url}"
        }
    },
    mounted: function () {
    },
    watch: {
        errorText: function () {
            if (this.errorText !== false) {
                setTimeout(() => {
                    this.errorText = false;
                }, 5000);
            }
        }
    },
    methods: {
        checkFilter: function (s) {
            let checker = true;
            if (this.filter.length) {
                checker = false;
                if (s.product_name.toUpperCase().indexOf(this.filter.toUpperCase()) >= 0) checker = true;
                if (s.slug.toUpperCase().indexOf(this.filter.toUpperCase()) >= 0) checker = true;
                if (s.developer_name.toUpperCase().indexOf(this.filter.toUpperCase()) >= 0) checker = true;
            }
            if (!this.types.includes(s.type)) checker = false;
            return checker;
        },
        getChipClass: function (type) {
            let chipClass = '';
            if (this.types.includes(type)) chipClass = 'accented';
            return chipClass;
        },
        setFilterType: function (type) {
            let idx = this.types.findIndex( el => { return el === type});
            if (idx < 0) this.types.push(type);
            else this.types.splice(idx ,1);
        },
        formatCurrency: function (sum, currency = "RUB") {
            return new Intl.NumberFormat("ru-RU", { style: "decimal", currency: currency }).format(sum);
        },
        formatDate: function (method, date_string) {
            return $.wrlDates[method](new Date(date_string));
        }
    }
});
reseller.component('drawerButton', actionButton);
reseller.mount('div#devapi-reseller');