<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, Shield, GraduationCap, CreditCard, FileText, Banknote, Calendar, CalendarRange, Wallet, DollarSign } from 'lucide-vue-next';

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


const overviewItems = computed(() => {
    return [
        {
            title: 'Dashboard',
            href: route('admin.dashboard'),
            icon: LayoutGrid,
            show: true,
        },
    ].filter(item => item.show);
});

const personalItems = computed(() => {
    return [
        {
            title: 'My Payslips',
            href: route('staff.payslips.index'),
            icon: Wallet,
            show: true, // Visible to all staff members
        },
    ];
});

const academicsItems = computed(() => {
    return [
        {
            title: 'Admission / Applicants',
            href: '/admin/admissions',
            icon: Users,
            show: isAdmin.value || userPermissions.value.includes('view_applications'),
        },
        {
            title: 'Students',
            href: '/admin/students',
            icon: GraduationCap,
            show: true,
        },
        {
            title: 'My Courses',
            href: route().has('admin.teaching.courses.index') ? route('admin.teaching.courses.index') : '#',
            icon: BookOpen,
            show: ['lecturer', 'course_coordinator', 'dean', 'hod'].some(role => (page.props.auth.user as any)?.roles?.includes(role)) || userPermissions.value.includes('manage_courses'),
        },
        {
            title: 'Timetables',
            href: route().has('admin.timetables.index') ? route('admin.timetables.index') : '#',
            icon: Calendar,
            show: isAdmin.value || userPermissions.value.includes('manage_courses'),
        },
        {
            title: 'Course Management',
            href: '/admin/academics',
            icon: Folder,
            show: isAdmin.value || userPermissions.value.includes('manage_courses'),
        },
        {
            title: 'Allocations',
            href: route().has('admin.course-allocations.index') ? route('admin.course-allocations.index') : '#',
            icon: CalendarRange,
            show: isAdmin.value || userPermissions.value.includes('manage_courses'),
        },
        {
            title: 'Session & Semester',
            href: '/admin/sessions',
            icon: CalendarRange,
            show: isAdmin.value || userPermissions.value.includes('manage_courses'),
        },
        {
            title: 'Results',
            href: '/admin/results',
            icon: FileText,
            show: isAdmin.value || userPermissions.value.includes('view_results'),
        },
    ].filter(i => i.show);
});

const financeItems = computed(() => {
    return [
        {
            title: 'Finance Dashboard',
            href: route().has('admin.finance.dashboard') ? route('admin.finance.dashboard') : '#',
            icon: LayoutGrid,
            show: isAdmin.value || userPermissions.value.includes('view_payments'),
        },
        {
            title: 'Expenses',
            href: route().has('admin.finance.expenses.index') ? route('admin.finance.expenses.index') : '#',
            icon: DollarSign,
            show: isAdmin.value || userPermissions.value.includes('manage_payments'),
        },
        {
            title: 'Payroll',
            href: route().has('admin.finance.payroll.index') ? route('admin.finance.payroll.index') : '#',
            icon: Banknote,
            show: isAdmin.value || userPermissions.value.includes('manage_payments'),
        },
        {
            title: 'Staff Salaries',
            href: route().has('admin.finance.salary.index') ? route('admin.finance.salary.index') : '#',
            icon: Wallet,
            show: isAdmin.value || userPermissions.value.includes('manage_payments'),
        },
        {
            title: 'Invoices',
            href: route().has('admin.invoices.index') ? route('admin.invoices.index') : '#',
            icon: CreditCard,
            show: isAdmin.value || userPermissions.value.includes('view_payments'),
        },
        {
            title: 'Payments',
            href: '/admin/payments',
            icon: CreditCard,
            show: isAdmin.value || userPermissions.value.includes('view_payments'),
        },
        {
            title: 'Finance Configuration',
            href: '/admin/finance',
            icon: Shield,
            show: isAdmin.value || userPermissions.value.includes('manage_payments'),
        },
    ].filter(i => i.show);
});

const administrationItems = computed(() => {
    return [
        {
            title: 'Staff Management',
            href: route().has('admin.staff.index') ? route('admin.staff.index') : '#',
            icon: Users,
            show: isAdmin.value || userPermissions.value.includes('manage_staff'),
        },
        {
            title: 'System Users',
            href: '/admin/users',
            icon: Shield,
            show: isAdmin.value,
        },
    ].filter(i => i.show);
});

const frontDeskItems = computed(() => {
    return [
        {
            title: 'Front Desk Dashboard',
            href: route().has('admin.front-desk.dashboard') ? route('admin.front-desk.dashboard') : '#',
            icon: LayoutGrid,
            show: isAdmin.value || (page.props.auth.user as any)?.roles?.includes('receptionist'),
        },
        {
            title: 'Visitors',
            href: route().has('admin.front-desk.visitors.index') ? route('admin.front-desk.visitors.index') : '#',
            icon: Users,
            show: isAdmin.value || userPermissions.value.includes('manage_visitors'),
        },
        {
            title: 'Complaints',
            href: route().has('admin.front-desk.complaints.index') ? route('admin.front-desk.complaints.index') : '#',
            icon: FileText,
            show: isAdmin.value || userPermissions.value.includes('manage_complaints'),
        },
        {
            title: 'Enquiries',
            href: route().has('admin.front-desk.enquiries.index') ? route('admin.front-desk.enquiries.index') : '#',
            icon: BookOpen,
            show: isAdmin.value || userPermissions.value.includes('manage_enquiries'),
        },
    ].filter(i => i.show);
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
            <NavMain :items="overviewItems" label="Overview" />
            <NavMain v-if="frontDeskItems.length > 0" :items="frontDeskItems" label="Front Desk" />
            <NavMain :items="personalItems" label="Personal" />
            <NavMain :items="academicsItems" label="Academics" />
            <NavMain :items="financeItems" label="Finance" />
            <NavMain :items="administrationItems" label="Administration" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
