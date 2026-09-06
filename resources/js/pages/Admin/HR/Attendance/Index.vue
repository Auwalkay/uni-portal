<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    Search, 
    Filter, 
    X,
    Clock,
    Upload,
    Plus,
    Calendar,
    ChevronLeft,
    ChevronRight,
    CheckCircle2,
    XCircle,
    Clock3,
    FileSpreadsheet,
    Trash2,
    Edit2,
    BarChart3,
    LayoutGrid,
    Umbrella,
    Gift,
    PartyPopper,
    Check,
    ChevronsUpDown,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    UserCheck,
    UserX,
    Loader2,
    Sparkles,
    CheckSquare,
    ListCheck
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardFooter,
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
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Command, CommandInput, CommandList, CommandEmpty, CommandItem } from '@/components/ui/command'

const props = defineProps<{
    attendances: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    departments: Array<{ id: string; name: string }>;
    allStaff: Array<{ 
        id: string; 
        name: string; 
        staff_number?: string;
        department_id?: string;
        department_name?: string;
        existing_status?: string | null;
        clock_in?: string | null;
        clock_out?: string | null;
        notes?: string;
    }>;
    holiday: any;
    holidays: Array<{ id: string; name: string; date: string; description?: string }>;
    filters: {
        date?: string;
        department_id?: string;
        status?: string;
        sort_by?: string;
        sort_dir?: string;
    };
}>();

const selectedDate = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const selectedDept = ref(props.filters.department_id ? String(props.filters.department_id) : 'ALL');
const selectedStatus = ref(props.filters.status ? String(props.filters.status) : 'ALL');
const sortBy = ref(props.filters.sort_by || 'clock_in');
const sortDir = ref(props.filters.sort_dir || 'asc');

