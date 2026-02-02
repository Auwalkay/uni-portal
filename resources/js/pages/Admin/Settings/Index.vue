<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Shield, 
    Users, 
    Key, 
    Settings2, 
    ChevronRight,
    UserCheck,
    Lock,
    Globe,
    Database,
    Bell,
    ShieldAlert
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    stats: {
        roles_count: number;
        permissions_count: number;
        admin_users: number;
        staff_users: number;
    }
}>();

const breadcrumbs = [
    { title: 'System Settings', href: '/admin/settings' }
];

const settingsModules = [
    {
        title: 'Roles & Permissions',
        description: 'Define system roles and assign specific permissions to control user access levels.',
        icon: Shield,
        href: route('admin.settings.roles.index'),
        color: 'text-indigo-600',
        bgColor: 'bg-indigo-50 dark:bg-indigo-900/20',
        stats: `${props.stats.roles_count} Roles Defined`
    },
    {
        title: 'User Access Control',
        description: 'Manage all system users, assign roles, and audit account statuses.',
        icon: Users,
        href: route('admin.users.index'),
        color: 'text-blue-600',
        bgColor: 'bg-blue-50 dark:bg-blue-900/20',
        stats: `${props.stats.admin_users + props.stats.staff_users} Core Personnel`
    },
    {
        title: 'Security & Auth',
        description: 'Configure multi-factor authentication, session timeouts, and password policies.',
        icon: Lock,
        href: '#',
        color: 'text-orange-600',
        bgColor: 'bg-orange-50 dark:bg-orange-900/20',
        stats: 'Enterprise Secure'
    },
    {
        title: 'System Logs',
        description: 'Monitor administrative actions and track system-wide changes through audit logs.',
        icon: Database,
        href: route('admin.settings.logs.index'),
        color: 'text-slate-600',
        bgColor: 'bg-slate-50 dark:bg-slate-900/20',
        stats: 'Auto-logging Active'
    }
];
</script>

<template>
    <Head title="System Settings" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-6xl mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">System Settings</h1>
                    <p class="text-muted-foreground mt-1">Central hub for university portal administration and access control.</p>
                </div>
                
                <Badge variant="outline" class="px-3 py-1 border-primary/20 text-primary font-bold">
                    <ShieldAlert class="w-4 h-4 mr-2" /> Administrator Mode
                </Badge>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card v-for="module in settingsModules" :key="module.title" class="group hover:shadow-lg transition-all border-slate-200 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center gap-4 pb-2">
                        <div :class="[module.bgColor, 'p-3 rounded-xl transition-colors group-hover:scale-110 duration-200']">
                            <component :is="module.icon" :class="['w-6 h-6', module.color]" />
                        </div>
                        <div class="flex-1">
                            <CardTitle class="group-hover:text-primary transition-colors">{{ module.title }}</CardTitle>
                            <CardDescription>{{ module.stats }}</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            {{ module.description }}
                        </p>
                        <Button variant="ghost" as-child class="w-full justify-between group-hover:bg-primary/5 group-hover:text-primary p-0 h-auto py-2 px-3 border border-transparent group-hover:border-primary/10">
                            <Link :href="module.href">
                                Configure Module
                                <ChevronRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Access / Info Section -->
            <div class="bg-indigo-600 rounded-3xl p-8 text-white relative overflow-hidden shadow-2xl shadow-indigo-600/20 mt-12">
                <div class="relative z-10 grid md:grid-cols-2 gap-8 items-center">
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold">Advanced Role-Based Access Control</h2>
                        <p class="text-indigo-100/80 leading-relaxed italic">
                            "The portal utilizes a granular RBAC system powered by Spatie. This ensures that every staff member has exactly the access they need, no more, no less."
                        </p>
                        <div class="flex gap-4">
                            <div class="bg-white/10 backdrop-blur-sm p-3 rounded-2xl border border-white/20">
                                <p class="text-2xl font-black">{{ stats.permissions_count }}</p>
                                <p class="text-[10px] uppercase font-bold tracking-widest text-indigo-200">Total Capabilities</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm p-3 rounded-2xl border border-white/20">
                                <p class="text-2xl font-black">{{ stats.roles_count }}</p>
                                <p class="text-[10px] uppercase font-bold tracking-widest text-indigo-200">Active Roles</p>
                            </div>
                        </div>
                    </div>
                     <div class="hidden md:flex justify-end">
                        <Shield class="w-48 h-48 text-white/10 absolute -right-12 -bottom-12 rotate-12" />
                        <div class="space-y-3 w-64 bg-white/5 backdrop-blur-md p-4 rounded-2xl border border-white/10">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-green-400"></div>
                                <span class="text-xs font-medium">System Health: Optimal</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                                <span class="text-xs font-medium">Audit Logs: Enabled</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                                <span class="text-xs font-medium">RBAC Sync: Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
