<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Award, 
    Plus, 
    Edit, 
    Trash2, 
    CheckCircle2, 
    XCircle, 
    Users, 
    ShieldCheck, 
    Building2,
    Search,
    UserCheck,
    GraduationCap,
    BarChart3,
    LayoutGrid,
    List
} from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';
import { route } from 'ziggy-js';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

interface ScholarshipItem {
    id: string;
    name: string;
    type: string;
    percentage: number | string;
    amount: number | string;
    covers_admin_charges: boolean;
    covers_hostel_fees: boolean;
    is_active: boolean;
    students_count: number;
    applicants_count: number;
}

const props = defineProps<{
    scholarships: Array<ScholarshipItem>;
    summaryStats: {
        total_schemes: number;
        active_schemes: number;
        inactive_schemes: number;
        total_students: number;
        total_applicants: number;
        total_beneficiaries: number;
    };
}>();

const searchQuery = ref('');
const statusFilter = ref('ALL');
const currentView = ref<'grid' | 'table'>('table');

const filteredScholarships = computed(() => {
    return props.scholarships.filter(s => {
        const matchesSearch = s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              s.type.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'ALL' || 
                             (statusFilter.value === 'active' && s.is_active) || 
                             (statusFilter.value === 'inactive' && !s.is_active);
        return matchesSearch && matchesStatus;
    });
});

const formatDiscount = (scholarship: ScholarshipItem) => {
    if (scholarship.type === 'fixed') {
        return '₦' + Number(scholarship.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return Number(scholarship.percentage).toString() + '%';
};

// Create Modal State
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    type: 'percentage',
    percentage: '',
    amount: '',
    covers_admin_charges: false,
    covers_hostel_fees: false,
    is_active: true,
});

