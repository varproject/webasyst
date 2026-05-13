let backUsers = {
    props: ['type', 'value'],
    data: function () {
        return {
            groups: false,
            users: false,
            user_id: false,
            loaded: false
        };
    },
    template: {$backUsers},
    mounted: function () {
        $.post('?action=getBackendUsers', r => {
            if (r.status === 'ok') {
                ['groups', 'users', 'user_id'].forEach(el => {
                    if (r.data.hasOwnProperty(el)) this[el] = r.data[el];
                });
                this.loaded = true;
            } else console.log(r.errors);
        });
    },
    methods: {
        changeNotificationTarget: function (value) {
            if (this.value.values.includes(value)) {
                let idx = this.value.values.findIndex( el => { return el == value});
                if (idx !== -1) this.value.values.splice(idx, 1);
            } else this.value.values.push(value);
        },
        changeType: function () {
            this.value.values = [];
            if (['me', 'all'].includes(this.value.type)) this.value.values = [];
        },
        getList: function () {
            let list = [];
            if (this.value.type === 'groups') list = this.groups;
            else list = this.users;
            return list;
        }
    }
};