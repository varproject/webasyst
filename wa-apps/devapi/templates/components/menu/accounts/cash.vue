{literal}
<ul class="tabs secondary">
<li :class="menuMode==='view'?'selected':''"><a @click="changeCashMenu('view')">[`Правила`]</a></li>
<li :class="menuMode==='targets'?'selected':''"><a @click="changeCashMenu('targets')">[`Доступные подключения`]</a></li>
</ul>
<div class="custom-p-16">
<div v-show="menuMode==='view'">
    <div v-show="!cRule">
        <div v-show="sorter">
            <ul class="list" id="devapi-cash-rules">
                <li v-for="(rule, idx) in cAccount.cash.rules" class="item" :data-rule-id="rule.id">
                    <a class="image small" style="opacity: 35%"><span class="image sorter"><i class="fas fa-bars"></i></span></a>
                    <a class="details" @click="editRule(rule.id)">
                        <span v-if="rule.enable===1"><i class="far fa-check-square"></i></span>
                        <span v-else><i class="far fa-square"></i></span>
                        <strong class="custom-ml-8" :style="rule.enable?'':'color: gray !important'">{{rule.name}}</strong>
                    </a>
                </li>
            </ul>
        </div>
        <div v-if="!cAccount.cash.rules.length" class="gray custom-p-48">
            <p><strong>[`Пока не создано ни одного правила`]</strong></p>
            <p v-if="!cAccount.cash.targets.length"><strong>[`Чтобы добавить новое правило, необходимо чтобы было доступно хотя бы одно подключение`]</strong></p>
        </div>
        <div class="custom-mt-48">
            <action-button
                v-if="cAccount.cash.targets.length"
                @bclick="editRule()"
                title="[`Новое правило`]"
                icon="fas fa-plus"
                action="tmp"
                :run="runner"
                bclass="smallest outlined"
            ></action-button>
        </div>
    </div>
    <template v-if="cRule">
        <div class="fields wrl">
            <div class="field">
                <div class="name">[`Название правила`]</div>
                <div class="value">
                    <input v-model="cRule.name" class="longer" placeholder="[`Укажите название правила`]">
                </div>
            </div>
            <div class="field">
                <div class="name">[`Состояние`]</div>
                <div class="value">
                    <select v-model="cRule.enable" class="wa-select">
                        <option :value="0">[`Выключено`]</option>
                        <option :value="1">[`Включено`]</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Продолжить обработку следующими правилами`]</div>
                <div class="value">
                    <select v-model="cRule.break" class="wa-select">
                        <option :value="0">[`Да, продолжить`]</option>
                        <option :value="1">[`Нет, прервать`]</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Выберите тип операции`]</div>
                <div class="value">
                    <select v-model="cRule.transaction_type" class="wa-select">
                        <option value="">[`Любая операция`]</option>
                        <option v-for="(tt, idx) in transactionTypes" :value="tt.id">{{tt.name}}</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Продукт`]</div>
                <div class="value">
                    <select v-model="cRule.product_slug" class="wa-select" :disabled="disableRuleProducts">
                        <option value="">[`Любой продукт`]</option>
                        <template v-for="(p, idx) in cAccount.products">
                            <option v-if="p.published_version && p.price > 0" :value="p.slug">{{p.name}}</option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Передаваемая сумма`]</div>
                <div class="value">
                    <select v-model="cRule.diff.type" class="wa-select">
                        <option value="full">[`Вся сумма`]</option>
                        <option value="diff">[`Изменённая на...`]</option>
                    </select>
                    <template v-if="cRule.diff.type==='diff'">
                        <input v-model="cRule.diff.value" type="number" class="shortest">
                        <select v-model="cRule.diff.diff_type" class="wa-select">
                            <option value="%">[`процентов`]</option>
                            <option value="currency">[`рублей`]</option>
                        </select>
                    </template>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Выберите подключение`]</div>
                <div class="value">
                    <select v-model="cRule.target_id" class="wa-select">
                        <option :value="0">[`Выберите подключение`]</option>
                        <option v-for="(t, idx) in getCashTargetOptions()" :value="t.value">{{t.title}}</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Счет для зачисления`]</div>
                <div class="value">
                    <select v-model="cRule.cash_account" :disabled="!cRule.target_id" class="wa-select">
                        <option :value="0">[`Выберите значение`]</option>
                        <option v-for="(op, idx) in getRuleOptions('account')" :value="op.value">{{op.title}}</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Категория доходов`]</div>
                <div class="value">
                    <select v-model="cRule.category_income" :disabled="!cRule.target_id || !cRule.cash_account" class="wa-select">
                        <option :value="0">[`Выберите значение`]</option>
                        <template v-for="(op, idx) in getRuleOptions('category_income')">
                            <option :value="op.value">{{op.title}}</option>
                            <template v-if="op.hasOwnProperty('subcats')">
                                <option v-for="(sc, idx) in op.subcats" :value="sc.value">&nbsp;&nbsp;- {{sc.title}}</option>
                            </template>
                        </template>
                    </select>
                    <br><span class="hint">[`Транзакции будет присвоена выбранная категория доходов в случае если сумма транзакции положительная`]</span>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Категория расходов`]</div>
                <div class="value">
                    <select v-model="cRule.category_expense" :disabled="!cRule.target_id || !cRule.cash_account" class="wa-select">
                        <option :value="0">[`Выберите значение`]</option>
                        <template v-for="(op, idx) in getRuleOptions('category_expense')">
                            <option :value="op.value">{{op.title}}</option>
                            <template v-if="op.hasOwnProperty('subcats')">
                                <option v-for="(sc, idx) in op.subcats" :value="op.value">{{op.title}}</option>
                            </template>
                        </template>
                    </select>
                    <br><span class="hint">[`Транзакции будет присвоена выбранная категория расходов в случае если сумма транзакции отрицательная`]</span>
                </div>
            </div>
            <div class="field custom-mt-48">
                <div class="name"></div>
                <div class="value">
                    <action-button
                        @click="saveRule()"
                        :title="cRule.id ? 'Сохранить' : 'Создать'"
                        icon="far fa-save"
                        action="saveRule"
                        :run="runner"
                        :result="finish"
                        bclass="smaller outlined"
                    ></action-button>
                    <action-button
                        @click="cRule = false"
                        title="[`Закрыть`]"
                        icon="fas fa-times"
                        action="tmp"
                        :run="runner"
                        :result="finish"
                        bclass="smaller gray custom-ml-48"
                    ></action-button>
                    <action-button
                        v-if="cRule.id"
                        @click="deleteRule(cRule.id)"
                        title="[`Удалить`]"
                        icon="far fa-trash-alt"
                        action="deleteTarget()"
                        :run="runner"
                        :result="finish"
                        bclass="smaller red outlined custom-ml-48"
                    ></action-button>
                </div>
            </div>
        </div>
    </template>
