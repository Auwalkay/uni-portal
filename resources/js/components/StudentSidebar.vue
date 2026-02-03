<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, BookOpen, CreditCard, FileText, User, GraduationCap, CalendarClock } from 'lucide-vue-next';

import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/student/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My Profile',
        href: '/student/profile',
        icon: User,
    },
    {
        title: 'Course Registration',
        href: '/student/courses',
        icon: BookOpen,
    },
    {
        title: 'Results',
        href: '/student/results',
        icon: FileText,
    },
    {
        title: 'Timetable',
        href: '/student/timetable',
        icon: CalendarClock,
    },
    {
        title: 'Payments',
        href: '/student/payments',
        icon: CreditCard,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