const applyFilters = () => {
    router.get(route('admin.attendance.index'), {
        date: selectedDate.value,
        department_id: selectedDept.value === 'ALL' ? '' : selectedDept.value,
        status: selectedStatus.value === 'ALL' ? '' : selectedStatus.value,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

const handleSort = (column: string) => {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'asc';
    }
    applyFilters();
};

// Watchers for filtering
watch([selectedDate, selectedDept, selectedStatus, sortBy, sortDir], () => {
    applyFilters();
});

// Quick Batch Attendance Sheet State
const showQuickSheetModal = ref(false);
const quickSheetSearch = ref('');
const quickSheetDept = ref('ALL');

const batchMap = ref<Record<string, { status: string; clock_in?: string; clock_out?: string; notes?: string }>>({});

const syncBatchMap = () => {
    const map: Record<string, { status: string; clock_in?: string; clock_out?: string; notes?: string }> = {};
    props.allStaff.forEach(s => {
        map[s.id] = {
            status: s.existing_status || 'present',
            clock_in: s.clock_in || '',
            clock_out: s.clock_out || '',
            notes: s.notes || '',
        };
    });
    batchMap.value = map;
};

watch(() => props.allStaff, () => {
    syncBatchMap();
}, { immediate: true });

const quickSheetPage = ref(1);
const quickSheetPerPage = ref(50);

watch([quickSheetSearch, quickSheetDept], () => {
    quickSheetPage.value = 1;
});

const filteredQuickStaff = computed(() => {
    return props.allStaff.filter(s => {
        const matchesDept = quickSheetDept.value === 'ALL' || String(s.department_id) === String(quickSheetDept.value);
        const q = quickSheetSearch.value.toLowerCase().trim();
        const matchesQuery = !q || s.name.toLowerCase().includes(q) || (s.staff_number && s.staff_number.toLowerCase().includes(q));
        return matchesDept && matchesQuery;
    });
});

const totalQuickPages = computed(() => {
    return Math.ceil(filteredQuickStaff.value.length / quickSheetPerPage.value) || 1;
});

const paginatedQuickStaff = computed(() => {
    const start = (quickSheetPage.value - 1) * quickSheetPerPage.value;
    return filteredQuickStaff.value.slice(start, start + quickSheetPerPage.value);
});

const setStaffStatus = (staffId: string, status: 'present' | 'absent' | 'late' | 'on_leave') => {
    if (batchMap.value[staffId]) {
        batchMap.value[staffId].status = status;
    }
};

const markAllVisible = (status: 'present' | 'absent' | 'late' | 'on_leave') => {
    filteredQuickStaff.value.forEach(s => {
        if (batchMap.value[s.id]) {
            batchMap.value[s.id].status = status;
        }
    });
};

const quickStats = computed(() => {
    let present = 0;
    let absent = 0;
    let late = 0;
    let onLeave = 0;

    Object.values(batchMap.value).forEach(item => {
        if (item.status === 'present') present++;
        else if (item.status === 'absent') absent++;
        else if (item.status === 'late') late++;
        else if (item.status === 'on_leave') onLeave++;
    });

    return { present, absent, late, onLeave, total: props.allStaff.length };
});

const isSubmittingBulk = ref(false);

const saveBulkAttendance = () => {
    isSubmittingBulk.value = true;
    const payload = {
        date: selectedDate.value,
        attendances: props.allStaff.map(s => ({
            staff_id: s.id,
            status: batchMap.value[s.id]?.status || 'present',
            clock_in: batchMap.value[s.id]?.clock_in || null,
            clock_out: batchMap.value[s.id]?.clock_out || null,
            notes: batchMap.value[s.id]?.notes || null,
        })),
    };

    router.post(route('admin.attendance.bulk-store'), payload, {
        preserveScroll: true,
        onFinish: () => {
            isSubmittingBulk.value = false;
            showQuickSheetModal.value = false;
        },
    });
};

const showImportModal = ref(false);
const importForm = useForm({
    date: selectedDate.value,
    file: null as File | null,
});

const submitImport = () => {
    importForm.post(route('admin.attendance.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const showManualModal = ref(false);
const manualForm = useForm({
    staff_id: '',
    date: selectedDate.value,
    clock_in: '',
    clock_out: '',
    status: 'present',
    notes: '',
});

const submitManual = () => {
    manualForm.post(route('admin.attendance.store'), {
        onSuccess: () => {
            showManualModal.value = false;
            manualForm.reset();
        },
    });
};

const showEditModal = ref(false);
const editingRecordId = ref<string | null>(null);
const editingStaffName = ref('');
const editForm = useForm({
    clock_in: '',
    clock_out: '',
    status: 'present',
    notes: '',
});

const openEditModal = (record: any) => {
    editingRecordId.value = record.id;
    editingStaffName.value = record.staff?.user?.name || 'Staff Member';
    editForm.clock_in = record.clock_in ? record.clock_in.substring(0, 5) : '';
    editForm.clock_out = record.clock_out ? record.clock_out.substring(0, 5) : '';
    editForm.status = record.status;
    editForm.notes = record.notes || '';
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!editingRecordId.value) return;
    editForm.put(route('admin.attendance.update', editingRecordId.value), {
        onSuccess: () => {
            showEditModal.value = false;
            editingRecordId.value = null;
        },
    });
};

const showHolidayModal = ref(false);
const holidayForm = useForm({
    date: selectedDate.value,
    name: '',
    description: '',
});

watch(selectedDate, (newDate) => {
    holidayForm.date = newDate;
});

const submitHoliday = () => {
    holidayForm.post(route('admin.attendance.holiday.store'), {
        onSuccess: () => {
            showHolidayModal.value = false;
            holidayForm.reset(['name', 'description']);
        },
    });
};

const removeHoliday = (id: string) => {
    if (confirm('Are you sure you want to remove this holiday?')) {
        router.delete(route('admin.attendance.holiday.destroy', id), {
            preserveScroll: true
        });
    }
};

const showAllHolidaysModal = ref(false);
const showEditHolidayModal = ref(false);
const editingHolidayId = ref<string | null>(null);
const editHolidayForm = useForm({
    date: '',
    name: '',
    description: '',
});

const openEditHolidayModal = (h: any) => {
    editingHolidayId.value = h.id;
    editHolidayForm.date = h.date ? h.date.substring(0, 10) : '';
    editHolidayForm.name = h.name || '';
    editHolidayForm.description = h.description || '';
    showEditHolidayModal.value = true;
};

const submitEditHoliday = () => {
    if (!editingHolidayId.value) return;
    editHolidayForm.put(route('admin.attendance.holiday.update', editingHolidayId.value), {
        onSuccess: () => {
            showEditHolidayModal.value = false;
            editingHolidayId.value = null;
        },
    });
};

const deleteRecord = (id: string) => {
    if (confirm('Are you sure you want to remove this attendance record?')) {
        router.delete(route('admin.attendance.destroy', id), {
            preserveScroll: true
        });
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'present': return { variant: 'default' as const, label: 'Present', icon: CheckCircle2, color: 'text-green-600 bg-green-50 border-green-200' };
        case 'late': return { variant: 'secondary' as const, label: 'Late', icon: Clock3, color: 'text-amber-600 bg-amber-50 border-amber-200' };
        case 'absent': return { variant: 'destructive' as const, label: 'Absent', icon: XCircle, color: 'text-red-600 bg-red-50 border-red-200' };
        case 'on_leave': return { variant: 'outline' as const, label: 'On Leave', icon: Calendar, color: 'text-blue-600 bg-blue-50 border-blue-200' };
        default: return { variant: 'outline' as const, label: status, icon: Clock, color: '' };
    }
};

const formatTime = (time: string | null) => {
    if (!time) return '---';
    return time.substring(0, 5); // Assuming HH:mm:ss format from DB
};

const formatDateTime = (dateTimeStr: string | null) => {
    if (!dateTimeStr) return '---';
    const d = new Date(dateTimeStr);
    return d.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const markAbsentForUnlogged = () => {
    if (confirm(`Are you sure you want to mark all unlogged active staff members as ABSENT for ${selectedDate.value}?`)) {
        router.post(route('admin.attendance.mark-absent'), {
            date: selectedDate.value,
        }, {
            preserveScroll: true,
        });
    }
};

</script>

<template>
    <Head title="Staff Attendance" />

    <AdminLayout>
        <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-slate-900 dark:text-slate-100">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Staff Attendance</h1>
                    <p class="text-muted-foreground mt-1 text-slate-500">Track and manage employee daily attendance logs.</p>
                </div>

                <div class="flex flex-wrap sm:flex-nowrap gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                    <Button variant="outline" as-child class="border-slate-200 bg-white flex-1 sm:flex-none">
                        <Link :href="route('admin.attendance.reports')">
                            <BarChart3 class="w-4 h-4 mr-2" /> View Reports
                        </Link>
                    </Button>

                    <Button variant="outline" as-child class="border-slate-200 bg-white flex-1 sm:flex-none">
                        <Link :href="route('admin.attendance.calendar')">
                            <LayoutGrid class="w-4 h-4 mr-2" /> Calendar View
                        </Link>
                    </Button>

                    <Dialog v-model:open="showImportModal">
                        <DialogTrigger as-child>
                            <Button variant="outline" class="border-slate-200 bg-white flex-1 sm:flex-none">
                                <Upload class="w-4 h-4 mr-2" /> Bulk Import
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-[95vw] sm:max-w-[500px]">
                            <DialogHeader>
                                <DialogTitle>Import Attendance Log</DialogTitle>
                                <DialogDescription>
                                    Select a date and upload the daily attendance Excel file.
                                    <a :href="route('admin.attendance.download-template')" class="text-primary font-bold hover:underline block mt-1">Download Import Template</a>
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label>Target Date</Label>
                                    <Input type="date" v-model="importForm.date" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Excel File</Label>
                                    <Input type="file" accept=".xlsx,.csv" @input="importForm.file = $event.target.files[0]" />
                                    <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 space-y-2">
                                    <p class="text-xs font-bold text-blue-800 flex items-center gap-2">
                                        <FileSpreadsheet class="w-3 h-3" /> Excel Format:
                                    </p>
                                    <ul class="text-[11px] text-blue-700 list-disc list-inside font-medium leading-relaxed">
                                        <li>Required columns: <strong>staff_id</strong>, <strong>clock_in</strong></li>
                                        <li>Optional: <strong>staff_name</strong> (ignored), <strong>clock_out</strong></li>
                                        <li>Time format: 24h format (e.g., 08:30)</li>
                                    </ul>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button @click="submitImport" :disabled="importForm.processing || !importForm.file" class="w-full">
                                    {{ importForm.processing ? 'Importing...' : 'Upload & Process' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Button 
                        @click="syncBatchMap(); showQuickSheetModal = true" 
                        :disabled="!!holiday" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-sm flex-1 sm:flex-none"
                    >
                        <CheckSquare class="w-4 h-4 mr-2" /> Quick Ticking Sheet
                    </Button>

                    <Button variant="outline" @click="markAbsentForUnlogged" :disabled="!!holiday" class="border-red-200 bg-red-50 text-red-700 hover:bg-red-100 flex-1 sm:flex-none">
                        <XCircle class="w-4 h-4 mr-2" /> Mark Absent
                    </Button>

                    <Button @click="showManualModal = true" :disabled="!!holiday" class="flex-1 sm:flex-none">
                        <Plus class="w-4 h-4 mr-2" /> Add Record
                    </Button>
                </div>
            </div>

            <!-- Holiday Banner -->
            <div v-if="holiday" class="bg-indigo-600 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-xl shadow-indigo-200 group animate-in fade-in slide-in-from-top duration-500">
                <div class="absolute -right-8 -bottom-8 opacity-10 group-hover:scale-110 transition-transform duration-700">
                    <PartyPopper class="w-48 h-48" />
                </div>
                <div class="relative flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
                    <div class="space-y-2">
                        <div class="flex items-center justify-center md:justify-start gap-3">
                            <span class="p-2 bg-white/20 rounded-xl backdrop-blur-md">
                                <Umbrella class="w-6 h-6" />
                            </span>
                            <h2 class="text-xl sm:text-2xl font-black uppercase tracking-tight">Public Holiday: {{ holiday.name }}</h2>
                        </div>
                        <p class="text-indigo-100 font-medium max-w-2xl text-sm sm:text-base">{{ holiday.description || 'All university operations are suspended for this date.' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button variant="outline" class="bg-white/20 border-white/30 hover:bg-white/30 text-white font-bold px-4 rounded-xl" @click="openEditHolidayModal(holiday)">
                            <Edit2 class="w-4 h-4 mr-2" /> Edit Holiday
                        </Button>
                        <Button variant="outline" class="bg-white/10 border-white/20 hover:bg-white/20 text-white font-bold px-4 rounded-xl" @click="removeHoliday(holiday.id)">
                            <Trash2 class="w-4 h-4 mr-2" /> Remove Holiday
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <Button variant="outline" size="sm" class="h-9 border-slate-200 text-slate-700 font-bold px-4 rounded-xl hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50" @click="showAllHolidaysModal = true">
                    <Calendar class="w-4 h-4 mr-2" /> All Holidays ({{ holidays?.length || 0 }})
                </Button>

                <Button v-if="!holiday" variant="outline" size="sm" class="h-9 border-slate-200 text-slate-500 font-bold px-4 rounded-xl hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50" @click="showHolidayModal = true">
                    <Umbrella class="w-4 h-4 mr-2" /> Mark Date as Holiday
                </Button>
            </div>

            <!-- Dashboard Filters -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 items-end">
                <Card class="shadow-sm border-slate-200">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase tracking-wider text-slate-500 font-bold">Attendance Date</CardTitle>
                    </CardHeader>
                    <CardContent class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-primary" />
                        <Input type="date" v-model="selectedDate" class="border-none p-0 focus-visible:ring-0 text-base sm:text-lg font-bold" />
                    </CardContent>
                </Card>

                <div>
                    <Label class="text-xs font-bold text-slate-500 uppercase ml-1">Department</Label>
                    <Select v-model="selectedDept">
                        <SelectTrigger class="bg-white border-slate-200 mt-1">
                            <SelectValue placeholder="All Departments" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Departments</SelectItem>
                            <SelectItem v-for="dept in departments" :key="dept.id" :value="String(dept.id)">{{ dept.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label class="text-xs font-bold text-slate-500 uppercase ml-1">Status</Label>
                    <Select v-model="selectedStatus">
                        <SelectTrigger class="bg-white border-slate-200 mt-1">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Status</SelectItem>
                            <SelectItem value="present">Present</SelectItem>
                            <SelectItem value="late">Late</SelectItem>
                            <SelectItem value="absent">Absent</SelectItem>
                            <SelectItem value="on_leave">On Leave</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label class="text-xs font-bold text-slate-500 uppercase ml-1">Sort By</Label>
                    <Select v-model="sortBy">
                        <SelectTrigger class="bg-white border-slate-200 mt-1">
                            <SelectValue placeholder="Sort By" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="clock_in">Clock In Time (Default)</SelectItem>
                            <SelectItem value="clock_out">Clock Out Time</SelectItem>
                            <SelectItem value="created_at">Log Entry Time (Created At)</SelectItem>
                            <SelectItem value="name">Staff Name</SelectItem>
                            <SelectItem value="staff_number">Staff Number</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label class="text-xs font-bold text-slate-500 uppercase ml-1">Sort Order</Label>
                    <Select v-model="sortDir">
                        <SelectTrigger class="bg-white border-slate-200 mt-1">
                            <SelectValue placeholder="Order" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="asc">Ascending (Earliest / A-Z)</SelectItem>
                            <SelectItem value="desc">Descending (Latest / Z-A)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Attendance Table -->
            <Card class="border-slate-200 shadow-sm overflow-hidden bg-white">
                <div class="overflow-x-auto min-w-full">
                    <Table>
                        <TableHeader class="bg-slate-50">
                            <TableRow>
                                <TableHead class="min-w-[220px] sm:w-[320px]">
                                    <div class="flex items-center gap-2">
                                        <button @click="handleSort('name')" class="flex items-center gap-1 hover:text-indigo-600 font-bold transition-colors">
                                            Staff Name
                                            <ArrowUpDown class="w-3 h-3 text-slate-400" v-if="sortBy !== 'name'" />
                                            <ArrowUp class="w-3 h-3 text-indigo-600" v-else-if="sortDir === 'asc'" />
                                            <ArrowDown class="w-3 h-3 text-indigo-600" v-else />
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <button @click="handleSort('staff_number')" class="flex items-center gap-1 hover:text-indigo-600 font-bold transition-colors">
                                            Staff No.
                                            <ArrowUpDown class="w-3 h-3 text-slate-400" v-if="sortBy !== 'staff_number'" />
                                            <ArrowUp class="w-3 h-3 text-indigo-600" v-else-if="sortDir === 'asc'" />
                                            <ArrowDown class="w-3 h-3 text-indigo-600" v-else />
                                        </button>
                                    </div>
                                </TableHead>
                                <TableHead class="min-w-[120px]">
                                    <button @click="handleSort('clock_in')" class="flex items-center gap-1 hover:text-indigo-600 font-bold transition-colors">
                                        Clock In
                                        <ArrowUpDown class="w-3 h-3 text-slate-400" v-if="sortBy !== 'clock_in'" />
                                        <ArrowUp class="w-3 h-3 text-indigo-600" v-else-if="sortDir === 'asc'" />
                                        <ArrowDown class="w-3 h-3 text-indigo-600" v-else />
                                    </button>
                                </TableHead>
                                <TableHead class="min-w-[120px]">
                                    <button @click="handleSort('clock_out')" class="flex items-center gap-1 hover:text-indigo-600 font-bold transition-colors">
                                        Clock Out
                                        <ArrowUpDown class="w-3 h-3 text-slate-400" v-if="sortBy !== 'clock_out'" />
                                        <ArrowUp class="w-3 h-3 text-indigo-600" v-else-if="sortDir === 'asc'" />
                                        <ArrowDown class="w-3 h-3 text-indigo-600" v-else />
                                    </button>
                                </TableHead>
                                <TableHead class="min-w-[110px]">Status</TableHead>
                                <TableHead class="min-w-[90px]">Source</TableHead>
                                <TableHead class="min-w-[170px]">
                                    <button @click="handleSort('created_at')" class="flex items-center gap-1 hover:text-indigo-600 font-bold transition-colors">
                                        Audit Trail / Created At
                                        <ArrowUpDown class="w-3 h-3 text-slate-400" v-if="sortBy !== 'created_at'" />
                                        <ArrowUp class="w-3 h-3 text-indigo-600" v-else-if="sortDir === 'asc'" />
                                        <ArrowDown class="w-3 h-3 text-indigo-600" v-else />
                                    </button>
                                </TableHead>
                                <TableHead class="text-right min-w-[90px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="record in attendances.data" :key="record.id" class="group hover:bg-slate-50/50">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <Avatar class="h-10 w-10 border border-slate-200 shrink-0">
                                            <AvatarFallback class="bg-slate-100 text-slate-600 font-bold uppercase">{{ record.staff?.user?.name?.charAt(0) }}</AvatarFallback>
                                        </Avatar>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-bold text-slate-900 truncate">{{ record.staff?.user?.name }}</span>
                                            <div class="flex items-center gap-2 text-[10px] text-slate-500 font-medium mt-0.5">
                                                <span v-if="record.staff?.staff_number" class="font-mono font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{ record.staff.staff_number }}</span>
                                                <span class="uppercase tracking-wider truncate">{{ record.staff?.department?.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="font-mono text-sm font-bold text-slate-700 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="w-3 h-3 text-slate-400" />
                                        {{ formatTime(record.clock_in) }}
                                    </div>
                                </TableCell>
                                <TableCell class="font-mono text-sm font-bold text-slate-700 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="w-3 h-3 text-slate-400" />
                                        {{ formatTime(record.clock_out) }}
                                    </div>
                                </TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <Badge :class="['font-bold uppercase text-[10px] py-0.5 px-2 rounded-full', getStatusBadge(record.status).color]" variant="outline">
                                        <component :is="getStatusBadge(record.status).icon" class="w-3 h-3 mr-1" />
                                        {{ getStatusBadge(record.status).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <Badge variant="secondary" class="bg-slate-100 text-slate-600 border-none font-bold text-[10px] uppercase tracking-tighter">
                                        {{ record.source }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-xs">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[11px] font-medium text-slate-700">
                                            <span class="text-slate-400 font-normal">By:</span> {{ record.creator?.name || 'System / Auto' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono">
                                            {{ formatDateTime(record.created_at) }}
                                        </span>
                                        <div v-if="record.updater && record.updated_at && record.updated_at !== record.created_at" class="text-[10px] text-indigo-600 font-medium border-t border-slate-100 pt-0.5 mt-0.5">
                                            <span class="text-indigo-400 font-normal">Edit by:</span> {{ record.updater?.name }}
                                            <div class="text-[9px] text-slate-400 font-mono">{{ formatDateTime(record.updated_at) }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right whitespace-nowrap">
                                    <div class="flex justify-end gap-1 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-primary" @click="openEditModal(record)">
                                            <Edit2 class="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-destructive" @click="deleteRecord(record.id)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="attendances.data.length === 0">
                                <TableCell colspan="7" class="h-64 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="p-4 bg-slate-50 rounded-full border border-slate-100">
                                            <Clock class="w-10 h-10 text-slate-300" />
                                        </div>
                                        <div class="max-w-[250px]">
                                            <p class="font-bold text-slate-900 text-lg">No Attendance Data</p>
                                            <p class="text-sm text-slate-500">There are no records for {{ selectedDate }}. Upload a file or add entries manually.</p>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <CardFooter class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t p-4" v-if="attendances.total > 0">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ attendances.from }}</strong>-<strong>{{ attendances.to }}</strong> of <strong>{{ attendances.total }}</strong> attendance records
                    </div>
                    <div class="flex flex-wrap gap-1">
                         <Button 
                            v-for="(link, i) in attendances.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                            class="h-8 min-w-[32px] px-2 text-xs"
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>

            <!-- Manual Entry Dialog -->
            <Dialog v-model:open="showManualModal">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Add Manual Attendance</DialogTitle>
                        <DialogDescription>Create an attendance entry for a specific staff member.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-6 py-4">
                        <div class="grid gap-2">
                            <Label>Date</Label>
                            <Input type="date" v-model="manualForm.date" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Staff Member</Label>
                            <SearchableSelect
                                v-model="manualForm.staff_id"
                                :items="allStaff.map(s => ({ value: String(s.id), label: `${s.name} (${s.staff_number || 'N/A'})` }))"
                                placeholder="Search and select staff..."
                                searchPlaceholder="Search staff by name or ID..."
                                emptyText="No staff found."
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Clock In</Label>
                                <Input type="time" v-model="manualForm.clock_in" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Clock Out</Label>
                                <Input type="time" v-model="manualForm.clock_out" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <Select v-model="manualForm.status">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="present">Present</SelectItem>
                                    <SelectItem value="late">Late</SelectItem>
                                    <SelectItem value="absent">Absent</SelectItem>
                                    <SelectItem value="on_leave">On Leave</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitManual" :disabled="manualForm.processing" class="w-full">Save Attendance</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit Attendance Dialog -->
            <Dialog v-model:open="showEditModal">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Edit Attendance Record</DialogTitle>
                        <DialogDescription>Modify attendance details for {{ editingStaffName }}.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-6 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Clock In</Label>
                                <Input type="time" v-model="editForm.clock_in" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Clock Out</Label>
                                <Input type="time" v-model="editForm.clock_out" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <Select v-model="editForm.status">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="present">Present</SelectItem>
                                    <SelectItem value="late">Late</SelectItem>
                                    <SelectItem value="absent">Absent</SelectItem>
                                    <SelectItem value="on_leave">On Leave</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label>Notes / Reason for Adjustment</Label>
                            <Input v-model="editForm.notes" placeholder="Reason for manual adjustment..." />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitEdit" :disabled="editForm.processing" class="w-full bg-indigo-600 hover:bg-indigo-700">Update Attendance Record</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Holiday Dialog -->
            <Dialog v-model:open="showHolidayModal">
                <DialogContent class="sm:max-w-[450px]">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Umbrella class="w-5 h-5 text-indigo-600" /> Mark as Holiday
                        </DialogTitle>
                        <DialogDescription>Setting this date as a holiday will officially suspend attendance requirements.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-6 py-4">
                        <div class="grid gap-2">
                            <Label class="text-xs font-black uppercase text-slate-400">Selected Date</Label>
                            <Input type="date" v-model="holidayForm.date" readonly class="bg-slate-50 font-bold border-none" />
                        </div>
                        <div class="grid gap-2">
                            <Label class="text-xs font-black uppercase text-slate-400">Holiday Name</Label>
                            <Input v-model="holidayForm.name" placeholder="e.g. Eid-el-Kabir, Democracy Day" class="font-bold border-slate-200" />
                            <p v-if="holidayForm.errors.name" class="text-xs text-red-500">{{ holidayForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label class="text-xs font-black uppercase text-slate-400">Description (Optional)</Label>
                            <Input v-model="holidayForm.description" placeholder="Short note about the holiday..." class="border-slate-200 font-medium" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitHoliday" :disabled="holidayForm.processing || !holidayForm.name" class="w-full bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200">
                            {{ holidayForm.processing ? 'Saving...' : 'Mark Official Holiday' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- All Holidays Dialog -->
            <Dialog v-model:open="showAllHolidaysModal">
                <DialogContent class="max-w-[95vw] sm:max-w-[700px]">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Umbrella class="w-5 h-5 text-indigo-600" /> Declared Public Holidays
                        </DialogTitle>
                        <DialogDescription>Overview of all official public holidays declared in the system.</DialogDescription>
                    </DialogHeader>

                    <div class="py-4 max-h-[60vh] overflow-y-auto">
                        <div v-if="!holidays || holidays.length === 0" class="text-center py-8 text-slate-500">
                            <Umbrella class="w-10 h-10 mx-auto opacity-30 mb-2" />
                            <p class="font-bold text-sm">No holidays declared yet</p>
                        </div>
                        <Table v-else>
                            <TableHeader class="bg-slate-50">
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Holiday Name</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="h in holidays" :key="h.id">
                                    <TableCell class="font-mono font-bold text-xs text-indigo-600 whitespace-nowrap">
                                        {{ h.date ? h.date.substring(0, 10) : '' }}
                                    </TableCell>
                                    <TableCell class="font-bold text-slate-900">{{ h.name }}</TableCell>
                                    <TableCell class="text-xs text-slate-500 max-w-[200px] truncate">{{ h.description || '---' }}</TableCell>
                                    <TableCell class="text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-indigo-600" @click="openEditHolidayModal(h)">
                                                <Edit2 class="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-red-600" @click="removeHoliday(h.id)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <DialogFooter class="flex flex-col sm:flex-row justify-between items-center gap-2">
                        <Button variant="outline" @click="showAllHolidaysModal = false">Close</Button>
                        <Button @click="showAllHolidaysModal = false; showHolidayModal = true;" class="bg-indigo-600 hover:bg-indigo-700">
                            <Plus class="w-4 h-4 mr-2" /> Mark New Holiday
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit Holiday Dialog -->
            <Dialog v-model:open="showEditHolidayModal">
                <DialogContent class="sm:max-w-[450px]">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Edit2 class="w-5 h-5 text-indigo-600" /> Edit Public Holiday
                        </DialogTitle>
                        <DialogDescription>Update the date, name, or details of this public holiday.</DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-6 py-4">
                        <div class="grid gap-2">
                            <Label class="text-xs font-black uppercase text-slate-400">Holiday Date</Label>
                            <Input type="date" v-model="editHolidayForm.date" class="font-bold border-slate-200" />
                            <p v-if="editHolidayForm.errors.date" class="text-xs text-red-500">{{ editHolidayForm.errors.date }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label class="text-xs font-black uppercase text-slate-400">Holiday Name</Label>
                            <Input v-model="editHolidayForm.name" placeholder="e.g. Eid-el-Kabir, Democracy Day" class="font-bold border-slate-200" />
                            <p v-if="editHolidayForm.errors.name" class="text-xs text-red-500">{{ editHolidayForm.errors.name }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label class="text-xs font-black uppercase text-slate-400">Description (Optional)</Label>
                            <Input v-model="editHolidayForm.description" placeholder="Short note about the holiday..." class="border-slate-200 font-medium" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitEditHoliday" :disabled="editHolidayForm.processing || !editHolidayForm.name || !editHolidayForm.date" class="w-full bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200">
                            {{ editHolidayForm.processing ? 'Updating...' : 'Update Holiday Details' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Quick Attendance Ticking Sheet Modal -->
            <Dialog v-model:open="showQuickSheetModal">
                <DialogContent class="max-w-[95vw] lg:max-w-[1100px] max-h-[90vh] flex flex-col p-0 overflow-hidden rounded-2xl">
                    <DialogHeader class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <DialogTitle class="text-xl font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                    <CheckSquare class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                    Quick Staff Attendance Sheet
                                </DialogTitle>
                                <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                                    Easily tick Present, Absent, Late, or On Leave for active staff members for <span class="font-bold text-slate-900 dark:text-slate-100 font-mono">{{ selectedDate }}</span>
                                </DialogDescription>
                            </div>

                            <!-- Live Stat Badges -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <Badge variant="outline" class="bg-emerald-50 text-emerald-700 border-emerald-200 font-bold px-2.5 py-1">
                                    ✓ Present: {{ quickStats.present }}
                                </Badge>
                                <Badge variant="outline" class="bg-rose-50 text-rose-700 border-rose-200 font-bold px-2.5 py-1">
                                    ✗ Absent: {{ quickStats.absent }}
                                </Badge>
                                <Badge variant="outline" class="bg-amber-50 text-amber-700 border-amber-200 font-bold px-2.5 py-1">
                                    🕒 Late: {{ quickStats.late }}
                                </Badge>
                                <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 font-bold px-2.5 py-1">
                                    🏖 Leave: {{ quickStats.onLeave }}
                                </Badge>
                            </div>
                        </div>
                    </DialogHeader>

                    <!-- Sheet Controls & Filters -->
                    <div class="p-4 bg-slate-100/60 dark:bg-slate-900/40 border-b border-slate-200/60 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
                            <div class="relative flex-1 min-w-[200px]">
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input 
                                    v-model="quickSheetSearch" 
                                    placeholder="Filter staff by name or staff no..." 
                                    class="pl-9 h-9 text-xs bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800" 
                                />
                            </div>
                            <Select v-model="quickSheetDept">
                                <SelectTrigger class="w-[180px] h-9 text-xs bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800">
                                    <SelectValue placeholder="All Departments" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL" class="text-xs">All Departments</SelectItem>
                                    <SelectItem v-for="dept in departments" :key="dept.id" :value="String(dept.id)" class="text-xs">{{ dept.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Batch Quick Action Buttons -->
                        <div class="flex items-center gap-2">
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                class="h-9 text-xs font-bold bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100"
                                @click="markAllVisible('present')"
                            >
                                <UserCheck class="w-3.5 h-3.5 mr-1.5" /> Mark All Present
                            </Button>
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                class="h-9 text-xs font-bold bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100"
                                @click="markAllVisible('absent')"
                            >
                                <UserX class="w-3.5 h-3.5 mr-1.5" /> Mark All Absent
                            </Button>
                        </div>
                    </div>

                    <!-- Staff Interactive Ticking Table -->
                    <div class="flex-1 overflow-y-auto p-4 max-h-[50vh]">
                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                            <Table>
                                <TableHeader class="bg-slate-50 dark:bg-slate-900">
                                    <TableRow>
                                        <TableHead class="w-[50px]">#</TableHead>
                                        <TableHead>Staff Member</TableHead>
                                        <TableHead>Department</TableHead>
                                        <TableHead class="text-center w-[380px]">Attendance Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody class="divide-y">
                                    <TableRow v-for="(staff, idx) in paginatedQuickStaff" :key="staff.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                                        <TableCell class="text-xs text-muted-foreground font-mono">{{ (quickSheetPage - 1) * quickSheetPerPage + idx + 1 }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2.5">
                                                <Avatar class="h-8 w-8 border border-slate-200 dark:border-slate-800">
                                                    <AvatarFallback class="bg-indigo-100 text-indigo-700 font-bold text-xs">{{ staff.name.charAt(0) }}</AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p class="font-semibold text-xs text-slate-900 dark:text-slate-100 leading-tight">{{ staff.name }}</p>
                                                    <p class="text-[10px] text-muted-foreground font-mono">{{ staff.staff_number || 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-xs text-muted-foreground">
                                            {{ staff.department_name || 'General' }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <!-- Interactive Status Pills -->
                                            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 gap-1">
                                                <button 
                                                    type="button" 
                                                    @click="setStaffStatus(staff.id, 'present')"
                                                    :class="[
                                                        'px-3 py-1 text-xs font-bold rounded-lg transition-all flex items-center gap-1',
                                                        batchMap[staff.id]?.status === 'present' 
                                                            ? 'bg-emerald-600 text-white shadow-sm scale-105' 
                                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                                                    ]"
                                                >
                                                    <CheckCircle2 class="w-3.5 h-3.5" /> Present
                                                </button>

                                                <button 
                                                    type="button" 
                                                    @click="setStaffStatus(staff.id, 'absent')"
                                                    :class="[
                                                        'px-3 py-1 text-xs font-bold rounded-lg transition-all flex items-center gap-1',
                                                        batchMap[staff.id]?.status === 'absent' 
                                                            ? 'bg-rose-600 text-white shadow-sm scale-105' 
                                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                                                    ]"
                                                >
                                                    <XCircle class="w-3.5 h-3.5" /> Absent
                                                </button>

                                                <button 
                                                    type="button" 
                                                    @click="setStaffStatus(staff.id, 'late')"
                                                    :class="[
                                                        'px-3 py-1 text-xs font-bold rounded-lg transition-all flex items-center gap-1',
                                                        batchMap[staff.id]?.status === 'late' 
                                                            ? 'bg-amber-500 text-white shadow-sm scale-105' 
                                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                                                    ]"
                                                >
                                                    <Clock3 class="w-3.5 h-3.5" /> Late
                                                </button>

                                                <button 
                                                    type="button" 
                                                    @click="setStaffStatus(staff.id, 'on_leave')"
                                                    :class="[
                                                        'px-3 py-1 text-xs font-bold rounded-lg transition-all flex items-center gap-1',
                                                        batchMap[staff.id]?.status === 'on_leave' 
                                                            ? 'bg-blue-600 text-white shadow-sm scale-105' 
                                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                                                    ]"
                                                >
                                                    <Calendar class="w-3.5 h-3.5" /> Leave
                                                </button>
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="filteredQuickStaff.length === 0">
                                        <TableCell colspan="4" class="h-28 text-center text-xs text-muted-foreground">
                                            No active staff members found matching your filter.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Sheet Pagination Controls -->
                        <div v-if="filteredQuickStaff.length > quickSheetPerPage" class="flex items-center justify-between pt-3 pb-1 text-xs text-muted-foreground">
                            <span>Showing {{ (quickSheetPage - 1) * quickSheetPerPage + 1 }} to {{ Math.min(quickSheetPage * quickSheetPerPage, filteredQuickStaff.length) }} of {{ filteredQuickStaff.length }} staff</span>
                            <div class="flex items-center gap-2">
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    class="h-8 text-xs font-bold"
                                    :disabled="quickSheetPage <= 1"
                                    @click="quickSheetPage--"
                                >
                                    <ChevronLeft class="w-3.5 h-3.5 mr-1" /> Previous
                                </Button>
                                <span class="font-bold text-slate-700 dark:text-slate-300">Page {{ quickSheetPage }} of {{ totalQuickPages }}</span>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    class="h-8 text-xs font-bold"
                                    :disabled="quickSheetPage >= totalQuickPages"
                                    @click="quickSheetPage++"
                                >
                                    Next <ChevronRight class="w-3.5 h-3.5 ml-1" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <DialogFooter class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-row items-center justify-between">
                        <p class="text-xs text-muted-foreground hidden sm:block">
                            Submitting will save attendance logs for <strong class="text-slate-800 dark:text-slate-200">{{ allStaff.length }}</strong> staff members.
                        </p>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <Button type="button" variant="outline" class="rounded-xl font-bold" @click="showQuickSheetModal = false">
                                Cancel
                            </Button>
                            <Button 
                                type="button" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl px-6 shadow-md"
                                :disabled="isSubmittingBulk"
                                @click="saveBulkAttendance"
                            >
                                <Loader2 v-if="isSubmittingBulk" class="w-4 h-4 mr-2 animate-spin" />
                                <CheckCircle2 v-else class="w-4 h-4 mr-2" />
                                Save & Confirm All Attendance
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
