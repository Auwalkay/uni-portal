<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Search,
    GraduationCap,
    FileText,
    Settings2,
    UserSearch,
    ArrowRight,
    FilterX,
    Download,
    CheckCircle2,
    Clock,
    BookOpen,
    Users,
    Sparkles,
    Copy,
    Check,
    Layers,
    X,
    Eye
} from 'lucide-vue-next';
import debounce from 'lodash/debounce';
import { route } from 'ziggy-js';

interface RegisteredCourse {
    id: string;
    code: string;
    title: string;
    units: number;
}

interface StudentItem {
    id: string;
    user: {
        id?: string;
        name: string;
        email?: string;
    };
    passport_photo_path?: string;
    matriculation_number: string;
    current_level: string | number;
    status: string;
    academic_department?: {
        id: string;
        name: string;
        faculty?: {
            id: string;
            name: string;
        };
    };
    program?: {
        id: string;
        name: string;
    };
    is_registered: boolean;
    registered_courses_count: number;
    registered_units: number;
    registered_courses: RegisteredCourse[];
}

const props = defineProps<{
    students: {
        data: StudentItem[];
        links: any[];
        total: number;
        from: number;
        to: number;
        current_page: number;
        last_page: number;
    };
    overview_stats: {
        active_session_name: string;
        total_students: number;
        total_registered: number;
        total_pending: number;
        registration_percentage: number;
        total_courses_registered: number;
    };
    cohort_stats?: {
        total: number;
        registered: number;
        pending: number;
        percentage: number;
    } | null;
    filters: {
        search: string;
        faculty_id: string;
        department_id: string;
        programme_id: string;
        level: string;
        session_id: string;
        semester_id: string;
        status: string;
        per_page: number;
    };
    faculties: any[];
    departments: any[];
    programmes: any[];
    sessions: any[];
    semesters: any[];
    active_session?: {
        id: string;
        name: string;
    } | null;
}>();

// Filter states
const search = ref(props.filters.search || '');
const facultyId = ref(props.filters.faculty_id || 'all');
const departmentId = ref(props.filters.department_id || 'all');
const programmeId = ref(props.filters.programme_id || 'all');
const level = ref(props.filters.level || 'all');
const sessionId = ref(props.filters.session_id || '');
const semesterId = ref(props.filters.semester_id || '');
const status = ref(props.filters.status || 'all');
const perPage = ref(String(props.filters.per_page || 15));

// Copy state
const copiedMatric = ref<string | null>(null);

const copyMatric = (matric: string) => {
    if (!matric || matric === 'Pending') return;
    navigator.clipboard.writeText(matric);
    copiedMatric.value = matric;
    setTimeout(() => {
        if (copiedMatric.value === matric) {
            copiedMatric.value = null;
        }
    }, 2000);
};

// Cascaded departments based on faculty
const filteredDepartments = computed(() => {
    if (facultyId.value === 'all') return props.departments;
    return props.departments.filter(d => String(d.faculty_id) === String(facultyId.value));
});

// Cascaded programmes based on department
const filteredProgrammes = computed(() => {
    if (departmentId.value !== 'all') {
        return props.programmes.filter(p => String(p.department_id) === String(departmentId.value));
    }
    if (facultyId.value !== 'all') {
        const deptIds = filteredDepartments.value.map(d => String(d.id));
        return props.programmes.filter(p => deptIds.includes(String(p.department_id)));
    }
    return props.programmes;
});

// Dropdown options
const facultyItems = computed(() => [
    { value: 'all', label: 'All Faculties' },
    ...props.faculties.map(f => ({ value: String(f.id), label: f.name }))
]);

const departmentItems = computed(() => [
    { value: 'all', label: 'All Departments' },
    ...filteredDepartments.value.map(d => ({ value: String(d.id), label: d.name }))
]);

const programmeItems = computed(() => [
    { value: 'all', label: 'All Programmes' },
    ...filteredProgrammes.value.map(p => ({ value: String(p.id), label: p.name }))
]);

const sessionItems = computed(() => [
    ...props.sessions.map(s => ({ value: String(s.id), label: s.name }))
]);

const semesterItems = computed(() => [
    { value: '', label: 'All Semesters' },
    ...props.semesters.map(s => ({ value: String(s.id), label: s.name }))
]);

