<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Shield, 
    ShieldCheck, 
    Users, 
    Settings2, 
    ChevronRight,
    ArrowLeft,
    Plus,
    Info,
    Search,
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const props = defineProps<{
    roles: Array<{
        id: number;
        name: string;
        users_count: number;
    }>;
}>();

const breadcrumbs = [
    { title: 'System Settings', href: route('admin.settings.index') },
    { title: 'Roles & Permissions', href: '#' }
];

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getRoleDescription = (name: string) => {
    const descriptions: Record<string, string> = {
        'admin': 'Full system access. Can manage all university modules and portal settings.',
        'registrar': 'Academic administration. Manages students, results, and sessions.',
        'staff': 'Basic staff access level. Primary role for university employees.',
        'student': 'Access to student dashboard, course registration, and results.',
        'lecturer': 'Academic instruction. Can enter grades and view course lists.',
        'bursar': 'Financial administration. Manages invoices, fee types, and payments.',
        'dean': 'Faculty-level oversight. Can view results and academic reports.',
        'hod': 'Department-level management. Can manage courses and student lists.',
    };
    return descriptions[name.toLowerCase()] || 'Custom role for specific administrative or academic tasks.';
};
</script>

<template>
    <Head title="Manage Roles" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-6xl mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                   <Button variant="ghost" size="icon" as-child>
                       <Link :href="route('admin.settings.index')">
                            <ArrowLeft class="w-5 h-5" />
                       </Link>
                   </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">Roles & Permissions</h1>
                        <p class="text-muted-foreground mt-1">Manage administrative authorities and user access levels across the portal.</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" class="gap-2">
                         <Plus class="w-4 h-4" /> Create Custom Role
                    </Button>
                </div>
            </div>

            <div class="grid gap-6">
                <!-- Core System Roles -->
                <Card class="border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <ShieldCheck class="w-5 h-5 text-indigo-600" /> Defined System Roles
                                </CardTitle>
                                <CardDescription>University-wide roles defined in the RBAC matrix.</CardDescription>
                            </div>
                            <Badge variant="secondary" class="bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400">
                                {{ roles.length }} Active Roles
                            </Badge>
                        </div>
                    </CardHeader>
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-[250px]">Role Name</TableHead>
                                <TableHead>Responsibility Overview</TableHead>
                                <TableHead class="text-center w-[150px]">Members</TableHead>
                                <TableHead class="text-right w-[150px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="role in roles" :key="role.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                                            <Shield class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                        </div>
                                        <span class="font-bold text-foreground">{{ formatRoleName(role.name) }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm text-muted-foreground line-clamp-1 group-hover:line-clamp-none transition-all">
                                        {{ getRoleDescription(role.name) }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Users class="w-3.5 h-3.5 text-muted-foreground" />
                                        <span class="text-sm font-semibold">{{ role.users_count }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button variant="ghost" size="sm" as-child class="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50">
                                        <Link :href="route('admin.settings.roles.edit', role.id)">
                                            <Settings2 class="w-4 h-4 mr-2" /> Permissions
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <!-- Information/Help Card -->
                <div class="p-6 bg-slate-50 dark:bg-slate-900/20 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 flex gap-4">
                    <div class="p-3 bg-white dark:bg-slate-950 rounded-xl h-fit border shadow-sm">
                        <Info class="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <h4 class="font-bold mb-1">About Permission Synchronization</h4>
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            Changes to role permissions are applied in real-time. However, users currently logged in may need to refresh their session (re-login) for certain complex capability updates to propagate to their frontend navigation components.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
