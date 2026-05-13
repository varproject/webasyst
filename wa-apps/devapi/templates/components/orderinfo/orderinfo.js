orderinfo = Vue.createApp({
    data() {
        return {
            accounts: {$accounts},
            accountId: {$accountId},
            value: {$value},
            history: [],
            runAction: false,
            errorText: false,
            discounts: {$discounts|json_encode}
        }
    },
    mounted: function () {
        let that = this;
        if (this.value) this.getInfo(this.value);
        $("#check-accounts-list").waToggle({
            change: function(event, target, toggle) {
                let value = $(target).data('value');
                that.accountId = value;
            }
        });
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
        getInfo: function (v = null) {
            if (!v) return;
            if (typeof v === 'string') v = v.replace(/^https?:\/\/|/, '').replace(/\/$/, '');
            let idx = this.history.findIndex( el => { return el.value == v});
            if (idx >= 0) {
                let h = JSON.parse(JSON.stringify(this.history[idx]));
                this.history.splice(idx , 1);
                this.history.unshift(h);
                return;
            }
            this.runAction = 'checkInfo';
            $.post('{$wa_backend_url}devapi/?action=checkOrder', { account_id: this.accountId, value: v}, r => {
                if (r.status === 'ok') {
                    this.history.unshift(r.data);
                } else this.errorText = r.errors.join('; ');
                this.runAction = false;
            });
        },
        getDiscountByType: function (type) {
            let name = type;
            if (this.discounts.hasOwnProperty(type)) name = this.discounts[type];
            return name;
        },
        formatDate: function (method, date_string) {
            return $.wrlDates[method](new Date(date_string));
        },
        formatCurrency: function (sum, currency = "RUB") {
            return new Intl.NumberFormat("ru-RU", { style: "currency", currency: currency }).format(sum);
        },
        checkLicenseType: function (datetime) {
            let name = 'Бессрочная';
            if (datetime) {
                name = 'до ' + this.formatDate('humanDate', datetime);
            }
            return name;
        },
        getProductName: function (slug) {
            let name = slug;
            let accIdx = this.accounts.findIndex( acc => { return acc.id == this.accountId});
            if (accIdx < 0) return name;
            let idx = this.accounts[accIdx].products.findIndex( p => { return p.slug === slug});
            if (idx >= 0) {
                if (this.accounts[accIdx].products[idx].name.length) name = this.accounts[accIdx].products[idx].name;
            }
            return name;
        }
    }
});
orderinfo.component('drawerButton', actionButton);
orderinfo.mount('div#devapi-order-info');

