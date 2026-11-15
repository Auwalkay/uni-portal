<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    CalendarCheck,
    BookOpen,
    ChevronDown,
    ChevronUp,
    Calendar,
    ShieldAlert,
    CheckCircle2,
    XCircle,
    Search,
    LayoutGrid,
    List,
    TrendingUp,
    Layers,
    Clock,
    AlertTriangle,
} from 'lucide-vue-next';

interface CourseAttendance {
    registration_id: string;
    course_id: string;
    course_code: string;
    course_title: string;
    units: number;
    semester_id?: string;
    semester_name?: string;
    total_classes: number;
    attended_classes: number;
    missed_classes: number;
    attendance_percentage: number | null;
    dates: Array<{
        date: string;
        formatted_date: string;
        status: string;
    }>;
}

interface AcademicSessionItem {
    id: string;
    name: string;
    academic_stream?: string;
    is_current: boolean;
}

interface SemesterItem {
    id: string;
    name: string;
    is_current: boolean;
}

const props = defineProps<{
    courses: CourseAttendance[];
    stats: {
        total_courses: number;
        total_classes: number;
        total_attended: number;
        overall_percentage: number | null;
    };
    sessions: AcademicSessionItem[];
    semesters: SemesterItem[];
    filters: {
        session_id: string | null;
        semester_id: string | null;
    };
    session?: {
        id: string;
        name: string;
        academic_stream?: string;
    } | null;
    selectedSession?: {
        id: string;
        name: string;
        academic_stream?: string;
    } | null;
    selectedSemester?: {
        id: string;
        name: string;
        is_current?: boolean;
    } | null;
    studentStream?: string;
    minRequiredPercentage: number;
    isEnforced: boolean;
}>();

// UI State
const searchQuery = ref('');
const statusFilter = ref<'all' | 'eligible' | 'at_risk' | 'ineligible' | 'no_records'>('all');
const viewMode = ref<'cards' | 'table'>('cards');
const expandedCourses = ref<Record<string, boolean>>({});

const toggleCourse = (registrationId: string) => {
    expandedCourses.value[registrationId] = !expandedCourses.value[registrationId];
};

const handleSessionChange = (newSessionId: string) => {
    router.get(
        route('student.attendance.index'),
        { session_id: newSessionId },
        { preserveState: true, replace: true }
    );
};

const handleSemesterChange = (newSemesterId: string) => {
    router.get(
        route('student.attendance.index'),
        {
            session_id: props.filters.session_id,
            semester_id: newSemesterId,
        },
        { preserveState: true, replace: true }
    );
};

// Quick status categorization
const getComplianceCategory = (percentage: number | null, totalClasses: number) => {
    if (totalClasses === 0 || percentage === null) return 'no_records';
    if (percentage >= props.minRequiredPercentage) return 'eligible';
    if (percentage >= 60) return 'at_risk';
    return 'ineligible';
};

// Filter counts
const statusCounts = computed(() => {
    let eligible = 0;
    let atRisk = 0;
    let ineligible = 0;
    let noRecords = 0;

    props.courses.forEach((c) => {
        const cat = getComplianceCategory(c.attendance_percentage, c.total_classes);
        if (cat === 'eligible') eligible++;
        else if (cat === 'at_risk') atRisk++;
        else if (cat === 'ineligible') ineligible++;
        else noRecords++;
    });

    return {
        all: props.courses.length,
        eligible,
        at_risk: atRisk,
        ineligible,
        no_records: noRecords,
    };
});

// Filtered Course List
const filteredCourses = computed(() => {
    return props.courses.filter((course) => {
        const q = searchQuery.value.trim().toLowerCase();
        const matchesSearch =
            !q ||
            course.course_code.toLowerCase().includes(q) ||
            course.course_title.toLowerCase().includes(q);

        if (!matchesSearch) return false;

        if (statusFilter.value === 'all') return true;
        return getComplianceCategory(course.attendance_percentage, course.total_classes) === statusFilter.value;
    });
});

