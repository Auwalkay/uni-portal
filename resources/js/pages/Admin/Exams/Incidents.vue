<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { 
    ShieldAlert, Search, Edit3, Filter, AlertOctagon, CheckCircle2, Clock, AlertTriangle, ArrowLeft, RefreshCw, Calendar, Building, Plus
} from 'lucide-vue-next';
import { format } from 'date-fns';
import SearchableSelect from '@/components/SearchableSelect.vue';

interface OptionItem {
    id: string;
    label: string;
}

interface Props {
    incidents: {
        data: any[];
        links: any[];
        total: number;
    };
    sessions: any[];
    semesters: any[];
    departments: any[];
    examOptions?: OptionItem[];
    studentOptions?: OptionItem[];
    staffOptions?: OptionItem[];
    stats: {
        total: number;
        logged: number;
        under_investigation: number;
        resolved: number;
        sanctioned: number;
    };
    filters: {
        session_id?: string;
        semester_id?: string;
        incident_type?: string;
        status?: string;
        search?: string;
    };
    canManageExams?: boolean;
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const selectedSessionId = ref(props.filters.session_id || '');
const selectedSemesterId = ref(props.filters.semester_id || '');
const selectedIncidentType = ref(props.filters.incident_type || '');
const selectedStatus = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('admin.exams.incidents.index'), {
        session_id: selectedSessionId.value || undefined,
        semester_id: selectedSemesterId.value || undefined,
        incident_type: selectedIncidentType.value || undefined,
        status: selectedStatus.value || undefined,
        search: search.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedSessionId.value = '';
    selectedSemesterId.value = '';
    selectedIncidentType.value = '';
    selectedStatus.value = '';
    applyFilters();
};

const sessionOptions = computed(() => [
    { value: '', label: 'All Sessions' },
    ...(props.sessions || []).map(s => ({ value: s.id, label: s.name }))
]);

const semesterOptions = computed(() => [
    { value: '', label: 'All Semesters' },
    ...(props.semesters || []).map(s => ({ value: s.id, label: s.name }))
]);

const incidentTypeOptions = [
    { value: '', label: 'All Categories' },
    { value: 'malpractice', label: 'Exam Malpractice' },
    { value: 'contraband', label: 'Prohibited Items / Contraband' },
    { value: 'impersonation', label: 'Impersonation' },
    { value: 'medical', label: 'Medical Emergency' },
    { value: 'absenteeism', label: 'Unauthorized Absence' },
    { value: 'other', label: 'Other Infraction' },
];

const incidentFormTypeOptions = [
    { value: 'malpractice', label: 'Exam Malpractice' },
    { value: 'contraband', label: 'Prohibited Items / Contraband' },
    { value: 'impersonation', label: 'Impersonation' },
    { value: 'medical', label: 'Medical Emergency' },
    { value: 'absenteeism', label: 'Unauthorized Absence' },
    { value: 'other', label: 'Other Infraction' },
];

const incidentStatusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'logged', label: 'Logged / New' },
    { value: 'under_investigation', label: 'Under Investigation' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'sanctioned', label: 'Sanctioned / Penalized' },
];

const incidentModalStatusOptions = [
    { value: 'logged', label: 'Logged / New' },
    { value: 'under_investigation', label: 'Under Investigation' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'sanctioned', label: 'Sanctioned / Penalized' },
];

// Create / Register Incident Modal
const isCreateModalOpen = ref(false);
const createForm = useForm({
    exam_schedule_id: '',
    student_id: '',
    invigilator_id: '',
    incident_type: 'malpractice',
    description: '',
    status: 'logged',
    action_taken: '',
});

const formattedExamOptions = computed(() => [
    { value: '', label: 'Select Exam / Course' },
    ...(props.examOptions || []).map(e => ({ value: e.id, label: e.label }))
]);

const formattedStudentOptions = computed(() => [
    { value: '', label: 'Select Student (Matric / Name)' },
    ...(props.studentOptions || []).map(s => ({ value: s.id, label: s.label }))
]);

