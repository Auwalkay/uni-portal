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

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const page = usePage();
const userPermissions = computed(() => (page.props.auth.user as any)?.permissions || []);
const isAdmin = computed(() => (page.props.auth.user as any)?.roles?.includes('admin'));


const mainNavItems = computed(() => {
    const items = [
        {
            title: 'Dashboard',
            href: route('admin.dashboard'),
            icon: LayoutGrid,
            show: true,
        },
        {
            title: 'Admissions',
            href: '/admin/admissions',
            icon: FileText,
            show: isAdmin.value || userPermissions.value.includes('view_applications'),
        },
        {
            title: 'Results',
            href: '/admin/results',
            icon: FileText,
            show: isAdmin.value || userPermissions.value.includes('view_results'),
        },
        {
            title: 'Students',
            href: '/admin/students',
            icon: GraduationCap,
            show: true,
        },
        {
            title: 'Staff Only',
            href: route('admin.staff.index'),
            icon: Users,
            show: isAdmin.value || userPermissions.value.includes('manage_staff'),
        },
        {
            title: 'Users',
            href: '/admin/users',
            icon: Users,
            show: isAdmin.value,
        },
        {
            title: 'Payments',
            href: '/admin/payments',
            icon: CreditCard,
            show: isAdmin.value || userPermissions.value.includes('view_payments'),
        },
        {
            title: 'Finance Dashboard',
            href: route('admin.finance.dashboard'),
            icon: Banknote,
            show: isAdmin.value || userPermissions.value.includes('view_payments'),
        },
        {
            title: 'Finance Config',
            href: '/admin/finance',
            icon: Banknote,
            show: isAdmin.value || userPermissions.value.includes('manage_payments'),
        },
        {
            title: 'Academics',
            href: '/admin/academics',
            icon: BookOpen,
            show: isAdmin.value || userPermissions.value.includes('manage_courses'),
        },
        {
            title: 'Sessions',
            href: '/admin/sessions',
            icon: Calendar,
            show: isAdmin.value || userPermissions.value.includes('manage_courses'),
        },
    ];

    return items.filter(item => item.show);
});

const footerNavItems = computed(() => {
    return [
        {
            title: 'System Settings',
            href: '/admin/settings',
            icon: Shield,
            show: isAdmin.value,
        },
    ].filter(item => item.show);
});

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
