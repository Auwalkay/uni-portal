<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
    UserCheck, AlertTriangle, FileText, CheckCircle, Eye, EyeOff, Users,
    LayoutList, CalendarRange, QrCode, ArrowLeft, Layers
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

interface Props {
    exam: any;
    schedules: any[];
    incidents: any[];
    conflicts: any[];
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    staff: any[];
    students: any[];
    buildings?: any[];
    canManageExams?: boolean;
}

const props = defineProps<Props>();

const activeTab = ref<'schedules' | 'venues' | 'invigilators' | 'incidents'>('schedules');
const searchQuery = ref('');

// Computed Metrics
const totalPaperSlots = computed(() => props.schedules?.length || 0);

const totalSeatingCapacity = computed(() => {
    return (props.schedules || []).reduce((acc, curr) => acc + (Number(curr.max_capacity) || 0), 0);
});

const totalInvigilatorAssignments = computed(() => {
    return (props.schedules || []).reduce((acc, curr) => acc + (curr.invigilators?.length || 0), 0);
});

const totalIncidentsCount = computed(() => props.incidents?.length || 0);

const filteredSchedules = computed(() => {
    let list = props.schedules || [];
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter((s: any) => 
            (s.course?.code && s.course.code.toLowerCase().includes(q)) ||
            (s.course?.title && s.course.title.toLowerCase().includes(q)) ||
            (s.venue && s.venue.toLowerCase().includes(q)) ||
            (s.reference_id && s.reference_id.toLowerCase().includes(q))
        );
    }
    return list;
});

// Grouped by Venue for Venue Tab
const groupedByVenue = computed(() => {
    const groups: { [key: string]: { venueName: string; capacity: number; items: any[] } } = {};
    (props.schedules || []).forEach((s) => {
        const vName = s.venue ? s.venue.trim() : 'Unassigned Venue';
        if (!groups[vName]) {
            groups[vName] = {
                venueName: vName,
                capacity: s.max_capacity || 0,
                items: [],
            };
        }
        groups[vName].items.push(s);
    });
    return Object.values(groups);
});

// Invigilator Modals State
const isInvigilatorModalOpen = ref(false);
const selectedExamForInvigilator = ref<any>(null);
const selectedInvigilatorStaffIds = ref<string[]>([]);
const tempStaffSelect = ref<string>('');

const invigilatorForm = useForm({
    staff_id: '',
    role: 'chief',
});

const openInvigilatorModal = (examSlot: any) => {
    selectedExamForInvigilator.value = examSlot;
    selectedInvigilatorStaffIds.value = [];
    tempStaffSelect.value = '';
    isInvigilatorModalOpen.value = true;
};

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

const submitInvigilator = () => {
    if (selectedInvigilatorStaffIds.value.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Staff Required', text: 'Select at least one staff member.' });
        return;
    }
    invigilatorForm.staff_id = selectedInvigilatorStaffIds.value[0];
    (invigilatorForm as any).staff_ids = selectedInvigilatorStaffIds.value;

    invigilatorForm.post(route('admin.exams.invigilators.assign', selectedExamForInvigilator.value.id), {
        onSuccess: () => {
            isInvigilatorModalOpen.value = false;
            invigilatorForm.reset();
            selectedInvigilatorStaffIds.value = [];
        },
    });
};

const removeInvigilator = (id: string) => {
    if (confirm('Remove invigilator from this exam schedule?')) {
        router.delete(route('admin.exams.invigilators.remove', id));
    }
};

// Incident Modal State
const isIncidentModalOpen = ref(false);
const selectedExamForIncident = ref<any>(null);
const incidentForm = useForm({
    student_id: '',
    incident_type: 'malpractice',
    description: '',
    evidence_notes: '',
});

