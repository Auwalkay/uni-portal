<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, Shield, GraduationCap, CreditCard, FileText, Banknote, Calendar } from 'lucide-vue-next';

import NavFooter from '@/components/NavFooter.vue';
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
        href: '/admin/dashboard', // TODO: Use route('admin.dashboard') when available
        icon: LayoutGrid,
    },
    {
        title: 'Admissions',
        href: '/admin/admissions',
        icon: FileText,
    },
    {
        title: 'Results',
        href: '/admin/results',
        icon: FileText,
    },
    {
        title: 'Students',
        href: '/admin/students',
        icon: GraduationCap,
    },
    {
        title: 'Staff & Users',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Payments',
        href: '/admin/payments',
        icon: CreditCard,
    },
    {
        title: 'Finance Config',
        href: '/admin/finance',
        icon: Banknote,
    },
    {
        title: 'Academics',
        href: '/admin/academics',
        icon: BookOpen,
    },
    {
        title: 'Sessions',
        href: '/admin/sessions',
        icon: Calendar,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'System Settings',
        href: '/admin/settings',
        icon: Shield,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
