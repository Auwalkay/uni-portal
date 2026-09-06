<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import ExamVerificationModal from '@/components/Exams/ExamVerificationModal.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { 
    Calendar, Clock, Building, Plus, Search, Trash2, Edit3, ShieldAlert, 
    UserCheck, AlertTriangle, FileText, CheckCircle, Info, Eye, EyeOff, Users, Sparkles, Upload, Download,
    LayoutList, Grid, Printer, CalendarRange, FileSpreadsheet, QrCode, ScanLine, ShieldCheck, CheckCircle2, XCircle
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

interface Props {
    schedules: {
        data: any[];
        links: any[];
        total?: number;
        from?: number;
        to?: number;
    };
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    staff: any[];
    students: any[];
    stats: {
        total_exams: number;
        total_invigilators: number;
        total_incidents: number;
        total_capacity: number;
        conflicts_count: number;
    };
    conflicts: any[];
    filters: {
        session_id?: string;
        semester_id?: string;
        department_id?: string;
        level?: string;
        exam_type?: string;
        search?: string;
    };
    isPublished?: boolean;
}

const props = defineProps<Props>();

const viewMode = ref<'table' | 'grid' | 'printable'>('table');

const isScheduleModalOpen = ref(false);
const isEditing = ref(false);
const editingScheduleId = ref<string | null>(null);

const isInvigilatorModalOpen = ref(false);
const selectedExamForInvigilator = ref<any>(null);

const isIncidentModalOpen = ref(false);
const selectedExamForIncident = ref<any>(null);

const isDetailsModalOpen = ref(false);
const viewingExam = ref<any>(null);

const isImportModalOpen = ref(false);

const page = usePage();
const verifiedCandidate = computed(() => (page.props as any).flash?.verified_candidate || null);

const selectedScheduleForAttendance = ref<string>('');

const openVerifyModal = (scheduleId?: string) => {
    verificationTokenInput.value = '';
    selectedScheduleForAttendance.value = scheduleId || '';
    isVerifyModalOpen.value = true;
};

const activeSelectedExam = computed(() => {
    if (!selectedScheduleForAttendance.value) return null;
    return props.schedules.data.find(e => e.id === selectedScheduleForAttendance.value) || null;
});

const isCandidateRegisteredForActiveExam = computed(() => {
    if (!verifiedCandidate.value || !activeSelectedExam.value) return null;
    const regCourseIds = verifiedCandidate.value.registered_course_ids || [];
    return regCourseIds.includes(activeSelectedExam.value.course_id);
});

const handleVerifySubmit = () => {
    if (!verificationTokenInput.value.trim()) return;
    isVerifying.value = true;
    router.get(route('admin.exams.verify_pass', verificationTokenInput.value.trim()), {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isVerifying.value = false;
        }
    });
};

const markStudentAttendance = (scheduleId: string, studentId: string) => {
    router.post(route('admin.exams.mark_attendance'), {
        exam_schedule_id: scheduleId,
        student_id: studentId,
        status: 'present',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Exam Attendance Marked Present!',
            });
        }
    });
};

const filterSessionId = ref(props.filters.session_id || '');
const filterSemesterId = ref(props.filters.semester_id || '');
const filterDepartmentId = ref(props.filters.department_id || '');
const filterLevel = ref(props.filters.level || '');
const filterExamType = ref(props.filters.exam_type || '');
const search = ref(props.filters.search || '');

const breadcrumbs = [
    { title: 'Academic', href: '#' },
    { title: 'Examinations', href: route('admin.exams.index') },
];

// Computed: Group exam schedules by Date for Grid / Timetable View
const groupedByDate = computed(() => {
    const groups: { [key: string]: { formattedDate: string; rawDate: string; items: any[] } } = {};

    props.schedules.data.forEach((exam) => {
        const rawDate = exam.exam_date ? exam.exam_date.substring(0, 10) : 'Unscheduled';
        const formattedDate = exam.exam_date ? format(new Date(exam.exam_date), 'EEEE, MMMM dd, yyyy') : 'Unscheduled';

        if (!groups[rawDate]) {
            groups[rawDate] = {
                formattedDate,
                rawDate,
                items: [],
            };
        }
        groups[rawDate].items.push(exam);
    });

    return Object.values(groups).sort((a, b) => a.rawDate.localeCompare(b.rawDate));
});

// Current Active Session Name & Semester Name
const activeSessionName = computed(() => {
    if (!filterSessionId.value) return props.sessions[0]?.name || 'Current Session';
    return props.sessions.find(s => s.id === filterSessionId.value)?.name || 'Academic Session';
});

const activeSemesterName = computed(() => {
    if (!filterSemesterId.value) return props.semesters[0]?.name || 'Semester';
    return props.semesters.find(sem => sem.id === filterSemesterId.value)?.name || 'Semester';
});

// Forms
const scheduleForm = useForm({
    session_id: props.sessions[0]?.id || '',
    semester_id: props.semesters[0]?.id || '',
    department_id: '',
    level: '100',
    course_id: '',
    exam_date: '',
    start_time: '09:00',
    end_time: '12:00',
    venue: '',
    exam_type: 'final',
    max_capacity: 100,
    instructions: '',
});

const invigilatorForm = useForm({
    staff_id: '',
    role: 'assistant',
});

