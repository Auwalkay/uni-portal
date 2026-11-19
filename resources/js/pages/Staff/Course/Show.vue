<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    BookOpen, Users, ArrowLeft, Download, Search, MoreHorizontal,
    Upload, FileSpreadsheet, AlertTriangle, CheckCircle2, X, Loader2,
    Calendar, CalendarCheck, Trash2, ShieldCheck, User, Filter, RotateCcw,
    Building2, GraduationCap, Layers, CheckSquare, Square, Eye
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle
} from '@/components/ui/dialog';
import { ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    course: { id: string; code: string; title: string; units: number };
    session: { id: string; name: string };
    students: Array<{
        id: string;
        student_id?: string;
        student_name: string;
        matriculation_number: string;
        email: string;
        department_id?: string;
        department: string;
        program_id?: string;
        program?: string;
        level: string;
        registered_at: string;
        registered_by_id?: string;
        registered_by_name?: string;
        registration_source?: string;
        is_self_registered?: boolean;
    }>;
    departments?: Array<{ id: string; name: string; count: number }>;
    programs?: Array<{ id: string; name: string; count: number }>;
    levels?: string[];
    attendanceHistory?: Array<{
        id?: string;
        date: string;
        formatted_date: string;
        total_records: number;
        present_count: number;
        absent_count: number;
        attendance_rate: number;
        department_ids?: string[] | null;
        department_names?: string[];
        is_all_departments?: boolean;
        notes?: string | null;
    }>;
    importSummary?: {
        enrolled: number;
        skipped: number;
        errors: Array<{ row: number; matric: string; reason: string }>;
    } | null;
    attendanceSummary?: {
        processed: number;
        present: number;
        absent: number;
        unregistered: number;
        errors: Array<{ row: number; matric: string; reason: string }>;
    } | null;
}>();

const activeTab = ref<'students' | 'attendance'>('students');

// Filters state for Enrolled Students
const search = ref('');
const selectedDepartment = ref('all');
const selectedProgram = ref('all');
const selectedLevel = ref('all');

const hasActiveFilters = computed(() => {
    return selectedDepartment.value !== 'all' ||
        selectedProgram.value !== 'all' ||
        selectedLevel.value !== 'all' ||
        search.value.trim() !== '';
});

const resetFilters = () => {
    selectedDepartment.value = 'all';
    selectedProgram.value = 'all';
    selectedLevel.value = 'all';
    search.value = '';
};

// Filtered students computation
const filteredStudents = computed(() => {
    return props.students.filter(s => {
        // Department filter
        if (selectedDepartment.value !== 'all') {
            if (s.department_id !== selectedDepartment.value && s.department !== selectedDepartment.value) {
                return false;
            }
        }
        // Program filter
        if (selectedProgram.value !== 'all') {
            if (s.program_id !== selectedProgram.value && s.program !== selectedProgram.value) {
                return false;
            }
        }
        // Level filter
        if (selectedLevel.value !== 'all') {
            if (String(s.level) !== String(selectedLevel.value)) {
                return false;
            }
        }
        // Search text
        if (search.value) {
            const lower = search.value.toLowerCase().trim();
            const matchName = (s.student_name || '').toLowerCase().includes(lower);
            const matchMatric = (s.matriculation_number || '').toLowerCase().includes(lower);
            const matchEmail = (s.email || '').toLowerCase().includes(lower);
            const matchDept = (s.department || '').toLowerCase().includes(lower);
            const matchProg = (s.program || '').toLowerCase().includes(lower);
            if (!matchName && !matchMatric && !matchEmail && !matchDept && !matchProg) {
                return false;
            }
        }
        return true;
    });
});

// Dynamic export URL for students with active filters
const exportStudentsUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedDepartment.value !== 'all') params.append('department_id', selectedDepartment.value);
    if (selectedProgram.value !== 'all') params.append('program_id', selectedProgram.value);
    if (selectedLevel.value !== 'all') params.append('level', selectedLevel.value);
    const qs = params.toString();
    return route('admin.teaching.courses.students.export', props.course.id) + (qs ? `?${qs}` : '');
});

// Attendance Register Department Filter
const registerDeptFilter = ref('all');

const filteredAttendanceHistory = computed(() => {
    if (!props.attendanceHistory) return [];
    if (registerDeptFilter.value === 'all') return props.attendanceHistory;

    return props.attendanceHistory.filter(att => {
        // If All Departments, it applies to this department
        if (att.is_all_departments || !att.department_ids || att.department_ids.length === 0) {
            return true;
        }
        return att.department_ids.includes(registerDeptFilter.value);
    });
});

const exportAttendanceUrl = computed(() => {
    const params = new URLSearchParams();
    if (registerDeptFilter.value !== 'all') params.append('department_id', registerDeptFilter.value);
    const qs = params.toString();
    return route('admin.teaching.courses.attendance.export', props.course.id) + (qs ? `?${qs}` : '');
});

const breadcrumbs = [
    { title: 'Teaching Schedule', href: route().has('admin.teaching.courses.index') ? route('admin.teaching.courses.index') : '/admin/dashboard' },
    { title: props.course.code, href: '#' }
];

// Upload dialog & state (Student enrolment)
const showUploadModal = ref(false);
const showResultsModal = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const form = useForm({
    file: null as File | null,
    skip_existing: true,
});

watch(() => props.importSummary, (newVal) => {
    if (newVal) {
        showResultsModal.value = true;
    }
}, { immediate: true });

const onFileSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFile.value = target.files[0];
        form.file = target.files[0];
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const removeFile = () => {
    selectedFile.value = null;
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitUpload = () => {
    if (!form.file) return;

    form.post(route('admin.teaching.courses.students.upload', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            removeFile();
            if (props.importSummary && (props.importSummary.enrolled > 0 || props.importSummary.errors.length > 0)) {
                showResultsModal.value = true;
            }
        },
    });
};

// Attendance upload state & handlers (Department-Aware)
const showAttendanceModal = ref(false);
const showAttendanceSummaryModal = ref(false);
const attendanceFileInput = ref<HTMLInputElement | null>(null);
const selectedAttendanceFile = ref<File | null>(null);

// Cohort selection for lecture attendance
const cohortType = ref<'all' | 'specific'>('all');
const selectedCohortDepartmentIds = ref<string[]>([]);

const attendanceForm = useForm({
    attendance_date: new Date().toISOString().split('T')[0],
    department_ids: [] as string[],
    notes: '',
    file: null as File | null,
});

