<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Eye,
    Trash2,
    PlusCircle,
    CheckCircle2,
    Download,
    Upload,
    Search,
    Filter,
    X,
    Calendar,
    Clock,
    DollarSign,
    Wallet,
    Users,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Loader2,
    FileSpreadsheet,
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';

interface PayrollItemModel {
    id: string;
    month: number;
    year: number;
    total_amount: number | string;
    status: 'draft' | 'paid';
    generated_at: string;
    created_at?: string;
    paid_at?: string;
    items_count?: number;
    generated_by?: {
        id: string;
        name: string;
        email: string;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPayrolls {
    data: PayrollItemModel[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

const props = defineProps<{
    payrolls: PaginatedPayrolls;
    stats?: {
        total_payrolls: number;
        total_paid: number;
        total_draft: number;
        paid_count: number;
        draft_count: number;
    };
    availableYears?: number[];
    filters?: {
        search?: string;
        status?: string;
        year?: string;
        month?: string;
        sort_field?: string;
        sort_direction?: string;
        per_page?: string;
    };
}>();

const formatCurrency = (val: any) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(Number(val) || 0);

// --- Filters & Sorting State ---
const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || 'all');
const selectedYear = ref(props.filters?.year || 'all');
const selectedMonth = ref(props.filters?.month || 'all');
const selectedPerPage = ref(props.filters?.per_page || '10');
const sortField = ref(props.filters?.sort_field || 'period');
const sortDirection = ref(props.filters?.sort_direction || 'desc');
const isFiltering = ref(false);

const applyFilters = () => {
    isFiltering.value = true;
    router.get(route('admin.finance.payroll.index'), {
        search: search.value || undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        year: selectedYear.value !== 'all' ? selectedYear.value : undefined,
        month: selectedMonth.value !== 'all' ? selectedMonth.value : undefined,
        sort_field: sortField.value,
        sort_direction: sortDirection.value,
        per_page: selectedPerPage.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onFinish: () => {
            isFiltering.value = false;
        },
    });
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = 'all';
    selectedYear.value = 'all';
    selectedMonth.value = 'all';
    selectedPerPage.value = '10';
    sortField.value = 'period';
    sortDirection.value = 'desc';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        (selectedStatus.value && selectedStatus.value !== 'all') ||
        (selectedYear.value && selectedYear.value !== 'all') ||
        (selectedMonth.value && selectedMonth.value !== 'all') ||
        selectedPerPage.value !== '10' ||
        sortField.value !== 'period' ||
        sortDirection.value !== 'desc'
    );
});

const sortBy = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
    applyFilters();
};

watch(selectedPerPage, () => {
    applyFilters();
});

// --- Generate Modal State ---
const isGenerateModalOpen = ref(false);
const generateForm = useForm({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
});

const submitGenerate = () => {
    generateForm.post(route('admin.finance.payroll.generate'), {
        onSuccess: () => {
            isGenerateModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Payroll Generated',
                text: 'New payroll cycle created successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Generation Failed',
                text: 'Failed to generate payroll. Check if this month already exists.',
            });
        },
    });
};

// --- Import Modal State ---
const isImportModalOpen = ref(false);
const selectedPayroll = ref<any>(null);
const importForm = useForm({
    file: null as File | null,
});

const openImportModal = (payroll: any) => {
    selectedPayroll.value = payroll;
    importForm.reset();
    isImportModalOpen.value = true;
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.file = target.files[0];
    }
};

const submitImport = () => {
    if (!selectedPayroll.value || !importForm.file) return;

    importForm.post(route('admin.finance.payroll.import', selectedPayroll.value.id), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Import Successful',
                text: 'Payroll spreadsheet imported and totals recalculated.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                text: 'Failed to update payroll from spreadsheet. Please verify row format.',
            });
        },
    });
};

// --- Delete Payroll ---
const deletePayroll = (id: string, periodText: string) => {
    if (confirm(`Are you sure you want to delete the payroll for ${periodText}? This will permanently remove all staff item records for this run.`)) {
        router.delete(route('admin.finance.payroll.destroy', id), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted',
                    text: 'Payroll cycle record deleted.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
        });
    }
};

const monthNames = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];
</script>

