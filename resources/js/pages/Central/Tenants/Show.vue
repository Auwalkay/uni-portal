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
    ArrowLeft,
    ExternalLink,
    Power,
    ShieldAlert,
    Calendar,
    CreditCard,
    CheckCircle2,
    AlertCircle,
    PlusCircle,
    Pencil,
    Upload
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { MoreVertical } from 'lucide-vue-next';

const props = defineProps<{
    tenant: any;
    insights: {
        students_count: number;
        staff_count: number;
        faculties_count: number;
        departments_count: number;
        courses_count: number;
        applications_count: number;
    };
    recentData: {
        students: any;
        applicants: any[];
        faculties: any[];
        departments: any[];
        programmes: any[];
        school_fees: any[];
    };
}>();

const showEditModal = ref(false);
const showFacultyModal = ref(false);
const showUploadModal = ref(false);

const uploadForm = useForm({
    file: null as File | null,
});

const editForm = useForm({
    school_name: props.tenant.school_name || '',
    email: props.tenant.email || '',
    address: props.tenant.address || '',
    logo: null as any,
    _method: 'PUT'
});

const facultyShortcutForm = useForm({
    name: '',
    code: ''
});

const submitEdit = () => {
    editForm.post(route('central.tenants.update', props.tenant.id), {
        onSuccess: () => {
            showEditModal.value = false;
            Swal.fire({
                title: 'Updated!',
                text: 'Polytechnic details have been updated.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const submitFacultyShortcut = () => {
    facultyShortcutForm.post(route('central.tenants.faculties.store', props.tenant.id), {
        onSuccess: () => {
            showFacultyModal.value = false;
            facultyShortcutForm.reset();
            Swal.fire({
                title: 'Added!',
                text: 'New faculty has been created.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const submitUpload = () => {
    uploadForm.post(route('central.tenants.academics.upload', props.tenant.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
            Swal.fire({
                title: 'Imported!',
                text: 'The academic structure has been successfully imported.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
        onError: (errors) => {
            Swal.fire({
                title: 'Upload Failed',
                text: errors.file || 'Failed to import academic structure.',
                icon: 'error',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const form = useForm({
    amount: '',
    start_date: new Date().toISOString().split('T')[0],
    payment_reference: '',
    payment_method: 'Bank Transfer',
    notes: ''
});

const submitSubscription = () => {
    form.post(route('central.tenants.subscriptions.store', props.tenant.id), {
        onSuccess: () => {
            form.reset();
            Swal.fire({
                title: 'Recorded!',
                text: 'The subscription has been successfully recorded.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount);
};

const toggleTenantStatus = () => {
    Swal.fire({
        title: props.tenant.is_active ? 'Suspend Polytechnic?' : 'Activate Polytechnic?',
        text: props.tenant.is_active 
            ? "This will immediately block all staff and students from logging in or accessing their dashboard." 
            : "This will restore full access to the portal for all users.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: props.tenant.is_active ? '#dc2626' : '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: props.tenant.is_active ? 'Yes, Suspend it' : 'Yes, Activate it'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('central.tenants.toggle-status', props.tenant.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: `The polytechnic has been ${props.tenant.is_active ? 'activated' : 'suspended'}.`,
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        }
    });
};

const getActiveSubscription = () => {
    if (!props.tenant.subscriptions || props.tenant.subscriptions.length === 0) return null;
    return props.tenant.subscriptions.find((s: any) => s.status === 'active') || props.tenant.subscriptions[0];
};

const activeSub = getActiveSubscription();
</script>

<template>
    <Head :title="`${tenant.id} Monitoring`" />

    <CentralLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('central.tenants.index')" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <ArrowLeft class="w-5 h-5 text-slate-600" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-2xl text-slate-900 leading-tight tracking-tight">
                            {{ tenant.school_name || tenant.id }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="tenant.is_active ? 'default' : 'destructive'" class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5">
                                {{ tenant.is_active ? 'Active' : 'Suspended' }}
                            </Badge>
                            <span class="text-slate-400 text-sm flex items-center gap-1">
                                <Database class="w-3 h-3" /> {{ tenant.tenancy_db_name || 'tenant_' + tenant.id }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <Button @click="showUploadModal = true" variant="outline" class="border-emerald-200 text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800 shadow-sm transition-all group h-9 px-3">
                        <Upload class="w-4 h-4 mr-2 group-hover:-translate-y-0.5 transition-transform" />
                        Bulk Upload
                    </Button>
                    <Link :href="route('central.tenants.academics', tenant.id)">
                        <Button variant="outline" class="border-indigo-200 text-indigo-700 hover:bg-indigo-50 hover:text-indigo-800 shadow-sm transition-all h-9 px-3">
                            <BookOpen class="w-4 h-4 mr-2" />
                            Manage Academics
                        </Button>
                    </Link>
                    <Button @click="showEditModal = true" variant="outline" class="border-slate-200 text-slate-700 hover:bg-slate-50 shadow-sm transition-all h-9 px-3">
                        <Pencil class="w-4 h-4 mr-2 text-slate-500" />
                        Edit Details
                    </Button>
                    <Button @click="showFacultyModal = true" variant="outline" class="border-slate-200 text-slate-700 hover:bg-slate-50 shadow-sm transition-all h-9 px-3">
                        <PlusCircle class="w-4 h-4 mr-2 text-slate-500" />
                        Add Faculty
                    </Button>
                    <Button @click="toggleTenantStatus" :variant="tenant.is_active ? 'destructive' : 'default'" class="shadow-sm transition-all h-9 px-3" :class="!tenant.is_active ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'">
                        <Power class="w-4 h-4 mr-2 text-white" />
                        {{ tenant.is_active ? 'Suspend Access' : 'Activate Access' }}
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Institutional Identity Header -->
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-slate-50 rounded-bl-full -z-10 opacity-50"></div>
                    
                    <div class="flex flex-col md:flex-row gap-8 items-start md:items-center">
                        <div class="h-24 w-24 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center overflow-hidden shrink-0 filter drop-shadow-sm">
                            <img v-if="tenant.logo_path" :src="'/storage/' + tenant.logo_path" class="h-full w-full object-contain p-2" />
                            <Building2 v-else class="h-12 w-12 text-slate-300" />
                        </div>
                        
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ tenant.school_name || tenant.id }}</h3>
                            </div>
                            
                            <div class="flex flex-wrap gap-2">
                                <a v-for="domain in tenant.domains" :key="domain.id" 
                                   :href="'http://' + domain.domain + ':8000'" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg transition-colors border border-indigo-100/50 group">
                                    <ExternalLink class="h-3.5 w-3.5 text-indigo-400 group-hover:text-indigo-600 transition-colors" />
                                    {{ domain.domain }}
                                </a>
                                <span v-if="!tenant.domains.length" class="text-sm text-slate-500 italic">No domains configured</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabbed Dashboard -->
                <Tabs defaultValue="overview" class="w-full">
                    <TabsList class="mb-6 bg-slate-100/80 p-1.5 rounded-xl border border-slate-200/60 inline-flex shadow-inner">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="students">Recent Students</TabsTrigger>
                        <TabsTrigger value="applicants">Recent Applicants</TabsTrigger>
                        <TabsTrigger value="faculties">Faculties</TabsTrigger>
                        <TabsTrigger value="departments">Departments</TabsTrigger>
                        <TabsTrigger value="programmes">Programmes</TabsTrigger>
                        <TabsTrigger value="fees">Fee Setup</TabsTrigger>
                        <TabsTrigger value="subscriptions">Subscriptions</TabsTrigger>
                    </TabsList>

                    <!-- Tab 1: Overview -->
                    <TabsContent value="overview" class="space-y-6">
                        <!-- Live Metrics -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                            
                            <Card class="bg-white border-slate-200/60 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-400 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-semibold text-slate-600">Total Students</CardTitle>
                                    <div class="h-8 w-8 rounded-full bg-blue-50 flex items-center justify-center">
                                        <Users class="h-4 w-4 text-blue-600" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ insights.students_count }}</div>
                                    <p class="text-xs text-slate-500 mt-1">Enrolled across all programs</p>
                                </CardContent>
                            </Card>

                            <Card class="bg-white border-slate-200/60 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-400 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-semibold text-slate-600">Admissions</CardTitle>
                                    <div class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center">
                                        <FileText class="h-4 w-4 text-indigo-600" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ insights.applications_count }}</div>
                                    <p class="text-xs text-slate-500 mt-1">Total applications received</p>
                                </CardContent>
                            </Card>

                            <Card class="bg-white border-slate-200/60 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-semibold text-slate-600">Total Staff</CardTitle>
                                    <div class="h-8 w-8 rounded-full bg-emerald-50 flex items-center justify-center">
                                        <Briefcase class="h-4 w-4 text-emerald-600" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ insights.staff_count }}</div>
                                    <p class="text-xs text-slate-500 mt-1">Academic and non-academic</p>
                                </CardContent>
                            </Card>

                            <Card class="bg-white border-slate-200/60 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-400 to-amber-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-semibold text-slate-600">Faculties</CardTitle>
                                    <div class="h-8 w-8 rounded-full bg-amber-50 flex items-center justify-center">
                                        <Building2 class="h-4 w-4 text-amber-600" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ insights.faculties_count }}</div>
                                    <p class="text-xs text-slate-500 mt-1">Active institutional faculties</p>
                                </CardContent>
                            </Card>

                            <Card class="bg-white border-slate-200/60 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 to-cyan-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-semibold text-slate-600">Departments</CardTitle>
                                    <div class="h-8 w-8 rounded-full bg-cyan-50 flex items-center justify-center">
                                        <GraduationCap class="h-4 w-4 text-cyan-600" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ insights.departments_count }}</div>
                                    <p class="text-xs text-slate-500 mt-1">Departments across faculties</p>
                                </CardContent>
                            </Card>

                            <Card class="bg-white border-slate-200/60 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-violet-400 to-violet-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-semibold text-slate-600">Listed Courses</CardTitle>
                                    <div class="h-8 w-8 rounded-full bg-violet-50 flex items-center justify-center">
                                        <BookOpen class="h-4 w-4 text-violet-600" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ insights.courses_count }}</div>
                                    <p class="text-xs text-slate-500 mt-1">Total approved curriculum</p>
                                </CardContent>
                            </Card>

                            <!-- Subscription Status Card -->
                            <Card class="col-span-1 sm:col-span-2 relative overflow-hidden" :class="activeSub?.status === 'active' ? 'bg-gradient-to-br from-green-50 to-emerald-50 border-green-200' : 'bg-gradient-to-br from-amber-50 to-orange-50 border-amber-200'">
                                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                                    <CardTitle class="text-sm font-bold tracking-wide uppercase" :class="activeSub?.status === 'active' ? 'text-green-800' : 'text-amber-800'">Central Subscription</CardTitle>
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center" :class="activeSub?.status === 'active' ? 'bg-green-100' : 'bg-amber-100'">
                                        <CreditCard class="w-4 h-4" :class="activeSub?.status === 'active' ? 'text-green-700' : 'text-amber-700'" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex items-end gap-3">
                                        <div class="text-4xl font-black tracking-tight" :class="activeSub?.status === 'active' ? 'text-green-700' : 'text-amber-700'">
                                            {{ activeSub ? activeSub.status.toUpperCase() : 'NONE' }}
                                        </div>
                                    </div>
                                    <p class="text-sm font-medium mt-2" :class="activeSub?.status === 'active' ? 'text-green-600' : 'text-amber-600'">
                                        {{ activeSub ? 'Coverage Ends: ' + formatDate(activeSub.end_date) : 'No subscription payments recorded.' }}
                                    </p>
                                </CardContent>
                            </Card>

                        </div>
                    </TabsContent>

                    <!-- Tab 2: Students -->
                    <TabsContent value="students">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-white">
                                <h4 class="font-bold text-lg text-slate-900">Recent Student Enrollments</h4>
                                <p class="text-sm text-slate-500">Latest students admitted into the polytechnic.</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-y border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Matric Number</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Student Profile</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Academic Placement</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Programme</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="student in recentData.students.data" :key="student.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-700">{{ student.matriculation_number || 'Pending' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-900">{{ student.user?.name }}</div>
                                                <div class="text-slate-500 mt-0.5">{{ student.user?.email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-slate-800">{{ student.department?.faculty?.name || 'N/A' }}</div>
                                                <div class="text-slate-500 mt-0.5">{{ student.department?.name || 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-700">
                                                {{ student.program?.name || 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Badge :variant="student.status === 'active' ? 'default' : 'secondary'" class="bg-opacity-20 text-xs">
                                                    {{ student.status }}
                                                </Badge>
                                            </td>
                                        </tr>
                                        <tr v-if="recentData.students.data.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">No students found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab 3: Applicants -->
                    <TabsContent value="applicants">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-white">
                                <h4 class="font-bold text-lg text-slate-900">Recent Applications</h4>
                                <p class="text-sm text-slate-500">Latest prospective students seeking admission.</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-y border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider">App ID</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Applicant Identity</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Contact</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="applicant in recentData.applicants" :key="applicant.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap font-mono font-medium text-indigo-600">{{ applicant.application_number }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-900">{{ applicant.user?.name }}</div>
                                                <div class="text-slate-500 mt-0.5">{{ applicant.user?.email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-600">{{ applicant.phone_number }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Badge variant="outline" class="text-xs">{{ applicant.admission_status }}</Badge>
                                            </td>
                                        </tr>
                                        <tr v-if="recentData.applicants.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">No applicants found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab 4: Faculties -->
                    <TabsContent value="faculties">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-white flex justify-between items-center bg-slate-50/50">
                                <div>
                                    <h4 class="font-bold text-lg text-slate-900">Assigned Faculties</h4>
                                    <p class="text-sm text-slate-500">Top-level academic divisions.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-y border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider w-32">Code</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Faculty Name</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="faculty in recentData.faculties" :key="faculty.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap font-mono font-medium text-slate-500">{{ faculty.code }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-800">{{ faculty.name }}</td>
                                        </tr>
                                        <tr v-if="recentData.faculties.length === 0">
                                            <td colspan="2" class="px-6 py-12 text-center text-slate-500">No faculties defined yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab 5: Departments -->
                    <TabsContent value="departments">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-white flex justify-between items-center bg-slate-50/50">
                                <div>
                                    <h4 class="font-bold text-lg text-slate-900">Academic Departments</h4>
                                    <p class="text-sm text-slate-500">Sub-divisions under faculties.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-y border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider w-32">Code</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Department Name</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Parent Faculty</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="dept in recentData.departments" :key="dept.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap font-mono font-medium text-slate-500">{{ dept.code }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-800">{{ dept.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-600 flex items-center gap-2">
                                                <Building2 class="w-3 h-3 text-slate-400" />
                                                {{ dept.faculty?.name || 'Unknown' }}
                                            </td>
                                        </tr>
                                        <tr v-if="recentData.departments.length === 0">
                                            <td colspan="3" class="px-6 py-12 text-center text-slate-500">No departments defined yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab 6: Programmes -->
                    <TabsContent value="programmes">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-white flex justify-between items-center bg-slate-50/50">
                                <div>
                                    <h4 class="font-bold text-lg text-slate-900">Study Programmes</h4>
                                    <p class="text-sm text-slate-500">Certifications offered by departments (ND, HND).</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-y border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Programme Overview</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Parent Department</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Award</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="prog in recentData.programmes" :key="prog.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-800">{{ prog.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-600 flex items-center gap-2">
                                                <GraduationCap class="w-3 h-3 text-slate-400" />
                                                {{ prog.department?.name || 'Unknown' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Badge variant="outline" class="bg-slate-50 text-slate-700 tracking-wide font-bold">{{ prog.award }}</Badge>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-600 font-medium">{{ prog.duration }} Years</td>
                                        </tr>
                                        <tr v-if="recentData.programmes.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">No programmes structured yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab 7: School Fees -->
                    <TabsContent value="fees">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-white flex justify-between items-center bg-slate-50/50">
                                <div>
                                    <h4 class="font-bold text-lg text-slate-900">Fee Configuration</h4>
                                    <p class="text-sm text-slate-500">Recent fee structures defined by this polytechnic.</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-y border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Fee Description</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Academic Session</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Amount</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="fee in recentData.school_fees" :key="fee.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-semibold text-slate-900">{{ fee.fee_type?.name }}</div>
                                                <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                                    <Building2 class="w-3 h-3" /> {{ fee.faculty?.name || 'All Faculties' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-600">{{ fee.session?.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-bold text-emerald-600 text-base tracking-tight">
                                                ₦{{ Number(fee.amount).toLocaleString() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Badge :variant="fee.is_compulsory ? 'default' : 'secondary'" class="text-xs">{{ fee.is_compulsory ? 'Compulsory' : 'Optional' }}</Badge>
                                            </td>
                                        </tr>
                                        <tr v-if="recentData.school_fees.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">No fees configured for this tenant.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- Tab 8: Subscriptions -->
                    <TabsContent value="subscriptions" class="space-y-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Subscription History</h3>
                                <p class="text-sm text-slate-500">Log of platform access payments.</p>
                            </div>
                            
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button class="bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all group">
                                        <CreditCard class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                                        Record Payment
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[425px] overflow-hidden p-0 border-0 rounded-2xl">
                                    <div class="h-2 bg-indigo-600 w-full"></div>
                                    <div class="p-6">
                                        <DialogHeader class="mb-4 text-left">
                                            <DialogTitle class="text-xl font-bold text-slate-900">Record Subscription</DialogTitle>
                                            <DialogDescription class="text-slate-500 text-sm mt-1.5">
                                                Enter payment details to extend platform access by 1 year.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div class="grid gap-4 py-2">
                                            <div class="space-y-2">
                                                <Label for="amount" class="text-sm font-semibold text-slate-700">Amount Paid (₦)</Label>
                                                <Input id="amount" v-model="form.amount" type="number" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg h-10" placeholder="e.g. 500000" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="start_date" class="text-sm font-semibold text-slate-700">Coverage Start Date</Label>
                                                <Input id="start_date" v-model="form.start_date" type="date" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg h-10" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="ref" class="text-sm font-semibold text-slate-700">Payment Reference</Label>
                                                <Input id="ref" v-model="form.payment_reference" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg h-10" placeholder="e.g. TXN-123456" />
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-2">
                                                    <Label for="method" class="text-sm font-semibold text-slate-700">Method</Label>
                                                    <Input id="method" v-model="form.payment_method" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg h-10" placeholder="Bank Transfer" />
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="notes" class="text-sm font-semibold text-slate-700">Additional Notes</Label>
                                                <Textarea id="notes" v-model="form.notes" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg min-h-[80px] resize-none" placeholder="Any relevant details..." />
                                            </div>
                                        </div>
                                    </div>
                                    <DialogFooter class="bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-end gap-2">
                                        <Button type="button" :disabled="form.processing" @click="submitSubscription" class="bg-indigo-600 hover:bg-indigo-700 text-white w-full sm:w-auto font-medium shadow-sm transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-lg">
                                            {{ form.processing ? 'Saving...' : 'Save Subscription' }}
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mt-6">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left align-middle border-collapse">
                                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 border-b border-slate-100">
                                        <tr>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Start Date</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">End Date</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Amount Paid</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Reference</th>
                                            <th class="px-6 py-4 font-semibold tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr v-for="sub in tenant.subscriptions" :key="sub.id" class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(sub.start_date) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(sub.end_date) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-900">{{ formatCurrency(sub.amount) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-500">{{ sub.payment_reference || 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Badge :variant="sub.status === 'active' ? 'default' : 'secondary'" class="bg-opacity-20 text-xs">
                                                    {{ sub.status.toUpperCase() }}
                                                </Badge>
                                            </td>
                                        </tr>
                                        <tr v-if="!tenant.subscriptions || tenant.subscriptions.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">No subscription history found for this polytechnic.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>

            </div>
        </div>

        <!-- Edit Tenant Modal -->
        <Dialog :open="showEditModal" @update:open="showEditModal = false">
            <DialogContent class="sm:max-w-[550px] overflow-hidden p-0 border-0 rounded-2xl">
                <div class="h-2 bg-slate-800 w-full"></div>
                <div class="p-6">
                    <DialogHeader class="mb-4 text-left">
                        <DialogTitle class="text-xl font-bold text-slate-900">Edit Polytechnic Details</DialogTitle>
                        <DialogDescription class="text-slate-500 text-sm mt-1.5">
                            Update core administrative information for {{ tenant.school_name || 'this institution' }}.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-5 py-2">
                        <div class="space-y-2">
                            <Label for="school_name" class="text-sm font-semibold text-slate-700">Institution Name</Label>
                            <Input id="school_name" v-model="editForm.school_name" class="border-slate-200 focus:border-slate-800 focus:ring-slate-800 rounded-lg h-10" placeholder="Full name of polytechnic" />
                            <span v-if="editForm.errors.school_name" class="text-xs text-red-500">{{ editForm.errors.school_name }}</span>
                        </div>
                        <div class="space-y-2">
                            <Label for="email" class="text-sm font-semibold text-slate-700">Official Contact Email</Label>
                            <Input id="email" type="email" v-model="editForm.email" class="border-slate-200 focus:border-slate-800 focus:ring-slate-800 rounded-lg h-10" placeholder="admin@poly.edu.ng" />
                            <span v-if="editForm.errors.email" class="text-xs text-red-500">{{ editForm.errors.email }}</span>
                        </div>
                        <div class="space-y-2">
                            <Label for="address" class="text-sm font-semibold text-slate-700">Physical Address</Label>
                            <Textarea id="address" v-model="editForm.address" class="border-slate-200 focus:border-slate-800 focus:ring-slate-800 rounded-lg min-h-[80px] resize-none" placeholder="Main campus address..." />
                            <span v-if="editForm.errors.address" class="text-xs text-red-500">{{ editForm.errors.address }}</span>
                        </div>
                        <div class="space-y-2">
                            <Label for="logo" class="text-sm font-semibold text-slate-700">Institutional Crest / Logo</Label>
                            <Input id="logo" type="file" @input="editForm.logo = ($event.target as HTMLInputElement).files?.[0] || null" class="border-slate-200 focus:border-slate-800 focus:ring-slate-800 rounded-lg file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer" accept="image/*" />
                            <p class="text-xs text-slate-400 mt-1">Recommended format: Square PNG with transparent background.</p>
                            <span v-if="editForm.errors.logo" class="text-xs text-red-500">{{ editForm.errors.logo }}</span>
                        </div>
                    </div>
                </div>
                <DialogFooter class="bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <Button variant="outline" type="button" @click="showEditModal = false" class="border-slate-200 text-slate-600 hover:bg-slate-100 font-medium rounded-lg">Cancel</Button>
                    <Button :disabled="editForm.processing" @click="submitEdit" class="bg-slate-900 hover:bg-slate-800 text-white font-medium shadow-sm transition-all focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 rounded-lg">
                        {{ editForm.processing ? 'Saving Changes...' : 'Save Details' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Faculty Modal -->
        <Dialog :open="showFacultyModal" @update:open="showFacultyModal = false">
            <DialogContent class="sm:max-w-[425px] overflow-hidden p-0 border-0 rounded-2xl">
                <div class="h-2 bg-indigo-600 w-full"></div>
                <div class="p-6">
                    <DialogHeader class="mb-4 text-left">
                        <DialogTitle class="text-xl font-bold text-slate-900">Add Academic Faculty</DialogTitle>
                        <DialogDescription class="text-slate-500 text-sm mt-1.5">
                            Quickly provision a new top-level faculty for this institution.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-5 py-2">
                        <div class="space-y-2">
                            <Label for="fac_name" class="text-sm font-semibold text-slate-700">Faculty Title</Label>
                            <Input id="fac_name" v-model="facultyShortcutForm.name" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg h-10" placeholder="e.g. Faculty of Environmental Design" />
                            <span v-if="facultyShortcutForm.errors.name" class="text-xs text-red-500">{{ facultyShortcutForm.errors.name }}</span>
                        </div>
                        <div class="space-y-2">
                            <Label for="fac_code" class="text-sm font-semibold text-slate-700">Short Code</Label>
                            <Input id="fac_code" v-model="facultyShortcutForm.code" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg h-10" placeholder="e.g. FED" />
                            <span v-if="facultyShortcutForm.errors.code" class="text-xs text-red-500">{{ facultyShortcutForm.errors.code }}</span>
                        </div>
                    </div>
                </div>
                <DialogFooter class="bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <Button variant="outline" type="button" @click="showFacultyModal = false" class="border-slate-200 text-slate-600 hover:bg-slate-100 font-medium rounded-lg">Cancel</Button>
                    <Button :disabled="facultyShortcutForm.processing" @click="submitFacultyShortcut" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-lg">
                        {{ facultyShortcutForm.processing ? 'Creating...' : 'Create Faculty' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Bulk Upload Modal -->
        <Dialog :open="showUploadModal" @update:open="showUploadModal = false">
            <DialogContent class="sm:max-w-[500px] overflow-hidden p-0 border-0 rounded-2xl">
                <div class="h-2 bg-emerald-500 w-full"></div>
                <div class="p-6">
                    <DialogHeader class="mb-4 text-left">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-8 w-8 rounded-full bg-emerald-50 flex items-center justify-center">
                                <Upload class="h-4 w-4 text-emerald-600" />
                            </div>
                            <DialogTitle class="text-xl font-bold text-slate-900">Bulk Academic Upload</DialogTitle>
                        </div>
                        <DialogDescription class="text-slate-500 text-sm">
                            Instantly provision entire academic hierarchies (Faculties -> Departments -> Programmes) via Excel or CSV.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="space-y-5 py-2">
                        <div class="bg-slate-50/80 border border-slate-200/60 rounded-xl p-4">
                            <h4 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-1.5">
                                <FileText class="w-4 h-4 text-slate-400" />
                                Required Column Headers:
                            </h4>
                            <div class="text-[11px] font-mono text-slate-600 bg-white p-3 border border-slate-200 rounded-lg overflow-x-auto leading-relaxed shadow-sm whitespace-nowrap">
                                faculty_name, faculty_code, department_name, department_code, programme_name, award, duration
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Label class="text-sm font-semibold text-slate-700">Select Data File</Label>
                            <label for="bulk-upload-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 hover:border-emerald-400 transition-colors group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <Upload class="w-8 h-8 mb-3 text-slate-400 group-hover:text-emerald-500 transition-colors" />
                                    <p class="mb-1 text-sm text-slate-600 font-medium">
                                        <span class="text-emerald-600 font-bold group-hover:underline">Click to browse</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-slate-500">CSV, XLS, or XLSX files only</p>
                                </div>
                                <Input id="bulk-upload-file" type="file" class="hidden" @input="uploadForm.file = ($event.target as HTMLInputElement).files?.[0] || null" accept=".csv,.xlsx,.xls" />
                            </label>
                            
                            <div v-if="uploadForm.file" class="flex items-center gap-2 mt-2 p-2 bg-emerald-50 border border-emerald-100 rounded-lg">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                <span class="text-sm font-medium text-emerald-800 truncate">{{ uploadForm.file.name }}</span>
                            </div>

                            <span v-if="uploadForm.errors.file" class="text-xs text-red-500 font-medium block mt-1">{{ uploadForm.errors.file }}</span>
                        </div>
                    </div>
                </div>

                <DialogFooter class="bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <Button variant="outline" type="button" @click="showUploadModal = false" class="border-slate-200 text-slate-600 hover:bg-slate-100 font-medium rounded-lg">Cancel</Button>
                    <Button :disabled="uploadForm.processing || !uploadForm.file" @click="submitUpload" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium shadow-sm transition-all focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 rounded-lg">
                        <Upload class="w-4 h-4 mr-2" v-if="!uploadForm.processing" />
                        {{ uploadForm.processing ? 'Processing Import...' : 'Start Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </CentralLayout>
</template>

