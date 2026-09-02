<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { 
    Search, 
    Filter, 
    X,
    Users,
    UserPlus,
    GraduationCap,
    Award,
    Sparkles,
    Upload,
    Download,
    FileSpreadsheet
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { useForm } from '@inertiajs/vue3';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps<{
    students: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        session_id?: string;
        faculty_id?: string;
        department_id?: string;
        level?: string;
        program?: string;
        program_id?: string;
        scholarship_id?: string;
        date_from?: string;
        date_to?: string;
        gender?: string;
        status?: string;
        entry_mode?: string;
        per_page?: string;
    };
    sessions: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string }>;
    departments: Array<{ id: string; name: string; faculty_id: string }>;
    programmes: Array<{ id: string; name: string }>;
    scholarships: Array<{ id: string; name: string }>;
    stats: {
        total: number;
        new: number;
        graduating: number;
    };
    permissions?: {
        can_view?: boolean;
        can_create?: boolean;
        can_edit?: boolean;
        can_delete?: boolean;
        can_import?: boolean;
        can_export?: boolean;
        can_assign_scholarship?: boolean;
        can_toggle_status?: boolean;
    };
}>();

const userPermissions = computed(() => ({
    can_view: props.permissions?.can_view ?? true,
    can_create: props.permissions?.can_create ?? true,
    can_edit: props.permissions?.can_edit ?? true,
    can_delete: props.permissions?.can_delete ?? true,
    can_import: props.permissions?.can_import ?? true,
    can_export: props.permissions?.can_export ?? true,
    can_assign_scholarship: props.permissions?.can_assign_scholarship ?? true,
    can_toggle_status: props.permissions?.can_toggle_status ?? true,
}));

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');
const selectedLevel = ref(props.filters.level || '');
const selectedProgram = ref(props.filters.program_id || '');
const selectedScholarship = ref(props.filters.scholarship_id || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// New filters
const selectedGender = ref(props.filters.gender || '');
const selectedStatus = ref(props.filters.status || '');
const selectedEntryMode = ref(props.filters.entry_mode || '');
const selectedPerPage = ref(props.filters.per_page || '15');

// Sorting states
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

// Interactive Bulk Scholarship Modal States
const showBulkScholarshipModal = ref(false);
const bulkSelectedScholarshipId = ref('');
const studentSearchQuery = ref('');
const searchedStudents = ref<any[]>([]);
const selectedBulkStudents = ref<any[]>([]);
const isSearching = ref(false);

const openBulkModal = () => {
    showBulkScholarshipModal.value = true;
    bulkSelectedScholarshipId.value = '';
    studentSearchQuery.value = '';
    searchedStudents.value = [];
    selectedBulkStudents.value = [];
};

const selectedBulkScholarshipDetails = computed(() => {
    if (!bulkSelectedScholarshipId.value) return null;
    return props.scholarships.find(s => s.id === bulkSelectedScholarshipId.value) || null;
});

const searchStudents = async () => {
    if (!studentSearchQuery.value.trim()) {
        searchedStudents.value = [];
        return;
    }
    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.students.search-bulk'), {
            params: { query: studentSearchQuery.value }
        });
        searchedStudents.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        isSearching.value = false;
    }
};

const selectBulkStudent = (student: any) => {
    if (!selectedBulkStudents.value.some(s => s.id === student.id)) {
        selectedBulkStudents.value.push(student);
    }
    searchedStudents.value = [];
    studentSearchQuery.value = '';
};

const removeBulkStudent = (studentId: string) => {
    selectedBulkStudents.value = selectedBulkStudents.value.filter(s => s.id !== studentId);
};

const confirmBulkScholarship = () => {
    if (selectedBulkStudents.value.length === 0) return;
    router.post(route('admin.students.bulk-assign-scholarship'), {
        student_ids: selectedBulkStudents.value.map(s => s.id),
        scholarship_id: bulkSelectedScholarshipId.value === 'none' ? null : bulkSelectedScholarshipId.value
    }, {
        onSuccess: () => {
            showBulkScholarshipModal.value = false;
            bulkSelectedScholarshipId.value = '';
            selectedBulkStudents.value = [];
        }
    });
};

