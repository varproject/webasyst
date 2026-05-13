let devapiAccounts = {
    props: ['runner', 'finish'],
    emits: ['run', 'finish', 'error'],
    data: function () {
        return {
            loaded: false,
            accounts: false,
            cAccount: false,
            cRemote: false,
            cRule: false,
            cTarget: false,
            cMenu: 'general',
            menuMode: 'view',
            sort: false,
            accActions: [
/*                { id: 'promocodes', name: 'Промокоды...'},
                { id: 'products', name: 'Продукты разработчика...'},
                { id: 'partner', name: 'Партнерская программа WA...'},
                { id: 'info', name: 'Информация о правах доступа...'},*/
                { id: 'update', name: 'Обновить все транзакции...'},
                { id: 'truncate', name: 'Удалить все транзакции...'},
                { id: 'edit', name: 'Изменить аккаунт...'},
                { id: 'delete', name: 'Удалить аккаунт...'}
            ],
            templates: {$templates},
            ruleOptions: { 0: { account: [], category_income: [], category_expense: []}},
            transactionTypes: {$transactionTypes},
            cLi: 'general'
        };
    },
    template: {$menuAccounts},
    mounted: function () {
        $.post('?module=menu&action=getAccounts', r => {
            if (r.status === 'ok') {
                this.accounts = r.data.accounts;
                if (this.accounts && this.accounts.length) {
                    this.cAccount = JSON.parse(JSON.stringify(this.accounts[0]));
                }
                this.loaded = true;
                this.$nextTick( () => {
                    this.enableAccountMenu();
                    $('#devapi-cash-rules').sortable({
                        axis: 'y',
                        update: function () {
                            let rules = $('#devapi-cash-rules').find('li');
                            let data = [];
                            rules.each(function () {
                                data.push($(this).data('rule-id'));
                            });
                            $.post('?action=sortRules', { rules: data}, r => {
                                if (r.status !== 'ok') that.$emit('error', r.errors);
                            }, 'json');
                        }
                    });
                });
            } else this.$emit('error', r.errors);
        });
    },
    computed: {
        checkAppCash: function () {
            if (!this.cAccount || !this.cAccount.cash.targets.length) return false;
            let idx = this.cAccount.cash.targets.findIndex( t => { return t.id === -1});
            return idx >= 0;
        },
        disableRuleProducts: function () {
            return ['payout', 'buy'].includes(this.cRule.transaction_type);
        },
        sorter: function () {
            return this.cAccount && this.cRule === false && this.cAccount.cash.rules.length && this.menuMode === 'view';
        }
    },
    watch: {
        cMenu: function () {
            this.menuMode = 'view';
            this.cRemote = false;
            if (this.cMenu === 'cash') {
                this.$nextTick(() => {
                    this.menuMode = 'tmp';
                    this.menuMode = 'view';
                });
            }
        },
        disableRuleProducts: function () {
            if (this.cRule && this.disableRuleProducts) {
                this.cRule.product_slug = '';
            }
        }
    },
    methods: {
        reloadTransactions: function () {
            this.$emit('run', 'reloadTransactions');
            $.post('?action=reloadTransactions', { account_id: this.cAccount.id}, r => {
                if (r.status === 'ok') {
                    this.$emit('run', false);
                } else this.$emit('error', r.errors);
            });
        },
        runAction: function (action_id) {
            let params = { account_id: this.cAccount.id};
            switch (action_id) {
                case 'promocodes':
                    this.$emit('run', action_id + 'Action');
                    $.post('?action=promocodesList', params, r => {
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
                    break;
                case 'products':
                    this.$emit('run', action_id + 'Action');
                    $.post('?action=productsList', params, r => {
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
                    break;
                case 'partner':
                    this.$emit('run', action_id + 'Action');
                    $.post('?action=reseller', params, r => {
                        if (r.status === 'ok') {
                            $.waDrawer({
                                html: r.data,
                                direction: "right",
                                onBgClick: function (event, $drawer, drawer_instance) {
                                    drawer_instance.close();
                                },
                                onClose: function(drawer_instance) {
                                    reseller.unmount();
                                    reseller = undefined;
                                },
                            });
                        } else $.waDialog.alert({
                            title: "Ошибка",
                            text: r.errors.join('; '),
                            button_title: 'Закрыть'
                        });
                        this.$emit('run', false);
                    });
                    break;
                case 'edit':
                    this.editAccount(this.cAccount.id);
                    break;
                case 'delete':
                    let that = this;
                    $.waDialog.confirm({
                        title: 'Действие необратимо!',
                        text: 'Удалить текущий аккаунт?',
                        success_button_title: 'Удалить!',
                        success_button_class: 'red outlined smallest',
                        cancel_button_class: 'custom-ml-48 green larger',
                        cancel_button_title: 'Закрыть',
                        onSuccess: function () {
                            that.$emit('run', action_id + 'Action');
                            $.post('?action=deleteAccount', params, r => {
                                if (r.status === 'ok') {
                                    let cAccId = that.cAccount.id;
                                    let adx = that.accounts.findIndex( a => { return a.id == cAccId});
                                    if (adx >= 0) that.accounts.splice(adx, 1);
                                    let acc = false;
                                    if (that.accounts.length) acc = JSON.parse(JSON.stringify(that.accounts[0]));
                                    that.cAccount = acc;
                                } else alert(r.errors.join('; '));
                                that.$emit('run', false);
                            });
                        }
                    });
                    break;
                case 'truncate':
                    this.openDialogConfirm(
                        'Удалить все транзакции текущего аккаунта?',
                        'Действие необратимо!',
                        '?action=truncateTransactions',
                        params,
                        'Удалить',
                    );
                    break;
                case 'update':
                    this.openDialogConfirm(
                        'Удалить все транзакции и загрузить заново?<br><br><span class="gray">Индикацию никто не прикрутил, придется подождать около минуты</brspan>',
                        'Загрузка всех транзакций',
                        '?action=updateTransactions',
                        params,
                        'Загрузить',
                        'green',
                        'custom-ml-48 gray outlined'
                    );
                    break;
                case 'info':
                    if (!this.cAccount.is_remote) return;
                    this.$emit('run', 'checkInfo');
                    $.post('?action=checkRemoteInfo', { account_id: this.cAccount.id}, r => {
                        if (r.status === 'ok') {
                            $.waDialog.alert({
                                title: "Информация о правах доступа",
                                text: r.data,
                                button_title: 'Закрыть',
                                button_class: 'gray'
                            });
                            this.$emit('finish');
                        } else this.$emit('error', r.errors);
                    });
                    break;
            }
        },
        openDialogConfirm: function (
            text,
            title = 'Внимание!',
            url,
            params = { },
            success_button_title = 'Ok',
            success_button_class = 'red outlined smallest',
            cancel_button_class = 'custom-ml-48 green larger',
            cancel_button_title = 'Закрыть'
        ) {
            let that = this;
            $.waDialog.confirm({
                title: title,
                text: text,
                success_button_title: success_button_title,
                success_button_class: success_button_class,
                cancel_button_title: cancel_button_title,
                cancel_button_class: cancel_button_class,
                onSuccess: function () {
                    that.$emit('run', 'dialogAction');
                    $.post(url, params, r => {
                        if (r.status === 'ok') {

                        } else alert(r.errors.join('; '));
                        that.$emit('run', false);
                    });
                }
            });
        },
        editAccount: function (acc_id = null) {
            let acc = { id: '', is_remote: 0, name: '', token: '', remote_url: ''};
            if (acc_id) {
                let idx = this.accounts.findIndex( el => { return el.id === acc_id});
                if (idx >= 0) acc = this.accounts[idx];
            }
            let dialog = $('#devapi-dialog-edit-account').clone();
            let that = this;
            $.waDialog({
                html: dialog,
                onOpen: function ($dialog, dialog_instance) {
                    showUrlDiv = function (show = true) {
                        if (show) $(form).find('input[name="remote_url"]').closest('div.field').show();
                        else {
                            $(form).find('input[name="remote_url"]').closest('div.field').hide();
                            $(form).find('input[name="remote_url"]').val('');
                        }
                    }
                    let form = $(dialog).find('form.acc-data');
                    let remote = $(form).find('input[name="is_remote"]');
                    let rmSwitch = $(form).find('span.switch');
                    $(rmSwitch).waSwitch({
                        active: !!acc.is_remote,
                        disabled: !!acc.id,
                        change: function(active, wa_switch) {
                            let v = $(remote).val() == '1' ? 0 : 1;
                            $(remote).val(v);
                            showUrlDiv(!!v);
                        }
                    });
                    $(form).find('h3').html(acc.id ? acc.name.replaceAll(/[<>]/g, '') : 'Новый аккаунт');
                    $(form).find('input[name="name"]').val(acc.name);
                    $(form).find('input[name="id"]').val(acc.id);
                    $(remote).val(acc.is_remote);
                    $(form).find('input[name="remote_url"]').val(acc.remote_url);
                    $(form).find('input[name="token"]').val(acc.token);
                    showUrlDiv(!!acc.is_remote);
                    $('.devapi-dialog-edit-account').on('click', '#account-save', function() {
                        $.post('?action=saveAccount', $(this).closest('form').serialize(), r => {
                            that.$emit('run', 'accountButton');
                            if (r.status === 'ok') {
                                let adx = that.accounts.length;
                                if (acc_id) {
                                    let idx = that.accounts.findIndex( el => { return el.id===acc_id});
                                    if (idx >= 0) adx = idx;
                                }
                                if (acc_id) that.accounts[adx] = r.data;
                                else that.accounts.push(r.data);
                                that.cAccount = r.data;
                                $(dialog_instance).trigger('close');
                            } else that.$emit('error', r.errors);
                            that.$emit('run', false);
                        });
                        return false;
                    });
                }
            });
        },
        setAccount: function (acc_id) {
            let idx = this.accounts.findIndex( el => { return acc_id===el.id});
            if (idx >= 0) {
                this.cMenu = 'general';
                this.menuMode = 'view';
                this.cRule = false;
                this.cTarget = false;
                this.cAccount = JSON.parse(JSON.stringify(this.accounts[idx]));
                this.enableAccountMenu();
            }
            else this.$emit('Не удалось определить аккаунт');
        },
        editRemote: function (remote_id = null) {
            let remote = JSON.parse(JSON.stringify(this.templates.remote));
            if (remote_id) {
                let idx = this.cAccount.remotes.findIndex( el => { return el.id === remote_id});
                if (idx >= 0) remote = JSON.parse(JSON.stringify(this.cAccount.remotes[idx]));
            }
            remote.account_id = JSON.parse(JSON.stringify(this.cAccount.id));
            this.cRemote = remote;
            this.menuMode = 'editRemote';
        },
        saveRemote: function () {
            this.$emit('run', 'saveRemote');
            $.post('?action=saveRemote', { remote: this.cRemote}, r => {
                if (r.status === 'ok') {
                    let idx = this.cAccount.remotes.findIndex( el => { return el.id === r.data.id});
                    if (idx >= 0) this.cAccount.remotes[idx] = r.data;
                    else this.cAccount.remotes.push(r.data);
                    this.cRemote = r.data;
                    this.$emit('finish');
                } else this.$emit('error', r.errors);
            });
        },
        changeRemoteProduct: function (slug) {
            let idx = this.cRemote.products.findIndex( p => { return p === slug});
            if (idx >= 0) this.cRemote.products.splice(idx, 1);
            else this.cRemote.products.push(slug);
        },
        deleteRemote: function () {
            let that = this;
            $.waDialog.confirm({
                title: "<i class=\"fas fa-exclamation-triangle smaller state-error\"></i> Действие необратимо!",
                text: "Удалить подключение " + that.cRemote.name + "?",
                success_button_title: 'Да, удалить',
                success_button_class: 'danger',
                cancel_button_title: 'Оставить',
                cancel_button_class: 'light-gray',
                onSuccess: function () {
                    that.$emit('run', 'deleteRemote');
                    let idx = that.cAccount.remotes.findIndex( rm => { return rm.id === that.cRemote.id});
                    if (idx < 0) {
                        that.$emit('error', 'Не удалось определить удаленное подключение');
                        return;
                    }
                    $.post('?action=deleteRemote', { account_id: that.cAccount.id, remote_id: that.cRemote.id}, r => {
                        if (r.status === 'ok') {
                            that.menuMode = 'view';
                            that.cRemote = false;
                            that.cAccount.remotes.splice(idx, 1);
                            that.$emit('finish');
                        } else that.$emit('error', r.errors);
                    });
                }
            });
        },
        editRule: function (rule_id = null) {
            this.$emit('run', 'eidtRule');
            let rule = JSON.parse(JSON.stringify(this.templates.rule));
            rule.account_id = this.cAccount.id;
            if (rule_id) {
                let idx = this.cAccount.cash.rules.findIndex( r => { return r.id === rule_id});
                if (idx >= 0) rule = JSON.parse(JSON.stringify(this.cAccount.cash.rules[idx]));
            }
            this.cRule = rule;
            this.$emit('run', false);
        },
        saveRule: function () {
            this.$emit('run', 'saveRule');
            $.post('?action=saveCashRule', this.cRule, r => {
                if (r.status === 'ok') {
                    let rule = r.data;
                    let idx = this.cAccount.cash.rules.findIndex( rule => { return rule.id === r.data.id});
                    if (idx >= 0) this.cAccount.cash.rules[idx] = r.data;
                    else this.cAccount.cash.rules.push(r.data);
                    this.cRule = false;
                    this.$emit('finish');
                } else this.$emit('error', r.errors);
            });
        },
        deleteRule: function (rule_id) {
            let idx = this.cAccount.cash.rules.findIndex( rule => { return rule.id === rule_id});
            if (idx < 0) {
                $.waDialog.alert({
                    text: 'Не удалось найти правило с id ' + rule_id,
                    button_title: 'Закрыть'
                });
                return;
            }
            if (!confirm('Удалить правило?')) return;
            this.$emit('run', 'deleteRule');
            $.post('?action=deleteRule', { account_id: this.cAccount.id, id: rule_id}, r => {
                if (r.status === 'ok') {
                    this.cAccount.cash.rules.splice(idx, 1);
                    this.$emit('finish');
                    this.cRule = false;
                }
                else this.$emit('error', r.errors);
            });
        },
        changeRuleTarget: function () {
            this.checkOptions(this.cRule.target_id);
        },
        getRuleOptions: function (type) {
            let target_id = JSON.parse(JSON.stringify(this.cRule.target_id));
            if (!target_id) return [];
            let options = JSON.parse(JSON.stringify(this.ruleOptions[0][type]));
            if (this.ruleOptions.hasOwnProperty(target_id)) {
                options = this.ruleOptions[target_id][type];
            } else {
                this.ruleOptions[this.cRule.target_id] = JSON.parse(JSON.stringify(this.ruleOptions[0]));
                $.post('?action=getTargetRuleOptions', { target_id: target_id}, r => {
                    if (r.status === 'ok') {
                        this.ruleOptions[target_id] = r.data;
                    } else this.$emit('error', r.errors);
                });
            }
            return options;
        },
        editTarget: function (target_id = null) {
            let target = JSON.parse(JSON.stringify(this.templates.target));
            target.account_id = this.cAccount.id;
            if (target_id > 0) {
                let idx = this.cAccount.cash.targets.findIndex( t => { return t.id === target_id});
                if (idx >= 0) target = JSON.parse(JSON.stringify(this.cAccount.cash.targets[idx]));
            }
            this.cTarget = target;
        },
        saveTarget: function () {
            this.$emit('run', 'saveTarget');
            $.post('?action=saveTarget', { target: this.cTarget}, r => {
                if (r.status === 'ok') {
                    if (r.data.hasOwnProperty('id')) {
                        let idx = this.cAccount.cash.targets.findIndex(t => {
                            return t.id === r.data.id
                        });
                        if (idx >= 0) this.cAccount.cash.targets[idx] = r.data;
                        else this.cAccount.cash.targets.push(r.data);
                        this.cTarget = false;
                    } else {
                        window.open(r.data.uri, "newWindow", "width=700,height=570,resizable=yes,scrollbars=no,status=no,left=100,top=100,toolbar=no,location=no");
                    }
                    this.$emit('finish');
                } else this.$emit('error', r.errors);
            });
        },
        deleteTarget: function () {
            this.$emit('run', 'deleteTarget');
            $.post('?action=preDeleteTarget', { target_id: this.cTarget.id, account_id: this.cAccount.id}, r => {
                if (r.status === 'ok') {
                    this.$emit('finish');
                    let that = this;
                    let rules = '';
                    if (r.data.length) rules = 'Будут удалены правила: ' + r.data.join(', ');
                    $.waDialog.confirm({
                        title: "Действие необратимо!",
                        text: "Удалить подключение " + that.cTarget.name + '?<br>' + rules,
                        success_button_title: 'Удалить',
                        success_button_class: 'danger',
                        cancel_button_title: 'Отмена',
                        cancel_button_class: 'light-gray',
                        onSuccess: function () {
                            that.$emit('run', 'deleteTarget');
                            $.post('?action=deleteTarget', { account_id: that.cAccount.id, target_id: that.cTarget.id}, r => {
                                if (r.status === 'ok') {
                                    that.cAccount.cash.rules = r.data;
                                    let idx = that.cAccount.cash.targets.findIndex( t => { return t.id === that.cTarget.id});
                                    if (idx >= 0) that.cAccount.cash.targets.splice(idx, 1);
                                    that.cTarget = false;
                                } else alert(r.errors.join('; '));
                                that.$emit('run', false);
                            });
                        }
                    });
                } else this.$emit('error', r.errors);
            });
        },
        getCashTargetOptions: function (remote_only = false) {
            let options = [];
            this.cAccount.cash.targets.forEach((el) => {
                if (!remote_only || el.id >= 0) {
                    options.push({ value: el.id, title: el.name});
                }
            });
            return options;
        },
        changeCashMenu: function (value) {
            this.cTarget = false;
            this.cRule = false;
            this.menuMode = value;
        },
        showToken: function () {
            this.$emit('run', 'showToken');
            $.post('?action=getRemoteToken', { account_id: this.cAccount.id, remote_id: this.cRemote.id}, r => {
                if (r.status === 'ok') {
                    $.waDialog.alert({
                        title: "Токен для доступа к API",
                        text: r.data,
                        button_title: 'Закрыть'
                    });
                    this.$emit('run', false);
                } else this.$emit('error', r.errors);
            });
        },
        enableAccountMenu: function () {
            this.$nextTick( () => {
                let that = this;
                $("#devapi-account-menu").waToggle({
                    change: function (event, target, toggle) {
                        that.cMenu = $(target).data('value');
                        that.menuMode = 'view';
                    }
                });
            });
        },
        getAccountLiClass: function (acc_id) {
            return this.cAccount.id == acc_id ? 'selected' : '';
        }
    }
};