const levelItems = [
    { value: 'all', label: 'All Levels' },
    { value: '100', label: '100 Level' },
    { value: '200', label: '200 Level' },
    { value: '300', label: '300 Level' },
    { value: '400', label: '400 Level' },
    { value: '500', label: '500 Level' },
    { value: '600', label: '600 Level' },
    { value: '700', label: '700 Level (PG)' },
    { value: '800', label: '800 Level (PG)' },
];

const perPageItems = [
    { value: '15', label: '15 per page' },
    { value: '25', label: '25 per page' },
    { value: '50', label: '50 per page' },
    { value: '100', label: '100 per page' },
];

// Active filters count and list
const hasActiveFilters = computed(() => {
    return !!search.value ||
        facultyId.value !== 'all' ||
        departmentId.value !== 'all' ||
        programmeId.value !== 'all' ||
        level.value !== 'all' ||
        semesterId.value !== '' ||
        status.value !== 'all';
});

// Auto-reset department if faculty changes and department is not under it
watch(facultyId, (newFaculty) => {
    if (newFaculty !== 'all' && departmentId.value !== 'all') {
        const belongs = filteredDepartments.value.some(d => String(d.id) === String(departmentId.value));
        if (!belongs) {
            departmentId.value = 'all';
            programmeId.value = 'all';
        }
    }
});

// Auto-reset programme if department changes
watch(departmentId, () => {
    if (programmeId.value !== 'all') {
        const belongs = filteredProgrammes.value.some(p => String(p.id) === String(programmeId.value));
        if (!belongs) {
            programmeId.value = 'all';
        }
    }
});

