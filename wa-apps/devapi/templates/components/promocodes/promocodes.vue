{literal}
<div class="drawer order-info" id="devapi-promocodes">
<div class="drawer-background"></div>
<div class="drawer-body">
    <div class="drawer-block">
        <header class="drawer-header">
            <div v-if="accounts.length > 1" class="toggle animate custom-m-8 custom-p-8" id="check-accounts-list">
                <span v-for="(acc, idx) in accounts" :data-value="acc.id" :class="acc.id==accountId?'selected':''">{{acc.name}}</span>
            </div>
            <div class="custom-mt-8">
                <input v-model="filter" type="text" class="small long" placeholder="[`Начните вводить для поиска промокода`]">
                <a @click="createPromocode()" class="button smallest gray custom-ml-12"><i class="fas fa-plus"></i>&nbsp;&nbsp;[`Новый`]</a>
            </div>
        </header>
        <div class="drawer-content">
            <template v-if="errorText!==false">
                <span class="danger alert">{{ errorText }}</span>
            </template>
            <template v-if="countPromocodes">
                <table class="smaller compact">
                    <tbody>
                    <tr>
                        <th></th>
                        <th></th>
                        <th>[`Дата выдачи`]</th>
                        <th>[`Промокод`]</th>
                        <th>%</th>
                        <th>[`Продуктов`]</th>
                        <th>[`Период`]</th>
                        <th></th>
                    </tr>
                    <template v-for="(code, idx) in getPromocodes()">
                        <tr v-if="!filter.length || code.code.toUpperCase().indexOf(filter.toUpperCase()) !== -1" :class="code.active ? '' : 'gray'">
                            <td>
                                <span v-if="parseInt(code.usage) > 0" :title="'Использован: ' + code.usage"><i class="far fa-check-square"></i></span>
                                <span v-else title="[`Не использовался`]"><i class="far fa-square"></i></span>
                            </td>
                            <td>
                                <span v-if="code.type==='single'" title="[`Одноразовый`]"><i class="fas fa-user"></i></span>
                                <span v-else title="[`Многоразовый`]"><i class="fas fa-user-friends"></i></span>
                            </td>
                            <td :title="code.create_datetime">{{formatDate('humanDate', code.create_datetime)}}</td>
                            <td>
                                <pre style="display: inline">{{code.code}}</pre>
                                <span v-if="code.description.length" :title="code.description" style="display: inline; margin-left: 3px">
                                    <i class="far fa-comment-dots"></i>
                                </span>
                            </td>
                            <td>{{code.percent}}%</td>
                            <td>{{code.products.length}}<span :title="getCodeProducts(code).join('\r\n')" class="custom-ml-8"><i class="fas fa-info-circle"></i></span></td>
                            <td>
                                <span v-if="code.start_date || code.end_date" :title="getCodePeriod(code)"><i class="far fa-calendar-alt"></i></span>
                                <span v-else title="[`Бессрочный`]"><i class="fas fa-infinity"></i></span>
                            </td>
                            <td>
                                <a @click="deleteCode(idx)">
                                    <span :style="'color: ' + (code.active ? 'red' : 'gray')"><i class="far fa-trash-alt"></i></span>
                                </a>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </template>
            <template v-else>
                [`Нет купонов`]
            </template>
            <footer class="drawer-footer">
            </footer>
        </div>
    </div>
</div>
<div id="promo-dialogs" style="display: none;">
    <div class="dialog" id="dialog-new-promocode">
        <div class="dialog-background"></div>
        <div class="dialog-body">
            <header class="dialog-header">
                <input v-model="newPromo.code" class="longer" type="text" maxlength="10" placeholder="Введите код не более 10 символов">
                <a title="[`Сгенерировать`]" class="button circle outlined light-gray a-uniquid" style="font-size: 1rem;"><i class="fas fa-magic"></i></a>
            </header>
            <div class="dialog-content">
                <div class="fields">
                    <div class="field">
                        <div class="name">[`Размер скидки`]</div>
                        <div class="value">
                            <input v-model="newPromo.percent" type="number" class="smaller shortest" min="1" max="100"> %
                        </div>
                    </div>
                    <div class="field">
                        <div class="name">[`Тип`]</div>
                        <div class="value">
                            <select v-model="newPromo.type" class="wa-select smaller">
                                <option value="single">[`Одноразовый`]</option>
                                <option value="multi">[`Многоразовый`]</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <div class="name">[`Дата начала действия`]</div>
                        <div class="value">
                            <input v-model="newPromo.start_date" type="date">
                        </div>
                    </div>
                    <div class="field">
                        <div class="name">[`Дата окончания действия`]</div>
                        <div class="value">
                            <input v-model="newPromo.end_date" type="date">
                        </div>
                    </div>
                    <div class="field">
                        <div class="name">[`Продукты`]</div>
                        <div class="value">
                            <div class="flexbox vertical" style="max-height: 150px;overflow: auto;">
                                <template v-for="(p, idx) in getProducts()">
                                    <span>
                                        <input @click="addRemoveProduct(p.slug)" type="checkbox" :checked="checkPromoProduct(p.slug)" :id="p.slug"> <label :for="p.slug">{{p.name}}</label>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <div class="name">[`Комментарий`]</div>
                        <div class="value">
                            <input v-model="newPromo.description" class="long" type="text">
                        </div>
                    </div>
                </div>
            </div>
            <footer class="dialog-footer">
                <button id="a-create-promo" class="green smaller">[`Создать`]</button>
                <button class="js-close-dialog smaller button light-gray custom-ml-48">[`Отмена`]</button>
            </footer>
        </div>
    </div>
</div>
</div>

{/literal}
<script>
    $(function () {
        {include '../actionButton/actionButton.js'}
        {include './promocodes.js'}
    })
</script>