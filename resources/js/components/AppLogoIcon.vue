<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 42" :class="className" v-bind="$attrs">
        <image href="/img/LogoDario.png" x="0" y="0" width="40" height="42" />
    </svg>
</template>