const getStatusBadge = (percentage: number | null, totalClasses: number) => {
    if (totalClasses === 0 || percentage === null) {
        return {
            label: 'No Lectures Logged',
            badgeClass: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 font-semibold',
            color: 'slate',
        };
    }
    if (percentage >= props.minRequiredPercentage) {
        return {
            label: `Eligible (${percentage}%)`,
            badgeClass: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800 font-bold',
            color: 'emerald',
        };
    }
    if (percentage >= 60) {
        return {
            label: `At Risk (${percentage}%)`,
            badgeClass: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-800 font-bold',
            color: 'amber',
        };
    }
    return {
        label: `Ineligible (${percentage}%)`,
        badgeClass: 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-800 font-bold',
        color: 'rose',
    };
};
</script>

<template>
    <Head title="My Lecture Attendance" />

    <StudentLayout>
        <div class="space-y-4 sm:space-y-6 p-3.5 sm:p-6 md:p-8 max-w-7xl mx-auto min-h-screen w-full min-w-0 max-w-full overflow-x-hidden">

            <!-- 1. Responsive Hero Header Banner -->
            <div class="relative rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-700 to-emerald-900 text-white p-4.5 sm:p-7 md:p-8 overflow-hidden shadow-lg border border-emerald-600/30 w-full min-w-0">
                <!-- Background ambient lights -->
                <div class="absolute -right-16 -top-16 w-60 h-60 sm:w-72 sm:h-72 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-60 h-60 sm:w-72 sm:h-72 bg-teal-400/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 sm:gap-6 w-full min-w-0">
                    <div class="space-y-2 w-full min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                            <Badge class="bg-emerald-500/25 border-emerald-300/30 text-emerald-100 font-semibold px-2 sm:px-2.5 py-0.5 text-[11px] sm:text-xs backdrop-blur-md">
                                Academic Compliance
                            </Badge>
                            <Badge v-if="studentStream" variant="outline" class="bg-white/10 border-white/20 text-white font-bold text-[10px] tracking-wider uppercase px-2 py-0.5">
                                {{ studentStream }} Stream
                            </Badge>
                            <span v-if="selectedSemester" class="text-[11px] sm:text-xs text-emerald-200/90 font-medium">
                                • {{ selectedSemester.name }}
                            </span>
                        </div>

                        <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2.5 drop-shadow-sm">
                            <CalendarCheck class="w-6 h-6 sm:w-8 sm:h-8 text-emerald-300 shrink-0" />
                            <span>My Lecture Attendance</span>
                        </h1>

                        <p class="text-emerald-100/85 text-xs sm:text-sm font-normal leading-relaxed break-words">
                            Monitor your lecture attendance compliance in real-time for
                            <strong class="text-white">{{ selectedSession?.name || session?.name || 'Current Session' }}</strong>.
                        </p>
                    </div>

                    <!-- Session Selector on Hero -->
                    <div class="w-full lg:w-auto min-w-0 shrink-0">
                        <div class="flex items-center gap-2 bg-black/20 backdrop-blur-md p-1.5 rounded-xl sm:rounded-2xl border border-white/15 w-full sm:w-auto">
                            <span class="text-xs font-semibold text-emerald-100 pl-2 shrink-0">Session:</span>
                            <Select :model-value="filters.session_id || ''" @update:model-value="handleSessionChange">
                                <SelectTrigger class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white border-0 h-9 sm:h-10 font-bold text-xs rounded-lg sm:rounded-xl shadow-md w-full sm:w-[200px] min-w-0 focus:ring-2 focus:ring-emerald-400">
                                    <SelectValue placeholder="Select Session" />
                                </SelectTrigger>
                                <SelectContent class="max-h-72">
                                    <SelectItem
                                        v-for="s in sessions"
                                        :key="s.id"
                                        :value="s.id"
                                        class="text-xs font-semibold py-2"
                                    >
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span>{{ s.name }}</span>
                                            <Badge v-if="s.is_current" variant="outline" class="text-[9px] px-1 py-0 bg-emerald-50 text-emerald-700 border-emerald-300 font-bold">
                                                Active
                                            </Badge>
                                            <span v-if="s.academic_stream" class="text-muted-foreground text-[10px]">
                                                [{{ s.academic_stream }}]
                                            </span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Responsive Segmented Semester Navigation Tabs & Policy Banner -->
            <div class="w-full min-w-0 max-w-full flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-2 sm:p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <!-- Semester Tabs (Scrollable track on mobile without overflow) -->
                <div class="w-full lg:w-auto min-w-0 overflow-x-auto scrollbar-none flex items-center gap-1.5 p-0.5">
                    <button
                        @click="handleSemesterChange('all')"
                        class="px-3.5 sm:px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer whitespace-nowrap shrink-0"
                        :class="filters.semester_id === 'all'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                    >
                        All Semesters
                    </button>

                    <button
                        v-for="sem in semesters"
                        :key="sem.id"
                        @click="handleSemesterChange(sem.id)"
                        class="px-3.5 sm:px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer whitespace-nowrap shrink-0 flex items-center gap-1.5"
                        :class="filters.semester_id === sem.id
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                    >
                        <span>{{ sem.name }}</span>
                        <span
                            v-if="sem.is_current"
                            class="text-[9px] px-1.5 py-0.5 rounded-md font-extrabold uppercase shrink-0"
                            :class="filters.semester_id === sem.id ? 'bg-white/20 text-white' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'"
                        >
                            Active
                        </span>
                    </button>
                </div>

                <!-- Exam Docket Policy Alert Badge -->
                <div
                    v-if="isEnforced"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-900/60 text-xs font-semibold text-amber-900 dark:text-amber-200 shrink-0"
                >
                    <ShieldAlert class="w-4 h-4 text-amber-600 shrink-0" />
                    <span>Exam Docket requires <strong>{{ minRequiredPercentage }}%</strong> minimum attendance</span>
                </div>
            </div>

            <!-- 3. Responsive 4-Column Metric KPI Cards (2 cols on mobile, 4 on desktop) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 w-full min-w-0">
                <!-- Registered Courses -->
                <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:border-slate-300 transition-all rounded-xl sm:rounded-2xl">
                    <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                        <div class="space-y-0.5 sm:space-y-1 min-w-0">
                            <p class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider truncate">Registered</p>
                            <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ stats.total_courses }}</p>
                            <p class="text-[10px] sm:text-[11px] text-muted-foreground truncate">Enrolled courses</p>
                        </div>
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <BookOpen class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Classes Held -->
                <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:border-slate-300 transition-all rounded-xl sm:rounded-2xl">
                    <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                        <div class="space-y-0.5 sm:space-y-1 min-w-0">
                            <p class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider truncate">Uploaded</p>
                            <p class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400 tracking-tight">{{ stats.total_classes }}</p>
                            <p class="text-[10px] sm:text-[11px] text-muted-foreground truncate">Class sessions</p>
                        </div>
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <Layers class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Classes Attended -->
                <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:border-slate-300 transition-all rounded-xl sm:rounded-2xl">
                    <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                        <div class="space-y-0.5 sm:space-y-1 min-w-0">
                            <p class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider truncate">Attended</p>
                            <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ stats.total_attended }}</p>
                            <p class="text-[10px] sm:text-[11px] text-muted-foreground truncate">
                                <span v-if="stats.total_classes > 0" class="text-rose-600 font-semibold">
                                    {{ Math.max(0, stats.total_classes - stats.total_attended) }} missed
                                </span>
                                <span v-else>Awaiting uploads</span>
                            </p>
                        </div>
                        <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <CheckCircle2 class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Overall Attendance Rate -->
                <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:border-slate-300 transition-all rounded-xl sm:rounded-2xl">
                    <CardContent class="p-3.5 sm:p-5 flex items-center justify-between">
                        <div class="space-y-0.5 sm:space-y-1 min-w-0">
                            <p class="text-[10px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider truncate">Rate</p>
                            <p
                                class="text-2xl sm:text-3xl font-black tracking-tight"
                                :class="stats.overall_percentage === null
                                    ? 'text-slate-400'
                                    : (stats.overall_percentage >= minRequiredPercentage ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400')"
                            >
                                {{ stats.overall_percentage !== null ? `${stats.overall_percentage}%` : 'N/A' }}
                            </p>
                            <p class="text-[10px] sm:text-[11px] font-semibold truncate">
                                <span v-if="stats.overall_percentage === null" class="text-slate-400">
                                    No records yet
                                </span>
                                <span v-else-if="stats.overall_percentage >= minRequiredPercentage" class="text-emerald-600 dark:text-emerald-400">
                                    ✓ Eligible
                                </span>
                                <span v-else class="text-rose-600 dark:text-rose-400">
                                    ⚠ Below {{ minRequiredPercentage }}%
                                </span>
                            </p>
                        </div>
                        <div
                            class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center shrink-0"
                            :class="stats.overall_percentage === null
                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                                : (stats.overall_percentage >= minRequiredPercentage ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600')"
                        >
                            <TrendingUp class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- 4. Interactive Search, Status Filters & View Controls -->
            <div class="space-y-3 w-full min-w-0">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 bg-white dark:bg-slate-900 p-3 sm:p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs w-full min-w-0">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-0">
                        <Search class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2 shrink-0" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Filter by course code or title..."
                            class="pl-9 h-9 text-xs bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 rounded-xl w-full"
                        />
                    </div>

                    <!-- View Mode Toggle (Cards vs Table) -->
                    <div class="flex items-center justify-end shrink-0">
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                            <button
                                @click="viewMode = 'cards'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                                :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                            >
                                <LayoutGrid class="w-3.5 h-3.5" />
                                <span>Cards</span>
                            </button>
                            <button
                                @click="viewMode = 'table'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                                :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                            >
                                <List class="w-3.5 h-3.5" />
                                <span>Table</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status Filter Chips (Scrollable row with touch support) -->
                <div class="w-full min-w-0 max-w-full overflow-x-auto scrollbar-none flex items-center gap-2 pb-1 text-xs">
                    <button
                        @click="statusFilter = 'all'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all cursor-pointer whitespace-nowrap shrink-0 border"
                        :class="statusFilter === 'all'
                            ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 border-transparent shadow-xs'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-50'"
                    >
                        All Courses ({{ statusCounts.all }})
                    </button>

                    <button
                        @click="statusFilter = 'eligible'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all cursor-pointer whitespace-nowrap shrink-0 border flex items-center gap-1.5"
                        :class="statusFilter === 'eligible'
                            ? 'bg-emerald-600 text-white border-transparent shadow-xs'
                            : 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900 hover:bg-emerald-50/50'"
                    >
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Eligible ({{ statusCounts.eligible }})</span>
                    </button>

                    <button
                        @click="statusFilter = 'at_risk'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all cursor-pointer whitespace-nowrap shrink-0 border flex items-center gap-1.5"
                        :class="statusFilter === 'at_risk'
                            ? 'bg-amber-600 text-white border-transparent shadow-xs'
                            : 'bg-white dark:bg-slate-900 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900 hover:bg-amber-50/50'"
                    >
                        <AlertTriangle class="w-3.5 h-3.5" />
                        <span>At Risk ({{ statusCounts.at_risk }})</span>
                    </button>

                    <button
                        @click="statusFilter = 'ineligible'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all cursor-pointer whitespace-nowrap shrink-0 border flex items-center gap-1.5"
                        :class="statusFilter === 'ineligible'
                            ? 'bg-rose-600 text-white border-transparent shadow-xs'
                            : 'bg-white dark:bg-slate-900 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-900 hover:bg-rose-50/50'"
                    >
                        <XCircle class="w-3.5 h-3.5" />
                        <span>Ineligible ({{ statusCounts.ineligible }})</span>
                    </button>

                    <button
                        @click="statusFilter = 'no_records'"
                        class="px-3 py-1.5 rounded-xl font-bold transition-all cursor-pointer whitespace-nowrap shrink-0 border flex items-center gap-1.5"
                        :class="statusFilter === 'no_records'
                            ? 'bg-slate-600 text-white border-transparent shadow-xs'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-50'"
                    >
                        <Clock class="w-3.5 h-3.5" />
                        <span>Awaiting ({{ statusCounts.no_records }})</span>
                    </button>
                </div>
            </div>

            <!-- 5. Course Content Area -->
            <!-- CARD VIEW -->
            <div v-if="viewMode === 'cards' && filteredCourses.length > 0" class="space-y-3 sm:space-y-4 w-full min-w-0">
                <Card
                    v-for="course in filteredCourses"
                    :key="course.registration_id"
                    class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all rounded-xl sm:rounded-2xl overflow-hidden w-full min-w-0"
                >
                    <div class="p-4 sm:p-6 space-y-3.5">
                        <!-- Top Row: Course Code, Info & Status Badge -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                    <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-mono tracking-tight bg-slate-100 dark:bg-slate-800 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-lg">
                                        {{ course.course_code }}
                                    </span>
                                    <Badge variant="outline" class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 border-slate-200 dark:border-slate-700">
                                        {{ course.units }} Units
                                    </Badge>
                                    <Badge v-if="course.semester_name" variant="secondary" class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ course.semester_name }}
                                    </Badge>
                                </div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-200 break-words pt-0.5">
                                    {{ course.course_title }}
                                </h3>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-1 sm:pt-0">
                                <Badge
                                    variant="outline"
                                    :class="getStatusBadge(course.attendance_percentage, course.total_classes).badgeClass"
                                    class="text-[10px] sm:text-xs px-2.5 sm:px-3 py-1 rounded-xl shadow-2xs"
                                >
                                    {{ getStatusBadge(course.attendance_percentage, course.total_classes).label }}
                                </Badge>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 gap-1 text-xs font-bold rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                    @click="toggleCourse(course.registration_id)"
                                >
                                    <span>{{ expandedCourses[course.registration_id] ? 'Hide' : (course.dates.length > 0 ? `Dates (${course.dates.length})` : 'Details') }}</span>
                                    <ChevronUp v-if="expandedCourses[course.registration_id]" class="w-3.5 h-3.5" />
                                    <ChevronDown v-else class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </div>

                        <!-- Progress Bar & Ratio -->
                        <div class="space-y-1.5 pt-0.5">
                            <div class="flex items-center justify-between text-xs flex-wrap gap-1">
                                <span class="text-muted-foreground font-medium text-[11px] sm:text-xs">
                                    Attended: <strong class="text-slate-900 dark:text-white">{{ course.attended_classes }}</strong> of
                                    <strong class="text-slate-900 dark:text-white">{{ course.total_classes }}</strong> classes
                                    <span v-if="course.missed_classes > 0" class="text-rose-600 font-semibold ml-1">
                                        ({{ course.missed_classes }} missed)
                                    </span>
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white text-[11px] sm:text-xs">
                                    {{ course.attendance_percentage !== null ? `${course.attendance_percentage}%` : 'No classes recorded' }}
                                </span>
                            </div>

                            <!-- Styled Progress Bar -->
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 sm:h-2.5 overflow-hidden">
                                <div
                                    v-if="course.attendance_percentage !== null"
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="course.attendance_percentage >= minRequiredPercentage
                                        ? 'bg-emerald-500'
                                        : (course.attendance_percentage >= 60 ? 'bg-amber-500' : 'bg-rose-500')"
                                    :style="{ width: `${Math.min(100, Math.max(0, course.attendance_percentage))}%` }"
                                ></div>
                                <div v-else class="h-full w-full bg-slate-200/50 dark:bg-slate-800/50"></div>
                            </div>
                        </div>

                        <!-- Expandable Date Breakdown -->
                        <div
                            v-if="expandedCourses[course.registration_id]"
                            class="border-t border-slate-100 dark:border-slate-800 pt-3.5 mt-2 space-y-2.5"
                        >
                            <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Calendar class="w-3.5 h-3.5 text-emerald-600" />
                                Class Attendance Log ({{ course.dates.length }} Dates)
                            </p>

                            <div v-if="course.dates.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 pt-1">
                                <div
                                    v-for="d in course.dates"
                                    :key="d.date"
                                    class="p-2 sm:p-2.5 rounded-xl border flex items-center justify-between text-xs transition-all shadow-2xs"
                                    :class="d.status === 'present'
                                        ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-900'
                                        : 'bg-rose-50/70 border-rose-200 dark:bg-rose-950/20 dark:border-rose-900'"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Calendar class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ d.formatted_date }}</span>
                                    </div>
                                    <Badge
                                        class="text-[9px] sm:text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-md shrink-0"
                                        :class="d.status === 'present' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
                                    >
                                        {{ d.status }}
                                    </Badge>
                                </div>
                            </div>

                            <div v-else class="text-xs text-muted-foreground italic py-2.5 bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 text-center border border-dashed border-slate-200 dark:border-slate-800">
                                No lecture records uploaded for this course yet.
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- TABLE VIEW (With smooth horizontal scroll container) -->
            <div v-else-if="viewMode === 'table' && filteredCourses.length > 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden w-full min-w-0 max-w-full">
                <div class="w-full min-w-0 max-w-full overflow-x-auto">
                    <Table class="min-w-[700px]">
                        <TableHeader class="bg-slate-50 dark:bg-slate-800/60">
                            <TableRow>
                                <TableHead class="text-xs font-bold uppercase">Course</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Units</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Semester</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Lectures</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Attended</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Missed</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Attendance %</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-center">Status</TableHead>
                                <TableHead class="text-xs font-bold uppercase text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="course in filteredCourses" :key="course.registration_id">
                                <TableRow class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50">
                                    <TableCell>
                                        <div class="space-y-0.5">
                                            <p class="font-bold font-mono text-xs text-slate-900 dark:text-white">{{ course.course_code }}</p>
                                            <p class="text-xs text-muted-foreground line-clamp-1 max-w-xs">{{ course.course_title }}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center font-bold text-xs">{{ course.units }}</TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="outline" class="text-[10px] font-semibold">
                                            {{ course.semester_name || 'N/A' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center font-bold text-xs text-blue-600">{{ course.total_classes }}</TableCell>
                                    <TableCell class="text-center font-bold text-xs text-emerald-600">{{ course.attended_classes }}</TableCell>
                                    <TableCell class="text-center font-bold text-xs text-rose-600">{{ course.missed_classes }}</TableCell>
                                    <TableCell class="text-center font-black text-xs">
                                        {{ course.attendance_percentage !== null ? `${course.attendance_percentage}%` : 'N/A' }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge
                                            variant="outline"
                                            :class="getStatusBadge(course.attendance_percentage, course.total_classes).badgeClass"
                                            class="text-[10px] px-2 py-0.5"
                                        >
                                            {{ getStatusBadge(course.attendance_percentage, course.total_classes).label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 text-xs font-bold cursor-pointer"
                                            @click="toggleCourse(course.registration_id)"
                                        >
                                            {{ expandedCourses[course.registration_id] ? 'Hide' : 'Dates' }}
                                        </Button>
                                    </TableCell>
                                </TableRow>

                                <!-- Inline Table History Expansion -->
                                <TableRow v-if="expandedCourses[course.registration_id]" class="bg-slate-50/50 dark:bg-slate-800/30">
                                    <TableCell colspan="9" class="p-3 sm:p-4">
                                        <div v-if="course.dates.length > 0" class="space-y-2">
                                            <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Attendance Log for {{ course.course_code }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                <div
                                                    v-for="d in course.dates"
                                                    :key="d.date"
                                                    class="px-2.5 py-1 rounded-lg border text-xs flex items-center gap-2"
                                                    :class="d.status === 'present' ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300'"
                                                >
                                                    <span>{{ d.formatted_date }}</span>
                                                    <span class="font-bold uppercase text-[9px]">{{ d.status }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-muted-foreground italic py-1">
                                            No dates recorded yet for this course.
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Empty Search Results -->
            <div
                v-else-if="courses.length > 0 && filteredCourses.length === 0"
                class="p-8 sm:p-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl text-center space-y-3 bg-white dark:bg-slate-900 w-full min-w-0"
            >
                <Search class="w-8 h-8 sm:w-10 sm:h-10 text-slate-300 mx-auto" />
                <h3 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-200">No Courses Match Filter</h3>
                <p class="text-xs text-muted-foreground max-w-sm mx-auto">
                    No courses match "{{ searchQuery }}".
                </p>
                <Button
                    size="sm"
                    variant="outline"
                    class="text-xs font-bold"
                    @click="searchQuery = ''; statusFilter = 'all'"
                >
                    Reset Filters
                </Button>
            </div>

            <!-- No Registered Courses -->
            <div
                v-else
                class="p-8 sm:p-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl text-center space-y-3 bg-white dark:bg-slate-900 w-full min-w-0"
            >
                <BookOpen class="w-10 h-10 sm:w-12 sm:h-12 text-slate-300 dark:text-slate-600 mx-auto" />
                <h3 class="text-base sm:text-lg font-bold text-slate-800 dark:text-slate-200">No Course Registrations Found</h3>
                <p class="text-xs text-muted-foreground max-w-md mx-auto leading-relaxed">
                    You have not registered for any courses in the selected session
                    <span v-if="selectedSemester"> ({{ selectedSemester.name }})</span>.
                </p>
                <div class="pt-2">
                    <Link :href="route('student.courses.index')">
                        <Button size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold gap-1.5 shadow-xs">
                            Go to Course Registration
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
