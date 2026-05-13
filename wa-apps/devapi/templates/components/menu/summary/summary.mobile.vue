<template v-if="loaded">
    {literal}
    <template v-if="accounts.length">
        <div class="custom-p-24 summary-toolbar">
            <ul v-if="accounts" class="tabs custom-mb-20 small">
                <template v-for="(acc) in accounts">
                    <li :class="getAccountLiClass(acc.id)" @click="changeAccount(acc.id)">
                        <a>
                            <span v-if="parseInt(acc.new_transactions)" class="count largest"><span
                                    class='badge'>{{ acc.new_transactions }}</span></span>
                            <span v-else><i class="fas fa-laptop-medical"></i></span>
                            &nbsp;{{ acc.name }}

                        </a>
                    </li>
                </template>
            </ul>
            <div class="small">
                <div class="flexbox small custom-mb-4" v-if="summary.hasOwnProperty('balance')">
                    <span class="gray">[`Баланс счета`]:</span>
                    <strong class="custom-ml-8">{{ formatCurrency(summary.balance.balance, summary.balance.currency) }}</strong>
                    <span v-if="summary.balance.update_datetime"
                          class="gray custom-ml-8">[{{ getDate('humanDateTime', summary.balance.update_datetime) }}]</span>
                </div>
                <div class="flexbox small custom-mb-4" v-if="summary!==false">
                    <span class="gray">[`За период`]:</span>
                    <span class="custom-ml-8" title="[`Сумма продаж`]">{{ formatCurrency(summary.sum) }}</span>
                    <span class="custom-ml-8" title="[`Количество продаж`]">[{{ summary.count }}]</span>
                    <span class="custom-ml-8" v-if="summary.compares.sum">
                        &nbsp;&nbsp;&nbsp;
                        {{ getComparePercent() }} %
                        <span v-if="getComparePercent()>=0" style="color: var(--green)"><i class="fas fa-long-arrow-alt-up"></i></span>
                        <span v-else style="color: var(--red)"><i class="fas fa-long-arrow-alt-down"></i></span>
                    </span>
                </div>
                <div class="flexbox small custom-mb-4" v-if="summary!==false && summary.compares.sum">
                    <span class="gray">[`Предыдущий период`]:</span>
                    <span class="custom-ml-8"
                          title="[`Сумма продаж`]">{{ formatCurrency(summary.compares.sum.toFixed(2)) }}</span>
                    <span v-if="summary!==false" class="custom-ml-8"
                          title="[`Количество продаж`]">[{{ summary.compares.count }}]</span>
                </div>
                <div class="dev-select flexbox custom-mt-8">
                    <dropdown
                            @update="setFilter($event, 'list_type')"
                            :options="options.list_types"
                            :search="false"
                            :hover="false"
                            id="dd-filter-list-type"
                            ddclass="smaller custom-m-0-mobile"
                            :value="settings.list_type"
                            name="Период"
                    ></dropdown>
                    <dropdown class="custom-ml-16"
                              @update="setFilter($event, 'limit')"
                              :options="options.limits"
                              :search="false"
                              :hover="false"
                              id="dd-filter-limit"
                              ddclass="smaller custom-m-0-mobile"
                              :value="filters.limit"
                              name="Количество строк"
                    ></dropdown>
                    <dropdown class="custom-ml-16"
                              @update="setFilter($event, 'transaction_type')"
                              :options="options.transaction_types"
                              :search="false"
                              :hover="false"
                              id="dd-filter-transaction-type"
                              ddclass="smaller custom-m-0-mobile"
                              name="Все типы"
                    ></dropdown>
                    <dropdown class="custom-ml-16"
                              @update="setFilter($event, 'products')"
                              :options="options.products"
                              :search="true"
                              :hover="false"
                              id="dd-filter-products"
                              ddstyle="width: 250px;"
                              ddclass="smaller custom-m-0-mobile"
                              value=""
                              :trigger="accountId"
                              name="Продукты"
                    ></dropdown>
                </div>
                <div v-if="filters.list_type==='period'" class="smaller custom-mb-20">
                    [`с`]
                    <input type="date" :max="filters.period.to" v-model="filters.period.from" @change="updateSummary()">
                    [`по`]
                    <input type="date" :min="filters.period.from" v-model="filters.period.to" @change="updateSummary()">
                </div>
            </div>
            <div v-if="summary!==false" style="margin-top: 0.5rem;" class="small">
                <ul class="paginator-block paging smaller" v-if="filters.limit">
                    <li>
                        <a @click="setFilter(getPaginationOffset('left'), 'offset')">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <li :class="(Number.isInteger(page) && (page/summary.limit-1)==(summary.offset/summary.limit)) ? 'selected' : ''"
                        v-for="(page, idx) in pagination">
                        <a v-if="Number.isInteger(page)"
                           @click="setFilter(getPaginationOffset(page), 'offset')">{{ page / summary.limit }}</a>
                        <a v-else>{{ page }}</a>
                    </li>
                    <li>
                        <a @click="setFilter(getPaginationOffset('right'), 'offset')">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
                <div class="smaller" style="display:inline;">
                <action-button
                        @click="checkOrderLicense('')"
                        title="[`Проверить лицензии`]"
                        icon="fas fa-balance-scale-right"
                        action="tmp"
                        :run="false"
                        bclass="outlined smallest custom-ml-48 dark-gray custom-m-0-mobile"
                        style="height: fit-content;"
                ></action-button>
                <action-button
                        @click="viewPromos()"
                        title="[`Промокоды`]"
                        icon="fas fa-tags"
                        action="viewPromos"
                        :run="false"
                        bclass="outlined smallest custom-ml-24 dark-gray custom-m-0-mobile"
                        style="height: fit-content;"
                ></action-button>
                <action-button
                        @click="viewProducts()"
                        title="[`Продукты`]"
                        icon="fas fa-code-branch"
                        action="viewProducts"
                        :run="false"
                        bclass="outlined smallest custom-ml-24 dark-gray custom-m-0-mobile"
                        style="height: fit-content;"
                ></action-button>
                </div>
            </div>
        </div>
        <div id="table-products" class="custom-pl-24 custom-p-0-mobile">
            <template v-if="summary!==false && runner!=='getSummary'">
                <table class="small zebra custom-mb-48" style="width: unset !important;">
                    <tbody>
                    <tr>
                        <th></th>
                        <th>[`Дата`]</th>
                        <th>[`До`]</th>
                        <th>[`Сумма`]</th>
                        <th>[`После`]</th>
                        <th>[`Заказ`]</th>
                        <th>[`Комментарий`]</th>
                    </tr>
                    <tr v-for="(rec, idx) in summary.records" class="small">
                        <td >
                            <span v-if="rec.slug" class="gray custom-mr-4" :title="getProductName(rec.slug)"><i
                                    class="far fa-check-square"></i></span>
                            <span v-else-if="!['buy','payout'].includes(rec.type)" class="custom-mr-4"
                                  title="[`Не удалось определить продукт`]"><i
                                    class="far fa-question-circle"></i></span>

                            <span v-if="rec.type==='payout'" :title="options.transaction_types[rec.type].name"><i
                                    class="fas fa-sign-out-alt"></i></span>
                            <span v-else-if="rec.type==='cancel'" style="color: var(--red)"
                                  :title="options.transaction_types[rec.type].name">
                                 <i class="fas fa-undo"></i></span>
                            <span v-else-if="rec.type==='upgrade'" style="color: var(--orange)"
                                  :title="options.transaction_types[rec.type].name">
                                 <i class="fas fa-external-link-square-alt"></i></span>
                            <span v-else-if="rec.type==='royalty_month'" style="color: var(--blue)"
                                  :title="options.transaction_types[rec.type].name">
                                 <i class="far fa-calendar-alt"></i></span>
                            <span v-else-if="rec.type==='royalty_year'" style="color: var(--blue)"
                                  :title="options.transaction_types[rec.type].name">
                                 <i class="fas fa-calendar-alt"></i></span>
                            <span v-else-if="rec.type==='royalty'" style="color: var(--green)"
                                  :title="options.transaction_types[rec.type].name">
                                <i class="far fa-money-bill-alt"></i></span>
                            <span v-else-if="rec.type==='buy'" style="color: var(--orange)"
                                  :title="options.transaction_types[rec.type].name">
                                <i class="fas fa-cart-arrow-down"></i></span>
                            <span v-else style="color: var(--red)"><i class="far fa-question-circle"></i></span>
                            <template v-if="rec.discount">
                                <span class="small custom-ml-4">
                                    <span v-if="rec.discount==='special_offer'" title="[`Специальное предложение Webasyst`]" style="color: var(--yellow)"><i class="fas fa-percent"></i></span>
                                    <span v-else-if="rec.discount==='product_offer'" title="[`Специальное предложение разработчика`]" style="color: var(--orange)"><i class="fas fa-percent"></i></span>
                                    <span v-else-if="rec.discount==='promocode'" title="[`Скидка по промокоду`]" style="color: var(--red)"><i class="fas fa-tag"></i></span>
                                    <span v-else-if="rec.discount==='repeate_buy'" title="[`Скидка за повторную лицензию`]" style="color: var(--blue)"><i class="far fa-copy"></i></span>
                                    <span v-else-if="rec.discount==='partner'" title="[`Скидка реселлеру`]" style="color: var(--pink)"><i class="fas fa-user-injured"></i></span>
                                </span>
                            </template>
                        </td>
                        <td class="small">{{ rec.datetime }}</td>
                        <td>{{ formatCurrency(rec.balance_before, 'RUB', true) }}</td>
                        <td style="font-weight: 500;">{{ formatCurrency(rec.amount) }}</td>
                        <td>{{ formatCurrency(rec.balance_after, 'RUB', true) }}</td>
                        <td><a @click="checkOrderLicense(rec.order_id)">{{ rec.order_id }}</a></td>
                        <td>{{ rec.comment }}</td>
                    </tr>
                    </tbody>
                </table>
            </template>
            {/literal}
            <template v-else-if="runner==='getSummary'">
                {include '../skeleton.html'}
            </template>
        </div>
        <div id="devapi-templates" style="display: none;">

        </div>
    </template>
    <template v-else>
        <div class="custom-p-48">
            <strong class="gray">[`Пока не добавлено ни одного аккаунта разработчика`]</strong>
        </div>
    </template>
</template>
<div v-else class="custom-p-24">
    {include '../skeleton.html'}
</div>