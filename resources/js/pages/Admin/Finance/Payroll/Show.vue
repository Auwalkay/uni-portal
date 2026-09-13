<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { format } from 'date-fns';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';
import {
    ArrowLeft,
    CheckCircle,
    Printer,
    Search,
    X,
    Edit3,
    UserX,
    UserCheck,
    Plus,
    Trash2,
    Calendar,
    AlertCircle,
    Download,
    Calculator,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import Pagination from '@/components/Pagination.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    payroll: any;
    items: any;
    attendanceStats?: Record<string, { absent_days: number; late_days: number; present_days: number }>;
    summary?: {
        gross_basic: number;
        total_allowances: number;
        total_deductions: number;
        total_net_payable: number;
        total_staff: number;
        active_staff: number;
        excluded_staff: number;
        total_absent_days?: number;
        total_late_days?: number;
    };
    filters?: {
        search?: string;
        status?: string;
        per_page?: string;
    };
}>();

const formatCurrency = (val: any) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val || 0);

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedPerPage = ref(props.filters?.per_page || '20');

const updateFilters = debounce(() => {
    router.get(route('admin.finance.payroll.show', props.payroll.id), {
        search: search.value,
        status: selectedStatus.value,
        per_page: selectedPerPage.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedStatus, selectedPerPage], () => {
    updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedPerPage.value = '20';
};

const markAsPaid = () => {
    if (confirm('Are you sure you want to mark this payroll as paid? This ends the workflow.')) {
        router.post(route('admin.finance.payroll.mark-as-paid', props.payroll.id), {}, {
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Processed', text: 'Payroll marked as paid', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const print = () => {
    window.print();
};

// --- Edit Staff Payroll Item Modal State ---
interface BreakdownItem {
    label: string;
    amount: number | string;
}

const isEditModalOpen = ref(false);
const editingItem = ref<any>(null);

const editForm = useForm({
    basic_salary: 0,
    status: 'pending',
    allowances: [] as BreakdownItem[],
    deductions: [] as BreakdownItem[],
    remarks: '',
});

const openEditModal = (item: any) => {
    editingItem.value = item;
    editForm.basic_salary = Number(item.basic_salary) || 0;
    editForm.status = item.status || 'pending';
    editForm.remarks = item.remarks || '';

    // Parse allowances
    const allowances: BreakdownItem[] = [];
    if (item.allowance_breakdown && typeof item.allowance_breakdown === 'object') {
        Object.entries(item.allowance_breakdown).forEach(([label, amount]) => {
            allowances.push({ label, amount: Number(amount) || 0 });
        });
    } else if (Number(item.total_allowances) > 0) {
        allowances.push({ label: 'Regular Allowance', amount: Number(item.total_allowances) });
    }
    editForm.allowances = allowances;

    // Parse deductions
    const deductions: BreakdownItem[] = [];
    if (item.deduction_breakdown && typeof item.deduction_breakdown === 'object') {
        Object.entries(item.deduction_breakdown).forEach(([label, amount]) => {
            deductions.push({ label, amount: Number(amount) || 0 });
        });
    } else if (Number(item.total_deductions) > 0) {
        deductions.push({ label: 'Standard Deduction', amount: Number(item.total_deductions) });
    }
    editForm.deductions = deductions;

    isEditModalOpen.value = true;
};

const addAllowance = () => {
    editForm.allowances.push({ label: '', amount: 0 });
};

const removeAllowance = (index: number) => {
    editForm.allowances.splice(index, 1);
};

const addDeduction = () => {
    editForm.deductions.push({ label: '', amount: 0 });
};

const removeDeduction = (index: number) => {
    editForm.deductions.splice(index, 1);
};

const autoCalculateAbsencePenalty = () => {
    if (!editingItem.value) return;
    const staffId = editingItem.value.staff_id;
    const stats = props.attendanceStats?.[staffId];
    const absentDays = stats?.absent_days || 0;

    if (absentDays <= 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Absences Recorded',
            text: 'This staff member has 0 absent days recorded for this month.',
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false,
        });
        return;
    }

    const basic = Number(editForm.basic_salary) || 0;
    // Calculate daily rate assuming 30 days standard month
    const dailyRate = basic / 30;
    const penalty = Math.round(dailyRate * absentDays * 100) / 100;
    const label = `Absence Fine (${absentDays} ${absentDays === 1 ? 'day' : 'days'})`;

    const existingIdx = editForm.deductions.findIndex(d => d.label.toLowerCase().includes('absence'));
    if (existingIdx >= 0) {
        editForm.deductions[existingIdx] = { label, amount: penalty };
    } else {
        editForm.deductions.push({ label, amount: penalty });
    }

    Swal.fire({
        icon: 'success',
        title: 'Absence Penalty Added',
        text: `Calculated ₦${penalty.toLocaleString()} fine for ${absentDays} absent days.`,
        toast: true,
        position: 'top-end',
        timer: 3000,
        showConfirmButton: false,
    });
};

const calculatedTotalAllowances = computed(() => {
    return editForm.allowances.reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
});

const calculatedTotalDeductions = computed(() => {
    return editForm.deductions.reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
});

const calculatedNetSalary = computed(() => {
    if (editForm.status === 'excluded') return 0;
    const basic = Number(editForm.basic_salary) || 0;
    return Math.max(0, basic + calculatedTotalAllowances.value - calculatedTotalDeductions.value);
});

const submitEditItem = () => {
    if (!editingItem.value) return;

    editForm.put(route('admin.finance.payroll.items.update', [props.payroll.id, editingItem.value.id]), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: 'Staff salary details and deductions updated.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

const toggleExclusion = (item: any) => {
    const isCurrentlyExcluded = item.status === 'excluded';
    const actionText = isCurrentlyExcluded ? 'include this staff member in the payroll' : 'exclude this staff member from receiving payment for this month';

    if (confirm(`Are you sure you want to ${actionText}?`)) {
        router.post(route('admin.finance.payroll.items.toggle-exclusion', [props.payroll.id, item.id]), {}, {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: isCurrentlyExcluded ? 'Staff included in payroll.' : 'Staff excluded from payroll.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
        });
    }
};
</script>

<template>
    <Head title="Payroll Details" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between no-print">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.finance.payroll.index')">
                        <Button variant="outline" size="icon"><ArrowLeft class="h-4 w-4" /></Button>
                    </Link>
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Payroll: {{ format(new Date(payroll.year, payroll.month - 1), 'MMMM yyyy') }}</h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-muted-foreground font-semibold">Workflow Status:</span>
                            <Badge :variant="payroll.status === 'paid' ? 'default' : 'secondary'" :class="payroll.status === 'paid' ? 'bg-emerald-600 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'">
                                {{ payroll.status.toUpperCase() }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="print"><Printer class="mr-2 h-4 w-4" /> Print</Button>
                    <Button v-if="payroll.status !== 'paid'" @click="markAsPaid" class="bg-emerald-600 hover:bg-emerald-700 font-bold">
                        <CheckCircle class="mr-2 h-4 w-4" /> Mark as Paid
                    </Button>
                </div>
            </div>

            <!-- Financial & Attendance Analysis Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <Card class="border-emerald-200 dark:border-emerald-900 bg-emerald-50/40 dark:bg-emerald-950/20">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Total Net Payable</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(summary?.total_net_payable ?? payroll.total_amount) }}</div>
                        <p class="text-[11px] text-emerald-700/70 dark:text-emerald-400/70 mt-1 font-medium">Final payout amount</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Gross Basic Salary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ formatCurrency(summary?.gross_basic) }}</div>
                        <p class="text-[11px] text-muted-foreground mt-1">Base staff salaries</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Allowances</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">+{{ formatCurrency(summary?.total_allowances) }}</div>
                        <p class="text-[11px] text-muted-foreground mt-1">Bonuses & add-ons</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Deductions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">-{{ formatCurrency(summary?.total_deductions) }}</div>
                        <p class="text-[11px] text-muted-foreground mt-1">Absences & fines</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Monthly Attendance</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span class="text-rose-600 font-extrabold">{{ summary?.total_absent_days ?? 0 }}</span>
                            <span class="text-xs text-muted-foreground">Absences</span>
                        </div>
                        <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1 font-medium">
                            {{ summary?.total_late_days ?? 0 }} Late arrival records
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Staff Records</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary?.total_staff ?? items.total }}</div>
                        <p class="text-[11px] text-muted-foreground mt-1">
                            <span class="text-emerald-600 font-semibold">{{ summary?.active_staff ?? items.total }} Active</span>
                            <span v-if="summary?.excluded_staff" class="text-rose-600 font-semibold ml-1.5">({{ summary.excluded_staff }} Excluded)</span>
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row gap-4 items-center justify-between no-print">
                <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <!-- Search -->
                    <div class="relative w-full sm:w-[300px]">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search staff name or ID..."
                            class="pl-10 h-10"
                            v-model="search"
                        />
                    </div>

                    <!-- Status -->
                    <Select v-model="selectedStatus">
                        <SelectTrigger class="w-full sm:w-[180px] h-10">
                            <SelectValue placeholder="Payment Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL_STATUS">All Statuses</SelectItem>
                            <SelectItem value="pending">Pending / Eligible</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="excluded">Excluded / Unpaid</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Per Page -->
                    <Select v-model="selectedPerPage">
                        <SelectTrigger class="w-full sm:w-[130px] h-10">
                            <SelectValue placeholder="Per Page" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10 per page</SelectItem>
                            <SelectItem value="20">20 per page</SelectItem>
                            <SelectItem value="50">50 per page</SelectItem>
                            <SelectItem value="100">100 per page</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex gap-2 w-full sm:w-auto justify-end">
                    <Button 
                        v-if="search || (selectedStatus && selectedStatus !== 'ALL_STATUS') || selectedPerPage !== '20'" 
                        variant="ghost" 
                        @click="clearFilters"
                        class="text-destructive hover:text-destructive hover:bg-destructive/10 h-10"
                    >
                        <X class="w-4 h-4 mr-2" />
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Staff Payments Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg font-bold">Staff Salary Breakdown & Deductions</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Staff Name</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Attendance (Month)</TableHead>
                                <TableHead>Basic Salary</TableHead>
                                <TableHead>Allowances</TableHead>
                                <TableHead>Deductions</TableHead>
                                <TableHead>Net Salary</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right no-print">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="item in items.data" 
                                :key="item.id"
                                :class="item.status === 'excluded' ? 'bg-rose-50/50 dark:bg-rose-950/20 opacity-75' : ''"
                            >
                                <TableCell class="font-medium">
                                    <div>
                                        <p class="font-extrabold text-slate-900 dark:text-slate-100">{{ item.staff?.user?.name }}</p>
                                        <p class="text-xs font-mono text-muted-foreground">{{ item.staff?.staff_number }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>{{ item.staff?.department?.name || 'N/A' }}</TableCell>
                                <TableCell>
                                    <div v-if="props.attendanceStats?.[item.staff_id]" class="flex items-center gap-1 text-xs">
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-bold" title="Present Days">
                                            {{ props.attendanceStats[item.staff_id].present_days || 0 }} P
                                        </span>
                                        <span v-if="props.attendanceStats[item.staff_id].late_days" class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-bold" title="Late Days">
                                            {{ props.attendanceStats[item.staff_id].late_days }} L
                                        </span>
                                        <span v-if="props.attendanceStats[item.staff_id].absent_days" class="px-2 py-0.5 rounded bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 font-bold" title="Absent Days">
                                            {{ props.attendanceStats[item.staff_id].absent_days }} A
                                        </span>
                                    </div>
                                    <span v-else class="text-muted-foreground text-xs font-medium">0 Absences</span>
                                </TableCell>
                                <TableCell>{{ formatCurrency(item.basic_salary) }}</TableCell>
                                <TableCell class="text-emerald-600 font-medium">+{{ formatCurrency(item.total_allowances) }}</TableCell>
                                <TableCell class="text-rose-600 font-medium">
                                    <div>
                                        -{{ formatCurrency(item.total_deductions) }}
                                        <p v-if="item.remarks" class="text-[11px] text-muted-foreground italic font-normal line-clamp-1" :title="item.remarks">
                                            {{ item.remarks }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell class="font-extrabold text-slate-900 dark:text-slate-100">
                                    <span v-if="item.status === 'excluded'" class="text-rose-600 line-through">
                                        {{ formatCurrency(item.net_salary) }}
                                    </span>
                                    <span v-else>
                                        {{ formatCurrency(item.net_salary) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        v-if="item.status === 'excluded'" 
                                        variant="destructive"
                                        class="bg-rose-100 text-rose-800 border-rose-200 font-bold text-[10px] uppercase"
                                    >
                                        Excluded / Unpaid
                                    </Badge>
                                    <Badge 
                                        v-else-if="item.status === 'paid' || payroll.status === 'paid'" 
                                        class="bg-emerald-100 text-emerald-800 border-emerald-200 font-bold text-[10px] uppercase"
                                    >
                                        Paid
                                    </Badge>
                                    <Badge 
                                        v-else 
                                        variant="outline"
                                        class="bg-amber-50 text-amber-700 border-amber-200 font-bold text-[10px] uppercase"
                                    >
                                        Pending
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right no-print">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit Adjustments (Draft Only) -->
                                        <Button 
                                            v-if="payroll.status === 'draft'"
                                            size="sm"
                                            variant="outline"
                                            class="h-8 text-xs gap-1 font-semibold"
                                            @click="openEditModal(item)"
                                            title="Edit Salary & Deductions"
                                        >
                                            <Edit3 class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                            <span>Adjust</span>
                                        </Button>

                                        <!-- Toggle Exclude (Draft Only) -->
                                        <Button 
                                            v-if="payroll.status === 'draft'"
                                            size="sm"
                                            :variant="item.status === 'excluded' ? 'default' : 'outline'"
                                            :class="item.status === 'excluded' ? 'bg-emerald-600 hover:bg-emerald-700 text-white h-8 text-xs' : 'h-8 text-xs border-rose-200 text-rose-600 hover:bg-rose-50'"
                                            @click="toggleExclusion(item)"
                                            :title="item.status === 'excluded' ? 'Include in Payroll' : 'Exclude from Payroll'"
                                        >
                                            <UserCheck v-if="item.status === 'excluded'" class="w-3.5 h-3.5" />
                                            <UserX v-else class="w-3.5 h-3.5" />
                                            <span>{{ item.status === 'excluded' ? 'Include' : 'Exclude' }}</span>
                                        </Button>

                                        <!-- Download Payslip -->
                                        <a 
                                            :href="route('admin.finance.payroll.payslip.download', [payroll.id, item.id])" 
                                            target="_blank"
                                        >
                                            <Button size="sm" variant="ghost" class="h-8 w-8 p-0" title="Download Payslip PDF">
                                                <Download class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300" />
                                            </Button>
                                        </a>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="p-4 border-t flex justify-end no-print">
                        <Pagination :links="items.links" />
                    </div>
                </CardContent>
            </Card>

            <!-- Adjust Staff Salary & Deductions Modal -->
            <Dialog :open="isEditModalOpen" @update:open="val => isEditModalOpen = val">
                <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="text-xl font-bold flex items-center gap-2">
                            <span>Adjust Salary & Deductions</span>
                        </DialogTitle>
                        <DialogDescription>
                            Customize payable basic salary, allowances, and itemized deductions (e.g., absence penalties) for 
                            <strong class="text-foreground">{{ editingItem?.staff?.user?.name }}</strong> 
                            ({{ editingItem?.staff?.staff_number }}).
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitEditItem" class="space-y-5 py-2">
                        <!-- Attendance Summary Widget -->
                        <div v-if="editingItem && props.attendanceStats?.[editingItem.staff_id]" class="bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/80 rounded-xl p-3.5 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-indigo-900 dark:text-indigo-200 flex items-center gap-1.5">
                                    <Calendar class="w-4 h-4 text-indigo-600" />
                                    Monthly Attendance Record ({{ format(new Date(payroll.year, payroll.month - 1), 'MMMM yyyy') }})
                                </span>
                                <Button 
                                    type="button" 
                                    size="sm" 
                                    variant="outline" 
                                    class="h-7 text-[11px] font-bold text-rose-700 border-rose-300 bg-white hover:bg-rose-50 gap-1 rounded-lg"
                                    @click="autoCalculateAbsencePenalty"
                                >
                                    <Calculator class="w-3 h-3" />
                                    Auto-Add Absence Deduction
                                </Button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs pt-1">
                                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-indigo-100 dark:border-indigo-900">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Present</span>
                                    <span class="font-extrabold text-emerald-600 text-sm">{{ props.attendanceStats[editingItem.staff_id].present_days }} Days</span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-indigo-100 dark:border-indigo-900">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Late</span>
                                    <span class="font-extrabold text-amber-600 text-sm">{{ props.attendanceStats[editingItem.staff_id].late_days }} Days</span>
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-2 rounded-lg border border-indigo-100 dark:border-indigo-900">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Absent</span>
                                    <span class="font-extrabold text-rose-600 text-sm">{{ props.attendanceStats[editingItem.staff_id].absent_days }} Days</span>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Basic Salary (NGN)</Label>
                                <Input type="number" step="0.01" min="0" v-model="editForm.basic_salary" class="font-mono" />
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Payment Status</Label>
                                <Select v-model="editForm.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">Eligible / Include in Payroll</SelectItem>
                                        <SelectItem value="excluded">Excluded / Do Not Pay This Month</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Allowances Section -->
                        <div class="space-y-3 pt-2 border-t">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-extrabold uppercase text-emerald-700 tracking-wider">Allowances & Earnings</Label>
                                <Button type="button" size="sm" variant="ghost" class="h-7 text-xs text-emerald-600 hover:text-emerald-700 gap-1" @click="addAllowance">
                                    <Plus class="w-3.5 h-3.5" /> Add Allowance
                                </Button>
                            </div>

                            <div v-if="editForm.allowances.length > 0" class="space-y-2">
                                <div v-for="(al, idx) in editForm.allowances" :key="idx" class="flex items-center gap-2">
                                    <Input v-model="al.label" placeholder="e.g. Performance Bonus, Housing" class="text-xs flex-1" />
                                    <Input type="number" step="0.01" min="0" v-model="al.amount" placeholder="Amount" class="text-xs w-32 font-mono" />
                                    <Button type="button" size="icon" variant="ghost" class="h-8 w-8 text-rose-500 hover:text-rose-700 shrink-0" @click="removeAllowance(idx)">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                            <p v-else class="text-xs text-muted-foreground italic">No extra allowances added.</p>
                        </div>

                        <!-- Deductions Section -->
                        <div class="space-y-3 pt-2 border-t">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-extrabold uppercase text-rose-700 tracking-wider">Deductions & Penalties</Label>
                                <Button type="button" size="sm" variant="ghost" class="h-7 text-xs text-rose-600 hover:text-rose-700 gap-1" @click="addDeduction">
                                    <Plus class="w-3.5 h-3.5" /> Add Deduction
                                </Button>
                            </div>

                            <div v-if="editForm.deductions.length > 0" class="space-y-2">
                                <div v-for="(dd, idx) in editForm.deductions" :key="idx" class="flex items-center gap-2">
                                    <Input v-model="dd.label" placeholder="e.g. Absence Penalty, Disciplinary Deduction" class="text-xs flex-1" />
                                    <Input type="number" step="0.01" min="0" v-model="dd.amount" placeholder="Amount" class="text-xs w-32 font-mono" />
                                    <Button type="button" size="icon" variant="ghost" class="h-8 w-8 text-rose-500 hover:text-rose-700 shrink-0" @click="removeDeduction(idx)">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                            <p v-else class="text-xs text-muted-foreground italic">No deductions added.</p>
                        </div>

                        <!-- Remarks / Notes -->
                        <div class="space-y-1.5 pt-2 border-t">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Adjustment Remarks / Reason</Label>
                            <Textarea v-model="editForm.remarks" placeholder="Provide notes or reasons for salary adjustments (e.g. Absence fine applied for 2 days; Approved by HR)." rows="2" class="text-xs" />
                        </div>

                        <!-- Net Salary Preview Bar -->
                        <div class="bg-slate-100 dark:bg-slate-900 p-3.5 rounded-xl border flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-600 dark:text-slate-300">Recalculated Net Payable Salary:</span>
                            <span class="text-lg font-black" :class="editForm.status === 'excluded' ? 'text-rose-600 line-through' : 'text-emerald-600'">
                                {{ editForm.status === 'excluded' ? '₦0.00 (EXCLUDED)' : formatCurrency(calculatedNetSalary) }}
                            </span>
                        </div>

                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" @click="isEditModalOpen = false">Cancel</Button>
                            <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 font-bold" :disabled="editForm.processing">
                                Save Adjustments
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
