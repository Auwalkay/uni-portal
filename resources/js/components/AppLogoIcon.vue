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
    <div
        class="flex aspect-sqaure size-8 items-center justify-center rounded-md bg-white overflow-hidden"
        :class="className"
        v-bind="$attrs"
    >
        <img src="/miu-logo.png" alt="MIU Logo" class="size-full object-contain" />
    </div>
</template>
