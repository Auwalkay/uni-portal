<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

import { 
    Search, 
    Filter, 
    Download, 
    FileText, 
    Users, 
    CreditCard, 
    Wallet, 
    AlertCircle,
    ArrowLeft,
    TrendingUp,
    CheckCircle2,
    Building2,
    CalendarDays
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label';
import Pagination from '@/components/Pagination.vue';

const props = defineProps<{
    students: any;
    summaryStats: {
        total_billed: number;
        total_paid: number;
        total_balance: number;
        student_count: number;
        paid_count: number;
        partial_count: number;
        unpaid_count: number;
    };
    sessions: Array<any>;
    currentSession: any;
    faculties: Array<any>;
    departments: Array<any>;
    programs: Array<any>;
    filters: any;
}>();

const selectedSession = ref(props.filters.session_id || props.currentSession?.id || '');
const selectedFaculty = ref(props.filters.faculty_id || 'ALL');
const selectedDept = ref(props.filters.department_id || 'ALL');
const selectedLevel = ref(props.filters.level || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');
const searchQuery = ref(props.filters.search || '');

const levels = ['100', '200', '300', '400', '500', '600'];

const updateFilters = () => {
    router.get(route('admin.finance.bursary.student-fees'), {
        session_id: selectedSession.value,
        faculty_id: selectedFaculty.value === 'ALL' ? '' : selectedFaculty.value,
        department_id: selectedDept.value === 'ALL' ? '' : selectedDept.value,
        level: selectedLevel.value === 'ALL' ? '' : selectedLevel.value,
        status: selectedStatus.value === 'ALL' ? '' : selectedStatus.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Debounce search
let timeout: any;
watch(searchQuery, () => {
    clearTimeout(timeout);
    timeout = setTimeout(updateFilters, 500);
});

watch([selectedSession, selectedFaculty, selectedDept, selectedLevel, selectedStatus], () => {
    updateFilters();
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-700 border-green-200';
        case 'partially_paid': return 'bg-amber-100 text-amber-700 border-amber-200';
        default: return 'bg-red-100 text-red-700 border-red-200';
    }
};

</script>

<template>
    <Head title="Student Fee Analytics" />

    <AdminLayout>
        <div class="py-12 px-8 space-y-10 w-full max-w-[1600px] mx-auto animate-in fade-in duration-700">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-end gap-6 border-b border-slate-100 pb-8">
                <div class="space-y-2">
                    <div class="flex items-center gap-3 text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">
                        <span class="text-slate-900">Bursary</span>
                        <span>/</span>
                        <span>Fee Collections</span>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900">Fee Analytics</h1>
                    <p class="text-slate-500 font-medium tracking-tight flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-slate-300" /> Academic Period: {{ currentSession?.name }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button variant="outline" class="h-11 px-6 rounded-xl border-slate-200 font-bold shadow-sm" as-child>
                        <a :href="route('admin.finance.bursary.student-fees.pdf', filters)">
                            <Download class="w-4 h-4 mr-2" /> PDF Report
                        </a>
                    </Button>
                    <Button variant="default" class="h-11 px-6 rounded-xl font-bold shadow-md shadow-primary/20" as-child>
                        <a :href="route('admin.finance.bursary.student-fees.export', filters)">
                            <FileText class="w-4 h-4 mr-2" /> Excel Export
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Smart Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <Card class="border-none shadow-xl shadow-slate-200/40 bg-gradient-to-br from-slate-900 to-slate-800 text-white overflow-hidden relative group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <Wallet class="w-32 h-32" />
                    </div>
                    <CardContent class="p-6 space-y-4">
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Total Collected</p>
                        <h2 class="text-3xl font-black">{{ formatCurrency(summaryStats.total_paid) }}</h2>
                        <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-green-500 animate-in slide-in-from-left duration-1000" 
                                :style="{ width: (summaryStats.total_paid / (summaryStats.total_billed || 1) * 100) + '%' }"
                            ></div>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                            {{ Math.round(summaryStats.total_paid / (summaryStats.total_billed || 1) * 100) }}% of total billing
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Outstanding</p>
                            <div class="p-2 bg-amber-50 rounded-lg"><AlertCircle class="w-4 h-4 text-amber-600" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900">{{ formatCurrency(summaryStats.total_balance) }}</h2>
                        <p class="text-[10px] text-amber-600 font-bold uppercase tracking-tight">Pending Revenue</p>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Fully Paid</p>
                            <div class="p-2 bg-green-50 rounded-lg"><CheckCircle2 class="w-4 h-4 text-green-600" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900">{{ summaryStats.paid_count }}</h2>
                        <p class="text-[10px] text-green-600 font-bold uppercase tracking-tight">{{ summaryStats.student_count }} Total Students</p>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Total Billing</p>
                            <div class="p-2 bg-blue-50 rounded-lg"><TrendingUp class="w-4 h-4 text-blue-600" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900">{{ formatCurrency(summaryStats.total_billed) }}</h2>
                        <p class="text-[10px] text-blue-600 font-bold uppercase tracking-tight">Current Session Billing</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filter Controls -->
            <div class="bg-slate-50/50 p-2 rounded-2xl border border-slate-100 flex flex-wrap items-center gap-2">
                <div class="flex-1 min-w-[300px]">
                    <div class="relative group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary transition-colors" />
                        <Input 
                            v-model="searchQuery" 
                            placeholder="Search by Name or Matric Number..." 
                            class="pl-11 h-12 border-none bg-white rounded-xl shadow-sm font-bold text-sm"
                        />
                    </div>
                </div>

                <Select v-model="selectedSession">
                    <SelectTrigger class="w-44 h-12 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="Session" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedFaculty">
                    <SelectTrigger class="w-52 h-12 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="Faculty" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">All Faculties</SelectItem>
                        <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">{{ f.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedStatus">
                    <SelectTrigger class="w-44 h-12 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">All Status</SelectItem>
                        <SelectItem value="paid">Fully Paid</SelectItem>
                        <SelectItem value="partially_paid">Partially Paid</SelectItem>
                        <SelectItem value="unpaid">Unpaid</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedLevel">
                    <SelectTrigger class="w-32 h-12 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="Level" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">All Levels</SelectItem>
                        <SelectItem v-for="l in levels" :key="l" :value="l">{{ l }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Data Table -->
            <Card class="border-none shadow-2xl shadow-slate-200/30 overflow-hidden bg-white rounded-3xl">
                <Table>
                    <TableHeader class="bg-slate-50/50">
                        <TableRow class="hover:bg-transparent border-slate-100">
                            <TableHead class="py-6 px-8 text-xs font-black uppercase text-slate-400 tracking-widest">Student</TableHead>
                            <TableHead class="py-6 text-xs font-black uppercase text-slate-400 tracking-widest">Programme Info</TableHead>
                            <TableHead class="py-6 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Billing</TableHead>
                            <TableHead class="py-6 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Paid</TableHead>
                            <TableHead class="py-6 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Balance</TableHead>
                            <TableHead class="py-6 px-8 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="student in students.data" :key="student.id" class="border-slate-50 group hover:bg-slate-50/50 transition-all">
                            <TableCell class="py-6 px-8">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-900 group-hover:text-primary transition-colors uppercase tracking-tight">{{ student.user?.name }}</span>
                                    <span class="font-mono text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ student.matriculation_number }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="py-6">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-600">{{ student.program?.name }}</span>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ student.current_level }} Level</span>
                                </div>
                            </TableCell>
                            <TableCell class="py-6 text-right font-black text-slate-400 text-xs">
                                {{ formatCurrency(student.total_billed) }}
                            </TableCell>
                            <TableCell class="py-6 text-right font-black text-green-600 text-sm">
                                {{ formatCurrency(student.total_paid) }}
                            </TableCell>
                            <TableCell class="py-6 text-right font-black text-red-600 text-sm">
                                {{ formatCurrency(student.balance) }}
                            </TableCell>
                            <TableCell class="py-6 px-8 text-center">
                                <Badge :class="['rounded-lg px-3 py-1 font-black text-[10px] uppercase tracking-widest border shadow-sm', getStatusBadge(student.fee_status)]">
                                    {{ student.fee_status.replace('_', ' ') }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="students.data.length === 0">
                            <TableCell colspan="6" class="py-32 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center border border-slate-100">
                                        <Wallet class="w-8 h-8 text-slate-200" />
                                    </div>
                                    <p class="font-black text-slate-900">No matching fee records found.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <div class="flex justify-center pb-12">
                <Pagination :links="students.links" />
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-in {
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