const submitCreate = () => {
    createForm.post(route('admin.scholarships.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State
const showEditModal = ref(false);
const editForm = useForm({
    id: '',
    name: '',
    type: 'percentage',
    percentage: '',
    amount: '',
    covers_admin_charges: false,
    covers_hostel_fees: false,
    is_active: true,
    students_count: 0,
    applicants_count: 0,
});

const openEditModal = (scholarship: ScholarshipItem) => {
    editForm.id = scholarship.id;
    editForm.name = scholarship.name;
    editForm.type = scholarship.type || 'percentage';
    editForm.percentage = String(scholarship.percentage || '');
    editForm.amount = String(scholarship.amount || '');
    editForm.covers_admin_charges = !!scholarship.covers_admin_charges;
    editForm.covers_hostel_fees = !!scholarship.covers_hostel_fees;
    editForm.is_active = !!scholarship.is_active;
    editForm.students_count = scholarship.students_count || 0;
    editForm.applicants_count = scholarship.applicants_count || 0;
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('admin.scholarships.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Delete
const deleteScholarship = (id: string, name: string) => {
    if (confirm(`Are you sure you want to delete the "${name}" scholarship? This action cannot be undone.`)) {
        router.delete(route('admin.scholarships.destroy', id));
    }
};
</script>

<template>
    <Head title="Scholarships & Student Headcount Analysis" />

    <AdminLayout>
        <div class="py-12 px-8 space-y-10 w-full max-w-[1600px] mx-auto animate-in fade-in duration-700">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-end gap-6 border-b border-slate-100 pb-8">
                <div class="space-y-2">
                    <div class="flex items-center gap-3 text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">
                        <span class="text-slate-900">Academic</span>
                        <span>/</span>
                        <span>Financial Aid</span>
                        <span>/</span>
                        <span>Scholarship Analysis</span>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900 flex items-center gap-3">
                        <Award class="w-9 h-9 text-purple-600" /> Scholarships & Enrolment Analysis
                    </h1>
                    <p class="text-slate-500 font-medium tracking-tight">
                        Analyze student enrolment distribution and applicant allocation across all scholarship categories.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button @click="showCreateModal = true" class="h-11 px-6 rounded-xl font-bold bg-purple-600 hover:bg-purple-700 shadow-md shadow-purple-600/20 text-white">
                        <Plus class="w-4 h-4 mr-2" /> Add Scholarship Scheme
                    </Button>
                </div>
            </div>

            <!-- Smart Summary KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1: Total Enrolled Students on Scholarship -->
                <Card class="border-none shadow-xl shadow-purple-950/10 bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 text-white overflow-hidden relative group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <GraduationCap class="w-28 h-28 text-purple-300" />
                    </div>
                    <CardContent class="p-6 space-y-3">
                        <div class="flex justify-between items-start">
                            <p class="text-purple-300 font-bold text-[10px] uppercase tracking-widest flex items-center gap-1.5">
                                <GraduationCap class="w-3.5 h-3.5 text-purple-400" /> Enrolled Student Scholars
                            </p>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-purple-500/30 text-purple-200 border border-purple-400/30">
                                Active Students
                            </span>
                        </div>
                        <h2 class="text-4xl font-black text-white">
                            {{ summaryStats.total_students }}
                        </h2>
                        <p class="text-xs text-slate-300 font-medium tracking-tight">
                            Students actively benefiting from scholarships
                        </p>
                    </CardContent>
                </Card>

                <!-- Card 2: Applicants on Scholarship -->
                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-3">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Applicant Scholars</p>
                            <div class="p-2 bg-blue-50 rounded-xl"><UserCheck class="w-4 h-4 text-blue-600" /></div>
                        </div>
                        <h2 class="text-4xl font-black text-slate-900">{{ summaryStats.total_applicants }}</h2>
                        <p class="text-xs text-slate-500 font-bold">Prospective students assigned scholarship aid</p>
                    </CardContent>
                </Card>

                <!-- Card 3: Total Combined Beneficiaries -->
                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-3">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Total Beneficiaries</p>
                            <div class="p-2 bg-indigo-50 rounded-xl"><Users class="w-4 h-4 text-indigo-600" /></div>
                        </div>
                        <h2 class="text-4xl font-black text-slate-900">{{ summaryStats.total_beneficiaries }}</h2>
                        <p class="text-xs text-indigo-600 font-bold">Combined Students & Applicants</p>
                    </CardContent>
                </Card>

                <!-- Card 4: Scholarship Schemes Active -->
                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-3">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Scholarship Schemes</p>
                            <div class="p-2 bg-purple-50 rounded-xl"><Award class="w-4 h-4 text-purple-600" /></div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h2 class="text-4xl font-black text-slate-900">{{ summaryStats.total_schemes }}</h2>
                            <span class="text-xs font-bold text-slate-400">Total Schemes</span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-black uppercase">
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-md">{{ summaryStats.active_schemes }} Active</span>
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md">{{ summaryStats.inactive_schemes }} Inactive</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- View Mode Switcher & Filter Toolbar -->
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="relative flex-1 min-w-[260px] max-w-md">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <Input 
                            v-model="searchQuery" 
                            placeholder="Search by scholarship name or discount type..." 
                            class="pl-10 h-11 border-none bg-white rounded-xl shadow-sm text-xs font-bold text-slate-800"
                        />
                    </div>

                    <select v-model="statusFilter" class="h-11 px-4 border-none bg-white rounded-xl shadow-sm text-xs font-bold text-slate-600 cursor-pointer">
                        <option value="ALL">All Statuses</option>
                        <option value="active">Active Schemes Only</option>
                        <option value="inactive">Inactive Schemes Only</option>
                    </select>

                    <div class="text-xs font-bold text-slate-400 px-2">
                        Showing {{ filteredScholarships.length }} of {{ scholarships.length }} schemes
                    </div>
                </div>

                <!-- View Switch Buttons -->
                <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl shadow-sm border border-slate-200/80 self-start md:self-auto">
                    <button 
                        @click="currentView = 'table'" 
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-black transition-all',
                            currentView === 'table' 
                                ? 'bg-purple-600 text-white shadow-sm shadow-purple-600/20' 
                                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                        ]"
                    >
                        <List class="w-4 h-4" /> Table View
                    </button>

                    <button 
                        @click="currentView = 'grid'" 
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-black transition-all',
                            currentView === 'grid' 
                                ? 'bg-purple-600 text-white shadow-sm shadow-purple-600/20' 
                                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'
                        ]"
                    >
                        <LayoutGrid class="w-4 h-4" /> Card Grid View
                    </button>
                </div>
            </div>

            <!-- VIEW MODE 1: GRID VIEW -->
            <div v-if="currentView === 'grid'" class="space-y-6 animate-in fade-in duration-300">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card 
                        v-for="sch in filteredScholarships" 
                        :key="sch.id" 
                        class="border border-slate-100 shadow-xl shadow-slate-200/40 bg-white hover:border-purple-200 transition-all rounded-3xl overflow-hidden group flex flex-col justify-between"
                    >
                        <CardHeader class="p-6 border-b border-slate-50 bg-slate-50/50 space-y-3">
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <Badge :class="sch.is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'" class="rounded-lg text-[9px] font-black uppercase tracking-wider">
                                        {{ sch.is_active ? 'Active Scheme' : 'Inactive' }}
                                    </Badge>
                                    <h3 class="text-lg font-black text-slate-900 group-hover:text-purple-600 transition-colors">{{ sch.name }}</h3>
                                </div>
                                <span class="px-3 py-1 bg-purple-50 text-purple-700 font-black text-sm rounded-xl border border-purple-100 shadow-sm">
                                    {{ formatDiscount(sch) }}
                                </span>
                            </div>

                            <!-- Coverage Badges -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <span v-if="sch.covers_admin_charges" class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-100 text-[9px] font-black uppercase tracking-tight flex items-center gap-1">
                                    <ShieldCheck class="w-3 h-3 text-blue-600" /> Admin Fee Covered
                                </span>
                                <span v-if="sch.covers_hostel_fees" class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 text-[9px] font-black uppercase tracking-tight flex items-center gap-1">
                                    <Building2 class="w-3 h-3 text-indigo-600" /> Hostel Fee Covered
                                </span>
                                <span v-if="!sch.covers_admin_charges && !sch.covers_hostel_fees" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[9px] font-bold uppercase tracking-tight">
                                    Tuition Only
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent class="p-6 space-y-5 flex-1 flex flex-col justify-between">
                            <!-- Student Headcount Metrics Grid -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-purple-50/60 p-3.5 rounded-2xl border border-purple-100/60 text-center">
                                    <p class="text-[9px] font-black text-purple-600 uppercase tracking-wider">Students</p>
                                    <p class="text-2xl font-black text-purple-700 mt-1">
                                        {{ sch.students_count }}
                                    </p>
                                    <span class="text-[9px] font-bold text-purple-500">Enrolled</span>
                                </div>

                                <div class="bg-blue-50/60 p-3.5 rounded-2xl border border-blue-100/60 text-center">
                                    <p class="text-[9px] font-black text-blue-600 uppercase tracking-wider">Applicants</p>
                                    <p class="text-2xl font-black text-blue-700 mt-1">
                                        {{ sch.applicants_count }}
                                    </p>
                                    <span class="text-[9px] font-bold text-blue-500">Assigned</span>
                                </div>

                                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Total</p>
                                    <p class="text-2xl font-black text-slate-900 mt-1">
                                        {{ sch.students_count + sch.applicants_count }}
                                    </p>
                                    <span class="text-[9px] font-bold text-slate-500">Scholars</span>
                                </div>
                            </div>

                            <!-- Student Share Progress Bar -->
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <div class="flex justify-between items-center text-xs font-bold">
                                    <span class="text-slate-500">Share of Scholarship Students:</span>
                                    <span class="text-purple-600 font-black">
                                        {{ summaryStats.total_students > 0 ? Math.round((sch.students_count / summaryStats.total_students) * 100) : 0 }}%
                                    </span>
                                </div>

                                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden flex">
                                    <div 
                                        class="h-full bg-purple-600 transition-all duration-700" 
                                        :style="{ width: (summaryStats.total_students > 0 ? (sch.students_count / summaryStats.total_students * 100) : 0) + '%' }"
                                    ></div>
                                </div>

                                <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase">
                                    <span>{{ sch.students_count }} out of {{ summaryStats.total_students }} total students</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                                <Button variant="outline" size="sm" class="rounded-xl font-bold text-xs" @click="openEditModal(sch)">
                                    <Edit class="w-3.5 h-3.5 mr-1 text-slate-500" /> Edit Scheme
                                </Button>
                                <Button variant="ghost" size="sm" class="rounded-xl font-bold text-xs text-red-600 hover:text-red-700 hover:bg-red-50" @click="deleteScholarship(sch.id, sch.name)">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="filteredScholarships.length === 0" class="py-20 text-center bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <p class="text-base font-black text-slate-700">No matching scholarship schemes found.</p>
                    <p class="text-xs text-slate-400 font-medium mt-1">Try clearing or adjusting your search term.</p>
                </div>
            </div>

            <!-- VIEW MODE 2: COMPACT TABLE VIEW -->
            <div v-if="currentView === 'table'" class="animate-in fade-in duration-300">
                <Card class="border-none shadow-2xl shadow-slate-200/30 overflow-hidden bg-white rounded-3xl">
                    <CardHeader class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                        <div>
                            <CardTitle class="text-xl font-black text-slate-900">Scholarship Enrolment Table</CardTitle>
                            <CardDescription class="text-xs font-medium text-slate-500">Compact list showing student & applicant distribution for all {{ scholarships.length }} schemes.</CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-slate-50/50">
                                <TableRow class="hover:bg-transparent border-slate-100">
                                    <TableHead class="py-5 px-6 text-xs font-black uppercase text-slate-400 tracking-widest">Scholarship Name</TableHead>
                                    <TableHead class="py-5 text-xs font-black uppercase text-slate-400 tracking-widest">Discount Model</TableHead>
                                    <TableHead class="py-5 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Enrolled Students</TableHead>
                                    <TableHead class="py-5 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Applicants</TableHead>
                                    <TableHead class="py-5 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Total Scholars</TableHead>
                                    <TableHead class="py-5 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Status</TableHead>
                                    <TableHead class="py-5 px-6 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="scholarship in filteredScholarships" :key="scholarship.id" class="border-slate-50 hover:bg-slate-50/50 transition-all">
                                    <TableCell class="py-5 px-6">
                                        <div class="flex flex-col">
                                            <span class="font-black text-slate-900 text-sm uppercase tracking-tight">{{ scholarship.name }}</span>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span v-if="scholarship.covers_admin_charges" class="text-[9px] px-1.5 py-0.2 rounded bg-blue-50 text-blue-700 font-bold uppercase border border-blue-100">Admin Fee</span>
                                                <span v-if="scholarship.covers_hostel_fees" class="text-[9px] px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 font-bold uppercase border border-indigo-100">Hostel Fee</span>
                                                <span v-if="!scholarship.covers_admin_charges && !scholarship.covers_hostel_fees" class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-500 font-bold uppercase">Tuition Only</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-5">
                                        <span class="px-2.5 py-1 rounded-xl bg-purple-50 text-purple-700 font-black text-xs border border-purple-100">
                                            {{ formatDiscount(scholarship) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="py-5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="font-black text-purple-700 text-base">{{ scholarship.students_count }}</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                                {{ summaryStats.total_students > 0 ? Math.round((scholarship.students_count / summaryStats.total_students) * 100) : 0 }}% share
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-5 text-center font-black text-blue-600 text-base">
                                        {{ scholarship.applicants_count }}
                                    </TableCell>
                                    <TableCell class="py-5 text-center font-black text-slate-900 text-base">
                                        {{ scholarship.students_count + scholarship.applicants_count }}
                                    </TableCell>
                                    <TableCell class="py-5 text-center">
                                        <Badge :class="scholarship.is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'" class="rounded-lg px-3 py-1 font-black text-[10px] uppercase">
                                            {{ scholarship.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="py-5 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="ghost" size="icon" @click="openEditModal(scholarship)">
                                                <Edit class="w-4 h-4 text-slate-500 hover:text-purple-600" />
                                            </Button>
                                            <Button variant="ghost" size="icon" @click="deleteScholarship(scholarship.id, scholarship.name)">
                                                <Trash2 class="w-4 h-4 text-red-500" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredScholarships.length === 0">
                                    <TableCell colspan="7" class="py-20 text-center text-slate-400 font-bold">
                                        No scholarship records found matching your filters.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <!-- Create Modal -->
            <Dialog v-model:open="showCreateModal">
                <DialogContent class="sm:max-w-[480px] rounded-3xl p-6">
                    <DialogHeader class="space-y-1">
                        <DialogTitle class="text-xl font-black text-slate-900 flex items-center gap-2">
                            <Award class="w-5 h-5 text-purple-600" /> Create Scholarship Scheme
                        </DialogTitle>
                        <DialogDescription class="text-xs font-medium text-slate-500">
                            Configure a new scholarship scheme for students and applicants.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="name" class="text-xs font-bold text-slate-700">Scholarship Name</Label>
                            <Input id="name" v-model="createForm.name" placeholder="e.g. Merit Academic Excellence Grant" class="h-11 rounded-xl font-bold text-sm" required />
                            <p v-if="createForm.errors.name" class="text-xs text-red-500 font-bold">{{ createForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="type" class="text-xs font-bold text-slate-700">Discount Model</Label>
                            <select id="type" v-model="createForm.type" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-purple-500" required>
                                <option value="percentage">Percentage Discount (%)</option>
                                <option value="fixed">Fixed Lump Sum Amount (₦)</option>
                            </select>
                            <p v-if="createForm.errors.type" class="text-xs text-red-500 font-bold">{{ createForm.errors.type }}</p>
                        </div>

                        <div v-if="createForm.type === 'percentage'" class="space-y-1.5">
                            <Label for="percentage" class="text-xs font-bold text-slate-700">Discount Percentage (%)</Label>
                            <Input id="percentage" type="number" step="0.01" min="0" max="100" v-model="createForm.percentage" placeholder="e.g. 50" class="h-11 rounded-xl font-bold text-sm" required />
                            <p v-if="createForm.errors.percentage" class="text-xs text-red-500 font-bold">{{ createForm.errors.percentage }}</p>
                        </div>

                        <div v-if="createForm.type === 'fixed'" class="space-y-1.5">
                            <Label for="amount" class="text-xs font-bold text-slate-700">Discount Amount (₦)</Label>
                            <Input id="amount" type="number" step="0.01" min="0" v-model="createForm.amount" placeholder="e.g. 150000" class="h-11 rounded-xl font-bold text-sm" required />
                            <p v-if="createForm.errors.amount" class="text-xs text-red-500 font-bold">{{ createForm.errors.amount }}</p>
                        </div>

                        <div class="space-y-3 pt-2 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="flex items-center space-x-2">
                                <Checkbox id="covers_admin" v-model:checked="createForm.covers_admin_charges" />
                                <Label for="covers_admin" class="text-xs font-bold text-slate-700 cursor-pointer">
                                    Covers Administrative Charges
                                </Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="covers_hostel" v-model:checked="createForm.covers_hostel_fees" />
                                <Label for="covers_hostel" class="text-xs font-bold text-slate-700 cursor-pointer">
                                    Covers Hostel Accommodation Fees
                                </Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="is_active" v-model:checked="createForm.is_active" />
                                <Label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer">
                                    Activate Scholarship Immediately
                                </Label>
                            </div>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" class="rounded-xl font-bold" @click="showCreateModal = false">Cancel</Button>
                            <Button type="submit" class="rounded-xl font-bold bg-purple-600 hover:bg-purple-700 text-white" :disabled="createForm.processing">Create Scheme</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Modal -->
            <Dialog v-model:open="showEditModal">
                <DialogContent class="sm:max-w-[480px] rounded-3xl p-6">
                    <DialogHeader class="space-y-1">
                        <DialogTitle class="text-xl font-black text-slate-900 flex items-center gap-2">
                            <Edit class="w-5 h-5 text-purple-600" /> Edit Scholarship Scheme
                        </DialogTitle>
                        <DialogDescription class="text-xs font-medium text-slate-500">
                            Update rules and coverage for this scholarship.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="edit-name" class="text-xs font-bold text-slate-700">Scholarship Name</Label>
                            <Input id="edit-name" v-model="editForm.name" class="h-11 rounded-xl font-bold text-sm" required />
                            <p v-if="editForm.errors.name" class="text-xs text-red-500 font-bold">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-type" class="text-xs font-bold text-slate-700">Discount Model</Label>
                            <select 
                                id="edit-type" 
                                v-model="editForm.type" 
                                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-purple-500 disabled:bg-slate-100 disabled:cursor-not-allowed" 
                                :disabled="editForm.students_count > 0 || editForm.applicants_count > 0"
                                required
                            >
                                <option value="percentage">Percentage Discount (%)</option>
                                <option value="fixed">Fixed Lump Sum Amount (₦)</option>
                            </select>
                            <p v-if="editForm.errors.type" class="text-xs text-red-500 font-bold">{{ editForm.errors.type }}</p>
                        </div>

                        <div v-if="editForm.type === 'percentage'" class="space-y-1.5">
                            <Label for="edit-percentage" class="text-xs font-bold text-slate-700">Discount Percentage (%)</Label>
                            <Input 
                                id="edit-percentage" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                max="100" 
                                v-model="editForm.percentage" 
                                class="h-11 rounded-xl font-bold text-sm"
                                :disabled="editForm.students_count > 0 || editForm.applicants_count > 0"
                                required 
                            />
                            <p v-if="editForm.students_count > 0 || editForm.applicants_count > 0" class="text-[10px] text-amber-600 font-bold uppercase leading-tight">
                                Value locked: Currently in use by {{ editForm.students_count + editForm.applicants_count }} scholars.
                            </p>
                            <p v-if="editForm.errors.percentage" class="text-xs text-red-500 font-bold">{{ editForm.errors.percentage }}</p>
                        </div>

                        <div v-if="editForm.type === 'fixed'" class="space-y-1.5">
                            <Label for="edit-amount" class="text-xs font-bold text-slate-700">Discount Amount (₦)</Label>
                            <Input 
                                id="edit-amount" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                v-model="editForm.amount" 
                                class="h-11 rounded-xl font-bold text-sm"
                                :disabled="editForm.students_count > 0 || editForm.applicants_count > 0"
                                required 
                            />
                            <p v-if="editForm.students_count > 0 || editForm.applicants_count > 0" class="text-[10px] text-amber-600 font-bold uppercase leading-tight">
                                Value locked: Currently in use by {{ editForm.students_count + editForm.applicants_count }} scholars.
                            </p>
                            <p v-if="editForm.errors.amount" class="text-xs text-red-500 font-bold">{{ editForm.errors.amount }}</p>
                        </div>

                        <div class="space-y-3 pt-2 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="flex items-center space-x-2">
                                <Checkbox id="edit_covers_admin" v-model:checked="editForm.covers_admin_charges" />
                                <Label for="edit_covers_admin" class="text-xs font-bold text-slate-700 cursor-pointer">
                                    Covers Administrative Charges
                                </Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="edit_covers_hostel" v-model:checked="editForm.covers_hostel_fees" />
                                <Label for="edit_covers_hostel" class="text-xs font-bold text-slate-700 cursor-pointer">
                                    Covers Hostel Accommodation Fees
                                </Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="edit_is_active" v-model:checked="editForm.is_active" />
                                <Label for="edit_is_active" class="text-xs font-bold text-slate-700 cursor-pointer">
                                    Scholarship Scheme Active
                                </Label>
                            </div>
                        </div>

                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" class="rounded-xl font-bold" @click="showEditModal = false">Cancel</Button>
                            <Button type="submit" class="rounded-xl font-bold bg-purple-600 hover:bg-purple-700 text-white" :disabled="editForm.processing">Save Changes</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