const openIncidentModal = (examSlot: any) => {
    selectedExamForIncident.value = examSlot;
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

// Toggle Exercise Publish / Docket
const togglePublish = () => {
    router.post(route('admin.exams.exercises.toggle_publish', props.exam.id), {}, { preserveScroll: true });
};

const toggleDocket = () => {
    router.post(route('admin.exams.exercises.toggle_docket', props.exam.id), {}, { preserveScroll: true });
};

const deleteSchedule = (id: string) => {
    if (confirm('Are you sure you want to delete this scheduled course slot?')) {
        router.delete(route('admin.exams.destroy', id));
    }
};

const staffModalOptions = computed(() => [
    { value: '', label: 'Select Invigilator Staff Member...' },
    ...(props.staff || []).map(st => ({ value: st.id, label: `${st.name} (${st.staff_number})` }))
]);

const studentModalOptions = computed(() => [
    { value: '', label: 'Select Student...' },
    ...(props.students || []).map(st => ({ value: st.id, label: `${st.name} (${st.matric_number})` }))
]);

const breadcrumbs = [
    { title: 'Academic Management', href: '#' },
    { title: 'Examination Exercises', href: route('admin.exams.index') },
    { title: props.exam?.title || 'Exam Details', href: '#' },
];
</script>

<template>
    <Head :title="`${exam.title} - Examination Details`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 md:p-8 space-y-8 w-full max-w-7xl mx-auto">
            
            <!-- Sleek Header Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                class="h-7 px-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 text-xs font-medium"
                                @click="router.visit(route('admin.exams.index'))"
                            >
                                <ArrowLeft class="w-3.5 h-3.5 mr-1" /> Exercises Hub
                            </Button>
                            <Badge class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 text-xs font-semibold px-2.5 py-0.5 rounded-md">
                                {{ exam.session?.name }}
                            </Badge>
                            <Badge class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 text-xs font-semibold px-2.5 py-0.5 rounded-md">
                                {{ exam.semester?.name }}
                            </Badge>
                            <Badge v-if="exam.exam_type" variant="outline" class="text-xs font-medium capitalize border-slate-200">
                                {{ exam.exam_type.replace('_', ' ') }}
                            </Badge>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2.5">
                            <CalendarRange class="w-7 h-7 text-indigo-600 dark:text-indigo-400 shrink-0" />
                            {{ exam.title }}
                        </h1>

                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-3xl">
                            Scheduled Period: 
                            <strong class="text-slate-700 dark:text-slate-300 font-semibold">
                                {{ exam.start_date ? format(new Date(exam.start_date), 'MMMM dd, yyyy') : 'Dates Unspecified' }}
                                <span v-if="exam.end_date"> – {{ format(new Date(exam.end_date), 'MMMM dd, yyyy') }}</span>
                            </strong>
                        </p>
                    </div>

                    <!-- Top Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <Button 
                            v-if="props.canManageExams" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs h-10 px-4 rounded-xl shadow-xs gap-1.5"
                            @click="router.visit(route('admin.exams.create', { exam_id: exam.id }))"
                        >
                            <Plus class="w-4 h-4" />
                            <span>+ Schedule Paper Slot</span>
                        </Button>

                        <Button 
                            v-if="props.canManageExams" 
                            variant="outline" 
                            :class="[
                                'h-10 text-xs font-semibold gap-1.5 rounded-xl border',
                                exam.is_published ? 'bg-emerald-50/80 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-amber-50/80 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
                            ]"
                            @click="togglePublish"
                        >
                            <Eye v-if="exam.is_published" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <EyeOff v-else class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            <span>{{ exam.is_published ? 'Published' : 'Draft Mode' }}</span>
                        </Button>

                        <Button 
                            v-if="props.canManageExams" 
                            variant="outline" 
                            class="h-10 text-xs font-semibold gap-1.5 rounded-xl border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-900"
                            @click="toggleDocket"
                        >
                            <FileText class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Docket Access: {{ exam.is_docket_enabled ? 'Enabled' : 'Disabled' }}</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 p-5 rounded-2xl">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Scheduled Slots</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalPaperSlots }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <Calendar class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 p-5 rounded-2xl">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Seating Capacity</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalSeatingCapacity }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <Building class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 p-5 rounded-2xl">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Invigilator Staff</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalInvigilatorAssignments }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <UserCheck class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 p-5 rounded-2xl cursor-pointer hover:border-rose-300 transition-colors" @click="activeTab = 'incidents'">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Incidents Logged</p>
                            <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ totalIncidentsCount }}</p>
                        </div>
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/50 rounded-xl text-rose-600 dark:text-rose-400">
                            <ShieldAlert class="w-5 h-5" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Tab Switcher & Search Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 shadow-xs">
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl overflow-x-auto">
                    <button 
                        @click="activeTab = 'schedules'"
                        :class="[
                            'px-4 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                            activeTab === 'schedules' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        <LayoutList class="w-3.5 h-3.5" /> Paper Slots ({{ totalPaperSlots }})
                    </button>
                    <button 
                        @click="activeTab = 'venues'"
                        :class="[
                            'px-4 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                            activeTab === 'venues' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        <Building2 class="w-3.5 h-3.5" /> Venue Breakdown ({{ groupedByVenue.length }})
                    </button>
                    <button 
                        @click="activeTab = 'incidents'"
                        :class="[
                            'px-4 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5',
                            activeTab === 'incidents' ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                        ]"
                    >
                        <ShieldAlert class="w-3.5 h-3.5" /> Incidents Log ({{ totalIncidentsCount }})
                    </button>
                </div>

                <div class="relative w-full sm:w-72">
                    <Input 
                        v-model="searchQuery" 
                        placeholder="Search slot, course, venue..." 
                        class="pl-9 h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                    />
                    <Search class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                </div>
            </div>

            <!-- Tab Content 1: Schedules List -->
            <div v-if="activeTab === 'schedules'" class="space-y-4">
                <div v-if="filteredSchedules.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <Card 
                        v-for="slot in filteredSchedules" 
                        :key="slot.id" 
                        class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 hover:border-indigo-400 dark:hover:border-indigo-600 transition-all flex flex-col justify-between rounded-2xl overflow-hidden"
                    >
                        <CardContent class="p-5 space-y-4">
                            <div class="space-y-1.5 border-b border-slate-100 dark:border-slate-800 pb-3">
                                <span class="font-mono text-[10px] font-bold text-indigo-600 dark:text-indigo-400 block uppercase tracking-wider">{{ slot.reference_id }}</span>
                                <h4 class="font-bold text-slate-900 dark:text-slate-100 text-base leading-snug">
                                    {{ slot.course?.code }} - {{ slot.course?.title }}
                                </h4>
                                <span class="text-xs text-slate-500 dark:text-slate-400 block">
                                    {{ slot.department?.name || 'General Department' }} • Level {{ slot.level || '100' }}
                                </span>
                            </div>

                            <div class="space-y-2 text-xs text-slate-600 dark:text-slate-400">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 font-medium text-slate-400"><Calendar class="w-3.5 h-3.5 text-indigo-500" /> Date:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ format(new Date(slot.exam_date), 'EEE, MMM dd, yyyy') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 font-medium text-slate-400"><Clock class="w-3.5 h-3.5 text-indigo-500" /> Time:</span>
                                    <Badge variant="outline" class="font-mono text-xs font-semibold px-2 py-0.5">{{ slot.start_time }} - {{ slot.end_time }}</Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 font-medium text-slate-400"><Building class="w-3.5 h-3.5 text-emerald-500" /> Venue:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ slot.venue }} ({{ slot.max_capacity }} seats)</span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs space-y-1.5">
                                <span class="font-semibold text-slate-400 uppercase tracking-wider block text-[10px]">Invigilators</span>
                                <div v-if="slot.invigilators && slot.invigilators.length > 0" class="flex flex-wrap gap-1">
                                    <Badge 
                                        v-for="inv in slot.invigilators" 
                                        :key="inv.id" 
                                        variant="outline" 
                                        class="text-[10px] bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium px-2"
                                    >
                                        {{ inv.staff?.user?.name }} ({{ inv.role }})
                                    </Badge>
                                </div>
                                <span v-else class="text-slate-400 italic text-[11px]">None assigned</span>
                            </div>
                        </CardContent>

                        <div class="p-3 bg-slate-50/80 dark:bg-slate-950/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1 text-xs">
                            <Button size="sm" variant="outline" class="h-8 text-xs border-emerald-300 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/30 font-semibold rounded-lg" @click="router.visit(route('admin.exams.scanner', { schedule_id: slot.id }))">
                                <QrCode class="w-3.5 h-3.5 mr-1" /> Scan
                            </Button>

                            <div class="flex items-center gap-1">
                                <Button size="sm" variant="outline" class="h-8 text-xs border-rose-200 text-rose-700 dark:border-rose-900 dark:text-rose-400 rounded-lg" @click="openIncidentModal(slot)">
                                    <ShieldAlert class="w-3.5 h-3.5 mr-1" /> Incident
                                </Button>
                                <Button v-if="props.canManageExams" size="sm" variant="outline" class="h-8 text-xs border-indigo-200 text-indigo-700 dark:border-indigo-800 dark:text-indigo-300 rounded-lg" @click="openInvigilatorModal(slot)">
                                    + Staff
                                </Button>
                                <Button v-if="props.canManageExams" size="sm" variant="ghost" class="h-8 w-8 p-0 text-slate-400 hover:text-indigo-600 rounded-lg" @click="router.visit(route('admin.exams.edit', slot.id))">
                                    <Edit3 class="w-3.5 h-3.5" />
                                </Button>
                                <Button v-if="props.canManageExams" size="sm" variant="ghost" class="h-8 w-8 p-0 text-slate-400 hover:text-rose-600 rounded-lg" @click="deleteSchedule(slot.id)">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>

                <div v-else class="p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 space-y-3">
                    <CalendarRange class="w-8 h-8 text-slate-400 mx-auto" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">No Paper Slots Scheduled Yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Create the first timetable paper slot for {{ exam.title }}.</p>
                    <div class="pt-2">
                        <Button v-if="props.canManageExams" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs gap-1.5 rounded-xl h-9 px-4" @click="router.visit(route('admin.exams.create', { exam_id: exam.id }))">
                            <Plus class="w-4 h-4" /> Schedule First Paper Slot
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tab Content 2: Venue Timetable Breakdown -->
            <div v-else-if="activeTab === 'venues'" class="space-y-4">
                <div v-for="venueGroup in groupedByVenue" :key="venueGroup.venueName" class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="font-bold text-base text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <Building2 class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            {{ venueGroup.venueName }}
                            <Badge variant="outline" class="text-xs font-semibold">Capacity: {{ venueGroup.capacity }} Seats</Badge>
                        </h3>
                        <span class="text-xs font-medium text-slate-500">{{ venueGroup.items.length }} Scheduled Paper(s)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-1">
                        <div v-for="item in venueGroup.items" :key="item.id" class="p-3 bg-slate-50/80 dark:bg-slate-950/60 rounded-xl border border-slate-200/60 dark:border-slate-800 space-y-1 text-xs">
                            <strong class="text-slate-900 dark:text-slate-100 font-bold block">{{ item.course?.code }} - {{ item.course?.title }}</strong>
                            <span class="text-slate-500 block text-[11px]">{{ format(new Date(item.exam_date), 'MMM dd, yyyy') }} • {{ item.start_time }} - {{ item.end_time }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content 3: Incidents Log -->
            <div v-else-if="activeTab === 'incidents'" class="space-y-4">
                <div v-if="incidents.length > 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 overflow-hidden shadow-xs">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-950">
                            <TableRow>
                                <TableHead class="text-xs font-semibold">Ref ID</TableHead>
                                <TableHead class="text-xs font-semibold">Student</TableHead>
                                <TableHead class="text-xs font-semibold">Category</TableHead>
                                <TableHead class="text-xs font-semibold">Description</TableHead>
                                <TableHead class="text-xs font-semibold">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="inc in incidents" :key="inc.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-950/50">
                                <TableCell class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ inc.reference_id }}</TableCell>
                                <TableCell class="text-xs">
                                    <span class="font-semibold text-slate-900 dark:text-slate-100 block">{{ inc.student?.user?.name || 'Student' }}</span>
                                    <span class="text-[10px] text-slate-500">{{ inc.student?.matric_number }}</span>
                                </TableCell>
                                <TableCell class="text-xs capitalize font-medium">{{ inc.incident_type }}</TableCell>
                                <TableCell class="text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate">{{ inc.description }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="text-[10px] uppercase font-bold border-rose-200 text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300">{{ inc.status }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-else class="p-10 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 text-xs text-slate-500">
                    No examination incidents or malpractice cases reported for this exercise.
                </div>
            </div>

        </div>

        <!-- Invigilator Assign Modal -->
        <Dialog v-model:open="isInvigilatorModalOpen">
            <DialogContent class="sm:max-w-[450px] rounded-2xl">
                <DialogHeader class="border-b pb-3">
                    <DialogTitle class="flex items-center gap-2 text-slate-900 dark:text-slate-100 font-bold text-base">
                        <UserCheck class="w-4.5 h-4.5 text-indigo-600" /> Assign Staff Invigilator
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2 text-xs">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Select Staff Invigilators *</Label>
                        <SearchableSelect
                            v-model="tempStaffSelect"
                            :items="staffModalOptions"
                            placeholder="Select Invigilator..."
                            trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                            @update:model-value="addInvigilatorStaffPill"
                        />
                        <div v-if="selectedInvigilatorStaffIds.length > 0" class="flex flex-wrap gap-1.5 pt-2">
                            <Badge 
                                v-for="stId in selectedInvigilatorStaffIds" 
                                :key="stId" 
                                class="bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 text-xs flex items-center gap-1 rounded-md px-2 py-0.5"
                            >
                                {{ getStaffLabel(stId) }}
                                <button type="button" @click="removeInvigilatorStaffPill(stId)" class="hover:text-red-600 ml-1">&times;</button>
                            </Badge>
                        </div>
                    </div>
                </div>
                <DialogFooter class="pt-3 border-t gap-2">
                    <Button variant="ghost" class="h-9 text-xs rounded-lg" @click="isInvigilatorModalOpen = false">Cancel</Button>
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold h-9 text-xs rounded-lg px-4" @click="submitInvigilator">Assign Invigilator</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Log Incident Modal -->
        <Dialog v-model:open="isIncidentModalOpen">
            <DialogContent class="sm:max-w-[500px] rounded-2xl">
                <DialogHeader class="border-b pb-3">
                    <DialogTitle class="flex items-center gap-2 text-rose-700 dark:text-rose-400 font-bold text-base">
                        <ShieldAlert class="w-4.5 h-4.5 text-rose-600" /> Log Exam Incident / Malpractice
                    </DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitIncident" class="space-y-3.5 text-xs py-2">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Student Involved *</Label>
                        <SearchableSelect v-model="incidentForm.student_id" :items="studentModalOptions" placeholder="Select Student..." trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800" />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Category</Label>
                        <select v-model="incidentForm.incident_type" class="w-full h-9 text-xs border rounded-lg px-3 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                            <option value="malpractice">Exam Malpractice (Cheating)</option>
                            <option value="contraband">Prohibited Items / Contraband</option>
                            <option value="impersonation">Impersonation</option>
                            <option value="medical">Medical Emergency</option>
                            <option value="other">Other Misconduct</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Incident Description *</Label>
                        <Textarea v-model="incidentForm.description" rows="3" class="text-xs rounded-lg border-slate-200 dark:border-slate-800" required />
                    </div>
                    <DialogFooter class="pt-3 border-t gap-2">
                        <Button type="button" variant="ghost" class="h-9 text-xs rounded-lg" @click="isIncidentModalOpen = false">Cancel</Button>
                        <Button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold h-9 text-xs rounded-lg px-4">Log Incident</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
