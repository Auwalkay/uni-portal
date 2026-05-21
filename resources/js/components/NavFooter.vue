<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { useActiveUrl } from '@/composables/useActiveUrl';

interface Props {
    items: NavItem[];
    class?: string;
}

const props = defineProps<Props>();
const { urlIsActive } = useActiveUrl();
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                        as-child
                        :is-active="urlIsActive(item.href)"
                    >
                        <component
                            :is="item.href?.startsWith('http') ? 'a' : Link"
                            :href="toUrl(item.href)"
                            :target="item.href?.startsWith('http') ? '_blank' : undefined"
                            :rel="item.href?.startsWith('http') ? 'noopener noreferrer' : undefined"
                            class="flex items-center w-full"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <div v-if="item.href && urlIsActive(item.href)" class="ml-auto w-1 h-3 bg-primary rounded-full" />
                        </component>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