const incidentForm = useForm({
    student_id: '',
    invigilator_id: '',
    incident_type: 'malpractice',
    description: '',
    status: 'logged',
    action_taken: '',
});

const importForm = useForm({
    session_id: props.sessions[0]?.id || '',
    semester_id: props.semesters[0]?.id || '',
    file: null as File | null,
});

// Dropdown Options for SearchableSelect
const sessionFilterOptions = computed(() => [
    { value: '', label: 'All Sessions' },
    ...(props.sessions || []).map(s => ({ value: s.id, label: s.name }))
]);

const semesterFilterOptions = computed(() => [
    { value: '', label: 'All Semesters' },
    ...(props.semesters || []).map(sem => ({ value: sem.id, label: sem.name }))
]);

const departmentFilterOptions = computed(() => [
    { value: '', label: 'All Departments' },
    ...(props.departments || []).map(d => ({ value: d.id, label: d.name }))
]);

const levelFilterOptions = computed(() => [
    { value: '', label: 'All Levels' },
    { value: '100', label: '100 Level' },
    { value: '200', label: '200 Level' },
    { value: '300', label: '300 Level' },
    { value: '400', label: '400 Level' },
    { value: '500', label: '500 Level' },
]);

const examTypeFilterOptions = computed(() => [
    { value: '', label: 'All Types' },
    { value: 'final', label: 'Final Exam' },
    { value: 'mid_term', label: 'Mid-Term Exam' },
    { value: 'cbt', label: 'Computer Based Test (CBT)' },
    { value: 'resit', label: 'Resit / Make-up Exam' },
]);

const sessionModalOptions = computed(() => [
    ...(props.sessions || []).map(s => ({ value: s.id, label: s.name }))
]);

const semesterModalOptions = computed(() => [
    ...(props.semesters || []).map(sem => ({ value: sem.id, label: sem.name }))
]);

const courseModalOptions = computed(() => [
    { value: '', label: 'Select Course' },
    ...(props.courses || []).map(c => ({ value: c.id, label: `${c.code} - ${c.title}` }))
]);

const departmentModalOptions = computed(() => [
    { value: '', label: 'All Departments' },
    ...(props.departments || []).map(d => ({ value: d.id, label: d.name }))
]);

const staffModalOptions = computed(() => [
    { value: '', label: 'Select Staff Member' },
    ...(props.staff || []).map(st => ({ value: st.id, label: `${st.name} (${st.staff_number})` }))
]);

const staffOptionalModalOptions = computed(() => [
    { value: '', label: 'Select Invigilator (Optional)' },
    ...(props.staff || []).map(st => ({ value: st.id, label: `${st.name} (${st.staff_number})` }))
]);

const invigilatorRoleOptions = computed(() => [
    { value: 'chief', label: 'Chief Invigilator' },
    { value: 'assistant', label: 'Assistant Invigilator' },
]);

const studentModalOptions = computed(() => [
    { value: '', label: 'Select Student' },
    ...(props.students || []).map(st => ({ value: st.id, label: `${st.name} (${st.matric_number})` }))
]);

const incidentCategoryOptions = computed(() => [
    { value: 'malpractice', label: 'Exam Malpractice (Cheating)' },
    { value: 'contraband', label: 'Unauthorized Materials' },
    { value: 'impersonation', label: 'Impersonation' },
    { value: 'medical', label: 'Medical Emergency' },
    { value: 'absenteeism', label: 'Unexplained Absence' },
    { value: 'other', label: 'Other Misconduct' },
]);

const incidentStatusOptions = computed(() => [
    { value: 'logged', label: 'Logged' },
    { value: 'under_investigation', label: 'Under Investigation' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'sanctioned', label: 'Sanctioned' },
]);

const openCreateSchedule = () => {
    isEditing.value = false;
    editingScheduleId.value = null;
    scheduleForm.reset();
    scheduleForm.session_id = props.sessions[0]?.id || '';
    scheduleForm.semester_id = props.semesters[0]?.id || '';
    isScheduleModalOpen.value = true;
};

const openEditSchedule = (exam: any) => {
    isEditing.value = true;
    editingScheduleId.value = exam.id;
    scheduleForm.session_id = exam.session_id;
    scheduleForm.semester_id = exam.semester_id;
    scheduleForm.department_id = exam.department_id || '';
    scheduleForm.level = exam.level || '100';
    scheduleForm.course_id = exam.course_id;
    scheduleForm.exam_date = exam.exam_date ? exam.exam_date.substring(0, 10) : '';
    scheduleForm.start_time = exam.start_time;
    scheduleForm.end_time = exam.end_time;
    scheduleForm.venue = exam.venue;
    scheduleForm.exam_type = exam.exam_type;
    scheduleForm.max_capacity = exam.max_capacity;
    scheduleForm.instructions = exam.instructions || '';
    isScheduleModalOpen.value = true;
};

const submitSchedule = () => {
    if (isEditing.value && editingScheduleId.value) {
        scheduleForm.put(route('admin.exams.update', editingScheduleId.value), {
            onSuccess: () => {
                isScheduleModalOpen.value = false;
                scheduleForm.reset();
            },
        });
    } else {
        scheduleForm.post(route('admin.exams.store'), {
            onSuccess: () => {
                isScheduleModalOpen.value = false;
                scheduleForm.reset();
            },
        });
    }
};

