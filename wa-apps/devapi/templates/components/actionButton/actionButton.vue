{literal}
<a @click="$emit(buttonClick, $event)" :class="getButtonClass()" :style="getButtonStyle()">
    <span v-if="checkLoading()" class="loading"><i class="fas fa-spinner wa-animation-spin speed-1000"></i></span>
    <span class="bIcon" v-if="run!==action"><i :class="icon"></i></span>
    <span v-if="checkResult()" class="trueResult"><i class="fas fa-check"></i></span>
    <span v-if="title">&nbsp;&nbsp;{{title}}</span>
</a>
{/literal}