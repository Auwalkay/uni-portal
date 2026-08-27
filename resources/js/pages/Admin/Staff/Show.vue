<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    User, Mail, Building2, Briefcase, GraduationCap, Shield, ArrowLeft, 
    Calendar, Pencil, ShieldCheck, UserCircle, Building, Hash, BookOpen, 
    BarChart3, Clock, AlertCircle, CalendarClock, MapPin, Download, RefreshCw,
    CheckCircle2, XCircle, Clock3, CalendarDays, Filter, Sparkles, TrendingUp,
    Banknote, Check
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { route } from 'ziggy-js';

const props = defineProps<{
    staff: {
        id: string;
        name: string;
        email: string;
        roles: Array<{ id: string; name: string }>;
        staff: {
            id: string;
            staff_number: string;
            designation: string;
            is_academic: boolean;
            department: {
                name: string;
                faculty: {
                    name: string;
                } | null;
            } | null;
            allocations: Array<{
                id: string;
                course: {
                    code: string;
                    title: string;
                    unit: number;
                };
                session: {
                    name: string;
                };
            }>;
        } | null;
    };
    timetable?: Array<any>;
    payslips?: Array<any>;
    attendanceData?: {
        weekly: Array<{
            week: string;
            start_date: string;
            records: Array<{
                id: string;
                date: string;
                day_name: string;
                formatted_date: string;
                clock_in: string | null;
                clock_out: string | null;
                status: string;
                notes: string | null;
            }>;
            present_count: number;
            total_count: number;
        }>;
        stats: {
            present: number;
            late: number;
            absent: number;
            on_leave: number;
            total: number;
            rate: number;
        };
        filters: {
            month: number;
            year: number;
        };
    };
}>();

const selectedMonth = ref(String(props.attendanceData?.filters?.month || new Date().getMonth() + 1));
const selectedYear = ref(String(props.attendanceData?.filters?.year || new Date().getFullYear()));

