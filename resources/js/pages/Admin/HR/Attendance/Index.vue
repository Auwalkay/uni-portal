<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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
    PartyPopper
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

const props = defineProps<{
    attendances: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    departments: Array<{ id: string; name: string }>;
    allStaff: Array<{ id: string; name: string }>;
    holiday: any;
    filters: {
        date?: string;
        department_id?: string;
        status?: string;
    };
}>();

const selectedDate = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const selectedDept = ref(props.filters.department_id || 'ALL');
const selectedStatus = ref(props.filters.status || 'ALL');

// Watchers for filtering
watch([selectedDate, selectedDept, selectedStatus], () => {
    router.get(route('admin.attendance.index'), {
        date: selectedDate.value,
        department_id: selectedDept.value === 'ALL' ? '' : selectedDept.value,
        status: selectedStatus.value === 'ALL' ? '' : selectedStatus.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
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

                <div class="flex gap-2">
                    <Button variant="outline" as-child class="border-slate-200 bg-white">
                        <Link :href="route('admin.attendance.reports')">
                            <BarChart3 class="w-4 h-4 mr-2" /> View Reports
                        </Link>
                    </Button>

                    <Button variant="outline" as-child class="border-slate-200 bg-white">
                        <Link :href="route('admin.attendance.calendar')">
                            <LayoutGrid class="w-4 h-4 mr-2" /> Calendar View
                        </Link>
                    </Button>

                    <Dialog v-model:open="showImportModal">
                        <DialogTrigger as-child>
                            <Button variant="outline" class="border-slate-200 bg-white">
                                <Upload class="w-4 h-4 mr-2" /> Bulk Import
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
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

                    <Button @click="showManualModal = true" :disabled="!!holiday">
                        <Plus class="w-4 h-4 mr-2" /> Add Record
                    </Button>
                </div>
            </div>

            <!-- Holiday Banner -->
            <div v-if="holiday" class="bg-indigo-600 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-indigo-200 group animate-in fade-in slide-in-from-top duration-500">
                <div class="absolute -right-8 -bottom-8 opacity-10 group-hover:scale-110 transition-transform duration-700">
                    <PartyPopper class="w-48 h-48" />
                </div>
                <div class="relative flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
                    <div class="space-y-2">
                        <div class="flex items-center justify-center md:justify-start gap-3">
                            <span class="p-2 bg-white/20 rounded-xl backdrop-blur-md">
                                <Umbrella class="w-6 h-6" />
                            </span>
                            <h2 class="text-2xl font-black uppercase tracking-tight">Public Holiday: {{ holiday.name }}</h2>
                        </div>
                        <p class="text-indigo-100 font-medium max-w-2xl">{{ holiday.description || 'All university operations are suspended for this date.' }}</p>
                    </div>
                    <Button variant="outline" class="bg-white/10 border-white/20 hover:bg-white/20 text-white font-bold px-6 rounded-xl" @click="removeHoliday(holiday.id)">
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
            <div class="grid gap-4 md:grid-cols-4 items-end">
                <Card class="md:col-span-1 shadow-sm border-slate-200">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs uppercase tracking-wider text-slate-500 font-bold">Attendance Date</CardTitle>
                    </CardHeader>
                    <CardContent class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-primary" />
                        <Input type="date" v-model="selectedDate" class="border-none p-0 focus-visible:ring-0 text-lg font-bold" />
                    </CardContent>
                </Card>

                <div class="md:col-span-3 flex flex-col md:flex-row gap-4 items-center">
                    <div class="w-full">
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

                    <div class="w-full">
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
                </div>
            </div>

            <!-- Attendance Table -->
            <Card class="border-slate-200 shadow-sm overflow-hidden bg-white">
                <Table>
                    <TableHeader class="bg-slate-50">
                        <TableRow>
                            <TableHead class="w-[300px]">Staff Member</TableHead>
                            <TableHead>Clock In</TableHead>
                            <TableHead>Clock Out</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Source</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="record in attendances.data" :key="record.id" class="group hover:bg-slate-50/50">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-10 w-10 border border-slate-200">
                                        <AvatarFallback class="bg-slate-100 text-slate-600 font-bold uppercase">{{ record.staff?.user?.name?.charAt(0) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900">{{ record.staff?.user?.name }}</span>
                                        <span class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">{{ record.staff?.department?.name }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="font-mono text-sm font-bold text-slate-700">
                                <div class="flex items-center gap-1.5">
                                    <Clock class="w-3 h-3 text-slate-400" />
                                    {{ formatTime(record.clock_in) }}
                                </div>
                            </TableCell>
                            <TableCell class="font-mono text-sm font-bold text-slate-700">
                                <div class="flex items-center gap-1.5">
                                    <Clock class="w-3 h-3 text-slate-400" />
                                    {{ formatTime(record.clock_out) }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :class="['font-bold uppercase text-[10px] py-0.5 px-2 rounded-full', getStatusBadge(record.status).color]" variant="outline">
                                    <component :is="getStatusBadge(record.status).icon" class="w-3 h-3 mr-1" />
                                    {{ getStatusBadge(record.status).label }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary" class="bg-slate-100 text-slate-600 border-none font-bold text-[10px] uppercase tracking-tighter">
                                    {{ record.source }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-primary">
                                        <Edit2 class="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-destructive" @click="deleteRecord(record.id)">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="attendances.data.length === 0">
                            <TableCell colspan="6" class="h-64 text-center">
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
                            <Select v-model="manualForm.staff_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Staff" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="staff in allStaff" :key="staff.id" :value="staff.id">{{ staff.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[10px] text-slate-400 font-medium">Tip: Use the search in the dropdown to find staff quickly.</p>
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