const deleteExam = (id: string) => {
    if (confirm('Are you sure you want to delete this exam schedule?')) {
        router.delete(route('admin.exams.destroy', id));
    }
};

const openInvigilatorModal = (exam: any) => {
    selectedExamForInvigilator.value = exam;
    invigilatorForm.reset();
    isInvigilatorModalOpen.value = true;
};

const submitInvigilator = () => {
    invigilatorForm.post(route('admin.exams.invigilators.assign', selectedExamForInvigilator.value.id), {
        onSuccess: () => {
            isInvigilatorModalOpen.value = false;
            invigilatorForm.reset();
        },
    });
};

const removeInvigilator = (id: string) => {
    if (confirm('Remove invigilator from this exam?')) {
        router.delete(route('admin.exams.invigilators.remove', id));
    }
};

const openIncidentModal = (exam: any) => {
    selectedExamForIncident.value = exam;
    incidentForm.reset();
    isIncidentModalOpen.value = true;
};

const submitIncident = () => {
    incidentForm.post(route('admin.exams.incidents.store', selectedExamForIncident.value.id), {
        onSuccess: () => {
            isIncidentModalOpen.value = false;
            incidentForm.reset();
        },
    });
};

const openDetailsModal = (exam: any) => {
    viewingExam.value = exam;
    isDetailsModalOpen.value = true;
};

const openImportModal = () => {
    importForm.reset();
    importForm.session_id = props.sessions[0]?.id || '';
    importForm.semester_id = props.semesters[0]?.id || '';
    isImportModalOpen.value = true;
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.file = target.files[0];
    }
};

const submitImport = () => {
    importForm.post(route('admin.exams.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
        },
    });
};

const downloadTemplate = () => {
    window.location.href = route('admin.exams.template');
};

const printMasterTimetable = () => {
    window.print();
};

const togglePublish = () => {
    router.post(route('admin.exams.toggle_publish'), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = (page.props as any).flash;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: flash?.success || 'Publication status updated',
            });
        }
    });
};

