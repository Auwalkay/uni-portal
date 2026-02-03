<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { BookOpen, CreditCard, Users, GraduationCap, TrendingUp, Calendar, ArrowRight, UserPlus, FileText, ArrowUpRight, ArrowDownRight, Activity, CalendarClock, MapPin, Building2, Library, School } from 'lucide-vue-next';
import StatsCard from '@/components/StatsCard.vue';
import BarChart from '@/components/Charts/BarChart.vue';
import DoughnutChart from '@/components/Charts/DoughnutChart.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    stats: {
        total_students: number;
        fresh_students: number;
        applications: number;
        revenue: number;
        active_courses: number;
        revenue_growth: number;
        student_growth: number;
        outstanding_fees?: number | null;
        registration_compliance?: number | null;
        gender_distribution?: { male: number; female: number } | null;
        structural?: {
            faculties: number;
            departments: number;
            programs: number;
            sessions: number;
            staff: number;
            academic_staff: number;
            non_academic_staff: number;
        };
    };
    recentActivity: Array<{
        id: string;
        type: string;
        title: string;
        description: string;
        amount?: number;
        time_ago: string;
        icon: string;
    }>;
    sessions: Array<{ id: string; name: string }>;
    filters: { session_id: string };
    currentSessionName: string;
    charts: {
        revenue: { labels: string[]; data: number[] };
        faculty: { labels: string[]; data: number[] };
        level: { labels: string[]; data: number[] };
        program: { labels: string[]; data: number[] };
        staff_department: { labels: string[]; data: number[] };
    };
    myAllocations?: Array<{
        id: string;
        course: {
            id: string;
            code: string;
            title: string;
            unit: number;
        };
    }>;
    myTimetable?: Array<any>;
    lecturerStats?: {
        total_students: number;
        total_courses: number;
        classes_today: number;
    } | null;
}>();

const formatTime = (time: string) => {
    return time.substring(0, 5);
};

const getClassesForDay = (day: string) => {
    return props.myTimetable?.filter((t: any) => t.day === day) || [];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
];

const selectedSession = ref(props.filters.session_id);

watch(selectedSession, (newSession) => {
    router.visit(route('admin.dashboard'), {
        data: { session_id: newSession },
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'recentActivity', 'charts', 'currentSessionName', 'filters']
    });
});

const formatCurrency = (value: number) => {
    return '₦' + new Intl.NumberFormat('en-NG', { maximumFractionDigits: 0 }).format(value);
};

const getIcon = (iconName: string) => {
    const icons: Record<string, any> = { Users, CreditCard, BookOpen, GraduationCap, UserPlus, FileText, Activity };
    return icons[iconName] || Activity;
};

// Chart Options
const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 13, family: "'Inter', sans-serif" },
            bodyFont: { size: 13, family: "'Inter', sans-serif" },
            displayColors: false,
            callbacks: {
                label: (context: any) => {
                    return formatCurrency(context.raw);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: '#f1f5f9',
                borderDash: [2, 2],
                drawBorder: false,
            },
            ticks: {
                font: { size: 11, family: "'Inter', sans-serif" },
                color: '#64748b',
                callback: (value: number) => {
                    return '₦' + new Intl.NumberFormat('en-NG', { notation: 'compact', compactDisplay: 'short' }).format(value);
                }
            },
            border: { display: false }
        },
        x: {
            grid: { display: false },
            ticks: {
                font: { size: 11, family: "'Inter', sans-serif" },
                color: '#64748b'
            },
            border: { display: false }
        }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { position: 'right' as const, labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } } },
    }
};

const revenueChartData = {
    labels: props.charts.revenue.labels,
    datasets: [{
        label: 'Revenue',
        backgroundColor: '#4f46e5',
        hoverBackgroundColor: '#4338ca',
        borderRadius: 8,
        barPercentage: 0.6,
        categoryPercentage: 0.8,
        data: props.charts.revenue.data
    }]
};


const facultyChartData = {
    labels: props.charts.faculty.labels,
    datasets: [{
        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
        borderWidth: 0,
        data: props.charts.faculty.data
    }]
};






