{literal}
<div class="custom-ml-16 flexbox">
<template v-if="loaded">
    <div>
        <select v-model="value.type" class="wa-select" @change="changeType()">
            <option value="me">[`Только мне`]</option>
            <option value="all">[`Всем`]</option>
            <option value="groups">[`Группам`]...</option>
            <option value="users">[`Сотрудникам`]...</option>
        </select>
    </div>
    <div class="flexbox vertical custom-ml-16" style="overflow-y: auto; max-height: 150px;" v-if="!['me', 'all'].includes(value.type)">
        <div v-for="(el, idx) in getList()">
            <div class="flexbox middle">
            <input @change="changeNotificationTarget(el.id)" :id="type + '_' + value.type + el.id" :checked="value.values.includes(el.id)" type="checkbox">&nbsp;&nbsp;
            <label :for="type + '_' + value.type + el.id">
                <template v-if="value.type==='groups'">
                    {{el.name}}
                </template>
                <template v-else>
                    <img class="userpic userpic-20" :src="el.photo_url"> {{el.name}}
                </template>
            </label>
            </div>
        </div>
    </div>
</template>
<div class="spinner" v-else></div>
</div>
{/literal}