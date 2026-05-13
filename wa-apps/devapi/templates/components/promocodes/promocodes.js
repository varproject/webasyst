promocodes = Vue.createApp({
    data() {
        return {
            accountId: {$accountId},
            accounts: {$accounts},
            filter: '',
            dialog: false,
            newPromo: false,
            waUrl: "{$wa_backend_url}",
            runAction: false,
            errorText: false,
    }
    },
    mounted: function () {
        let that = this;
        this.newPromo = this.getTemplate('promo');
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
    computed: {
        countPromocodes: function () {
            let count = 0;
            let idx = this.getCurrentAccountIdx();
            if (idx >= 0) {
                count = this.accounts[idx].promocodes.length;
            }
            return count;
        }
    },
    methods: {
        getPromocodes: function () {
            let codes = [];
            let idx = this.getCurrentAccountIdx();
            if (idx >= 0 && this.accounts[idx].hasOwnProperty('promocodes')) {
                codes = this.accounts[idx].promocodes;
            }
            return codes;
        },
        getCodeProducts: function(code) {
            let products = [];
            let adx = this.getCurrentAccountIdx();
            if (adx >= 0) {
                products = this.accounts[adx].products;
            }
            let names = [];
            code.products.forEach( p => {
                let pdx = products.findIndex( pr => { return pr.slug === p});
                if (pdx >= 0) names.push(products[pdx].name);
                else names.push(p);
            });
            return names;
        },
        getProducts: function () {
            let products = [];
            let adx = this.getCurrentAccountIdx();
            if (adx >= 0) {
                this.accounts[adx].products.forEach(p => {
                    if (p.published_version && parseInt(p.price)) products.push({ slug: p.slug, name: p.name});
                });
            }
            return products;
        },
        deleteCode: function (idx) {
            let promo = false;
            let adx = this.getCurrentAccountIdx();
            if (adx >= 0) promo = this.accounts[adx].promocodes[idx];
            if (promo) {
                let html = promo.code + ' ' + promo.percent + '%';
                let that = this;
                $.waDialog.confirm({
                    title: "Удалить промокод?",
                    text: html,
                    success_button_title: 'Удалить',
                    success_button_class: 'red outlined',
                    cancel_button_title: 'Нет',
                    cancel_button_class: 'light-gray',
                    onSuccess: function () {
                        $.post(that.waUrl + 'devapi/?action=deletePromocode', { account_id: that.accountId, code: promo.code}, r => {
                            if (r.status === 'ok') {
                                that.accounts[adx].promocodes.splice(idx, 1);
                            } else alert (r.errors.join('; '));
                        });
                    }
                });
            } else {
                $.waDialog.alert({
                    title: "Ошибка",
                    text: "Не удалось найти промокод",
                    button_title: 'Закрыть',
                    button_class: 'warning',
                });
            }
        },
        createPromocode: function () {
            let that = this;
            if (this.dialog !== false) this.dialog.show();
            else {
                this.dialog = $.waDialog({
                    $wrapper: $('#dialog-new-promocode'),
                    onOpen: function ($dialog, dialog_instance) {
                        $('body').on('click', '.a-uniquid', function () {
                            $.post(that.waUrl + 'devapi/?action=getUniqueId', r => {
                                if (r.status === 'ok') that.newPromo.code = r.data;
                            });
                        });
                        $('#a-create-promo').on('click', function () {
                            let adx = that.getCurrentAccountIdx();
                            let message = null;
                            if (!that.newPromo.code.length) message = 'Код купона не может быть пустым';
                            if (!that.newPromo.products.length) message = 'Выберите хотя бы один продукт';
                            if (adx < 0) message = 'Не удалось определить текущий аккаунт';
                            if (message) {
                                alert(message);
                                return;
                            }
                            $.post(that.waUrl + 'devapi/?action=createPromocode', {
                                account_id: that.accountId,
                                promo: that.newPromo
                            }, r => {
                                if (r.status === 'ok') {
                                    that.accounts[adx].promocodes.unshift(r.data);
                                    $(dialog_instance).trigger('close');
                                } else alert(r.errors.join('; '));
                            });
                        });
                    },
                    onClose: function (dialog_instance) {
                        dialog_instance.hide();
                        that.newPromo = that.getTemplate('promo');
                        return false;
                    }
                });
            }
        },
        addRemoveProduct: function (slug) {
            let idx = this.newPromo.products.findIndex(p => { return p === slug});
            if (idx >= 0) this.newPromo.products.splice(idx , 1);
            else this.newPromo.products.push(slug);
        },
        getCodePeriod: function (code) {
            let period = '';
            if (code.start_date) period = 'c ' + this.formatDate('humanDateTime', code.start_date);
            if (code.end_date) {
                if (period.length) period += ' ';
                period += 'по ' + this.formatDate('humanDateTime', code.end_date);
            }
            return period.length ? period : 'Бессрочный';
        },
        getTemplate: function (type) {
            let data = null;
            switch (type) {
                case 'promo':
                    data = { code: '', percent: 1, products: [], type: 'single', start_date: null, end_date: null, description: ''};
                    break;
            }
            return data;
        },
        getCurrentAccountIdx: function () {
            return this.accounts.findIndex( acc => { return acc.id == this.accountId});
        },
        formatDate: function (method, date_string) {
            return $.wrlDates[method](new Date(date_string));
        },
        getProductName: function (slug) {
            let name = slug;
            let accIdx = this.accounts.findIndex( acc => { return acc.id === this.accountId});
            if (accIdx < 0) return name;
            let idx = this.accounts[accIdx].products.findIndex( p => { return p.slug === slug});
            if (idx >= 0) {
                if (this.accounts[accIdx].products[idx].name.length) name = this.products[idx].name;
            }
            return name;
        },
        checkPromoProduct: function (slug) {
            return this.newPromo && this.newPromo.products.includes(slug);
        }
    }
});
promocodes.component('drawerButton', actionButton);
promocodes.mount('div#devapi-promocodes');


