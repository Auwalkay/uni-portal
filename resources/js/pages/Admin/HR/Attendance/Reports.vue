<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { 
    BarChart3, 
    Calendar, 
    Download, 
    FileText, 
    Filter, 
    Users,
    ArrowLeft,
    PieChart,
    TrendingUp,
    AlertCircle,
    Building2,
    CalendarDays,
    Clock,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    Eye,
    ShieldAlert,
    Clock3
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';

const props = defineProps<{
    stats: Array<any>;
    departmentSummary: Array<any>;
    atRiskStaff: Array<any>;
    overallStats: {
        total_staff: number;
        avg_attendance_rate: number;
        avg_punctuality_rate: number;
        total_present: number;
        total_late: number;
        total_absent: number;
        total_leave: number;
        at_risk_count: number;
        total_hours_worked: number;
    };
    dateRange: { start: string; end: string };
    sessions: Array<any>;
    semesters: Array<any>;
    departments: Array<any>;
    reportTitle: string;
    filters: any;
}>();

const activeTab = ref<'staff' | 'departments' | 'at_risk'>('staff');
const reportType = ref(props.filters.type || 'monthly');
const selectedDate = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const selectedSession = ref(props.filters.session_id || '');
const selectedSemester = ref(props.filters.semester_id || '');
const selectedDept = ref(props.filters.department_id || 'ALL');

const updateReport = () => {
    router.get(route('admin.attendance.reports'), {
        type: reportType.value,
        date: selectedDate.value,
        session_id: selectedSession.value,
        semester_id: selectedSemester.value,
        department_id: selectedDept.value === 'ALL' ? '' : selectedDept.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([reportType, selectedDate, selectedSession, selectedSemester, selectedDept], () => {
    updateReport();
});

// Staff Detail History Drilldown Modal
const showHistoryModal = ref(false);
const selectedStaffMember = ref<any>(null);
const staffRecords = ref<Array<any>>([]);
const loadingHistory = ref(false);

const openStaffHistory = async (staffItem: any) => {
    selectedStaffMember.value = staffItem.staff;
    loadingHistory.value = true;
    showHistoryModal.value = true;
    staffRecords.value = [];

    try {
        const response = await axios.get(route('admin.attendance.staff.history', staffItem.staff_id), {
            params: {
                start_date: props.dateRange?.start,
                end_date: props.dateRange?.end,
            }
        });
        if (response.data.staff) {
            selectedStaffMember.value = response.data.staff;
        }
        staffRecords.value = response.data.records || [];
    } catch (e) {
        console.error('Failed to load staff history', e);
    } finally {
        loadingHistory.value = false;
    }
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '---';
    const cleanDate = dateStr.includes('T') ? dateStr.substring(0, 10) : dateStr;
    const parts = cleanDate.split('-');
    if (parts.length === 3) {
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const d = new Date(year, month, day);
        return d.toLocaleDateString('en-US', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }
    return dateStr;
};

const formatTime12h = (timeStr: string) => {
    if (!timeStr) return '---';
    if (timeStr.includes('AM') || timeStr.includes('PM')) return timeStr;
    const parts = timeStr.split(':');
    if (parts.length < 2) return timeStr;
    let hours = parseInt(parts[0], 10);
    const minutes = parts[1];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return `${hours.toString().padStart(2, '0')}:${minutes} ${ampm}`;
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'present': return { label: 'Present', class: 'bg-green-100 text-green-800 border-green-200' };
        case 'late': return { label: 'Late', class: 'bg-amber-100 text-amber-800 border-amber-200' };
        case 'absent': return { label: 'Absent', class: 'bg-red-100 text-red-800 border-red-200' };
        case 'on_leave': return { label: 'On Leave', class: 'bg-blue-100 text-blue-800 border-blue-200' };
        default: return { label: status, class: 'bg-slate-100 text-slate-800 border-slate-200' };
    }
};
</script>

<template>
    <Head title="Attendance Analytics & Compliance Report" />

    <AdminLayout>
        <div class="py-12 px-8 space-y-10 w-full max-w-[1600px] mx-auto animate-in fade-in duration-700">
            
            <!-- Elegant Header -->
            <div class="flex flex-col md:flex-row justify-between items-end gap-6 border-b border-slate-100 pb-8">
                <div class="space-y-2">
                    <div class="flex items-center gap-3 text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">
                        <Link :href="route('admin.attendance.index')" class="hover:text-primary transition-colors flex items-center gap-1">
                            <ArrowLeft class="w-3 h-3" /> Dashboard
                        </Link>
                        <span>/</span>
                        <span class="text-slate-900">Attendance & Compliance Analytics</span>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900">Attendance Analytics</h1>
                    <p class="text-slate-500 font-medium tracking-tight flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-indigo-500" /> {{ reportTitle }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button variant="outline" class="h-11 px-6 rounded-xl border-slate-200 font-bold shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600" as-child>
                        <a :href="route('admin.attendance.export', { ...filters, format: 'pdf' })" target="_blank">
                            <Download class="w-4 h-4 mr-2 text-indigo-600" /> Export PDF
                        </a>
                    </Button>
                    <Button variant="default" class="h-11 px-6 rounded-xl font-bold bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200" as-child>
                        <a :href="route('admin.attendance.export', { ...filters, format: 'excel' })">
                            <FileText class="w-4 h-4 mr-2" /> Export Excel
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Smart KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6">
                <!-- Card 1: Attendance Rate -->
                <Card class="border-none shadow-xl shadow-indigo-100 bg-gradient-to-br from-indigo-600 to-violet-700 text-white overflow-hidden relative group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <TrendingUp class="w-32 h-32" />
                    </div>
                    <CardContent class="p-6 space-y-4">
                        <p class="text-indigo-100 font-bold text-xs uppercase tracking-widest">Overall Attendance</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black">{{ overallStats?.avg_attendance_rate || 0 }}%</span>
                            <span class="text-indigo-200 text-xs font-bold">Avg Rate</span>
                        </div>
                        <div class="w-full h-1.5 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-white transition-all duration-1000" :style="{ width: (overallStats?.avg_attendance_rate || 0) + '%' }"></div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2: Punctuality Score -->
                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Punctuality Score</p>
                            <div class="p-2 bg-emerald-50 rounded-lg"><Clock3 class="w-4 h-4 text-emerald-600" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900">{{ overallStats?.avg_punctuality_rate || 0 }}%</h2>
                        <p class="text-[10px] text-emerald-600 font-bold">On-Time Arrival Index</p>
                    </CardContent>
                </Card>

                <!-- Card 3: Total Absences -->
                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Unlogged Absences</p>
                            <div class="p-2 bg-red-50 rounded-lg"><AlertCircle class="w-4 h-4 text-red-600" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900">{{ overallStats?.total_absent || 0 }}</h2>
                        <p class="text-[10px] text-red-600 font-bold">Absent Log Entries</p>
                    </CardContent>
                </Card>

                <!-- Card 4: Total Work Hours -->
                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Hours Logged</p>
                            <div class="p-2 bg-blue-50 rounded-lg"><Clock class="w-4 h-4 text-blue-600" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900">{{ overallStats?.total_hours_worked || 0 }}h</h2>
                        <p class="text-[10px] text-blue-600 font-bold">Total Staff Work Time</p>
                    </CardContent>
                </Card>

                <!-- Card 5: At-Risk Staff -->
                <Card class="border-none shadow-lg shadow-amber-100 bg-amber-50/50 border border-amber-200/60">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-amber-800 font-bold text-xs uppercase tracking-widest">At-Risk Staff</p>
                            <div class="p-2 bg-amber-100 rounded-lg"><ShieldAlert class="w-4 h-4 text-amber-700" /></div>
                        </div>
                        <h2 class="text-3xl font-black text-amber-950">{{ overallStats?.at_risk_count || 0 }}</h2>
                        <p class="text-[10px] text-amber-700 font-bold">Low Attendance / High Absences</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Configuration & Filter Bar -->
            <div class="bg-slate-50/70 p-2 rounded-2xl border border-slate-200 flex flex-wrap items-center gap-3">
                <div class="px-3 py-1 flex items-center gap-2">
                    <Filter class="w-4 h-4 text-slate-400" />
                    <span class="text-xs font-black uppercase text-slate-500 tracking-wider">Report Filters</span>
                </div>
                
                <Select v-model="reportType">
                    <SelectTrigger class="w-48 h-10 border-slate-200 bg-white rounded-xl font-bold text-xs shadow-sm">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="monthly">Monthly Report</SelectItem>
                        <SelectItem value="weekly">Weekly Report</SelectItem>
                        <SelectItem value="session">Academic Session</SelectItem>
                        <SelectItem value="semester">Academic Semester</SelectItem>
                    </SelectContent>
                </Select>

                <div v-if="reportType === 'monthly' || reportType === 'weekly'">
                    <Input type="date" v-model="selectedDate" class="w-44 h-10 border-slate-200 bg-white rounded-xl font-bold text-xs shadow-sm" />
                </div>

                <Select v-if="reportType === 'session'" v-model="selectedSession">
                    <SelectTrigger class="w-48 h-10 border-slate-200 bg-white rounded-xl font-bold text-xs shadow-sm">
                        <SelectValue placeholder="Select Session" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-if="reportType === 'semester'" v-model="selectedSemester">
                    <SelectTrigger class="w-48 h-10 border-slate-200 bg-white rounded-xl font-bold text-xs shadow-sm">
                        <SelectValue placeholder="Select Semester" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedDept">
                    <SelectTrigger class="w-60 h-10 border-slate-200 bg-white rounded-xl font-bold text-xs shadow-sm">
                        <SelectValue placeholder="All Departments" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">All Departments</SelectItem>
                        <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Interactive Tabbed Views -->
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div class="flex items-center gap-2">
                        <button 
                            @click="activeTab = 'staff'"
                            :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', activeTab === 'staff' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
                        >
                            <Users class="w-4 h-4" /> Staff Member Performance ({{ stats?.length || 0 }})
                        </button>
                        <button 
                            @click="activeTab = 'departments'"
                            :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', activeTab === 'departments' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
                        >
                            <Building2 class="w-4 h-4" /> Departmental Summary ({{ departmentSummary?.length || 0 }})
                        </button>
                        <button 
                            @click="activeTab = 'at_risk'"
                            :class="['px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2', activeTab === 'at_risk' ? 'bg-amber-600 text-white shadow-md shadow-amber-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
                        >
                            <ShieldAlert class="w-4 h-4" /> At-Risk Compliance Flag ({{ atRiskStaff?.length || 0 }})
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Staff Performance Table -->
                <Card v-if="activeTab === 'staff'" class="border-none shadow-xl shadow-slate-200/40 overflow-hidden bg-white rounded-3xl">
                    <Table>
                        <TableHeader class="bg-slate-50">
                            <TableRow class="hover:bg-transparent border-slate-100">
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-slate-400">Staff ID</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-slate-400">Staff Member</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Days Logged</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Attendance Breakdown</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Avg Clock In</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Work Hours</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-right">Punctuality</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-slate-400 text-right">Attendance Rate</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-slate-400 text-center">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="staff in stats" :key="staff.staff_id" class="border-slate-50 group hover:bg-slate-50/70 transition-all">
                                <TableCell class="py-4 px-6 font-mono text-xs font-black text-indigo-600 tracking-tighter">{{ staff.staff?.staff_number }}</TableCell>
                                <TableCell class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-900 group-hover:text-indigo-600 transition-colors">{{ staff.staff?.user?.name }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ staff.staff?.department?.name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="py-4 text-center font-bold text-slate-700">{{ staff.total_days }}</TableCell>
                                <TableCell class="py-4">
                                    <div class="flex justify-center items-center gap-2">
                                        <Badge class="bg-green-50 text-green-700 border-green-200 font-bold text-[10px] px-2 py-0.5">
                                            {{ staff.present_count }} Present
                                        </Badge>
                                        <Badge class="bg-amber-50 text-amber-700 border-amber-200 font-bold text-[10px] px-2 py-0.5">
                                            {{ staff.late_count }} Late
                                        </Badge>
                                        <Badge class="bg-red-50 text-red-700 border-red-200 font-bold text-[10px] px-2 py-0.5">
                                            {{ staff.absent_count }} Absent
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="py-4 text-center font-bold text-xs text-slate-800">{{ staff.avg_clock_in }}</TableCell>
                                <TableCell class="py-4 text-center font-bold text-xs text-slate-700">{{ staff.total_hours_formatted }}</TableCell>
                                <TableCell class="py-4 text-right font-bold text-xs text-emerald-600">{{ staff.punctuality_rate }}%</TableCell>
                                <TableCell class="py-4 px-6 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <span :class="['text-sm font-black', staff.rate >= 80 ? 'text-green-600' : (staff.rate >= 50 ? 'text-amber-600' : 'text-red-600')]">
                                            {{ staff.rate }}%
                                        </span>
                                        <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all duration-500" 
                                                :style="{ 
                                                    width: staff.rate + '%',
                                                    backgroundColor: staff.rate >= 80 ? '#16a34a' : (staff.rate >= 50 ? '#d97706' : '#dc2626')
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="py-4 px-6 text-center">
                                    <Button variant="ghost" size="sm" class="h-8 text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50" @click="openStaffHistory(staff)">
                                        <Eye class="w-3.5 h-3.5 mr-1" /> View Logs
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="stats?.length === 0">
                                <TableCell colspan="9" class="py-16 text-center text-slate-400 font-bold">
                                    No staff attendance data found for the selected filter criteria.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <!-- Tab 2: Department Summary Table -->
                <Card v-if="activeTab === 'departments'" class="border-none shadow-xl shadow-slate-200/40 overflow-hidden bg-white rounded-3xl">
                    <Table>
                        <TableHeader class="bg-slate-50">
                            <TableRow class="hover:bg-transparent border-slate-100">
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-slate-400">Department Name</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Active Staff</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Total Recorded Days</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Total Present</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Total Late</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-center">Total Absent</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-slate-400 text-right">Avg Punctuality %</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-slate-400 text-right">Avg Attendance Rate</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="dept in departmentSummary" :key="dept.department_name" class="border-slate-50 hover:bg-slate-50/70 transition-all">
                                <TableCell class="py-4 px-6 font-black text-slate-900">{{ dept.department_name }}</TableCell>
                                <TableCell class="py-4 text-center font-bold text-slate-700">{{ dept.staff_count }}</TableCell>
                                <TableCell class="py-4 text-center font-bold text-slate-700">{{ dept.total_days }}</TableCell>
                                <TableCell class="py-4 text-center font-bold text-green-600">{{ dept.present_count }}</TableCell>
                                <TableCell class="py-4 text-center font-bold text-amber-600">{{ dept.late_count }}</TableCell>
                                <TableCell class="py-4 text-center font-bold text-red-600">{{ dept.absent_count }}</TableCell>
                                <TableCell class="py-4 text-right font-bold text-xs text-emerald-600">{{ dept.avg_punctuality }}%</TableCell>
                                <TableCell class="py-4 px-6 text-right">
                                    <span :class="['text-sm font-black', dept.avg_rate >= 80 ? 'text-green-600' : 'text-amber-600']">
                                        {{ dept.avg_rate }}%
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <!-- Tab 3: At-Risk Staff Table -->
                <Card v-if="activeTab === 'at_risk'" class="border-none shadow-xl shadow-slate-200/40 overflow-hidden bg-white rounded-3xl">
                    <Table>
                        <TableHeader class="bg-amber-50/50">
                            <TableRow class="hover:bg-transparent border-amber-100">
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-amber-800">Staff ID</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-amber-800">Staff Member</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-amber-800 text-center">Unlogged Absences</TableHead>
                                <TableHead class="py-4 text-xs font-black uppercase text-amber-800 text-center">Punctuality Score</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-amber-800 text-right">Attendance Rate</TableHead>
                                <TableHead class="py-4 px-6 text-xs font-black uppercase text-amber-800 text-center">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="staff in atRiskStaff" :key="staff.staff_id" class="border-slate-50 hover:bg-amber-50/30 transition-all">
                                <TableCell class="py-4 px-6 font-mono text-xs font-black text-amber-900">{{ staff.staff?.staff_number }}</TableCell>
                                <TableCell class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-900">{{ staff.staff?.user?.name }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ staff.staff?.department?.name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="py-4 text-center font-black text-red-600">{{ staff.absent_count }} days</TableCell>
                                <TableCell class="py-4 text-center font-bold text-xs text-amber-700">{{ staff.punctuality_rate }}%</TableCell>
                                <TableCell class="py-4 px-6 text-right font-black text-red-600">{{ staff.rate }}%</TableCell>
                                <TableCell class="py-4 px-6 text-center">
                                    <Button variant="ghost" size="sm" class="h-8 text-xs font-bold text-amber-800 hover:text-amber-950 hover:bg-amber-100" @click="openStaffHistory(staff)">
                                        <Eye class="w-3.5 h-3.5 mr-1" /> View Timeline
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="atRiskStaff?.length === 0">
                                <TableCell colspan="6" class="py-16 text-center text-green-700 font-bold">
                                    <CheckCircle2 class="w-8 h-8 mx-auto mb-2 text-green-600" />
                                    Great news! No staff members are currently flagged as at-risk for low attendance.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>

            <!-- Staff History Log Drilldown Modal -->
            <Dialog v-model:open="showHistoryModal">
                <DialogContent class="max-w-[95vw] sm:max-w-[750px]">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Clock class="w-5 h-5 text-indigo-600" /> Detailed Attendance Log History
                        </DialogTitle>
                        <DialogDescription v-if="selectedStaffMember">
                            Date-by-date attendance timeline for <strong>{{ selectedStaffMember?.user?.name || selectedStaffMember?.name || 'Staff Member' }}</strong> ({{ selectedStaffMember?.staff_number || selectedStaffMember?.user?.email || '' }})
                        </DialogDescription>
                    </DialogHeader>

                    <div class="py-4 max-h-[60vh] overflow-y-auto">
                        <div v-if="loadingHistory" class="text-center py-8 text-slate-500 font-bold">
                            Loading attendance records...
                        </div>
                        <div v-else-if="staffRecords.length === 0" class="text-center py-8 text-slate-400 font-bold">
                            No detailed daily logs found for this period.
                        </div>
                        <Table v-else>
                            <TableHeader class="bg-slate-50">
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-center">Clock In</TableHead>
                                    <TableHead class="text-center">Clock Out</TableHead>
                                    <TableHead class="text-center">Hours Worked</TableHead>
                                    <TableHead>Notes</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="rec in staffRecords" :key="rec.id">
                                    <TableCell class="font-bold text-xs text-slate-900 whitespace-nowrap">
                                        {{ rec.formatted_date || formatDate(rec.date) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadge(rec.status).class" class="font-bold text-[10px] uppercase">
                                            {{ getStatusBadge(rec.status).label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center font-bold text-xs text-slate-700 whitespace-nowrap">
                                        {{ rec.formatted_clock_in || formatTime12h(rec.clock_in) }}
                                    </TableCell>
                                    <TableCell class="text-center font-bold text-xs text-slate-700 whitespace-nowrap">
                                        {{ rec.formatted_clock_out || formatTime12h(rec.clock_out) }}
                                    </TableCell>
                                    <TableCell class="text-center font-bold text-xs text-indigo-600">
                                        {{ rec.formatted_hours }}
                                    </TableCell>
                                    <TableCell class="text-xs text-slate-500 max-w-[150px] truncate">
                                        {{ rec.notes || '---' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="showHistoryModal = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
