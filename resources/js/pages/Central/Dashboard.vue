<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Users, 
    BookOpen, 
    Building2, 
    GraduationCap, 
    Briefcase,
    FileText,
    ArrowRight,
    TrendingUp,
    AlertTriangle,
    History,
    Zap,
    DollarSign,
    CheckCircle2,
    Calendar,
    ShieldCheck,
    UserPlus,
    Shield,
    Upload
} from 'lucide-vue-next';

import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger 
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Swal from 'sweetalert2';

import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    tenants: any[];
    platformInsights: {
        total_students: number;
        total_staff: number;
        total_applications: number;
        total_courses: number;
        total_departments: number;
        total_revenue: number;
        active_tenants: number;
        expiring_soon: any[];
    };
    recentActivity: {
        registrations: any[];
        payments: any[];
    };
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount);
};

const currentHour = new Date().getHours();
const greeting = computed(() => {
    if (currentHour < 12) return 'Good Morning';
    if (currentHour < 17) return 'Good Afternoon';
    return 'Good Evening';
});

const todayDate = new Date().toLocaleDateString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
});

const isUploadModalOpen = ref(false);

const uploadForm = useForm({
    tenant_id: '',
    file: null as File | null,
});

const submitUpload = () => {
    if (!uploadForm.tenant_id) {
        Swal.fire('Error', 'Please select a polytechnic first.', 'error');
        return;
    }

    uploadForm.post(route('central.tenants.academics.upload', uploadForm.tenant_id), {
        onSuccess: () => {
            isUploadModalOpen.value = false;
            uploadForm.reset();
            Swal.fire('Imported!', 'Academic structure imported successfully.', 'success');
        },
        onError: (errors) => {
            Swal.fire('Error', errors.file || 'Failed to import structure.', 'error');
        }
    });
};
</script>

