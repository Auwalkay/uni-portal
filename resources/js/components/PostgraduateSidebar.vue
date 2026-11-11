<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { markRaw, computed } from 'vue';
import {
    LayoutGrid,
    BookOpen,
    CreditCard,
    FileText,
    User,
    GraduationCap,
    Library,
    LifeBuoy,
    Megaphone,
    HelpCircle,
} from 'lucide-vue-next';

const RawLink = markRaw(Link);
const page = usePage();

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
import { Badge } from '@/components/ui/badge';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const studentData = computed(() => (page.props.auth as any)?.user?.student);

const overviewItems: NavItem[] = [
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
        title: 'Announcements',
        href: '/student/announcements',
        icon: Megaphone,
    },
];

const academicsItems: NavItem[] = [
    {
        title: 'Course Registration',
        href: '/student/courses',
        icon: BookOpen,
    },
    {
        title: 'Results & Transcript',
        href: '/student/results',
        icon: FileText,
    },
];

const researchItems: NavItem[] = [
    {
        title: 'Research & Thesis Hub',
        href: '/student/research',
        icon: GraduationCap,
    },
];

const financeItems: NavItem[] = [
    {
        title: 'Fees & Milestones',
        href: '/student/payments',
        icon: CreditCard,
    },
];

const resourcesItems: NavItem[] = [
    {
        title: 'E-Library & Journals',
        href: '/student/library',
        icon: Library,
    },
    {
        title: 'SPGS Support Desk',
        href: '/student/support',
        icon: LifeBuoy,
    },
    {
        title: 'Candidate Handbook',
        href: '/student/manual',
        icon: HelpCircle,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border/60 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" :as="RawLink" href="/student/dashboard">
                        <AppLogo />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- SPGS Candidate Institutional Badge -->
            <div class="px-2 pt-2 group-data-[collapsible=icon]:hidden">
                <div class="p-2.5 rounded-xl bg-purple-500/10 border border-purple-500/20 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">
                            SPGS Portal
                        </span>
                        <Badge variant="outline" class="text-[9px] px-1.5 py-0 h-4 bg-purple-100/80 dark:bg-purple-950/60 text-purple-800 dark:text-purple-200 border-purple-300 dark:border-purple-800">
                            {{ studentData?.degree_type || (studentData?.current_level === 900 ? 'Ph.D' : 'Postgraduate') }}
                        </Badge>
                    </div>
                    <p class="text-xs font-semibold text-foreground truncate">
                        {{ studentData?.programme_name || 'Postgraduate Candidate' }}
                    </p>
                    <p v-if="studentData?.spgs_file_number" class="text-[10px] font-mono text-muted-foreground truncate">
                        File: {{ studentData.spgs_file_number }}
                    </p>
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="overviewItems" label="Overview" />
            <NavMain :items="academicsItems" label="Candidacy & Coursework" />
            <NavMain :items="researchItems" label="Research & Dissertation" />
            <NavMain :items="financeItems" label="Finances & Milestones" />
            <NavMain :items="resourcesItems" label="SPGS Resources" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

