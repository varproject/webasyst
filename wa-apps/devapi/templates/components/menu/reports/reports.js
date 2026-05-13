let devapiReports = {
    props: ['runner', 'finish'],
    emits: ['run', 'finish', 'error'],
    data: function () {
        return {
            loaded: false,
            accounts: false,
            params: false,
            options: false,
            charts: false,
            chart: false,
            cChart: { type: false, variant: false, period: false},
            cAccount: false,
        };
    },
    template: {$menuReports},
    mounted: function () {
        $.post('?module=menu&action=getReports', r => {
            if (r.status === 'ok') {
                this.accounts = r.data.accounts;
                this.options = r.data.options;
                if (this.accounts && this.accounts.length) {
                    this.cAccount = JSON.parse(JSON.stringify(this.accounts[0]));
                }
                this.resetParams();
                this.loaded = true;
            } else this.$emit('error', r.errors);
        });
    },
    computed: {
        chartEidth: function () {
            return this.cChart.type === 'pie' ? 'width: 700px;' : 'width: 700px;';
        }
    },
    watch: {
    },
    methods: {
        generateChart: function () {
            this.$emit('run', 'generateChart');
            $.post('?action=generateChart', { account_id: this.cAccount.id, params: this.params}, r => {
                if (r.status === 'ok') {
                    this.charts = JSON.parse(JSON.stringify(r.data.charts));
                    let chartType = this.cChart.type ? this.cChart.type : 'bar';
                    let chartVariant = this.cChart.variant ? this.cChart.variant : 'cnt';
                    let chartPeriod = this.cChart.period ? this.cChart.period : 'summary';
                    this.setChart(chartType, r.data.charts[chartType][chartPeriod][chartVariant].datasets, r.data.charts[chartType][chartPeriod][chartVariant].labels);
                    this.$emit('finish');
                } else this.$emit('error', r.errors);
            });
        },
        setChart: function(type, datasets, labels = []) {
            if (type === 'table') return;
            if (this.chart !== false) this.chart.destroy();
            else {
                this.cChart = {
                    type: type,
                    period: 'summary',
                    variant: 'amounts'
                };
                this.$nextTick( () => {
                    let that = this;
                    $('.devapi-chart.toggle').waToggle({
                        change: function (event, target, toggle) {
                            let type = $(target).data('type');
                            let value = $(target).data('id');
                            that.cChart[type] = value;
                            let chart = JSON.parse(JSON.stringify(that.charts[that.cChart.type][that.cChart.period][that.cChart.variant]));
                            that.setChart(that.cChart.type, chart.datasets, chart.labels);
                        }
                    });
                });
            }
            let target_id = 'devapi-' + this.cChart.type + '-chart';
            this.chart = new Chart(
                document.getElementById(target_id),
                {
                    type: type,
                    data: {
                        labels: labels,
                        datasets: datasets
                    }
                }
            );
        },
        getDataTable: function (type) {
            let source = JSON.parse(JSON.stringify(this.charts.bar[this.cChart.period][this.cChart.variant]));
            if (type === 'datasets' && this.cChart.period === 'summary') {
                source[type] = source[type].sort((a, b) => b.data[0] - a.data[0]);
            }
            return source[type];
        },
        getCSV: function () {
            this.$emit('run', 'generateCSV');
            $.post('?action=getCsvReport', { account_id: this.cAccount.id, params: this.params, options: this.cChart}, r => {
                if (r.status === 'ok') {
                    window.open(r.data.url);
                } else this.$emit('error', r.errors);
                this.$emit('finish');
            });
        },
        setSelected: function (type, id) {
            let idx = this.params.selected[type].findIndex( el => { return el === id});
            if (idx >= 0) this.params.selected[type].splice(idx, 1);
            else this.params.selected[type].push(id);
        },
        changeDropDown: function (type, value) {
            if (this.params[type] === value) return;
            let els = type === 'products' ? ['products', 'product_types'] : ['transaction_types'];
            els.forEach( field => {
                this.params.selected[field] = [];
            });
            this.params[type] = value;
        },
        selectAll: function (type = null) {
            let items = [];
            if (type === null) type = this.params.products === 'selected' ? 'products' : 'product_types';
            switch (type) {
                case 'products':
                    this.cAccount.products.forEach( el => {
                        if (el.published_version) items.push(el.slug);
                    });
                    break;
                case 'product_types':
                case 'transaction_types':
                    items = Object.keys(this.options[type]);
                    break;
            }
            if (this.params.selected[type].length === items.length) this.params.selected[type] = [];
            else this.params.selected[type] = items;
        },
        getItems: function () {
            let items = this.cAccount.products;
            if (this.params.type !== 'products') {
                items = this.options[this.params.type];
            }
            return items;
        },
        resetParams: function () {
            this.params = {
                period: 'month',
                period_free: { from: '', to: ''},
                products: 'all',
                transaction_types: 'all',
                selected: {
                    transaction_types: [],
                    products: [],
                    product_types: []
                }
            }
        },
        setAccount: function (acc_id) {
            let idx = this.accounts.findIndex( el => { return acc_id===el.id});
            if (idx >= 0) {
                this.cAccount = JSON.parse(JSON.stringify(this.accounts[idx]));
            }
            else this.$emit('Не удалось определить аккаунт');
        },
        getAccountLiClass: function (acc_id) {
            return this.cAccount.id == acc_id ? 'selected' : '';
        }
    }
};