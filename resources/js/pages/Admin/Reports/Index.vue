<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import { 
    GraduationCap, DollarSign, Users, Building, FileText, Activity, 
    TrendingUp, Award, Library, Package, ChevronRight, Download, Calendar,
    RefreshCw, Filter, UserCheck, Inbox, ShieldAlert
} from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
} from 'chart.js';
import { Line, Doughnut, Bar, Pie } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, PointElement, LineElement, ArcElement);

const props = defineProps<{
    academicStats: {
        total_students: number;
        students_by_level: Array<{ label: string, value: number }>;
        students_by_gender: Array<{ label: string, value: number }>;
        total_faculties: number;
        total_departments: number;
        total_programmes: number;
        total_courses: number;
        total_registrations: number;
        total_applicants: number;
        applicants_by_status: Array<{ label: string, value: number }>;
        applicants_by_mode: Array<{ label: string, value: number }>;
    };
    studentsByFaculty: Array<{ label: string, value: number }>;
    studentsByDepartment: Array<{ label: string, faculty: string, value: number }>;
    studentsByProgramme: Array<{ label: string, department: string, value: number }>;
    financeStats: {
        total_invoiced: number;
        total_collected: number;
        outstanding_balance: number;
        collection_rate: number;
        monthly_revenue: Array<{ label: string, value: number }>;
        expenses: {
            total_expenses: number;
            expenses_by_category: Array<{ label: string, value: number }>;
        };
        payroll: {
            total_payroll_cost: number;
            total_payrolls_run: number;
        };
        scholarship: {
            total_scholarship_students: number;
            total_scholarships: number;
        };
    };
    attendanceStats: {
        total_staff: number;
        academic_staff: number;
        non_academic_staff: number;
        attendance_rates: Array<{ label: string, value: number }>;
    };
    hostelStats: {
        total_hostels: number;
        total_rooms: number;
        bed_capacity: number;
        occupied_beds: number;
        vacant_beds: number;
        occupancy_rate: number;
    };
    libraryStats: {
        total_books: number;
        total_loans: number;
        active_loans: number;
        overdue_loans: number;
    };
    sickbayStats: {
        total_visits: number;
        active_bed_occupancy: number;
        total_beds: number;
        visits_by_type: Array<{ label: string, value: number }>;
    };
    inventoryStats: {
        total_unique_items: number;
        total_quantity: number;
        assigned_quantity: number;
        available_quantity: number;
        total_complaints: number;
        pending_complaints: number;
    };
    
    // Lookups
    sessions: Array<{ id: string, name: string }>;
    faculties: Array<{ id: string, name: string }>;
    departments: Array<{ id: string, name: string, faculty_id: string }>;
    programmes: Array<{ id: string, name: string, department_id: string }>;
    entryModes: Array<string>;
    filters: {
        session_id?: string | null;
        faculty_id?: string | null;
        department_id?: string | null;
        program_id?: string | null;
        level?: string | null;
        gender?: string | null;
        entry_mode?: string | null;
        start_date?: string | null;
        end_date?: string | null;
    };
}>();

const activeTab = ref('overview');

// Filter state
const filterForm = ref({
    session_id: props.filters.session_id || 'all',
    faculty_id: props.filters.faculty_id || 'all',
    department_id: props.filters.department_id || 'all',
    program_id: props.filters.program_id || 'all',
    level: props.filters.level || 'all',
    gender: props.filters.gender || 'all',
    entry_mode: props.filters.entry_mode || 'all',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

// Filter lists computed based on parent selections
const filteredDepartments = computed(() => {
    if (filterForm.value.faculty_id === 'all') return props.departments;
    return props.departments.filter(d => d.faculty_id === filterForm.value.faculty_id);
});

const filteredProgrammes = computed(() => {
    if (filterForm.value.department_id === 'all') {
        if (filterForm.value.faculty_id === 'all') return props.programmes;
        // If department is all, but faculty is selected, filter programmes by all departments in that faculty
        const deptIdsInFaculty = props.departments
            .filter(d => d.faculty_id === filterForm.value.faculty_id)
            .map(d => d.id);
        return props.programmes.filter(p => deptIdsInFaculty.includes(p.department_id));
    }
    return props.programmes.filter(p => p.department_id === filterForm.value.department_id);
});

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(filterForm.value).forEach(([key, val]) => {
        if (val !== 'all' && val !== '' && val !== null) {
            params[key] = val;
        }
    });

    router.get(route('admin.reports.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.value = {
        session_id: 'all',
        faculty_id: 'all',
        department_id: 'all',
        program_id: 'all',
        level: 'all',
        gender: 'all',
        entry_mode: 'all',
        start_date: '',
        end_date: '',
    };
    router.get(route('admin.reports.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Apply automatically when filters change
watch(() => [
    filterForm.value.session_id,
    filterForm.value.level,
    filterForm.value.gender,
    filterForm.value.entry_mode,
    filterForm.value.start_date,
    filterForm.value.end_date
], () => {
    applyFilters();
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(amount);
};

// CHART DATA GENERATORS
const monthlyRevenueChartData = computed(() => {
    const labels = props.financeStats.monthly_revenue.map(item => item.label);
    const data = props.financeStats.monthly_revenue.map(item => item.value);
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            label: 'Collected Revenue (₦)',
            data: data.length ? data : [0],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
        }]
    };
});

const studentsByLevelChartData = computed(() => {
    const labels = props.academicStats.students_by_level.map(item => `${item.label} Level`);
    const data = props.academicStats.students_by_level.map(item => item.value);
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            label: 'Students Count',
            data: data.length ? data : [0],
            backgroundColor: '#4f46e5',
            borderRadius: 8,
        }]
    };
});

