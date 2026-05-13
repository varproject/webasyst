devapiSummary = {
    props: ['runner', 'finish'],
    emits: ['run', 'finish', 'error'],
    data: function () {
        return {
            loaded: false,
            accounts: false,
            accountId: false,
            summary: false,
            filters: false,
            options: false,
            settings: false,
            list_type: false
        };
    },
    template: {$menuSummary},
    mounted: function () {
        $.post('?module=menu&action=getSummary', r => {
            if (r.status === 'ok') {
                this.accounts = r.data.accounts;
                this.accountId = r.data.accountId;
                this.filters = r.data.filters;
                this.options = r.data.options;
                this.settings = r.data.settings;
                this.loaded = true;
                this.list_type = this.filters.list_type;
                if (this.accountId) this.updateSummary();
            } else this.$emit('error', r.errors);
        });
    },
    computed: {
        pagination() {
            if (!this.summary || !this.summary.records.length || !this.filters.limit) return;
            let total = Math.ceil(this.summary.count / this.summary.limit);
            let current = this.summary.offset / this.summary.limit + 1;
            let result = [];
            let points = false;
            for (let i=1; i<=total; i++) {
                if (i<3 || i>total-2 || i===current || (i>=current-2 && i<current) || (i<=current+2 && i > current)) {
                    result.push(i * this.summary.limit);
                    points = false;
                } else {
                    if (!points) {
                        result.push('...');
                        points = true;
                    }
                }
            }
            return result;
        },
    },
    watch: {
        list_type: function () {
            if (this.list_type === 'period') {
                this.summary.records = [];
                this.summary.compares.sum = 0;
                this.summary.compares.count = 0;
                this.filters.period.from = null;
                this.filters.period.to = null;
            }
        }
    },
    methods: {
        setFilter: function (value, type) {
            if (
                !this.filters.hasOwnProperty(type) ||
                (type === 'offset' && !this.summary.records.length)
            ) return;
            this.list_type = value;
            if(['products'].includes(type)) {
                if (value) this.filters[type] = [value];
                else this.filters[type] = [];
            }
            else if(this.filters[type] === value) return;
            else this.filters[type] = value;
            if (!['offset'].includes(type)) this.filters.offset = 0;
            if (value !== 'period') this.updateSummary();
        },
        changeAccount: function (acc_id) {
            this.accountId = acc_id;
            this.filters.offset = 0;
            this.filters.products = [];
            this.updateSummary();
        },
        updateSummary: function () {
            this.$emit('run', 'getSummary');
            $.post('?action=updateSummary', { account_id: this.accountId, filters: this.filters}, r=> {
                if (r.status === 'ok') {
                    this.summary = r.data.summary;
                    this.options.products = r.data.products;
                    let idx = this.accounts.findIndex(acc => { return acc.id === this.accountId});
                    if (idx >= 0) this.accounts[idx].new_transactions = 0;
                    this.$emit('run', false);
                    this.updateToolbarCounter();
                    $('html, body').animate({ scrollTop: 0}, 300);
                } else {
                    this.summary.records = [];
                    this.summary.count = 0;
                    this.summary.sum = 0;
                    this.summary.compares = { count: 0, sum: 0};
                    this.$emit('error', r.errors);
                }
            });
        },
        getPaginationOffset: function (offset) {
            if (!Number.isInteger(offset)) {
                if (offset === 'left') {
                    if ((this.summary.offset - this.summary.limit) >= 0) {
                        offset = this.summary.offset;
                    } else return 0;
                } else {
                    if (((this.summary.offset - 0) + (this.summary.limit-0)) < this.summary.count) {
                        offset = this.summary.offset - 0 + this.summary.limit * 2;
                    } else return this.summary.offset;
                }
            }
            return offset - this.summary.limit;
        },
        viewProducts: function () {
            this.$emit('run', 'viewProducts');
            $.post('?action=productsList', { account_id: this.accountId}, r => {
                if (r.status === 'ok') {
                    $.waDrawer({
                        html: r.data,
                        direction: "right",
                        onBgClick: function (event, $drawer, drawer_instance) {
                            drawer_instance.close();
                        },
                        onClose: function(drawer_instance) {
                            accProducts.unmount();
                            accProducts = undefined;
                        },
                    });
                } else $.waDialog.alert({
                    title: "Ошибка",
                    text: r.errors.join('; '),
                    button_title: 'Закрыть'
                });
                this.$emit('run', false);
            });
        },
        viewPromos: function () {
            this.$emit('run', 'viewPromos');
            $.post('?action=promocodesList', { account_id: this.accountId}, r => {
                if (r.status === 'ok') {
                    $.waDrawer({
                        html: r.data,
                        direction: "right",
                        onBgClick: function (event, $drawer, drawer_instance) {
                            drawer_instance.close();
                        },
                        onClose: function(drawer_instance) {
                            promocodes.unmount();
                            promocodes = undefined;
                        },
                    });
                } else $.waDialog.alert({
                    title: "Ошибка",
                    text: r.errors.join('; '),
                    button_title: 'Закрыть'
                });
                this.$emit('run', false);
            });
        },
        checkOrderLicense: function (value = null) {
            this.$emit('run', 'orderInfo');
            $.post('?action=getOrderInfoDialog', { account_id: this.accountId, value: value}, r => {
                if (r.status === 'ok') {
                    this.$emit('run', false);
                    $.waDrawer({
                        html: r.data,
                        direction: "right",
                        width: "750px",
                        onOpen: function($drawer, drawer_instance) {

                        },
                        onClose: function(drawer_instance) {
                            orderinfo.unmount();
                            orderinfo = undefined;
                        },
                        onBgClick: function (event, $drawer, drawer_instance) {
                            drawer_instance.close();
                        }
                    });
                } else this.$emit('error', r.errors);
            });
        },
        getProductName: function (slug) {
            let idx = this.options.products.findIndex( el => { return el.slug===slug});
            if (idx>=0) return this.options.products[idx].name;
            else return 'Не удалось определить название продукта';
        },
        getComparePercent: function () {
            let percents = (this.summary.sum/this.summary.compares.sum*100-100).toFixed(2);
            if (this.summary.compares.sum < 0 && this.summary.sum >0 && percents < 0) percents = Math.abs(percents);
            return percents;
        },
        getDate: function (method, date_string) {
            return $.wrlDates[method](new Date(date_string));
        },
        formatCurrency: function (sum, currency = "RUB", remote_null = false) {
            if (remote_null) {
                let idx = this.accounts.findIndex( acc => { return acc.id === this.accountId});
                if (idx >= 0 && this.accounts[idx].is_remote == 1 && sum == 0) return '---';
            }
            return new Intl.NumberFormat("ru-RU", { style: "currency", currency: currency }).format(sum);
        },
        getAccountLiClass: function (acc_id) {
            return this.accountId === acc_id ? 'selected' : '';
        },
        updateToolbarCounter: function () {
            let value = 0;
            let idx = this.accounts.findIndex( acc => { return acc.id == this.summary.account_id});
            if (idx >= 0) this.accounts[idx].new_transactions = 0;
            this.accounts.forEach(acc => {
                value += parseInt(acc.new_transactions);
            });
            let target = $('#wa-app-devapi').find('a > span.indicator');
            if (value) $(target).html(value);
            else $(target).remove();
        }
    }
};