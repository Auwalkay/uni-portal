<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const tenantLogo = computed(() => (page.props as any).tenant?.logo);
</script>

<template>
    <div v-if="tenantLogo" class="flex items-center justify-center">
        <img :src="tenantLogo" alt="School Logo" v-bind="$attrs" class="object-contain" />
    </div>
    <div v-else class="flex items-center justify-center">
        <img src="/images/nbte-logo.png" alt="NBTE Logo" v-bind="$attrs" class="object-contain" />
    </div>
</template>
