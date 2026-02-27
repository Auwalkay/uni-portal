<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import ApplicationMark from './ApplicationMark.vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <ApplicationMark :class="className" v-bind="$attrs" />
</template>
