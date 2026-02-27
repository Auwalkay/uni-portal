<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { 
    LayoutGrid, 
    Building2, 
    Settings, 
    Users, 
    Shield, 
    Globe, 
    BookOpen, 
    GraduationCap, 
    FileText, 
    BarChart3, 
    CreditCard,
    Activity,
    LogOut,
    Lock
} from 'lucide-vue-next';

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
import ApplicationMark from './ApplicationMark.vue';
import AppLogo from './AppLogo.vue';

import { computed } from 'vue';
import { route } from 'ziggy-js';

const overviewItems = computed(() => {
    return [
        {
            title: 'SaaS Dashboard',
            href: route('central.dashboard'),
            icon: LayoutGrid,
            show: true,
        },
        {
            title: 'Statistics',
            icon: BarChart3,
            show: true,
            items: [
                { title: 'Global Enrollment', href: '#' },
                { title: 'Financial Overview', href: '#' },
                { title: 'Platform Usage', href: '#' },
            ]
        },
    ].filter(item => item.show);
});

const tenantItems = computed(() => {
    return [
        {
            title: 'Polytechnics',
            href: route().has('central.tenants.index') ? route('central.tenants.index') : '#',
            icon: Building2,
            show: true,
        },
        {
            title: 'Domains',
            href: route('central.domains.index'),
            icon: Globe,
            show: true,
        },
        {
            title: 'Subscriptions',
            href: route('central.subscriptions.index'),
            icon: CreditCard,
            show: true,
        },
    ].filter(i => i.show);
});

const academicItems = computed(() => {
    return [
        {
            title: 'Faculties',
            href: '#',
            icon: Building2,
            show: true,
        },
        {
            title: 'Departments',
            href: '#',
            icon: GraduationCap,
            show: true,
        },
        {
            title: 'Programmes',
            href: '#',
            icon: BookOpen,
            show: true,
        },
    ].filter(i => i.show);
});

const reportItems = computed(() => {
    return [
        {
            title: 'Audit Logs',
            href: '#',
            icon: FileText,
            show: true,
        },
        {
            title: 'Platform Health',
            href: '#',
            icon: Activity,
            show: true,
        },
    ].filter(i => i.show);
});

const userManagementItems = computed(() => {
    return [
        {
            title: 'Manage Users',
            href: route('central.central-users.index'),
            icon: Users,
            show: true,
        },
        {
            title: 'Roles & Permissions',
            href: '#',
            icon: Shield,
            show: true,
        },
    ].filter(i => i.show);
});

const systemItems = computed(() => {
    return [
        {
            title: 'Platform Settings',
            href: '#',
            icon: Settings,
            show: true,
        },
    ].filter(i => i.show);
});

const footerNavItems = computed(() => {
    return [
        {
            title: 'SaaS Settings',
            href: '#',
            icon: Settings,
            show: true,
        },
    ].filter(item => item.show);
});

</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
        <SidebarHeader>
            <!-- Brand / Logo -->
            <div class="p-6 pb-2">
                <Link :href="route('central.dashboard')" class="flex items-center gap-3">
                    <ApplicationMark class="block h-9 w-auto fill-current text-primary" />
                    <span class="font-bold text-lg text-slate-800 dark:text-white tracking-tight leading-tight">NBTE Central<br/><span class="text-sm font-medium text-slate-500">Portal</span></span>
                </Link>
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="overviewItems" label="Overview" />
            <NavMain :items="tenantItems" label="Tenants & Domains" />
            <NavMain :items="userManagementItems" label="User Management" />
            <!-- <NavMain :items="academicItems" label="Academic Monitoring" /> -->
            <NavMain :items="reportItems" label="Reports & Analytics" />
            <NavMain :items="systemItems" label="System" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser :is-central="true" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
