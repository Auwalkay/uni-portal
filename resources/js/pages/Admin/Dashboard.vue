<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { BookOpen, CreditCard, Users, GraduationCap, TrendingUp, Calendar, ArrowRight, UserPlus, FileText, ArrowUpRight, ArrowDownRight, Activity } from 'lucide-vue-next';
import StatsCard from '@/components/StatsCard.vue';
import BarChart from '@/components/Charts/BarChart.vue';
import DoughnutChart from '@/components/Charts/DoughnutChart.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

const props = defineProps<{
    stats: {
        total_students: number;
        fresh_students: number;
        applications: number;
        revenue: number;
        active_courses: number;
        revenue_growth: number;
        student_growth: number;
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
    };
}>();

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
            backgroundColor: '#1f2937',
            padding: 12,
            cornerRadius: 8,
            titleFont: { size: 13 },
            bodyFont: { size: 12 }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: { color: '#f3f4f6' },
            ticks: { font: { size: 11 } }
        },
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 } }
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
        borderRadius: 6,
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

            <!-- Main Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Students Card -->
                <Card v-if="stats.total_students !== null" class="hover:shadow-md transition-shadow duration-200">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Students</CardTitle>
                        <Users class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_students.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                             <span class="inline-flex items-center text-emerald-600 font-medium bg-emerald-50 px-1 py-0.5 rounded">
                                <ArrowUpRight class="w-3 h-3 mr-0.5" /> +2.5%
                             </span>
                             <span class="opacity-75">vs last month</span>
                        </p>
                    </CardContent>
                </Card>

                <!-- Fresh Intake Card -->
                <Card v-if="stats.fresh_students !== null" class="hover:shadow-md transition-shadow duration-200">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                         <CardTitle class="text-sm font-medium text-muted-foreground">Fresh Intake</CardTitle>
                        <UserPlus class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                         <div class="text-2xl font-bold">{{ stats.fresh_students.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                            <span v-if="stats.student_growth >= 0" class="inline-flex items-center text-emerald-600 font-medium bg-emerald-50 px-1 py-0.5 rounded">
                                <ArrowUpRight class="w-3 h-3 mr-0.5" /> {{ stats.student_growth }}%
                            </span>
                             <span v-else class="inline-flex items-center text-rose-600 font-medium bg-rose-50 px-1 py-0.5 rounded">
                                <ArrowDownRight class="w-3 h-3 mr-0.5" /> {{ stats.student_growth }}%
                            </span>
                            <span class="opacity-75">vs last session</span>
                        </p>
                    </CardContent>
                </Card>
                
                 <!-- Revenue Card -->
                <Card v-if="stats.revenue !== null" class="hover:shadow-md transition-shadow duration-200">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                        <CreditCard class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold tracking-tight">{{ formatCurrency(stats.revenue) }}</div>
                         <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                             <span v-if="stats.revenue_growth >= 0" class="inline-flex items-center text-emerald-600 font-medium bg-emerald-50 px-1 py-0.5 rounded">
                                <ArrowUpRight class="w-3 h-3 mr-0.5" /> {{ stats.revenue_growth }}%
                            </span>
                            <span v-else class="inline-flex items-center text-rose-600 font-medium bg-rose-50 px-1 py-0.5 rounded">
                                <ArrowDownRight class="w-3 h-3 mr-0.5" /> {{ stats.revenue_growth }}%
                            </span>
                            <span class="opacity-75">vs last session</span>
                        </p>
                    </CardContent>
                </Card>

                 <!-- Applications Card -->
                 <Card v-if="stats.applications !== null" class="hover:shadow-md transition-shadow duration-200">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Pending Applications</CardTitle>
                        <FileText class="h-4 w-4 text-amber-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.applications.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Applications under review
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
            <div v-if="charts.revenue.data.length > 0 || charts.faculty.data.length > 0" class="grid gap-6 md:grid-cols-7">
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

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Recent Activity Feed -->
                <Card class="md:col-span-2 hover:shadow-md transition-shadow duration-200">
                    <CardHeader class="flex flex-row items-center justify-between">
                         <CardTitle class="text-lg flex items-center gap-2">
                            <Activity class="w-5 h-5 text-primary" />
                            Recent Activity
                        </CardTitle>
                        <Button variant="ghost" size="sm" class="h-8 text-xs">View All</Button>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div v-for="(item, index) in recentActivity" :key="index" class="flex gap-4 group">
                                <div class="relative mt-0.5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full border bg-secondary group-hover:bg-primary/10 group-hover:border-primary/20 transition-colors">
                                         <CreditCard v-if="item.type === 'payment'" class="h-4 w-4 text-emerald-600" />
                                         <UserPlus v-else class="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div v-if="index !== recentActivity.length - 1" class="absolute left-1/2 top-9 h-full w-px -translate-x-1/2 bg-border group-hover:bg-primary/20 transition-colors"></div>
                                </div>
                                <div class="flex flex-1 flex-col gap-1 pb-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-foreground">{{ item.title }}</p>
                                        <span class="text-xs text-muted-foreground">{{ item.time_ago }}</span>
                                    </div>
                                    <p class="text-xs text-muted-foreground line-clamp-1">{{ item.description }}</p>
                                </div>
                            </div>
                            
                            <div v-if="recentActivity.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
                                <Activity class="h-10 w-10 opacity-20 mb-2" />
                                <p class="text-sm">No recent activity detected.</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions / Quick Links -->
                <div class="space-y-6">
                     <Card v-if="stats.total_students !== null || stats.revenue !== null" class="bg-gradient-to-br from-primary to-primary/90 text-primary-foreground border-none">
                        <CardHeader>
                            <CardTitle class="text-lg">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-2">
                             <Button v-if="stats.total_students !== null" variant="secondary" class="w-full justify-start text-primary hover:bg-white/95" as-child>
                                <a :href="route('admin.students.create')">
                                    <UserPlus class="mr-2 h-4 w-4" /> Enroll New Student
                                </a>
                            </Button>
                            <Button v-if="stats.revenue !== null" variant="secondary" class="w-full justify-start text-primary hover:bg-white/95" as-child>
                                <a :href="route('admin.finance.index')">
                                    <CreditCard class="mr-2 h-4 w-4" /> Manage Fees
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">System Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Current Session</span>
                                <span class="font-medium">{{ currentSessionName }}</span>
                            </div>
                             <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Registration</span>
                                <Badge variant="outline" class="text-emerald-600 bg-emerald-50 border-emerald-200">Active</Badge>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Portal Status</span>
                                <Badge variant="outline" class="text-green-600 bg-green-50 border-green-200">Online</Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
