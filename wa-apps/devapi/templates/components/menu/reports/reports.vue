{literal}
<div class="flexbox vertical" v-if="loaded">
<template v-if="accounts.length">
    <div>
        <ul v-if="accounts" class="tabs custom-mb-20 small">
            <template v-for="(acc) in accounts">
                <li :class="getAccountLiClass(acc.id)" @click="setAccount(acc.id)">
                    <a><span><i class="fas fa-laptop-medical"></i></span> &nbsp;{{ acc.name }}</a>
                </li>
            </template>
        </ul>
    </div>
    <div class="flexbox custom-p-16">
        <div class="fields">
            <div class="field">
                <div class="name"><strong>[`Период`]</strong></div>
                <div class="value">
                    <dropdown
                        @update="params.period = $event"
                        :options="options.period"
                        :search="false"
                        :hover="false"
                        id="dd-filter-period"
                        ddclass="smaller"
                        :value="params.period"
                        main_style="margin-bottom:8px;"
                    ></dropdown>
                    <div v-if="params.period==='period'" class="smaller custom-ml-12" style="display: inline;">
                        <span class="custom-mr-8">[`с`]</span>
                        <input type="date" :max="params.period_free.to" v-model="params.period_free.from">
                        <span class="custom-mr-8 custom-ml-8">[`по`]</span>
                        <input type="date" :min="params.period_free.from" v-model="params.period_free.to">
                    </div>
                </div>
            </div>
            <div class="field">
                <div class="name header for-select"><strong>[`Продукты`]</strong></div>
                <div class="value">
                    <dropdown
                        @update="changeDropDown('products', $event)"
                        :options="{ all: 'Все продукты', types: 'По типам продуктов...', selected: 'Выбранные продукты...'}"
                        :search="false"
                        :hover="false"
                        id="dd-filter-products"
                        ddclass="smaller"
                        :value="params.products"
                        main_style="margin-bottom:8px;"
                    ></dropdown>
                    <a v-if="params.products!=='all'" class="button smaller nobutton custom-ml-12"
                       @click="selectAll()"
                       style="color: var(--dark-gray)">
                    <span class="">
                        <i class="fas fa-check-double"></i>
                    </span>
                    </a>
                    <div v-if="params.products!=='all'" style="max-width: 1200px;">
                        <ul class="chips smaller" style="margin: unset;">
                            <template v-if="params.products==='selected'">
                                <template v-for="(item, idx) in cAccount.products">
                                    <li v-if="item.published_version"
                                        :class="params.selected.products.includes(item.slug) ? 'selected':''">
                                        <a @click="setSelected('products', item.slug)">
                                        <span v-if="params.selected.products.includes(item.slug)"><i
                                            class="fas fa-circle"
                                            style="color: var(--green) !important;"></i></span>
                                            <span v-else><i class="fas fa-circle" style="color: var(--gray)"></i></span>
                                            {{ item.name }}
                                        </a>
                                    </li>
                                </template>
                            </template>
                            <template v-if="params.products==='types'">
                                <template v-for="(item, idx) in options.product_types">
                                    <li :class="params.selected.product_types.includes(idx) ? 'selected':''">
                                        <a @click="setSelected('product_types', idx)">
                                        <span v-if="params.selected.product_types.includes(idx)"><i
                                            class="fas fa-circle"
                                            style="color: var(--green) !important;"></i></span>
                                            <span v-else><i class="fas fa-circle" style="color: var(--gray)"></i></span>
                                            {{ item }}
                                        </a>
                                    </li>
                                </template>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="field">
                <div class="name header for-select"><strong>[`Тип транзакции`]</strong></div>
                <div class="value">
                    <dropdown
                        @update="changeDropDown('transaction_types', $event)"
                        :options="{ all: 'Все типы', plus: 'С положительным балансом', selected: 'Выбранные типы...'}"
                        :search="false"
                        :hover="false"
                        id="dd-filter-transaction_type"
                        ddclass="smaller"
                        :value="params.transaction_types"
                        main_style="margin-bottom:8px;"
                    ></dropdown>
                    <a v-if="params.transaction_types==='selected'" class="button smaller nobutton custom-ml-12"
                       @click="selectAll('transaction_types')"
                       style="color: var(--dark-gray)">
                    <span class="">
                        <i class="fas fa-check-double"></i>
                    </span>
                    </a>
                    <div v-if="params.transaction_types==='selected'" style="max-width: 1200px;">
                        <ul class="chips smaller" style="margin: unset;">
                            <template v-for="(item, idx) in options.transaction_types">
                                <li :class="params.selected.transaction_types.includes(item.id) ? 'selected' : ''">
                                    <a @click="setSelected('transaction_types', item.id)">
                                    <span v-if="params.selected.transaction_types.includes(item.id)"><i
                                        class="fas fa-circle" style="color: var(--green) !important;"></i></span>
                                        <span v-else><i class="fas fa-circle" style="color: var(--gray)"></i></span>
                                        {{ item.name }}
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="field">
                <div class="name align-right">
                    <action-button
                        @bclick="generateChart"
                        title="[`Сформировать`]"
                        icon="fas fa-chart-area"
                        action="generateChart"
                        :run="runner"
                        :result="finish"
                        bclass="outlined dark-gray"
                    ></action-button>
                </div>
                <div class="value">
                    <div class="flexbox space-20" v-show="cChart.variant">
                        <div class="toggle devapi-chart">
                            <span data-type="type" data-id="bar" :class="cChart.variant==='bar'?'selected':''">
                                <i class="fas fa-chart-bar"></i></span>
                            <span data-type="type" data-id="pie" :class="cChart.type==='pie'?'selected':''">
                                <i class="fas fa-chart-pie"></i></span>
                            <span data-type="type" data-id="table" :class="cChart.type==='table'?'selected':''">
                                <i class="fas fa-table"></i></span>
                        </div>
                        <div class="toggle devapi-chart">
                            <span data-type="variant" data-id="amounts"
                                  :class="cChart.variant==='amounts'?'selected':''">[`По сумме`]</span>
                            <span data-type="variant" data-id="cnt" :class="cChart.variant==='cnt'?'selected':''">[`По количеству`]</span>
                        </div>
                        <div class="toggle devapi-chart">
                            <span data-type="period" data-id="summary" :class="cChart.period==='summary'?'selected':''">[`Суммарно`]</span>
                            <span data-type="period" data-id="periods" :class="cChart.period==='periods'?'selected':''">[`По периодам`]</span>
                        </div>
                        <div class="flexbox middle">
                            <a @click="getCSV()" v-if="cChart.type==='table'">
                                <span class="badge gray"><i class="fas fa-cloud-download-alt"></i>&nbsp;&nbsp;[`Скачать CSV`]</span>
                            </a>
                        </div>
                    </div>
                    <div v-show="cChart.type==='pie'" style="width: 600px;">
                        <canvas id="devapi-pie-chart"></canvas>
                    </div>
                    <div v-show="cChart.type==='bar'" style="width: 1200px;">
                        <canvas id="devapi-bar-chart"></canvas>
                    </div>
                    <div v-if="cChart.type==='table'" style="width: 1200px;" class="custom-pt-20">
                        <table class="zebra">
                            <tbody>
                            <tr>
                                <th>[`Продукт`]</th>
                                <th v-for="label in getDataTable('labels')">{{ label }}</th>
                            </tr>
                            <tr v-for="item in getDataTable('datasets')">
                                <td>{{ item.label }}</td>
                                <td v-for="value in item.data">{{ value !== 0 ? value : '' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<template v-else>
    <div class="custom-p-48">
        <strong class="gray">[`Пока не добавлено ни одного аккаунта разработчика`]</strong>
    </div>
</template>
</div>
{/literal}