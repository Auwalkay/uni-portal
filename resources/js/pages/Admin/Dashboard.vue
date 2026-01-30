<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { BookOpen, CreditCard, Users, GraduationCap, TrendingUp, Calendar, ArrowRight } from 'lucide-vue-next';
import StatsCard from '@/components/StatsCard.vue';

const props = defineProps<{
    stats: {
        total_students: number;
        fresh_students: number;
        revenue: number;
        active_courses: number;
    };
    recentActivity: Array<{
        type: string;
        title: string;
        description: string;
        time: string;
        icon: string;
    }>;
    sessions: Array<{ id: string; name: string }>;
    filters: { session_id: string };
    currentSessionName: string;
    charts: {
        revenue: { labels: string[]; data: number[] };
        faculty: { labels: string[]; data: number[] };
        registration: { labels: string[]; data: number[] };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
];

const selectedSession = ref(props.filters.session_id);

watch(selectedSession, (newSession) => {
    router.get(route('admin.dashboard'), { session_id: newSession }, { preserveState: true, preserveScroll: true });
});

const formatCurrency = (value: number) => {
    return '₦' + new Intl.NumberFormat('en-NG').format(value);
};

const getIcon = (iconName: string) => {
    const icons: Record<string, any> = { Users, CreditCard, BookOpen };
    return icons[iconName] || Users;
};
import BarChart from '@/components/Charts/BarChart.vue';
import DoughnutChart from '@/components/Charts/DoughnutChart.vue';

// Chart Options
const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: { display: true, text: 'Monthly Revenue (₦)' }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right' as const },
    }
};

const revenueChartData = {
    labels: props.charts.revenue.labels,
    datasets: [{
        label: 'Revenue',
        backgroundColor: '#4f46e5',
        data: props.charts.revenue.data
    }]
};

const facultyChartData = {
    labels: props.charts.faculty.labels,
    datasets: [{
        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
        data: props.charts.faculty.data
    }]
};

const registrationChartData = {
    labels: props.charts.registration.labels,
    datasets: [{
        backgroundColor: ['#22c55e', '#ef4444'],
        data: props.charts.registration.data
    }]
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header & Filter -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Dashboard Overview</h1>
                    <p class="text-muted-foreground">
                        Analysis for {{ currentSessionName }} Session
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <select v-model="selectedSession" class="h-9 w-[200px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <option v-for="session in sessions" :key="session.id" :value="session.id">
                            {{ session.name }} Session
                        </option>
                    </select>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Students"
                    :value="stats.total_students.toLocaleString()"
                    description="Active students"
                    :icon="Users"
                />
                <StatsCard
                    title="Fresh Intake"
                    :value="stats.fresh_students.toLocaleString()"
                    :description="`Admitted in ${currentSessionName}`"
                    :icon="GraduationCap"
                    trend="neutral"
                />
                <StatsCard
                    title="Session Revenue"
                    :value="formatCurrency(stats.revenue)"
                    description="Fees collected this session"
                    :icon="CreditCard"
                    trend="up"
                />
                <StatsCard
                    title="Active Courses"
                    :value="stats.active_courses.toLocaleString()"
                    description="Courses with registration"
                    :icon="BookOpen"
                />
            </div>

// ... existing code ...

            <!-- Charts Section -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold">Revenue Trend</h3>
                    <div class="h-[300px]">
                        <BarChart :chart-data="revenueChartData" :chart-options="barOptions" />
                    </div>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold">Student Distribution</h3>
                    <div class="h-[300px]">
                         <DoughnutChart :chart-data="facultyChartData" :chart-options="doughnutOptions" />
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Recent Activity -->
                <div class="col-span-2 rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pb-4 border-b">
                        <h3 class="text-lg font-semibold">Recent Activity</h3>
                        <p class="text-sm text-muted-foreground">Latest system events.</p>
                    </div>
                    <div class="p-6">
                        <div v-if="recentActivity.length > 0" class="space-y-6">
                            <div v-for="(activity, index) in recentActivity" :key="index" class="flex items-start gap-4">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <component :is="getIcon(activity.icon)" class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ activity.title }}</p>
                                    <p class="text-xs text-muted-foreground">{{ activity.description }}</p>
                                </div>
                                <div class="ml-auto text-xs text-muted-foreground">{{ activity.time }}</div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-muted-foreground">
                            No recent activity found for this session.
                        </div>
                    </div>
                </div>

                <!-- Quick Actions or Further Analysis Placeholder -->
                <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="p-6 pb-4 border-b">
                        <h3 class="text-lg font-semibold">Quick Actions</h3>
                         <p class="text-sm text-muted-foreground">Manage ongoing tasks.</p>
                    </div>
                    <div class="p-4 space-y-2">
                         <a :href="route('admin.students.index')" class="flex items-center justify-between p-3 rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <Users class="h-4 w-4" />
                                </div>
                                <span class="text-sm font-medium">Manage Students</span>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:block" />
                        </a>
                        <a :href="route('admin.payments.index')" class="flex items-center justify-between p-3 rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                    <CreditCard class="h-4 w-4" />
                                </div>
                                <span class="text-sm font-medium">Verify Payments</span>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:block" />
                        </a>
                         <a :href="route('admin.sessions.index')" class="flex items-center justify-between p-3 rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                    <Calendar class="h-4 w-4" />
                                </div>
                                <span class="text-sm font-medium">Session Settings</span>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:block" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
