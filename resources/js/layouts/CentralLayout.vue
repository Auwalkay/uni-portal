<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import CentralSidebar from '@/components/CentralSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <CentralSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden relative">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            
            <header v-if="$slots.header" class="border-b border-sidebar-border bg-white px-6 py-4 shadow-sm z-10 sticky top-0">
                <div class="max-w-7xl mx-auto">
                    <slot name="header" />
                </div>
            </header>

            <slot />
        </AppContent>
    </AppShell>
</template>