</div>
<template v-if="menuMode==='targets'">
    <template v-if="cTarget===false">
        <div class="fields wrl custom-mt-24">
            <div class="field">
                <div class="name">[`Приложение "Деньги"`]</div>
                <div class="value">
                    <span class="gray" v-if="checkAppCash">[`Приложение "Деньги" установлено и доступно для передачи информации о транзакциях`]</span>
                    <span class="gray" v-else>[`Приложение "Деньги" отсутствует в данной установке Webasyst`]</span>
                </div>
            </div>
            <div class="field">
                <div class="name">[`Удаленные подключения`]</div>
                <div class="value">
                    <template v-if="getCashTargetOptions(true).length">
                        <ul class="list">
                            <li class="item" v-for="(t, idx) in getCashTargetOptions(true)">
                                <a class="image" @click="editTarget(t.value)"><i class="fas fa-network-wired"></i></a>
                                <a class="details" @click="editTarget(t.value)">{{t.title}}</a>
                            </li>
                        </ul>
                    </template>
                    <p class="gray" v-else>[`Нет настроенных подключений`]</p>
                    <action-button
                        @click="editTarget()"
                        title="[`Добавить`]"
                        icon="fas fa-plus"
                        action="tmp"
                        :run="runner"
                        bclass="smallest outlined"
                    ></action-button>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <div class="fields wrl custom-mt-24">
            <div class="field">
                <div class="name">[`Название подключения`]</div>
                <div class="value">
                    <input v-model="cTarget.name" type="text" class="longer" placeholder="[`Укажите название удаленного подключения`]">
                </div>
            </div>
            <div class="field">
                <div class="name">[`URL для подключения`]</div>
                <div class="value">
                    <input type="text" v-model="cTarget.url" class="longer" placeholder="https://site.ru/api.php" :disabled="cTarget.id">
                </div>
            </div>
            <div class="field custom-mt-48">
                <div class="name"></div>
                <div class="value">
                    <action-button
                        @click="saveTarget()"
                        :title="cTarget.id ? 'Сохранить' : 'Создать'"
                        icon="far fa-save"
                        action="saveTarget"
                        :run="runner"
                        :result="finish"
                        bclass="smaller outlined"
                    ></action-button>
                    <action-button
                        @click="cTarget = false"
                        title="[`Закрыть`]"
                        icon="fas fa-times"
                        action="tmp"
                        :run="runner"
                        :result="finish"
                        bclass="smaller gray custom-ml-48"
                    ></action-button>
                    <action-button
                        v-if="cTarget.id"
                        @click="deleteTarget()"
                        title="[`Удалить`]"
                        icon="far fa-trash-alt"
                        action="deleteTarget()"
                        :run="runner"
                        :result="finish"
                        bclass="smaller red outlined custom-ml-48"
                    ></action-button>
                </div>
            </div>
        </div>
    </template>
</template>
</div>
{/literal}