<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    User, 
    Mail, 
    BadgeCheck, 
    Building2, 
    Briefcase, 
    GraduationCap, 
    Shield, 
    ArrowLeft,
    Calendar,
    Pencil,
    ShieldCheck,
    UserCircle,
    Building,
    Hash
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription
} from '@/components/ui/card'
import { route } from 'ziggy-js';

const props = defineProps<{
    staff: {
        id: string;
        name: string;
        email: string;
        roles: Array<{ id: string; name: string }>;
        staff: {
            staff_number: string;
            designation: string;
            is_academic: boolean;
            department: {
                name: string;
                faculty: {
                    name: string;
                } | null;
            } | null;
        } | null;
    };
}>();

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const breadcrumbs = [
    { title: 'Staff Management', href: '/admin/staff' },
    { title: 'Staff Profile', href: '#' }
];
</script>

<template>
    <Head :title="`${staff.name} - Staff Profile`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-5xl mx-auto">
            
            <!-- Header/Navigation -->
            <div class="flex items-center justify-between">
                <Button variant="ghost" as-child class="gap-2">
                    <Link :href="route('admin.staff.index')">
                        <ArrowLeft class="w-4 h-4" /> Back to Directory
                    </Link>
                </Button>

                <Button variant="outline" as-child class="gap-2 border-primary/20 hover:bg-primary/5">
                    <Link :href="route('admin.staff.edit', staff.id)">
                        <Pencil class="w-4 h-4" /> Edit Profile
                    </Link>
                </Button>
            </div>

            <!-- Profile Summary Card -->
            <div class="relative">
                <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent rounded-t-2xl border-x border-t"></div>
                
                <Card class="border shadow-md pt-12 overflow-hidden bg-white dark:bg-slate-950">
                    <CardContent class="relative px-8 pb-8 flex flex-col md:flex-row items-start md:items-center gap-6">
                        <Avatar class="h-24 w-24 border-4 border-background shadow-xl scale-110">
                            <AvatarFallback class="bg-primary/10 text-primary text-3xl font-bold">
                                {{ staff.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="flex-1 space-y-1 pt-2 md:pt-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-3xl font-extrabold tracking-tight">{{ staff.name }}</h1>
                                <Badge variant="secondary" class="bg-primary/5 text-primary border-primary/10 font-bold px-3">
                                    {{ staff.staff?.is_academic ? 'Academic Staff' : 'Non-Academic Staff' }}
                                </Badge>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-muted-foreground text-sm font-medium">
                                <div class="flex items-center gap-1.5">
                                    <Briefcase class="w-4 h-4 text-primary/60" /> {{ staff.staff?.designation || 'No Designation' }}
                                </div>
                                <div class="flex items-center gap-1.5 border-l pl-4">
                                    <Hash class="w-4 h-4 text-primary/60" /> {{ staff.staff?.staff_number || 'No ID' }}
                                </div>
                                <div class="flex items-center gap-1.5 border-l pl-4">
                                    <Mail class="w-4 h-4 text-primary/60" /> {{ staff.email }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 w-full md:w-auto mt-4 md:mt-0 pt-4 md:pt-0 border-t md:border-t-0 border-slate-100">
                             <div class="flex flex-wrap gap-1.5 justify-start md:justify-end">
                                <template v-for="role in staff.roles" :key="role.id">
                                    <Badge 
                                        v-if="role.name !== 'staff'" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white border-0 py-1"
                                    >
                                        <ShieldCheck class="w-3.5 h-3.5 mr-1.5" /> {{ formatRoleName(role.name) }}
                                    </Badge>
                                </template>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Left Column: Primary Details -->
                <div class="md:col-span-2 space-y-8">
                    <!-- Academic Context -->
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-4 border-b bg-slate-50/50 dark:bg-slate-900/20">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <Building2 class="w-5 h-5 text-primary" /> Academic & Administrative Placement
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="grid sm:grid-cols-2 gap-8 py-8">
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Faculty</p>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-primary/5 rounded-lg border border-primary/10">
                                            <Building class="w-5 h-5 text-primary" />
                                        </div>
                                        <p class="font-semibold text-lg">{{ staff.staff?.department?.faculty?.name || 'Not assigned to a faculty' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Department</p>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-orange-500/5 rounded-lg border border-orange-500/10">
                                            <GraduationCap class="w-5 h-5 text-orange-600" />
                                        </div>
                                        <p class="font-semibold text-lg">{{ staff.staff?.department?.name || 'General Administration' }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Roles and Permissions -->
                    <Card class="border shadow-sm overflow-hidden">
                        <CardHeader class="pb-4 border-b bg-indigo-50/30 dark:bg-indigo-900/10">
                            <CardTitle class="text-lg flex items-center gap-2 text-indigo-700 dark:text-indigo-400">
                                <Shield class="w-5 h-5" /> System Roles & Access Levels
                            </CardTitle>
                            <CardDescription>Visual breakdown of administrative authorities assigned to this account.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="divide-y">
                                <div v-for="role in staff.roles" :key="role.id" 
                                    class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-900/30 transition-colors"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="p-2 rounded-full border bg-white dark:bg-slate-900">
                                            <ShieldCheck class="w-4 h-4 text-indigo-600" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm">{{ formatRoleName(role.name) }}</p>
                                            <p class="text-xs text-muted-foreground">Standard access level for {{ role.name }}.</p>
                                        </div>
                                    </div>
                                    <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-tighter">Active Role</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Account Stats -->
                <div class="space-y-8">
                    <!-- Quick Stats Card -->
                    <Card class="border shadow-inner bg-slate-50 dark:bg-slate-900/20">
                        <CardContent class="p-6 space-y-6">
                            <div class="space-y-2">
                                <Label class="text-xs font-black uppercase text-muted-foreground tracking-widest">Employee Profile</Label>
                                <div class="p-4 bg-white dark:bg-slate-950 rounded-xl border border-dashed border-slate-300 dark:border-slate-800">
                                   <div class="flex items-center justify-between mb-4">
                                       <span class="text-sm text-muted-foreground">Status</span>
                                       <Badge class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-100 border-green-200">Active</Badge>
                                   </div>
                                   <div class="flex items-center justify-between">
                                       <span class="text-sm text-muted-foreground">Type</span>
                                       <span class="text-sm font-bold">{{ staff.staff?.is_academic ? 'Regular Academic' : 'Administrative' }}</span>
                                   </div>
                                </div>
                            </div>

                            <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                                <div class="flex items-center gap-3">
                                    <Calendar class="w-4 h-4 text-muted-foreground" />
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-muted-foreground uppercase tracking-widest leading-none mb-1">Onboarded Since</span>
                                        <span class="text-sm font-medium italic">December 2023</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <UserCircle class="w-4 h-4 text-muted-foreground" />
                                    <div class="flex flex-col">
                                        <span class="text-[10px] text-muted-foreground uppercase tracking-widest leading-none mb-1">Account Identity</span>
                                        <span class="text-xs font-mono truncate max-w-[180px]">{{ staff.id }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Security Info -->
                    <div class="p-5 bg-indigo-600 rounded-2xl text-white shadow-xl shadow-indigo-600/20 space-y-4">
                        <div class="flex items-center gap-2">
                            <ShieldCheck class="w-5 h-5" />
                            <h4 class="font-bold">Security Status</h4>
                        </div>
                        <p class="text-indigo-100 text-xs leading-relaxed">
                            This staff member has elevated system permissions. Ensure all administrative actions are audited and comply with University data privacy policies.
                        </p>
                        <Button variant="secondary" class="w-full bg-white text-indigo-600 hover:bg-indigo-50 border-0 font-bold" as-child>
                             <Link :href="route('admin.staff.edit', staff.id)">Manage Account</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
