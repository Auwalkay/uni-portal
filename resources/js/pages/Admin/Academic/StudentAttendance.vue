<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    AlertTriangle,
    ArrowRight,
    CalendarCheck,
    Download,
    Search,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

interface AttendanceRow {
    id: string;
    student_id: string;
    course_id: string;
    student_name: string;
    matriculation_number: string;
    course_code: string;
    course_title: string;
    department_name: string;
    sessions_held: number;
    sessions_attended: number;
    percentage: number | null;
}

const props = defineProps<{
    records: {
        data: AttendanceRow[];
        links: any[];
        total?: number;
        from?: number;
        to?: number;
        current_page?: number;
        per_page?: number;
    };
    stats: {
        total_sessions_held: number;
        total_records: number;
        below_threshold_count: number;
    };
    sessions: Array<{ id: string; name: string; academic_stream?: string }>;
    departments: Array<{ id: string; name: string }>;
    filters: {
        session_id?: string | null;
        department_id?: string;
        threshold?: string;
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Student Attendance', href: '/admin/student-attendance' },
];

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || '');
const selectedDepartment = ref(props.filters.department_id || 'all');
const selectedThreshold = ref(props.filters.threshold || 'all');

function applyFilters() {
    router.get(
        '/admin/student-attendance',
        {
            search: search.value || undefined,
            session_id: selectedSession.value || undefined,
            department_id: selectedDepartment.value !== 'all' ? selectedDepartment.value : undefined,
            threshold: selectedThreshold.value !== 'all' ? selectedThreshold.value : undefined,
        },
        { preserveState: true, replace: true }
    );
}

watch(search, debounce(applyFilters, 300));
watch([selectedSession, selectedDepartment, selectedThreshold], applyFilters);

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (selectedSession.value) params.set('session_id', selectedSession.value);
    if (selectedDepartment.value !== 'all') params.set('department_id', selectedDepartment.value);
    if (selectedThreshold.value !== 'all') params.set('threshold', selectedThreshold.value);
    const qs = params.toString();
    return `/admin/student-attendance/export${qs ? `?${qs}` : ''}`;
});
</script>

<template>
    <Head title="Student Attendance Records" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center dark:border-slate-800 dark:bg-slate-900">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-slate-100">
                        Student Attendance Records
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Monitor university-wide lecture attendance rates and identify students below the 75% exam eligibility threshold.
                    </p>
                </div>

                <Button as-child variant="outline" class="min-h-[40px] gap-2">
                    <a :href="exportUrl">
                        <Download class="h-4 w-4" />
                        <span>Export CSV</span>
                    </a>
                </Button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card class="border-slate-200 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-bold tracking-wider text-slate-500 uppercase">
                            Lecture Sessions Held
                        </CardTitle>
                        <CalendarCheck class="h-4 w-4 text-indigo-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_sessions_held }}</div>
                        <p class="text-xs text-muted-foreground">Recorded sessions in view</p>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-bold tracking-wider text-slate-500 uppercase">
                            Enrolled Course Records
                        </CardTitle>
                        <Users class="h-4 w-4 text-emerald-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total_records }}</div>
                        <p class="text-xs text-muted-foreground">Student-course registrations</p>
                    </CardContent>
                </Card>

                <Card class="border-rose-200/70 bg-rose-50/40 dark:border-rose-900/50 dark:bg-rose-950/20">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-bold tracking-wider text-rose-600 uppercase dark:text-rose-400">
                            Below 75% Threshold
                        </CardTitle>
                        <AlertTriangle class="h-4 w-4 text-rose-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black text-rose-700 dark:text-rose-300">{{ stats.below_threshold_count }}</div>
                        <p class="text-xs text-rose-600/80 dark:text-rose-400/80">Ineligible for exam clearance</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Bar -->
            <div class="flex flex-col flex-wrap items-stretch justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center dark:border-slate-800 dark:bg-slate-900">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search student name, matric no., or course code..."
                        class="w-full pl-9"
                    />
                </div>

                <!-- Threshold Switch -->
                <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-950">
                    <button
                        type="button"
                        @click="selectedThreshold = 'all'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                            selectedThreshold === 'all'
                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-800 dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400',
                        ]"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        @click="selectedThreshold = 'eligible'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                            selectedThreshold === 'eligible'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400',
                        ]"
                    >
                        Eligible (&ge;75%)
                    </button>
                    <button
                        type="button"
                        @click="selectedThreshold = 'below'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                            selectedThreshold === 'below'
                                ? 'bg-rose-600 text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400',
                        ]"
                    >
                        Below 75%
                    </button>
                </div>

                <Select v-model="selectedDepartment" v-if="departments.length > 0">
                    <SelectTrigger class="w-full sm:w-[200px]">
                        <SelectValue placeholder="All Departments" />
                    </SelectTrigger>
                    <SelectContent class="max-h-[300px]">
                        <SelectItem value="all">All Departments</SelectItem>
                        <SelectItem v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
                            {{ dept.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedSession">
                    <SelectTrigger class="w-full sm:w-[190px]">
                        <SelectValue placeholder="Select Session" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">
                            {{ s.name }} {{ s.academic_stream ? `(${s.academic_stream})` : '' }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-800/60">
                            <TableRow>
                                <TableHead class="w-12 text-xs">#</TableHead>
                                <TableHead class="text-xs">Student</TableHead>
                                <TableHead class="text-xs">Course</TableHead>
                                <TableHead class="text-xs">Department</TableHead>
                                <TableHead class="text-center text-xs">Attended / Held</TableHead>
                                <TableHead class="text-center text-xs">Rate</TableHead>
                                <TableHead class="text-right text-xs">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(row, idx) in records.data" :key="row.id">
                                <TableCell class="text-xs text-muted-foreground">
                                    {{ ((records.current_page || 1) - 1) * (records.per_page || 20) + idx + 1 }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">{{ row.student_name }}</div>
                                    <div class="font-mono text-[11px] text-muted-foreground">{{ row.matriculation_number }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="mr-1.5 font-mono text-[10px]">{{ row.course_code }}</Badge>
                                    <span class="text-xs font-medium">{{ row.course_title }}</span>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ row.department_name }}</TableCell>
                                <TableCell class="text-center text-xs font-semibold">
                                    {{ row.sessions_attended }} / {{ row.sessions_held }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge
                                        v-if="row.percentage !== null"
                                        :class="row.percentage >= 75
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'"
                                        class="text-[11px] font-bold"
                                    >
                                        {{ row.percentage }}%
                                    </Badge>
                                    <span v-else class="text-xs text-muted-foreground">No sessions</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            :href="route('admin.teaching.courses.show', row.course_id)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                        >
                                            <span>Course</span>
                                            <ArrowRight class="h-3 w-3" />
                                        </Link>
                                        <Link
                                            :href="route('admin.students.show', row.student_id)"
                                            class="text-xs font-semibold text-slate-600 hover:underline dark:text-slate-300"
                                        >
                                            Student
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="records.data.length === 0">
                                <TableCell colspan="7" class="py-12 text-center text-sm text-muted-foreground">
                                    No student attendance records match the selected filters.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="records.links && records.links.length > 3"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-xs dark:border-slate-800"
                >
                    <span class="text-muted-foreground">
                        Showing {{ records.from || 0 }} to {{ records.to || 0 }} of {{ records.total || 0 }} records
                    </span>
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, i) in records.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'rounded-md border px-2.5 py-1 text-xs font-medium transition',
                                    link.active
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="rounded-md border border-slate-200 px-2.5 py-1 text-xs text-slate-400 dark:border-slate-800"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