watch(() => props.attendanceSummary, (newVal) => {
    if (newVal) {
        showAttendanceSummaryModal.value = true;
    }
}, { immediate: true });

const onAttendanceFileSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedAttendanceFile.value = target.files[0];
        attendanceForm.file = target.files[0];
    }
};

const triggerAttendanceFileInput = () => {
    attendanceFileInput.value?.click();
};

const removeAttendanceFile = () => {
    selectedAttendanceFile.value = null;
    attendanceForm.file = null;
    if (attendanceFileInput.value) {
        attendanceFileInput.value.value = '';
    }
};

const toggleDepartmentCohort = (deptId: string) => {
    const idx = selectedCohortDepartmentIds.value.indexOf(deptId);
    if (idx > -1) {
        selectedCohortDepartmentIds.value.splice(idx, 1);
    } else {
        selectedCohortDepartmentIds.value.push(deptId);
    }
};

const selectAllDepartments = () => {
    if (props.departments) {
        selectedCohortDepartmentIds.value = props.departments.map(d => d.id);
    }
};

const clearAllDepartments = () => {
    selectedCohortDepartmentIds.value = [];
};

// Compute student count for selected cohort in upload modal
const cohortStudentCount = computed(() => {
    if (cohortType.value === 'all') {
        return props.students.length;
    }
    if (!props.departments) return 0;
    return props.departments
        .filter(d => selectedCohortDepartmentIds.value.includes(d.id))
        .reduce((sum, d) => sum + d.count, 0);
});

// Dynamic download template URL (filters to selected departments if specific cohort)
const attendanceTemplateUrl = computed(() => {
    const params = new URLSearchParams();
    if (cohortType.value === 'specific' && selectedCohortDepartmentIds.value.length > 0) {
        selectedCohortDepartmentIds.value.forEach(id => params.append('department_ids[]', id));
    }
    const qs = params.toString();
    return route('admin.teaching.courses.attendance.template', props.course.id) + (qs ? `?${qs}` : '');
});

const submitAttendanceUpload = () => {
    if (!attendanceForm.file || !attendanceForm.attendance_date) return;

    if (cohortType.value === 'specific' && selectedCohortDepartmentIds.value.length === 0) {
        alert('Please select at least one department cohort for this lecture session.');
        return;
    }

    attendanceForm.department_ids = cohortType.value === 'all' ? [] : [...selectedCohortDepartmentIds.value];

    attendanceForm.post(route('admin.teaching.courses.attendance.upload', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAttendanceModal.value = false;
            removeAttendanceFile();
            selectedCohortDepartmentIds.value = [];
            cohortType.value = 'all';
        },
    });
};

const deleteAttendanceDate = (date: string) => {
    if (confirm(`Are you sure you want to delete attendance records for ${date}?`)) {
        router.delete(route('admin.teaching.courses.attendance.delete', [props.course.id, date]), {
            preserveScroll: true,
        });
    }
};

const showDateDetailsModal = ref(false);
const dateDetailsLoading = ref(false);
const dateDetailsSearch = ref('');
const dateDetailsStatus = ref<'all' | 'present' | 'absent'>('all');
const selectedDateMeta = ref<{
    date: string;
    formatted_date: string;
    present_count: number;
    absent_count: number;
    attendance_rate: number;
} | null>(null);
const dateDetailsRecords = ref<Array<{
    id: string;
    student_id: string;
    student_name: string;
    matriculation_number: string;
    department: string;
    status: string;
    remarks?: string | null;
}>>([]);

