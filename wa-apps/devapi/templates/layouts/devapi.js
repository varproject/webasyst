const devapiData = Vue.createApp({
    data() {
        return {
            cApp: null,
            cLi: false,
            errorText: false,
            runAction: false,
            runResult: null,
            loading: false,
            backendUrl: "{$wa_backend_url}devapi/"
        }
    },
    mounted: function () {
        $('#devapi-error').show();
        this.loading = $.waLoading();
    },
    watch: {
        runResult: function () {
            this.setAnimate();
        },
        runAction: function () {
            this.setAnimate();
        }
    },
    methods: {
        getMenuClass: function (el) {
            if (el) el += '/';
            return this.$route.path === this.backendUrl + el? 'selected' : '';
        },
        setAnimate: function () {
            if (!this.loading) return;
            if (this.runAction === false || this.runResult === true) this.loading.done();
            else this.loading.animate(6000, 99, false);
        },
        finishAction: function(timeOut = 2000) {
            this.runResult = true;
            setTimeout(() => {
                this.runAction = false;
                this.runResult = null;
            }, timeOut);
        },
        setError: function (texts, timeOut = 7000) {
            let text = texts;
            if (Array.isArray(texts)) text = texts.join('; ');
            if (!text.length) text = 'Непредвиденная ошибка сервера';
            window.scrollTo(0, 0);
            this.errorText = text;
            setTimeout(() => {
                this.errorText = false;
            }, timeOut);
            this.runAction = false;
        }
    }
});

let routes = [
    { path: '{$wa_backend_url}devapi/', component: devapiSummary},
    { path: '{$wa_backend_url}devapi/reports/', component: devapiReports},
    { path: '{$wa_backend_url}devapi/accounts/', component: devapiAccounts},
    { path: '{$wa_backend_url}devapi/settings/', component: devapiSettings}
]

let router = VueRouter.createRouter({
    history: VueRouter.createWebHistory(),
    routes
});

devapiData.use(router);
devapiData.component('router-link', VueRouter.RouterLink);
devapiData.component('router-view', VueRouter.RouterView);
devapiData.component('actionButton', actionButton);
devapiData.component('backUsers', backUsers);
devapiData.component('dropdown', wrlDropdown);
devapiData.component('cmp-settings', devapiSettings);
devapiData.component('cmp-summary', devapiSummary);
devapiData.component('cmp-accounts', devapiAccounts);
devapiData.mount('div#devapi-app');