const months = [
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const years = computed(() => {
    const currentYr = new Date().getFullYear();
    return [currentYr - 2, currentYr - 1, currentYr, currentYr + 1].map(y => String(y));
});

const filterAttendance = () => {
    router.get(route('admin.staff.show', props.staff.id), {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

const formatTime = (time: string | null) => {
    if (!time) return '---';
    const parts = time.split(':');
    let h = parseInt(parts[0], 10);
    if (isNaN(h)) return time;
    const m = parts[1] || '00';
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h.toString().padStart(2, '0')}:${m} ${ampm}`;
};

const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(Number(amount));
};

const getMonthName = (month: number) => {
    return new Date(2000, month - 1).toLocaleString('default', { month: 'long' });
};

const getClassesForDay = (day: string) => {
    return props.timetable?.filter((t: any) => t.day === day) || [];
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const totalUnits = computed(() => {
    if (!props.staff.staff?.allocations) return 0;
    return props.staff.staff.allocations.reduce((acc, curr) => acc + (curr.course?.unit || 0), 0);
});

const teachingLoadWithPercentage = computed(() => {
    const units = totalUnits.value;
    const maxLoad = 15;
    return Math.min((units / maxLoad) * 100, 100);
});

const getAttendanceBadge = (status: string) => {
    switch (status) {
        case 'present': return { label: 'Present', color: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' };
        case 'late': return { label: 'Late', color: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300' };
        case 'absent': return { label: 'Absent', color: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950 dark:text-rose-300' };
        case 'on_leave': return { label: 'On Leave', color: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300' };
        default: return { label: status, color: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};

const breadcrumbs = [
    { title: 'Staff Management', href: '/admin/staff' },
    { title: 'Staff Profile', href: '#' }
];

const resetPassword = () => {
    if (confirm('Are you sure you want to reset this staff member\'s password? A new random password will be generated and emailed to them immediately.')) {
        router.post(route('admin.staff.reset_password', props.staff.id));
    }
};
</script>

<template>
    <Head :title="`${staff.name} - Staff Profile`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-6 space-y-8 w-full max-w-[1600px] mx-auto">
            
            <!-- Hero Header Section -->
            <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl overflow-hidden shadow-xl text-white border border-slate-800">
                <div class="absolute inset-0 bg-grid-white/[0.05] bg-[length:24px_24px]"></div>
                
                <div class="relative p-8 md:p-10 flex flex-col md:flex-row items-center md:items-end justify-between gap-8">
                    <div class="flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-left">
                        <Avatar class="h-32 w-32 border-4 border-white/20 shadow-2xl rounded-2xl ring-4 ring-black/20">
                            <AvatarFallback class="bg-indigo-600 text-white text-4xl font-extrabold rounded-2xl">
                                {{ staff.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <Badge variant="secondary" class="bg-indigo-500/20 text-indigo-200 border-indigo-500/30 px-3 py-1 font-bold">
                                    {{ staff.staff?.is_academic ? 'Academic Faculty' : 'Administrative Staff' }}
                                </Badge>
                                <Badge variant="outline" class="border-emerald-500/40 text-emerald-300 bg-emerald-500/10 px-3 py-1">
                                    <CheckCircle2 class="w-3.5 h-3.5 mr-1" /> Active Status
                                </Badge>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white">{{ staff.name }}</h1>
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-slate-300 text-sm">
                                <span class="flex items-center gap-1.5"><Briefcase class="w-4 h-4 text-indigo-400" /> {{ staff.staff?.designation || 'Staff Member' }}</span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1.5"><Mail class="w-4 h-4 text-indigo-400" /> {{ staff.email }}</span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1.5"><Hash class="w-4 h-4 text-indigo-400" /> {{ staff.staff?.staff_number || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Button variant="outline" type="button" @click="resetPassword" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-200 border-amber-500/30 font-bold backdrop-blur-sm">
                            <RefreshCw class="w-4 h-4 mr-2" /> Reset Password
                        </Button>
                        <Button variant="default" as-child class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30">
                            <Link :href="route('admin.staff.edit', staff.id)">
                                <Pencil class="w-4 h-4 mr-2" /> Edit Profile
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Content Grid Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                
                <!-- Left Sidebar Info (1/4) -->
                <div class="space-y-6">
                    <Card class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground">Departmental Info</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Faculty</div>
                                <div class="font-bold flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                    <Building class="w-4 h-4 text-indigo-600" />
                                    {{ staff.staff?.department?.faculty?.name || 'N/A' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Department</div>
                                <div class="font-bold flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                    <Building2 class="w-4 h-4 text-orange-600" />
                                    {{ staff.staff?.department?.name || 'Unassigned' }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground">System Access & Roles</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div class="flex flex-wrap gap-2">
                                <Badge v-for="role in staff.roles" :key="role.id" variant="secondary" class="font-semibold py-1 px-2.5">
                                    <Shield class="w-3 h-3 mr-1 text-primary" />
                                    {{ formatRoleName(role.name) }}
                                </Badge>
                            </div>
                        </CardContent>
                        <CardFooter class="bg-muted/10 border-t p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium">
                                <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                Account Secured & Verified
                            </div>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Right Main Content Tabs (3/4) -->
                <div class="xl:col-span-3">
                    <Tabs default-value="overview" class="w-full">
                        <TabsList class="w-full justify-start border-b rounded-none h-14 bg-transparent p-0 mb-6 gap-8">
                            <TabsTrigger value="overview" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base">
                                Overview
                            </TabsTrigger>
                            <TabsTrigger value="attendance" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base flex items-center gap-2">
                                <Clock class="w-4 h-4" /> Attendance Logs
                            </TabsTrigger>
                            <TabsTrigger value="academic" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base" v-if="staff.staff?.is_academic">
                                Teaching & Research
                            </TabsTrigger>
                            <TabsTrigger value="payslips" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base">
                                Payslips
                            </TabsTrigger>
                            <TabsTrigger value="activity" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base">
                                Activity Log
                            </TabsTrigger>
                        </TabsList>

                        <!-- Overview Tab -->
                        <TabsContent value="overview" class="space-y-6">
                            <div class="grid md:grid-cols-3 gap-6">
                                <Card class="shadow-sm">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase">Employment Status</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-emerald-600">Active</div>
                                        <p class="text-xs text-muted-foreground mt-1">Full-time Employee</p>
                                    </CardContent>
                                </Card>

                                <Card class="shadow-sm">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase">Primary Role</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ staff.staff?.is_academic ? 'Academic' : 'Non-Academic' }}</div>
                                        <p class="text-xs text-muted-foreground mt-1">{{ staff.staff?.designation || 'Staff' }}</p>
                                    </CardContent>
                                </Card>

                                <Card class="shadow-sm">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase">Attendance Rate</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-indigo-600">{{ attendanceData?.stats?.rate || 0 }}%</div>
                                        <p class="text-xs text-muted-foreground mt-1">{{ attendanceData?.stats?.present || 0 }} Days Present / {{ attendanceData?.stats?.total || 0 }} Logged</p>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <!-- Attendance Tab (Weekly Grouping + Month/Year Filter) -->
                        <TabsContent value="attendance" class="space-y-6">
                            <!-- Filter Bar & Summary Cards -->
                            <Card class="border shadow-sm">
                                <CardHeader class="pb-4 border-b">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                        <div>
                                            <CardTitle class="text-lg font-bold flex items-center gap-2">
                                                <CalendarDays class="w-5 h-5 text-indigo-600" />
                                                Attendance History
                                            </CardTitle>
                                            <CardDescription>Filtered attendance records grouped by week.</CardDescription>
                                        </div>

                                        <!-- Month & Year Filter Controls -->
                                        <div class="flex items-center gap-3 w-full md:w-auto">
                                            <div class="w-36">
                                                <Select v-model="selectedMonth" @update:modelValue="filterAttendance">
                                                    <SelectTrigger class="h-9 font-semibold">
                                                        <SelectValue placeholder="Month" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="w-28">
                                                <Select v-model="selectedYear" @update:modelValue="filterAttendance">
                                                    <SelectTrigger class="h-9 font-semibold">
                                                        <SelectValue placeholder="Year" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="y in years" :key="y" :value="y">{{ y }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>
                                </CardHeader>

                                <CardContent class="p-6">
                                    <!-- Attendance Metrics Cards -->
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                                            <div class="text-xs font-bold text-slate-500 uppercase">Total Days</div>
                                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ attendanceData?.stats?.total || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900">
                                            <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase">Present</div>
                                            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ attendanceData?.stats?.present || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-900">
                                            <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase">Late</div>
                                            <div class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1">{{ attendanceData?.stats?.late || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900">
                                            <div class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase">Absent</div>
                                            <div class="text-2xl font-black text-rose-700 dark:text-rose-300 mt-1">{{ attendanceData?.stats?.absent || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-900 col-span-2 md:col-span-1">
                                            <div class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase">Attendance Rate</div>
                                            <div class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-1">{{ attendanceData?.stats?.rate || 0 }}%</div>
                                        </div>
                                    </div>

                                    <!-- Weekly Grouped Attendance Cards -->
                                    <div v-if="attendanceData?.weekly && attendanceData.weekly.length > 0" class="space-y-6">
                                        <div v-for="weekGroup in attendanceData.weekly" :key="weekGroup.week" class="border rounded-xl overflow-hidden bg-card shadow-sm">
                                            <!-- Week Header -->
                                            <div class="bg-muted/30 p-4 border-b flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <Calendar class="w-4 h-4 text-indigo-600" />
                                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ weekGroup.week }}</span>
                                                </div>
                                                <Badge variant="outline" class="font-semibold bg-background">
                                                    {{ weekGroup.present_count }} / {{ weekGroup.total_count }} Days Present
                                                </Badge>
                                            </div>

                                            <!-- Week Daily Records Table -->
                                            <Table>
                                                <TableHeader>
                                                    <TableRow class="bg-muted/10 text-xs">
                                                        <TableHead class="font-bold">Date</TableHead>
                                                        <TableHead class="font-bold">Day</TableHead>
                                                        <TableHead class="font-bold">Clock In</TableHead>
                                                        <TableHead class="font-bold">Clock Out</TableHead>
                                                        <TableHead class="font-bold">Status</TableHead>
                                                        <TableHead class="font-bold">Notes</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow v-for="rec in weekGroup.records" :key="rec.id" class="hover:bg-muted/30">
                                                        <TableCell class="font-medium text-xs">{{ rec.formatted_date }}</TableCell>
                                                        <TableCell class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ rec.day_name }}</TableCell>
                                                        <TableCell class="text-xs font-mono">{{ formatTime(rec.clock_in) }}</TableCell>
                                                        <TableCell class="text-xs font-mono">{{ formatTime(rec.clock_out) }}</TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline" :class="`font-bold text-[11px] px-2 py-0.5 ${getAttendanceBadge(rec.status).color}`">
                                                                {{ getAttendanceBadge(rec.status).label }}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell class="text-xs text-muted-foreground">{{ rec.notes || '---' }}</TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </div>

                                    <!-- Empty Attendance State -->
                                    <div v-else class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border border-dashed rounded-xl">
                                        <Clock class="w-12 h-12 mb-3 opacity-20" />
                                        <h3 class="font-bold text-base text-slate-800 dark:text-slate-200">No Attendance Records Found</h3>
                                        <p class="text-xs max-w-sm mt-1 text-slate-500">There are no logged attendance entries for this staff member in {{ getMonthName(Number(selectedMonth)) }} {{ selectedYear }}.</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Academic Tab -->
                        <TabsContent value="academic" class="space-y-6" v-if="staff.staff?.is_academic">
                             <div class="grid md:grid-cols-3 gap-6">
                                <Card class="md:col-span-1 bg-slate-900 text-white border-0 shadow-lg">
                                    <CardHeader>
                                        <CardTitle class="text-sm font-normal text-slate-400 uppercase tracking-wider">Total Teaching Load</CardTitle>
                                        <div class="text-5xl font-black mt-2 text-white">{{ totalUnits }} <span class="text-base font-normal text-slate-400">Units</span></div>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-xs text-slate-400 font-semibold">
                                                <span>Workload Capacity</span>
                                                <span>{{ Math.round(teachingLoadWithPercentage) }}%</span>
                                            </div>
                                            <Progress :model-value="teachingLoadWithPercentage" class="h-2 bg-slate-800" />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card class="md:col-span-2 shadow-sm">
                                    <CardHeader>
                                        <CardTitle class="text-base font-bold">Teaching Assignment</CardTitle>
                                        <CardDescription>Courses allocated for the current academic session.</CardDescription>
                                    </CardHeader>
                                    <CardContent class="p-0">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead class="w-[100px] font-bold">Code</TableHead>
                                                    <TableHead class="font-bold">Course Title</TableHead>
                                                    <TableHead class="font-bold">Session</TableHead>
                                                    <TableHead class="text-right font-bold">Units</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow v-for="allocation in staff.staff?.allocations" :key="allocation.id">
                                                    <TableCell class="font-semibold text-xs">{{ allocation.course?.code }}</TableCell>
                                                    <TableCell class="text-xs">{{ allocation.course?.title }}</TableCell>
                                                    <TableCell><Badge variant="outline" class="font-semibold">{{ allocation.session?.name }}</Badge></TableCell>
                                                    <TableCell class="text-right font-bold text-xs">{{ allocation.course?.unit }}</TableCell>
                                                </TableRow>
                                                <TableRow v-if="!staff.staff?.allocations?.length">
                                                    <TableCell colspan="4" class="h-24 text-center text-muted-foreground text-xs">
                                                        No courses assigned yet.
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </CardContent>
                                </Card>
                             </div>

                            <!-- Timetable Section -->
                            <Card class="shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2 text-base font-bold">
                                        <CalendarClock class="w-5 h-5 text-indigo-600" />
                                        Weekly Timetable Schedule
                                    </CardTitle>
                                    <CardDescription>Scheduled classes based on allocated courses.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div v-if="!timetable || timetable.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground border rounded-lg border-dashed">
                                        <CalendarClock class="w-10 h-10 mb-3 opacity-20" />
                                        <p class="font-medium text-sm">No classes scheduled</p>
                                        <p class="text-xs text-muted-foreground mt-1">Allocated courses have not been added to the timetable for the current session.</p>
                                    </div>
                                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                                        <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day" 
                                            class="bg-slate-50 dark:bg-slate-900 rounded-xl border overflow-hidden flex flex-col min-h-[200px]"
                                        >
                                            <div class="bg-indigo-50/60 dark:bg-indigo-950/20 p-3 border-b border-indigo-100 dark:border-indigo-900 flex items-center justify-between">
                                                <span class="font-bold text-indigo-900 dark:text-indigo-400 uppercase text-xs tracking-wider">{{ day }}</span>
                                                <span class="text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full shadow-sm">
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
                        </TabsContent>

                        <!-- Payslips Tab -->
                        <TabsContent value="payslips" class="space-y-6">
                            <Card class="shadow-sm">
                                <CardHeader class="border-b">
                                    <CardTitle class="text-base font-bold">Payslip History</CardTitle>
                                    <CardDescription>View and download staff monthly payment records.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-0">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-muted/10 text-xs">
                                                <TableHead class="font-bold">Period</TableHead>
                                                <TableHead class="font-bold">Basic Salary</TableHead>
                                                <TableHead class="font-bold">Allowances</TableHead>
                                                <TableHead class="font-bold">Deductions</TableHead>
                                                <TableHead class="font-bold">Net Salary</TableHead>
                                                <TableHead class="font-bold">Status</TableHead>
                                                <TableHead class="text-right font-bold pr-6">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="payslip in payslips" :key="payslip.id" class="hover:bg-muted/30">
                                                <TableCell class="font-bold text-xs">
                                                    {{ getMonthName(payslip.payroll.month) }} {{ payslip.payroll.year }}
                                                </TableCell>
                                                <TableCell class="text-xs">{{ formatCurrency(payslip.basic_salary) }}</TableCell>
                                                <TableCell class="text-xs text-emerald-600 font-semibold">+ {{ formatCurrency(payslip.total_allowances) }}</TableCell>
                                                <TableCell class="text-xs text-rose-600 font-semibold">- {{ formatCurrency(payslip.total_deductions) }}</TableCell>
                                                <TableCell class="text-xs font-black text-slate-900 dark:text-white">{{ formatCurrency(payslip.net_salary) }}</TableCell>
                                                <TableCell>
                                                    <Badge :variant="payslip.payroll.paid_at ? 'default' : 'secondary'" class="font-bold text-[10px]">
                                                        {{ payslip.payroll.paid_at ? 'Paid' : 'Pending' }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell class="text-right pr-6">
                                                    <a :href="route('admin.finance.payroll.payslip.download', { payroll: payslip.payroll.id, payrollItem: payslip.id })" target="_blank">
                                                        <Button variant="ghost" size="sm" class="h-8 font-bold">
                                                            <Download class="w-3.5 h-3.5 mr-1" />
                                                            Download
                                                        </Button>
                                                    </a>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="!payslips?.length">
                                                <TableCell colspan="7" class="h-24 text-center text-muted-foreground text-xs">
                                                    No payslips found for this staff member.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Activity Log Tab -->
                        <TabsContent value="activity">
                             <div class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground border rounded-xl border-dashed bg-card">
                                <Clock class="w-12 h-12 mb-3 opacity-20" />
                                <h3 class="font-bold text-base text-slate-800 dark:text-slate-200">No Recent Activity</h3>
                                <p class="text-xs max-w-sm mt-1 text-slate-500">Activity logs including login history and administrative actions will be displayed here.</p>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