const formattedStaffOptions = computed(() => [
    { value: '', label: 'Select Reporting Staff (Optional)' },
    ...(props.staffOptions || []).map(st => ({ value: st.id, label: st.label }))
]);

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.exam_schedule_id = '';
    createForm.student_id = '';
    createForm.invigilator_id = '';
    createForm.incident_type = 'malpractice';
    createForm.description = '';
    createForm.status = 'logged';
    createForm.action_taken = '';
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    createForm.post(route('admin.exams.incidents.general_store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
};

// Edit / Resolve Incident Dialog
const isEditModalOpen = ref(false);
const selectedIncident = ref<any>(null);
const editForm = useForm({
    status: 'logged',
    action_taken: '',
});

const openEditModal = (incident: any) => {
    selectedIncident.value = incident;
    editForm.status = incident.status || 'logged';
    editForm.action_taken = incident.action_taken || '';
    isEditModalOpen.value = true;
};

const submitUpdate = () => {
    if (!selectedIncident.value) return;
    editForm.put(route('admin.exams.incidents.update', selectedIncident.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Reported Exam Incidents Registry" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Top Header & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500" @click="router.visit(route('admin.exams.index'))">
                            <ArrowLeft class="w-4 h-4" />
                        </Button>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <ShieldAlert class="w-6 h-6 text-rose-600" /> Exam Incidents & Malpractice Registry
                        </h1>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 pl-10">
                        Central database of reported examination infractions, malpractice cases, and disciplinary action records.
                    </p>
                </div>

                <div class="flex items-center gap-2 pl-10 md:pl-0">
                    <Button class="bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs h-9 gap-1.5 shadow-xs" @click="openCreateModal">
                        <Plus class="w-4 h-4" /> Register Incident
                    </Button>
                    <Button variant="outline" class="text-xs h-9 font-semibold gap-1.5" @click="router.visit(route('admin.exams.index'))">
                        <Calendar class="w-4 h-4 text-purple-600" /> Return to Timetable
                    </Button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card class="border shadow-xs bg-white dark:bg-slate-900">
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Incidents</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-0.5">{{ stats.total }}</p>
                        </div>
                        <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 rounded-xl text-rose-600">
                            <ShieldAlert class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-xs bg-white dark:bg-slate-900">
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Logged / New</p>
                            <p class="text-2xl font-black text-slate-700 dark:text-slate-200 mt-0.5">{{ stats.logged }}</p>
                        </div>
                        <div class="p-2.5 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600">
                            <Clock class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-xs bg-white dark:bg-slate-900">
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Investigating</p>
                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{{ stats.under_investigation }}</p>
                        </div>
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-amber-600">
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-xs bg-white dark:bg-slate-900">
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Resolved</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ stats.resolved }}</p>
                        </div>
                        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-emerald-600">
                            <CheckCircle2 class="w-5 h-5" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-xs bg-white dark:bg-slate-900">
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-red-600">Sanctioned</p>
                            <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-0.5">{{ stats.sanctioned }}</p>
                        </div>
                        <div class="p-2.5 bg-red-50 dark:bg-red-950/40 rounded-xl text-red-600">
                            <AlertOctagon class="w-5 h-5" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Filters Bar -->
            <Card class="border shadow-xs bg-white dark:bg-slate-900 p-4 rounded-2xl">
                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-2.5">
                        <!-- Search Box -->
                        <div class="relative sm:col-span-2">
                            <Search class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                            <Input 
                                v-model="search" 
                                placeholder="Search by student, matric, course, ref..." 
                                class="pl-9 h-9 text-xs" 
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Session Filter -->
                        <div>
                            <SearchableSelect
                                v-model="selectedSessionId"
                                :items="sessionOptions"
                                placeholder="Select Session"
                                search-placeholder="Search sessions..."
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Semester Filter -->
                        <div>
                            <SearchableSelect
                                v-model="selectedSemesterId"
                                :items="semesterOptions"
                                placeholder="Select Semester"
                                search-placeholder="Search semesters..."
                                @update:model-value="applyFilters"
                            />
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <SearchableSelect
                                v-model="selectedIncidentType"
                                :items="incidentTypeOptions"
                                placeholder="All Categories"
                                search-placeholder="Search category..."
                                @update:model-value="applyFilters"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 border-t text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-500">Status:</span>
                            <SearchableSelect
                                v-model="selectedStatus"
                                :items="incidentStatusOptions"
                                placeholder="All Statuses"
                                search-placeholder="Search status..."
                                @update:model-value="applyFilters"
                                class="w-44"
                            />
                        </div>

                        <Button variant="ghost" size="sm" class="h-7 text-slate-500 hover:text-slate-900 text-xs" @click="resetFilters">
                            <RefreshCw class="w-3.5 h-3.5 mr-1" /> Reset Filters
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Table of Reported Incidents -->
            <Card class="border shadow-xs bg-white dark:bg-slate-900 rounded-2xl overflow-hidden">
                <Table>
                    <TableHeader class="bg-slate-50 dark:bg-slate-800/60">
                        <TableRow>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500">Ref / Date</TableHead>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500">Student</TableHead>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500">Course / Exam</TableHead>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500">Infraction Category</TableHead>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500">Description</TableHead>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</TableHead>
                            <TableHead class="text-xs font-bold uppercase tracking-wider text-slate-500 text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="inc in incidents.data" :key="inc.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <TableCell class="font-mono text-xs">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ inc.reference_id }}</span>
                                <span class="text-[11px] text-slate-400">{{ inc.created_at ? format(new Date(inc.created_at), 'MMM dd, yyyy HH:mm') : '-' }}</span>
                            </TableCell>

                            <TableCell class="text-xs">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block">
                                    {{ inc.student?.user?.name || 'Unknown Student' }}
                                </span>
                                <span class="text-[11px] text-slate-500 font-mono">
                                    {{ inc.student?.matriculation_number || inc.student?.matric_number || 'No Matric' }}
                                </span>
                            </TableCell>

                            <TableCell class="text-xs">
                                <span class="font-bold text-purple-700 dark:text-purple-400 block">
                                    {{ inc.schedule?.course?.code }} - {{ inc.schedule?.course?.title }}
                                </span>
                                <span class="text-[11px] text-slate-400 block">
                                    Venue: {{ inc.schedule?.venue || 'Unassigned' }}
                                </span>
                            </TableCell>

                            <TableCell class="text-xs">
                                <Badge variant="outline" class="font-semibold text-[11px] uppercase tracking-wider" :class="{
                                    'bg-rose-50 text-rose-700 border-rose-200': inc.incident_type === 'malpractice',
                                    'bg-amber-50 text-amber-700 border-amber-200': inc.incident_type === 'contraband',
                                    'bg-purple-50 text-purple-700 border-purple-200': inc.incident_type === 'impersonation',
                                    'bg-blue-50 text-blue-700 border-blue-200': inc.incident_type === 'medical',
                                    'bg-slate-100 text-slate-700 border-slate-200': inc.incident_type === 'absenteeism' || inc.incident_type === 'other'
                                }">
                                    {{ inc.incident_type }}
                                </Badge>
                            </TableCell>

                            <TableCell class="text-xs max-w-xs">
                                <p class="line-clamp-2 text-slate-700 dark:text-slate-300">{{ inc.description }}</p>
                                <span v-if="inc.action_taken" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium block mt-1">
                                    Action: {{ inc.action_taken }}
                                </span>
                            </TableCell>

                            <TableCell class="text-xs">
                                <Badge class="capitalize font-semibold text-[11px]" :class="{
                                    'bg-slate-200 text-slate-800': inc.status === 'logged',
                                    'bg-amber-500 text-white': inc.status === 'under_investigation',
                                    'bg-emerald-600 text-white': inc.status === 'resolved',
                                    'bg-red-600 text-white': inc.status === 'sanctioned'
                                }">
                                    {{ inc.status?.replace('_', ' ') }}
                                </Badge>
                            </TableCell>

                            <TableCell class="text-right">
                                <Button size="sm" variant="outline" class="h-7 text-xs border-indigo-200 text-indigo-700 hover:bg-indigo-50" @click="openEditModal(inc)">
                                    <Edit3 class="w-3 h-3 mr-1" /> Resolve / Edit
                                </Button>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="incidents.data.length === 0">
                            <TableCell colspan="7" class="h-32 text-center text-slate-400">
                                No exam incidents reported matching criteria.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <!-- Register / Log New Incident Modal -->
            <Dialog v-model:open="isCreateModalOpen">
                <DialogContent class="sm:max-w-[550px]">
                    <DialogHeader class="border-b pb-4">
                        <DialogTitle class="flex items-center gap-2 text-rose-600">
                            <ShieldAlert class="w-5 h-5" /> Register New Examination Incident
                        </DialogTitle>
                        <DialogDescription class="text-xs text-slate-500">
                            Record a new malpractice, contraband, impersonation or exam emergency case in the registry.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitCreate" class="space-y-4 py-3 text-xs">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Exam / Course Schedule *</Label>
                            <SearchableSelect
                                v-model="createForm.exam_schedule_id"
                                :items="formattedExamOptions"
                                placeholder="Select Exam / Course"
                                search-placeholder="Search exam by course code or title..."
                            />
                            <p v-if="createForm.errors.exam_schedule_id" class="text-red-500 text-[11px]">{{ createForm.errors.exam_schedule_id }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Student Involved *</Label>
                            <SearchableSelect
                                v-model="createForm.student_id"
                                :items="formattedStudentOptions"
                                placeholder="Select Student (Matric / Name)"
                                search-placeholder="Search student by matric or name..."
                            />
                            <p v-if="createForm.errors.student_id" class="text-red-500 text-[11px]">{{ createForm.errors.student_id }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Infraction Category *</Label>
                                <SearchableSelect
                                    v-model="createForm.incident_type"
                                    :items="incidentFormTypeOptions"
                                    placeholder="Select Category"
                                    search-placeholder="Search category..."
                                />
                                <p v-if="createForm.errors.incident_type" class="text-red-500 text-[11px]">{{ createForm.errors.incident_type }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Initial Status *</Label>
                                <SearchableSelect
                                    v-model="createForm.status"
                                    :items="incidentModalStatusOptions"
                                    placeholder="Select Status"
                                    search-placeholder="Search status..."
                                />
                                <p v-if="createForm.errors.status" class="text-red-500 text-[11px]">{{ createForm.errors.status }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Reporting Invigilator / Staff (Optional)</Label>
                            <SearchableSelect
                                v-model="createForm.invigilator_id"
                                :items="formattedStaffOptions"
                                placeholder="Select Reporting Staff"
                                search-placeholder="Search staff by name or ID..."
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Incident Description *</Label>
                            <Textarea
                                v-model="createForm.description"
                                rows="3"
                                placeholder="Detailed description of what occurred, evidence confiscated, or witnesses present..."
                                class="text-xs"
                            />
                            <p v-if="createForm.errors.description" class="text-red-500 text-[11px]">{{ createForm.errors.description }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Action Taken / Remarks (Optional)</Label>
                            <Textarea
                                v-model="createForm.action_taken"
                                rows="2"
                                placeholder="Immediate action taken on site (e.g. answer booklet confiscated, student escorted out, medical aid called)..."
                                class="text-xs"
                            />
                        </div>

                        <DialogFooter class="border-t pt-3">
                            <Button type="button" variant="ghost" @click="isCreateModalOpen = false">Cancel</Button>
                            <Button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold" :disabled="createForm.processing">
                                Submit Incident Record
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Update / Resolve Incident Modal -->
            <Dialog v-model:open="isEditModalOpen">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader class="border-b pb-4">
                        <DialogTitle class="flex items-center gap-2 text-rose-600">
                            <ShieldAlert class="w-5 h-5" /> Update Disciplinary Incident Status
                        </DialogTitle>
                        <DialogDescription class="text-xs font-mono text-slate-500">
                            Ref: {{ selectedIncident?.reference_id }} | Student: {{ selectedIncident?.student?.user?.name }} ({{ selectedIncident?.student?.matric_number || selectedIncident?.student?.matriculation_number }})
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitUpdate" class="space-y-4 py-3 text-xs">
                        <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg border space-y-1">
                            <span class="font-bold text-slate-700 dark:text-slate-300 block">Incident Description</span>
                            <p class="text-slate-600 dark:text-slate-400 italic text-[11px]">{{ selectedIncident?.description }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Investigation Status</Label>
                            <SearchableSelect
                                v-model="editForm.status"
                                :items="incidentModalStatusOptions"
                                placeholder="Select Status"
                                search-placeholder="Search status..."
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Action Taken / Disciplinary Penalty</Label>
                            <Textarea 
                                v-model="editForm.action_taken" 
                                rows="3" 
                                placeholder="Specify penalty or disciplinary outcome (e.g., Paper cancelled, zero mark awarded, referred to Senate)..." 
                                class="text-xs" 
                            />
                        </div>

                        <DialogFooter class="border-t pt-3">
                            <Button type="button" variant="ghost" @click="isEditModalOpen = false">Cancel</Button>
                            <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white" :disabled="editForm.processing">
                                Save Disciplinary Update
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
