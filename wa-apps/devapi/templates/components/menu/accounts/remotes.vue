{literal}
<template v-if="menuMode==='view'">
    <template v-if="cAccount.remotes.length">
        <div class="list" style="max-width: 700px;">
            <template v-for="(rm, idx) in cAccount.remotes">
                <div class="item">
                    <a class="image" @click="editRemote(rm.id)"><i class="fas fa-project-diagram"></i></a>
                    <a class="details" @click="editRemote(rm.id)">
                        {{rm.name}} <span class="smaller gray"> ([`Последнее использование`] {{rm.last_use}})</span>
                    </a>
                </div>
            </template>
        </div>
    </template>
    <div v-else class="gray custom-p-48">
        <p><strong>[`Вы пока не предоставляли доступ`]</strong></p>
    </div>
    <div class="custom-mt-24">
        <action-button
            @bclick="editRemote()"
            title="[`Добавить`]"
            icon="fas fa-plus"
            action="tmp"
            :run="runner"
            bclass="small outlined"
        ></action-button>
    </div>
</template>
<template v-if="menuMode==='editRemote'">
    <div class="fields wrl">
        <div class="field">
            <div class="name">[`Название подключения`]</div>
            <div class="value">
                <input v-model="cRemote.name" type="text" class="long">
            </div>
        </div>
        <div class="field">
            <div class="name">[`Дата с которой предоставлять информацию`]</div>
            <div class="value">
                <input v-model="cRemote.start_date" type="date">
            </div>
        </div>
        <div class="field">
            <div class="name">[`Продукты`]</div>
            <div class="value">
                <div style="max-height: 250px; overflow: auto; width: fit-content;">
                    <template v-for="(p, idx) in cAccount.products">
                        <span v-if="p.published_version" style="display: block;">
                            <input @change="changeRemoteProduct(p.slug)" :id="'product-' + p.slug" type="checkbox" :checked="cRemote.products.includes(p.slug)">&nbsp;&nbsp;
                            <label :for="'product-' + p.slug">{{p.name}}</label>
                        </span>
                    </template>
                </div>
            </div>
        </div>
        <div class="field">
            <div class="name">[`Возможность создавать купоны`]</div>
            <div class="value">
                <input type="checkbox" @change="cRemote.params.promo=(cRemote.params.promo==1?'0':'1')" :checked="cRemote.params.promo==1">
                <span class="small gray custom-ml-8">[`Возможность создавать купоны будет доступна только дл ятех продуктов, к которым предоставлен доступ`]</span>
            </div>
        </div>
        <div class="field" v-if="cRemote.params.promo==1">
            <div class="name">[`Максимальная скидка для создаваемых купонов`]</div>
            <div class="value">
                <input v-model="cRemote.params.percent" type="number" min="0" max="100">
            </div>
        </div>
        <div class="field custom-mt-48">
            <div class="name"></div>
            <div class="value">
                <action-button
                    @bclick="saveRemote()"
                    title="[`Сохранить`]"
                    icon="far fa-save"
                    action="saveRemote"
                    :run="runner"
                    :result="finish"
                ></action-button>
                <action-button
                        @bclick="menuMode='view';cRemote=false"
                        title="[`Закрыть`]"
                        icon="fas fa-window-close"
                        action="tmp"
                        :run="runner"
                        :result="finish"
                        bclass="custom-ml-48 gray"
                ></action-button>
                <action-button
                        v-if="cRemote.id"
                        @bclick="showToken()"
                        title="[`Показать токен`]"
                        icon="fab fa-slack-hash"
                        action="showToken"
                        :run="runner"
                        :result="finish"
                        bclass="custom-ml-48 orange"
                ></action-button>
                <action-button
                        v-if="cRemote.id!==0"
                        @bclick="deleteRemote()"
                        title="[`Удалить`]"
                        icon="fas fa-trash-alt"
                        action="deleteRemote"
                        :run="runner"
                        :result="finish"
                        bclass="red custom-ml-48"
                ></action-button>
            </div>
        </div>
    </div>
</template>
{/literal}