const genderChartData = computed(() => {
    const labels = props.academicStats.students_by_gender.map(item => item.label.toUpperCase());
    const data = props.academicStats.students_by_gender.map(item => item.value);
    return {
        labels: labels.length ? labels : ['Male', 'Female'],
        datasets: [{
            data: data.length ? data : [0, 0],
            backgroundColor: ['#3b82f6', '#ec4899'],
            borderWidth: 0,
        }]
    };
});

const admissionStatusChartData = computed(() => {
    const labels = props.academicStats.applicants_by_status.map(item => item.label.toUpperCase());
    const data = props.academicStats.applicants_by_status.map(item => item.value);
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            label: 'Applicants',
            data: data.length ? data : [0],
            backgroundColor: '#f59e0b',
            borderRadius: 6,
        }]
    };
});

const expensesByCategoryChartData = computed(() => {
    const labels = props.financeStats.expenses.expenses_by_category.map(item => item.label);
    const data = props.financeStats.expenses.expenses_by_category.map(item => item.value);
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            data: data.length ? data : [0],
            backgroundColor: ['#ef4444', '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899'],
            borderWidth: 0,
        }]
    };
});

const attendanceChartData = computed(() => {
    const labels = props.attendanceStats.attendance_rates.map(item => item.label.toUpperCase());
    const data = props.attendanceStats.attendance_rates.map(item => item.value);
    return {
        labels: labels.length ? labels : ['Present', 'Absent', 'Late'],
        datasets: [{
            data: data.length ? data : [1, 0, 0],
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
            borderWidth: 0,
        }]
    };
});

const hostelOccupancyChartData = computed(() => {
    return {
        labels: ['Occupied Beds', 'Vacant Beds'],
        datasets: [{
            data: [props.hostelStats.occupied_beds, props.hostelStats.vacant_beds],
            backgroundColor: ['#6366f1', '#e2e8f0'],
            borderWidth: 0,
        }]
    };
});

const sickbayVisitsChartData = computed(() => {
    const labels = props.sickbayStats.visits_by_type.map(item => item.label ? item.label.toUpperCase() : 'GENERAL');
    const data = props.sickbayStats.visits_by_type.map(item => item.value);
    return {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            label: 'Visits',
            data: data.length ? data : [0],
            backgroundColor: '#e11d48',
            borderRadius: 6,
        }]
    };
});