async function openDateDetails(att: {
    date: string;
    formatted_date: string;
    present_count: number;
    absent_count: number;
    attendance_rate: number;
}) {
    selectedDateMeta.value = att;
    dateDetailsSearch.value = '';
    dateDetailsStatus.value = 'all';
    dateDetailsRecords.value = [];
    showDateDetailsModal.value = true;
    dateDetailsLoading.value = true;

    try {
        const res = await fetch(route('admin.teaching.courses.attendance.details', [props.course.id, att.date]), {
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const data = await res.json();
            dateDetailsRecords.value = data.records || [];
        }
    } finally {
        dateDetailsLoading.value = false;
    }
}

const filteredDateDetailsRecords = computed(() => {
    const q = dateDetailsSearch.value.trim().toLowerCase();
    return dateDetailsRecords.value.filter((r) => {
        if (dateDetailsStatus.value !== 'all' && r.status !== dateDetailsStatus.value) {
            return false;
        }
        if (q) {
            return (
                r.student_name.toLowerCase().includes(q) ||
                r.matriculation_number.toLowerCase().includes(q) ||
                r.department.toLowerCase().includes(q)
            );
        }
        return true;
    });
});

const avgAttendanceRate = computed(() => {
    if (!props.attendanceHistory || props.attendanceHistory.length === 0) return 0;
    const totalPresent = props.attendanceHistory.reduce((sum, h) => sum + h.present_count, 0);
    const totalRecords = props.attendanceHistory.reduce((sum, h) => sum + h.total_records, 0);
    return totalRecords > 0 ? Number(((totalPresent / totalRecords) * 100).toFixed(1)) : 0;
});
</script>

<template>
    <Head :title="`${course.code} - ${course.title}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-3.5 sm:p-6 md:p-8 space-y-4 sm:space-y-6 max-w-7xl mx-auto min-h-screen w-full min-w-0 max-w-full overflow-x-hidden">

            <!-- 1. Responsive Executive Course Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3.5 sm:gap-4 bg-white dark:bg-slate-900 p-4 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs w-full min-w-0">
                <div class="flex items-start sm:items-center gap-3 sm:gap-4 w-full min-w-0 flex-1">
                    <Button variant="outline" size="icon" as-child class="rounded-xl h-9 w-9 sm:h-10 sm:w-10 shrink-0 border-slate-200 dark:border-slate-700">
                        <Link :href="route().has('admin.teaching.courses.index') ? route('admin.teaching.courses.index') : '/admin/dashboard'">
                            <ArrowLeft class="w-4 h-4 text-slate-700 dark:text-slate-300" />
                        </Link>
                    </Button>

                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                            <span class="font-mono font-black text-xs sm:text-sm px-2 sm:px-2.5 py-0.5 rounded-lg bg-slate-900 text-white dark:bg-white dark:text-slate-900 tracking-tight shrink-0">
                                {{ course.code }}
                            </span>
                            <Badge variant="outline" class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 border-slate-200 dark:border-slate-700 shrink-0">
                                {{ course.units }} Units
                            </Badge>
                            <Badge variant="secondary" class="text-[10px] sm:text-xs font-semibold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 shrink-0">
                                {{ session.name }} Session
                            </Badge>
                        </div>
                        <h1 class="text-lg sm:text-xl md:text-2xl font-bold tracking-tight text-slate-900 dark:text-white break-words">
                            {{ course.title }}
                        </h1>
                    </div>
                </div>

                <!-- Header Actions (Touch-friendly 2-col on mobile, flex on desktop) -->
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full lg:w-auto pt-1 sm:pt-0 shrink-0">
                    <template v-if="activeTab === 'students'">
                        <a :href="exportStudentsUrl" class="w-full sm:w-auto">
                            <Button variant="outline" size="sm" class="w-full h-9 gap-1.5 text-xs font-semibold rounded-xl border-slate-200 dark:border-slate-700 justify-center">
                                <Download class="w-3.5 h-3.5 text-slate-600 dark:text-slate-400 shrink-0" />
                                <span>Export List</span>
                            </Button>
                        </a>
                        <Button
                            size="sm"
                            class="w-full sm:w-auto h-9 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 gap-1.5 text-xs font-bold rounded-xl shadow-xs justify-center cursor-pointer"
                            @click="showUploadModal = true"
                        >
                            <Upload class="w-3.5 h-3.5 shrink-0" />
                            <span>Upload Students</span>
                        </Button>
                    </template>

                    <template v-else>
                        <a :href="exportAttendanceUrl" class="w-full sm:w-auto">
                            <Button variant="outline" size="sm" class="w-full h-9 gap-1.5 text-xs font-semibold rounded-xl border-slate-200 dark:border-slate-700 justify-center">
                                <Download class="w-3.5 h-3.5 text-slate-600 dark:text-slate-400 shrink-0" />
                                <span>Export CSV</span>
                            </Button>
                        </a>
                        <Button
                            size="sm"
                            class="w-full sm:w-auto h-9 bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5 text-xs font-bold rounded-xl shadow-xs justify-center cursor-pointer"
                            @click="showAttendanceModal = true"
                        >
                            <CalendarCheck class="w-3.5 h-3.5 shrink-0" />
                            <span>Upload Attendance</span>
                        </Button>
                    </template>
                </div>
            </div>

            <!-- 2. Responsive Navigation Tabs -->
            <div class="w-full min-w-0 max-w-full overflow-x-auto scrollbar-none flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-px">
                <button
                    type="button"
                    @click="activeTab = 'students'"
                    class="pb-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 sm:gap-2 cursor-pointer whitespace-nowrap shrink-0"
                    :class="activeTab === 'students'
                        ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400 font-extrabold'
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                >
                    <Users class="w-4 h-4" />
                    <span>Enrolled Students</span>
                    <span
                        class="text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-bold"
                        :class="activeTab === 'students' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                        {{ students.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'attendance'"
                    class="pb-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 sm:gap-2 cursor-pointer whitespace-nowrap shrink-0"
                    :class="activeTab === 'attendance'
                        ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400 font-extrabold'
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                >
                    <CalendarCheck class="w-4 h-4" />
                    <span>Lecture Attendance</span>
                    <span
                        class="text-[10px] sm:text-[11px] px-2 py-0.5 rounded-full font-bold"
                        :class="activeTab === 'attendance' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                        {{ attendanceHistory?.length || 0 }} Dates
                    </span>
                </button>
            </div>

            <!-- 3. TAB 1: Unified Enrolled Students View with Multi-Attribute Filter Bar -->
            <div v-if="activeTab === 'students'" class="space-y-4 w-full min-w-0">
                <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs rounded-2xl overflow-hidden w-full min-w-0">
                    <CardHeader class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div class="space-y-0.5">
                                <CardTitle class="text-sm sm:text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>Class Roster</span>
                                    <Badge variant="outline" class="text-xs font-semibold font-mono">
                                        {{ filteredStudents.length }} of {{ students.length }}
                                    </Badge>
                                </CardTitle>
                                <CardDescription class="text-xs text-muted-foreground">
                                    Students confirmed registered for {{ course.code }}.
                                </CardDescription>
                            </div>

                            <!-- Reset filters button if any filter is active -->
                            <Button
                                v-if="hasActiveFilters"
                                variant="ghost"
                                size="sm"
                                class="h-8 text-xs text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 gap-1.5 cursor-pointer px-2"
                                @click="resetFilters"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span>Reset Filters</span>
                            </Button>
                        </div>

                        <!-- Multi-Attribute Filter Bar (Search, Department, Program, Level) -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5 pt-1">
                            <!-- Search by keyword (Full width on mobile, 1 col on desktop) -->
                            <div class="col-span-2 lg:col-span-1 relative w-full">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                <Input
                                    v-model="search"
                                    placeholder="Search name, matric..."
                                    class="pl-9 h-9 text-xs bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 rounded-xl w-full"
                                />
                            </div>

                            <!-- Department Filter (Full width on mobile, 1 col on tablet/desktop) -->
                            <div class="col-span-2 sm:col-span-1 lg:col-span-1 relative w-full">
                                <select
                                    v-model="selectedDepartment"
                                    class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer"
                                >
                                    <option value="all">All Departments ({{ students.length }})</option>
                                    <option
                                        v-for="dept in (departments || [])"
                                        :key="dept.id"
                                        :value="dept.id"
                                    >
                                        {{ dept.name }} ({{ dept.count }})
                                    </option>
                                </select>
                            </div>

                            <!-- Programme Filter (1 col on mobile and tablet) -->
                            <div class="col-span-1 relative w-full">
                                <select
                                    v-model="selectedProgram"
                                    class="w-full h-9 px-2.5 sm:px-3 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer truncate"
                                >
                                    <option value="all">All Programmes</option>
                                    <option
                                        v-for="prog in (programs || [])"
                                        :key="prog.id"
                                        :value="prog.id"
                                    >
                                        {{ prog.name }} ({{ prog.count }})
                                    </option>
                                </select>
                            </div>

                            <!-- Level Filter (1 col on mobile and tablet) -->
                            <div class="col-span-1 relative w-full">
                                <select
                                    v-model="selectedLevel"
                                    class="w-full h-9 px-2.5 sm:px-3 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer"
                                >
                                    <option value="all">All Levels</option>
                                    <option
                                        v-for="lvl in (levels || [])"
                                        :key="lvl"
                                        :value="lvl"
                                    >
                                        {{ lvl }} Level
                                    </option>
                                </select>
                            </div>
                        </div>
                    </CardHeader>

                    <!-- Mobile Student Cards List (< 640px) -->
                    <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
                        <div
                            v-for="(student, index) in filteredStudents"
                            :key="student.id"
                            class="p-3.5 space-y-2 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center justify-center shrink-0 uppercase border border-slate-200/80 dark:border-slate-700">
                                        {{ student.student_name ? student.student_name.charAt(0) : 'S' }}
                                    </div>
                                    <div class="space-y-0.5 min-w-0 flex-1">
                                        <p class="font-bold text-xs text-slate-900 dark:text-white uppercase truncate">
                                            {{ student.student_name }}
                                        </p>
                                        <p class="text-[11px] text-muted-foreground font-mono truncate">
                                            {{ student.email }}
                                        </p>
                                    </div>
                                </div>
                                <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700 shrink-0">
                                    {{ student.matriculation_number }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs text-muted-foreground pt-0.5 flex-wrap gap-1.5 pl-10.5">
                                <div class="truncate max-w-[200px]">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ student.department }}</span>
                                    <span v-if="student.program && student.program !== student.department" class="text-[10px] text-slate-400 block truncate">
                                        {{ student.program }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                                    <Badge variant="outline" class="text-[10px] font-bold px-1.5 py-0">
                                        {{ student.level }} Level
                                    </Badge>
                                    <span v-if="student.registration_source === 'admin'" class="text-[9px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800">
                                        Admin
                                    </span>
                                    <span v-else-if="student.registration_source === 'import'" class="text-[9px] font-semibold text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-1.5 py-0.5 rounded border border-indigo-200 dark:border-indigo-800">
                                        Import
                                    </span>
                                    <span class="text-[10px] text-slate-400">{{ student.registered_at }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredStudents.length === 0" class="p-8 text-center text-muted-foreground space-y-2">
                            <Users class="w-8 h-8 mx-auto text-slate-300" />
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">No students match the current filters.</p>
                            <Button v-if="hasActiveFilters" variant="outline" size="sm" class="text-xs mt-2" @click="resetFilters">
                                Reset Filters
                            </Button>
                        </div>
                    </div>

                    <!-- Desktop & Tablet Table (>= 640px) -->
                    <div class="hidden sm:block w-full min-w-0 max-w-full overflow-x-auto">
                        <Table class="min-w-[700px]">
                            <TableHeader class="bg-slate-50/70 dark:bg-slate-800/50">
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="w-[50px] text-xs font-bold uppercase text-center">#</TableHead>
                                    <TableHead class="text-xs font-bold uppercase">Student Name</TableHead>
                                    <TableHead class="text-xs font-bold uppercase">Matric Number</TableHead>
                                    <TableHead class="text-xs font-bold uppercase">Department</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-center">Level</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-center">Registered On</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-for="(student, index) in filteredStudents"
                                    :key="student.id"
                                    class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <TableCell class="text-center text-xs text-muted-foreground font-mono">{{ index + 1 }}</TableCell>

                                    <TableCell>
                                        <div class="flex flex-col py-0.5">
                                            <span class="font-bold text-xs text-slate-900 dark:text-white uppercase tracking-tight">
                                                {{ student.student_name }}
                                            </span>
                                            <span class="text-[11px] text-muted-foreground font-mono">
                                                {{ student.email }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700">
                                            {{ student.matriculation_number }}
                                        </span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-medium text-slate-800 dark:text-slate-200">
                                                {{ student.department }}
                                            </span>
                                            <span v-if="student.program && student.program !== student.department" class="text-[10px] text-muted-foreground">
                                                {{ student.program }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-center">
                                        <Badge variant="outline" class="text-[10px] font-bold px-2 py-0.5 border-slate-200 dark:border-slate-700">
                                            {{ student.level }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-center text-xs text-muted-foreground">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span>{{ student.registered_at }}</span>
                                            <span 
                                                v-if="student.registration_source === 'admin'" 
                                                class="inline-flex items-center text-[10px] font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800"
                                                :title="student.registered_by_name ? `Registered by Admin: ${student.registered_by_name}` : 'Registered by Administrator'"
                                            >
                                                Admin: {{ student.registered_by_name || 'Staff' }}
                                            </span>
                                            <span 
                                                v-else-if="student.registration_source === 'import'" 
                                                class="inline-flex items-center text-[10px] font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-1.5 py-0.5 rounded border border-indigo-200 dark:border-indigo-800"
                                                title="Enrolled via bulk CSV/Excel roster import"
                                            >
                                                Bulk Import
                                            </span>
                                            <span 
                                                v-else 
                                                class="inline-flex items-center text-[10px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800"
                                                title="Self-registered by student via portal"
                                            >
                                                Self-Registered
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" class="h-8 w-8 p-0 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                                                    <span class="sr-only">Open menu</span>
                                                    <MoreHorizontal class="h-4 w-4 text-slate-500" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="text-xs">
                                                <DropdownMenuLabel>Student Details</DropdownMenuLabel>
                                                <DropdownMenuItem disabled>
                                                    {{ student.department }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem disabled>
                                                    {{ student.level }} Level
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="filteredStudents.length === 0">
                                    <TableCell colspan="7" class="h-40 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <Users class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                                {{ hasActiveFilters ? 'No students match your selected filters.' : 'No students enrolled in this course yet.' }}
                                            </p>
                                            <Button v-if="hasActiveFilters" variant="outline" size="sm" class="text-xs mt-1" @click="resetFilters">
                                                Reset Filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            <!-- 4. TAB 2: Attendance Register View with Cohort Details -->
            <div v-else class="space-y-4 sm:space-y-6 w-full min-w-0">
                <!-- Attendance Overview KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 w-full min-w-0">
                    <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                        <CardContent class="p-4 sm:p-5 flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-[11px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider">Total Lectures Logged</p>
                                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ attendanceHistory?.length || 0 }}</p>
                                <p class="text-[11px] text-muted-foreground">Recorded lecture dates</p>
                            </div>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <Calendar class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                        <CardContent class="p-4 sm:p-5 flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-[11px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider">Average Attendance</p>
                                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ avgAttendanceRate }}%</p>
                                <p class="text-[11px] text-muted-foreground">Across all recorded classes</p>
                            </div>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <ShieldCheck class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs flex flex-col justify-between">
                        <CardContent class="p-4 sm:p-5 space-y-2">
                            <p class="text-[11px] sm:text-xs font-bold text-muted-foreground uppercase tracking-wider">Class Attendance Sheet</p>
                            <p class="text-[11px] sm:text-xs text-muted-foreground leading-relaxed">
                                Download pre-filled template with enrolled students or by specific department.
                            </p>
                            <Button
                                size="sm"
                                variant="outline"
                                class="w-full h-8 text-xs font-bold gap-1.5 border-slate-200 dark:border-slate-700 cursor-pointer"
                                @click="showAttendanceModal = true"
                            >
                                <Download class="w-3.5 h-3.5" /> Download Template / Upload
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Attendance History Table with Department Cohort Badges -->
                <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs rounded-2xl overflow-hidden w-full min-w-0">
                    <CardHeader class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <CardTitle class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Uploaded Lecture Sessions</CardTitle>
                                <CardDescription class="text-xs text-muted-foreground">
                                    All attendance sessions recorded for {{ course.code }} in {{ session.name }}.
                                </CardDescription>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Department filter for history -->
                                <select
                                    v-if="departments && departments.length > 1"
                                    v-model="registerDeptFilter"
                                    class="h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium cursor-pointer"
                                >
                                    <option value="all">All Department Cohorts</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">
                                        {{ d.name }}
                                    </option>
                                </select>

                                <Button
                                    size="sm"
                                    class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5 text-xs font-bold rounded-xl shadow-xs cursor-pointer"
                                    @click="showAttendanceModal = true"
                                >
                                    <Upload class="w-3.5 h-3.5" />
                                    <span>Upload New Date</span>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <!-- Mobile Attendance Date Cards (< 640px) -->
                    <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
                        <div
                            v-for="att in filteredAttendanceHistory"
                            :key="att.date"
                            class="p-4 space-y-2.5 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-emerald-600 shrink-0" />
                                    <span class="font-bold text-xs text-slate-900 dark:text-white">{{ att.formatted_date }}</span>
                                </div>
                                <span class="text-xs font-black" :class="att.attendance_rate >= 75 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ att.attendance_rate }}% Rate
                                </span>
                            </div>

                            <!-- Cohort badge mobile -->
                            <div>
                                <Badge
                                    v-if="att.is_all_departments || !att.department_names || att.department_names.length === 0"
                                    variant="outline"
                                    class="text-[10px] font-semibold text-slate-600 dark:text-slate-400 border-slate-200"
                                >
                                    All Departments
                                </Badge>
                                <Badge
                                    v-else-if="att.department_names.length === 1"
                                    class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border-indigo-200 text-[10px] font-semibold"
                                >
                                    {{ att.department_names[0] }}
                                </Badge>
                                <div v-else class="space-y-1">
                                    <Badge class="bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 text-[10px] font-semibold">
                                        Mixed Cohort ({{ att.department_names.length }})
                                    </Badge>
                                    <p class="text-[10px] text-muted-foreground truncate">{{ att.department_names.join(', ') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <div class="flex items-center gap-1.5">
                                    <Badge class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 text-[10px] font-bold px-1.5 py-0">
                                        {{ att.present_count }} Present
                                    </Badge>
                                    <Badge class="bg-rose-50 text-rose-700 dark:bg-rose-950/40 text-[10px] font-bold px-1.5 py-0">
                                        {{ att.absent_count }} Absent
                                    </Badge>
                                </div>

                                <div class="flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 h-7 text-xs font-semibold px-2"
                                        @click="openDateDetails(att)"
                                    >
                                        <Eye class="w-3.5 h-3.5 mr-1" />
                                        View
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 h-7 text-xs font-semibold px-2"
                                        @click="deleteAttendanceDate(att.date)"
                                    >
                                    <Trash2 class="w-3 h-3 mr-1" /> Delete
                                </Button>
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredAttendanceHistory.length === 0" class="p-8 text-center text-muted-foreground space-y-2">
                            <CalendarCheck class="w-8 h-8 mx-auto text-slate-300" />
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">No attendance uploaded for this filter.</p>
                        </div>
                    </div>

                    <!-- Desktop & Tablet Table (>= 640px) -->
                    <div class="hidden sm:block w-full min-w-0 max-w-full overflow-x-auto">
                        <Table class="min-w-[750px]">
                            <TableHeader class="bg-slate-50/70 dark:bg-slate-800/50">
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="w-[50px] text-xs font-bold uppercase text-center">#</TableHead>
                                    <TableHead class="text-xs font-bold uppercase">Lecture Date</TableHead>
                                    <TableHead class="text-xs font-bold uppercase">Department Cohort</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-center">Total Logged</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-center">Present</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-center">Absent</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-center">Rate</TableHead>
                                    <TableHead class="text-xs font-bold uppercase text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-for="(att, idx) in filteredAttendanceHistory"
                                    :key="att.date"
                                    class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                                >
                                    <TableCell class="text-center text-xs text-muted-foreground font-mono">{{ idx + 1 }}</TableCell>

                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Calendar class="w-4 h-4 text-emerald-600 shrink-0" />
                                            <div>
                                                <p class="font-bold text-xs text-slate-900 dark:text-white">{{ att.formatted_date }}</p>
                                                <p class="text-[10px] text-muted-foreground font-mono">{{ att.date }}</p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Cohort Column -->
                                    <TableCell>
                                        <div v-if="att.is_all_departments || !att.department_names || att.department_names.length === 0">
                                            <Badge variant="outline" class="text-[10px] font-semibold text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700">
                                                All Departments
                                            </Badge>
                                        </div>
                                        <div v-else-if="att.department_names.length === 1">
                                            <Badge class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-semibold text-[10px]">
                                                {{ att.department_names[0] }}
                                            </Badge>
                                        </div>
                                        <div v-else class="space-y-0.5">
                                            <Badge class="bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800 font-semibold text-[10px]">
                                                Mixed ({{ att.department_names.length }} Departments)
                                            </Badge>
                                            <p class="text-[10px] text-muted-foreground max-w-xs truncate" :title="att.department_names.join(', ')">
                                                {{ att.department_names.join(', ') }}
                                            </p>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-center font-bold text-xs">{{ att.total_records }}</TableCell>

                                    <TableCell class="text-center">
                                        <Badge class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800 font-bold text-[10px]">
                                            {{ att.present_count }} Present
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-center">
                                        <Badge class="bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800 font-bold text-[10px]">
                                            {{ att.absent_count }} Absent
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-16 bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="att.attendance_rate >= 75 ? 'bg-emerald-500' : (att.attendance_rate >= 60 ? 'bg-amber-500' : 'bg-rose-500')"
                                                    :style="{ width: `${Math.min(100, att.attendance_rate)}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-xs font-bold">{{ att.attendance_rate }}%</span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 h-8 gap-1 text-xs font-semibold rounded-lg cursor-pointer"
                                                @click="openDateDetails(att)"
                                            >
                                                <Eye class="w-3.5 h-3.5" />
                                                <span>View Details</span>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 h-8 gap-1 text-xs font-semibold rounded-lg cursor-pointer"
                                                @click="deleteAttendanceDate(att.date)"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                                <span>Delete</span>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="filteredAttendanceHistory.length === 0">
                                    <TableCell colspan="8" class="h-36 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <CalendarCheck class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No attendance records found for this cohort.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
        </div>

        <!-- 5. Upload Students Dialog Modal -->
        <Dialog v-model:open="showUploadModal">
            <DialogContent class="w-[calc(100vw-1.5rem)] max-w-[560px] p-4 sm:p-6 max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base sm:text-lg">
                        <FileSpreadsheet class="w-5 h-5 text-slate-800 dark:text-white" />
                        <span>Upload Students for {{ course.code }}</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm">
                        Bulk enroll students to <strong>{{ course.code }} - {{ course.title }}</strong> for the <strong>{{ session.name }}</strong> session using an Excel or CSV file.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitUpload" class="space-y-4 pt-2">
                    <!-- Template Download Helper -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                        <div class="text-xs text-slate-900 dark:text-slate-200 min-w-0">
                            <span class="font-bold">Need the standard template?</span>
                            <p class="text-[11px] text-muted-foreground">Includes required matriculation_number column.</p>
                        </div>
                        <a :href="route('admin.teaching.courses.students.template', course.id)" download class="shrink-0 w-full sm:w-auto">
                            <Button type="button" variant="outline" size="sm" class="w-full sm:w-auto h-8 text-xs gap-1.5 font-bold border-slate-200 dark:border-slate-700 justify-center">
                                <Download class="w-3.5 h-3.5" /> Sample
                            </Button>
                        </a>
                    </div>

                    <!-- Drag & Drop Zone -->
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="onFileSelected"
                    />

                    <div
                        v-if="!selectedFile"
                        @click="triggerFileInput"
                        class="border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-slate-400 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-slate-50/50 dark:bg-slate-800/20 flex flex-col items-center justify-center gap-2"
                    >
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300">
                            <Upload class="w-6 h-6" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Click to upload spreadsheet</p>
                            <p class="text-xs text-muted-foreground">Supports .xlsx, .xls, and .csv (Max 10MB)</p>
                        </div>
                    </div>

                    <!-- Selected File Card -->
                    <div v-else class="p-3 border border-slate-200 dark:border-slate-700 rounded-xl flex items-center justify-between bg-slate-50 dark:bg-slate-800/40">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                XLS
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ selectedFile.name }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
                            </div>
                        </div>
                        <Button type="button" variant="ghost" size="sm" class="h-8 w-8 p-0 text-slate-400 hover:text-red-600 shrink-0" @click="removeFile">
                            <X class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- Option checkbox -->
                    <div class="flex items-center gap-2">
                        <input
                            id="skip_existing"
                            type="checkbox"
                            v-model="form.skip_existing"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-500 h-4 w-4 shrink-0"
                        />
                        <label for="skip_existing" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                            Skip students already enrolled in this course
                        </label>
                    </div>

                    <div v-if="form.errors.file" class="text-xs text-red-600 font-bold">
                        {{ form.errors.file }}
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0 pt-2">
                        <Button type="button" variant="outline" @click="showUploadModal = false">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900 font-bold gap-2 cursor-pointer"
                            :disabled="!selectedFile || form.processing"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <Upload v-else class="w-4 h-4" />
                            Enroll Students
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 6. Enrolment Results Summary Modal -->
        <Dialog v-model:open="showResultsModal">
            <DialogContent class="w-[calc(100vw-1.5rem)] max-w-[620px] p-4 sm:p-6 max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base sm:text-lg">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600" />
                        Enrolment Upload Summary
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Summary of processed student enrolments for {{ course.code }}.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="importSummary" class="space-y-4 pt-2">
                    <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center">
                        <div class="p-2.5 sm:p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div class="text-xl sm:text-2xl font-black text-emerald-700">{{ importSummary.enrolled }}</div>
                            <div class="text-[10px] sm:text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Enrolled</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <div class="text-xl sm:text-2xl font-black text-amber-700">{{ importSummary.skipped }}</div>
                            <div class="text-[10px] sm:text-[11px] font-bold text-amber-800 uppercase tracking-wider">Skipped</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-rose-50 border border-rose-200 rounded-xl">
                            <div class="text-xl sm:text-2xl font-black text-rose-700">{{ importSummary.errors.length }}</div>
                            <div class="text-[10px] sm:text-[11px] font-bold text-rose-800 uppercase tracking-wider">Errors</div>
                        </div>
                    </div>

                    <!-- List of failures/errors if any -->
                    <div v-if="importSummary.errors.length > 0" class="space-y-2">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <AlertTriangle class="w-4 h-4 text-amber-600" /> Unprocessed Records ({{ importSummary.errors.length }})
                        </p>
                        <div class="max-h-48 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 p-2 text-xs divide-y divide-slate-200 dark:divide-slate-700">
                            <div v-for="(err, idx) in importSummary.errors" :key="idx" class="py-2 px-1 flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">Row {{ err.row }}:</span>
                                    <span class="font-mono ml-1 text-slate-900 dark:text-white bg-slate-200 dark:bg-slate-700 px-1 py-0.5 rounded">{{ err.matric }}</span>
                                    <p class="text-[11px] text-rose-600 mt-0.5">{{ err.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button class="bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:text-slate-900" @click="showResultsModal = false">
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- 7. Department-Aware Upload Attendance Dialog Modal -->
        <Dialog v-model:open="showAttendanceModal">
            <DialogContent class="w-[calc(100vw-1.5rem)] max-w-[580px] p-4 sm:p-6 max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base sm:text-lg">
                        <CalendarCheck class="w-5 h-5 text-emerald-600" />
                        <span>Upload Lecture Attendance</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm">
                        Select date, choose the target department cohort, and upload student attendance for <strong>{{ course.code }}</strong>.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAttendanceUpload" class="space-y-4 pt-1">
                    <!-- Lecture Date Picker -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Lecture Date <span class="text-rose-500">*</span>
                        </label>
                        <Input
                            type="date"
                            v-model="attendanceForm.attendance_date"
                            required
                            class="h-10 text-sm bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl"
                        />
                        <p v-if="attendanceForm.errors.attendance_date" class="text-xs text-rose-500 font-bold">
                            {{ attendanceForm.errors.attendance_date }}
                        </p>
                    </div>

                    <!-- Department Cohort Selector -->
                    <div class="space-y-2 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 bg-slate-50/70 dark:bg-slate-850">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <Building2 class="w-3.5 h-3.5 text-slate-500" />
                                <span>Target Department Cohort</span>
                            </label>
                            <Badge variant="outline" class="text-[10px] font-mono">
                                {{ cohortStudentCount }} student(s) in scope
                            </Badge>
                        </div>

                        <!-- Cohort Mode Radio -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button
                                type="button"
                                @click="cohortType = 'all'"
                                class="p-2.5 rounded-xl border text-left transition-all cursor-pointer flex flex-col gap-0.5"
                                :class="cohortType === 'all'
                                    ? 'bg-emerald-50/90 border-emerald-500 text-emerald-950 dark:bg-emerald-950/40 dark:border-emerald-600 dark:text-emerald-200 shadow-xs'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-slate-300'"
                            >
                                <span class="text-xs font-bold flex items-center justify-between">
                                    All Departments
                                    <span v-if="cohortType === 'all'" class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                </span>
                                <span class="text-[10px] text-muted-foreground">Combined / joint lecture for all {{ students.length }} registered</span>
                            </button>

                            <button
                                type="button"
                                @click="cohortType = 'specific'"
                                class="p-2.5 rounded-xl border text-left transition-all cursor-pointer flex flex-col gap-0.5"
                                :class="cohortType === 'specific'
                                    ? 'bg-emerald-50/90 border-emerald-500 text-emerald-950 dark:bg-emerald-950/40 dark:border-emerald-600 dark:text-emerald-200 shadow-xs'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-slate-300'"
                            >
                                <span class="text-xs font-bold flex items-center justify-between">
                                    Specific / Mixed
                                    <span v-if="cohortType === 'specific'" class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                </span>
                                <span class="text-[10px] text-muted-foreground">Select one or more department cohorts</span>
                            </button>
                        </div>

                        <!-- Department Checkboxes when specific is active -->
                        <div v-if="cohortType === 'specific'" class="mt-2.5 pt-2.5 border-t border-slate-200 dark:border-slate-700/80 space-y-2">
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>Choose department(s) held today:</span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="selectAllDepartments" class="text-emerald-600 hover:underline font-bold text-[11px] cursor-pointer">
                                        Select All
                                    </button>
                                    <span>•</span>
                                    <button type="button" @click="clearAllDepartments" class="text-slate-500 hover:underline font-medium text-[11px] cursor-pointer">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto pr-1">
                                <div
                                    v-for="dept in (departments || [])"
                                    :key="dept.id"
                                    @click="toggleDepartmentCohort(dept.id)"
                                    class="p-2 rounded-lg border text-xs cursor-pointer flex items-center justify-between transition-colors select-none"
                                    :class="selectedCohortDepartmentIds.includes(dept.id)
                                        ? 'bg-emerald-50/80 border-emerald-400 dark:bg-emerald-950/30 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200 font-bold'
                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                >
                                    <div class="flex items-center gap-2 truncate">
                                        <input
                                            type="checkbox"
                                            :checked="selectedCohortDepartmentIds.includes(dept.id)"
                                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 h-3.5 w-3.5 pointer-events-none"
                                        />
                                        <span class="truncate">{{ dept.name }}</span>
                                    </div>
                                    <Badge variant="outline" class="text-[9px] px-1 py-0 shrink-0 font-mono ml-1">
                                        {{ dept.count }}
                                    </Badge>
                                </div>
                            </div>

                            <p v-if="selectedCohortDepartmentIds.length === 0" class="text-[11px] text-rose-500 font-semibold">
                                Please select at least one department cohort above.
                            </p>
                        </div>
                    </div>

                    <!-- Template Download Helper (Dynamic to selected cohort) -->
                    <div class="bg-emerald-50/70 border border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900 rounded-xl p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                        <div class="text-xs text-emerald-950 dark:text-emerald-200 min-w-0">
                            <span class="font-bold">Need the class attendance template?</span>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                Pre-filled with {{ cohortStudentCount }} registered student(s) defaulted to 'Present'.
                            </p>
                        </div>
                        <a :href="attendanceTemplateUrl" download class="shrink-0 w-full sm:w-auto">
                            <Button type="button" variant="outline" size="sm" class="w-full sm:w-auto h-8 text-xs border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 gap-1.5 font-bold cursor-pointer justify-center">
                                <Download class="w-3.5 h-3.5" /> Class Template
                            </Button>
                        </a>
                    </div>

                    <!-- Drag & Drop Zone -->
                    <input
                        ref="attendanceFileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="onAttendanceFileSelected"
                    />

                    <div
                        v-if="!selectedAttendanceFile"
                        @click="triggerAttendanceFileInput"
                        class="border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-emerald-400 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-slate-50/50 dark:bg-slate-800/20 flex flex-col items-center justify-center gap-2"
                    >
                        <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600">
                            <Upload class="w-6 h-6" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Click to upload attendance sheet</p>
                            <p class="text-xs text-muted-foreground">Supports .xlsx, .xls, and .csv (Max 10MB)</p>
                        </div>
                    </div>

                    <!-- Selected File Card -->
                    <div v-else class="p-3 border border-slate-200 dark:border-slate-700 rounded-xl flex items-center justify-between bg-slate-50 dark:bg-slate-800/40">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                XLS
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ selectedAttendanceFile.name }}</p>
                                <p class="text-[10px] text-muted-foreground">{{ (selectedAttendanceFile.size / 1024).toFixed(1) }} KB</p>
                            </div>
                        </div>
                        <Button type="button" variant="ghost" size="sm" class="h-8 w-8 p-0 text-slate-400 hover:text-red-600 shrink-0 cursor-pointer" @click="removeAttendanceFile">
                            <X class="w-4 h-4" />
                        </Button>
                    </div>

                    <div v-if="attendanceForm.errors.file" class="text-xs text-rose-600 font-bold">
                        {{ attendanceForm.errors.file }}
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0 pt-2">
                        <Button type="button" variant="outline" @click="showAttendanceModal = false" class="cursor-pointer">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold gap-2 cursor-pointer"
                            :disabled="!selectedAttendanceFile || !attendanceForm.attendance_date || attendanceForm.processing || (cohortType === 'specific' && selectedCohortDepartmentIds.length === 0)"
                        >
                            <Loader2 v-if="attendanceForm.processing" class="w-4 h-4 animate-spin" />
                            <Upload v-else class="w-4 h-4" />
                            Record Attendance
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 8. Attendance Results Summary Modal -->
        <Dialog v-model:open="showAttendanceSummaryModal">
            <DialogContent class="w-[calc(100vw-1.5rem)] max-w-[620px] p-4 sm:p-6 max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-base sm:text-lg">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600" />
                        Attendance Upload Summary
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Summary of processed lecture attendance for {{ course.code }}.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="attendanceSummary" class="space-y-4 pt-2">
                    <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center">
                        <div class="p-2.5 sm:p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div class="text-xl sm:text-2xl font-black text-emerald-700">{{ attendanceSummary.present }}</div>
                            <div class="text-[10px] sm:text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Present</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-rose-50 border border-rose-200 rounded-xl">
                            <div class="text-xl sm:text-2xl font-black text-rose-700">{{ attendanceSummary.absent }}</div>
                            <div class="text-[10px] sm:text-[11px] font-bold text-rose-800 uppercase tracking-wider">Absent</div>
                        </div>
                        <div class="p-2.5 sm:p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="text-xl sm:text-2xl font-black text-slate-700">{{ attendanceSummary.processed }}</div>
                            <div class="text-[10px] sm:text-[11px] font-bold text-slate-800 uppercase tracking-wider">Total</div>
                        </div>
                    </div>

                    <!-- Skipped / Unregistered warning -->
                    <div v-if="attendanceSummary.unregistered > 0" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                        <span class="font-bold">{{ attendanceSummary.unregistered }} student(s)</span> in the spreadsheet are not registered for this course and were skipped.
                    </div>

                    <!-- List of errors if any -->
                    <div v-if="attendanceSummary.errors && attendanceSummary.errors.length > 0" class="space-y-2">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <AlertTriangle class="w-4 h-4 text-amber-600" /> Unprocessed Entries ({{ attendanceSummary.errors.length }})
                        </p>
                        <div class="max-h-48 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 p-2 text-xs divide-y divide-slate-200 dark:divide-slate-700">
                            <div v-for="(err, idx) in attendanceSummary.errors" :key="idx" class="py-2 px-1 flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">Row {{ err.row }}:</span>
                                    <span class="font-mono ml-1 text-slate-900 dark:text-white bg-slate-200 dark:bg-slate-700 px-1 py-0.5 rounded">{{ err.matric }}</span>
                                    <p class="text-[11px] text-rose-600 mt-0.5">{{ err.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold cursor-pointer" @click="showAttendanceSummaryModal = false">
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Per-Date Attendance Details Modal -->
        <Dialog v-model:open="showDateDetailsModal">
            <DialogContent class="sm:max-w-3xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CalendarCheck class="w-5 h-5 text-indigo-600" />
                        <span>{{ course.code }} Attendance · {{ selectedDateMeta?.formatted_date }}</span>
                    </DialogTitle>
                    <DialogDescription v-if="selectedDateMeta" class="flex flex-wrap items-center gap-2 pt-1">
                        <Badge class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 text-xs">
                            {{ selectedDateMeta.present_count }} Present
                        </Badge>
                        <Badge class="bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 text-xs">
                            {{ selectedDateMeta.absent_count }} Absent
                        </Badge>
                        <Badge variant="outline" class="text-xs font-bold">
                            {{ selectedDateMeta.attendance_rate }}% Rate
                        </Badge>
                    </DialogDescription>
                </DialogHeader>

                <div class="flex flex-col sm:flex-row gap-2 py-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="dateDetailsSearch"
                            placeholder="Search student name, matric no., or department..."
                            class="pl-9 h-9 text-xs"
                        />
                    </div>
                    <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 p-0.5 bg-slate-50 dark:bg-slate-800">
                        <button
                            type="button"
                            @click="dateDetailsStatus = 'all'"
                            :class="['px-2.5 py-1 text-xs font-semibold rounded-md transition', dateDetailsStatus === 'all' ? 'bg-white dark:bg-slate-900 shadow-xs text-slate-900 dark:text-white' : 'text-muted-foreground']"
                        >
                            All
                        </button>
                        <button
                            type="button"
                            @click="dateDetailsStatus = 'present'"
                            :class="['px-2.5 py-1 text-xs font-semibold rounded-md transition', dateDetailsStatus === 'present' ? 'bg-emerald-600 text-white shadow-xs' : 'text-muted-foreground']"
                        >
                            Present
                        </button>
                        <button
                            type="button"
                            @click="dateDetailsStatus = 'absent'"
                            :class="['px-2.5 py-1 text-xs font-semibold rounded-md transition', dateDetailsStatus === 'absent' ? 'bg-rose-600 text-white shadow-xs' : 'text-muted-foreground']"
                        >
                            Absent
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                    <div v-if="dateDetailsLoading" class="flex items-center justify-center py-12 gap-2 text-xs text-muted-foreground">
                        <Loader2 class="w-4 h-4 animate-spin" />
                        <span>Loading attendance records...</span>
                    </div>
                    <Table v-else>
                        <TableHeader class="bg-slate-50 dark:bg-slate-800/60">
                            <TableRow>
                                <TableHead class="w-10 text-xs">#</TableHead>
                                <TableHead class="text-xs">Student</TableHead>
                                <TableHead class="text-xs">Matric No.</TableHead>
                                <TableHead class="text-xs">Department</TableHead>
                                <TableHead class="text-xs text-right">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(rec, idx) in filteredDateDetailsRecords" :key="rec.id">
                                <TableCell class="text-xs text-muted-foreground">{{ idx + 1 }}</TableCell>
                                <TableCell class="text-xs font-semibold text-slate-900 dark:text-white">{{ rec.student_name }}</TableCell>
                                <TableCell class="text-xs font-mono">{{ rec.matriculation_number }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ rec.department }}</TableCell>
                                <TableCell class="text-right">
                                    <Badge
                                        :class="rec.status === 'present'
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200'
                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200'"
                                        class="text-[10px] font-bold uppercase"
                                    >
                                        {{ rec.status }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredDateDetailsRecords.length === 0">
                                <TableCell colspan="5" class="text-center py-10 text-xs text-muted-foreground">
                                    No attendance records match this filter.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showDateDetailsModal = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