const applyFilters = () => {
    router.get(route('admin.exams.index'), {
        session_id: filterSessionId.value,
        semester_id: filterSemesterId.value,
        department_id: filterDepartmentId.value,
        level: filterLevel.value,
        exam_type: filterExamType.value,
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Examination Management Hub" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full print:p-0 print:m-0">
            
            <!-- Hero Header & Actions (Hidden on Print) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 print:hidden">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                            <Calendar class="w-6 h-6" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                            Examination Management Hub
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 sm:pl-10">
                        Schedule examinations, view semester timetables, assign staff invigilators, and track malpractice logs.
                    </p>
                </div>
                
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- View Switcher Tabs -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border w-full sm:w-auto justify-center">
                        <button 
                            @click="viewMode = 'table'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all flex-1 sm:flex-none justify-center',
                                viewMode === 'table' ? 'bg-white dark:bg-slate-900 shadow-sm text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <LayoutList class="w-3.5 h-3.5" /> Table View
                        </button>
                        <button 
                            @click="viewMode = 'grid'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all flex-1 sm:flex-none justify-center',
                                viewMode === 'grid' ? 'bg-white dark:bg-slate-900 shadow-sm text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <Grid class="w-3.5 h-3.5" /> Timetable Grid
                        </button>
                        <button 
                            @click="viewMode = 'printable'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all flex-1 sm:flex-none justify-center',
                                viewMode === 'printable' ? 'bg-white dark:bg-slate-900 shadow-sm text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <Printer class="w-3.5 h-3.5" /> Master Sheet
                        </button>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <Button 
                            variant="outline" 
                            @click="togglePublish" 
                            :class="[
                                'font-semibold gap-2 border transition-all text-xs h-9 flex-1 sm:flex-none justify-center',
                                isPublished 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' 
                                    : 'bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
                            ]"
                        >
                            <Eye v-if="isPublished" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                            <EyeOff v-else class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                            <span>{{ isPublished ? 'Published' : 'Draft (Hidden)' }}</span>
                        </Button>

                        <Button variant="outline" class="border-purple-200 text-purple-700 dark:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-950/40 font-semibold gap-1.5 text-xs h-9 flex-1 sm:flex-none justify-center" @click="openImportModal">
                            <Upload class="w-3.5 h-3.5" />
                            <span>Bulk Upload</span>
                        </Button>

                        <Button class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm font-semibold gap-1.5 text-xs h-9 flex-1 sm:flex-none justify-center" @click="openVerifyModal()">
                            <QrCode class="w-3.5 h-3.5" />
                            <span>Scan QR / Verify Candidate</span>
                        </Button>

                        <Button class="bg-purple-600 hover:bg-purple-700 text-white shadow-sm font-semibold gap-1.5 text-xs h-9 flex-1 sm:flex-none justify-center" @click="openCreateSchedule">
                            <Plus class="w-3.5 h-3.5" />
                            <span>Schedule Exam</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Venue / Time Conflict Warning Banner (Hidden on Print) -->
            <div v-if="stats.conflicts_count > 0" class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 rounded-xl flex items-start gap-3 text-amber-800 dark:text-amber-300 print:hidden">
                <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div class="space-y-1 text-xs">
                    <p class="font-bold">Venue Scheduling Conflicts Detected ({{ stats.conflicts_count }})</p>
                    <p>There are overlapping exam schedules assigned to the same venue at the same time. Please review highlighted exam entries.</p>
                </div>
            </div>

            <!-- KPI Metric Summary Cards (Hidden on Print) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Scheduled Exams</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ stats.total_exams }}</p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/40 rounded-xl text-purple-600 dark:text-purple-400">
                            <Calendar class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Staff Invigilators</p>
                            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ stats.total_invigilators }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <UserCheck class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Malpractice & Incidents</p>
                            <p class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ stats.total_incidents }}</p>
                        </div>
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 rounded-xl text-rose-600 dark:text-rose-400">
                            <ShieldAlert class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Hall Capacity</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.total_capacity }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <Building class="w-6 h-6" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Filter & Search Toolbar (Hidden on Print) -->
            <Card class="border shadow-sm bg-white dark:bg-slate-900 p-4 print:hidden">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 flex-1">
                        <!-- Session Select -->
                        <SearchableSelect
                            v-model="filterSessionId"
                            :items="sessionFilterOptions"
                            placeholder="All Sessions"
                            search-placeholder="Search sessions..."
                            trigger-class="h-9 text-xs"
                            @update:model-value="applyFilters"
                        />

                        <!-- Semester Select -->
                        <SearchableSelect
                            v-model="filterSemesterId"
                            :items="semesterFilterOptions"
                            placeholder="All Semesters"
                            search-placeholder="Search semesters..."
                            trigger-class="h-9 text-xs"
                            @update:model-value="applyFilters"
                        />

                        <!-- Department Select -->
                        <SearchableSelect
                            v-model="filterDepartmentId"
                            :items="departmentFilterOptions"
                            placeholder="All Departments"
                            search-placeholder="Search departments..."
                            trigger-class="h-9 text-xs"
                            @update:model-value="applyFilters"
                        />

                        <!-- Level Select -->
                        <SearchableSelect
                            v-model="filterLevel"
                            :items="levelFilterOptions"
                            placeholder="All Levels"
                            search-placeholder="Search levels..."
                            trigger-class="h-9 text-xs"
                            @update:model-value="applyFilters"
                        />

                        <!-- Exam Type -->
                        <SearchableSelect
                            v-model="filterExamType"
                            :items="examTypeFilterOptions"
                            placeholder="All Types"
                            search-placeholder="Search exam types..."
                            trigger-class="h-9 text-xs"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full lg:w-64 shrink-0">
                        <Input 
                            v-model="search" 
                            placeholder="Search Ref ID, course, venue..." 
                            @input="applyFilters"
                            class="pl-9 h-9 text-xs focus-visible:ring-purple-500 w-full"
                        />
                        <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                    </div>
                </div>
            </Card>

            <!-- 1. TABLE VIEW MODE -->
            <div v-if="viewMode === 'table'" class="space-y-4 print:hidden">
                <Card class="border shadow-sm rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-slate-50 dark:bg-slate-950/60">
                                <TableRow>
                                    <TableHead class="font-bold py-3.5 pl-6 text-xs uppercase tracking-wider">Ref ID</TableHead>
                                    <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Course & Department</TableHead>
                                    <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Date & Time</TableHead>
                                    <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Venue & Capacity</TableHead>
                                    <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Type</TableHead>
                                    <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Invigilators</TableHead>
                                    <TableHead class="font-bold py-3.5 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="exam in schedules.data" 
                                    :key="exam.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-all border-b"
                                >
                                    <TableCell class="font-mono text-xs font-bold text-purple-700 dark:text-purple-400 pl-6">
                                        {{ exam.reference_id }}
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-bold text-slate-800 dark:text-slate-100 text-sm block">
                                            {{ exam.course?.code }} - {{ exam.course?.title }}
                                        </span>
                                        <span class="text-xs text-slate-500 block">
                                            {{ exam.department?.name || 'General Course' }} ({{ exam.level ? exam.level + 'L' : 'All Levels' }})
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-col text-xs text-slate-700 dark:text-slate-300 font-medium">
                                            <span class="flex items-center gap-1.5"><Calendar class="w-3.5 h-3.5 text-purple-500" /> {{ format(new Date(exam.exam_date), 'MMM dd, yyyy') }}</span>
                                            <span class="flex items-center gap-1.5 text-slate-500 mt-0.5"><Clock class="w-3.5 h-3.5 text-slate-400" /> {{ exam.start_time }} - {{ exam.end_time }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs block">{{ exam.venue }}</span>
                                        <span class="text-[11px] text-slate-500 font-medium block">Max Cap: {{ exam.max_capacity }} seats</span>
                                        <Badge variant="outline" class="text-[10px] bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 flex items-center gap-1 mt-1 w-max">
                                            <UserCheck class="w-2.5 h-2.5 text-emerald-600" />
                                            <span>{{ exam.attendances_count || 0 }} Present</span>
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                            exam.exam_type === 'final' ? 'bg-purple-50 text-purple-800 border-purple-200' :
                                            exam.exam_type === 'mid_term' ? 'bg-indigo-50 text-indigo-800 border-indigo-200' :
                                            exam.exam_type === 'cbt' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-amber-50 text-amber-800 border-amber-200'
                                        ]">
                                            {{ exam.exam_type }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1 items-center">
                                            <Badge 
                                                v-for="inv in exam.invigilators" 
                                                :key="inv.id" 
                                                variant="outline" 
                                                class="text-[10px] bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center gap-1"
                                            >
                                                <UserCheck class="w-2.5 h-2.5 text-indigo-500" />
                                                <span>{{ inv.staff?.user?.name }} ({{ inv.role }})</span>
                                            </Badge>
                                            <span v-if="exam.invigilators.length === 0" class="text-xs text-slate-400 italic">None assigned</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right pr-6">
                                        <div class="flex justify-end items-center gap-1.5">
                                            <Button variant="outline" size="sm" class="h-8 text-xs border-emerald-300 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 font-semibold" @click="openVerifyModal(exam.id)">
                                                <QrCode class="w-3.5 h-3.5 mr-1 text-emerald-600" /> Attendance
                                            </Button>
                                            <Button variant="outline" size="sm" class="h-8 text-xs" @click="openDetailsModal(exam)">
                                                <Eye class="w-3.5 h-3.5 mr-1" /> View
                                            </Button>
                                            <Button variant="outline" size="sm" class="h-8 text-xs border-indigo-200 text-indigo-700 hover:bg-indigo-50" @click="openInvigilatorModal(exam)">
                                                <UserCheck class="w-3.5 h-3.5 mr-1" /> Invigilator
                                            </Button>
                                            <Button variant="outline" size="sm" class="h-8 text-xs border-rose-200 text-rose-700 hover:bg-rose-50" @click="openIncidentModal(exam)">
                                                <ShieldAlert class="w-3.5 h-3.5 mr-1" /> Incident
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:bg-slate-100" @click="openEditSchedule(exam)">
                                                <Edit3 class="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-rose-500 hover:bg-rose-50" @click="deleteExam(exam.id)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="schedules.data.length === 0">
                                    <TableCell colspan="7" class="h-32 text-center text-slate-400">
                                        No exam schedules recorded yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Pagination :links="schedules.links" />
            </div>

            <!-- 2. VISUAL TIMETABLE GRID VIEW MODE -->
            <div v-else-if="viewMode === 'grid'" class="space-y-6 print:hidden">
                <div v-for="group in groupedByDate" :key="group.rawDate" class="space-y-3">
                    <div class="flex items-center gap-3 border-b pb-2">
                        <div class="p-2 bg-purple-100 dark:bg-purple-950/50 text-purple-600 rounded-lg">
                            <CalendarRange class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ group.formattedDate }}</h3>
                            <p class="text-xs text-slate-500 font-medium">{{ group.items.length }} examination session(s)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card 
                            v-for="exam in group.items" 
                            :key="exam.id"
                            class="border shadow-sm bg-white dark:bg-slate-900 hover:border-purple-300 dark:hover:border-purple-800 transition-all flex flex-col justify-between"
                        >
                            <CardContent class="p-4 space-y-3">
                                <div class="flex items-start justify-between gap-2 border-b pb-2.5">
                                    <div>
                                        <span class="font-mono text-[11px] font-bold text-purple-600 dark:text-purple-400 block">{{ exam.reference_id }}</span>
                                        <h4 class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-tight mt-0.5">
                                            {{ exam.course?.code }} - {{ exam.course?.title }}
                                        </h4>
                                    </div>
                                    <Badge :class="[
                                        'px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border shrink-0',
                                        exam.exam_type === 'final' ? 'bg-purple-50 text-purple-800 border-purple-200' : 'bg-indigo-50 text-indigo-800 border-indigo-200'
                                    ]">
                                        {{ exam.exam_type }}
                                    </Badge>
                                </div>

                                <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 font-medium"><Clock class="w-3.5 h-3.5 text-indigo-500" /> Time Slot:</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ exam.start_time }} - {{ exam.end_time }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 font-medium"><Building class="w-3.5 h-3.5 text-emerald-500" /> Hall Venue:</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ exam.venue }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 font-medium"><Users class="w-3.5 h-3.5 text-slate-400" /> Capacity:</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ exam.max_capacity }} seats</span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t text-[11px] space-y-1">
                                    <span class="font-bold text-slate-400 uppercase tracking-wider block text-[10px]">Invigilators</span>
                                    <div v-if="exam.invigilators.length > 0" class="flex flex-wrap gap-1">
                                        <Badge 
                                            v-for="inv in exam.invigilators" 
                                            :key="inv.id" 
                                            variant="outline" 
                                            class="text-[9px] bg-slate-50 dark:bg-slate-800"
                                        >
                                            {{ inv.staff?.user?.name }} ({{ inv.role }})
                                        </Badge>
                                    </div>
                                    <span v-else class="text-slate-400 italic">None assigned</span>
                                </div>
                            </CardContent>

                            <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border-t flex items-center justify-between text-xs">
                                <Button size="sm" variant="outline" class="h-7 text-[11px]" @click="openDetailsModal(exam)">
                                    <Eye class="w-3 h-3 mr-1" /> View
                                </Button>
                                <div class="flex items-center gap-1">
                                    <Button size="sm" variant="outline" class="h-7 text-[11px] border-indigo-200 text-indigo-700" @click="openInvigilatorModal(exam)">
                                        + Invigilator
                                    </Button>
                                    <Button size="sm" variant="ghost" class="h-7 w-7 p-0 text-slate-500" @click="openEditSchedule(exam)">
                                        <Edit3 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <div v-if="groupedByDate.length === 0" class="p-12 text-center text-slate-400 border border-dashed rounded-xl bg-white dark:bg-slate-900">
                    No exam schedules found.
                </div>
            </div>

            <!-- 3. PRINTABLE MASTER TIMETABLE VIEW MODE -->
            <div v-else-if="viewMode === 'printable'" class="space-y-6">
                <!-- Action bar above printable sheet (Hidden on print) -->
                <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 rounded-xl print:hidden">
                    <div>
                        <h3 class="font-bold text-sm text-purple-900 dark:text-purple-200">Official Master Examination Schedule Sheet</h3>
                        <p class="text-xs text-purple-700 dark:text-purple-300">Format ready for campus noticeboard posting and PDF archiving.</p>
                    </div>
                    <Button class="bg-purple-600 hover:bg-purple-700 text-white font-semibold gap-2" @click="printMasterTimetable">
                        <Printer class="w-4 h-4" /> Print Master Sheet
                    </Button>
                </div>

                <!-- Printable Document Sheet -->
                <Card class="border-2 border-slate-300 shadow-md bg-white dark:bg-slate-900 print:border-none print:shadow-none">
                    <CardContent class="p-8 space-y-6 print:p-0">
                    
                    <!-- University Header -->
                    <div class="text-center space-y-1.5 border-b pb-6 print:border-b-2 print:border-black">
                        <h2 class="text-2xl font-black uppercase tracking-wider text-slate-900 dark:text-slate-100">UNIVERSITY PORTAL ACADEMIC AFFAIRS</h2>
                        <h3 class="text-lg font-bold text-purple-800 dark:text-purple-400 uppercase tracking-widest print:text-black">
                            MASTER SEMESTER EXAMINATION TIMETABLE SHEET
                        </h3>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                            Academic Session: <strong>{{ activeSessionName }}</strong> | Semester: <strong>{{ activeSemesterName }}</strong>
                        </p>
                    </div>

                    <!-- Timetable Day-by-Day Master Table -->
                    <div v-for="group in groupedByDate" :key="group.rawDate" class="space-y-2">
                        <div class="p-2 bg-slate-100 dark:bg-slate-800 font-bold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-200 border-l-4 border-purple-600 print:bg-slate-200 print:border-black">
                            📅 {{ group.formattedDate }}
                        </div>

                        <Table class="border text-xs">
                            <TableHeader class="bg-slate-50 dark:bg-slate-950/60 print:bg-slate-100">
                                <TableRow>
                                    <TableHead class="font-bold py-2 text-[11px] uppercase text-slate-800">Time Slot</TableHead>
                                    <TableHead class="font-bold py-2 text-[11px] uppercase text-slate-800">Course Code & Title</TableHead>
                                    <TableHead class="font-bold py-2 text-[11px] uppercase text-slate-800">Dept / Level</TableHead>
                                    <TableHead class="font-bold py-2 text-[11px] uppercase text-slate-800">Exam Hall Venue</TableHead>
                                    <TableHead class="font-bold py-2 text-[11px] uppercase text-slate-800">Capacity</TableHead>
                                    <TableHead class="font-bold py-2 text-[11px] uppercase text-slate-800">Assigned Invigilators</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="exam in group.items" :key="exam.id" class="border-b">
                                    <TableCell class="font-bold text-purple-700 dark:text-purple-400 print:text-black">
                                        {{ exam.start_time }} - {{ exam.end_time }}
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ exam.course?.code }}</span>
                                        <span class="text-slate-600 dark:text-slate-400 font-medium block text-[11px]">{{ exam.course?.title }}</span>
                                    </TableCell>
                                    <TableCell class="font-medium text-slate-700 dark:text-slate-300">
                                        {{ exam.department?.name || 'General' }} ({{ exam.level ? exam.level + 'L' : 'All' }})
                                    </TableCell>
                                    <TableCell class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ exam.venue }}
                                    </TableCell>
                                    <TableCell class="font-medium text-slate-700 dark:text-slate-300">
                                        {{ exam.max_capacity }} seats
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="exam.invigilators.length > 0" class="space-y-0.5 font-medium">
                                            <div v-for="inv in exam.invigilators" :key="inv.id" class="text-[11px]">
                                                • {{ inv.staff?.user?.name }} ({{ inv.role }})
                                            </div>
                                        </div>
                                        <span v-else class="text-slate-400 italic">Unassigned</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Footer Signatures -->
                    <div class="grid grid-cols-2 gap-8 pt-8 border-t text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Prepared By: Chairman, University Exam Committee</span>
                            <div class="h-10 border-b border-slate-400 mt-2"></div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Approved By: Academic Registrar / VC</span>
                            <div class="h-10 border-b border-slate-400 mt-2"></div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Bulk Import Modal -->
        <Dialog v-model:open="isImportModalOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle>Bulk Upload Semester Exam Timetable</DialogTitle>
                    <DialogDescription class="text-xs">Upload an Excel/CSV file containing all course exam schedules and invigilator assignments.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitImport" class="space-y-4 py-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Academic Session</Label>
                            <SearchableSelect
                                v-model="importForm.session_id"
                                :items="sessionModalOptions"
                                placeholder="Select Academic Session"
                                search-placeholder="Search sessions..."
                                :error-class="!!importForm.errors.session_id"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Semester</Label>
                            <SearchableSelect
                                v-model="importForm.semester_id"
                                :items="semesterModalOptions"
                                placeholder="Select Semester"
                                search-placeholder="Search semesters..."
                                :error-class="!!importForm.errors.semester_id"
                            />
                        </div>
                    </div>

                    <div class="p-3 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-900/60 rounded-lg space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-purple-900 dark:text-purple-300">Need the CSV Template?</span>
                            <Button type="button" size="sm" variant="outline" class="h-7 text-xs border-purple-300 text-purple-700 dark:text-purple-300" @click="downloadTemplate">
                                <Download class="w-3.5 h-3.5 mr-1" /> Download CSV Template
                            </Button>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400">
                            The template supports course code, date, start/end time, hall capacity, exam type, and staff numbers for Chief + Assistant Invigilators.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Upload CSV/Excel File</Label>
                        <Input type="file" accept=".csv, .xlsx, .xls" @change="handleFileChange" class="h-10 text-xs" required />
                    </div>

                    <DialogFooter class="border-t pt-3">
                        <Button type="button" variant="ghost" @click="isImportModalOpen = false">Cancel</Button>
                        <Button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white" :disabled="importForm.processing">
                            <Upload class="w-4 h-4 mr-2" /> Upload & Process
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Schedule Create / Edit Modal -->
        <Dialog v-model:open="isScheduleModalOpen">
            <DialogContent class="sm:max-w-[550px]">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle>{{ isEditing ? 'Edit Exam Schedule' : 'Schedule New Exam' }}</DialogTitle>
                    <DialogDescription class="text-xs">Configure exam course, venue, date, and seating capacity.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitSchedule" class="space-y-4 py-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Academic Session</Label>
                            <SearchableSelect
                                v-model="scheduleForm.session_id"
                                :items="sessionModalOptions"
                                placeholder="Select Session"
                                search-placeholder="Search sessions..."
                                :error-class="!!scheduleForm.errors.session_id"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Semester</Label>
                            <SearchableSelect
                                v-model="scheduleForm.semester_id"
                                :items="semesterModalOptions"
                                placeholder="Select Semester"
                                search-placeholder="Search semesters..."
                                :error-class="!!scheduleForm.errors.semester_id"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Course</Label>
                        <SearchableSelect
                            v-model="scheduleForm.course_id"
                            :items="courseModalOptions"
                            placeholder="Select Course"
                            search-placeholder="Search courses..."
                            :error-class="!!scheduleForm.errors.course_id"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Department (Optional)</Label>
                            <SearchableSelect
                                v-model="scheduleForm.department_id"
                                :items="departmentModalOptions"
                                placeholder="All Departments"
                                search-placeholder="Search departments..."
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Level</Label>
                            <SearchableSelect
                                v-model="scheduleForm.level"
                                :items="levelFilterOptions.filter(o => o.value !== '')"
                                placeholder="Select Level"
                                search-placeholder="Search levels..."
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Exam Date</Label>
                            <Input v-model="scheduleForm.exam_date" type="date" class="h-9 text-xs" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Start Time</Label>
                            <Input v-model="scheduleForm.start_time" type="time" class="h-9 text-xs" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">End Time</Label>
                            <Input v-model="scheduleForm.end_time" type="time" class="h-9 text-xs" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2 space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Exam Venue / Hall</Label>
                            <Input v-model="scheduleForm.venue" placeholder="E.g. Multipurpose Hall A" class="h-9 text-xs" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Max Capacity</Label>
                            <Input v-model="scheduleForm.max_capacity" type="number" min="1" class="h-9 text-xs" required />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Exam Type</Label>
                        <SearchableSelect
                            v-model="scheduleForm.exam_type"
                            :items="examTypeFilterOptions.filter(o => o.value !== '')"
                            placeholder="Select Exam Type"
                            search-placeholder="Search exam types..."
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Instructions for Students</Label>
                        <Textarea v-model="scheduleForm.instructions" rows="2" placeholder="Enter hall rules or required materials..." class="text-xs" />
                    </div>

                    <DialogFooter class="border-t pt-3">
                        <Button type="button" variant="ghost" @click="isScheduleModalOpen = false">Cancel</Button>
                        <Button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white" :disabled="scheduleForm.processing">
                            Save Schedule
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Invigilator Allocation Modal -->
        <Dialog v-model:open="isInvigilatorModalOpen">
            <DialogContent class="sm:max-w-[450px]">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle>Assign Staff Invigilator</DialogTitle>
                    <DialogDescription class="text-xs">
                        Assign supervision staff to exam session: <strong v-if="selectedExamForInvigilator">{{ selectedExamForInvigilator.reference_id }} ({{ selectedExamForInvigilator.course?.code }})</strong>
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitInvigilator" class="space-y-4 py-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Staff Member</Label>
                        <SearchableSelect
                            v-model="invigilatorForm.staff_id"
                            :items="staffModalOptions"
                            placeholder="Select Staff Member"
                            search-placeholder="Search staff by name or number..."
                            :error-class="!!invigilatorForm.errors.staff_id"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Invigilator Role</Label>
                        <SearchableSelect
                            v-model="invigilatorForm.role"
                            :items="invigilatorRoleOptions"
                            placeholder="Select Invigilator Role"
                            search-placeholder="Search roles..."
                        />
                    </div>
                    <DialogFooter class="border-t pt-3">
                        <Button type="button" variant="ghost" @click="isInvigilatorModalOpen = false">Cancel</Button>
                        <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white" :disabled="invigilatorForm.processing">
                            Assign Invigilator
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Log Incident / Malpractice Modal -->
        <Dialog v-model:open="isIncidentModalOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle>Log Exam Incident / Malpractice</DialogTitle>
                    <DialogDescription class="text-xs">Document exam misconduct or hall incident.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitIncident" class="space-y-4 py-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Student Involved</Label>
                        <SearchableSelect
                            v-model="incidentForm.student_id"
                            :items="studentModalOptions"
                            placeholder="Select Student"
                            search-placeholder="Search student by name or matric..."
                            :error-class="!!incidentForm.errors.student_id"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Reporting Invigilator</Label>
                            <SearchableSelect
                                v-model="incidentForm.invigilator_id"
                                :items="staffOptionalModalOptions"
                                placeholder="Select Invigilator (Optional)"
                                search-placeholder="Search staff..."
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Incident Category</Label>
                            <SearchableSelect
                                v-model="incidentForm.incident_type"
                                :items="incidentCategoryOptions"
                                placeholder="Select Incident Category"
                                search-placeholder="Search category..."
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Incident Description</Label>
                        <Textarea v-model="incidentForm.description" rows="3" placeholder="Provide full details of incident..." class="text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</Label>
                            <SearchableSelect
                                v-model="incidentForm.status"
                                :items="incidentStatusOptions"
                                placeholder="Select Status"
                                search-placeholder="Search status..."
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Action Taken / Penalty</Label>
                            <Input v-model="incidentForm.action_taken" placeholder="E.g. Script confiscated" class="h-9 text-xs" />
                        </div>
                    </div>

                    <DialogFooter class="border-t pt-3">
                        <Button type="button" variant="ghost" @click="isIncidentModalOpen = false">Cancel</Button>
                        <Button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white" :disabled="incidentForm.processing">
                            Log Incident
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- View Single Exam Details Modal -->
        <Dialog v-model:open="isDetailsModalOpen">
            <DialogContent class="sm:max-w-[550px]">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle>Exam Details Pass</DialogTitle>
                    <DialogDescription class="text-xs font-mono text-purple-600">Ref: {{ viewingExam?.reference_id }}</DialogDescription>
                </DialogHeader>
                <div v-if="viewingExam" class="space-y-4 py-3 text-xs">
                    <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 dark:bg-slate-900 rounded-lg border">
                        <div>
                            <span class="text-slate-400 uppercase font-bold block">Course Code</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100 text-sm">{{ viewingExam.course?.code }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-bold block">Course Title</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ viewingExam.course?.title }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-bold block">Date & Time</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">
                                {{ format(new Date(viewingExam.exam_date), 'MMMM dd, yyyy') }} ({{ viewingExam.start_time }} - {{ viewingExam.end_time }})
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-bold block">Venue & Capacity</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ viewingExam.venue }} ({{ viewingExam.max_capacity }} seats)</span>
                        </div>
                    </div>

                    <!-- Invigilators -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Assigned Invigilators</Label>
                        <div v-if="viewingExam.invigilators.length > 0" class="space-y-1">
                            <div v-for="inv in viewingExam.invigilators" :key="inv.id" class="p-2 bg-white dark:bg-slate-900 border rounded flex items-center justify-between">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ inv.staff?.user?.name }} ({{ inv.role }})</span>
                                <Button size="sm" variant="ghost" class="h-6 text-rose-500" @click="removeInvigilator(inv.id)">Remove</Button>
                            </div>
                        </div>
                        <p v-else class="text-slate-400 italic">No invigilators assigned yet.</p>
                    </div>

                    <!-- Incidents -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Incident Logs ({{ viewingExam.incidents.length }})</Label>
                        <div v-if="viewingExam.incidents.length > 0" class="space-y-1">
                            <div v-for="inc in viewingExam.incidents" :key="inc.id" class="p-2.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 rounded text-rose-900 dark:text-rose-200 space-y-1">
                                <div class="flex justify-between font-bold">
                                    <span>Student: {{ inc.student?.user?.name }} ({{ inc.student?.matric_number }})</span>
                                    <Badge variant="destructive" class="text-[9px] uppercase">{{ inc.status }}</Badge>
                                </div>
                                <p class="text-[11px]">{{ inc.description }}</p>
                            </div>
                        </div>
                        <p v-else class="text-slate-400 italic">No incidents logged for this exam session.</p>
                    </div>
                </div>
                <DialogFooter class="border-t pt-3">
                    <Button variant="outline" @click="isDetailsModalOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Invigilator Candidate QR Verification Modal Component -->
        <ExamVerificationModal 
            v-model:open="isVerifyModalOpen"
            :selected-schedule-id="selectedScheduleForAttendance"
            :schedules="schedules.data"
            :verified-candidate="verifiedCandidate"
            :is-verifying="isVerifying"
            @verify="handleVerifySubmit"
            @mark-attendance="markStudentAttendance"
        />
        </div>
    </AdminLayout>
</template>
