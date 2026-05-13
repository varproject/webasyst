<div class="custom-p-24">
<h5><i class="fas fa-cogs"></i>&nbsp;&nbsp;[`Настройки`]</h5>
{literal}
<template v-if="loaded">
    <div class="fields wrl">
        <div class="field">
            <div class="name">[`Периодичность обновления`]</div>
            <div class="value">
                <select v-model="settings.refresh_rate" class="wa-select">
                    <option v-for="(title, val) in periodicity" :value="val">{{ title }}</option>
                </select>
            </div>
        </div>
        <div class="field">
            <div class="name">[`Перечень транзакций по умолчанию`]</div>
            <div class="value">
                <select v-model="settings.list_type" class="wa-select">
                    <option v-for="(t, v) in list_types" :value="v">{{ t }}</option>
                </select>
            </div>
        </div>
        <div class="field">
            <div class="name">[`Строк в таблице по умолчанию`]</div>
            <div class="value">
                <select v-model="settings.limit" class="wa-select">
                    <option v-for="(lim, v) in limits" :value="v">{{ lim }}</option>
                </select>
            </div>
        </div>
    </div>
    {/literal}
    <template v-if="isAdmin">
        <h5><i class="fas fa-tools"></i>&nbsp;&nbsp;[`Системные настройки`]</h5>
        <div class="fields wrl custom-mt-16">
            <div class="field">
                <div class="name">[`Название на панели`]</div>
                <div class="value">
                    <input v-model="appSettings.app_name" type="text" maxlength="24" class="small">
                </div>
            </div>
            <div class="field">
                <div class="name">[`Иконка на панели`]</div>
                <div class="value">
                    <div class="devapi-app-icon-block flexbox">
                        <template v-for="(icon, idx) in icons">
                            <div :class="getDivIconClass(icon)" @click="appSettings.app_icon=icon">
                                <img :src="'{$wa_app_static_url}img/' + icon"
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="field" v-if="appHelpdesk">
                <div class="name">[`Интеграция с приложением Поддержка`]</div>
                <div class="value">
                    <select v-model="appSettings.helpdesk" class="wa-select">
                        <option value="0">[`Выключить`]</option>
                        <option value="1">[`Включить`]</option>
                    </select>
                </div>
            </div>
        </div>
    </template>
    {literal}
    <div class="fields wrl">
        <div class="field">
            <div class="name">
                <action-button
                        @click="saveSettings()"
                        title="[`Сохранить`]"
                        icon="far fa-save"
                        action="saveSettings"
                        :run="runner"
                        :result="finish"
                        bclass="small outlined green custom-mt-32">
                </action-button>
            </div>
            <div class="value">
            </div>
        </div>
    </div>
</template>
{/literal}
<div v-else class="custom-p-24">
    {include '../skeleton.html'}
</div>
</div>