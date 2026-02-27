<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationMark from './ApplicationMark.vue';

const page = usePage();
const tenantName = computed(() => (page.props as any).tenant?.name);
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-white text-primary">
        <ApplicationMark class="size-6" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            {{ tenantName || 'NBTE Portal' }}
        </span>
        <span class="text-xs text-muted-foreground font-normal">Management System</span>
    </div>
</template>
