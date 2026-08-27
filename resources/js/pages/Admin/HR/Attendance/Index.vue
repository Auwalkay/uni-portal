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
    ArrowDown
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
    allStaff: Array<{ id: string; name: string; staff_number?: string }>;
    holiday: any;
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
                    <Button variant="outline" class="bg-white/10 border-white/20 hover:bg-white/20 text-white font-bold px-6 rounded-xl w-full md:w-auto" @click="removeHoliday(holiday.id)">
                        <Trash2 class="w-4 h-4 mr-2" /> Remove Holiday
                    </Button>
                </div>
            </div>

            <div v-else-if="!holiday" class="flex justify-end">
                <Button variant="outline" size="sm" class="h-9 border-slate-200 text-slate-500 font-bold px-4 rounded-xl hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50" @click="showHolidayModal = true">
                    <Umbrella class="w-4 h-4 mr-2" /> Mark as Holiday
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
                                <TableHead class="min-w-[170px]">Audit Trail</TableHead>
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

        </div>
    </AdminLayout>
</template>
