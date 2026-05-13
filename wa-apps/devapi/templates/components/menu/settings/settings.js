let devapiSettings = {
    props: ['runner', 'finish'],
    emits: ['run', 'finish', 'error'],
    data: function () {
        return {
            loaded: false,
            settings: false,
            appSettings: false,
            accounts: false,
            periodicity: false,
            list_types: false,
            icons: false,
            isAdmin: false,
            limits: false,
            appHelpdesk: {$appHelpdesk},
            appUrl: "{$wa_app_static_url}",
            rootPath: false
        };
    },
    template: {$menuSettings},
    mounted: function () {
        $.post('?module=menu&action=getSettings', r => {
            if (r.status === 'ok') {
                this.settings = r.data.settings;
                this.accounts = r.data.accounts;
                this.list_types = r.data.list_types;
                this.limits = r.data.limits;
                this.periodicity = r.data.periodicity;
                this.appSettings = r.data.appSettings;
                this.icons = r.data.icons;
                this.isAdmin = r.data.isAdmin;
                this.rootPath = r.data.rootPath;
                this.loaded = true;
                this.$nextTick(() => {
                    let that = this;
                    $(".switch.devapi").waSwitch({
                        change: function (active, sw) {
                            let source = $(sw.$field[0]).attr('source');
                            switch (source) {
                                case 'counter':
                                    that.settings[source] = that.settings[source] === 0 ? 1 : 0;
                                    break;
                                default:
                                    that.settings[source].enabled = that.settings[source].enabled == 0 ? 1 : 0;
                                    if (source === 'telegram' && that.settings.telegram.enabled == 0) that.settings.telegram.token = '';
                                    if (that.settings[source].enabled == 0) {
                                        that.settings[source].type = 'me';
                                        that.settings[source].values = [];
                                    }
                                    break;
                            }
                        }
                    });
                });
            } else {

            }
            if (this.periodicity === false) {
                this.periodicity = {
                    15: '15 минут',
                    30: '30 минут',
                    60: '1 час',
                    120: '2 часа',
                    180: '3 часа'
                };
            }
        });
    },
    watch: {
        runner: function () {
            devapiData.runAction = this.runner;
        }
    },
    methods: {
        saveSettings: function () {
            this.$emit('run', "saveSettings");
            $.post('?action=saveSettings', { settings: this.settings, appSettings: this.appSettings}, r => {
                if (r.status === 'ok') {
                    this.$emit('finish');
                    if (this.isAdmin) {
                        let li = $('#wa-applist').find('li#wa-app-devapi');
                        $(li).find('a').attr('data-wa-tooltip-content', this.appSettings.app_name);
                        $(li).find('img').attr('src', this.appUrl + 'img/' + this.appSettings.app_icon);
                        $(li).find('span.wa-app-name').html(this.appSettings.app_name);
                    }
                }
                else this.$emit('error', r.errors);
            });
        },
        getDivIconClass: function (icon) {
            let value = 'custom-ml-12 custom-p-4 devapi-app-icon ';
            if (icon === this.appSettings.app_icon) value += 'selected';
            return value;
        }
    }
};