<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { 
    Users, BookOpen, Download, Search, ArrowLeft, 
    GraduationCap, Calendar, Layers, CheckCircle2, Building, RefreshCw
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface CourseItem {
    id: string;
    code: string;
    title: string;
    credit_units?: number;
    credits?: number;
    department?: { name: string; code?: string };
}

interface RegistrationItem {
    id: string;
    created_at: string;
    student: {
        id: string;
        matriculation_number?: string;
        matric_number?: string;
        current_level?: string;
        phone_number?: string;
        user?: {
            name: string;
            email: string;
        };
        academic_department?: {
            name: string;
            faculty?: { name: string };
        };
        programme?: {
            name: string;
        };
    };
    session?: { name: string };
    semester?: { name: string };
}

interface Props {
    courses: CourseItem[];
    sessions: any[];
    semesters: any[];
    selectedCourse?: CourseItem | null;
    registrations?: {
        data: RegistrationItem[];
        links: any[];
        total: number;
        from: number;
        to: number;
    } | null;
    stats?: {
        total_registered: number;
    };
    filters: {
        course_id?: string;
        session_id?: string;
        semester_id?: string;
        level?: string;
        search?: string;
    };
    canManageExams?: boolean;
}

const props = defineProps<Props>();

// Filter form state
const selectedCourseId = ref(props.filters.course_id || '');
const selectedSessionId = ref(props.filters.session_id || '');
const selectedSemesterId = ref(props.filters.semester_id || '');
const selectedLevel = ref(props.filters.level || '');
const searchQuery = ref(props.filters.search || '');

// Dropdown options
const courseOptions = computed(() => [
    { value: '', label: 'Select a Course to View Enrolment...' },
    ...(props.courses || []).map(c => ({
        value: c.id,
        label: `${c.code} — ${c.title}`
    }))
]);

const sessionOptions = computed(() => [
    ...(props.sessions || []).map(s => ({ value: s.id, label: s.name }))
]);

const semesterOptions = computed(() => {
    let list = props.semesters || [];
    if (selectedSessionId.value) {
        list = list.filter((sem: any) => sem.session_id === selectedSessionId.value);
    }
    return [
        { value: '', label: 'All Semesters' },
        ...list.map((sem: any) => ({ value: sem.id, label: sem.name }))
    ];
});

const levelOptions = [
    { value: '', label: 'All Academic Levels' },
    { value: '100', label: '100 Level' },
    { value: '200', label: '200 Level' },
    { value: '300', label: '300 Level' },
    { value: '400', label: '400 Level' },
    { value: '500', label: '500 Level' },
    { value: '600', label: '600 Level' },
    { value: '700', label: 'PG / 700 Level' },
];

const applyFilters = () => {
    router.get(route('admin.exams.courses.students'), {
        course_id: selectedCourseId.value,
        session_id: selectedSessionId.value,
        semester_id: selectedSemesterId.value,
        level: selectedLevel.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleCourseChange = (courseId: string) => {
    selectedCourseId.value = courseId;
    applyFilters();
};

const handleSessionChange = (sessionId: string) => {
    selectedSessionId.value = sessionId;
    selectedSemesterId.value = '';
    applyFilters();
};

const handleSemesterChange = (semesterId: string) => {
    selectedSemesterId.value = semesterId;
    applyFilters();
};

const exportRoster = () => {
    if (!props.selectedCourse) return;
    const url = route('admin.exams.courses.export_students', {
        course: props.selectedCourse.id,
        session_id: selectedSessionId.value,
        semester_id: selectedSemesterId.value,
    });
    window.location.href = url;
};

const breadcrumbs = [
    { title: 'Academic Management', href: '#' },
    { title: 'Examination Exercises', href: route('admin.exams.index') },
    { title: 'Course Enrolments', href: '#' },
];
</script>

<template>
    <Head title="Course Registered Students - Examinations" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 md:p-8 space-y-8 w-full max-w-7xl mx-auto animate-in fade-in duration-500">
            
            <!-- Sleek Top Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-7 px-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 text-xs font-semibold"
                            @click="router.visit(route('admin.exams.index'))"
                        >
                            <ArrowLeft class="w-3.5 h-3.5 mr-1" /> Back to Exams
                        </Button>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-3">
                        <Users class="w-8 h-8 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        Course Registered Candidates
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        Inspect all students registered for specific courses and download official exam candidate rosters.
                    </p>
                </div>

                <div v-if="selectedCourse && registrations && registrations.total > 0" class="flex items-center gap-2 shrink-0">
                    <Button 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs h-10 px-5 rounded-xl shadow-xs gap-2 transition-all"
                        @click="exportRoster"
                    >
                        <Download class="w-4 h-4" />
                        <span>Export Candidate Roster (CSV)</span>
                    </Button>
                </div>
            </div>

            <!-- Filter Card -->
            <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 rounded-2xl overflow-hidden">
                <CardContent class="p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Course Select -->
                        <div class="lg:col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Target Course</label>
                            <SearchableSelect
                                :model-value="selectedCourseId"
                                :items="courseOptions"
                                placeholder="Choose Course..."
                                search-placeholder="Search by course code or title..."
                                trigger-class="h-10 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 font-bold"
                                @update:model-value="handleCourseChange"
                            />
                        </div>

                        <!-- Session Select -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Academic Session</label>
                            <SearchableSelect
                                :model-value="selectedSessionId"
                                :items="sessionOptions"
                                placeholder="Select Session..."
                                search-placeholder="Search session..."
                                trigger-class="h-10 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 font-semibold"
                                @update:model-value="handleSessionChange"
                            />
                        </div>

                        <!-- Semester Select -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Semester</label>
                            <SearchableSelect
                                :model-value="selectedSemesterId"
                                :items="semesterOptions"
                                placeholder="All Semesters"
                                search-placeholder="Search semester..."
                                trigger-class="h-10 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 font-semibold"
                                @update:model-value="handleSemesterChange"
                            />
                        </div>
                    </div>

                    <!-- Secondary Row: Search & Level -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full sm:w-auto">
                            <!-- Level Select -->
                            <select 
                                v-model="selectedLevel" 
                                @change="applyFilters"
                                class="h-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 font-semibold text-slate-700 dark:text-slate-300"
                            >
                                <option v-for="lvl in levelOptions" :key="lvl.value" :value="lvl.value">{{ lvl.label }}</option>
                            </select>

                            <!-- Refresh -->
                            <Button variant="ghost" size="sm" class="h-9 px-3 text-xs text-slate-500 hover:text-indigo-600 rounded-xl" @click="applyFilters">
                                <RefreshCw class="w-3.5 h-3.5 mr-1" /> Reload
                            </Button>
                        </div>

                        <div class="relative w-full sm:w-72">
                            <Input 
                                v-model="searchQuery" 
                                @keydown.enter="applyFilters"
                                placeholder="Search matric or name..." 
                                class="pl-9 h-9 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            />
                            <Search class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Course Selected Overview Card -->
            <div v-if="selectedCourse" class="space-y-6">
                <!-- Course Info KPI Strip -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card class="border border-indigo-200/80 dark:border-indigo-800/80 bg-gradient-to-br from-indigo-50/50 to-white dark:from-indigo-950/30 dark:to-slate-900 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total Registered</span>
                                <h3 class="text-3xl font-black text-slate-900 dark:text-slate-100">{{ stats?.total_registered || 0 }}</h3>
                                <p class="text-[11px] text-slate-500">Students registered for this course</p>
                            </div>
                            <div class="p-3 bg-indigo-600 text-white rounded-xl shadow-xs">
                                <Users class="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Course Code</span>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ selectedCourse.code }}</h3>
                                <p class="text-[11px] text-slate-500 font-medium truncate max-w-[180px]">{{ selectedCourse.title }}</p>
                            </div>
                            <div class="p-3 bg-slate-100 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 rounded-xl">
                                <BookOpen class="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Credit Units</span>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ selectedCourse.credits || selectedCourse.credit_units || 0 }} Units</h3>
                                <p class="text-[11px] text-slate-500">Academic weighting</p>
                            </div>
                            <div class="p-3 bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-xl">
                                <GraduationCap class="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card class="border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Hosting Department</span>
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate max-w-[180px]">
                                    {{ selectedCourse.department?.name || 'General Department' }}
                                </h3>
                                <p class="text-[11px] text-slate-500">Course department</p>
                            </div>
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl">
                                <Building class="w-6 h-6" />
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Students Table -->
                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 rounded-2xl overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                                Registered Candidates List ({{ registrations?.total || 0 }})
                            </h3>
                            <p class="text-xs text-slate-500">
                                Students with verified course registration in this academic session.
                            </p>
                        </div>

                        <div v-if="registrations && registrations.total > 0" class="flex items-center gap-2">
                            <Button size="sm" variant="outline" class="h-8 text-xs gap-1.5 font-semibold rounded-lg" @click="exportRoster">
                                <Download class="w-3.5 h-3.5 text-emerald-600" /> Export CSV
                            </Button>
                        </div>
                    </div>

                    <div v-if="registrations && registrations.data.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-slate-50/70 dark:bg-slate-800/40">
                                <TableRow>
                                    <TableHead class="w-12 text-center text-[11px] font-bold uppercase tracking-wider">#</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-wider">Matric Number</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-wider">Student Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-wider">Department & Programme</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-wider text-center">Level</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-wider">Registered On</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="(reg, idx) in registrations.data" 
                                    :key="reg.id"
                                    class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <TableCell class="text-center text-xs font-mono text-slate-400">
                                        {{ (registrations.from || 1) + idx }}
                                    </TableCell>
                                    <TableCell class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ reg.student?.matriculation_number || reg.student?.matric_number || 'N/A' }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="space-y-0.5">
                                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                {{ reg.student?.user?.name || 'Unknown Student' }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 font-normal">
                                                {{ reg.student?.user?.email || 'N/A' }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="space-y-0.5 text-xs">
                                            <p class="font-semibold text-slate-800 dark:text-slate-200">
                                                {{ reg.student?.academic_department?.name || 'General Department' }}
                                            </p>
                                            <p class="text-[11px] text-slate-500">
                                                {{ reg.student?.programme?.name || reg.student?.academic_department?.faculty?.name || '' }}
                                            </p>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="outline" class="text-[11px] font-semibold bg-slate-50 dark:bg-slate-800">
                                            {{ reg.student?.current_level ? `${reg.student.current_level} Lvl` : 'N/A' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-xs text-slate-500 font-medium">
                                        {{ reg.created_at ? new Date(reg.created_at).toLocaleDateString() : 'N/A' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Empty Registrations State -->
                    <div v-else class="p-12 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                            <Users class="w-6 h-6" />
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Student Registrations Found</h4>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            No students have registered for {{ selectedCourse.code }} in the selected academic session and semester.
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="registrations && registrations.links && registrations.links.length > 3" class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-500">
                            Showing {{ registrations.from || 0 }} to {{ registrations.to || 0 }} of {{ registrations.total }} candidates
                        </span>
                        <div class="flex items-center gap-1">
                            <Button
                                v-for="(link, i) in registrations.links"
                                :key="i"
                                size="sm"
                                variant="outline"
                                :class="[
                                    'h-8 px-3 text-xs',
                                    link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'text-slate-600'
                                ]"
                                :disabled="!link.url"
                                @click="link.url && router.visit(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Empty Initial State (No Course Selected) -->
            <div v-else class="p-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 space-y-4 shadow-xs">
                <div class="w-16 h-16 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <BookOpen class="w-8 h-8" />
                </div>
                <div class="max-w-md mx-auto space-y-1.5">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                        Select a Course to View Registered Candidates
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Use the course selector above to inspect all student registrations, class sizes, and export attendance sheets.
                    </p>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
