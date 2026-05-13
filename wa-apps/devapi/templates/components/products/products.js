accProducts = Vue.createApp({
    data() {
        return {
            filter: '',
            chips: { market: true, money: false},
            products: {$products},
            prices: {$prices},
            runAction: false,
            extData: {$extData},
            isRemote: {$isRemote},
            errorText: false,
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
    computed: {
    },
    methods: {
        getProductPrice: function (p) {
            let price = parseInt(p.price);
            if (this.prices.hasOwnProperty(price)) {
                price = this.formatCurrency(this.prices[price].RUB, 'RUB');
            }
            if (!price) price = 'Free';
            return price;
        },
        getProductClass: function (p) {
            let trClass = '';
            if (!p.published_version) trClass = 'disabled';
            return trClass;
        },
        getProductName: function (slug) {
            let name = slug;
            let idx = this.products.findIndex( p => { return p.slug === slug});
            if (idx >= 0) {
                if (this.products[idx].name.length) name = this.products[idx].name;
            }
            return name;
        },
        filterProduct: function (p) {
            let checker = true;
            if (this.filter) {
                checker = p.name.toUpperCase().indexOf(this.filter.toUpperCase()) >= 0 || p.slug.toUpperCase().indexOf(this.filter.toUpperCase()) >= 0;
            }
            if (this.chips.money && p.price == 0) checker = false;
            if (this.chips.market) {
                if (!p.published_version || p.discontinued_at !== null) checker = false;
            }
            return checker;
        },
        getDiscontinuedTitle: function (date) {
            return 'Снято с продажи ' + this.formatDate('humanDate', date)
        },
        getAwaitingText: function (p) {
            let title = p.awaiting_version;
            let part = p.awaiting_status;
            switch (p.awaiting_status.toUpperCase()) {
                case 'DRAFT':
                    part = '(черновик)';
                    break;
                case 'PENDING':
                case 'PENDING-CONF':
                    part = '(на модерации)';
                    break;
                case 'BETA':
                case 'TEST':
                    part = '(beta)';
                    break;
                case 'REJECTED':
                    part = '(отказано в публикации)';
                    break;
            }
            return title + ' ' + part;
        },
        changeChip: function (type) {
            this.chips[type] = !this.chips[type];
        },
        getChipClass: function (type) {
            let chipClass = '';
            if (this.chips[type]) chipClass = 'accented';
            return chipClass;
        },
        openProductPage: function (product_id = null) {
            if (!this.isRemote && product_id) {
                window.open('https://www.webasyst.ru/my/#/developer/product/' + product_id);
            }
        },
        formatCurrency: function (sum, currency = "RUB") {
            return new Intl.NumberFormat("ru-RU", { style: "decimal", currency: currency }).format(sum);
        },
        formatDate: function (method, date_string) {
            return $.wrlDates[method](new Date(date_string));
        }
    }
});
accProducts.component('drawerButton', actionButton);
accProducts.mount('div#devapi-acc-products');