const hasActiveFilters = computed(() => {
    return Object.values(props.filters).some(val => val !== null && val !== undefined);
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Reports', href: '/admin/reports' },
];
</script>

<template>
    <Head title="System Analytics & Reports" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full max-w-[1650px] mx-auto">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b pb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">System Analytics & Reports</h1>
                    <p class="text-muted-foreground mt-1">Unified reporting hub aggregating statistics and trends across all modules.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                        <RefreshCw class="w-4 h-4 mr-2" /> Clear Filters
                    </Button>
                    <Button as-child variant="default" class="bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-100 dark:shadow-none">
                        <a :href="route('admin.reports.export')" class="flex items-center">
                            <Download class="w-4 h-4 mr-2" /> Export Master Excel
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Master Filters Panel -->
            <Card class="bg-white/50 backdrop-blur-sm border shadow-sm">
                <CardHeader class="pb-3 flex flex-row items-center gap-2">
                    <Filter class="w-4 h-4 text-indigo-600" />
                    <CardTitle class="text-sm font-semibold">Report Filters</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-9 gap-4">
                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Session</Label>
                        <Select v-model="filterForm.session_id">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Sessions" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Sessions</SelectItem>
                                <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Faculty</Label>
                        <Select v-model="filterForm.faculty_id" @update:model-value="() => { filterForm.department_id = 'all'; filterForm.program_id = 'all'; applyFilters(); }">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Faculties" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Faculties</SelectItem>
                                <SelectItem v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Department</Label>
                        <Select v-model="filterForm.department_id" @update:model-value="() => { filterForm.program_id = 'all'; applyFilters(); }">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Departments" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Departments</SelectItem>
                                <SelectItem v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Programme</Label>
                        <Select v-model="filterForm.program_id" @update:model-value="applyFilters">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Programmes" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Programmes</SelectItem>
                                <SelectItem v-for="p in filteredProgrammes" :key="p.id" :value="p.id">{{ p.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Year Level</Label>
                        <Select v-model="filterForm.level">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Levels" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Levels</SelectItem>
                                <SelectItem value="100">100 Level</SelectItem>
                                <SelectItem value="200">200 Level</SelectItem>
                                <SelectItem value="300">300 Level</SelectItem>
                                <SelectItem value="400">400 Level</SelectItem>
                                <SelectItem value="500">500 Level</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Gender</Label>
                        <Select v-model="filterForm.gender">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Genders" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Genders</SelectItem>
                                <SelectItem value="male">Male</SelectItem>
                                <SelectItem value="female">Female</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Entry Mode</Label>
                        <Select v-model="filterForm.entry_mode">
                            <SelectTrigger class="h-9"><SelectValue placeholder="All Modes" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Modes</SelectItem>
                                <SelectItem v-for="m in entryModes" :key="m" :value="m">{{ m }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Start Date</Label>
                        <Input type="date" v-model="filterForm.start_date" class="h-9 text-xs dark:bg-gray-900" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">End Date</Label>
                        <Input type="date" v-model="filterForm.end_date" class="h-9 text-xs dark:bg-gray-900" />
                    </div>
                </CardContent>
            </Card>

            <!-- Tab Navigation Menu -->
            <div class="flex flex-wrap gap-2 border-b pb-1">
                <button 
                    v-for="tab in ['overview', 'academics', 'finance', 'hr', 'hostels_library', 'sickbay_inventory']" 
                    :key="tab"
                    @click="activeTab = tab"
                    :class="[
                        'px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition-all capitalize',
                        activeTab === tab 
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20' 
                            : 'border-transparent text-slate-600 hover:text-indigo-600 hover:border-slate-300'
                    ]"
                >
                    {{ tab.replace('_', ' & ') }}
                </button>
            </div>

            <!-- TAB CONTENT: OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-6 animate-in fade-in duration-300">
                <!-- KPI Dashboard Grid -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="bg-gradient-to-br from-indigo-50/40 to-indigo-100/20 border border-indigo-100">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-semibold text-indigo-900">Total Enrolled Students</CardTitle>
                            <GraduationCap class="h-5 w-5 text-indigo-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-indigo-950">{{ academicStats.total_students }}</div>
                            <p class="text-xs text-indigo-700 mt-1">Across {{ academicStats.total_departments }} active departments</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-gradient-to-br from-emerald-50/40 to-emerald-100/20 border border-emerald-100">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-semibold text-emerald-900">Tuition Revenue</CardTitle>
                            <DollarSign class="h-5 w-5 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-emerald-950">{{ formatCurrency(financeStats.total_collected) }}</div>
                            <p class="text-xs text-emerald-700 mt-1">{{ financeStats.collection_rate }}% collection rate achieved</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-gradient-to-br from-amber-50/40 to-amber-100/20 border border-amber-100">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-semibold text-amber-900">Hostel Bed Occupancy</CardTitle>
                            <Building class="h-5 w-5 text-amber-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-amber-950">{{ hostelStats.occupancy_rate }}%</div>
                            <p class="text-xs text-amber-700 mt-1">{{ hostelStats.occupied_beds }} beds occupied of {{ hostelStats.bed_capacity }}</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-gradient-to-br from-rose-50/40 to-rose-100/20 border border-rose-100">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-semibold text-rose-900">Sickbay Visits</CardTitle>
                            <Activity class="h-5 w-5 text-rose-600" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-rose-950">{{ sickbayStats.total_visits }}</div>
                            <p class="text-xs text-rose-700 mt-1">{{ sickbayStats.active_bed_occupancy }} patients currently in ward</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Charts Section -->
                <div class="grid gap-6 md:grid-cols-7">
                    <Card class="md:col-span-4">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Monthly Fee Collection Trend</CardTitle>
                            <CardDescription>Visual timeline of fee revenue over the last 6 months</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="h-[320px]">
                                <Line :data="monthlyRevenueChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="md:col-span-3">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Students by Level</CardTitle>
                            <CardDescription>Distribution of active students across year levels</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="h-[320px]">
                                <Bar :data="studentsByLevelChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TAB CONTENT: ACADEMICS & ADMISSIONS -->
            <div v-if="activeTab === 'academics'" class="space-y-6 animate-in fade-in duration-300">
                <div class="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total Applicants</p>
                                <h3 class="text-2xl font-bold mt-1">{{ academicStats.total_applicants }}</h3>
                            </div>
                            <Users class="w-8 h-8 text-indigo-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total Faculties</p>
                                <h3 class="text-2xl font-bold mt-1">{{ academicStats.total_faculties }}</h3>
                            </div>
                            <Building class="w-8 h-8 text-emerald-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total Courses</p>
                                <h3 class="text-2xl font-bold mt-1">{{ academicStats.total_courses }}</h3>
                            </div>
                            <FileText class="w-8 h-8 text-amber-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Active Registrations</p>
                                <h3 class="text-2xl font-bold mt-1">{{ academicStats.total_registrations }}</h3>
                            </div>
                            <TrendingUp class="w-8 h-8 text-rose-500" />
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Applicant Funnel by Status</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[300px]">
                            <Bar :data="admissionStatusChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </CardContent>
                    </Card>
                    <Card class="flex flex-col">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Student Gender Distribution</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[250px] flex items-center justify-center">
                            <Doughnut :data="genderChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </CardContent>
                    </Card>
                </div>

                <!-- NEW: DETAILED BREAKDOWNS (TABLES) -->
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Students per Faculty Table -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b">
                            <CardTitle class="text-sm font-bold">Students per Faculty</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Faculty Name</TableHead>
                                        <TableHead class="text-right">Students</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="fac in studentsByFaculty" :key="fac.label">
                                        <TableCell class="font-medium text-slate-800">{{ fac.label }}</TableCell>
                                        <TableCell class="text-right font-bold text-indigo-600">{{ fac.value }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="studentsByFaculty.length === 0">
                                        <TableCell colspan="2" class="text-center text-slate-400 py-6">No data found</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Students per Department Table -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b">
                            <CardTitle class="text-sm font-bold">Students per Department</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0 max-h-[400px] overflow-y-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Department</TableHead>
                                        <TableHead>Faculty</TableHead>
                                        <TableHead class="text-right">Students</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="dept in studentsByDepartment" :key="dept.label">
                                        <TableCell class="font-medium text-slate-800">{{ dept.label }}</TableCell>
                                        <TableCell class="text-[11px] text-slate-500">{{ dept.faculty }}</TableCell>
                                        <TableCell class="text-right font-bold text-indigo-600">{{ dept.value }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="studentsByDepartment.length === 0">
                                        <TableCell colspan="3" class="text-center text-slate-400 py-6">No data found</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Students per Programme Table -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b">
                            <CardTitle class="text-sm font-bold">Students per Programme</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0 max-h-[400px] overflow-y-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Programme Name</TableHead>
                                        <TableHead>Department</TableHead>
                                        <TableHead class="text-right">Students</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="prog in studentsByProgramme" :key="prog.label">
                                        <TableCell class="font-medium text-slate-800">{{ prog.label }}</TableCell>
                                        <TableCell class="text-[11px] text-slate-500">{{ prog.department }}</TableCell>
                                        <TableCell class="text-right font-bold text-indigo-600">{{ prog.value }}</TableCell>
                                    </TableRow>
                                    <TableRow v-if="studentsByProgramme.length === 0">
                                        <TableCell colspan="3" class="text-center text-slate-400 py-6">No data found</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TAB CONTENT: FINANCE & PAYROLL -->
            <div v-if="activeTab === 'finance'" class="space-y-6 animate-in fade-in duration-300">
                <div class="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent class="p-6">
                            <p class="text-sm font-medium text-slate-500">Total Invoiced</p>
                            <h3 class="text-2xl font-bold mt-1 text-indigo-900">{{ formatCurrency(financeStats.total_invoiced) }}</h3>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <p class="text-sm font-medium text-slate-500">Total Collected</p>
                            <h3 class="text-2xl font-bold mt-1 text-emerald-600">{{ formatCurrency(financeStats.total_collected) }}</h3>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <p class="text-sm font-medium text-slate-500">Outstanding Balance</p>
                            <h3 class="text-2xl font-bold mt-1 text-rose-600">{{ formatCurrency(financeStats.outstanding_balance) }}</h3>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <p class="text-sm font-medium text-slate-500">Scholarship Students</p>
                            <h3 class="text-2xl font-bold mt-1 text-amber-600">{{ financeStats.scholarship.total_scholarship_students }}</h3>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-6 md:grid-cols-7">
                    <Card class="md:col-span-4">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Approved Expenses by Category</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[300px] flex items-center justify-center">
                            <Doughnut :data="expensesByCategoryChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </CardContent>
                    </Card>
                    <Card class="md:col-span-3 space-y-6 flex flex-col justify-between">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Payroll & Scholarship Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="p-4 bg-slate-50 rounded-xl border flex justify-between items-center">
                                <div>
                                    <h4 class="text-xs font-semibold text-slate-500 uppercase">Total Payroll Cost</h4>
                                    <p class="text-lg font-bold mt-1 text-slate-900">{{ formatCurrency(financeStats.payroll.total_payroll_cost) }}</p>
                                </div>
                                <Badge>{{ financeStats.payroll.total_payrolls_run }} Runs</Badge>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl border flex justify-between items-center">
                                <div>
                                    <h4 class="text-xs font-semibold text-slate-500 uppercase">Available Scholarships</h4>
                                    <p class="text-lg font-bold mt-1 text-slate-900">{{ financeStats.scholarship.total_scholarships }} schemes</p>
                                </div>
                                <Award class="w-6 h-6 text-amber-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TAB CONTENT: HR & ATTENDANCE -->
            <div v-if="activeTab === 'hr'" class="space-y-6 animate-in fade-in duration-300">
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total Staff</p>
                                <h3 class="text-2xl font-bold mt-1">{{ attendanceStats.total_staff }}</h3>
                            </div>
                            <Users class="w-8 h-8 text-indigo-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Academic Staff</p>
                                <h3 class="text-2xl font-bold mt-1">{{ attendanceStats.academic_staff }}</h3>
                            </div>
                            <GraduationCap class="w-8 h-8 text-emerald-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Non-Academic Staff</p>
                                <h3 class="text-2xl font-bold mt-1">{{ attendanceStats.non_academic_staff }}</h3>
                            </div>
                            <Building class="w-8 h-8 text-amber-500" />
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <Card class="flex flex-col">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Staff Attendance Status (Last 30 Days)</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[300px] flex items-center justify-center">
                            <Pie :data="attendanceChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                        </CardContent>
                    </Card>
                    <Card class="flex flex-col justify-center p-6 bg-slate-50 border border-slate-100 rounded-2xl">
                        <div class="text-center space-y-4">
                            <Calendar class="w-12 h-12 text-indigo-600 mx-auto" />
                            <h3 class="text-lg font-bold text-slate-900">Attendance Monitoring</h3>
                            <p class="text-slate-500 text-sm max-w-sm mx-auto">
                                Staff attendance is compiled and calculated monthly to facilitate payroll generations and audits.
                            </p>
                            <Button as-child variant="outline">
                                <Link :href="route('admin.attendance.index')">Go to Attendance Register</Link>
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- TAB CONTENT: HOSTELS & LIBRARY -->
            <div v-if="activeTab === 'hostels_library'" class="space-y-6 animate-in fade-in duration-300">
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Hostel Section -->
                    <Card class="flex flex-col">
                        <CardHeader class="border-b pb-4">
                            <CardTitle class="text-base font-bold">Hostel Bed Occupancy Dashboard</CardTitle>
                            <CardDescription>Real-time occupancy rates of active hostels</CardDescription>
                        </CardHeader>
                        <CardContent class="flex-1 p-6 grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                            <div class="h-[220px] flex items-center justify-center">
                                <Doughnut :data="hostelOccupancyChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                            </div>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between border-b pb-1">
                                    <span>Total Hostels:</span>
                                    <strong class="text-slate-900">{{ hostelStats.total_hostels }}</strong>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span>Total Rooms:</span>
                                    <strong class="text-slate-900">{{ hostelStats.total_rooms }}</strong>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span>Bed Capacity:</span>
                                    <strong class="text-slate-900">{{ hostelStats.bed_capacity }}</strong>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span class="text-indigo-600 font-semibold">Occupied Beds:</span>
                                    <strong class="text-indigo-600">{{ hostelStats.occupied_beds }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-emerald-600 font-semibold">Vacant Beds:</span>
                                    <strong class="text-emerald-600">{{ hostelStats.vacant_beds }}</strong>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Library Section -->
                    <Card class="flex flex-col">
                        <CardHeader class="border-b pb-4">
                            <CardTitle class="text-base font-bold">Library Catalog & Borrowings</CardTitle>
                            <CardDescription>Overview of library transactions and resource health</CardDescription>
                        </CardHeader>
                        <CardContent class="flex-1 p-6 flex flex-col justify-between space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 text-center">
                                    <Library class="w-6 h-6 text-indigo-600 mx-auto" />
                                    <h4 class="text-2xl font-bold mt-2 text-indigo-900">{{ libraryStats.total_books }}</h4>
                                    <p class="text-xs text-indigo-700">Total Books in Catalog</p>
                                </div>
                                <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100 text-center">
                                    <FileText class="w-6 h-6 text-emerald-600 mx-auto" />
                                    <h4 class="text-2xl font-bold mt-2 text-emerald-900">{{ libraryStats.total_loans }}</h4>
                                    <p class="text-xs text-emerald-700">All-Time Book Loans</p>
                                </div>
                            </div>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between border-b pb-1">
                                    <span>Active Borrowed Books:</span>
                                    <strong class="text-slate-900">{{ libraryStats.active_loans }}</strong>
                                </div>
                                <div class="flex justify-between text-rose-600">
                                    <span>Overdue Returns:</span>
                                    <strong class="font-bold">{{ libraryStats.overdue_loans }}</strong>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- TAB CONTENT: SICKBAY & INVENTORY -->
            <div v-if="activeTab === 'sickbay_inventory'" class="space-y-6 animate-in fade-in duration-300">
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Sickbay Section -->
                    <Card class="flex flex-col">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Sickbay Patient Logs & Beds</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="p-3 bg-rose-50 rounded-xl text-center">
                                    <h4 class="text-xl font-bold text-rose-900">{{ sickbayStats.total_visits }}</h4>
                                    <p class="text-[10px] text-rose-700 uppercase font-semibold">Total Visits</p>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl text-center">
                                    <h4 class="text-xl font-bold text-slate-900">{{ sickbayStats.active_bed_occupancy }}</h4>
                                    <p class="text-[10px] text-slate-700 uppercase font-semibold">Occupied Beds</p>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl text-center">
                                    <h4 class="text-xl font-bold text-slate-900">{{ sickbayStats.total_beds }}</h4>
                                    <p class="text-[10px] text-slate-700 uppercase font-semibold">Observation Beds</p>
                                </div>
                            </div>
                            <div class="h-[220px]">
                                <Bar :data="sickbayVisitsChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Inventory Section -->
                    <Card class="flex flex-col">
                        <CardHeader>
                            <CardTitle class="text-base font-bold">Asset Inventory Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 flex flex-col justify-between flex-1 space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 rounded-xl border">
                                    <Package class="w-5 h-5 text-indigo-600 mb-2" />
                                    <h4 class="text-xl font-bold">{{ inventoryStats.total_unique_items }}</h4>
                                    <p class="text-xs text-slate-500">Unique Asset Items</p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-xl border">
                                    <TrendingUp class="w-5 h-5 text-emerald-600 mb-2" />
                                    <h4 class="text-xl font-bold">{{ inventoryStats.total_quantity }}</h4>
                                    <p class="text-xs text-slate-500">Total Asset Quantity</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between border-b pb-1">
                                    <span>Assigned Items to Staff:</span>
                                    <strong class="text-slate-900">{{ inventoryStats.assigned_quantity }}</strong>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span>Available Items in Stock:</span>
                                    <strong class="text-slate-900">{{ inventoryStats.available_quantity }}</strong>
                                </div>
                                <div class="flex justify-between border-b pb-1">
                                    <span>Total Inventory Complaints:</span>
                                    <strong class="text-slate-900">{{ inventoryStats.total_complaints }}</strong>
                                </div>
                                <div class="flex justify-between text-amber-600">
                                    <span>Pending/Open Complaints:</span>
                                    <strong class="font-bold">{{ inventoryStats.pending_complaints }}</strong>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
