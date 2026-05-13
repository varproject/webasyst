{literal}
<div class="drawer order-info" id="devapi-order-info">
<div class="drawer-background"></div>
<div class="drawer-body">
    <div class="drawer-block">
        <header class="drawer-header">
            <div class="toggle animate custom-m-8 custom-p-8" id="check-accounts-list">
                <span v-for="(acc, idx) in accounts" :data-value="acc.id" :class="acc.id==accountId?'selected':''">{{acc.name}}</span>
            </div>
            <div class="custom-mt-8">
            <input v-model="value" @keyup.enter="getInfo(value)" type="text" placeholder="[`Номер заказа, домен или ID инсталлера`]"
                   class="long custom-mr-12">
            <drawer-button
                    @click="getInfo(value)"
                    title="[`Проверить`]"
                    icon="far fa-question-circle"
                    bclass="outlined gray smallest"
                    action="checkInfo"
                    :run="runAction"
            ></drawer-button>
            </div>
        </header>
        <div class="drawer-content">
            <template v-if="errorText!==false">
                <span class="danger alert">{{ errorText }}</span>
            </template>
            <template v-if="runAction==='checkInfo'">
                <div class="skeleton">
                    <span class="skeleton-line"></span>
                    <span class="skeleton-list"></span>
                    <span class="skeleton-list"></span>
                    <span class="skeleton-line"></span>
                    <span class="skeleton-list"></span>
                    <span class="skeleton-list"></span>
                </div>
            </template>
            <template v-for="(h, idx) in history">
                <template v-if="h.type==='order'">
                    <div class="small history-element">
                        <div class="order-header large">
                            <span>[`Заказ #`]</span><strong>{{ h.value }}</strong>
                            <span class="gray custom-ml-16">[{{ h.status }}]</span>
                        </div>
                        <div class="custom-p-8">
                            <div v-if="h.discounts.length" class="order-discounts">
                                <strong>[`Скидки`]:</strong>
                                <ul class="custom-mt-4">
                                    <li v-for="(d,idx) in h.discounts">
                                        <strong>{{ d.percent }}%</strong> -&nbsp;
                                        {{ getDiscountByType(d.type) }}
                                        <pre v-if="d.type==='promocode'" style="display: inline;">{{ d.code }}</pre>
                                    </li>
                                </ul>
                            </div>
                            <div v-if="h.transactions.length" class="order-transactions">
                                <strong>[`Транзакции`]:</strong>
                                <table class="bordered compact">
                                    <tbody>
                                    <tr>
                                        <th>[`Дата`]</th>
                                        <th>[`Сумма`]</th>
                                        <th>[`Комментарий`]</th>
                                    </tr>
                                    <tr v-for="(t, idx) in h.transactions">
                                        <td>{{ formatDate('humanDateTime', t.datetime) }}</td>
                                        <td>{{ formatCurrency(t.amount, t.currency) }}</td>
                                        <td>{{ t.comment }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="h.licenses.length" class="order-licenses">
                                <strong>[`Лицензии`]:</strong>
                                <table class="bordered compact">
                                    <tbody>
                                    <tr>
                                        <th>[`Продукт`]</th>
                                        <th>[`Домен`]</th>
                                        <th>[`Истекает`]</th>
                                    </tr>
                                    <tr v-for="(l, idx) in h.licenses">
                                        <td>
                                        <span v-if="l.inst_datetime"
                                              :title="'Установлена: ' + formatDate('humanDateTime', l.inst_datetime)"
                                              class="custom-mr-4">
                                            <i class="fas fa-download"></i>
                                        </span>
                                            <span v-if="l.edition" :title="l.edition" class="custom-mr-4"
                                                  style="color: var(--yellow)">
                                            <i class="fas fa-medal"></i>
                                        </span>
                                            {{ getProductName(l.product) }}
                                        </td>
                                        <td>
                                            <a v-if="l.domain" target="_blank" :href="'//' + l.domain">{{ l.domain }}</a>
                                            <a v-if="l.domain" @click="getInfo(l.domain)" title="[`Посмотреть информацию по домену`]" class="custom-ml-4" style="color: var(--green)">
                                                <i class="far fa-question-circle"></i></a>
                                        </td>
                                        <td><span :title="l.expire_date">{{ checkLicenseType(l.expire_date) }}</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="small history-element">
                        <div class="order-header large">
                            <span v-if="h.type==='domain'">[`Лицензии по домену`] </span>
                            <span v-else>[`Лицензии на установке с ID`] </span><strong>{{ h.value }}</strong>
                            <span class="gray custom-ml-16">[{{ h.items.length }}]</span>
                        </div>
                        <div class="custom-p-8">
                            <strong>[`Лицензии`]:</strong>
                            <table class="compact">
                                <tbody>
                                <tr>
                                    <th>[`Продукт`]</th>
                                    <th>[`Заказ`]</th>
                                    <th>[`Истекает`]</th>
                                </tr>
                                <tr v-for="(l, idx) in h.items">
                                    <td>
                                        <span v-if="l.inst_datetime"
                                              :title="'Установлена: ' + formatDate('humanDateTime', l.inst_datetime)"
                                              class="custom-mr-4">
                                            <i class="fas fa-download"></i>
                                        </span>
                                        <span v-if="l.edition" :title="l.edition" class="custom-mr-4"
                                              style="color: var(--yellow)">
                                            <i class="fas fa-medal"></i>
                                        </span>
                                        {{ getProductName(l.product) }}
                                    </td>
                                    <td>{{ l.order_id }}
                                        <a v-if="l.order_id" @click="getInfo(l.order_id)" class="custom-ml-4" title="[`Посмотреть информацию по заказу`]"><i class="far fa-question-circle"></i></a>
                                    </td>
                                    <td><span :title="l.expire_date">{{ checkLicenseType(l.expire_date) }}</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </template>
        </div>
        <footer class="drawer-footer">
        </footer>
    </div>
</div>
</div>
{/literal}
<script>
$(function () {
    {include '../actionButton/actionButton.js'}
    {include './orderinfo.js'}
})
</script>
<style>
.toggle > *.selected {
    background: var(--toggle-background-color-selected);
    color: var(--toggle-text-color-selected);
    cursor: default;
    transition: 0.2s color;
}
</style>