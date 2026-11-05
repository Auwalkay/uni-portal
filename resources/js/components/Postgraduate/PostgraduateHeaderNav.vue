<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    GraduationCap,
    Users,
    UserCheck,
    FileText,
    ArrowUpRight,
    Award,
    FlaskConical,
    Sparkles,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';

interface Props {
    activeTab?: 'operations' | 'quotas' | 'supervision' | 'admissions';
    actionCount?: number;
    actionLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    activeTab: 'operations',
});

const page = usePage();

const hasRole = (roleNames: string | string[]) => {
    const roles = ((page.props.auth as any)?.user?.roles || []) as Array<{ name: string } | string>;
    const roleList = Array.isArray(roleNames) ? roleNames : [roleNames];
    return roles.some(r => {
        const name = typeof r === 'string' ? r : r.name;
        return roleList.includes(name);
    });
};

const hasPermission = (permissionName: string) => {
    const perms = ((page.props.auth as any)?.user?.permissions || []) as Array<{ name: string } | string>;
    const isSuperAdmin = hasRole(['admin', 'super_admin', 'super-admin']);
    if (isSuperAdmin) return true;
    return perms.some(p => {
        const name = typeof p === 'string' ? p : p.name;
        return name === permissionName;
    });
};

const navItems = computed(() => {
    const isSuperAdmin = hasRole(['admin', 'super_admin', 'super-admin']);

    return [
        {
            id: 'operations',
            label: 'SPGS Operations Desk',
            description: 'Theses, Milestones & Broadsheets',
            href: '/admin/postgraduate/research',
            icon: FlaskConical,
            show: isSuperAdmin || hasPermission('manage_postgraduate_school') || hasPermission('view_pg_dashboard') || hasRole(['dean_spgs', 'dpgc_chair', 'hod']),
        },
        {
            id: 'quotas',
            label: 'Faculty & Supervisory Quotas',
            description: 'NUC Caps & External Registry',
            href: '/admin/postgraduate/staff',
            icon: Users,
            show: isSuperAdmin || hasPermission('manage_pg_supervisors') || hasPermission('view_pg_supervisors') || hasRole(['dean_spgs', 'dean', 'hod', 'dpgc_chair']),
        },
        {
            id: 'supervision',
            label: 'Supervisory Workbench',
            description: 'My Assigned PG Candidates',
            href: '/staff/postgraduate/supervision',
            icon: UserCheck,
            show: hasPermission('supervise_pg_students') || hasRole(['lecturer', 'staff']),
        },
        {
            id: 'admissions',
            label: 'PG Admissions Pool',
            description: 'New Applications & Screening',
            href: '/admin/admissions/pg',
            icon: Award,
            show: isSuperAdmin || hasPermission('view_pg_applications'),
        },
    ].filter(item => item.show);
});
</script>

<template>
    <div class="border-b border-border/70 bg-card/60 backdrop-blur-sm -mt-2 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-2 pb-0 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Nav Items -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
                <Link
                    v-for="item in navItems"
                    :key="item.id"
                    :href="item.href"
                    class="group relative flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all shrink-0"
                    :class="[
                        activeTab === item.id
                            ? 'bg-purple-500/10 text-purple-700 dark:text-purple-300 font-bold border border-purple-300/60 dark:border-purple-800'
                            : 'text-muted-foreground hover:text-foreground hover:bg-muted/60 border border-transparent'
                    ]"
                >
                    <component
                        :is="item.icon"
                        class="w-4 h-4 transition-transform group-hover:scale-110"
                        :class="activeTab === item.id ? 'text-purple-600 dark:text-purple-400' : 'text-muted-foreground'"
                    />
                    <div class="flex flex-col items-start leading-tight">
                        <span>{{ item.label }}</span>
                        <span class="text-[10px] font-normal opacity-70 hidden md:inline">{{ item.description }}</span>
                    </div>

                    <span
                        v-if="activeTab === item.id"
                        class="absolute bottom-0 left-3 right-3 h-0.5 bg-purple-600 dark:bg-purple-400 rounded-full"
                    />
                </Link>
            </div>

            <!-- Context Indicator -->
            <div class="hidden lg:flex items-center gap-2 shrink-0 pb-1">
                <Badge variant="outline" class="text-[11px] font-medium py-1 px-2.5 bg-background border-border/70 text-muted-foreground flex items-center gap-1.5">
                    <Sparkles class="w-3 h-3 text-purple-600 dark:text-purple-400" />
                    School of Postgraduate Studies (SPGS)
                </Badge>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