const applyFilters = debounce(() => {
    router.get(route('admin.course_registration.index'), {
        search: search.value || undefined,
        faculty_id: facultyId.value === 'all' ? undefined : facultyId.value,
        department_id: departmentId.value === 'all' ? undefined : departmentId.value,
        programme_id: programmeId.value === 'all' ? undefined : programmeId.value,
        level: level.value === 'all' ? undefined : level.value,
        session_id: sessionId.value || undefined,
        semester_id: semesterId.value || undefined,
        status: status.value === 'all' ? undefined : status.value,
        per_page: perPage.value !== '15' ? perPage.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 400);

watch([search, facultyId, departmentId, programmeId, level, sessionId, semesterId, status, perPage], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    facultyId.value = 'all';
    departmentId.value = 'all';
    programmeId.value = 'all';
    level.value = 'all';
    semesterId.value = '';
    status.value = 'all';
    perPage.value = '15';
};

const exportCsvUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (facultyId.value !== 'all') params.append('faculty_id', facultyId.value);
    if (departmentId.value !== 'all') params.append('department_id', departmentId.value);
    if (programmeId.value !== 'all') params.append('programme_id', programmeId.value);
    if (level.value !== 'all') params.append('level', level.value);
    if (sessionId.value) params.append('session_id', sessionId.value);
    if (semesterId.value) params.append('semester_id', semesterId.value);
    if (status.value !== 'all') params.append('status', status.value);

    return `${route('admin.course_registration.export', undefined, false)}?${params.toString()}`;
});

const breadcrumbs = [
    { title: 'Academics', href: '#' },
    { title: 'Course Registration', href: '#' }
];
</script>

<template>
    <Head title="Course Registration Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-4 sm:px-6 lg:px-8 space-y-8 w-full max-w-7xl mx-auto">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-slate-900 p-6 md:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                            Course Registration
                        </h1>
                        <Badge variant="outline" class="font-semibold px-2.5 py-0.5 text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800">
                            {{ active_session?.name || overview_stats?.active_session_name || 'Active Session' }}
                        </Badge>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl">
                        Monitor university-wide course registrations, track enrollment progress in real time, and manage student course forms.
                    </p>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <Button variant="outline" as-child class="h-10 px-4 rounded-xl border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-2xs font-medium">
                        <a :href="exportCsvUrl" target="_blank">
                            <Download class="w-4 h-4 mr-2 text-slate-500" /> Export CSV Roster
                        </a>
                    </Button>
                </div>
            </div>

            <!-- KPI Metric Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Active Session Registered Students -->
                <Card class="border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:shadow-md transition-all rounded-2xl overflow-hidden relative">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Registered Students</span>
                                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ overview_stats.total_registered.toLocaleString() }}
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-inner">
                                <GraduationCap class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-xs text-slate-500 dark:text-slate-400">
                            <span>In active session ({{ active_session?.name || overview_stats.active_session_name }})</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2: Total Active Matriculated Students -->
                <Card class="border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:shadow-md transition-all rounded-2xl overflow-hidden relative">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Active Students</span>
                                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ overview_stats.total_students.toLocaleString() }}
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-inner">
                                <Users class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-xs text-slate-500 dark:text-slate-400">
                            <span>Eligible for course registration</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 3: Registration Progress Rate -->
                <Card class="border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:shadow-md transition-all rounded-2xl overflow-hidden relative">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Completion Rate</span>
                                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ overview_stats.registration_percentage }}%
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-inner">
                                <CheckCircle2 class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="mt-2.5 space-y-1.5">
                            <Progress :model-value="overview_stats.registration_percentage" class="h-2 bg-slate-100 dark:bg-slate-800" />
                            <div class="flex justify-between items-center text-[11px] text-slate-500 dark:text-slate-400">
                                <span>{{ overview_stats.total_pending.toLocaleString() }} pending</span>
                                <span>{{ overview_stats.total_registered.toLocaleString() }} completed</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 4: Total Course Enrollments -->
                <Card class="border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:shadow-md transition-all rounded-2xl overflow-hidden relative">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Course Enrollments</span>
                                <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ overview_stats.total_courses_registered.toLocaleString() }}
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-inner">
                                <BookOpen class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="mt-3 flex items-center text-xs text-slate-500 dark:text-slate-400">
                            <span>Individual course allocations</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Cohort Insight Banner (Displayed when academic filters are active) -->
            <div v-if="cohort_stats" class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 rounded-2xl shadow-sm border border-indigo-900/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/15">
                        <Sparkles class="w-5 h-5 text-indigo-300" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold flex items-center gap-2 text-indigo-100">
                            Filtered Cohort Progress
                            <Badge class="bg-indigo-500/20 text-indigo-200 border-indigo-400/30 text-[10px]">
                                {{ cohort_stats.percentage }}% Completed
                            </Badge>
                        </div>
                        <div class="text-xs text-indigo-200/80 mt-0.5">
                            <span class="font-bold text-white">{{ cohort_stats.registered }}</span> of
                            <span class="font-bold text-white">{{ cohort_stats.total }}</span> students registered
                            <span class="text-indigo-400">•</span>
                            <span class="font-bold text-amber-300">{{ cohort_stats.pending }}</span> students pending in this cohort
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-56 space-y-1.5 shrink-0">
                    <div class="flex justify-between text-[11px] text-indigo-200">
                        <span>Cohort Rate</span>
                        <span class="font-bold text-white">{{ cohort_stats.percentage }}%</span>
                    </div>
                    <Progress :model-value="cohort_stats.percentage" class="h-2 bg-white/15" />
                </div>
            </div>

            <!-- Smart Filter Controls Section -->
            <Card class="border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs rounded-2xl overflow-hidden">
                <CardContent class="p-6 space-y-5">
                    
                    <!-- Top Bar: Status Quick-Tabs & Search -->
                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/80">
                        <!-- Quick Status Tabs -->
                        <div class="inline-flex items-center p-1 bg-slate-100 dark:bg-slate-800/70 rounded-xl">
                            <button
                                type="button"
                                @click="status = 'all'"
                                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all"
                                :class="status === 'all'
                                    ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            >
                                All Students
                                <span class="ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                                    {{ props.students.total.toLocaleString() }}
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="status = 'registered'"
                                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5"
                                :class="status === 'registered'
                                    ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-emerald-600'"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Registered
                            </button>
                            <button
                                type="button"
                                @click="status = 'not_registered'"
                                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5"
                                :class="status === 'not_registered'
                                    ? 'bg-white dark:bg-slate-900 text-amber-700 dark:text-amber-400 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-amber-600'"
                            >
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                Not Registered
                            </button>
                        </div>

                        <!-- Live Search Input -->
                        <div class="relative w-full md:w-80">
                            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input
                                v-model="search"
                                placeholder="Search by name or matric number..."
                                class="pl-10 pr-9 h-10 rounded-xl bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-800 text-sm focus-visible:ring-primary"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Cascading Filters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5">
                        
                        <!-- Session -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Session</Label>
                            <SearchableSelect
                                v-model="sessionId"
                                :items="sessionItems"
                                placeholder="Select Session"
                                search-placeholder="Search sessions..."
                                trigger-class="rounded-xl border-slate-200 dark:border-slate-800 text-xs h-10"
                            />
                        </div>

                        <!-- Semester -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Semester</Label>
                            <SearchableSelect
                                v-model="semesterId"
                                :items="semesterItems"
                                placeholder="All Semesters"
                                search-placeholder="Search semesters..."
                                trigger-class="rounded-xl border-slate-200 dark:border-slate-800 text-xs h-10"
                                :disabled="!sessionId"
                            />
                        </div>

                        <!-- Faculty -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Faculty</Label>
                            <SearchableSelect
                                v-model="facultyId"
                                :items="facultyItems"
                                placeholder="All Faculties"
                                search-placeholder="Search faculties..."
                                trigger-class="rounded-xl border-slate-200 dark:border-slate-800 text-xs h-10"
                            />
                        </div>

                        <!-- Department -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Department</Label>
                            <SearchableSelect
                                v-model="departmentId"
                                :items="departmentItems"
                                placeholder="All Departments"
                                search-placeholder="Search departments..."
                                trigger-class="rounded-xl border-slate-200 dark:border-slate-800 text-xs h-10"
                            />
                        </div>

                        <!-- Programme -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Programme</Label>
                            <SearchableSelect
                                v-model="programmeId"
                                :items="programmeItems"
                                placeholder="All Programmes"
                                search-placeholder="Search programmes..."
                                trigger-class="rounded-xl border-slate-200 dark:border-slate-800 text-xs h-10"
                            />
                        </div>

                        <!-- Level -->
                        <div class="space-y-1.5">
                            <Label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Level</Label>
                            <Select v-model="level">
                                <SelectTrigger class="w-full rounded-xl bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs h-10">
                                    <SelectValue placeholder="All Levels" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="item in levelItems" :key="item.value" :value="item.value" class="text-xs">
                                        {{ item.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Active Filter Chips & Reset Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                Showing <strong class="text-slate-900 dark:text-white">{{ props.students.from || 0 }} - {{ props.students.to || 0 }}</strong> of <strong class="text-slate-900 dark:text-white">{{ props.students.total.toLocaleString() }}</strong> students
                            </span>

                            <!-- Active Filter Pills -->
                            <Badge
                                v-if="facultyId !== 'all'"
                                variant="secondary"
                                class="text-[11px] font-medium gap-1 pl-2.5 pr-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                            >
                                Faculty: {{ props.faculties.find(f => String(f.id) === String(facultyId))?.name || 'Selected' }}
                                <button type="button" @click="facultyId = 'all'" class="hover:text-red-500 p-0.5">
                                    <X class="w-3 h-3" />
                                </button>
                            </Badge>

                            <Badge
                                v-if="departmentId !== 'all'"
                                variant="secondary"
                                class="text-[11px] font-medium gap-1 pl-2.5 pr-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                            >
                                Dept: {{ props.departments.find(d => String(d.id) === String(departmentId))?.name || 'Selected' }}
                                <button type="button" @click="departmentId = 'all'" class="hover:text-red-500 p-0.5">
                                    <X class="w-3 h-3" />
                                </button>
                            </Badge>

                            <Badge
                                v-if="programmeId !== 'all'"
                                variant="secondary"
                                class="text-[11px] font-medium gap-1 pl-2.5 pr-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                            >
                                Prog: {{ props.programmes.find(p => String(p.id) === String(programmeId))?.name || 'Selected' }}
                                <button type="button" @click="programmeId = 'all'" class="hover:text-red-500 p-0.5">
                                    <X class="w-3 h-3" />
                                </button>
                            </Badge>

                            <Badge
                                v-if="level !== 'all'"
                                variant="secondary"
                                class="text-[11px] font-medium gap-1 pl-2.5 pr-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                            >
                                Level: {{ level }}L
                                <button type="button" @click="level = 'all'" class="hover:text-red-500 p-0.5">
                                    <X class="w-3 h-3" />
                                </button>
                            </Badge>

                            <Badge
                                v-if="semesterId"
                                variant="secondary"
                                class="text-[11px] font-medium gap-1 pl-2.5 pr-1.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                            >
                                Semester: {{ props.semesters.find(s => String(s.id) === String(semesterId))?.name || 'Selected' }}
                                <button type="button" @click="semesterId = ''" class="hover:text-red-500 p-0.5">
                                    <X class="w-3 h-3" />
                                </button>
                            </Badge>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Per Page Selector -->
                            <div class="flex items-center gap-1.5">
                                <Select v-model="perPage">
                                    <SelectTrigger class="w-28 rounded-xl bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-xs h-8">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in perPageItems" :key="item.value" :value="item.value" class="text-xs">
                                            {{ item.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Reset Filters Button -->
                            <Button
                                v-if="hasActiveFilters"
                                variant="ghost"
                                size="sm"
                                @click="resetFilters"
                                class="text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl h-8 px-2.5"
                            >
                                <FilterX class="w-3.5 h-3.5 mr-1.5" /> Clear Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Student Registrations Table -->
            <Card class="border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden rounded-2xl">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50/80 dark:bg-slate-800/40">
                            <TableRow class="hover:bg-transparent border-slate-200/80 dark:border-slate-800">
                                <TableHead class="pl-6 py-4 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Student Profile</TableHead>
                                <TableHead class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Matric Number</TableHead>
                                <TableHead class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Academic Info</TableHead>
                                <TableHead class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Registration Status</TableHead>
                                <TableHead class="text-right pr-6 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="student in props.students.data"
                                :key="student.id"
                                class="group hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors border-slate-100 dark:border-slate-800/80"
                            >
                                <!-- Student Name & Avatar -->
                                <TableCell class="pl-6 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <Avatar class="h-11 w-11 border-2 border-white dark:border-slate-800 shadow-2xs group-hover:scale-105 transition-transform shrink-0">
                                            <AvatarImage :src="student.passport_photo_path ? `/storage/${student.passport_photo_path}` : ''" />
                                            <AvatarFallback class="bg-primary/10 text-primary font-bold text-sm">
                                                {{ student.user?.name ? student.user.name.charAt(0) : 'S' }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 dark:text-white truncate group-hover:text-primary transition-colors text-sm">
                                                {{ student.user?.name }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {{ student.user?.email || 'No email provided' }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Matriculation Number with One-Click Copy -->
                                <TableCell>
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-mono text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60">
                                        <span>{{ student.matriculation_number }}</span>
                                        <button
                                            v-if="student.matriculation_number !== 'Pending'"
                                            type="button"
                                            @click="copyMatric(student.matriculation_number)"
                                            class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors cursor-pointer"
                                            :title="copiedMatric === student.matriculation_number ? 'Copied!' : 'Copy Matric Number'"
                                        >
                                            <Check v-if="copiedMatric === student.matriculation_number" class="w-3.5 h-3.5 text-emerald-600" />
                                            <Copy v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </TableCell>

                                <!-- Academic Department & Program -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                            {{ student.academic_department?.name || 'Department N/A' }}
                                        </div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <Badge variant="outline" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                                                {{ student.current_level }} Level
                                            </Badge>
                                            <span v-if="student.program" class="text-[10px] font-medium text-slate-500 dark:text-slate-400 truncate max-w-[180px]">
                                                {{ student.program.name }}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Registration Status & Courses Count -->
                                <TableCell>
                                    <!-- Registered State -->
                                    <div v-if="student.is_registered" class="space-y-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <Badge class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/80 text-[11px] font-semibold gap-1 px-2.5 py-0.5">
                                                <CheckCircle2 class="w-3 h-3 text-emerald-600" /> Registered
                                            </Badge>
                                        </div>

                                        <!-- Popover displaying registered courses list on click/hover -->
                                        <Popover>
                                            <PopoverTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1 text-xs text-slate-600 dark:text-slate-300 hover:text-primary font-medium underline underline-offset-2 transition-colors cursor-pointer"
                                                >
                                                    <BookOpen class="w-3.5 h-3.5 text-slate-400" />
                                                    <span>{{ student.registered_courses_count }} Courses ({{ student.registered_units }} Units)</span>
                                                </button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-80 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg" align="start">
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-800">
                                                        <span class="text-xs font-bold text-slate-900 dark:text-white">Registered Courses</span>
                                                        <span class="text-[10px] font-semibold text-slate-500">{{ student.registered_units }} Total Units</span>
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                                                        <div
                                                            v-for="course in student.registered_courses"
                                                            :key="course.id"
                                                            class="flex items-center justify-between text-xs p-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60"
                                                        >
                                                            <div class="min-w-0 pr-2">
                                                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ course.code }}</span>
                                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ course.title }}</p>
                                                            </div>
                                                            <Badge variant="outline" class="text-[10px] shrink-0 font-mono">
                                                                {{ course.units }} U
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                            </PopoverContent>
                                        </Popover>
                                    </div>

                                    <!-- Unregistered State -->
                                    <div v-else class="space-y-1">
                                        <Badge class="bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80 text-[11px] font-semibold gap-1 px-2.5 py-0.5">
                                            <Clock class="w-3 h-3 text-amber-600" /> Not Registered
                                        </Badge>
                                        <p class="text-[11px] text-slate-400">No courses submitted</p>
                                    </div>
                                </TableCell>

                                <!-- Action Buttons -->
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end items-center gap-2">
                                        <!-- Preview / Print Form -->
                                        <Button
                                            v-if="student.is_registered"
                                            variant="outline"
                                            size="sm"
                                            as-child
                                            class="h-9 px-3 rounded-xl border-slate-200 dark:border-slate-800 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 dark:hover:bg-blue-950/50 transition-all text-xs font-medium"
                                        >
                                            <a :href="route('admin.course_registration.form', student.id)" target="_blank" title="Preview Course Form">
                                                <Eye class="w-3.5 h-3.5 mr-1.5" /> Preview Form
                                            </a>
                                        </Button>

                                        <!-- Manage Registration (Primary) -->
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            as-child
                                            class="h-9 px-3.5 rounded-xl hover:bg-primary hover:text-white transition-all text-xs font-semibold group/btn"
                                        >
                                            <Link :href="route('admin.course_registration.manage', student.id)">
                                                <Settings2 class="w-3.5 h-3.5 mr-1.5" /> Manage
                                                <ArrowRight class="w-3 h-3 ml-1 opacity-0 group-hover/btn:opacity-100 -translate-x-1 group-hover/btn:translate-x-0 transition-all" />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty Search / Filter State -->
                            <TableRow v-if="props.students.data.length === 0">
                                <TableCell colspan="5" class="h-72 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center">
                                            <UserSearch class="w-7 h-7" />
                                        </div>
                                        <div>
                                            <p class="text-base font-bold text-slate-900 dark:text-white">No students found</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm">
                                                No student records matched your selected criteria or search term. Try adjusting your filters.
                                            </p>
                                        </div>
                                        <Button
                                            v-if="hasActiveFilters"
                                            variant="outline"
                                            size="sm"
                                            @click="resetFilters"
                                            class="rounded-xl text-xs mt-2"
                                        >
                                            <FilterX class="w-3.5 h-3.5 mr-1.5" /> Reset All Filters
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination Bar -->
            <div v-if="props.students.links && props.students.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-2">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing <span class="font-semibold text-slate-900 dark:text-white">{{ props.students.from || 0 }}</span> to <span class="font-semibold text-slate-900 dark:text-white">{{ props.students.to || 0 }}</span> of <span class="font-semibold text-slate-900 dark:text-white">{{ props.students.total.toLocaleString() }}</span> students
                </div>

                <nav class="flex items-center space-x-1.5">
                    <Link
                        v-for="(link, k) in props.students.links"
                        :key="k"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3.5 py-1.5 text-xs font-semibold rounded-xl transition-all"
                        :class="[
                            link.active
                                ? 'bg-primary text-white shadow-xs'
                                : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none cursor-not-allowed' : ''
                        ]"
                    />
                </nav>
            </div>

        </div>
    </AdminLayout>
</template>
