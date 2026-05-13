{literal}
<div class="drawer order-info" id="devapi-acc-products">
<div class="drawer-background"></div>
<div class="drawer-body">
    <div class="drawer-block">
        <header class="drawer-header">
            <div class="custom-mt-8">
                <input v-model="filter" type="text" class="small long" placeholder="[`Начните вводить для поиска продукта`]">
            </div>
            <ul class="chips small">
                <li :class="getChipClass('market')">
                    <a @click="changeChip('market')"><i class="fas fa-store"></i> [`Доступные в маркете`]</a>
                </li>
                <li :class="getChipClass('money')">
                    <a @click="changeChip('money')"><i class="fas fa-coins"></i> [`Только платные`]</a>
                </li>
            </ul>
        </header>
        <div class="drawer-content">
            <template v-if="errorText!==false">
                <span class="danger alert">{{ errorText }}</span>
            </template>
            <template v-if="products.length">
                <table class="smaller compact">
                    <tbody>
                    <tr>
                        <th>[`Название`]</th>
                        <th v-if="extData !== false"></th>
                        <th>[`Версия`]</th>
                        <th>[`Цена`]</th>
                        <th>[`Опции`]</th>
                    </tr>
                    <template v-for="(p, idx) in products">
                        <tr v-if="filterProduct(p)" :class="getProductClass(p)">
                            <td style="max-width: 250px">
                                <strong
                                        @click="openProductPage(p.id)"
                                        :title="p.slug"
                                        :style="'cursor:' + ((p.id && !isRemote)?'pointer':'default')"
                                        :class="p.discontinued_at ? 'gray' : ''"
                                > {{p.name?p.name:p.slug}}&nbsp;&nbsp;<span class="gray" v-if="p.discontinued_at" :title="getDiscontinuedTitle(p.discontinued_at)"><i class="fas fa-recycle"></i></span> </strong>
                            </td>
                            <td v-if="extData !== false">
                                <template v-if="p.published_version && p.id && !isRemote">
                                    <span>
                                        <a class="gray" :title="extData.title" target="_blank" :href="extData.url + p.id"><i :class="extData.icon"></i></a>
                                    </span>
                                </template>
                            </td>
                            <td>
                                <span class="small" style="font-family: monospace" v-html="p.published_version ? p.published_version : '---'"></span>
                                <span v-if="p.awaiting_version" :title="getAwaitingText(p)" class="custom-ml-4"><i class="fas fa-code-branch"></i></span>
                            </td>
                            <td>{{getProductPrice(p)}}</td>
                            <td>
                                <span v-if="parseInt(p.is_special_offers)" class="custom-ml-8" title="[`Участвует в акциях Webasyst`]"><i class="fas fa-percent"></i></span>
                                <span v-if="parseInt(p.is_repeat_discount)" class="custom-ml-8" title="[`Скидка за повторные покупки`]"><i class="far fa-copy"></i></span>
                                <span v-if="parseInt(p.is_reseller_discount)" class="custom-ml-8" title="[`Скидка реселлерам`]"><i class="fas fa-user-injured"></i></span>
                            </td>
                    </tr>
                    </template>
                    </tbody>
                </table>
            </template>
            <footer class="drawer-footer">
            </footer>
        </div>
    </div>
</div>
</div>

{/literal}
<script>
$(function () {
    {include '../actionButton/actionButton.js'}
    {include './products.js'}
})
</script>