// Computed departments based on selected faculty
const filteredDepartments = computed(() => {
    if (!selectedFaculty.value) return props.departments;
    return props.departments.filter(dept => dept.faculty_id === selectedFaculty.value);
});

// Sorting handler
const handleSort = (column: string) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
};

// Watchers
const updateFilters = debounce(() => {
    router.get(route('admin.students.index'), {
        search: search.value,
        session_id: selectedSession.value,
        faculty_id: selectedFaculty.value,
        department_id: selectedDepartment.value,
        level: selectedLevel.value,
        program_id: selectedProgram.value,
        scholarship_id: selectedScholarship.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        gender: selectedGender.value,
        status: selectedStatus.value,
        entry_mode: selectedEntryMode.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
        per_page: selectedPerPage.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([
    search, 
    selectedSession, 
    selectedFaculty, 
    selectedDepartment, 
    selectedLevel, 
    selectedProgram, 
    selectedScholarship, 
    dateFrom, 
    dateTo,
    selectedGender,
    selectedStatus,
    selectedEntryMode,
    sortBy,
    sortOrder,
    selectedPerPage
], () => {
     if (selectedFaculty.value && selectedDepartment.value) {
          const dept = props.departments.find(d => d.id === selectedDepartment.value);
          if (dept && dept.faculty_id !== selectedFaculty.value) {
              selectedDepartment.value = '';
          }
     }
     updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedSession.value = '';
    selectedFaculty.value = '';
    selectedDepartment.value = '';
    selectedLevel.value = '';
    selectedProgram.value = '';
    selectedScholarship.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    selectedGender.value = '';
    selectedStatus.value = '';
    selectedEntryMode.value = '';
    sortBy.value = 'created_at';
    sortOrder.value = 'desc';
    selectedPerPage.value = '15';
};

const showImportModal = ref(false);

const importForm = useForm({
    file: null as File | null,
    session_id: '',
    faculty_id: '',
    department_id: '',
    program_id: '',
    level: '',
    scholarship_id: '',
});

const filteredImportDepartments = computed(() => {
    if (!importForm.faculty_id) return props.departments;
    return props.departments.filter(dept => dept.faculty_id === importForm.faculty_id);
});

const filteredImportProgrammes = computed(() => {
    if (!importForm.department_id) return props.programmes;
    return props.programmes.filter(prog => prog.department_id === importForm.department_id);
});

const submitImport = () => {
    importForm.post(route('admin.students.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const handleExport = () => {
    const params = {
        search: search.value,
        session_id: selectedSession.value,
        faculty_id: selectedFaculty.value,
        department_id: selectedDepartment.value,
        level: selectedLevel.value,
        program_id: selectedProgram.value,
        scholarship_id: selectedScholarship.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    };
    
    const queryString = new URLSearchParams(params).toString();
    window.open(route('admin.students.export') + '?' + queryString, '_blank');
};
</script>

<template>
    <Head title="Manage Students" />

    <AdminLayout>
        <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">

            <!-- Header & Stats -->
            <div class="flex flex-col gap-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">Student Management</h1>
                        <p class="text-muted-foreground mt-1">Directory and profiles of all registered students.</p>
                    </div>

                    <div class="flex gap-2">
                        <Dialog v-if="userPermissions.can_import" v-model:open="showImportModal">
                            <DialogTrigger as-child>
                                <Button variant="outline">
                                    <Upload class="w-4 h-4 mr-2" /> Import
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-[650px]">
                                <DialogHeader>
                                    <DialogTitle>Import Students</DialogTitle>
                                    <DialogDescription>
                                        Select the target academic details and upload a student data spreadsheet.
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4 max-h-[80vh] overflow-y-auto pr-2">
                                    <!-- Session Select -->
                                    <div class="flex flex-col gap-2">
                                        <Label for="import_session">Session</Label>
                                        <Select v-model="importForm.session_id">
                                            <SelectTrigger id="import_session">
                                                <SelectValue placeholder="Select Session" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="importForm.errors.session_id" class="text-xs text-destructive">{{ importForm.errors.session_id }}</p>
                                    </div>

                                    <!-- Level Select -->
                                    <div class="flex flex-col gap-2">
                                        <Label for="import_level">Level <span class="text-muted-foreground text-xs">(optional — or specify per row in file)</span></Label>
                                        <Select v-model="importForm.level">
                                            <SelectTrigger id="import_level">
                                                <SelectValue placeholder="Per-row in file" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="100">100 Level</SelectItem>
                                                <SelectItem value="200">200 Level</SelectItem>
                                                <SelectItem value="300">300 Level</SelectItem>
                                                <SelectItem value="400">400 Level</SelectItem>
                                                <SelectItem value="500">500 Level</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="importForm.errors.level" class="text-xs text-destructive">{{ importForm.errors.level }}</p>
                                    </div>

                                    <!-- Faculty Select -->
                                    <div class="flex flex-col gap-2">
                                        <Label for="import_faculty">Faculty <span class="text-muted-foreground text-xs">(optional)</span></Label>
                                        <Select v-model="importForm.faculty_id" @update:model-value="importForm.department_id = ''; importForm.program_id = ''">
                                            <SelectTrigger id="import_faculty">
                                                <SelectValue placeholder="Per-row in file" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="importForm.errors.faculty_id" class="text-xs text-destructive">{{ importForm.errors.faculty_id }}</p>
                                    </div>

                                    <!-- Department Select -->
                                    <div class="flex flex-col gap-2">
                                        <Label for="import_department">Department <span class="text-muted-foreground text-xs">(optional)</span></Label>
                                        <Select v-model="importForm.department_id" :disabled="!importForm.faculty_id" @update:model-value="importForm.program_id = ''">
                                            <SelectTrigger id="import_department">
                                                <SelectValue placeholder="Per-row in file" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="d in filteredImportDepartments" :key="d.id" :value="d.id">{{ d.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="importForm.errors.department_id" class="text-xs text-destructive">{{ importForm.errors.department_id }}</p>
                                    </div>

                                    <!-- Program Select -->
                                    <div class="flex flex-col gap-2">
                                        <Label for="import_program">Programme <span class="text-muted-foreground text-xs">(optional — or specify per row in file)</span></Label>
                                        <Select v-model="importForm.program_id" :disabled="!importForm.department_id">
                                            <SelectTrigger id="import_program">
                                                <SelectValue placeholder="Per-row in file" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="p in filteredImportProgrammes" :key="p.id" :value="p.id">{{ p.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="importForm.errors.program_id" class="text-xs text-destructive">{{ importForm.errors.program_id }}</p>
                                    </div>

                                     <!-- Scholarship Select -->
                                     <div class="flex flex-col gap-2">
                                         <Label for="import_scholarship">Scholarship (Optional)</Label>
                                         <Select v-model="importForm.scholarship_id">
                                             <SelectTrigger id="import_scholarship">
                                                 <SelectValue placeholder="Select Scholarship" />
                                             </SelectTrigger>
                                             <SelectContent>
                                                 <SelectItem value="none">None</SelectItem>
                                                 <SelectItem v-for="s in scholarships" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                                             </SelectContent>
                                         </Select>
                                         <p v-if="importForm.errors.scholarship_id" class="text-xs text-destructive">{{ importForm.errors.scholarship_id }}</p>
                                     </div>

                                    <!-- File Select -->
                                    <div class="flex flex-col gap-2">
                                        <Label for="csv_file">Student Data File</Label>
                                        <Input 
                                            id="csv_file" 
                                            type="file" 
                                            accept=".csv,.xlsx" 
                                            @input="importForm.file = $event.target.files[0]"
                                        />
                                        <p v-if="importForm.errors.file" class="text-xs text-destructive">{{ importForm.errors.file }}</p>
                                    </div>

                                    <div class="bg-muted p-3 rounded-md text-xs space-y-2 col-span-1 md:col-span-2">
                                        <p class="font-bold flex items-center gap-1 text-foreground">
                                            <FileSpreadsheet class="w-3 h-3 text-primary" /> Excel Format Requirements:
                                        </p>
                                        <ul class="list-disc list-inside space-y-1 text-muted-foreground font-medium">
                                            <li>Required columns: <strong class="text-foreground">first_name, last_name, email</strong></li>
                                            <li>Optional columns: phone_number, gender, dob, address, state, lga, entry_mode, matric_number, jamb_reg, jamb_score, previous_institution, <strong class="text-foreground">scholarship</strong> (name match)</li>
                                            <li>Per-row columns (if not selected above): <strong class="text-foreground">programme</strong> (name match), <strong class="text-foreground">level</strong> (100–500), <strong class="text-foreground">scholarship</strong></li>
                                            <li>If Programme/Level/Scholarship are selected above, they apply to <em>all rows</em> and override the file columns</li>
                                        </ul>
                                        <div class="pt-2">
                                            <a 
                                                :href="route('admin.students.template')" 
                                                class="text-primary hover:underline inline-flex items-center gap-1 font-semibold"
                                                target="_blank"
                                            >
                                                <Download class="w-3 h-3" /> Download Excel Template
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button 
                                        type="submit" 
                                        @click="submitImport" 
                                        :disabled="importForm.processing || !importForm.file || !importForm.session_id"
                                        class="w-full"
                                    >
                                        {{ importForm.processing ? 'Importing...' : 'Start Import' }}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        <Button v-if="userPermissions.can_export" variant="outline" @click="handleExport" class="border-green-600/30 text-green-600 hover:bg-green-600/10 hover:text-green-700">
                            <FileSpreadsheet class="w-4 h-4 mr-2" /> Export
                        </Button>

                        <Button v-if="userPermissions.can_assign_scholarship" variant="outline" @click="openBulkModal" class="border-primary/30 text-primary hover:bg-primary/10">
                            <Award class="w-4 h-4 mr-2" /> Assign Scholarship
                        </Button>

                        <Button v-if="userPermissions.can_create" as-child shadow="md">
                            <Link :href="route('admin.students.create')">
                                <UserPlus class="w-4 h-4 mr-2" /> Add Student
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <Card class="bg-primary/5 border-primary/20 shadow-none">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
                            <Users class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total }}</div>
                            <p class="text-xs text-muted-foreground">Registered on platform</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">New Intake</CardTitle>
                            <Sparkles class="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.new }}</div>
                            <p class="text-xs text-muted-foreground">Admitted in current session</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Graduating Class</CardTitle>
                            <GraduationCap class="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.graduating }}</div>
                            <p class="text-xs text-muted-foreground">Final year students (400L+)</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
             <div class="flex flex-col lg:flex-row gap-4 items-end lg:items-center justify-between">
                <div class="flex flex-col gap-3 w-full lg:w-auto flex-1">
                    <div class="flex flex-col sm:flex-row gap-3">
                         <div class="relative w-full sm:w-[300px]">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                              type="search"
                              placeholder="Search name, matric, email..."
                              class="pl-8"
                              v-model="search"
                            />
                          </div>

                        <!-- Session -->
                        <Select v-model="selectedSession">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="Session" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_SESSIONS">Any Session</SelectItem>
                                <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Level -->
                         <Select v-model="selectedLevel">
                            <SelectTrigger class="w-[140px]">
                                <SelectValue placeholder="Level" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_LEVELS">All Levels</SelectItem>
                                <SelectItem value="100">100 Level</SelectItem>
                                <SelectItem value="200">200 Level</SelectItem>
                                <SelectItem value="300">300 Level</SelectItem>
                                <SelectItem value="400">400 Level</SelectItem>
                                <SelectItem value="500">500 Level</SelectItem>
                                <SelectItem value="600">600 Level</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                         <!-- Faculty -->
                        <Select v-model="selectedFaculty">
                            <SelectTrigger class="w-[200px]">
                                <SelectValue placeholder="Faculty" />
                            </SelectTrigger>
                             <SelectContent>
                                <SelectItem value="ALL_FACULTIES">All Faculties</SelectItem>
                                <SelectItem v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Department -->
                        <Select v-model="selectedDepartment" :disabled="!selectedFaculty">
                            <SelectTrigger class="w-[220px]">
                                <SelectValue placeholder="Department" />
                            </SelectTrigger>
                             <SelectContent>
                                <SelectItem value="ALL_DEPARTMENTS">All Departments</SelectItem>
                                <SelectItem v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                         <!-- Program Dropdown -->
                         <Select v-model="selectedProgram">
                            <SelectTrigger class="w-[220px]">
                                <SelectValue placeholder="Program" />
                            </SelectTrigger>
                             <SelectContent>
                                <SelectItem value="ALL_PROGRAMS">All Programs</SelectItem>
                                <SelectItem v-for="p in programmes" :key="p.id" :value="p.id">{{ p.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <!-- Scholarship -->
                        <Select v-model="selectedScholarship">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="Scholarship" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_SCHOLARSHIPS">All Students</SelectItem>
                                <SelectItem value="NONE">No Scholarship</SelectItem>
                                <SelectItem v-for="s in scholarships" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Date Range -->
                        <div class="flex items-center gap-2">
                            <Input type="date" v-model="dateFrom" class="w-[150px]" title="Admitted From" />
                            <span class="text-muted-foreground">to</span>
                            <Input type="date" v-model="dateTo" class="w-[150px]" title="Admitted To" />
                        </div>
                    </div>

                    <!-- New Filters (Gender, Status, Entry Mode) -->
                    <div class="flex flex-col sm:flex-row gap-3 flex-wrap border-t pt-3 mt-1">
                        <!-- Gender -->
                        <Select v-model="selectedGender">
                            <SelectTrigger class="w-[150px]">
                                <SelectValue placeholder="Filter Gender" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_GENDERS">All Genders</SelectItem>
                                <SelectItem value="male">Male</SelectItem>
                                <SelectItem value="female">Female</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Status -->
                        <Select v-model="selectedStatus">
                            <SelectTrigger class="w-[150px]">
                                <SelectValue placeholder="Filter Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_STATUS">All Status</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="deactivated">Deactivated</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Entry Mode -->
                        <Select v-model="selectedEntryMode">
                            <SelectTrigger class="w-[170px]">
                                <SelectValue placeholder="Filter Entry Mode" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_MODES">All Entry Modes</SelectItem>
                                <SelectItem value="UTME">UTME</SelectItem>
                                <SelectItem value="DE">Direct Entry (DE)</SelectItem>
                                <SelectItem value="PG">Postgraduate (PG)</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Per Page -->
                        <Select v-model="selectedPerPage">
                            <SelectTrigger class="w-[120px]">
                                <SelectValue placeholder="Per Page" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10 per page</SelectItem>
                                <SelectItem value="15">15 per page</SelectItem>
                                <SelectItem value="25">25 per page</SelectItem>
                                <SelectItem value="50">50 per page</SelectItem>
                                <SelectItem value="100">100 per page</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <Button 
                    v-if="search || selectedSession || selectedFaculty || selectedDepartment || selectedLevel || selectedProgram || selectedScholarship || dateFrom || dateTo || selectedGender || selectedStatus || selectedEntryMode || sortBy !== 'created_at' || sortOrder !== 'desc'" 
                    variant="ghost" 
                    @click="clearFilters"
                    class="text-destructive hover:text-destructive hover:bg-destructive/10"
                >
                    <X class="w-4 h-4 mr-2" />
                    Clear Filters
                </Button>
            </div>

            <!-- Table -->
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="cursor-pointer hover:bg-muted/50 select-none" @click="handleSort('name')">
                                Student
                                <span class="ml-1 text-[10px]">{{ sortBy === 'name' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}</span>
                            </TableHead>
                            <TableHead>Department / Faculty</TableHead>
                            <TableHead>Session</TableHead>
                            <TableHead class="cursor-pointer hover:bg-muted/50 select-none" @click="handleSort('level')">
                                Level & Program
                                <span class="ml-1 text-[10px]">{{ sortBy === 'level' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}</span>
                            </TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="student in students.data" :key="student.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                   <Avatar class="h-10 w-10 border-2 border-background">
                                        <AvatarImage :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : ''" />
                                        <AvatarFallback>{{ student.user.name.charAt(0) }}</AvatarFallback>
                                   </Avatar>
                                   <div>
                                       <div class="font-medium">{{ student.user.name }}</div>
                                       <div class="text-xs text-muted-foreground font-mono">{{ student.matriculation_number || 'NO MATRIC' }}</div>
                                       <div class="text-[10px] text-muted-foreground">{{ student.user.email }}</div>
                                   </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm font-medium">{{ student.academic_department?.name || 'N/A' }}</div>
                                <div class="text-xs text-muted-foreground">{{ student.academic_department?.faculty?.name || 'N/A' }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ student.admitted_session?.name || 'N/A' }}</Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col gap-1 items-start">
                                    <Badge variant="secondary">{{ student.current_level }}</Badge>
                                    <span class="text-xs text-muted-foreground line-clamp-1 truncate max-w-[150px]" :title="student.program?.name">{{ student.program?.name || 'N/A' }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :class="student.user.is_active ? 'bg-green-150 text-green-700 hover:bg-green-200 border-green-200' : 'bg-red-150 text-red-700 hover:bg-red-200 border-red-200'" variant="outline">
                                    {{ student.user.is_active ? 'Active' : 'Deactivated' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button v-if="userPermissions.can_view" variant="outline" size="sm" as-child>
                                        <Link :href="route('admin.students.show', student.id)">
                                            View
                                        </Link>
                                    </Button>
                                    <Button v-if="userPermissions.can_edit" variant="secondary" size="sm" as-child>
                                        <Link :href="route('admin.students.edit', student.id)">
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button 
                                        v-if="userPermissions.can_toggle_status"
                                        :variant="student.user.is_active ? 'destructive' : 'default'" 
                                        size="sm"
                                        @click="() => router.put(route('admin.students.toggle_status', student.id))"
                                    >
                                        {{ student.user.is_active ? 'Deactivate' : 'Activate' }}
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                         <TableRow v-if="students.data.length === 0">
                            <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                No students found matching your criteria.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                 <!-- Pagination -->
                <CardFooter class="flex items-center justify-between border-t p-4" v-if="students.total > 0">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ students.from }}</strong>-<strong>{{ students.to }}</strong> of <strong>{{ students.total }}</strong>
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in students.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>

        <!-- Bulk Scholarship Assignment Modal -->
        <Dialog v-model:open="showBulkScholarshipModal">
            <DialogContent class="sm:max-w-[850px] p-0 overflow-hidden">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle class="text-xl">Bulk Scholarship Assignment</DialogTitle>
                    <DialogDescription>
                        Select a scholarship, search for students by registration number, and confirm their assignment.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x border-t mt-4">
                    <!-- Left Side: Scholarship Selection & Details -->
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <Label for="bulk_scholarship" class="text-sm font-semibold">1. Choose Scholarship</Label>
                            <Select v-model="bulkSelectedScholarshipId">
                                <SelectTrigger id="bulk_scholarship">
                                    <SelectValue placeholder="Choose Scholarship" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Remove Scholarship (Set to None)</SelectItem>
                                    <SelectItem v-for="s in scholarships" :key="s.id" :value="s.id">
                                        {{ s.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Scholarship Details Box -->
                        <div class="space-y-2">
                            <Label class="text-sm font-semibold">Scholarship Details</Label>
                            <div v-if="selectedBulkScholarshipDetails" class="bg-primary/5 p-4 rounded-lg border border-primary/10 text-xs space-y-2.5">
                                <div class="font-bold text-foreground text-sm border-b pb-1 mb-1">{{ selectedBulkScholarshipDetails.name }}</div>
                                <div class="grid grid-cols-2 gap-y-2 text-sm">
                                    <span class="text-muted-foreground">Type:</span>
                                    <span class="font-semibold capitalize text-foreground">{{ selectedBulkScholarshipDetails.type || 'N/A' }}</span>
                                    
                                    <span class="text-muted-foreground">Value:</span>
                                    <span class="font-semibold text-foreground">
                                        {{ selectedBulkScholarshipDetails.type === 'percentage' 
                                           ? selectedBulkScholarshipDetails.percentage + '%' 
                                           : '₦' + Number(selectedBulkScholarshipDetails.amount || 0).toLocaleString() }}
                                    </span>
                                    
                                    <span class="text-muted-foreground">Covers Admin Charges:</span>
                                    <span class="font-semibold text-foreground">{{ selectedBulkScholarshipDetails.covers_admin_charges ? 'Yes' : 'No' }}</span>
                                    
                                    <span class="text-muted-foreground">Covers Hostel Fees:</span>
                                    <span class="font-semibold text-foreground">{{ selectedBulkScholarshipDetails.covers_hostel_fees ? 'Yes' : 'No' }}</span>
                                    
                                    <span class="text-muted-foreground">Status:</span>
                                    <span :class="selectedBulkScholarshipDetails.is_active ? 'text-green-600 font-bold' : 'text-red-600 font-bold'">
                                        {{ selectedBulkScholarshipDetails.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center p-6 border border-dashed rounded-md text-xs text-muted-foreground bg-muted/20">
                                Please select a scholarship to view details.
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Search and Selected Students -->
                    <div class="p-6 space-y-4">
                        <!-- Search Student Section -->
                        <div class="space-y-2 relative">
                            <Label for="student_search" class="text-sm font-semibold">2. Search & Select Students</Label>
                            <div class="flex gap-2">
                                <Input 
                                    id="student_search"
                                    v-model="studentSearchQuery"
                                    placeholder="Type student name or reg number..."
                                    @keyup.enter="searchStudents"
                                />
                                <Button type="button" variant="secondary" @click="searchStudents" :disabled="isSearching">
                                    {{ isSearching ? 'Searching...' : 'Search' }}
                                </Button>
                            </div>

                            <!-- Search Results Dropdown -->
                            <div v-if="searchedStudents.length > 0" class="absolute z-50 left-0 right-0 mt-1 bg-popover text-popover-foreground border rounded-md shadow-md max-h-[160px] overflow-y-auto">
                                <div 
                                    v-for="student in searchedStudents" 
                                    :key="student.id"
                                    @click="selectBulkStudent(student)"
                                    class="p-2 hover:bg-muted cursor-pointer border-b last:border-0 text-xs flex flex-col gap-0.5"
                                >
                                    <span class="font-semibold text-foreground">{{ student.name }}</span>
                                    <span class="text-[10px] text-muted-foreground font-mono">{{ student.matriculation_number || 'No Matric' }} - {{ student.email }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Students List -->
                        <div class="space-y-2">
                            <Label class="text-sm font-semibold">Selected Students ({{ selectedBulkStudents.length }})</Label>
                            <div v-if="selectedBulkStudents.length > 0" class="border rounded-md divide-y max-h-[180px] overflow-y-auto bg-background shadow-inner">
                                <div 
                                    v-for="student in selectedBulkStudents" 
                                    :key="student.id"
                                    class="p-2 flex justify-between items-center text-xs"
                                >
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-semibold text-foreground">{{ student.name }}</span>
                                        <span class="text-[10px] text-muted-foreground font-mono">{{ student.matriculation_number || 'No Matric' }}</span>
                                    </div>
                                    <Button 
                                        type="button" 
                                        variant="ghost" 
                                        size="icon" 
                                        class="h-6 w-6 text-destructive hover:bg-destructive/10" 
                                        @click="removeBulkStudent(student.id)"
                                    >
                                        <X class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <div v-else class="text-center p-6 border border-dashed rounded-md text-xs text-muted-foreground bg-muted/20">
                                No students selected yet. Search and select above.
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="p-6 border-t bg-muted/20 flex items-center justify-between">
                    <Button 
                        type="button" 
                        variant="outline" 
                        @click="showBulkScholarshipModal = false"
                    >
                        Cancel
                    </Button>
                    <Button 
                        type="button" 
                        @click="confirmBulkScholarship" 
                        :disabled="selectedBulkStudents.length === 0 || !bulkSelectedScholarshipId"
                    >
                        Confirm Assignment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
