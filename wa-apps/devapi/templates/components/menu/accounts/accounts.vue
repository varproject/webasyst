<div class="custom-p-24">
<div class="flexbox">
    <div>
        <h5><i class="fas fa-users"></i>&nbsp;&nbsp;[`Аккаунты разработчика`]</h5>
    </div>
    <div class="small">
        <action-button
                @click="editAccount()"
                title="Новый аккаунт"
                icon="fas fa-user-plus"
                action="tmp"
                :run="runner"
                :result="finish"
                bclass="outlined smallest custom-ml-48 gray"
        ></action-button>
    </div>
</div>
<template v-if="loaded">
    {literal}
    <ul v-if="accounts" class="tabs custom-mb-20 small">
        <li v-for="(acc, idx) in accounts" :class="getAccountLiClass(acc.id)" @click="setAccount(acc.id)">
            <a><i class="fas fa-laptop-medical"></i>&nbsp;&nbsp;{{ accounts[idx].name }}</a>
        </li>
    </ul>
    <template v-if="accounts.length">
    <div class="custom-mt-16">
        <div class="toggle animated custom-p-8" id="devapi-account-menu">
            <span data-value="general" :class="cMenu==='general'?'selected':''"><i class="fas fa-home"></i> [`Основные данные`]</span>
            <span data-value="cash" :class="cMenu==='cash'?'selected':''"><i class="fas fa-chart-line"></i> [`Интеграция с Деньги`]</span>
            <span v-if="!cAccount.is_remote" data-value="remotes" :class="cMenu==='remotes'?'selected':''"><i class="fas fa-project-diagram"></i> [`Удаленные подключения`]</span>
        </div>
    </div>
    <div class="custom-pl-32 custom-mt-24">
        <template v-if="cMenu==='general'">
            <template v-if="accounts.length && cAccount">
                <div class="flexbox vertical">
                    <div>
                        <h3 style="display: inline;">{{ cAccount.name }}</h3>
                        <template v-if="cAccount.id">
                            <dropdown
                                    @update="runAction($event)"
                                    :options="accActions"
                                    :search="false"
                                    :hover="false"
                                    :update_title="false"
                                    :is_remote="cAccount.is_remote"
                                    id="dd-account-actions"
                                    ddstyle="width: 250px; display: inline;"
                                    ddclass="smaller custom-ml-48"
                                    :trigger="false"
                                    name="Выберите действие"
                            ></dropdown>
                        </template>
                    </div>
                    <div>
                        <action-button
                                @click="runAction('products')"
                                title="[`Продукты`]"
                                icon="fas fa-code-branch"
                                action="viewProducts"
                                :run="false"
                                bclass="outlined large dark-gray"
                                style="height: fit-content;"
                        ></action-button>
                    </div>
                    <div class="custom-mt-16">
                        <action-button
                                @click="runAction('partner')"
                                title="[`Партнерская программа WA`]"
                                icon="far fa-handshake"
                                action="viewPromos"
                                :run="false"
                                bclass="outlined large dark-gray"
                                style="height: fit-content;"
                        ></action-button>
                    </div>
                    <div class="custom-mt-16">
                        <action-button
                                @click="runAction('promocodes')"
                                title="[`Промокоды`]"
                                icon="fas fa-tags"
                                action="viewPromos"
                                :run="false"
                                bclass="outlined large dark-gray"
                                style="height: fit-content;"
                        ></action-button>
                    </div>
                </div>
            </template>
            {/literal}
            <template v-else>
                <div class="custom-p-48">
                    <strong class="gray">[`Пока не добавлено ни одного аккаунта разработчика`]</strong>
                </div>
            </template>
        </template>
        <div v-show="cMenu==='cash'">
            {include './cash.vue'}
        </div>
        <template v-if="cMenu==='remotes'">
            {include './remotes.vue'}
        </template>
    </div>
    </template>
</template>
<div v-else class="custom-p-24">
    {include '../skeleton.html'}
</div>
<div style="display: none;">
    {include './editAccount.dialog.html'}
</div>
</div>