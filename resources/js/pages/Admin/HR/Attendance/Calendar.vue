<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    Calendar as CalendarIcon, 
    ChevronLeft, 
    ChevronRight,
    ArrowLeft,
    LayoutGrid,
    Search,
    Filter,
    Umbrella
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
} from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip"

const props = defineProps<{
    staffList: Array<any>;
    attendances: Record<string, Record<string, any>>;
    daysInMonth: number;
    currentMonth: string;
    selectedDate: string;
    departments: Array<any>;
    holidays: Record<string, any>;
    filters: any;
}>();

const selectedMonth = ref(props.selectedDate);
const selectedDept = ref(props.filters.department_id || 'ALL');

const days = computed(() => {
    const arr = [];
    const baseDate = new Date(selectedMonth.value);
    for (let i = 1; i <= props.daysInMonth; i++) {
        const d = new Date(baseDate.getFullYear(), baseDate.getMonth(), i);
        arr.push({
            day: i,
            dateString: d.toISOString().split('T')[0],
            isWeekend: d.getDay() === 0 || d.getDay() === 6
        });
    }
    return arr;
});

const updateCalendar = () => {
    router.get(route('admin.attendance.calendar'), {
        date: selectedMonth.value,
        department_id: selectedDept.value === 'ALL' ? '' : selectedDept.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([selectedMonth, selectedDept], () => {
    updateCalendar();
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'present': return 'bg-green-500';
        case 'late': return 'bg-amber-500';
        case 'absent': return 'bg-red-500';
        case 'on_leave': return 'bg-blue-500';
        default: return 'bg-slate-200';
    }
};

const getStatusLabel = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

</script>

<template>
    <Head title="Attendance Calendar" />

    <AdminLayout>
        <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="route('admin.attendance.index')">
                            <ArrowLeft class="w-4 h-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Attendance Calendar</h1>
                        <p class="text-muted-foreground mt-1">{{ currentMonth }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-2 px-2 border-r border-slate-100">
                        <Label class="text-[10px] font-bold uppercase text-slate-400">Month</Label>
                        <Input type="month" v-model="selectedMonth" class="border-none h-8 w-40 focus-visible:ring-0 font-bold" />
                    </div>
                    <div class="flex items-center gap-2 px-2">
                        <Label class="text-[10px] font-bold uppercase text-slate-400">Dept</Label>
                        <Select v-model="selectedDept">
                            <SelectTrigger class="border-none h-8 w-48 focus:ring-0 shadow-none font-bold">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Departments</SelectItem>
                                <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-6 p-4 bg-slate-50 rounded-xl border border-slate-200/60 shadow-inner text-xs font-bold uppercase tracking-wider text-slate-500">
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-green-500"></div> Present</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-amber-500"></div> Late</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-red-500"></div> Absent</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-blue-500"></div> On Leave</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-slate-200"></div> No Record</div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded bg-slate-100 border border-slate-200"></div> Weekend</div>
                <div class="flex items-center gap-2 text-indigo-600 font-black"><Umbrella class="w-3 h-3" /> Holiday</div>
            </div>

            <!-- Calendar Grid -->
            <Card class="border-slate-200 shadow-xl overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[700px]">
                    <table class="w-full border-collapse">
                        <thead class="sticky top-0 z-20 bg-slate-900 text-white">
                            <tr>
                                <th class="sticky left-0 z-30 bg-slate-900 px-6 py-4 text-left font-bold text-sm min-w-[250px] border-r border-slate-700 shadow-[2px_0_5px_rgba(0,0,0,0.1)]">
                                    Staff Member
                                </th>
                                <th v-for="d in days" :key="d.day" 
                                    :class="['px-2 py-4 text-center text-xs font-black border-r border-slate-700 min-w-[40px]', 
                                            d.isWeekend ? 'bg-slate-800' : '',
                                            holidays[d.dateString] ? 'bg-indigo-950 text-indigo-300' : '']">
                                    {{ d.day }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="staff in staffList" :key="staff.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="sticky left-0 z-10 bg-white px-6 py-4 border-r border-slate-100 shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900 leading-tight">{{ staff.user?.name }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium uppercase tracking-tight">{{ staff.department?.name }}</span>
                                    </div>
                                </td>
                                <td v-for="d in days" :key="d.day" 
                                    :class="['p-1 border-r border-slate-50 text-center', 
                                            d.isWeekend ? 'bg-slate-50/50' : '',
                                            holidays[d.dateString] ? 'bg-indigo-50/40' : '']">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <div v-if="holidays[d.dateString]" class="flex justify-center animate-pulse">
                                                    <Umbrella class="w-4 h-4 text-indigo-600" />
                                                </div>
                                                <div v-else-if="attendances[staff.id]?.[d.dateString]"
                                                     :class="['w-6 h-6 rounded-full mx-auto shadow-sm cursor-help transition-transform hover:scale-125', getStatusColor(attendances[staff.id][d.dateString].status)]">
                                                </div>
                                                <div v-else-if="!d.isWeekend" class="w-2 h-2 rounded-full bg-slate-200 mx-auto opacity-50"></div>
                                            </TooltipTrigger>
                                            <TooltipContent v-if="holidays[d.dateString] || attendances[staff.id]?.[d.dateString]" class="bg-slate-900 text-white border-none shadow-xl font-bold">
                                                <div class="flex flex-col gap-1 p-1">
                                                    <span class="text-xs">{{ d.day }} {{ currentMonth }}</span>
                                                    
                                                    <template v-if="holidays[d.dateString]">
                                                        <Badge class="bg-indigo-600 border-none uppercase text-[10px]">HOLIDAY: {{ holidays[d.dateString].name }}</Badge>
                                                        <span class="text-[9px] opacity-70 italic">{{ holidays[d.dateString].description }}</span>
                                                    </template>

                                                    <template v-else-if="attendances[staff.id][d.dateString]">
                                                        <Badge class="w-fit" :variant="attendances[staff.id][d.dateString].status === 'present' ? 'default' : 'secondary'">
                                                            {{ getStatusLabel(attendances[staff.id][d.dateString].status) }}
                                                        </Badge>
                                                        <span v-if="attendances[staff.id][d.dateString].clock_in" class="text-[10px] opacity-70">
                                                            Clock In: {{ attendances[staff.id][d.dateString].clock_in.substring(0,5) }}
                                                        </span>
                                                    </template>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </td>
                            </tr>
                            <tr v-if="staffList.length === 0">
                                <td :colspan="days.length + 1" class="py-20 text-center">
                                    <div class="flex flex-col items-center gap-2 opacity-30">
                                        <LayoutGrid class="w-12 h-12" />
                                        <p class="font-bold">No staff found for this department.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom scrollbar for the grid */
.overflow-x-auto::-webkit-scrollbar {
    height: 10px;
}
.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 5px;
}
.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
