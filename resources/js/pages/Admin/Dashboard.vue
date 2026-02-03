<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { BookOpen, CreditCard, Users, GraduationCap, TrendingUp, Calendar, ArrowRight, UserPlus, FileText, ArrowUpRight, ArrowDownRight, Activity, CalendarClock, MapPin, Building2, Library, School, Building, LineChart } from 'lucide-vue-next';
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
        total_outflow?: number | null;
        net_cash_flow?: number | null;
        active_students?: number | null;
        admissions_funnel?: {
            total_applicants: number;
            screened_applicants: number;
            pending_screening: number;
        } | null;
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
    auth: {
        user: {
            name: string;
            role?: string;
        }
    };
    charts: {
        revenue: { labels: string[]; data: number[] };
        faculty: { labels: string[]; data: number[] };
        level: { labels: string[]; data: number[] };
        program: { labels: string[]; data: number[] };
        staff_department: { labels: string[]; data: number[] };
        financial_trend: { labels: string[]; inflow: number[]; outflow: number[] };
        expense_categories: { labels: string[]; data: number[] };
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
    userRole: string;
 }>();

const user = props.auth?.user;
const roleColorMap: Record<string, string> = {
    admin: 'from-slate-800 to-slate-900',
    finance: 'from-emerald-800 to-emerald-900',
    academic: 'from-indigo-800 to-indigo-900',
    admissions: 'from-amber-800 to-amber-900',
};

const roleLabelMap: Record<string, string> = {
    admin: 'Administrator',
    finance: 'Finance Officer',
    academic: 'Academic Staff',
    admissions: 'Admissions Officer',
};

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
        legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 13, family: "'Inter', sans-serif" },
            bodyFont: { size: 13, family: "'Inter', sans-serif" },
            displayColors: true,
            callbacks: {
                label: (context: any) => {
                    return context.dataset.label + ': ' + formatCurrency(context.raw);
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
        legend: { position: 'bottom' as const, labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } } },
    }
};

const financialTrendChartData = {
    labels: props.charts.financial_trend.labels,
    datasets: [
        {
            label: 'Inflow',
            backgroundColor: '#10b981',
            borderRadius: 6,
            barPercentage: 0.6,
            categoryPercentage: 0.8,
            data: props.charts.financial_trend.inflow
        },
        {
            label: 'Outflow',
            backgroundColor: '#ef4444',
            borderRadius: 6,
            barPercentage: 0.6,
            categoryPercentage: 0.8,
            data: props.charts.financial_trend.outflow
        }
    ]
};

