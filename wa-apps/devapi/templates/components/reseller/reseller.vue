{literal}
<div class="drawer order-info" id="devapi-reseller">
<div class="drawer-background"></div>
<div class="drawer-body">
    <div class="drawer-block">
        <header class="drawer-header">
            <div class="custom-mt-8">
                <input v-model="filter" type="text" class="small long" placeholder="[`Начните вводить для поиска продукта`]">
            </div>
            <ul class="chips small">
                <li :class="getChipClass('APP')" @click="setFilterType('APP')">
                    <a><i class="fas fa-cubes"></i> [`Приложения`]</a>
                </li>
                <li :class="getChipClass('PLUGIN')" @click="setFilterType('PLUGIN')">
                    <a><i class="fas fa-plug"></i> [`Плагины`]</a>
                </li>
                <li :class="getChipClass('THEME')" @click="setFilterType('THEME')">
                    <a><i class="fas fa-paint-brush"></i> [`Темы дизайна`]</a>
                </li>
                <li :class="getChipClass('WIDGET')" @click="setFilterType('WIDGET')">
                    <a><i class="fas fa-chart-bar"></i> [`Виджеты`]</a>
                </li>
            </ul>
        </header>
        <div class="drawer-content">
            <template v-if="errorText!==false">
                <span class="danger alert">{{ errorText }}</span>
            </template>
            <template v-if="resellers.length">
                <table class="smaller compact">
                    <tbody>
                    <tr>
                        <th>[`Тип`]</th>
                        <th>[`Название`]</th>
                        <th>[`Скидка`]</th>
                        <th>[`Цена`]</th>
                        <th></th>
                    </tr>
                    <template v-for="(s, idx) in resellers">
                        <tr v-if="checkFilter(s)">
                            <td style="width: 6%">
                                <span v-if="s.type==='APP'" title="Приложение"><i class="fas fa-cubes"></i></span>
                                <span v-if="s.type==='PLUGIN'" title="Плагин"><i class="fas fa-plug"></i></span>
                                <span v-if="s.type==='THEME'" title="Тема дизайна"><i class="fas fa-paint-brush"></i></span>
                                <span v-if="s.type==='WIDGET'" title="Виджет"><i class="fas fa-chart-bar"></i></span>
                            </td>
                            <td style="width: 50% !important;">
                                <strong>{{s.product_name}}</strong>
                                [<span style="font-style: italic">{{s.developer_name}}</span>]
                            </td>
                            <td><span style="font-family: monospace">{{s.discount}}</span></td>
                            <td><span style="font-family: monospace">{{s.price_formatted}}</span></td>
                            <td>
                                <span v-if="s.url">
                                    <a :href="s.url" target="_blank" class="gray" title="[`Информация о продукте`]">
                                        <span><i class="fas fa-info-circle"></i></span>
                                    </a>
                                </span>
                                <span class="custom-ml-8">
                                    <a :href="s.buy_url.replace('webasyst.com', 'webasyst.ru')" target="_blank" class="gray">
                                        <span><i class="fas fa-external-link-alt"></i></span>
                                    </a>
                                </span>
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
    {include './reseller.js'}
})
</script>