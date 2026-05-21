<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, type LucideIcon } from 'lucide-vue-next';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                :key="item.title"
                as-child
                :default-open="item.isActive || item.items?.some(sub => sub.href && urlIsActive(sub.href))"
                class="group/collapsible"
            >
                <SidebarMenuItem>
                    <!-- Item with submenu -->
                    <template v-if="item.items?.length">
                         <CollapsibleTrigger as-child>
                            <SidebarMenuButton :tooltip="item.title" :is-active="item.isActive">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                     <SidebarMenuSubButton as-child :is-active="subItem.href ? urlIsActive(subItem.href) : false">
                                        <Link :href="subItem.href || '#'" class="flex items-center w-full">
                                            <span>{{ subItem.title }}</span>
                                            <div v-if="subItem.href && urlIsActive(subItem.href)" class="ml-auto w-1 h-3 bg-primary rounded-full" />
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </template>
                    <!-- Item without submenu -->
                    <template v-else>
                         <SidebarMenuButton as-child :is-active="item.href ? urlIsActive(item.href) : false" :tooltip="item.title">
                            <Link :href="item.href || '#'" class="flex items-center w-full">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <div v-if="item.href && urlIsActive(item.href)" class="ml-auto w-1 h-3.5 bg-primary rounded-full" />
                            </Link>
                        </SidebarMenuButton>
                    </template>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
