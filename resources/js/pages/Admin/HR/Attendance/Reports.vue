<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
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
    CalendarDays
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
import { Label } from '@/components/ui/label';

const props = defineProps<{
    stats: Array<any>;
    sessions: Array<any>;
    semesters: Array<any>;
    departments: Array<any>;
    reportTitle: string;
    filters: any;
}>();

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

const getAttendanceRate = (staff: any) => {
    if (staff.total_days == 0) return 0;
    return Math.round((staff.present_count / staff.total_days) * 100);
};

const overallAverage = computed(() => {
    if (props.stats.length === 0) return 0;
    const total = props.stats.reduce((acc, curr) => acc + getAttendanceRate(curr), 0);
    return Math.round(total / props.stats.length);
});

const totalPresent = computed(() => props.stats.reduce((acc, curr) => acc + Number(curr.present_count), 0));
const totalAbsent = computed(() => props.stats.reduce((acc, curr) => acc + Number(curr.absent_count), 0));

</script>

<template>
    <Head title="Attendance Analytics" />

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
                        <span class="text-slate-900">Analytics</span>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-slate-900">Attendance Analytics</h1>
                    <p class="text-slate-500 font-medium tracking-tight flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-slate-300" /> {{ reportTitle }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button variant="outline" class="h-11 px-6 rounded-xl border-slate-200 font-bold shadow-sm" as-child>
                        <a :href="route('admin.attendance.export', { ...filters, format: 'pdf' })">
                            <Download class="w-4 h-4 mr-2" /> PDF Report
                        </a>
                    </Button>
                    <Button variant="default" class="h-11 px-6 rounded-xl font-bold shadow-md shadow-primary/20" as-child>
                        <a :href="route('admin.attendance.export', { ...filters, format: 'excel' })">
                            <FileText class="w-4 h-4 mr-2" /> Excel Data
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Smart Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <Card class="border-none shadow-xl shadow-slate-200/40 bg-gradient-to-br from-indigo-600 to-violet-700 text-white overflow-hidden relative group">
                    <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <TrendingUp class="w-32 h-32" />
                    </div>
                    <CardContent class="p-6 space-y-4">
                        <p class="text-indigo-100 font-bold text-xs uppercase tracking-widest">Average Attendance</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-black">{{ overallAverage }}%</span>
                            <span class="text-indigo-200 text-sm font-bold">Overall</span>
                        </div>
                        <div class="w-full h-1.5 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-white animate-in slide-in-from-left duration-1000" :style="{ width: overallAverage + '%' }"></div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Total Presence</p>
                            <div class="p-2 bg-green-50 rounded-lg"><Users class="w-4 h-4 text-green-600" /></div>
                        </div>
                        <h2 class="text-4xl font-black text-slate-900">{{ totalPresent }}</h2>
                        <p class="text-[10px] text-green-600 font-bold">Confirmed Attendance Logs</p>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Total Absences</p>
                            <div class="p-2 bg-red-50 rounded-lg"><AlertCircle class="w-4 h-4 text-red-600" /></div>
                        </div>
                        <h2 class="text-4xl font-black text-slate-900">{{ totalAbsent }}</h2>
                        <p class="text-[10px] text-red-600 font-bold">Requires Attention</p>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg shadow-slate-200/50 bg-white">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Departments</p>
                            <div class="p-2 bg-blue-50 rounded-lg"><Building2 class="w-4 h-4 text-blue-600" /></div>
                        </div>
                        <h2 class="text-4xl font-black text-slate-900">{{ departments.length }}</h2>
                        <p class="text-[10px] text-blue-600 font-bold">Active Academic Units</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Mature Filter Section -->
            <div class="bg-slate-50/50 p-1.5 rounded-2xl border border-slate-100 flex flex-wrap items-center gap-1.5">
                <div class="px-4 py-2 flex items-center gap-3">
                    <Filter class="w-4 h-4 text-slate-400" />
                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-tighter">View Configuration</span>
                </div>
                
                <Select v-model="reportType">
                    <SelectTrigger class="w-44 h-11 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="monthly">Monthly Report</SelectItem>
                        <SelectItem value="weekly">Weekly Report</SelectItem>
                        <SelectItem value="session">Session Summary</SelectItem>
                        <SelectItem value="semester">Semester Summary</SelectItem>
                    </SelectContent>
                </Select>

                <div v-if="reportType === 'monthly' || reportType === 'weekly'" class="flex items-center gap-2">
                    <Input type="date" v-model="selectedDate" class="w-44 h-11 border-none bg-white rounded-xl shadow-sm font-bold text-sm" />
                </div>

                <Select v-if="reportType === 'session'" v-model="selectedSession">
                    <SelectTrigger class="w-44 h-11 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="Select Session" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-if="reportType === 'semester'" v-model="selectedSemester">
                    <SelectTrigger class="w-44 h-11 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="Select Semester" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedDept">
                    <SelectTrigger class="w-64 h-11 border-none bg-white rounded-xl shadow-sm font-bold text-sm">
                        <SelectValue placeholder="All Departments" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">All Departments</SelectItem>
                        <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                    </SelectContent>
                </Select>

                <div class="ml-auto pr-2">
                    <Button variant="ghost" size="icon" @click="updateReport" class="h-11 w-11 rounded-xl text-slate-400 hover:text-primary">
                        <TrendingUp class="w-5 h-5" />
                    </Button>
                </div>
            </div>

            <!-- Smart Data Table -->
            <Card class="border-none shadow-2xl shadow-slate-200/30 overflow-hidden bg-white rounded-3xl">
                <Table>
                    <TableHeader class="bg-slate-50/50">
                        <TableRow class="hover:bg-transparent border-slate-100">
                            <TableHead class="py-6 px-8 text-xs font-black uppercase text-slate-400 tracking-widest">ID</TableHead>
                            <TableHead class="py-6 px-8 text-xs font-black uppercase text-slate-400 tracking-widest">Staff Member</TableHead>
                            <TableHead class="py-6 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Engagement</TableHead>
                            <TableHead class="py-6 text-xs font-black uppercase text-slate-400 tracking-widest text-center">Status Mix</TableHead>
                            <TableHead class="py-6 px-8 text-xs font-black uppercase text-slate-400 tracking-widest text-right">Reliability</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="staff in stats" :key="staff.staff_id" class="border-slate-50 group hover:bg-slate-50/50 transition-all">
                            <TableCell class="py-6 px-8 font-mono text-xs font-black text-slate-400 tracking-tighter">{{ staff.staff?.staff_number }}</TableCell>
                            <TableCell class="py-6 px-8">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-900 group-hover:text-primary transition-colors">{{ staff.staff?.user?.name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ staff.staff?.department?.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="py-6 text-center font-bold text-slate-600">
                                <div class="flex flex-col items-center">
                                    <span class="text-sm">{{ staff.total_days }}</span>
                                    <span class="text-[9px] text-slate-300 uppercase tracking-tighter">Total Active Days</span>
                                </div>
                            </TableCell>
                            <TableCell class="py-6">
                                <div class="flex justify-center items-center gap-1.5">
                                    <div class="flex flex-col items-center w-12 border-r border-slate-100 last:border-0 px-2">
                                        <span class="text-xs font-black text-green-600">{{ staff.present_count }}</span>
                                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-tight">Pres</span>
                                    </div>
                                    <div class="flex flex-col items-center w-12 border-r border-slate-100 last:border-0 px-2">
                                        <span class="text-xs font-black text-amber-600">{{ staff.late_count }}</span>
                                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-tight">Late</span>
                                    </div>
                                    <div class="flex flex-col items-center w-12 border-r border-slate-100 last:border-0 px-2">
                                        <span class="text-xs font-black text-red-600">{{ staff.absent_count }}</span>
                                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-tight">Abs</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="py-6 px-8 text-right">
                                <div class="flex flex-col items-end gap-1.5">
                                    <div class="flex items-baseline gap-1">
                                        <span :class="['text-lg font-black tracking-tighter', getAttendanceRate(staff) >= 80 ? 'text-green-600' : 'text-amber-600']">
                                            {{ getAttendanceRate(staff) }}%
                                        </span>
                                    </div>
                                    <div class="w-32 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full bg-slate-900 rounded-full transition-all duration-1000" 
                                            :style="{ 
                                                width: getAttendanceRate(staff) + '%',
                                                backgroundColor: getAttendanceRate(staff) >= 80 ? '#16a34a' : (getAttendanceRate(staff) >= 50 ? '#d97706' : '#dc2626')
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="stats.length === 0">
                            <TableCell colspan="5" class="py-32 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center border border-slate-100">
                                        <PieChart class="w-8 h-8 text-slate-200" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-black text-slate-900">Insufficient Data</p>
                                        <p class="text-sm text-slate-400 max-w-[280px] mx-auto font-medium">Try adjusting your filters or timeframe to view attendance analytics.</p>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* Smooth transitions */
.animate-in {
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