const expenseCategoryChartData = {
    labels: props.charts.expense_categories.labels,
    datasets: [{
        backgroundColor: ['#6366f1', '#ec4899', '#14b8a6', '#f97316', '#8b5cf6', '#3b82f6'],
        borderWidth: 0,
        data: props.charts.expense_categories.data
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
            <!-- Personalized Welcome Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r p-8 text-white shadow-xl mb-2" :class="roleColorMap[userRole] || 'from-slate-800 to-slate-900'">
                <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 backdrop-blur-3xl -mr-20 transform skew-x-12"></div>
                <div class="absolute left-0 bottom-0 h-32 w-32 bg-white/5 rounded-full blur-3xl -ml-16 -mb-16"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <Badge variant="secondary" class="bg-white/20 text-white border-0 hover:bg-white/30 px-3 py-1 mb-2">
                            {{ roleLabelMap[userRole] }} Dashboard
                        </Badge>
                        <h1 class="text-4xl font-extrabold tracking-tight">
                            Welcome back, <span class="text-primary-foreground underline decoration-primary-foreground/30">{{ user?.name.split(' ')[0] }}</span>!
                        </h1>
                        <p class="text-white/80 text-lg max-w-2xl font-medium">
                            Here's what's happening at Auwalkay University for the <span class="font-bold underline">{{ currentSessionName }}</span> session.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/10 min-w-[240px]">
                         <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold text-white/70 uppercase tracking-widest">Selected Session</span>
                            <Calendar class="w-4 h-4 text-white/50" />
                        </div>
                         <Select v-model="selectedSession">
                            <SelectTrigger class="w-full h-11 bg-white/20 border-0 text-white focus:ring-offset-slate-900">
                                <SelectValue placeholder="Select Session" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                    {{ session.name }} Session
                                </SelectItem>
                            </SelectContent>
                        </Select>
                         <p class="text-[10px] text-white/50 mt-3 flex items-center gap-1.5 font-mono">
                            <Activity class="w-3 h-3" /> System Status: Optimal Performance
                        </p>
                    </div>
                </div>
            </div>

            <!-- Role-Centric "Hero" Metrics -->
            <div class="grid gap-6">
                <!-- FINANCE HERO -->
                <div v-if="userRole === 'finance'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="bg-emerald-600 text-white border-0 shadow-lg relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-24 bg-white/10 -mr-12 transform skew-x-12 transition-transform group-hover:translate-x-4"></div>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-emerald-100 uppercase tracking-wider">Net Cash Flow</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold">{{ formatCurrency(stats.net_cash_flow || 0) }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-emerald-200">
                                <TrendingUp class="w-3 h-3" /> Inflow - Outflow
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-emerald-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Revenue</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(stats.revenue || 0) }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-emerald-600 font-bold">
                                <ArrowUpRight class="w-3 h-3" /> {{ stats.revenue_growth }}% <span class="text-muted-foreground font-normal">vs last session</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-rose-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Outflow</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(stats.total_outflow || 0) }}</div>
                             <p class="text-xs text-muted-foreground mt-2">Expenses & Payroll</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-amber-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Outstanding Fees</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-amber-600">{{ formatCurrency(stats.outstanding_fees || 0) }}</div>
                             <div class="h-1.5 w-full bg-amber-100 dark:bg-amber-900/30 rounded-full mt-3 overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: 45%"></div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ACADEMIC HERO -->
                <div v-if="userRole === 'academic'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="bg-indigo-600 text-white border-0 shadow-lg relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-24 bg-white/10 -mr-12 transform skew-x-12 transition-transform group-hover:translate-x-4"></div>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-indigo-100 uppercase tracking-wider">Classes Today</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-4xl font-bold">{{ lecturerStats?.classes_today || 0 }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-indigo-200">
                                <Calendar class="w-3 h-3" /> {{ new Date().toLocaleDateString('en-GB', { weekday: 'long' }) }}
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-indigo-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Active Students</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ lecturerStats?.total_students || 0 }}</div>
                             <p class="text-xs text-muted-foreground mt-2">Across all your courses</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-violet-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Allocated Courses</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ lecturerStats?.total_courses || 0 }}</div>
                             <p class="text-xs text-muted-foreground mt-2">This academic session</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-emerald-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Portal Access</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2 text-emerald-600 font-bold text-lg py-1">
                                <div class="h-2.5 w-2.5 bg-emerald-500 rounded-full animate-pulse"></div>
                                LIVE & ONLINE
                            </div>
                            <p class="text-xs text-muted-foreground mt-1.5">No maintenance scheduled.</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- ADMISSIONS HERO -->
                <div v-if="userRole === 'admissions'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="bg-amber-600 text-white border-0 shadow-lg relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-24 bg-white/10 -mr-12 transform skew-x-12 transition-transform group-hover:translate-x-4"></div>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-amber-100 uppercase tracking-wider">Incoming Apps</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-4xl font-bold">{{ stats.admissions_funnel?.total_applicants || 0 }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-amber-200">
                                <FileText class="w-3 h-3" /> All applicants
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-amber-500 shadow-sm transition-all hover:shadow-md cursor-pointer">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Admitted (Proxy)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.admissions_funnel?.screened_applicants || 0 }}</div>
                             <p class="text-xs text-muted-foreground mt-2">Applicants with records</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-primary shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Pending Review</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-rose-600">{{ stats.admissions_funnel?.pending_screening || 0 }}</div>
                             <p class="text-xs text-muted-foreground mt-2 font-medium">Action required</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-indigo-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Compliance</CardTitle>
                        </CardHeader>
                        <CardContent>
                             <div class="text-3xl font-bold text-indigo-700">{{ stats.registration_compliance }}%</div>
                             <div class="h-1.5 w-full bg-indigo-100 rounded-full mt-3 overflow-hidden">
                                <div class="h-full bg-indigo-600 rounded-full" :style="{ width: `${stats.registration_compliance}%` }"></div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ADMIN HERO -->
                <div v-if="userRole === 'admin'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card class="bg-slate-900 dark:bg-slate-950 text-white border-0 shadow-xl relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-24 bg-white/5 -mr-12 transform skew-x-12"></div>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-slate-400 uppercase tracking-wider">Institutional Scale</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-4xl font-extrabold">{{ (stats.total_students || 0).toLocaleString() }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-emerald-400 font-bold">
                                <Users class="w-3 h-3" /> Total Enrolled Students
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-indigo-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Active Programs</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.structural?.programs || 0 }}</div>
                             <p class="text-xs text-muted-foreground mt-2">Across {{ stats.structural?.faculties }} Faculties</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-emerald-500 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Human Resources</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.structural?.staff || 0 }}</div>
                             <p class="text-xs text-muted-foreground mt-2">Academic & Non-Academic</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-white dark:bg-slate-900 border-l-4 border-l-primary shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Session Revenue</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold text-primary">{{ formatCurrency(stats.revenue || 0) }}</div>
                             <p class="text-xs text-muted-foreground mt-2 font-medium">Current session performance</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Secondary Insights Section (Pulse) -->
             <div class="grid md:grid-cols-2 gap-6" :style="{ order: userRole === 'admin' ? 3 : 5 }">
                <!-- Institutional Structure -->
                <Card class="border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-800/50">
                        <CardTitle class="text-lg flex items-center gap-2">
                             <Building2 class="w-5 h-5 text-indigo-500" /> University Infrastructure
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Faculties</span>
                                <div class="text-2xl font-bold">{{ stats.structural?.faculties }}</div>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Departments</span>
                                <div class="text-2xl font-bold">{{ stats.structural?.departments }}</div>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Academic Staff</span>
                                <div class="text-xl font-bold text-emerald-600">{{ stats.structural?.academic_staff }}</div>
                            </div>
                             <div class="space-y-1">
                                <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Non-Academic</span>
                                <div class="text-xl font-bold text-indigo-600">{{ stats.structural?.non_academic_staff }}</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t flex items-center justify-between text-xs text-muted-foreground">
                             <div class="flex items-center gap-1.5">
                                <School class="w-3.5 h-3.5" /> Total Programs: <span class="font-bold text-slate-900 dark:text-white">{{ stats.structural?.programs }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Student Demographics & Compliance -->
                <Card class="border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-800/50">
                        <CardTitle class="text-lg flex items-center gap-2">
                             <Users class="w-5 h-5 text-blue-500" /> Student Population
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-6">
                         <div v-if="stats.gender_distribution" class="mb-6">
                             <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Gender Ratio</span>
                                <span class="text-xs font-mono">{{ stats.gender_distribution.male }}% M / {{ stats.gender_distribution.female }}% F</span>
                             </div>
                            <div class="flex h-2.5 w-full rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                                <div class="bg-blue-500 h-full transition-all duration-1000" :style="{ width: `${stats.gender_distribution.male}%` }"></div>
                                <div class="bg-pink-500 h-full transition-all duration-1000" :style="{ width: `${stats.gender_distribution.female}%` }"></div>
                            </div>
                         </div>

                         <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                     <div class="h-8 w-8 rounded bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                        <BookOpen class="w-4 h-4" />
                                     </div>
                                     <div>
                                        <p class="text-xs font-bold text-slate-500">Course Registration</p>
                                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ stats.registration_compliance }}% Compliance</p>
                                     </div>
                                </div>
                                <div class="h-10 w-10 flex items-center justify-center relative">
                                     <svg class="h-10 w-10 transform -rotate-90">
                                        <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="4" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                        <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="4" fill="transparent" :stroke-dasharray="100.5" :stroke-dashoffset="100.5 - (stats.registration_compliance || 0)" class="text-indigo-600" />
                                     </svg>
                                </div>
                            </div>
                         </div>
                    </CardContent>
                </Card>
             </div>

            <!-- Analytics Hub -->
            <div class="space-y-6" :style="{ order: 8 }">
                <div class="flex items-center justify-between border-b pb-2">
                     <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600">
                            <LineChart class="w-6 h-6" />
                        </div>
                        Analytics Hub
                    </h2>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Main Financial/Admissions Trend -->
                    <Card class="lg:col-span-2 border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <CardHeader class="flex flex-row items-center justify-between pb-4">
                            <div>
                                <CardTitle class="text-lg">Financial Performance</CardTitle>
                                <p class="text-xs text-muted-foreground">Monthly Inflow vs Outflow</p>
                            </div>
                             <Badge variant="outline" class="font-mono text-[10px] text-emerald-600 border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20">LIVE DATA</Badge>
                        </CardHeader>
                        <CardContent class="h-[350px]">
                            <BarChart v-if="charts.financial_trend.inflow.length > 0 || charts.financial_trend.labels.length > 0" :chart-data="financialTrendChartData" :options="barOptions" />
                             <div v-else class="h-full flex items-center justify-center text-muted-foreground">
                                <p>No financial trend data available for this session.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Distribution -->
                    <Card class="border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg">Expense Breakdown</CardTitle>
                            <p class="text-xs text-muted-foreground">By category distribution</p>
                        </CardHeader>
                        <CardContent class="h-[350px]">
                            <DoughnutChart v-if="charts.expense_categories.data.length > 0" :chart-data="expenseCategoryChartData" :options="doughnutOptions" />
                             <div v-else class="h-full flex items-center justify-center text-muted-foreground">
                                <p>No expense data available.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                 <div class="grid md:grid-cols-3 gap-6">
                    <Card class="md:col-span-1 border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-md">Faculty Distribution</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[250px]">
                            <DoughnutChart :chart-data="facultyChartData" :options="doughnutOptions" />
                        </CardContent>
                    </Card>

                    <Card class="md:col-span-1 border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <CardHeader class="pb-2">
                             <CardTitle class="text-md">Enrollment by Level</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[250px]">
                            <BarChart :chart-data="levelChartData" :options="{ ...barOptions, plugins: { ...barOptions.plugins, legend: { display: false } } }" />
                        </CardContent>
                    </Card>

                    <Card class="md:col-span-1 border-0 shadow-sm bg-white dark:bg-slate-900 overflow-hidden">
                        <CardHeader class="pb-2">
                             <CardTitle class="text-md">Top Programs</CardTitle>
                        </CardHeader>
                        <CardContent class="h-[250px]">
                            <DoughnutChart :chart-data="programChartData" :options="doughnutOptions" />
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Lecturer Dashboard Section -->
            <div v-if="lecturerStats" :style="{ order: userRole === 'academic' ? 1 : 10 }">
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

            <!-- My Timetable Widget (Staff) -->
            <div v-if="myTimetable" class="w-full" :style="{ order: userRole === 'academic' ? 2 : 11 }">
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