<template>
    <Head title="Payroll Management" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Payroll Management</h2>
                    <p class="text-muted-foreground text-sm mt-1">
                        Generate, monitor monthly salary cycles, process disbursements, and export payroll records.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button 
                        @click="isGenerateModalOpen = true" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-sm"
                    >
                        <PlusCircle class="mr-2 h-4 w-4" /> 
                        Generate Payroll
                    </Button>
                </div>
            </div>

            <!-- Executive KPI Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="border-emerald-200 dark:border-emerald-900 bg-emerald-50/40 dark:bg-emerald-950/20">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                            Total Disbursed
                        </CardTitle>
                        <Wallet class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                            {{ formatCurrency(stats?.total_paid) }}
                        </div>
                        <p class="text-[11px] text-emerald-700/70 dark:text-emerald-400/70 mt-1 font-medium">
                            {{ stats?.paid_count ?? 0 }} paid payroll cycles
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-amber-200 dark:border-amber-900 bg-amber-50/40 dark:bg-amber-950/20">
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-300">
                            Pending / Draft Outlay
                        </CardTitle>
                        <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                            {{ formatCurrency(stats?.total_draft) }}
                        </div>
                        <p class="text-[11px] text-amber-700/70 dark:text-amber-400/70 mt-1 font-medium">
                            {{ stats?.draft_count ?? 0 }} draft cycles awaiting payment
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Payroll Cycles
                        </CardTitle>
                        <Calendar class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-800 dark:text-slate-200">
                            {{ stats?.total_payrolls ?? payrolls.total }}
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-1">
                            Total generated pay runs
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Disbursement Rate
                        </CardTitle>
                        <DollarSign class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                            {{ (stats?.total_payrolls && stats.total_payrolls > 0) ? Math.round(((stats.paid_count || 0) / stats.total_payrolls) * 100) : 0 }}%
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-1">
                            Cycles finalized as paid
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Smart Filter Bar -->
            <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-[260px]">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search period, initiator..."
                            class="pl-10 h-10"
                            v-model="search"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Year Select Filter -->
                    <Select v-model="selectedYear">
                        <SelectTrigger class="w-full sm:w-[140px] h-10">
                            <SelectValue placeholder="All Years" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Years</SelectItem>
                            <SelectItem 
                                v-for="y in (props.availableYears || [])" 
                                :key="y" 
                                :value="String(y)"
                            >
                                {{ y }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Month Select Filter -->
                    <Select v-model="selectedMonth">
                        <SelectTrigger class="w-full sm:w-[150px] h-10">
                            <SelectValue placeholder="All Months" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Months</SelectItem>
                            <SelectItem 
                                v-for="m in monthNames" 
                                :key="m.value" 
                                :value="String(m.value)"
                            >
                                {{ m.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Status Filter -->
                    <Select v-model="selectedStatus">
                        <SelectTrigger class="w-full sm:w-[160px] h-10">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="draft">Draft (Pending)</SelectItem>
                            <SelectItem value="paid">Paid (Disbursed)</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Per Page Select -->
                    <Select v-model="selectedPerPage">
                        <SelectTrigger class="w-full sm:w-[120px] h-10">
                            <SelectValue placeholder="Per Page" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10 / page</SelectItem>
                            <SelectItem value="20">20 / page</SelectItem>
                            <SelectItem value="50">50 / page</SelectItem>
                            <SelectItem value="100">100 / page</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 ml-auto">
                        <Button 
                            type="button" 
                            class="h-10 px-4 font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg flex items-center gap-1.5 shadow-sm"
                            :disabled="isFiltering"
                            @click="applyFilters"
                        >
                            <Loader2 v-if="isFiltering" class="w-4 h-4 animate-spin" />
                            <Filter v-else class="w-4 h-4" />
                            <span>Apply Filter</span>
                        </Button>
                        <Button 
                            v-if="hasActiveFilters" 
                            variant="ghost" 
                            @click="clearFilters"
                            class="text-destructive hover:text-destructive hover:bg-destructive/10 h-10"
                        >
                            <X class="w-4 h-4 mr-1.5" />
                            Reset
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Payroll Cycles Table -->
            <Card>
                <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg font-bold">Payroll Cycles</CardTitle>
                            <CardDescription>
                                Click column headers to sort by period, staff enrollment, amount, or status.
                            </CardDescription>
                        </div>
                        <Badge variant="outline" class="font-mono text-xs">
                            Total Records: {{ payrolls.total }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/70 dark:bg-slate-900/50">
                                <TableHead 
                                    class="cursor-pointer select-none font-bold text-xs" 
                                    @click="sortBy('period')"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>Period</span>
                                        <ArrowUp v-if="sortField === 'period' && sortDirection === 'asc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowDown v-else-if="sortField === 'period' && sortDirection === 'desc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowUpDown v-else class="w-3.5 h-3.5 text-muted-foreground opacity-50" />
                                    </div>
                                </TableHead>
                                <TableHead 
                                    class="cursor-pointer select-none font-bold text-xs" 
                                    @click="sortBy('items_count')"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>Enrolled Staff</span>
                                        <ArrowUp v-if="sortField === 'items_count' && sortDirection === 'asc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowDown v-else-if="sortField === 'items_count' && sortDirection === 'desc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowUpDown v-else class="w-3.5 h-3.5 text-muted-foreground opacity-50" />
                                    </div>
                                </TableHead>
                                <TableHead 
                                    class="cursor-pointer select-none font-bold text-xs" 
                                    @click="sortBy('total_amount')"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>Total Net Payout</span>
                                        <ArrowUp v-if="sortField === 'total_amount' && sortDirection === 'asc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowDown v-else-if="sortField === 'total_amount' && sortDirection === 'desc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowUpDown v-else class="w-3.5 h-3.5 text-muted-foreground opacity-50" />
                                    </div>
                                </TableHead>
                                <TableHead 
                                    class="cursor-pointer select-none font-bold text-xs" 
                                    @click="sortBy('status')"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>Workflow Status</span>
                                        <ArrowUp v-if="sortField === 'status' && sortDirection === 'asc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowDown v-else-if="sortField === 'status' && sortDirection === 'desc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowUpDown v-else class="w-3.5 h-3.5 text-muted-foreground opacity-50" />
                                    </div>
                                </TableHead>
                                <TableHead class="font-bold text-xs">Generated By</TableHead>
                                <TableHead 
                                    class="cursor-pointer select-none font-bold text-xs" 
                                    @click="sortBy('generated_at')"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <span>Date Generated</span>
                                        <ArrowUp v-if="sortField === 'generated_at' && sortDirection === 'asc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowDown v-else-if="sortField === 'generated_at' && sortDirection === 'desc'" class="w-3.5 h-3.5 text-indigo-600" />
                                        <ArrowUpDown v-else class="w-3.5 h-3.5 text-muted-foreground opacity-50" />
                                    </div>
                                </TableHead>
                                <TableHead class="text-right font-bold text-xs">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="payroll in payrolls.data" 
                                :key="payroll.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors"
                            >
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center shrink-0">
                                            <Calendar class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-sm text-slate-900 dark:text-slate-100">
                                                {{ format(new Date(payroll.year, payroll.month - 1), 'MMMM yyyy') }}
                                            </p>
                                            <p class="text-[11px] text-muted-foreground">Monthly Salary Cycle</p>
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge variant="secondary" class="font-medium text-xs gap-1">
                                        <Users class="h-3 w-3 text-slate-500" />
                                        <span>{{ payroll.items_count ?? 0 }} Staff</span>
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <span class="font-black text-sm text-slate-900 dark:text-slate-100 font-mono">
                                        {{ formatCurrency(payroll.total_amount) }}
                                    </span>
                                </TableCell>

                                <TableCell>
                                    <Badge 
                                        v-if="payroll.status === 'paid'" 
                                        class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 font-bold text-[10px] uppercase gap-1"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        PAID
                                    </Badge>
                                    <Badge 
                                        v-else 
                                        class="bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 font-bold text-[10px] uppercase gap-1"
                                    >
                                        <Clock class="h-3 w-3" />
                                        DRAFT
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900 dark:text-slate-200">
                                            {{ payroll.generated_by?.name || 'Administrator' }}
                                        </p>
                                        <p class="text-[11px] text-muted-foreground">
                                            {{ payroll.generated_by?.email || 'System' }}
                                        </p>
                                    </div>
                                </TableCell>

                                <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
                                    {{ payroll.generated_at ? format(new Date(payroll.generated_at), 'MMM d, yyyy • HH:mm') : (payroll.created_at ? format(new Date(payroll.created_at), 'MMM d, yyyy • HH:mm') : 'N/A') }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- View Payroll -->
                                        <Link :href="route('admin.finance.payroll.show', payroll.id)">
                                            <Button 
                                                size="sm" 
                                                variant="outline" 
                                                class="h-8 text-xs font-semibold gap-1"
                                                title="View Breakdown & Deductions"
                                            >
                                                <Eye class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                                <span>View</span>
                                            </Button>
                                        </Link>

                                        <!-- Export Excel -->
                                        <a :href="route('admin.finance.payroll.export', payroll.id)" target="_blank">
                                            <Button 
                                                size="icon" 
                                                variant="ghost" 
                                                class="h-8 w-8 text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50" 
                                                title="Export Excel Spreadsheet"
                                            >
                                                <Download class="h-3.5 w-3.5" />
                                            </Button>
                                        </a>

                                        <!-- Upload / Import (Draft Only) -->
                                        <Button 
                                            v-if="payroll.status !== 'paid'" 
                                            size="icon" 
                                            variant="ghost" 
                                            class="h-8 w-8 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50" 
                                            title="Upload & Update Spreadsheet" 
                                            @click="openImportModal(payroll)"
                                        >
                                            <Upload class="h-3.5 w-3.5" />
                                        </Button>

                                        <!-- Delete (Draft Only) -->
                                        <Button 
                                            v-if="payroll.status !== 'paid'" 
                                            size="icon" 
                                            variant="ghost" 
                                            class="h-8 w-8 text-rose-600 hover:text-rose-700 hover:bg-rose-50" 
                                            @click="deletePayroll(payroll.id, format(new Date(payroll.year, payroll.month - 1), 'MMMM yyyy'))" 
                                            title="Delete Payroll Cycle"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="payrolls.data.length === 0">
                                <TableCell colspan="7" class="text-center py-12 text-muted-foreground">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Calendar class="h-10 w-10 text-slate-300 dark:text-slate-600" />
                                        <p class="font-bold text-sm text-slate-800 dark:text-slate-200">No payroll cycles found</p>
                                        <p class="text-xs text-muted-foreground max-w-sm">
                                            No payroll records matched your search query or filter settings. Try resetting filters or generate a new cycle.
                                        </p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <Button 
                                                v-if="hasActiveFilters" 
                                                variant="outline" 
                                                size="sm" 
                                                @click="clearFilters" 
                                                class="text-xs"
                                            >
                                                Reset Filters
                                            </Button>
                                            <Button 
                                                size="sm" 
                                                @click="isGenerateModalOpen = true" 
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold"
                                            >
                                                Generate Payroll
                                            </Button>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination Footer -->
                    <div class="p-4 border-t flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-xs text-muted-foreground">
                            Showing 
                            <span class="font-bold text-foreground">{{ payrolls.from || 0 }}</span> 
                            to 
                            <span class="font-bold text-foreground">{{ payrolls.to || 0 }}</span> 
                            of 
                            <span class="font-bold text-foreground">{{ payrolls.total || 0 }}</span> 
                            cycles
                        </p>
                        <Pagination :links="payrolls.links" />
                    </div>
                </CardContent>
            </Card>

            <!-- Import Payroll Modal -->
            <Dialog v-model:open="isImportModalOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Upload class="h-5 w-5 text-indigo-600" /> Upload & Update Payroll
                        </DialogTitle>
                        <DialogDescription>
                            Upload an edited Excel or CSV spreadsheet to update basic salaries, allowances, deductions, status, or remarks for 
                            <strong>{{ selectedPayroll ? format(new Date(selectedPayroll.year, selectedPayroll.month - 1), 'MMMM yyyy') : '' }}</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitImport" class="space-y-4 py-2">
                        <div class="space-y-2">
                            <Label for="payroll_index_file" class="font-semibold text-xs uppercase tracking-wider text-slate-600">
                                Select Payroll Spreadsheet (.xlsx, .csv, .xls)
                            </Label>
                            <Input 
                                id="payroll_index_file" 
                                type="file" 
                                accept=".csv, .xls, .xlsx" 
                                @change="handleFileChange" 
                                required 
                            />
                            <p class="text-[11px] text-muted-foreground">
                                First download the pre-filled template using the Export Excel button, edit the numbers, then upload here.
                            </p>
                        </div>

                        <div v-if="importForm.errors.file" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg font-medium">
                            {{ importForm.errors.file }}
                        </div>

                        <DialogFooter class="pt-3">
                            <Button type="button" variant="outline" @click="isImportModalOpen = false">Cancel</Button>
                            <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 font-bold" :disabled="importForm.processing">
                                <Upload class="mr-1.5 h-4 w-4" /> Upload & Update
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Generate Payroll Modal -->
            <Dialog v-model:open="isGenerateModalOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <PlusCircle class="h-5 w-5 text-indigo-600" /> Generate Payroll
                        </DialogTitle>
                        <DialogDescription>
                            Create a new payroll cycle for a specific month and year. This calculates salaries and allowances for all active staff.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid grid-cols-2 gap-4 py-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase text-slate-600">Month</Label>
                            <Select v-model="generateForm.month">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Month" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="m in monthNames" :key="m.value" :value="m.value">
                                        {{ m.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase text-slate-600">Year</Label>
                            <Input type="number" min="2020" max="2099" v-model="generateForm.year" />
                        </div>
                    </div>
                    <DialogFooter class="pt-2">
                        <Button variant="outline" @click="isGenerateModalOpen = false">Cancel</Button>
                        <Button 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold"
                            @click="submitGenerate" 
                            :disabled="generateForm.processing"
                        >
                            Generate
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
