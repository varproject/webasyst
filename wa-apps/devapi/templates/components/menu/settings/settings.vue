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
    <h5><i class="fas fa-bell"></i>&nbsp;&nbsp;[`Уведомления о новых транзакциях`]</h5>
    <div class="fields wrl">
        <div class="field">
            <div class="name">[`Счетчик на иконке приложения`]</div>
            <div class="value">
                <span class="switch devapi" id="devapi-settings-counter">
                    <input type="checkbox" name="" source="counter" :checked="settings.counter===1">
                </span>
            </div>
        </div>
        <div class="field">
            <div class="name">[`Объявления в фреймворке`]</div>
            <div class="value">
                <div class="flexbox">
                    <span class="switch devapi" id="devapi-settings-announcement">
                        <input type="checkbox" source="announcement" :checked="settings.announcement.enabled==1">
                    </span>
                    <back-users v-show="settings.announcement.enabled == 1"
                        type="announcement"
                        :value="settings.announcement"
                    ></back-users>
                </div>
            </div>
        </div>
        <div class="field">
            <div class="name">[`Уведомления в Telegram`]</div>
            <div class="value">
                <div class="flexbox">
                    <span class="switch devapi" id="devapi-settings-telegram">
                        <input type="checkbox" source="telegram" :checked="settings.telegram.enabled==1">
                    </span>
                    <back-users v-show="settings.telegram.enabled == 1"
                                type="telegram"
                                :value="settings.telegram"
                    ></back-users>
                </div>
                <input type="password" class="long custom-mt-8 smaller" v-model="settings.telegram.token" v-show="settings.telegram.enabled==1" placeholder="[`Укажите токен бота Telegram`]">
                <p class="hint width-60">
                    [`Если аккаунт пользователя не "привязан" к аккаунту Telegram, то лля получения уведомлений в профиле пользователя в раздел Мессенджеры
                    необходимо добавить числовой id пользователя в Telegram, выбрать тип мессенджера "Другой"
                    и указать название мессенджера telegramID`]
                </p>
            </div>
        </div>
        <div class="field">
            <div class="name">[`Уведомления в Max`]</div>
            <div class="value">
                <div class="flexbox">
                    <span class="switch devapi" id="devapi-settings-max">
                        <input type="checkbox" source="max" :checked="settings.max.enabled==1">
                    </span>
                    <back-users v-show="settings.max.enabled == 1"
                                type="max"
                                :value="settings.max"
                    ></back-users>
                </div>
                <input type="password" class="long custom-mt-8 smaller" v-model="settings.max.token" v-show="settings.max.enabled==1" placeholder="[`Укажите токен бота Max`]">
                <p class="hint width-60">
                    [`Если аккаунт пользователя не "привязан" к аккаунту Max, то лля получения уведомлений в профиле пользователя в раздел Мессенджеры
                    необходимо добавить числовой id пользователя в Max, выбрать тип мессенджера "Другой"
                    и указать название мессенджера maxID`]
                </p>
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
                    <div class="flexbox">
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
            {literal}
            <div class="field">
                <div class="name">[`Получение транзакций по cron`]</div>
                <div class="value">
                <span class="alert info" style="font-family: monospace; width: fit-content">
                    [путь до интерпретатора php] {{rootPath}}/cli.php devapi runner
                </span>
                </div>
            </div>
            {/literal}
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