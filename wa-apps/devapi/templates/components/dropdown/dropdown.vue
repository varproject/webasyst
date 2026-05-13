{literal}
<div :class="getClass" :id="id" :style="getMainStyle">
<button class="dropdown-toggle button" type="button" :style="getStyle">{{ name }}</button>
<div class="dropdown-body" style="background: var(--background-color)">
    <input v-if="search" v-model="filter" type="text" class="small custom-m-8" placeholder="[`Введите название`]">
    <input class="dropdown-trigger" :value="trigger" type="hidden">
    <ul class="menu">
        <template v-for="(op, idx) in getOptions">
                <li v-if="checkOption(op.id)">
                    <a :data-id="op.id">{{op.name}}</a>
                </li>
        </template>
    </ul>
</div>
</div>
{/literal}