const levelChartData = {
    labels: props.charts.level.labels,
    datasets: [{
        label: 'Students',
        backgroundColor: '#f59e0b',
        borderRadius: 4,
        data: props.charts.level.data
    }]
};

const programChartData = {
    labels: props.charts.program.labels,
    datasets: [{
        backgroundColor: ['#6366f1', '#ec4899', '#14b8a6', '#f97316', '#8b5cf6'],
        borderWidth: 0,
        data: props.charts.program.data
    }]
};



const staffChartData = {
    labels: props.charts.staff_department.labels,
    datasets: [{
        label: 'Staff',
        backgroundColor: '#10b981',
        hoverBackgroundColor: '#059669',
        borderRadius: 6,
        data: props.charts.staff_department.data
    }]
};

</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-6">
                 <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                    <p class="text-muted-foreground mt-1 text-lg">
                        Overview for <span class="font-semibold text-primary">{{ currentSessionName }} Session</span>
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-secondary/50 rounded-md text-sm font-medium text-secondary-foreground">
                        <Calendar class="w-4 h-4" />
                        <span>Today: {{ new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric'}) }}</span>
                    </div>
                     <Select v-model="selectedSession">
                        <SelectTrigger class="w-[180px] h-10">
                            <SelectValue placeholder="Select Session" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.name }} Session
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Structural Stats (Faculties/Departments/etc) -->
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <Building class="w-5 h-5 text-indigo-500" />
                    Institutional Structure
                </h2>
                <div v-if="stats.structural" class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <Card class="bg-indigo-50 dark:bg-slate-900/50 border-indigo-100 dark:border-indigo-900">
                    <CardContent class="p-4 flex items-center justify-between">
                         <div>
                             <p class="text-xs font-medium text-indigo-500 uppercase tracking-wider">Faculties</p>
                             <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ stats.structural.faculties }}</div>
                         </div>
                         <div class="h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
                             <Building2 class="w-5 h-5" />
                         </div>
                    </CardContent>
                </Card>
                 <Card class="bg-violet-50 dark:bg-slate-900/50 border-violet-100 dark:border-violet-900">
                    <CardContent class="p-4 flex items-center justify-between">
                         <div>
                             <p class="text-xs font-medium text-violet-500 uppercase tracking-wider">Departments</p>
                             <div class="text-2xl font-bold text-violet-700 dark:text-violet-300">{{ stats.structural.departments }}</div>
                         </div>
                         <div class="h-10 w-10 bg-violet-100 rounded-full flex items-center justify-center text-violet-600">
                             <Library class="w-5 h-5" />
                         </div>
                    </CardContent>
                </Card>
                 <Card class="bg-fuchsia-50 dark:bg-slate-900/50 border-fuchsia-100 dark:border-fuchsia-900">
                    <CardContent class="p-4 flex items-center justify-between">
                         <div>
                             <p class="text-xs font-medium text-fuchsia-500 uppercase tracking-wider">Programs</p>
                             <div class="text-2xl font-bold text-fuchsia-700 dark:text-fuchsia-300">{{ stats.structural.programs }}</div>
                         </div>
                         <div class="h-10 w-10 bg-fuchsia-100 rounded-full flex items-center justify-center text-fuchsia-600">
                             <GraduationCap class="w-5 h-5" />
                         </div>
                    </CardContent>
                </Card>
                 <Card class="bg-cyan-50 dark:bg-slate-900/50 border-cyan-100 dark:border-cyan-900">
                    <CardContent class="p-4 flex items-center justify-between">
                         <div>
                             <p class="text-xs font-medium text-cyan-500 uppercase tracking-wider">Sessions</p>
                             <div class="text-2xl font-bold text-cyan-700 dark:text-cyan-300">{{ stats.structural.sessions }}</div>
                         </div>
                         <div class="h-10 w-10 bg-cyan-100 rounded-full flex items-center justify-center text-cyan-600">
                             <School class="w-5 h-5" />
                         </div>
                    </CardContent>
                </Card>

                 <!-- Staff Card -->
                 <Card class="bg-emerald-50 dark:bg-slate-900/50 border-emerald-100 dark:border-emerald-900">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between mb-2">
                             <div>
                                 <p class="text-xs font-medium text-emerald-500 uppercase tracking-wider">Total Staff</p>
                                 <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ stats.structural.staff }}</div>
                             </div>
                             <div class="h-10 w-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                 <Users class="w-5 h-5" />
                             </div>
                        </div>
                        
                        <div class="flex items-center gap-2 pt-2 border-t border-emerald-100 dark:border-emerald-900/50 mt-1">
                            <div class="flex-1">
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium uppercase tracking-wide">Academic</span>
                                <div class="text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ stats.structural.academic_staff }}</div>
                            </div>
                            <div class="w-px h-6 bg-emerald-200 dark:bg-emerald-800"></div>
                            <div class="flex-1 text-right">
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium uppercase tracking-wide">Non-Academic</span>
                                <div class="text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ stats.structural.non_academic_staff }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>



            </div>

            <!-- Main Stats Grid -->

            <!-- Student Insights -->
            <div>
                 <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <Users class="w-5 h-5 text-blue-500" />
                    Student Insights
                </h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Students Card -->
                    <Card v-if="stats.total_students !== null" class="overflow-hidden relative group hover:shadow-lg transition-all duration-300 border-l-4 border-l-primary/80">
                        <div class="absolute right-0 top-0 h-24 w-24 bg-primary/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative">
                            <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Students</CardTitle>
                            <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                                <Users class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent class="relative">
                            <div class="text-3xl font-extrabold tracking-tight mt-1">{{ stats.total_students.toLocaleString() }}</div>
                            <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1.5 font-medium">
                                 <span class="inline-flex items-center text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-100">
                                    <ArrowUpRight class="w-3 h-3 mr-0.5" /> +2.5%
                                 </span>
                                 <span class="opacity-75">vs last month</span>
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Fresh Intake Card -->
                    <Card v-if="stats.fresh_students !== null" class="overflow-hidden relative group hover:shadow-lg transition-all duration-300 border-l-4 border-l-blue-500/80">
                        <div class="absolute right-0 top-0 h-24 w-24 bg-blue-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative">
                             <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Fresh Intake</CardTitle>
                             <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                 <UserPlus class="h-4 w-4" />
                             </div>
                        </CardHeader>
                        <CardContent class="relative">
                             <div class="text-3xl font-extrabold tracking-tight mt-1">{{ stats.fresh_students.toLocaleString() }}</div>
                            <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1.5 font-medium">
                                <span v-if="stats.student_growth >= 0" class="inline-flex items-center text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-100">
                                    <ArrowUpRight class="w-3 h-3 mr-0.5" /> {{ stats.student_growth }}%
                                </span>
                                 <span v-else class="inline-flex items-center text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-full border border-rose-100">
                                    <ArrowDownRight class="w-3 h-3 mr-0.5" /> {{ stats.student_growth }}%
                                </span>
                                <span class="opacity-75">vs last session</span>
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Registration Compliance -->
                    <Card v-if="stats.registration_compliance !== null" class="border-l-4 border-l-indigo-500 hover:shadow-md transition-shadow">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Registration Compliance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-end justify-between">
                                <div class="text-2xl font-bold text-indigo-700">{{ stats.registration_compliance }}%</div>
                                <span class="text-xs text-indigo-500 font-medium mb-1">Target: 95%</span>
                            </div>
                             <div class="h-2 w-full bg-indigo-100 rounded-full mt-2 overflow-hidden">
                                <div class="h-full bg-indigo-600 rounded-full transition-all duration-1000" :style="{ width: `${stats.registration_compliance}%` }"></div>
                            </div>
                             <p class="text-xs text-muted-foreground mt-2">Students registered for courses</p>
                        </CardContent>
                     </Card>

                     <!-- Demographics -->
                      <Card v-if="stats.gender_distribution" class="border-l-4 border-l-pink-500 hover:shadow-md transition-shadow">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Demographics</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center justify-between mt-1">
                                <div class="text-center">
                                    <span class="block text-xl font-bold text-blue-600">{{ stats.gender_distribution.male }}%</span>
                                    <span class="text-[10px] uppercase text-muted-foreground">Male</span>
                                </div>
                                <div class="h-8 w-px bg-slate-200"></div>
                                <div class="text-center">
                                    <span class="block text-xl font-bold text-pink-600">{{ stats.gender_distribution.female }}%</span>
                                    <span class="text-[10px] uppercase text-muted-foreground">Female</span>
                                </div>
                            </div>
                            <div class="flex h-1.5 w-full rounded-full mt-3 overflow-hidden">
                                <div class="bg-blue-500 h-full" :style="{ width: `${stats.gender_distribution.male}%` }"></div>
                                <div class="bg-pink-500 h-full" :style="{ width: `${stats.gender_distribution.female}%` }"></div>
                            </div>
                        </CardContent>
                     </Card>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Financial Overview -->
                <div>
                     <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <CreditCard class="w-5 h-5 text-emerald-500" />
                        Financial Overview
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                         <!-- Revenue Card -->
                        <Card v-if="stats.revenue !== null" class="overflow-hidden relative group hover:shadow-lg transition-all duration-300 border-l-4 border-l-emerald-500/80">
                            <div class="absolute right-0 top-0 h-24 w-24 bg-emerald-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative">
                                <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Revenue</CardTitle>
                                <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                     <CreditCard class="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent class="relative">
                                <div class="text-3xl font-extrabold tracking-tight mt-1">{{ formatCurrency(stats.revenue) }}</div>
                                 <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1.5 font-medium">
                                     <span v-if="stats.revenue_growth >= 0" class="inline-flex items-center text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-100">
                                        <ArrowUpRight class="w-3 h-3 mr-0.5" /> {{ stats.revenue_growth }}%
                                    </span>
                                    <span v-else class="inline-flex items-center text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-full border border-rose-100">
                                        <ArrowDownRight class="w-3 h-3 mr-0.5" /> {{ stats.revenue_growth }}%
                                    </span>
                                    <span class="opacity-75">vs last session</span>
                                </p>
                            </CardContent>
                        </Card>

                        <!-- Outstanding Fees -->
                         <Card v-if="stats.outstanding_fees !== null" class="border-l-4 border-l-orange-500 hover:shadow-md transition-shadow">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Outstanding Fees</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold text-orange-600">{{ formatCurrency(stats.outstanding_fees ?? 0) }}</div>
                                <p class="text-xs text-muted-foreground mt-1">Pending payments this session</p>
                                <div class="h-1.5 w-full bg-orange-100 rounded-full mt-3 overflow-hidden">
                                    <div class="h-full bg-orange-500 rounded-full" style="width: 45%"></div>
                                </div>
                            </CardContent>
                         </Card>
                    </div>
                </div>

                <!-- Applications -->
                <div>
                     <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <FileText class="w-5 h-5 text-amber-500" />
                        Admissions
                    </h2>
                    <div class="grid gap-4">
                         <!-- Applications Card -->
                         <Card v-if="stats.applications !== null" class="overflow-hidden relative group hover:shadow-lg transition-all duration-300 border-l-4 border-l-amber-500/80">
                             <div class="absolute right-0 top-0 h-24 w-24 bg-amber-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative">
                                <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Pending Apps</CardTitle>
                                <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                     <FileText class="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent class="relative">
                                <div class="text-3xl font-extrabold tracking-tight mt-1">{{ stats.applications.toLocaleString() }}</div>
                                 <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1.5 font-medium">
                                    <span class="inline-flex items-center text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full border border-amber-100">
                                        <FileText class="w-3 h-3 mr-0.5" /> Pending
                                    </span>
                                    <span class="opacity-75">needs attention</span>
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Lecturer Dashboard Section -->
            <div v-if="lecturerStats" class="space-y-6">
                <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <GraduationCap class="w-5 h-5 text-indigo-500" />
                    Academic Overview
                </h2>
                 <!-- Stats Overview -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white border-0 shadow-md">
                        <CardHeader class="pb-2">
                             <CardTitle class="text-sm font-medium text-indigo-100 uppercase tracking-wider">Students Taught</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold">{{ lecturerStats.total_students }}</div>
                            <p class="text-xs text-indigo-200 mt-1">Across {{ lecturerStats.total_courses }} courses</p>
                        </CardContent>
                    </Card>

                    <Card class="bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white border-0 shadow-md">
                        <CardHeader class="pb-2">
                             <CardTitle class="text-sm font-medium text-fuchsia-100 uppercase tracking-wider">Active Courses</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold">{{ lecturerStats.total_courses }}</div>
                            <p class="text-xs text-fuchsia-200 mt-1">Allocated this session</p>
                        </CardContent>
                    </Card>

                    <Card class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white border-0 shadow-md">
                        <CardHeader class="pb-2">
                             <CardTitle class="text-sm font-medium text-emerald-100 uppercase tracking-wider">Classes Today</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold">{{ lecturerStats.classes_today }}</div>
                            <p class="text-xs text-emerald-200 mt-1">{{ new Date().toLocaleDateString('en-GB', { weekday: 'long' }) }}</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Course Cards -->
                    <div class="md:col-span-2 space-y-4">
                        <h3 class="text-lg font-semibold flex items-center gap-2 text-slate-800 dark:text-slate-200">
                             <BookOpen class="w-5 h-5 text-indigo-600" /> My Allocations
                        </h3>
                         <div class="grid sm:grid-cols-2 gap-4">
                            <a v-for="(allocation, index) in myAllocations" 
                                :key="allocation.id"
                                :href="route('admin.teaching.courses.show', allocation.course.id)"
                                class="group relative overflow-hidden rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all duration-300"
                            >
                                <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-150"></div>
                                
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <Badge variant="outline" class="font-mono text-xs bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 border-slate-200 dark:border-slate-700">
                                            {{ allocation.course.code }}
                                        </Badge>
                                        <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                             <ArrowRight class="w-4 h-4" />
                                        </div>
                                    </div>
                                    
                                    <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-1 group-hover:text-indigo-600 transition-colors line-clamp-1">
                                        {{ allocation.course.title }}
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ allocation.course.unit }} Units
                                    </p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Today's Schedule (Mini) -->
                     <Card class="bg-indigo-900 text-white border-0 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-800/50 rounded-full blur-3xl -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-purple-600/30 rounded-full blur-2xl -ml-5 -mb-5"></div>
                        
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2 text-indigo-100 relative z-10">
                                    <CalendarClock class="w-5 h-5" /> Today's Schedule
                                </CardTitle>
                                
                                <Dialog>
                                    <DialogTrigger as-child>
                                        <Button size="sm" variant="secondary" class="relative z-10 bg-indigo-500/20 text-indigo-100 hover:bg-indigo-500/30 border-0 h-7">
                                            View Full
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="max-w-[95vw] md:max-w-7xl h-[90vh] flex flex-col p-6">
                                        <DialogHeader class="flex-shrink-0">
                                            <DialogTitle class="flex items-center gap-2 text-xl">
                                                <CalendarClock class="w-5 h-5 text-indigo-600" />
                                                Weekly Timetable
                                            </DialogTitle>
                                            <DialogDescription>
                                                Complete schedule of all your allocated classes.
                                            </DialogDescription>
                                        </DialogHeader>
                                        
                                        <div class="flex-1 overflow-y-auto min-h-0 mt-4 pr-1">
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                                                <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day" 
                                                class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-100 dark:border-slate-800 overflow-hidden flex flex-col min-h-[200px]"
                                            >
                                                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-3 border-b border-indigo-100 dark:border-indigo-900 flex items-center justify-between">
                                                    <span class="font-bold text-indigo-900 dark:text-indigo-400 uppercase text-xs tracking-wider">{{ day }}</span>
                                                    <span class="text-[10px] font-semibold text-indigo-400 dark:text-indigo-300 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full shadow-sm">
                                                        {{ getClassesForDay(day).length }}
                                                    </span>
                                                </div>
                                                
                                                <div class="p-2 space-y-2 flex-1">
                                                    <div v-if="getClassesForDay(day).length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 opacity-60">
                                                        <span class="text-xs">No classes</span>
                                                    </div>
                                                    
                                                    <div v-for="cls in getClassesForDay(day)" :key="cls.id" 
                                                        class="bg-white dark:bg-slate-950 rounded-lg p-3 shadow-sm border-l-4 border-indigo-500 hover:shadow-md transition-all group"
                                                    >
                                                        <div class="flex justify-between items-start mb-1">
                                                            <Badge variant="secondary" class="font-mono text-[10px] bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-100">
                                                                {{ formatTime(cls.start_time) }} - {{ formatTime(cls.end_time) }}
                                                            </Badge>
                                                        </div>
                                                        
                                                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-xs mb-0.5 group-hover:text-indigo-600 transition-colors">
                                                            {{ cls.course.code }}
                                                        </h4>
                                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-1">{{ cls.course.title }}</p>
                                                        
                                                        <div class="space-y-1 pt-1 border-t border-slate-50 dark:border-slate-900">
                                                            <div class="flex items-center gap-1.5 text-[10px] text-slate-600 dark:text-slate-400">
                                                                <MapPin class="w-2.5 h-2.5 text-slate-400" />
                                                                <span class="font-medium">{{ cls.venue || 'TBA' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </CardHeader>
                        <CardContent class="relative z-10 space-y-3">
                             <div v-if="lecturerStats.classes_today === 0" class="text-center py-6 text-indigo-200/60">
                                <div class="bg-indigo-800/50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <Calendar class="w-6 h-6" />
                                </div>
                                <p class="text-sm">No classes scheduled for today.</p>
                            </div>
                            <div v-else class="space-y-3">
                                <template v-for="cls in getClassesForDay(new Date().toLocaleDateString('en-US', { weekday: 'long' }))" :key="cls.id">
                                     <div class="bg-white/10 backdrop-blur-md rounded-lg p-3 border border-white/10 hover:bg-white/20 transition-colors">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs font-mono font-medium text-indigo-200">{{ formatTime(cls.start_time) }}</span>
                                            <Badge variant="secondary" class="bg-indigo-500/20 text-indigo-100 hover:bg-indigo-500/30 text-[10px] border-0">{{ cls.course.code }}</Badge>
                                        </div>
                                        <p class="text-sm font-semibold truncate">{{ cls.course.title }}</p>
                                        <div class="flex items-center gap-1.5 mt-2 text-xs text-indigo-300">
                                            <MapPin class="w-3 h-3" /> {{ cls.venue }}
                                        </div>
                                     </div>
                                </template>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Original Timetable Widget (Now only shown if NOT lecturerStats to avoid duplication, or could remain) -->
            <!-- Actually, user might want full week view too. Let's keep it but maybe below. -->
            <div v-if="myTimetable && !lecturerStats" class="w-full">
            <!-- ... existing content ... -->

            <!-- My Timetable Widget (Staff) -->
            <div v-if="myTimetable" class="w-full">
                <Card class="hover:shadow-md transition-shadow duration-200 border-indigo-100 bg-white dark:bg-slate-900">
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2 text-indigo-900 dark:text-indigo-400">
                            <CalendarClock class="w-5 h-5 text-indigo-600" />
                            My Teaching Schedule
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!myTimetable || myTimetable.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground border rounded-lg border-dashed">
                             <CalendarClock class="w-10 h-10 mb-3 opacity-20" />
                            <p class="font-medium">No classes scheduled</p>
                            <p class="text-xs text-muted-foreground mt-1">Your allocated courses have not been added to the timetable yet.</p>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                            <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day" 
                                class="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-100 dark:border-slate-800 overflow-hidden flex flex-col min-h-[200px]"
                            >
                                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-3 border-b border-indigo-100 dark:border-indigo-900 flex items-center justify-between">
                                    <span class="font-bold text-indigo-900 dark:text-indigo-400 uppercase text-xs tracking-wider">{{ day }}</span>
                                    <span class="text-[10px] font-semibold text-indigo-400 dark:text-indigo-300 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full shadow-sm">
                                        {{ getClassesForDay(day).length }}
                                    </span>
                                </div>
                                
                                <div class="p-2 space-y-2 flex-1">
                                    <div v-if="getClassesForDay(day).length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 opacity-60">
                                        <span class="text-xs">No classes</span>
                                    </div>
                                    
                                    <div v-for="cls in getClassesForDay(day)" :key="cls.id" 
                                        class="bg-white dark:bg-slate-950 rounded-lg p-3 shadow-sm border-l-4 border-indigo-500 hover:shadow-md transition-all group"
                                    >
                                        <div class="flex justify-between items-start mb-1">
                                            <Badge variant="secondary" class="font-mono text-[10px] bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-100">
                                                {{ formatTime(cls.start_time) }} - {{ formatTime(cls.end_time) }}
                                            </Badge>
                                        </div>
                                        
                                        <h4 class="font-bold text-slate-800 dark:text-slate-200 text-xs mb-0.5 group-hover:text-indigo-600 transition-colors">
                                            {{ cls.course.code }}
                                        </h4>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-1">{{ cls.course.title }}</p>
                                        
                                        <div class="space-y-1 pt-1 border-t border-slate-50 dark:border-slate-900">
                                            <div class="flex items-center gap-1.5 text-[10px] text-slate-600 dark:text-slate-400">
                                                <MapPin class="w-2.5 h-2.5 text-slate-400" />
                                                <span class="font-medium">{{ cls.venue || 'TBA' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            </div>

            <!-- Charts Section -->
            <div v-if="charts.revenue.data.length > 0 || charts.faculty.data.length > 0">
                 <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <LineChart class="w-5 h-5 text-emerald-500" />
                    Analytics & Trends
                </h2>
                <div class="grid gap-6 md:grid-cols-7">
                <!-- Revenue Chart (Larger) -->
                <Card v-if="charts.revenue.data.length > 0" class="md:col-span-4 lg:col-span-5 hover:shadow-md transition-shadow duration-200">
                    <CardHeader>
                        <CardTitle class="text-lg">Financial Overview</CardTitle>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <div class="h-[350px] w-full">
                            <BarChart :chart-data="revenueChartData" :chart-options="barOptions" />
                        </div>
                    </CardContent>
                </Card>
 
                <!-- Distribution Chart (Smaller) -->
                <Card v-if="charts.faculty.data.length > 0" :class="charts.revenue.data.length > 0 ? 'md:col-span-3 lg:col-span-2' : 'md:col-span-7 lg:col-span-7'" class="hover:shadow-md transition-shadow duration-200">
                    <CardHeader>
                         <CardTitle class="text-lg">Student Distribution</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[350px] w-full flex items-center justify-center">
                            <DoughnutChart :chart-data="facultyChartData" :chart-options="doughnutOptions" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Additional Charts Section (Levels / Programs) -->
            <div v-if="charts.level.data.length > 0 || charts.program.data.length > 0" class="grid gap-6 md:grid-cols-2 mb-6">
                 <!-- Level Distribution -->
                <Card v-if="charts.level.data.length > 0" class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle class="text-lg">Students by Level</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px] w-full">
                            <BarChart :chart-data="levelChartData" :chart-options="barOptions" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Program Distribution -->
                <Card v-if="charts.program.data.length > 0" class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle class="text-lg">Top Programs</CardTitle>
                    </CardHeader>
                    <CardContent>
                         <div class="h-[300px] w-full flex items-center justify-center">
                            <DoughnutChart :chart-data="programChartData" :chart-options="doughnutOptions" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Staff Chart -->
             <div v-if="charts.staff_department.data.length > 0" class="mb-6">
                <Card class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <CardTitle class="text-lg">Staff by Department</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px] w-full">
                            <BarChart :chart-data="staffChartData" :chart-options="barOptions" />
                        </div>
                    </CardContent>
                </Card>
            </div>
            </div>

            <!-- System Activity -->
            <div>
                 <h2 class="text-xl font-semibold tracking-tight text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <Activity class="w-5 h-5 text-indigo-500" />
                    System Activity
                </h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Recent Activity Feed -->
                <Card class="md:col-span-2 shadow-sm border-slate-200 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/50">
                         <CardTitle class="text-base font-semibold flex items-center gap-2 text-slate-800 dark:text-slate-100">
                            <Activity class="w-4 h-4 text-indigo-500" />
                            Recent Activity
                        </CardTitle>
                        <Button variant="ghost" size="sm" class="h-7 text-xs text-muted-foreground hover:text-indigo-600">View All</Button>
                    </CardHeader>
                    <CardContent class="pt-6">
                         <div class="space-y-6">
                            <div v-for="(item, index) in recentActivity" :key="index" class="flex items-start gap-4 pb-4 border-b border-slate-100 dark:border-slate-800 last:border-0 last:pb-0">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full shrink-0"
                                     :class="item.type === 'payment' ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600'">
                                     <CreditCard v-if="item.type === 'payment'" class="h-4 w-4" />
                                     <UserPlus v-else class="h-4 w-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-0.5">
                                         <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">{{ item.title }}</p>
                                         <time class="text-[10px] text-slate-500 font-mono whitespace-nowrap">{{ item.time_ago }}</time>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="recentActivity.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                                <Activity class="h-10 w-10 text-slate-200 mb-2" />
                                <p class="text-sm text-muted-foreground">No recent activity found.</p>
                            </div>
                         </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions & Status -->
                <div class="space-y-6">
                     <Card class="bg-slate-900 text-white border-0 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600/20 rounded-full blur-3xl -mr-32 -mt-32"></div>
                        <CardHeader>
                            <CardTitle class="text-lg relative z-10">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-3 relative z-10">
                             <Button v-if="stats.total_students !== null" variant="secondary" class="w-full justify-start h-auto py-3 bg-white/10 hover:bg-white/20 text-white border-0" as-child>
                                <a :href="route('admin.students.create')" class="flex items-center">
                                    <div class="p-2 rounded bg-indigo-500/30 mr-3">
                                        <UserPlus class="h-4 w-4" />
                                    </div>
                                    <div class="text-left">
                                        <div class="font-medium text-sm">Enroll New Student</div>
                                        <div class="text-[10px] opacity-70">Register fresh intake</div>
                                    </div>
                                </a>
                            </Button>
                            
                            <Button v-if="stats.revenue !== null" variant="secondary" class="w-full justify-start h-auto py-3 bg-white/10 hover:bg-white/20 text-white border-0" as-child>
                                <a :href="route('admin.finance.index')" class="flex items-center">
                                    <div class="p-2 rounded bg-emerald-500/30 mr-3">
                                        <CreditCard class="h-4 w-4" />
                                    </div>
                                    <div class="text-left">
                                        <div class="font-medium text-sm">Finance & Invoices</div>
                                        <div class="text-[10px] opacity-70">Manage school fees</div>
                                    </div>
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card class="border-l-4 border-l-amber-500">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground uppercase tracking-wider">System Health</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-2">
                            <div class="flex items-center justify-between text-sm py-2 border-b border-dashed">
                                <span class="text-slate-600 font-medium">Current Session</span>
                                <Badge variant="secondary" class="font-mono text-xs">{{ currentSessionName }}</Badge>
                            </div>
                             <div class="flex items-center justify-between text-sm py-2 border-b border-dashed">
                                <span class="text-slate-600 font-medium">Portal Access</span>
                                <div class="flex items-center gap-1.5 text-emerald-600 text-xs font-bold uppercase">
                                    <span class="relative flex h-2 w-2">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Online
                                </div>
                            </div>
                             <div class="flex items-center justify-between text-sm pt-2">
                                <span class="text-slate-600 font-medium">Database</span>
                                <span class="text-xs text-muted-foreground">Stable</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
            </div>
        </div>
    </AdminLayout>
</template>