<template>
    <Head title="Platform Health Dashboard" />

    <CentralLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-3xl text-slate-900 tracking-tight">
                        {{ greeting }}, Admin
                    </h2>
                    <p class="text-slate-500 mt-1 flex items-center gap-2">
                        <Calendar class="h-4 w-4" /> {{ todayDate }}
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-white/50 backdrop-blur-sm border p-1 rounded-lg">
                    <Badge variant="secondary" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                        Live Tracking Enabled
                    </Badge>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Main KPI Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Revenue Card -->
                    <Card class="relative overflow-hidden border-none shadow-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-blue-800 text-white group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                            <DollarSign class="h-24 w-24" />
                        </div>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-indigo-100 uppercase tracking-wider">Total SaaS Revenue</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-4xl font-extrabold">{{ formatCurrency(platformInsights.total_revenue) }}</div>
                            <div class="flex items-center gap-1 mt-2 text-indigo-100 text-xs">
                                <TrendingUp class="h-3 w-3" />
                                <span>Platform-wide subscription total</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Active Entities -->
                    <Card class="bg-white border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-slate-500">Live Polytechnics</CardTitle>
                            <Building2 class="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900">{{ tenants.length }}</div>
                            <div class="flex items-center gap-1 mt-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-xs text-emerald-600 font-medium">{{ platformInsights.active_tenants }} Operational</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="bg-white border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-slate-500">Global Enrollment</CardTitle>
                            <Users class="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900">{{ platformInsights.total_students.toLocaleString() }}</div>
                            <p class="text-xs text-slate-500 mt-1">Active students across all sites</p>
                        </CardContent>
                    </Card>

                    <Card class="bg-white border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-slate-500">Applications</CardTitle>
                            <Zap class="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900">{{ platformInsights.total_applications.toLocaleString() }}</div>
                            <p class="text-xs text-slate-500 mt-1">Processed processing fee payments</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-widest flex items-center gap-2 mb-4">
                            <Zap class="h-4 w-4 text-indigo-500" /> Platform Quick Actions
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Link :href="route('central.central-users.index')" 
                                  class="flex items-center gap-4 p-4 bg-white border border-slate-200 rounded-2xl hover:border-indigo-500 hover:shadow-md transition group">
                                <div class="bg-indigo-50 text-indigo-600 p-3 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition">
                                    <UserPlus class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 leading-tight">Add Platform Admin</p>
                                    <p class="text-xs text-slate-500">Create & assign roles to users</p>
                                </div>
                            </Link>

                            <Link :href="route('central.tenants.index')" 
                                  class="flex items-center gap-4 p-4 bg-white border border-slate-200 rounded-2xl hover:border-blue-500 hover:shadow-md transition group">
                                <div class="bg-blue-50 text-blue-600 p-3 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition">
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 leading-tight">Register Polytechnic</p>
                                    <p class="text-xs text-slate-500">Add a new university site</p>
                                </div>
                            </Link>

                            <button @click="isUploadModalOpen = true"
                                  class="flex items-center gap-4 p-4 bg-white border border-slate-200 rounded-2xl hover:border-emerald-500 hover:shadow-md transition group text-left w-full">
                                <div class="bg-emerald-50 text-emerald-600 p-3 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition">
                                    <Upload class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 leading-tight">Bulk Academic Upload</p>
                                    <p class="text-xs text-slate-500">Provision faculties & depts</p>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-indigo-900 rounded-2xl p-6 text-white relative overflow-hidden flex flex-col justify-center border border-indigo-800">
                        <div class="relative z-10">
                            <h4 class="font-bold flex items-center gap-2">
                                <ShieldCheck class="h-4 w-4 text-indigo-400" /> Administrative Oversight
                            </h4>
                            <p class="text-xs text-indigo-100/70 mt-1 leading-relaxed">
                                You are managing the national NBTE portal. All actions are logged for audit compliance.
                            </p>
                        </div>
                        <Shield class="absolute -bottom-4 -right-4 h-24 w-24 text-white/5" />
                    </div>
                </div>

                <!-- Smart Alerts -->
                <div v-if="platformInsights.expiring_soon.length > 0" class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <AlertTriangle class="h-4 w-4 text-amber-500" /> Urgent Action Required
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="sub in platformInsights.expiring_soon" :key="sub.id" 
                            class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between group hover:bg-amber-100 transition">
                            <div class="flex items-center gap-3">
                                <div class="bg-amber-500 text-white p-2 rounded-lg">
                                    <Calendar class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-amber-900">{{ sub.tenant?.school_name || 'Institution' }}</p>
                                    <p class="text-xs text-amber-700">Expires: {{ new Date(sub.end_date).toLocaleDateString() }}</p>
                                </div>
                            </div>
                            <Link :href="route('central.tenants.show', sub.tenant_id)" class="bg-white p-2 border rounded-full text-amber-600 hover:bg-amber-500 hover:text-white transition">
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Top Institutions Table -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <History class="h-5 w-5 text-indigo-600" /> Institution Ranking
                            </h3>
                            <Link :href="route('central.tenants.index')" class="text-sm text-indigo-600 hover:underline">Full Directory</Link>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Institution</th>
                                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Growth</th>
                                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="tenant in tenants.slice(0, 5)" :key="tenant.id" class="hover:bg-slate-50/50 transition duration-200">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-center overflow-hidden">
                                                    <img v-if="tenant.logo_path" :src="'/storage/' + tenant.logo_path" class="h-full w-full object-contain p-1.5" />
                                                    <Building2 v-else class="h-5 w-5 text-indigo-500" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">{{ tenant.school_name || tenant.id }}</p>
                                                    <p class="text-xs text-slate-400">{{ tenant.domains[0]?.domain }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-slate-900">{{ tenant.insights?.students?.toLocaleString() }} Students</span>
                                                <span class="text-[10px] text-slate-500 uppercase tracking-tight">{{ tenant.insights?.applications }} Applicants</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <Badge :variant="tenant.is_active ? 'default' : 'destructive'" class="text-[9px] uppercase font-bold tracking-widest px-2 py-0.5">
                                                {{ tenant.is_active ? 'Online' : 'Limited' }}
                                            </Badge>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <Link :href="route('central.tenants.show', tenant.id)" class="text-slate-400 hover:text-indigo-600 transition">
                                                <ArrowRight class="h-5 w-5 ml-auto" />
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Activity Feed Section -->
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <Zap class="h-5 w-5 text-amber-500" /> System Pulse
                            </h3>
                            
                            <!-- Combined Activity Feed -->
                            <Card class="border-slate-200 shadow-sm overflow-hidden bg-white/50 backdrop-blur-md">
                                <CardContent class="p-0">
                                    <div class="divide-y divide-slate-100">
                                        <!-- Real Payments -->
                                        <div v-for="pay in recentActivity.payments" :key="pay.title + pay.date" class="p-4 hover:bg-slate-50/50 transition group">
                                            <div class="flex gap-4">
                                                <div class="mt-1 bg-emerald-100 text-emerald-700 p-2 rounded-xl group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300">
                                                    <CreditCard class="h-4 w-4" />
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex justify-between items-start">
                                                        <h4 class="text-xs font-bold text-slate-900">{{ pay.title }}</h4>
                                                        <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ pay.date }}</span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1 italic">{{ pay.description }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Real Registrations -->
                                        <div v-for="reg in recentActivity.registrations" :key="reg.title + reg.date" class="p-4 hover:bg-slate-50/50 transition group">
                                            <div class="flex gap-4">
                                                <div class="mt-1 bg-indigo-100 text-indigo-700 p-2 rounded-xl group-hover:bg-indigo-500 group-hover:text-white transition-colors duration-300">
                                                    <Building2 class="h-4 w-4" />
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex justify-between items-start">
                                                        <h4 class="text-xs font-bold text-slate-900">{{ reg.title }}</h4>
                                                        <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ reg.date }}</span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ reg.description }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="recentActivity.registrations.length === 0 && recentActivity.payments.length === 0" class="p-8 text-center bg-slate-50/30">
                                        <p class="text-sm text-slate-400 italic">No recent activity detected.</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- System Overview Mini Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100">
                                <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">Total Depts</p>
                                <p class="text-xl font-extrabold text-indigo-900 mt-1">{{ platformInsights.total_departments }}</p>
                            </div>
                            <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-200">
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Courses</p>
                                <p class="text-xl font-extrabold text-slate-900 mt-1">{{ platformInsights.total_courses }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Bulk Upload Modal -->
        <Dialog :open="isUploadModalOpen" @update:open="isUploadModalOpen = false">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Global Academic Upload</DialogTitle>
                    <DialogDescription>
                        Select a polytechnic and upload a CSV to provision its academic hierarchy.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="grid gap-4 py-4">
                    <div class="space-y-2">
                        <Label>Target Polytechnic</Label>
                        <select v-model="uploadForm.tenant_id" class="w-full border-slate-200 rounded-md text-sm px-3 py-2">
                            <option value="">Select a School...</option>
                            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                {{ tenant.school_name || tenant.id }}
                            </option>
                        </select>
                        <span v-if="uploadForm.errors.tenant_id" class="text-xs text-red-500">{{ uploadForm.errors.tenant_id }}</span>
                    </div>

                    <div class="bg-slate-50 border rounded-lg p-4">
                        <h4 class="text-sm font-bold text-slate-800 mb-2 font-sans">Required CSV Format:</h4>
                        <div class="text-[10px] font-mono text-slate-600 bg-white p-2 border rounded overflow-x-auto leading-relaxed">
                            faculty_name,faculty_code,department_name,department_code,programme_name,award,duration<br/>
                            Science,SCI,Computer Science,CSC,Software Engineering,ND,2
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Select File (CSV/XLSX)</Label>
                        <Input type="file" @input="uploadForm.file = ($event.target as HTMLInputElement).files?.[0] || null" accept=".csv,.xlsx,.xls" />
                        <span v-if="uploadForm.errors.file" class="text-xs text-red-500 font-sans tracking-tight">{{ uploadForm.errors.file }}</span>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isUploadModalOpen = false">Cancel</Button>
                    <Button :disabled="uploadForm.processing" @click="submitUpload" class="bg-indigo-600 hover:bg-indigo-700">
                        {{ uploadForm.processing ? 'Uploading...' : 'Start Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </CentralLayout>
</template>
