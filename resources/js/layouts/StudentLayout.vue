<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import StudentSidebar from '@/components/StudentSidebar.vue';
import PostgraduateSidebar from '@/components/PostgraduateSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isPostgraduate = computed(() => {
    return Boolean((page.props.auth as any)?.user?.student?.is_postgraduate);
});
</script>

<template>
    <AppShell variant="sidebar">
        <PostgraduateSidebar v-if="isPostgraduate" />
        <StudentSidebar v-else />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
