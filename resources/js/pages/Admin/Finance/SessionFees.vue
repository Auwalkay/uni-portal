<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import SearchableSelect from '@/components/SearchableSelect.vue';
import {
    Plus,
    Edit,
    Trash2,
    ArrowLeft,
    RefreshCw,
    Search,
    Filter,
    X,
    GraduationCap,
    Layers,
    Building2,
    BookOpen,
    Info,
    Sparkles
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { format } from 'date-fns';

interface FeeType {
    id: number;
    name: string;
    slug: string;
    description?: string;
    is_one_time?: boolean;
}

interface Faculty {
    id: string | number;
    name: string;
    code?: string;
}

interface Department {
    id: string | number;
    faculty_id: string | number;
    name: string;
    code?: string;
}

interface Program {
    id: string | number;
    department_id: string | number;
    name: string;
    code?: string;
    type?: string;
    academic_stream?: string;
}

interface FeeConfiguration {
    id: number;
    fee_type_id: number;
    fee_type: FeeType;
    amount: number;
    faculty_id?: string | number | null;
    faculty?: Faculty;
    department_id?: string | number | null;
    department?: Department & { faculty?: Faculty };
    program_id?: string | number | null;
    program?: Program & { department?: Department & { faculty?: Faculty } };
    level?: string | null;
    entry_mode?: string | null;
    is_compulsory: boolean;
    is_milestone_fee?: boolean;
    milestone_type?: string | null;
}

const milestoneTypeLabels: Record<string, string> = {
    bench_fee: 'Research Bench / Lab Fee',
    practicum_fee: 'Practicum / Attachment Fee',
    defense_fee: 'External Viva Voce Defense Fee',
    extension_fee: 'Programme Extension Fee',
};

const props = defineProps<{
    session: any;
    feeTypes: FeeType[];
    faculties: Faculty[];
    departments: Department[];
    programs: Program[];
}>();

// Map lookups for fast cascading & resolution
const facultyById = computed(() => {
    const map = new Map<string, Faculty>();
    props.faculties.forEach(f => map.set(String(f.id), f));
    return map;
});

const departmentById = computed(() => {
    const map = new Map<string, Department>();
    props.departments.forEach(d => map.set(String(d.id), d));
    return map;
});

const programById = computed(() => {
    const map = new Map<string, Program>();
    props.programs.forEach(p => map.set(String(p.id), p));
    return map;
});

// ==========================================
// TABLE FILTERS STATE
// ==========================================
const searchFilter = ref('');
const streamFilter = ref('all'); // 'all', 'UG', 'PG'
const filterFacultyId = ref('all');
const filterDepartmentId = ref('all');
const filterProgramId = ref('all');
const filterFeeTypeId = ref('all');

// Cascading Filter: Filter Department options based on selected Filter Faculty
const filterDepartmentOptions = computed(() => {
    let list = props.departments;
    if (filterFacultyId.value && filterFacultyId.value !== 'all') {
        list = list.filter(d => String(d.faculty_id) === String(filterFacultyId.value));
    }
    return [
        { value: 'all', label: 'All Departments' },
        ...list.map(d => ({ value: String(d.id), label: d.name }))
    ];
});

// Cascading Filter: Filter Program options based on selected Filter Department or Faculty
const filterProgramOptions = computed(() => {
    let list = props.programs;

    if (streamFilter.value === 'UG') {
        list = list.filter(p => (p.academic_stream === 'UG' || p.type === 'UG'));
    } else if (streamFilter.value === 'PG') {
        list = list.filter(p => (p.academic_stream === 'PG' || ['PGD', 'MSC', 'PHD'].includes(p.type || '')));
    }

    if (filterDepartmentId.value && filterDepartmentId.value !== 'all') {
        list = list.filter(p => String(p.department_id) === String(filterDepartmentId.value));
    } else if (filterFacultyId.value && filterFacultyId.value !== 'all') {
        const allowedDeptIds = new Set(
            props.departments
                .filter(d => String(d.faculty_id) === String(filterFacultyId.value))
                .map(d => String(d.id))
        );
        list = list.filter(p => allowedDeptIds.has(String(p.department_id)));
    }

    return [
        { value: 'all', label: 'All Programmes' },
        ...list.map(p => ({
            value: String(p.id),
            label: p.type ? `[${p.type}] ${p.name}` : p.name
        }))
    ];
});

const facultyFilterOptions = computed(() => [
    { value: 'all', label: 'All Faculties' },
    ...props.faculties.map(f => ({ value: String(f.id), label: f.name }))
]);

const feeTypeFilterOptions = computed(() => [
    { value: 'all', label: 'All Fee Types' },
    ...props.feeTypes.map(t => ({ value: String(t.id), label: t.name }))
]);

// Auto-reset dependent filter values if they are no longer in valid options
watch(filterFacultyId, (newFac) => {
    if (newFac && newFac !== 'all') {
        if (filterDepartmentId.value !== 'all') {
            const dept = departmentById.value.get(String(filterDepartmentId.value));
            if (dept && String(dept.faculty_id) !== String(newFac)) {
                filterDepartmentId.value = 'all';
                filterProgramId.value = 'all';
            }
        }
    }
});

watch(filterDepartmentId, (newDept) => {
    if (newDept && newDept !== 'all') {
        const dept = departmentById.value.get(String(newDept));
        if (dept && filterFacultyId.value === 'all') {
            filterFacultyId.value = String(dept.faculty_id);
        }
        if (filterProgramId.value !== 'all') {
            const prog = programById.value.get(String(filterProgramId.value));
            if (prog && String(prog.department_id) !== String(newDept)) {
                filterProgramId.value = 'all';
            }
        }
    }
});

watch(filterProgramId, (newProg) => {
    if (newProg && newProg !== 'all') {
        const prog = programById.value.get(String(newProg));
        if (prog) {
            filterDepartmentId.value = String(prog.department_id);
            const dept = departmentById.value.get(String(prog.department_id));
            if (dept) {
                filterFacultyId.value = String(dept.faculty_id);
            }
        }
    }
});

const resetFilters = () => {
    searchFilter.value = '';
    streamFilter.value = 'all';
    filterFacultyId.value = 'all';
    filterDepartmentId.value = 'all';
    filterProgramId.value = 'all';
    filterFeeTypeId.value = 'all';
};

const hasActiveFilters = computed(() => {
    return !!(
        searchFilter.value.trim() ||
        streamFilter.value !== 'all' ||
        filterFacultyId.value !== 'all' ||
        filterDepartmentId.value !== 'all' ||
        filterProgramId.value !== 'all' ||
        filterFeeTypeId.value !== 'all'
    );
});

// ==========================================
// FILTERED TABLE DATA
// ==========================================
const filteredFeeConfigurations = computed(() => {
    const configs: FeeConfiguration[] = props.session.fee_configurations || [];

    return configs.filter(config => {
        // 1. Fee Type Filter
        if (filterFeeTypeId.value !== 'all' && String(config.fee_type_id) !== String(filterFeeTypeId.value)) {
            return false;
        }

        // Resolve associated program, department, faculty
        let prog = config.program;
        if (!prog && config.program_id) {
            prog = programById.value.get(String(config.program_id)) as any;
        }

        let dept = config.department;
        if (!dept && config.department_id) {
            dept = departmentById.value.get(String(config.department_id)) as any;
        } else if (!dept && prog?.department_id) {
            dept = departmentById.value.get(String(prog.department_id)) as any;
        }

        let fac = config.faculty;
        if (!fac && config.faculty_id) {
            fac = facultyById.value.get(String(config.faculty_id)) as any;
        } else if (!fac && dept?.faculty_id) {
            fac = facultyById.value.get(String(dept.faculty_id)) as any;
        }

        // 2. Stream Filter (UG / PG)
        if (streamFilter.value === 'PG') {
            const isPgProg = prog && (prog.academic_stream === 'PG' || ['PGD', 'MSC', 'PHD'].includes(prog.type || ''));
            const isPgEntry = config.entry_mode && ['Postgraduate', 'PGD', 'MBA', 'MIM', 'MSc', 'PhD'].includes(config.entry_mode);
            const isPgLevel = config.level && ['700', '800', '900'].includes(String(config.level));
            const isMilestone = !!config.is_milestone_fee;
            if (!isPgProg && !isPgEntry && !isPgLevel && !isMilestone) {
                return false;
            }
        } else if (streamFilter.value === 'UG') {
            const isUgProg = prog && (prog.academic_stream === 'UG' || prog.type === 'UG');
            const isUgEntry = config.entry_mode && ['UTME', 'Direct Entry', 'Transfer'].includes(config.entry_mode);
            const isUgLevel = config.level && ['100', '200', '300', '400', '500'].includes(String(config.level));
            const isPgOnly = (prog && prog.academic_stream === 'PG') || !!config.is_milestone_fee;
            if (isPgOnly || (!isUgProg && !isUgEntry && !isUgLevel && config.program_id)) {
                return false;
            }
        }

        // 3. Program Filter
        if (filterProgramId.value !== 'all') {
            if (String(config.program_id) !== String(filterProgramId.value)) {
                return false;
            }
        }

        // 4. Department Filter
        if (filterDepartmentId.value !== 'all') {
            const matchesDeptDirect = String(config.department_id) === String(filterDepartmentId.value);
            const matchesDeptViaProg = dept && String(dept.id) === String(filterDepartmentId.value);
            if (!matchesDeptDirect && !matchesDeptViaProg) {
                return false;
            }
        }

        // 5. Faculty Filter
        if (filterFacultyId.value !== 'all') {
            const matchesFacDirect = String(config.faculty_id) === String(filterFacultyId.value);
            const matchesFacViaDept = dept && String(dept.faculty_id) === String(filterFacultyId.value);
            const matchesFacViaProg = fac && String(fac.id) === String(filterFacultyId.value);
            if (!matchesFacDirect && !matchesFacViaDept && !matchesFacViaProg) {
                return false;
            }
        }

        // 6. Search Filter
        if (searchFilter.value.trim()) {
            const term = searchFilter.value.trim().toLowerCase();
            const typeName = (config.fee_type?.name || '').toLowerCase();
            const progName = (prog?.name || '').toLowerCase();
            const deptName = (dept?.name || '').toLowerCase();
            const facName = (fac?.name || '').toLowerCase();
            const entryMode = (config.entry_mode || '').toLowerCase();
            const amountStr = String(config.amount);

            const matchesSearch =
                typeName.includes(term) ||
                progName.includes(term) ||
                deptName.includes(term) ||
                facName.includes(term) ||
                entryMode.includes(term) ||
                amountStr.includes(term);

            if (!matchesSearch) {
                return false;
            }
        }

        return true;
    });
});

// ==========================================
// MODAL CASCADING SELECTS & FORM
// ==========================================
const isConfigModalOpen = ref(false);
const editingConfig = ref(false);

const configForm = useForm({
    id: null as number | null,
    session_id: props.session.id,
    fee_type_id: null as number | null,
    amount: 0,
    faculty_id: 'all' as string | null,
    department_id: 'all' as string | null,
    program_id: 'all' as string | null,
    level: '',
    entry_mode: 'all' as string | null,
    is_compulsory: true,
    is_milestone_fee: false,
    milestone_type: '' as string | null,
});

// Modal Cascading: Departments available based on chosen modal faculty
const modalDepartmentOptions = computed(() => {
    let list = props.departments;
    if (configForm.faculty_id && configForm.faculty_id !== 'all') {
        list = list.filter(d => String(d.faculty_id) === String(configForm.faculty_id));
    }
    return [
        { value: 'all', label: 'All Departments (Faculty-wide)' },
        ...list.map(d => ({ value: String(d.id), label: d.name }))
    ];
});

// Modal Cascading: Programs available based on chosen modal department or faculty
const modalProgramOptions = computed(() => {
    let list = props.programs;
    if (configForm.department_id && configForm.department_id !== 'all') {
        list = list.filter(p => String(p.department_id) === String(configForm.department_id));
    } else if (configForm.faculty_id && configForm.faculty_id !== 'all') {
        const allowedDeptIds = new Set(
            props.departments
                .filter(d => String(d.faculty_id) === String(configForm.faculty_id))
                .map(d => String(d.id))
        );
        list = list.filter(p => allowedDeptIds.has(String(p.department_id)));
    }
    return [
        { value: 'all', label: 'All Programmes (Department-wide)' },
        ...list.map(p => ({
            value: String(p.id),
            label: p.type ? `[${p.type}] ${p.name}` : p.name
        }))
    ];
});

const modalFacultyOptions = computed(() => [
    { value: 'all', label: 'All Faculties (University-wide)' },
    ...props.faculties.map(f => ({ value: String(f.id), label: f.name }))
]);

const modalFeeTypeOptions = computed(() => [
    ...props.feeTypes.map(t => ({ value: Number(t.id), label: t.name }))
]);

const entryModeOptions = [
    { value: 'all', label: 'All Entry Modes & Tiers (Universal)' },
    { value: 'PGD', label: 'PGD (Postgraduate Diploma - All Programmes)' },
    { value: 'MSc', label: 'M.Sc / Academic Masters (All Programmes)' },
    { value: 'MBA', label: 'MBA (Master of Business Administration)' },
    { value: 'PhD', label: 'Ph.D / Doctorate (All Programmes)' },
    { value: 'Postgraduate', label: 'General Postgraduate (All Tiers)' },
    { value: 'UTME', label: 'UTME (Undergraduate)' },
    { value: 'Direct Entry', label: 'Direct Entry (Undergraduate)' },
    { value: 'Transfer', label: 'Transfer (Undergraduate)' },
];

// Handlers for two-way modal cascading
const onModalFacultyChange = (val: string) => {
    configForm.faculty_id = val;
    if (val !== 'all' && configForm.department_id !== 'all') {
        const dept = departmentById.value.get(String(configForm.department_id));
        if (dept && String(dept.faculty_id) !== String(val)) {
            configForm.department_id = 'all';
            configForm.program_id = 'all';
        }
    }
};

const onModalDepartmentChange = (val: string) => {
    configForm.department_id = val;
    if (val !== 'all') {
        const dept = departmentById.value.get(String(val));
        if (dept) {
            configForm.faculty_id = String(dept.faculty_id);
        }
        if (configForm.program_id !== 'all') {
            const prog = programById.value.get(String(configForm.program_id));
            if (prog && String(prog.department_id) !== String(val)) {
                configForm.program_id = 'all';
            }
        }
    }
};

const onModalProgramChange = (val: string) => {
    configForm.program_id = val;
    if (val !== 'all') {
        const prog = programById.value.get(String(val));
        if (prog) {
            configForm.department_id = String(prog.department_id);
            const dept = departmentById.value.get(String(prog.department_id));
            if (dept) {
                configForm.faculty_id = String(dept.faculty_id);
            }
            if (prog.academic_stream === 'PG' || ['PGD', 'MSC', 'PHD'].includes(prog.type || '')) {
                configForm.entry_mode = 'Postgraduate';
                // Best practice: Program-specific PG tuition has level = null
                configForm.level = '';
            }
        }
    }
};

const openCreateConfig = () => {
    configForm.reset();
    configForm.session_id = props.session.id;
    configForm.fee_type_id = props.feeTypes[0]?.id ?? null;
    configForm.faculty_id = 'all';
    configForm.department_id = 'all';
    configForm.program_id = 'all';
    configForm.entry_mode = 'all';
    configForm.level = '';
    configForm.is_compulsory = true;
    configForm.is_milestone_fee = false;
    configForm.milestone_type = '';
    editingConfig.value = false;
    isConfigModalOpen.value = true;
};

const openEditConfig = (config: FeeConfiguration) => {
    configForm.reset();
    configForm.id = config.id;
    configForm.session_id = props.session.id;
    configForm.fee_type_id = config.fee_type_id;
    configForm.amount = config.amount;
    configForm.faculty_id = config.faculty_id ? String(config.faculty_id) : 'all';
    configForm.department_id = config.department_id ? String(config.department_id) : 'all';
    configForm.program_id = config.program_id ? String(config.program_id) : 'all';
    configForm.level = config.level || '';
    configForm.entry_mode = config.entry_mode || 'all';
    configForm.is_compulsory = !!config.is_compulsory;
    configForm.is_milestone_fee = !!config.is_milestone_fee;
    configForm.milestone_type = config.milestone_type || '';
    editingConfig.value = true;
    isConfigModalOpen.value = true;
};

const submitConfig = () => {
    const form = configForm;
    const payload = {
        ...form.data(),
        faculty_id: form.faculty_id === 'all' || !form.faculty_id ? null : form.faculty_id,
        department_id: form.department_id === 'all' || !form.department_id ? null : form.department_id,
        program_id: form.program_id === 'all' || !form.program_id ? null : form.program_id,
        entry_mode: form.entry_mode === 'all' || !form.entry_mode ? null : form.entry_mode,
        level: form.level && form.level.trim() ? form.level.trim() : null,
        milestone_type: form.is_milestone_fee && form.milestone_type ? form.milestone_type : null,
    };

    if (editingConfig.value && configForm.id) {
        router.put(route('admin.finance.fee_configurations.update', configForm.id), payload, {
            onSuccess: () => {
                isConfigModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Fee Rule updated successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: (err) => {
                const message = Object.values(err)[0] || 'Failed to save fee rule';
                Swal.fire({ icon: 'error', title: 'Error', text: String(message), toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    } else {
        router.post(route('admin.finance.fee_configurations.store'), payload, {
            onSuccess: () => {
                isConfigModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Fee Rule created successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: (err) => {
                const message = Object.values(err)[0] || 'Failed to create fee rule';
                Swal.fire({ icon: 'error', title: 'Error', text: String(message), toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            }
        });
    }
};

const deleteConfig = (config: FeeConfiguration) => {
    Swal.fire({
        title: 'Delete this fee rule?',
        text: `Are you sure you want to delete this ${config.fee_type?.name} rule?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete rule',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.finance.fee_configurations.destroy', config.id), {
                onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', text: 'Fee rule deleted', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
                onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete fee rule', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
            });
        }
    });
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(val);
};
</script>

<template>
    <Head :title="`Fees: ${session.name}`" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <Link :href="route('admin.finance.index')" class="text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                        <h2 class="text-3xl font-bold tracking-tight">{{ session.name }} Session Fees</h2>
                        <Badge
                            v-if="session.academic_stream === 'PG'"
                            variant="secondary"
                            class="bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800 font-semibold text-xs px-2.5 py-0.5"
                        >
                            Postgraduate (PG)
                        </Badge>
                        <Badge
                            v-else
                            variant="outline"
                            class="bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800 font-semibold text-xs px-2.5 py-0.5"
                        >
                            Undergraduate (UG)
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">
                        Manage fee rules for {{ session.name }} 
                        ({{ format(new Date(session.start_date), 'MMM d, yyyy') }} - {{ format(new Date(session.end_date), 'MMM d, yyyy') }}).
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="openCreateConfig" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">
                        <Plus class="mr-2 h-4 w-4" /> Add Rule
                    </Button>
                </div>
            </div>

            <!-- Filters Bar Card -->
            <Card class="border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Filter class="w-4 h-4 text-zinc-500" />
                            <CardTitle class="text-sm font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                                Filter Fee Configurations
                            </CardTitle>
                        </div>
                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="sm"
                            @click="resetFilters"
                            class="h-8 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30"
                        >
                            <X class="w-3.5 h-3.5 mr-1" /> Reset All Filters
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Row 1: Search & Academic Stream Segment -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="relative md:col-span-2">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                            <Input
                                v-model="searchFilter"
                                placeholder="Search by fee type, programme, department, faculty, entry mode..."
                                class="pl-9 h-9"
                            />
                        </div>
                        <div class="flex items-center gap-1 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-md">
                            <button
                                type="button"
                                @click="streamFilter = 'all'"
                                class="flex-1 py-1 px-2.5 text-xs font-medium rounded transition-colors text-center"
                                :class="streamFilter === 'all' ? 'bg-white dark:bg-zinc-900 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'"
                            >
                                All Streams
                            </button>
                            <button
                                type="button"
                                @click="streamFilter = 'UG'"
                                class="flex-1 py-1 px-2.5 text-xs font-medium rounded transition-colors text-center"
                                :class="streamFilter === 'UG' ? 'bg-white dark:bg-zinc-900 shadow-xs text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'"
                            >
                                Undergraduate
                            </button>
                            <button
                                type="button"
                                @click="streamFilter = 'PG'"
                                class="flex-1 py-1 px-2.5 text-xs font-medium rounded transition-colors text-center"
                                :class="streamFilter === 'PG' ? 'bg-white dark:bg-zinc-900 shadow-xs text-purple-600 dark:text-purple-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'"
                            >
                                Postgraduate (SPGS)
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: Cascading Searchable Selects -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <Label class="text-xs text-zinc-500 mb-1 block">Faculty Filter</Label>
                            <SearchableSelect
                                v-model="filterFacultyId"
                                :options="facultyFilterOptions"
                                placeholder="All Faculties"
                                search-placeholder="Search faculty..."
                                trigger-class="h-9 w-full text-xs"
                            />
                        </div>
                        <div>
                            <Label class="text-xs text-zinc-500 mb-1 block">Department Filter</Label>
                            <SearchableSelect
                                v-model="filterDepartmentId"
                                :options="filterDepartmentOptions"
                                placeholder="All Departments"
                                search-placeholder="Search department..."
                                trigger-class="h-9 w-full text-xs"
                            />
                        </div>
                        <div>
                            <Label class="text-xs text-zinc-500 mb-1 block">Programme Filter</Label>
                            <SearchableSelect
                                v-model="filterProgramId"
                                :options="filterProgramOptions"
                                placeholder="All Programmes"
                                search-placeholder="Search programme..."
                                trigger-class="h-9 w-full text-xs"
                            />
                        </div>
                        <div>
                            <Label class="text-xs text-zinc-500 mb-1 block">Fee Type Filter</Label>
                            <SearchableSelect
                                v-model="filterFeeTypeId"
                                :options="feeTypeFilterOptions"
                                placeholder="All Fee Types"
                                search-placeholder="Search fee type..."
                                trigger-class="h-9 w-full text-xs"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Fee Configuration Rules</CardTitle>
                        <CardDescription>
                            Showing {{ filteredFeeConfigurations.length }} of {{ (session.fee_configurations || []).length }} fee rules defined for this academic session.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Fee Type</TableHead>
                                <TableHead>Target Scope (Faculty / Dept / Programme)</TableHead>
                                <TableHead class="w-[120px]">Level</TableHead>
                                <TableHead class="w-[140px]">Amount</TableHead>
                                <TableHead class="text-right w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody v-if="filteredFeeConfigurations.length > 0">
                            <TableRow v-for="config in filteredFeeConfigurations" :key="config.id">
                                <TableCell class="font-medium align-top">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ config.fee_type?.name }}</span>
                                        <Badge v-if="config.is_compulsory" class="text-[10px]" variant="outline">Compulsory</Badge>
                                        <Badge v-if="config.is_milestone_fee" class="text-[10px] bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300" variant="secondary">
                                            Milestone: {{ config.milestone_type ? (milestoneTypeLabels[config.milestone_type] || config.milestone_type) : 'PG' }}
                                        </Badge>
                                    </div>
                                    <div v-if="config.fee_type?.is_one_time" class="text-[11px] text-zinc-400 mt-0.5">
                                        One-Time Non-refundable
                                    </div>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground align-top">
                                    <div v-if="config.program" class="space-y-0.5">
                                        <div class="font-medium text-foreground flex items-center gap-1">
                                            <BookOpen class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                            <span>{{ config.program.name }}</span>
                                            <Badge v-if="config.program.type" variant="outline" class="text-[10px] font-mono uppercase ml-1">
                                                {{ config.program.type }}
                                            </Badge>
                                        </div>
                                        <div class="text-[11px] text-zinc-500 pl-4.5">
                                            {{ config.program.department?.faculty?.name ? `${config.program.department.faculty.name} • ` : '' }}{{ config.program.department?.name || '' }}
                                        </div>
                                    </div>
                                    <div v-else-if="config.department" class="space-y-0.5">
                                        <div class="font-medium text-foreground flex items-center gap-1 flex-wrap">
                                            <Layers class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                            <span>Dept: {{ config.department.name }}</span>
                                            <Badge v-if="config.entry_mode && ['PGD', 'MSc', 'MBA', 'PhD'].includes(config.entry_mode)" variant="secondary" class="text-[10px] ml-1 bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300">
                                                All {{ config.entry_mode }} Programmes
                                            </Badge>
                                        </div>
                                        <div v-if="config.department.faculty?.name" class="text-[11px] text-zinc-500 pl-4.5">
                                            Faculty: {{ config.department.faculty.name }}
                                        </div>
                                    </div>
                                    <div v-else-if="config.faculty" class="space-y-0.5">
                                        <div class="font-medium text-foreground flex items-center gap-1 flex-wrap">
                                            <Building2 class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                                            <span>Faculty: {{ config.faculty.name }}</span>
                                            <Badge v-if="config.entry_mode && ['PGD', 'MSc', 'MBA', 'PhD'].includes(config.entry_mode)" variant="secondary" class="text-[10px] ml-1 bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300">
                                                All {{ config.entry_mode }} Programmes
                                            </Badge>
                                        </div>
                                        <div v-if="config.entry_mode && ['PGD', 'MSc', 'MBA', 'PhD'].includes(config.entry_mode)" class="text-[11px] text-zinc-500 pl-4.5">
                                            Auto-applies to all {{ config.entry_mode }} courses in this faculty
                                        </div>
                                    </div>
                                    <div v-else class="space-y-0.5">
                                        <div class="italic text-zinc-500 flex items-center gap-1 flex-wrap">
                                            <span>Institution-wide Global</span>
                                            <Badge v-if="config.entry_mode && ['PGD', 'MSc', 'MBA', 'PhD'].includes(config.entry_mode)" variant="secondary" class="text-[10px] ml-1 bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300">
                                                All {{ config.entry_mode }} Programmes
                                            </Badge>
                                        </div>
                                    </div>
                                    
                                    <div v-if="config.entry_mode" class="mt-1.5 flex items-center gap-1">
                                        <span class="text-[10px] text-zinc-400">Entry:</span>
                                        <Badge variant="outline" class="text-[10px] bg-slate-50 border-slate-200 dark:bg-zinc-800 dark:border-zinc-700">
                                            {{ config.entry_mode }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="align-top">
                                    <Badge v-if="!config.level" variant="secondary" class="text-xs font-normal">
                                        All Levels
                                    </Badge>
                                    <Badge v-else variant="outline" class="font-mono text-xs font-semibold">
                                        {{ config.level }} Level
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-bold text-base align-top text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(config.amount) }}
                                </TableCell>
                                <TableCell class="text-right space-x-1 align-top">
                                    <Button variant="ghost" size="icon" @click="openEditConfig(config)" title="Edit Rule">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-rose-600 hover:text-rose-700 hover:bg-rose-50" @click="deleteConfig(config)" title="Delete Rule">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                        <TableBody v-else>
                            <TableRow>
                                <TableCell colspan="5" class="text-center py-10 text-muted-foreground">
                                    <p class="font-medium">No matching fee rules found.</p>
                                    <p class="text-xs text-zinc-400 mt-1" v-if="hasActiveFilters">
                                        Try adjusting your search criteria or reset filters.
                                    </p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- ADD / EDIT CONFIG MODAL -->
            <Dialog v-model:open="isConfigModalOpen">
                <DialogContent class="sm:max-w-2xl max-h-[92vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                            <Plus v-if="!editingConfig" class="w-5 h-5 text-indigo-600" />
                            <Edit v-else class="w-5 h-5 text-indigo-600" />
                            {{ editingConfig ? 'Edit Fee Rule' : 'New Fee Rule' }}
                        </DialogTitle>
                        <DialogDescription>
                            Configure how fees are applied for academic session: <strong>{{ session.name }}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-2">
                        <!-- Fee Type & Amount -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="grid gap-1.5">
                                <Label class="text-xs font-semibold">Fee Type *</Label>
                                <SearchableSelect
                                    :model-value="configForm.fee_type_id"
                                    @update:model-value="(v) => configForm.fee_type_id = Number(v)"
                                    :options="modalFeeTypeOptions"
                                    placeholder="Select fee type"
                                    search-placeholder="Search fee type..."
                                    :disabled="editingConfig"
                                    trigger-class="h-9 w-full"
                                />
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-xs font-semibold">Amount (₦) *</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-sm font-semibold text-zinc-500">₦</span>
                                    <Input
                                        type="number"
                                        class="pl-8 h-9 font-semibold text-zinc-900 dark:text-zinc-100"
                                        v-model="configForm.amount"
                                        placeholder="0.00"
                                        min="0"
                                    />
                                </div>
                                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400" v-if="configForm.amount">
                                    {{ formatCurrency(configForm.amount) }}
                                </p>
                            </div>
                        </div>

                        <!-- Target Scope Box with Cascading SearchableSelects -->
                        <div class="p-4 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-zinc-50/70 dark:bg-zinc-900/40 space-y-3.5">
                            <div class="flex items-center justify-between pb-1 border-b border-zinc-200/80 dark:border-zinc-800">
                                <div class="flex items-center gap-1.5">
                                    <Layers class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                        Target Scope Hierarchy
                                    </h4>
                                </div>
                                <span class="text-[11px] text-zinc-500 bg-zinc-200/60 dark:bg-zinc-800 px-2 py-0.5 rounded-full">
                                    Leave at "All" for broader scope
                                </span>
                            </div>
                             
                            <!-- Faculty and Department in 2 columns -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Faculty</Label>
                                    <SearchableSelect
                                        :model-value="configForm.faculty_id || 'all'"
                                        @update:model-value="onModalFacultyChange"
                                        :options="modalFacultyOptions"
                                        placeholder="All Faculties (University-wide)"
                                        search-placeholder="Search faculty..."
                                        trigger-class="h-9 w-full"
                                    />
                                </div>

                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Department</Label>
                                    <SearchableSelect
                                        :model-value="configForm.department_id || 'all'"
                                        @update:model-value="onModalDepartmentChange"
                                        :options="modalDepartmentOptions"
                                        placeholder="All Departments (Faculty-wide)"
                                        search-placeholder="Search department..."
                                        trigger-class="h-9 w-full"
                                    />
                                </div>
                            </div>

                            <!-- Programme (Full width) -->
                            <div class="grid gap-1.5">
                                <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Programme (Optional Specific Course)</Label>
                                <SearchableSelect
                                    :model-value="configForm.program_id || 'all'"
                                    @update:model-value="onModalProgramChange"
                                    :options="modalProgramOptions"
                                    placeholder="All Programmes (Department / Faculty-wide)"
                                    search-placeholder="Search programme..."
                                    trigger-class="h-9 w-full"
                                />
                            </div>

                            <!-- Degree Tier & Level in clean 2 columns -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-0.5">
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Degree Tier / Entry Mode</Label>
                                    <SearchableSelect
                                        :model-value="configForm.entry_mode || 'all'"
                                        @update:model-value="(v) => configForm.entry_mode = v"
                                        :options="entryModeOptions"
                                        placeholder="All Entry Modes & Tiers"
                                        search-placeholder="Search entry mode or tier..."
                                        trigger-class="h-9 w-full"
                                    />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Academic Level (Optional)</Label>
                                    <Input
                                        v-model="configForm.level"
                                        placeholder="e.g. 100, 200 (or leave blank for all)"
                                        class="h-9"
                                    />
                                </div>
                            </div>

                            <!-- Clean Callout Banner -->
                            <div class="p-2.5 bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 rounded-md flex items-start gap-2 text-xs text-indigo-900 dark:text-indigo-300">
                                <Info class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" />
                                <div class="text-[11px] leading-relaxed">
                                    <span class="font-semibold text-indigo-950 dark:text-indigo-200">Tier Pricing:</span>
                                    Leave <em>Programme</em> at "All" and select a Degree Tier (e.g. <code class="px-1 py-0.5 bg-indigo-100 dark:bg-indigo-900 rounded font-mono text-[10px]">PGD</code>, <code class="px-1 py-0.5 bg-indigo-100 dark:bg-indigo-900 rounded font-mono text-[10px]">M.Sc</code>, or <code class="px-1 py-0.5 bg-indigo-100 dark:bg-indigo-900 rounded font-mono text-[10px]">MBA</code>) to automatically set the tuition for all matching courses under the faculty at once.
                                </div>
                            </div>
                        </div>

                        <!-- Checkboxes & Milestones -->
                        <div class="space-y-3 pt-1">
                            <div class="flex items-center space-x-2.5">
                                <Checkbox id="comp-new" v-model:checked="configForm.is_compulsory" />
                                <Label for="comp-new" class="text-xs font-medium cursor-pointer">Is Compulsory Fee?</Label>
                            </div>
                            <div class="flex items-center space-x-2.5">
                                <Checkbox id="milestone-fee" v-model:checked="configForm.is_milestone_fee" />
                                <Label for="milestone-fee" class="text-xs font-medium cursor-pointer">
                                    Unbundled Postgraduate Milestone Fee (Bench, Practicum, Defense, Extension)
                                </Label>
                            </div>
                            <div v-if="configForm.is_milestone_fee" class="grid gap-1.5 pl-6 pt-1">
                                <Label class="text-xs">Milestone Trigger Stage</Label>
                                <Select :model-value="configForm.milestone_type || undefined" @update:model-value="(v) => configForm.milestone_type = v as string">
                                    <SelectTrigger class="h-9"><SelectValue placeholder="Select Milestone Stage" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="bench_fee">Research Bench / Laboratory Fee</SelectItem>
                                        <SelectItem value="practicum_fee">Executive Practicum / Field Attachment Fee</SelectItem>
                                        <SelectItem value="defense_fee">External Viva Voce / Dissertation Defense Fee</SelectItem>
                                        <SelectItem value="extension_fee">Programme Extension / Renewal Fee</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>
                    <DialogFooter class="pt-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-end gap-2">
                        <Button variant="outline" @click="isConfigModalOpen = false">Cancel</Button>
                        <Button @click="submitConfig" :disabled="configForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-xs">
                            Save Fee Rule
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
