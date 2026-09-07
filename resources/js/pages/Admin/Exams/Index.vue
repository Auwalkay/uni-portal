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
    Calendar, Clock, Building, Building2, Plus, Search, Trash2, Edit3, ShieldAlert, 
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
    canManageExams?: boolean;
    buildings?: any[];
}

const props = defineProps<Props>();

const viewMode = ref<'table' | 'venue' | 'grid' | 'printable'>('venue');

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
const isVerifyModalOpen = ref(false);
const verificationTokenInput = ref('');
const isVerifying = ref(false);

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

const handleVerifySubmit = (token?: string) => {
    const codeToVerify = typeof token === 'string' ? token.trim() : verificationTokenInput.value.trim();
    if (!codeToVerify) return;
    verificationTokenInput.value = codeToVerify;
    isVerifying.value = true;
    router.get(route('admin.exams.verify_pass', codeToVerify), {}, {
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

const currentUser = computed(() => (page.props as any).auth?.user);

const isUserInvigilatorForExam = (exam: any) => {
    if (!currentUser.value?.id || !exam?.invigilators || !Array.isArray(exam.invigilators)) return false;
    return exam.invigilators.some((inv: any) => 
        inv.staff?.user_id === currentUser.value.id || 
        inv.staff?.user?.id === currentUser.value.id
    );
};

const myInvigilatedCount = computed(() => {
    return (props.schedules.data || []).filter(isUserInvigilatorForExam).length;
});

const filterMyInvigilationsOnly = ref(false);

const displaySchedulesData = computed(() => {
    if (filterMyInvigilationsOnly.value) {
        return props.schedules.data.filter(isUserInvigilatorForExam);
    }
    return props.schedules.data;
});

// Computed: Group exam schedules by Date for Grid / Timetable View
const groupedByDate = computed(() => {
    const groups: { [key: string]: { formattedDate: string; rawDate: string; items: any[] } } = {};

    displaySchedulesData.value.forEach((exam) => {
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

// Computed: Group exam schedules by Venue (Building) -> Date -> Time Slot -> Courses
const groupedByVenue = computed(() => {
    const groups: {
        [key: string]: {
            venueName: string;
            capacity: number;
            totalExams: number;
            datesMap: {
                [rawDate: string]: {
                    rawDate: string;
                    dateFormatted: string;
                    timeSlotsMap: {
                        [timeKey: string]: {
                            startTime: string;
                            endTime: string;
                            exams: any[];
                        };
                    };
                };
            };
        };
    } = {};

    displaySchedulesData.value.forEach((exam) => {
        const venueName = exam.venue ? exam.venue.trim() : 'Unassigned Venue';
        const rawDate = exam.exam_date ? exam.exam_date.substring(0, 10) : 'No Date';
        const dateFormatted = exam.exam_date ? format(new Date(exam.exam_date), 'EEEE, MMM dd, yyyy') : 'Unscheduled Date';
        const timeKey = `${exam.start_time || '00:00'}-${exam.end_time || '00:00'}`;

        if (!groups[venueName]) {
            groups[venueName] = {
                venueName,
                capacity: exam.max_capacity || 0,
                totalExams: 0,
                datesMap: {},
            };
        }

        groups[venueName].totalExams++;

        if (!groups[venueName].datesMap[rawDate]) {
            groups[venueName].datesMap[rawDate] = {
                rawDate,
                dateFormatted,
                timeSlotsMap: {},
            };
        }

        if (!groups[venueName].datesMap[rawDate].timeSlotsMap[timeKey]) {
            groups[venueName].datesMap[rawDate].timeSlotsMap[timeKey] = {
                startTime: exam.start_time,
                endTime: exam.end_time,
                exams: [],
            };
        }

        groups[venueName].datesMap[rawDate].timeSlotsMap[timeKey].exams.push(exam);
    });

    return Object.values(groups).map(g => ({
        venueName: g.venueName,
        capacity: g.capacity,
        totalExams: g.totalExams,
        dates: Object.values(g.datesMap)
            .sort((a, b) => a.rawDate.localeCompare(b.rawDate))
            .map(d => ({
                rawDate: d.rawDate,
                dateFormatted: d.dateFormatted,
                timeSlots: Object.values(d.timeSlotsMap).sort((a, b) => (a.startTime || '').localeCompare(b.startTime || ''))
            }))
    })).sort((a, b) => a.venueName.localeCompare(b.venueName));
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
    { value: '', label: '+ Add Course to Exam Schedule...' },
    ...(props.courses || []).map(c => ({ value: c.id, label: `${c.code} - ${c.title}` }))
]);

const selectedCourseIds = ref<string[]>([]);
const tempCourseSelect = ref<string>('');

const addCoursePill = (val: string) => {
    if (!val) return;
    if (!selectedCourseIds.value.includes(val)) {
        selectedCourseIds.value.push(val);
    }
    tempCourseSelect.value = '';
};

const removeCoursePill = (courseId: string) => {
    selectedCourseIds.value = selectedCourseIds.value.filter(id => id !== courseId);
};

const getCourseLabel = (courseId: string) => {
    const found = (props.courses || []).find(c => c.id === courseId);
    return found ? `${found.code} - ${found.title}` : courseId;
};

const buildingSelectOptions = computed(() => [
    { value: '', label: 'Quick Select Registered Building...' },
    ...(props.buildings || []).map(b => ({
        value: b.name,
        label: `🏢 ${b.name} (${b.code}) — Cap: ${b.capacity}`
    }))
]);

const venueList = ref<Array<{ name: string; capacity: number | string }>>([
    { name: '', capacity: 100 }
]);

const addVenueRow = () => {
    venueList.value.push({ name: '', capacity: 100 });
};

const removeVenueRow = (index: number) => {
    if (venueList.value.length > 1) {
        venueList.value.splice(index, 1);
    }
};

const selectedBuildingQuick = ref('');
const handleBuildingQuickSelect = (val: string) => {
    if (!val) return;
    const found = (props.buildings || []).find(b => b.name === val);
    if (found) {
        const lastIndex = venueList.value.length - 1;
        if (venueList.value[lastIndex] && !venueList.value[lastIndex].name.trim()) {
            venueList.value[lastIndex].name = found.name;
            venueList.value[lastIndex].capacity = found.capacity;
        } else {
            venueList.value.push({ name: found.name, capacity: found.capacity });
        }
    }
    selectedBuildingQuick.value = '';
};

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
    router.visit(route('admin.exams.create'));
};

const openEditSchedule = (exam: any) => {
    router.visit(route('admin.exams.edit', exam.id));
};

const submitSchedule = () => {
    if (selectedCourseIds.value.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Course Required',
            text: 'Please select at least one course for the examination schedule.',
        });
        return;
    }

    const validVenues = venueList.value.filter(v => v.name.trim() !== '');
    if (validVenues.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Venue Required',
            text: 'Please enter at least one venue or hall for this exam schedule.',
        });
        return;
    }

    scheduleForm.course_id = selectedCourseIds.value[0];
    (scheduleForm as any).course_ids = selectedCourseIds.value;
    scheduleForm.venue = validVenues.map(v => v.name.trim()).join(', ');
    const totalCap = validVenues.reduce((acc, curr) => acc + (Number(curr.capacity) || 0), 0);
    scheduleForm.max_capacity = totalCap > 0 ? totalCap : 100;

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

const selectedInvigilatorHallScheduleId = ref<string>('');
const selectedInvigilatorStaffIds = ref<string[]>([]);
const tempStaffSelect = ref<string>('');

const addInvigilatorStaffPill = (val: string) => {
    if (!val) return;
    if (!selectedInvigilatorStaffIds.value.includes(val)) {
        selectedInvigilatorStaffIds.value.push(val);
    }
    tempStaffSelect.value = '';
};

const removeInvigilatorStaffPill = (stId: string) => {
    selectedInvigilatorStaffIds.value = selectedInvigilatorStaffIds.value.filter(id => id !== stId);
};

const getStaffLabel = (stId: string) => {
    const found = (props.staff || []).find(st => st.id === stId);
    return found ? `${found.name} (${found.staff_number})` : stId;
};

const openInvigilatorModal = (exam: any) => {
    selectedExamForInvigilator.value = exam;
    selectedInvigilatorHallScheduleId.value = exam.id;
    invigilatorForm.reset();
    selectedInvigilatorStaffIds.value = [];
    tempStaffSelect.value = '';
    isInvigilatorModalOpen.value = true;
};

const sameCourseHallOptions = computed(() => {
    if (!selectedExamForInvigilator.value) return [];
    const courseId = selectedExamForInvigilator.value.course_id;
    const date = selectedExamForInvigilator.value.exam_date;

    const matches = (props.schedules.data || []).filter(e => e.course_id === courseId && e.exam_date === date);
    return matches.map(m => ({
        value: m.id,
        label: `📍 ${m.venue} (${m.max_capacity} seats) — ${m.start_time}`
    }));
});

const submitInvigilator = () => {
    if (selectedInvigilatorStaffIds.value.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Staff Required',
            text: 'Please select at least one staff member to assign as invigilator.',
        });
        return;
    }

    const targetScheduleId = selectedInvigilatorHallScheduleId.value || selectedExamForInvigilator.value.id;
    
    invigilatorForm.staff_id = selectedInvigilatorStaffIds.value[0];
    (invigilatorForm as any).staff_ids = selectedInvigilatorStaffIds.value;

    invigilatorForm.post(route('admin.exams.invigilators.assign', targetScheduleId), {
        onSuccess: () => {
            isInvigilatorModalOpen.value = false;
            invigilatorForm.reset();
            selectedInvigilatorStaffIds.value = [];
            tempStaffSelect.value = '';
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

const downloadTemplate = (type = 'combined') => {
    window.location.href = route('admin.exams.template', { type });
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

const resetAllFilters = () => {
    filterSessionId.value = '';
    filterSemesterId.value = '';
    filterDepartmentId.value = '';
    filterLevel.value = '';
    filterExamType.value = '';
    search.value = '';
    filterMyInvigilationsOnly.value = false;
    applyFilters();
};
</script>

<template>
    <Head title="Examination Management Hub" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full print:p-0 print:m-0">
            
            <!-- Hero Header & Actions (Hidden on Print) -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4 print:hidden">
                <!-- Top Row: Title + Active Session Badge & Primary Actions -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <div class="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                <Calendar class="w-6 h-6" />
                            </div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                                Examination Management Hub
                            </h1>
                            <Badge class="bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border-purple-200 text-xs font-semibold px-2.5 py-0.5">
                                {{ activeSessionName }} • {{ activeSemesterName }}
                            </Badge>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Schedule examinations, view semester timetables, assign staff invigilators, and track malpractice logs.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <Button v-if="props.canManageExams" class="bg-purple-600 hover:bg-purple-700 text-white shadow-sm font-semibold gap-1.5 text-xs h-9 px-4 rounded-xl" @click="openCreateSchedule">
                            <Plus class="w-4 h-4" />
                            <span>Schedule Exam</span>
                        </Button>

                        <Button class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm font-semibold gap-1.5 text-xs h-9 px-4 rounded-xl" @click="router.visit(route('admin.exams.scanner'))">
                            <QrCode class="w-4 h-4" />
                            <span>Mobile QR Scanner</span>
                        </Button>
                    </div>
                </div>

                <!-- Secondary Row: View Switcher Tabs & Tools Toolbar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <!-- View Mode Switcher -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200/60 dark:border-slate-700/60 overflow-x-auto">
                        <button 
                            @click="viewMode = 'venue'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-lg flex items-center gap-1.5 transition-all whitespace-nowrap',
                                viewMode === 'venue' ? 'bg-white dark:bg-slate-900 shadow-xs text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <Building class="w-3.5 h-3.5 text-indigo-600" /> By Venue
                        </button>
                        <button 
                            @click="viewMode = 'table'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all whitespace-nowrap',
                                viewMode === 'table' ? 'bg-white dark:bg-slate-900 shadow-xs text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <LayoutList class="w-3.5 h-3.5" /> Table View
                        </button>
                        <button 
                            @click="viewMode = 'grid'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all whitespace-nowrap',
                                viewMode === 'grid' ? 'bg-white dark:bg-slate-900 shadow-xs text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <Grid class="w-3.5 h-3.5" /> By Date
                        </button>
                        <button 
                            @click="viewMode = 'printable'"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all whitespace-nowrap',
                                viewMode === 'printable' ? 'bg-white dark:bg-slate-900 shadow-xs text-purple-700 dark:text-purple-300' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <Printer class="w-3.5 h-3.5" /> Master Sheet
                        </button>
                    </div>

                    <!-- Management & Utilities Bar -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Button 
                            v-if="props.canManageExams"
                            variant="outline" 
                            @click="togglePublish" 
                            :class="[
                                'font-semibold gap-1.5 border text-xs h-8 px-3 rounded-lg',
                                isPublished 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' 
                                    : 'bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300'
                            ]"
                        >
                            <Eye v-if="isPublished" class="w-3.5 h-3.5 text-emerald-600" />
                            <EyeOff v-else class="w-3.5 h-3.5 text-amber-600" />
                            <span>{{ isPublished ? 'Published' : 'Draft Mode' }}</span>
                        </Button>

                        <Button v-if="props.canManageExams" variant="outline" class="border-purple-200 text-purple-700 dark:text-purple-300 hover:bg-purple-50 font-semibold gap-1.5 text-xs h-8 px-3 rounded-lg" @click="openImportModal">
                            <Upload class="w-3.5 h-3.5" />
                            <span>Bulk Upload</span>
                        </Button>

                        <Button variant="outline" class="border-indigo-200 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 font-semibold gap-1.5 text-xs h-8 px-3 rounded-lg" @click="router.visit('/admin/buildings')">
                            <Building2 class="w-3.5 h-3.5" />
                            <span>Campus Buildings</span>
                        </Button>

                        <Button variant="outline" class="border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 font-semibold gap-1.5 text-xs h-8 px-3 rounded-lg" @click="openVerifyModal()">
                            <ScanLine class="w-3.5 h-3.5" />
                            <span>Quick Verify</span>
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
                <Card class="border shadow-xs bg-white dark:bg-slate-900 relative overflow-hidden">
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

                <Card class="border shadow-xs bg-white dark:bg-slate-900 relative overflow-hidden">
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

                <Card class="border shadow-xs bg-white dark:bg-slate-900 relative overflow-hidden">
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

                <Card class="border shadow-xs bg-white dark:bg-slate-900 relative overflow-hidden">
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
            <Card class="border shadow-xs bg-white dark:bg-slate-900 p-4 print:hidden rounded-2xl">
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5">
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

                        <!-- Search Input -->
                        <div class="relative w-full">
                            <Input 
                                v-model="search" 
                                placeholder="Search Ref ID, course..." 
                                @keyup.enter="applyFilters"
                                class="pl-9 h-9 text-xs focus-visible:ring-purple-500 w-full"
                            />
                            <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="myInvigilatedCount > 0"
                                variant="outline"
                                :class="[
                                    'h-7 text-xs font-semibold gap-1.5 transition-all rounded-lg',
                                    filterMyInvigilationsOnly 
                                        ? 'bg-amber-100 border-amber-400 text-amber-900 dark:bg-amber-950 dark:text-amber-200' 
                                        : 'border-amber-300 text-amber-800 hover:bg-amber-50 dark:text-amber-300'
                                ]"
                                @click="filterMyInvigilationsOnly = !filterMyInvigilationsOnly"
                            >
                                <Sparkles class="w-3.5 h-3.5 text-amber-600" />
                                <span>My Invigilations ({{ myInvigilatedCount }})</span>
                            </Button>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="sm" @click="resetAllFilters" class="h-7 text-xs text-slate-500 hover:text-slate-700">
                                Reset Filters
                            </Button>
                            <Button size="sm" @click="applyFilters" class="h-7 text-xs bg-slate-900 dark:bg-slate-100 dark:text-slate-900 rounded-lg">
                                Apply Filters
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- 1. VENUE GROUPED VIEW MODE -->
            <div v-if="viewMode === 'venue'" class="space-y-6 print:hidden">
                <div v-for="group in groupedByVenue" :key="group.venueName" class="space-y-4 bg-white dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    
                    <!-- Venue Header -->
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-100 dark:bg-purple-950/50 text-purple-600 rounded-lg">
                                <Building2 class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                    {{ group.venueName }}
                                    <Badge variant="outline" class="text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                                        Capacity: {{ group.capacity }} Seats
                                    </Badge>
                                </h3>
                                <p class="text-xs text-slate-500 font-medium">
                                    {{ group.totalExams }} course exam paper(s) scheduled across {{ group.dates.length }} date(s)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Dates within Venue -->
                    <div class="space-y-5 pt-1">
                        <div v-for="dateGroup in group.dates" :key="dateGroup.rawDate" class="space-y-3">
                            
                            <!-- Date Badge Header -->
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-md text-xs font-bold text-slate-700 dark:text-slate-300">
                                    <Calendar class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                                    {{ dateGroup.dateFormatted }}
                                </div>
                                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                            </div>

                            <!-- Time Slots within Date -->
                            <div v-for="slot in dateGroup.timeSlots" :key="slot.startTime + '-' + slot.endTime" class="space-y-3 pl-2 sm:pl-4 border-l-2 border-purple-300 dark:border-purple-800">
                                
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <Badge class="bg-indigo-600 text-white dark:bg-indigo-500 font-mono text-xs px-2.5 py-0.5">
                                            <Clock class="w-3 h-3 mr-1 inline" /> {{ slot.startTime }} - {{ slot.endTime }}
                                        </Badge>
                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">
                                            {{ slot.exams.length }} Course(s) scheduled in this venue at this time
                                        </span>
                                    </div>
                                </div>

                                <!-- Courses Grid taking this venue at this date & time slot -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <Card 
                                        v-for="exam in slot.exams" 
                                        :key="exam.id"
                                        :class="[
                                            'border shadow-xs bg-white dark:bg-slate-950/60 hover:border-purple-300 dark:hover:border-purple-800 transition-all flex flex-col justify-between',
                                            isUserInvigilatorForExam(exam) ? 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-300' : ''
                                        ]"
                                    >
                                        <CardContent class="p-3.5 space-y-2.5">
                                            <div class="flex items-start justify-between gap-2 border-b pb-2">
                                                <div>
                                                    <span class="font-mono text-[10px] font-bold text-purple-600 dark:text-purple-400 block">{{ exam.reference_id }}</span>
                                                    <h4 class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-tight mt-0.5">
                                                        {{ exam.course?.code }} - {{ exam.course?.title }}
                                                    </h4>
                                                    <span class="text-[11px] text-slate-500 block mt-0.5">
                                                        {{ exam.department?.name || 'General Course' }} ({{ exam.level ? exam.level + 'L' : 'All Levels' }})
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="space-y-1 text-xs text-slate-600 dark:text-slate-400">
                                                <div class="flex items-center justify-between">
                                                    <span class="flex items-center gap-1.5 font-medium"><UserCheck class="w-3.5 h-3.5 text-emerald-500" /> Present:</span>
                                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ exam.attendances_count || 0 }} Verified</span>
                                                </div>
                                            </div>

                                            <div class="pt-1.5 border-t text-[11px] space-y-1">
                                                <span class="font-bold text-slate-400 uppercase tracking-wider block text-[10px]">Invigilators</span>
                                                <div v-if="exam.invigilators && exam.invigilators.length > 0" class="flex flex-wrap gap-1">
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

                                        <div class="p-2.5 bg-slate-50 dark:bg-slate-950/60 border-t flex items-center justify-between text-xs">
                                            <Button size="sm" variant="outline" class="h-7 text-[11px] border-emerald-300 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100" @click="router.visit(route('admin.exams.scanner', { schedule_id: exam.id }))">
                                                <QrCode class="w-3 h-3 mr-1 text-emerald-600" /> Scan
                                            </Button>
                                            <div class="flex items-center gap-1">
                                                <Button size="sm" variant="outline" class="h-7 text-[11px]" @click="openDetailsModal(exam)">
                                                    <Eye class="w-3 h-3 mr-1" /> View
                                                </Button>
                                                <Button v-if="props.canManageExams" size="sm" variant="outline" class="h-7 text-[11px] border-indigo-200 text-indigo-700" @click="openInvigilatorModal(exam)">
                                                    + Staff
                                                </Button>
                                                <Button v-if="props.canManageExams" size="sm" variant="ghost" class="h-7 w-7 p-0 text-slate-500 hover:text-indigo-600" @click="openEditSchedule(exam)" title="Edit Exam Schedule">
                                                    <Edit3 class="w-3.5 h-3.5" />
                                                </Button>
                                                <Button v-if="props.canManageExams" size="sm" variant="ghost" class="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40" @click="deleteExam(exam.id)" title="Delete Exam Schedule">
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </Button>
                                            </div>
                                        </div>
                                    </Card>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="groupedByVenue.length === 0" class="p-10 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-400 p-4">
                        <Building2 class="w-8 h-8" />
                    </div>
                    <div class="max-w-md mx-auto space-y-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                            No Exam Schedules Found
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            There are currently no examination schedules registered for 
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ activeSessionName }}</span> 
                            ({{ activeSemesterName }}).
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-2.5 pt-2">
                        <Button v-if="props.canManageExams" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs gap-1.5 rounded-xl" @click="openCreateSchedule">
                            <Plus class="w-4 h-4" /> Schedule New Exam
                        </Button>
                        <Button v-if="props.canManageExams" variant="outline" class="border-purple-200 text-purple-700 dark:text-purple-300 hover:bg-purple-50 text-xs gap-1.5 rounded-xl" @click="openImportModal">
                            <Upload class="w-4 h-4" /> Bulk CSV Upload
                        </Button>
                        <Button variant="outline" class="border-slate-200 text-slate-700 dark:text-slate-300 hover:bg-slate-50 text-xs gap-1.5 rounded-xl" @click="resetAllFilters">
                            Clear Filters / View All
                        </Button>
                    </div>
                </div>

                <Pagination :links="schedules.links" />
            </div>

            <!-- 2. TABLE & MOBILE CARDS VIEW MODE -->
            <div v-else-if="viewMode === 'table'" class="space-y-4 print:hidden">
                <!-- Mobile Cards Layout (visible on phones/tablets below md breakpoint) -->
                <div class="block md:hidden space-y-3">
                    <div 
                        v-for="exam in displaySchedulesData" 
                        :key="'mobile-' + exam.id"
                        :class="[
                            'p-4 rounded-2xl border shadow-xs transition-all space-y-3',
                            isUserInvigilatorForExam(exam) ? 'bg-amber-50/70 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800 ring-1 ring-amber-400/30' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
                        ]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-black text-slate-900 dark:text-slate-100 text-base leading-tight block">
                                    {{ exam.course?.code }} - {{ exam.course?.title }}
                                </span>
                                <span class="text-xs text-slate-500 font-medium mt-0.5 block">
                                    {{ exam.department?.name || 'General Course' }} ({{ exam.level ? exam.level + 'L' : 'All Levels' }})
                                </span>
                            </div>
                            <Badge v-if="isUserInvigilatorForExam(exam)" class="bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950 dark:text-amber-300 text-[10px] gap-1 px-2 py-0.5 font-bold shrink-0">
                                <Sparkles class="w-3 h-3 text-amber-600" />
                                <span>MY INVIGILATION</span>
                            </Badge>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-950/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-1.5">
                                <Calendar class="w-3.5 h-3.5 text-purple-500 shrink-0" />
                                <span class="truncate">{{ format(new Date(exam.exam_date), 'MMM dd, yyyy') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span class="truncate">{{ exam.start_time }} - {{ exam.end_time }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 col-span-2">
                                <Building class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ exam.venue }}</span>
                                <span class="text-[11px] text-slate-400 font-normal">({{ exam.max_capacity }} seats)</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 pt-1">
                            <Badge variant="outline" class="text-[10px] bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 flex items-center gap-1 justify-center py-1">
                                <UserCheck class="w-3 h-3 text-emerald-600" />
                                <span>{{ exam.attendances_count || 0 }} Verified Present</span>
                            </Badge>

                            <Button 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs h-11 px-4 gap-2 shadow-sm rounded-xl w-full sm:w-auto justify-center" 
                                @click="router.visit(route('admin.exams.scanner', { schedule_id: exam.id }))"
                            >
                                <QrCode class="w-4 h-4" />
                                <span>Scan Attendance</span>
                            </Button>
                        </div>
                    </div>

                    <div v-if="displaySchedulesData.length === 0" class="p-8 text-center text-xs text-slate-400 bg-white dark:bg-slate-900 rounded-2xl border">
                        No exam schedules found matching criteria.
                    </div>
                </div>

                <!-- Desktop Table View (visible on medium screens and up) -->
                <Card class="hidden md:block border shadow-sm rounded-xl overflow-hidden bg-white dark:bg-slate-900">
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
                                    v-for="exam in displaySchedulesData" 
                                    :key="exam.id"
                                    :class="[
                                        'hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-all border-b',
                                        isUserInvigilatorForExam(exam) ? 'bg-amber-50/30 dark:bg-amber-950/10' : ''
                                    ]"
                                >
                                    <TableCell class="font-mono text-xs font-bold text-purple-700 dark:text-purple-400 pl-6">
                                        <div class="flex flex-col gap-1">
                                            <span>{{ exam.reference_id }}</span>
                                            <Badge v-if="isUserInvigilatorForExam(exam)" class="bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300 text-[9px] gap-1 px-1.5 py-0 w-max font-bold">
                                                <Sparkles class="w-2.5 h-2.5 text-amber-600" />
                                                <span>MY INVIGILATION</span>
                                            </Badge>
                                        </div>
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
                                            <Button variant="outline" size="sm" class="h-8 text-xs border-emerald-300 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 font-semibold" @click="router.visit(route('admin.exams.scanner', { schedule_id: exam.id }))">
                                                <QrCode class="w-3.5 h-3.5 mr-1 text-emerald-600" /> Attendance
                                            </Button>
                                            <Button variant="outline" size="sm" class="h-8 text-xs" @click="openDetailsModal(exam)">
                                                <Eye class="w-3.5 h-3.5 mr-1" /> View
                                            </Button>
                                            <Button v-if="props.canManageExams" variant="outline" size="sm" class="h-8 text-xs border-indigo-200 text-indigo-700 hover:bg-indigo-50" @click="openInvigilatorModal(exam)">
                                                <UserCheck class="w-3.5 h-3.5 mr-1" /> Invigilator
                                            </Button>
                                            <Button variant="outline" size="sm" class="h-8 text-xs border-rose-200 text-rose-700 hover:bg-rose-50" @click="openIncidentModal(exam)">
                                                <ShieldAlert class="w-3.5 h-3.5 mr-1" /> Incident
                                            </Button>
                                            <Button v-if="props.canManageExams" variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:bg-slate-100" @click="openEditSchedule(exam)">
                                                <Edit3 class="w-4 h-4" />
                                            </Button>
                                            <Button v-if="props.canManageExams" variant="ghost" size="icon" class="h-8 w-8 text-rose-500 hover:bg-rose-50" @click="deleteExam(exam.id)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="displaySchedulesData.length === 0">
                                    <TableCell colspan="7" class="h-32 text-center text-slate-400">
                                        No exam schedules found matching criteria.
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
                                    <Button v-if="props.canManageExams" size="sm" variant="ghost" class="h-7 w-7 p-0 text-slate-500 hover:text-indigo-600" @click="openEditSchedule(exam)" title="Edit Exam Schedule">
                                        <Edit3 class="w-3.5 h-3.5" />
                                    </Button>
                                    <Button v-if="props.canManageExams" size="sm" variant="ghost" class="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40" @click="deleteExam(exam.id)" title="Delete Exam Schedule">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <div v-if="groupedByDate.length === 0" class="p-10 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-400 p-4">
                        <Calendar class="w-8 h-8" />
                    </div>
                    <div class="max-w-md mx-auto space-y-1">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                            No Examination Dates Found
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            There are currently no examination schedules registered for 
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ activeSessionName }}</span> 
                            ({{ activeSemesterName }}).
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-2.5 pt-2">
                        <Button v-if="props.canManageExams" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs gap-1.5 rounded-xl" @click="openCreateSchedule">
                            <Plus class="w-4 h-4" /> Schedule New Exam
                        </Button>
                        <Button v-if="props.canManageExams" variant="outline" class="border-purple-200 text-purple-700 dark:text-purple-300 hover:bg-purple-50 text-xs gap-1.5 rounded-xl" @click="openImportModal">
                            <Upload class="w-4 h-4" /> Bulk CSV Upload
                        </Button>
                        <Button variant="outline" class="border-slate-200 text-slate-700 dark:text-slate-300 hover:bg-slate-50 text-xs gap-1.5 rounded-xl" @click="resetAllFilters">
                            Clear Filters / View All
                        </Button>
                    </div>
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

                    <div class="p-3.5 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-900/60 rounded-xl space-y-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <span class="text-xs font-bold text-purple-900 dark:text-purple-300">Download CSV Templates:</span>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Button type="button" size="sm" variant="outline" class="h-7 text-[11px] border-purple-300 text-purple-700 dark:text-purple-300 font-semibold" @click="downloadTemplate('combined')">
                                    <Download class="w-3.5 h-3.5 mr-1" /> Timetable + Invigilators
                                </Button>
                                <Button type="button" size="sm" variant="outline" class="h-7 text-[11px] border-indigo-300 text-indigo-700 dark:text-indigo-300 font-semibold" @click="downloadTemplate('invigilators_only')">
                                    <Download class="w-3.5 h-3.5 mr-1" /> Invigilators Only
                                </Button>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 font-medium">
                            Upload a <strong>Combined CSV</strong> (exam date, time, venue + chief/assistant invigilators) or an <strong>Invigilators Only CSV</strong> (course_code, venue, staff_number, role) to assign staff to existing exam schedules.
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
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Course(s) for this Exam</Label>
                            <span v-if="selectedCourseIds.length > 0" class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold">
                                {{ selectedCourseIds.length }} course(s) selected
                            </span>
                        </div>
                        
                        <SearchableSelect
                            v-model="tempCourseSelect"
                            :items="courseModalOptions"
                            placeholder="+ Add course to exam schedule..."
                            search-placeholder="Search course code or title..."
                            @update:model-value="addCoursePill"
                        />

                        <!-- Selected Course Badges -->
                        <div v-if="selectedCourseIds.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                            <Badge
                                v-for="cId in selectedCourseIds"
                                :key="cId"
                                class="bg-purple-100 text-purple-900 dark:bg-purple-950 dark:text-purple-200 border border-purple-300 dark:border-purple-800 text-xs py-1 px-2.5 flex items-center gap-1.5 shadow-2xs"
                            >
                                <span>{{ getCourseLabel(cId) }}</span>
                                <button
                                    type="button"
                                    @click="removeCoursePill(cId)"
                                    class="text-purple-600 hover:text-red-600 dark:text-purple-400 dark:hover:text-red-400 font-bold ml-1 rounded-full hover:bg-purple-200/60 dark:hover:bg-purple-900/60 w-4 h-4 inline-flex items-center justify-center transition-colors"
                                    title="Remove course"
                                >
                                    &times;
                                </button>
                            </Badge>
                        </div>
                        <p v-else class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">⚠️ Select one or more courses to schedule at this venue & date.</p>
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

                    <!-- Venues Dynamic Array List -->
                    <div class="space-y-3 rounded-lg border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <Label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Exam Venues & Hall Capacities</Label>
                                <p class="text-[11px] text-slate-500">Add one or multiple venues to schedule this exam across multiple halls.</p>
                            </div>
                            <Button type="button" variant="outline" size="sm" class="h-7 text-xs gap-1 border-purple-300 text-purple-700 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-300" @click="addVenueRow">
                                <Plus class="w-3.5 h-3.5" />
                                Add Venue
                            </Button>
                        </div>

                        <!-- Quick Select registered campus building -->
                        <div v-if="buildingSelectOptions.length > 1" class="pt-1">
                            <SearchableSelect
                                v-model="selectedBuildingQuick"
                                :items="buildingSelectOptions"
                                placeholder="⚡ Quick select registered building to add..."
                                search-placeholder="Search campus building..."
                                @update:model-value="handleBuildingQuickSelect"
                            />
                        </div>

                        <!-- Dynamic Venue Rows -->
                        <div class="space-y-2 pt-1">
                            <div v-for="(vItem, idx) in venueList" :key="idx" class="flex items-center gap-2 bg-white dark:bg-slate-950 p-2 rounded-md border border-slate-200 dark:border-slate-800">
                                <span class="text-xs font-bold text-purple-600 dark:text-purple-400 w-5 text-center">#{{ idx + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <Input
                                        v-model="vItem.name"
                                        placeholder="Hall / Building Name (e.g. Multipurpose Hall A)"
                                        class="h-8 text-xs bg-transparent"
                                        required
                                    />
                                </div>
                                <div class="w-28">
                                    <Input
                                        v-model="vItem.capacity"
                                        type="number"
                                        min="1"
                                        placeholder="Capacity"
                                        class="h-8 text-xs bg-transparent"
                                        title="Capacity for this hall"
                                        required
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 w-8 p-0 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30"
                                    :disabled="venueList.length <= 1"
                                    @click="removeVenueRow(idx)"
                                    title="Remove venue"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-200 dark:border-slate-800">
                            <span>Selected Halls: <strong class="text-slate-800 dark:text-slate-200">{{ venueList.filter(v => v.name.trim()).length }}</strong></span>
                            <span>Total Seating Capacity: <strong class="text-purple-600 dark:text-purple-400 font-bold">{{ venueList.reduce((acc, curr) => acc + (Number(curr.capacity) || 0), 0) }} seats</strong></span>
                        </div>
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
                    <div v-if="sameCourseHallOptions.length > 1" class="space-y-1.5 p-3 bg-purple-50 dark:bg-purple-950/40 rounded-xl border border-purple-200 dark:border-purple-800">
                        <Label class="text-xs font-bold uppercase tracking-wider text-purple-900 dark:text-purple-300 flex items-center gap-1.5">
                            <Building class="w-4 h-4 text-purple-600" />
                            <span>Target Exam Hall Venue</span>
                        </Label>
                        <SearchableSelect
                            v-model="selectedInvigilatorHallScheduleId"
                            :items="sameCourseHallOptions"
                            placeholder="Select Target Hall Venue"
                            search-placeholder="Search hall venue..."
                        />
                    </div>
                    <div v-else-if="selectedExamForInvigilator" class="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <Building class="w-4 h-4 text-purple-600 shrink-0" />
                        <span>Assigned Hall Venue: <strong>{{ selectedExamForInvigilator.venue }}</strong> ({{ selectedExamForInvigilator.max_capacity }} seats)</span>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Staff Member(s)</Label>
                            <span v-if="selectedInvigilatorStaffIds.length > 0" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">
                                {{ selectedInvigilatorStaffIds.length }} staff member(s) selected
                            </span>
                        </div>
                        
                        <SearchableSelect
                            v-model="tempStaffSelect"
                            :items="staffModalOptions"
                            placeholder="+ Add staff member..."
                            search-placeholder="Search staff by name or number..."
                            @update:model-value="addInvigilatorStaffPill"
                        />

                        <!-- Selected Staff Badges -->
                        <div v-if="selectedInvigilatorStaffIds.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                            <Badge
                                v-for="stId in selectedInvigilatorStaffIds"
                                :key="stId"
                                class="bg-indigo-50 text-indigo-900 dark:bg-indigo-950 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800 text-xs py-1 px-2.5 flex items-center gap-1.5 shadow-2xs"
                            >
                                <span>{{ getStaffLabel(stId) }}</span>
                                <button
                                    type="button"
                                    @click="removeInvigilatorStaffPill(stId)"
                                    class="text-indigo-600 hover:text-red-600 dark:text-indigo-400 dark:hover:text-red-400 font-bold ml-1 rounded-full hover:bg-indigo-200/60 dark:hover:bg-indigo-900/60 w-4 h-4 inline-flex items-center justify-center transition-colors"
                                    title="Remove staff"
                                >
                                    &times;
                                </button>
                            </Badge>
                        </div>
                        <p v-else class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">⚠️ Select one or more staff members to assign as invigilators.</p>
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
