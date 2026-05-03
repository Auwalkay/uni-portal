<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
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
    ShieldAlert,
    Award,
    Hash
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardFooter,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Swal from 'sweetalert2';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        roles_count: number;
        permissions_count: number;
        admin_users: number;
        staff_users: number;
    },
    settings: {
        matric_format: string;
        admin_charge_amount: string | number;
        admin_charge_enabled: boolean;
    }
}>();

const breadcrumbs = [
    { title: 'System Settings', href: '/admin/settings' }
];

const form = useForm({
    key: 'matric_format',
    value: props.settings.matric_format
});

const adminChargeForm = useForm({
    amount: props.settings.admin_charge_amount,
    enabled: props.settings.admin_charge_enabled
});

const submitSetting = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: 'System setting updated successfully',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const submitAdminCharge = () => {
    // We'll update both settings
    const amountPromise = axios.post(route('admin.settings.update'), { key: 'admin_charge_amount', value: adminChargeForm.amount });
    const enabledPromise = axios.post(route('admin.settings.update'), { key: 'admin_charge_enabled', value: adminChargeForm.enabled });
    
    Promise.all([amountPromise, enabledPromise]).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Updated',
            text: 'Administrative charge settings updated',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    });
};

const matricPreview = computed(() => {
    const year = new Date().getFullYear().toString().slice(-2);
    const random = '1234';
    const sequence = '001';
    const dept = 'SEN';
    const faculty = 'ENG';
    return form.value
        ? form.value.replace('{YEAR}', year.toString())
                    .replace('{RANDOM}', random)
                    .replace('{SEQUENCE}', sequence)
                    .replace('{DEPT}', dept)
                    .replace('{FACULTY}', faculty)
        : '---';
});

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
        title: 'Designations',
        description: 'Manage staff designations and ranks across the university departments.',
        icon: Award,
        href: route('admin.designations.index'),
        color: 'text-rose-600',
        bgColor: 'bg-rose-50 dark:bg-rose-900/20',
        stats: 'Dynamic Ranks'
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
                <!-- General Configuration Card -->
                <Card class="border-primary/20 bg-primary/5">
                    <CardHeader class="flex flex-row items-center gap-4">
                        <div class="bg-primary/10 p-3 rounded-xl text-primary">
                            <Settings2 class="w-6 h-6" />
                        </div>
                        <div>
                            <CardTitle>General Configuration</CardTitle>
                            <CardDescription>Configure core system behaviors and identifiers.</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="matric_format" class="flex items-center gap-2">
                                    <Hash class="w-4 h-4" />
                                    Matriculation Number Format
                                </Label>
                                <Input 
                                    id="matric_format" 
                                    v-model="form.value" 
                                    placeholder="MIU{YEAR}{SEQUENCE}" 
                                />
                                <div class="bg-white dark:bg-slate-900 border rounded-xl p-3 text-center">
                                    <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mb-1">Preview</p>
                                    <p class="text-xl font-black font-mono tracking-tighter text-primary">{{ matricPreview }}</p>
                                </div>
                                <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider">
                                    Placeholders: {YEAR}, {RANDOM}, {SEQUENCE}, {DEPT}, {FACULTY}
                                </p>
                            </div>
                            <Button @click="submitSetting" :disabled="form.processing" class="w-full">
                                {{ form.processing ? 'Saving...' : 'Update Format' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Administrative Charges Card -->
                <Card class="border-indigo-200 bg-indigo-50/30">
                    <CardHeader class="flex flex-row items-center gap-4">
                        <div class="bg-indigo-100 p-3 rounded-xl text-indigo-600">
                            <CreditCard class="w-6 h-6" />
                        </div>
                        <div>
                            <CardTitle>Administrative Charges</CardTitle>
                            <CardDescription>Global administrative fee applied to school fees.</CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-4">
                            <div class="flex items-center justify-between">
                                <Label for="admin_enabled">Enable Admin Charge</Label>
                                <Switch 
                                    id="admin_enabled" 
                                    v-model:checked="adminChargeForm.enabled" 
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="admin_amount">Charge Amount (₦)</Label>
                                <Input 
                                    id="admin_amount" 
                                    type="number" 
                                    v-model="adminChargeForm.amount" 
                                    :disabled="!adminChargeForm.enabled"
                                />
                            </div>
                        </div>
                        <Button @click="submitAdminCharge" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white">
                            Update Financials
                        </Button>
                    </CardContent>
                </Card>

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
