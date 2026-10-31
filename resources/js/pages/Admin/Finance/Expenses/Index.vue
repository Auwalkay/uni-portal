<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import debounce from 'lodash/debounce';
import { format, formatDistanceToNow } from 'date-fns';
import Swal from 'sweetalert2';

import AdminLayout from '@/layouts/AdminLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
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

import {
    Plus,
    Search,
    Filter,
    CheckCircle,
    XCircle,
    Trash2,
    Edit,
    Eye,
    Download,
    RotateCcw,
    Wallet,
    Clock,
    AlertCircle,
    Calendar,
    ArrowUpDown,
    ArrowUpRight,
    User as UserIcon,
    FileText,
    Check,
    X,
    Building2,
    DollarSign,
} from 'lucide-vue-next';

interface ExpenseItem {
    id: string;
    title: string;
    description?: string;
    amount: number | string;
    date: string;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string;
    created_at: string;
    category?: {
        id: string;
        name: string;
    };
    user?: {
        id: string;
        name: string;
        email: string;
        staff?: {
            id: string;
            staff_number: string;
        };
    };
    requester?: {
        id: string;
        name: string;
        email: string;
        staff?: {
            id: string;
            staff_number: string;
        };
    };
    approver?: {
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

interface PaginatedExpenses {
    data: Array<ExpenseItem>;
    links: Array<PaginationLink>;
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

const props = defineProps<{
    expenses: PaginatedExpenses;
    categories: Array<{ id: string; name: string }>;
    filters: {
        search?: string;
        status?: string;
        category_id?: string;
        user_id?: string;
        date_from?: string;
        date_to?: string;
        sort_by?: string;
        sort_dir?: string;
        per_page?: string | number;
    };
    stats: {
        total_approved_amount: number;
        pending_count: number;
        pending_amount: number;
        this_month_approved_amount: number;
        rejected_count: number;
        rejected_amount: number;
        status_counts: {
            all: number;
            pending: number;
            approved: number;
            rejected: number;
        };
    };
    users: Array<any>;
    canRequestForOthers: boolean;
    canApproveExpenses: boolean;
}>();

// Filter States
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const categoryId = ref(props.filters.category_id || 'all');
const userId = ref(props.filters.user_id || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const sortBy = ref(props.filters.sort_by || 'date');
const sortDir = ref(props.filters.sort_dir || 'desc');
const perPage = ref(String(props.filters.per_page || '15'));
const activeDatePreset = ref<string>('custom');

const hasActiveFilters = computed(() => {
    return (
        search.value !== '' ||
        (status.value !== '' && status.value !== 'all') ||
        (categoryId.value !== '' && categoryId.value !== 'all') ||
        (userId.value !== '' && userId.value !== 'all') ||
        dateFrom.value !== '' ||
        dateTo.value !== '' ||
        perPage.value !== '15' ||
        sortBy.value !== 'date' ||
        sortDir.value !== 'desc'
    );
});

// Search and Filter Debounce
const applyFilters = debounce(() => {
    router.get(
        route('admin.finance.expenses.index'),
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
            category_id: categoryId.value !== 'all' ? categoryId.value : undefined,
            user_id: userId.value !== 'all' ? userId.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            sort_by: sortBy.value !== 'date' ? sortBy.value : undefined,
            sort_dir: sortDir.value !== 'desc' ? sortDir.value : undefined,
            per_page: perPage.value !== '15' ? perPage.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch([search, status, categoryId, userId, dateFrom, dateTo, sortBy, sortDir, perPage], applyFilters);

const setStatusTab = (tab: string) => {
    status.value = tab;
};

const setDatePreset = (preset: string) => {
    activeDatePreset.value = preset;
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth();
    const pad = (n: number) => String(n).padStart(2, '0');

    if (preset === 'all') {
        dateFrom.value = '';
        dateTo.value = '';
    } else if (preset === 'today') {
        const todayStr = `${y}-${pad(m + 1)}-${pad(now.getDate())}`;
        dateFrom.value = todayStr;
        dateTo.value = todayStr;
    } else if (preset === 'this_month') {
        const firstDay = `${y}-${pad(m + 1)}-01`;
        const lastDay = new Date(y, m + 1, 0).getDate();
        dateFrom.value = firstDay;
        dateTo.value = `${y}-${pad(m + 1)}-${pad(lastDay)}`;
    } else if (preset === 'last_month') {
        const prevMonth = m === 0 ? 11 : m - 1;
        const prevYear = m === 0 ? y - 1 : y;
        const firstDay = `${prevYear}-${pad(prevMonth + 1)}-01`;
        const lastDay = new Date(prevYear, prevMonth + 1, 0).getDate();
        dateFrom.value = firstDay;
        dateTo.value = `${prevYear}-${pad(prevMonth + 1)}-${pad(lastDay)}`;
    } else if (preset === 'this_year') {
        dateFrom.value = `${y}-01-01`;
        dateTo.value = `${y}-12-31`;
    }
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    categoryId.value = 'all';
    userId.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    sortBy.value = 'date';
    sortDir.value = 'desc';
    perPage.value = '15';
    activeDatePreset.value = 'all';
};

const exportExpenses = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (status.value && status.value !== 'all') params.append('status', status.value);
    if (categoryId.value && categoryId.value !== 'all') params.append('category_id', categoryId.value);
    if (userId.value && userId.value !== 'all') params.append('user_id', userId.value);
    if (dateFrom.value) params.append('date_from', dateFrom.value);
    if (dateTo.value) params.append('date_to', dateTo.value);
    if (sortBy.value) params.append('sort_by', sortBy.value);
    if (sortDir.value) params.append('sort_dir', sortDir.value);

    window.location.href = `${route('admin.finance.expenses.export', undefined, false)}?${params.toString()}`;
};

// Currency Formatter
const formatCurrency = (val: any) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(num);
};

// Staff Dropdown Options
const staffOptions = computed(() => {
    return (props.users || []).map(u => {
        const staffNum = u.staff?.staff_number ? ` [${u.staff.staff_number}]` : '';
        return {
            value: u.id,
            label: `${u.name}${staffNum} (${u.email})`,
        };
    });
});

// Create & Edit Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null as string | null,
    title: '',
    description: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    expense_category_id: '' as string,
    user_id: '' as string,
});

const openCreate = () => {
    form.reset();
    form.id = null;
    form.user_id = '';
    form.date = new Date().toISOString().split('T')[0];
    isEditing.value = false;
    isModalOpen.value = true;
};

const openEdit = (expense: ExpenseItem) => {
    isDetailsOpen.value = false;
    cancelPendingAction();
    form.title = expense.title;
    form.description = expense.description || '';
    form.amount = String(expense.amount);
    form.date = expense.date ? expense.date.split('T')[0] : new Date().toISOString().split('T')[0];
    form.expense_category_id = expense.category?.id || '';
    form.user_id = expense.user?.id || '';
    form.id = expense.id;
    isEditing.value = true;
    isModalOpen.value = true;
};

const formatAmountInput = (val: string) => {
    let cleaned = val.replace(/[^0-9.]/g, '');
    const parts = cleaned.split('.');
    if (parts.length > 2) {
        cleaned = parts[0] + '.' + parts.slice(1).join('');
    }
    const integerPart = parts[0];
    const decimalPart = parts[1] !== undefined ? '.' + parts.slice(1).join('') : '';
    if (integerPart) {
        return Number(integerPart).toLocaleString('en-US') + decimalPart;
    }
    return cleaned;
};

watch(() => form.amount, (newVal) => {
    if (newVal) {
        const formatted = formatAmountInput(String(newVal));
        if (formatted !== newVal) {
            form.amount = formatted;
        }
    }
});

const submitExpense = () => {
    const rawAmount = String(form.amount).replace(/,/g, '');
    const formattedAmount = form.amount;
    form.amount = rawAmount;

    if (isEditing.value && form.id) {
        form.put(route('admin.finance.expenses.update', form.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Updated', text: 'Expense updated successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => {
                form.amount = formattedAmount;
            },
        });
    } else {
        form.post(route('admin.finance.expenses.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Created', text: 'Expense request submitted successfully.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => {
                form.amount = formattedAmount;
            },
        });
    }
};

// Details Modal State & Inline Actions (No stacked SweetAlert dialogs!)
const selectedExpense = ref<ExpenseItem | null>(null);
const isDetailsOpen = ref(false);
const pendingAction = ref<'approve' | 'reject' | 'delete' | null>(null);
const rejectionReason = ref('');
const rejectionError = ref('');
const isProcessingAction = ref(false);

const openDetails = (expense: ExpenseItem, action: 'approve' | 'reject' | 'delete' | null = null) => {
    selectedExpense.value = expense;
    pendingAction.value = action;
    rejectionReason.value = '';
    rejectionError.value = '';
    isDetailsOpen.value = true;
};

const cancelPendingAction = () => {
    pendingAction.value = null;
    rejectionReason.value = '';
    rejectionError.value = '';
};

const confirmApprove = (expense: ExpenseItem) => {
    isProcessingAction.value = true;
    router.post(route('admin.finance.expenses.approve', expense.id), {}, {
        onSuccess: () => {
            isDetailsOpen.value = false;
            cancelPendingAction();
            Swal.fire({
                icon: 'success',
                title: 'Approved',
                text: `Expense "${expense.title}" approved successfully.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onFinish: () => {
            isProcessingAction.value = false;
        },
    });
};

const confirmReject = (expense: ExpenseItem) => {
    if (!rejectionReason.value || !rejectionReason.value.trim()) {
        rejectionError.value = 'Please provide a clear reason for rejecting this expense.';
        return;
    }

    isProcessingAction.value = true;
    router.post(route('admin.finance.expenses.reject', expense.id), {
        rejection_reason: rejectionReason.value.trim(),
    }, {
        onSuccess: () => {
            isDetailsOpen.value = false;
            cancelPendingAction();
            Swal.fire({
                icon: 'success',
                title: 'Rejected',
                text: `Expense "${expense.title}" has been rejected.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onFinish: () => {
            isProcessingAction.value = false;
        },
    });
};

const confirmDelete = (expense: ExpenseItem) => {
    isProcessingAction.value = true;
    router.delete(route('admin.finance.expenses.destroy', expense.id), {
        onSuccess: () => {
            isDetailsOpen.value = false;
            cancelPendingAction();
            Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: 'Expense deleted successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onFinish: () => {
            isProcessingAction.value = false;
        },
    });
};

const getStatusBadgeVariant = (s: string) => {
    switch (s) {
        case 'approved': return 'default';
        case 'rejected': return 'destructive';
        case 'pending': return 'secondary';
        default: return 'outline';
    }
};
</script>

<template>
    <Head title="Expense Management" />
    <AdminLayout>
        <div class="w-full min-w-0 max-w-[1700px] mx-auto p-3 sm:p-5 md:p-6 space-y-4 sm:space-y-6 overflow-x-hidden">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 w-full min-w-0">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Expenses Management</h2>
                    <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                        Track, review, and approve institution-wide operational expenditures and claims.
                    </p>
                </div>
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto min-w-0">
                    <Button variant="outline" @click="exportExpenses" class="shadow-xs font-semibold text-xs sm:text-sm h-9 sm:h-10 min-w-0 px-2 sm:px-4">
                        <Download class="mr-1.5 sm:mr-2 h-4 w-4 shrink-0" />
                        <span class="truncate">Export CSV</span>
                    </Button>
                    <Button @click="openCreate" class="shadow-xs font-semibold text-xs sm:text-sm h-9 sm:h-10 min-w-0 px-2 sm:px-4">
                        <Plus class="mr-1.5 sm:mr-2 h-4 w-4 shrink-0" />
                        <span class="truncate">New Expense</span>
                    </Button>
                </div>
            </div>

            <!-- Smart KPI Summary Analytics Cards (2x2 Mobile, 4-Col Desktop) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4 w-full min-w-0">
                <!-- Card 1: Total Approved Outflow -->
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-3 sm:p-5 shadow-xs transition-all hover:shadow-md min-w-0 overflow-hidden">
                    <div class="flex items-center justify-between gap-1">
                        <h3 class="text-xs sm:text-sm font-medium text-muted-foreground truncate">Total Approved</h3>
                        <Wallet class="h-4 w-4 text-muted-foreground shrink-0" />
                    </div>
                    <div class="mt-1.5 sm:mt-2 flex items-baseline gap-1.5 min-w-0">
                        <span class="text-sm sm:text-xl md:text-2xl font-bold tracking-tight text-sidebar-foreground truncate font-mono">
                            {{ formatCurrency(stats.total_approved_amount) }}
                        </span>
                    </div>
                    <p class="mt-1 text-[10px] sm:text-xs text-muted-foreground truncate">
                        Cumulative approved outflow
                    </p>
                </div>

                <!-- Card 2: Pending Approvals with Live Pulse -->
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-3 sm:p-5 shadow-xs transition-all hover:shadow-md min-w-0 overflow-hidden">
                    <div class="flex items-center justify-between gap-1">
                        <h3 class="text-xs sm:text-sm font-medium text-muted-foreground truncate">Pending Approvals</h3>
                        <div class="relative shrink-0">
                            <Clock class="h-4 w-4 text-amber-500" />
                            <span v-if="stats.pending_count > 0" class="absolute -top-1 -right-1 flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                            </span>
                        </div>
                    </div>
                    <div class="mt-1.5 sm:mt-2 flex flex-wrap items-baseline gap-1 sm:gap-2 min-w-0">
                        <span class="text-sm sm:text-xl md:text-2xl font-bold tracking-tight text-sidebar-foreground">
                            {{ stats.pending_count }}
                        </span>
                        <span class="text-[10px] sm:text-xs font-semibold text-amber-600 dark:text-amber-400 font-mono truncate">
                            ({{ formatCurrency(stats.pending_amount) }})
                        </span>
                    </div>
                    <p class="mt-1 text-[10px] sm:text-xs text-muted-foreground truncate">
                        Awaiting managerial review
                    </p>
                </div>

                <!-- Card 3: This Month's Spending -->
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-3 sm:p-5 shadow-xs transition-all hover:shadow-md min-w-0 overflow-hidden">
                    <div class="flex items-center justify-between gap-1">
                        <h3 class="text-xs sm:text-sm font-medium text-muted-foreground truncate">This Month</h3>
                        <Calendar class="h-4 w-4 text-muted-foreground shrink-0" />
                    </div>
                    <div class="mt-1.5 sm:mt-2 flex items-baseline gap-1.5 min-w-0">
                        <span class="text-sm sm:text-xl md:text-2xl font-bold tracking-tight text-sidebar-foreground truncate font-mono">
                            {{ formatCurrency(stats.this_month_approved_amount) }}
                        </span>
                    </div>
                    <p class="mt-1 text-[10px] sm:text-xs text-muted-foreground truncate">
                        Approved for current month
                    </p>
                </div>

                <!-- Card 4: Rejected Claims -->
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-3 sm:p-5 shadow-xs transition-all hover:shadow-md min-w-0 overflow-hidden">
                    <div class="flex items-center justify-between gap-1">
                        <h3 class="text-xs sm:text-sm font-medium text-muted-foreground truncate">Rejected Claims</h3>
                        <AlertCircle class="h-4 w-4 text-muted-foreground shrink-0" />
                    </div>
                    <div class="mt-1.5 sm:mt-2 flex flex-wrap items-baseline gap-1 sm:gap-2 min-w-0">
                        <span class="text-sm sm:text-xl md:text-2xl font-bold tracking-tight text-sidebar-foreground">
                            {{ stats.rejected_count }}
                        </span>
                        <span class="text-[10px] sm:text-xs font-semibold text-rose-600 dark:text-rose-400 font-mono truncate">
                            ({{ formatCurrency(stats.rejected_amount) }})
                        </span>
                    </div>
                    <p class="mt-1 text-[10px] sm:text-xs text-muted-foreground truncate">
                        Declined expenditures
                    </p>
                </div>
            </div>

            <!-- Status Tabs & Filter Controls Card -->
            <Card class="shadow-xs border-border/80 w-full min-w-0 overflow-hidden">
                <CardContent class="p-3 sm:p-5 space-y-3 sm:space-y-4 w-full min-w-0">
                    <!-- Status Filter Tabs with Horizontal Scroll for Mobile -->
                    <div class="flex items-center justify-between gap-2 pb-2.5 sm:pb-3 border-b border-border/60 w-full min-w-0">
                        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar scroll-smooth pb-0.5 sm:pb-0 sm:flex-wrap flex-1 min-w-0">
                            <button
                                type="button"
                                @click="setStatusTab('all')"
                                :class="[
                                    'px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
                                    status === 'all'
                                        ? 'bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-muted/50 hover:bg-muted text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                All Expenses
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/15">
                                    {{ stats.status_counts.all }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="setStatusTab('pending')"
                                :class="[
                                    'px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
                                    status === 'pending'
                                        ? 'bg-amber-500 text-slate-950 shadow-xs'
                                        : 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                Pending Review
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/15">
                                    {{ stats.status_counts.pending }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="setStatusTab('approved')"
                                :class="[
                                    'px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
                                    status === 'approved'
                                        ? 'bg-emerald-600 text-white shadow-xs'
                                        : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Approved
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/15">
                                    {{ stats.status_counts.approved }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="setStatusTab('rejected')"
                                :class="[
                                    'px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
                                    status === 'rejected'
                                        ? 'bg-rose-600 text-white shadow-xs'
                                        : 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-700 dark:text-rose-400'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                Rejected
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/15">
                                    {{ stats.status_counts.rejected }}
                                </span>
                            </button>
                        </div>

                        <!-- Reset Filters Button -->
                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="sm"
                            @click="resetFilters"
                            class="text-xs text-muted-foreground hover:text-foreground h-8 px-2 shrink-0 gap-1"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Reset</span>
                        </Button>
                    </div>

                    <!-- Search and Advanced Filters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3 w-full min-w-0">
                        <!-- Search Box -->
                        <div class="relative sm:col-span-2 lg:col-span-2 w-full min-w-0">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search title, claimant, staff #..."
                                class="pl-9 h-9 text-xs w-full"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground text-xs"
                            >
                                &times;
                            </button>
                        </div>

                        <!-- Category Selector -->
                        <div class="w-full min-w-0">
                            <Select v-model="categoryId">
                                <SelectTrigger class="h-9 text-xs w-full">
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Categories</SelectItem>
                                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Date Preset / Date Range -->
                        <div class="w-full min-w-0">
                            <Select v-model="activeDatePreset" @update:modelValue="setDatePreset">
                                <SelectTrigger class="h-9 text-xs w-full">
                                    <SelectValue placeholder="Date Range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Time</SelectItem>
                                    <SelectItem value="today">Today</SelectItem>
                                    <SelectItem value="this_month">This Month</SelectItem>
                                    <SelectItem value="last_month">Last Month</SelectItem>
                                    <SelectItem value="this_year">This Year</SelectItem>
                                    <SelectItem value="custom">Custom Date Range</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sorting -->
                        <div class="w-full min-w-0">
                            <Select v-model="sortBy">
                                <SelectTrigger class="h-9 text-xs w-full">
                                    <SelectValue placeholder="Sort By" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="date">Sort by Date</SelectItem>
                                    <SelectItem value="amount">Sort by Amount</SelectItem>
                                    <SelectItem value="title">Sort by Title</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Custom Date Range Expandable -->
                    <div v-if="activeDatePreset === 'custom' || dateFrom || dateTo" class="pt-3 border-t grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2.5 sm:gap-3 w-full min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 text-xs min-w-0">
                            <span class="text-muted-foreground font-medium text-[11px] sm:text-xs">From:</span>
                            <Input type="date" v-model="dateFrom" class="h-8 text-xs w-full sm:w-36" />
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 text-xs min-w-0">
                            <span class="text-muted-foreground font-medium text-[11px] sm:text-xs">To:</span>
                            <Input type="date" v-model="dateTo" class="h-8 text-xs w-full sm:w-36" />
                        </div>
                        <div class="col-span-2 sm:col-span-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 text-xs min-w-0">
                            <span class="text-muted-foreground font-medium text-[11px] sm:text-xs">Order:</span>
                            <Select v-model="sortDir">
                                <SelectTrigger class="h-8 text-xs w-full sm:w-28">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="desc">Descending</SelectItem>
                                    <SelectItem value="asc">Ascending</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Content Container: Desktop Table vs. Mobile Cards -->
            <Card class="shadow-xs border-border/80 overflow-hidden w-full min-w-0">
                <CardContent class="p-0 w-full min-w-0">
                    <!-- 1. DESKTOP / TABLET DATA TABLE (>= md) -->
                    <div class="hidden md:block overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-muted/30">
                                <TableRow>
                                    <TableHead class="w-[120px]">Date</TableHead>
                                    <TableHead class="min-w-[220px]">Expense Details</TableHead>
                                    <TableHead class="w-[140px]">Category</TableHead>
                                    <TableHead class="w-[140px]">Amount</TableHead>
                                    <TableHead class="w-[130px]">Status</TableHead>
                                    <TableHead class="min-w-[160px]">Beneficiary</TableHead>
                                    <TableHead class="min-w-[160px]">Requester</TableHead>
                                    <TableHead class="text-right w-[150px]">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="expense in expenses.data" 
                                    :key="expense.id"
                                    class="hover:bg-muted/40 transition-colors cursor-pointer group"
                                    @click="openDetails(expense)"
                                >
                                    <!-- Date -->
                                    <TableCell class="font-medium whitespace-nowrap">
                                        <div class="text-xs font-bold text-foreground">
                                            {{ expense.date ? format(new Date(expense.date), 'MMM d, yyyy') : 'N/A' }}
                                        </div>
                                        <div class="text-[10px] text-muted-foreground font-mono">
                                            {{ expense.created_at ? formatDistanceToNow(new Date(expense.created_at), { addSuffix: true }) : '' }}
                                        </div>
                                    </TableCell>

                                    <!-- Title & Description -->
                                    <TableCell>
                                        <div class="font-bold text-sm text-foreground group-hover:text-primary transition-colors">
                                            {{ expense.title }}
                                        </div>
                                        <div v-if="expense.description" class="text-xs text-muted-foreground line-clamp-1 max-w-[280px]">
                                            {{ expense.description }}
                                        </div>
                                    </TableCell>

                                    <!-- Category -->
                                    <TableCell>
                                        <Badge variant="outline" class="font-semibold text-[11px] bg-background">
                                            {{ expense.category?.name || 'Uncategorized' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Amount -->
                                    <TableCell>
                                        <div class="font-extrabold text-sm text-foreground font-mono">
                                            {{ formatCurrency(expense.amount) }}
                                        </div>
                                    </TableCell>

                                    <!-- Status Badge -->
                                    <TableCell>
                                        <div class="flex flex-col gap-0.5">
                                            <Badge 
                                                :variant="getStatusBadgeVariant(expense.status)" 
                                                :class="[
                                                    'capitalize text-[11px] font-bold w-fit flex items-center gap-1',
                                                    expense.status === 'approved' ? 'bg-emerald-600 hover:bg-emerald-600 text-white' : '',
                                                    expense.status === 'pending' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30' : '',
                                                    expense.status === 'rejected' ? 'bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-500/30' : ''
                                                ]"
                                            >
                                                <CheckCircle v-if="expense.status === 'approved'" class="h-3 w-3" />
                                                <Clock v-else-if="expense.status === 'pending'" class="h-3 w-3" />
                                                <XCircle v-else class="h-3 w-3" />
                                                {{ expense.status }}
                                            </Badge>
                                            <span v-if="expense.status === 'approved' && expense.approver?.name" class="text-[10px] text-muted-foreground line-clamp-1">
                                                By {{ expense.approver.name }}
                                            </span>
                                            <span v-else-if="expense.status === 'rejected' && expense.rejection_reason" class="text-[10px] text-rose-600 dark:text-rose-400 line-clamp-1" :title="expense.rejection_reason">
                                                {{ expense.rejection_reason }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <!-- Beneficiary -->
                                    <TableCell>
                                        <div class="text-xs font-semibold text-foreground">
                                            {{ expense.user?.name || 'Unknown' }}
                                        </div>
                                        <div v-if="expense.user?.staff?.staff_number" class="text-[10px] text-muted-foreground font-mono">
                                            {{ expense.user.staff.staff_number }}
                                        </div>
                                    </TableCell>

                                    <!-- Requester -->
                                    <TableCell>
                                        <div class="text-xs text-foreground font-medium">
                                            {{ expense.requester?.name || expense.user?.name || 'Self' }}
                                        </div>
                                        <div v-if="(expense.requester?.staff?.staff_number || expense.user?.staff?.staff_number)" class="text-[10px] text-muted-foreground font-mono">
                                            {{ expense.requester?.staff?.staff_number || expense.user?.staff?.staff_number }}
                                        </div>
                                    </TableCell>

                                    <!-- Action Buttons -->
                                    <TableCell class="text-right" @click.stop>
                                        <div class="flex items-center justify-end gap-1">
                                            <!-- Quick View Button -->
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                                title="View Details"
                                                @click="openDetails(expense)"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>

                                            <!-- Actions for Pending Expenses -->
                                            <template v-if="expense.status === 'pending'">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    class="h-8 w-8 text-muted-foreground hover:text-primary"
                                                    title="Edit"
                                                    @click="openEdit(expense)"
                                                >
                                                    <Edit class="h-4 w-4" />
                                                </Button>

                                                <Button
                                                    v-if="canApproveExpenses"
                                                    size="icon"
                                                    variant="ghost"
                                                    class="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                                                    title="Approve"
                                                    @click="openDetails(expense, 'approve')"
                                                >
                                                    <CheckCircle class="h-4 w-4" />
                                                </Button>

                                                <Button
                                                    v-if="canApproveExpenses"
                                                    size="icon"
                                                    variant="ghost"
                                                    class="h-8 w-8 text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30"
                                                    title="Reject"
                                                    @click="openDetails(expense, 'reject')"
                                                >
                                                    <XCircle class="h-4 w-4" />
                                                </Button>

                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    class="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    title="Delete"
                                                    @click="openDetails(expense, 'delete')"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </template>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Desktop Empty State -->
                                <TableRow v-if="expenses.data.length === 0">
                                    <TableCell colspan="8" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <div class="h-12 w-12 rounded-2xl bg-muted/60 flex items-center justify-center text-muted-foreground">
                                                <Wallet class="h-6 w-6" />
                                            </div>
                                            <div>
                                                <h4 class="text-base font-bold">No expenses found</h4>
                                                <p class="text-xs text-muted-foreground mt-0.5 max-w-sm">
                                                    {{ hasActiveFilters ? 'No expenses match your active filter criteria. Try clearing some filters.' : 'There are no expense records logged in the system yet.' }}
                                                </p>
                                            </div>
                                            <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="resetFilters" class="text-xs">
                                                <RotateCcw class="mr-1.5 h-3.5 w-3.5" /> Clear Filters
                                            </Button>
                                            <Button v-else size="sm" @click="openCreate" class="text-xs">
                                                <Plus class="mr-1.5 h-3.5 w-3.5" /> Submit First Expense
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- 2. MOBILE FINANCIAL CARDS VIEW (< md) -->
                    <div class="block md:hidden divide-y divide-border/60 w-full min-w-0">
                        <div
                            v-for="expense in expenses.data"
                            :key="expense.id"
                            class="p-3 sm:p-4 space-y-3 hover:bg-muted/30 transition-colors active:bg-muted/40 cursor-pointer w-full min-w-0 overflow-hidden"
                            @click="openDetails(expense)"
                        >
                            <!-- Top: Date & Status Badge -->
                            <div class="flex items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                                    <span class="text-xs font-bold text-foreground truncate">
                                        {{ expense.date ? format(new Date(expense.date), 'MMM d, yyyy') : 'N/A' }}
                                    </span>
                                    <span class="text-[10px] text-muted-foreground font-mono shrink-0">
                                        {{ expense.created_at ? formatDistanceToNow(new Date(expense.created_at), { addSuffix: true }) : '' }}
                                    </span>
                                </div>
                                <Badge
                                    :variant="getStatusBadgeVariant(expense.status)"
                                    :class="[
                                        'capitalize text-[10px] font-bold px-2 py-0.5 flex items-center gap-1 shrink-0',
                                        expense.status === 'approved' ? 'bg-emerald-600 text-white' : '',
                                        expense.status === 'pending' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30' : '',
                                        expense.status === 'rejected' ? 'bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-500/30' : ''
                                    ]"
                                >
                                    <CheckCircle v-if="expense.status === 'approved'" class="h-2.5 w-2.5" />
                                    <Clock v-else-if="expense.status === 'pending'" class="h-2.5 w-2.5" />
                                    <XCircle v-else class="h-2.5 w-2.5" />
                                    {{ expense.status }}
                                </Badge>
                            </div>

                            <!-- Title, Description & Category Pill -->
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 min-w-0">
                                    <h4 class="font-bold text-sm text-foreground line-clamp-1 leading-snug min-w-0 flex-1">
                                        {{ expense.title }}
                                    </h4>
                                    <Badge variant="outline" class="text-[10px] font-semibold shrink-0 bg-background px-1.5 py-0 max-w-[120px] truncate">
                                        {{ expense.category?.name || 'Uncategorized' }}
                                    </Badge>
                                </div>
                                <p v-if="expense.description" class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                    {{ expense.description }}
                                </p>
                            </div>

                            <!-- Financial Amount & Beneficiary / Requester Row -->
                            <div class="flex items-end justify-between gap-2 pt-1 min-w-0">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block">
                                        Claimant
                                    </span>
                                    <div class="text-xs font-semibold text-foreground flex items-center gap-1 mt-0.5 min-w-0">
                                        <UserIcon class="h-3 w-3 text-muted-foreground shrink-0" />
                                        <span class="truncate">{{ expense.user?.name || 'Unknown' }}</span>
                                        <span v-if="expense.user?.staff?.staff_number" class="text-[10px] text-muted-foreground font-mono shrink-0">
                                            ({{ expense.user.staff.staff_number }})
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block">
                                        Amount
                                    </span>
                                    <div class="font-black text-base font-mono text-foreground mt-0.5">
                                        {{ formatCurrency(expense.amount) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Rejection Reason Callout if rejected -->
                            <div v-if="expense.status === 'rejected' && expense.rejection_reason" class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-[11px] text-rose-700 dark:text-rose-400 break-words">
                                <span class="font-bold">Reason:</span> {{ expense.rejection_reason }}
                            </div>

                            <!-- Touch-Friendly Card Actions Bar -->
                            <div class="flex items-center justify-between gap-1.5 sm:gap-2 pt-2 border-t border-border/50 min-w-0" @click.stop>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-8 text-xs font-medium px-2 flex-1 min-w-0"
                                    @click="openDetails(expense)"
                                >
                                    <Eye class="mr-1 h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">Details</span>
                                </Button>

                                <template v-if="expense.status === 'pending'">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="h-8 w-8 p-0 text-muted-foreground hover:text-primary shrink-0"
                                        title="Edit"
                                        @click="openEdit(expense)"
                                    >
                                        <Edit class="h-3.5 w-3.5" />
                                    </Button>

                                    <Button
                                        v-if="canApproveExpenses"
                                        size="sm"
                                        class="h-8 text-xs font-bold px-2 bg-emerald-600 hover:bg-emerald-700 text-white gap-1 shadow-xs shrink-0"
                                        @click="openDetails(expense, 'approve')"
                                    >
                                        <Check class="h-3.5 w-3.5 shrink-0" />
                                        <span>Approve</span>
                                    </Button>

                                    <Button
                                        v-if="canApproveExpenses"
                                        size="sm"
                                        variant="destructive"
                                        class="h-8 text-xs font-bold px-2 gap-1 shadow-xs shrink-0"
                                        @click="openDetails(expense, 'reject')"
                                    >
                                        <X class="h-3.5 w-3.5 shrink-0" />
                                        <span>Reject</span>
                                    </Button>
                                </template>
                            </div>
                        </div>

                        <!-- Mobile Empty State -->
                        <div v-if="expenses.data.length === 0" class="text-center py-10 px-4">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="h-12 w-12 rounded-2xl bg-muted/60 flex items-center justify-center text-muted-foreground">
                                    <Wallet class="h-6 w-6" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold">No expenses found</h4>
                                    <p class="text-xs text-muted-foreground mt-0.5 max-w-xs">
                                        {{ hasActiveFilters ? 'No expenses match your active filter criteria.' : 'There are no expense records logged in the system yet.' }}
                                    </p>
                                </div>
                                <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="resetFilters" class="text-xs">
                                    <RotateCcw class="mr-1.5 h-3.5 w-3.5" /> Clear Filters
                                </Button>
                                <Button v-else size="sm" @click="openCreate" class="text-xs">
                                    <Plus class="mr-1.5 h-3.5 w-3.5" /> Submit First Expense
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. RESPONSIVE INTEGRATED PAGINATION FOOTER -->
                    <div v-if="expenses.total > 0" class="flex flex-col sm:flex-row items-center justify-between border-t p-3 sm:p-4 px-3 sm:px-6 gap-3 sm:gap-4 bg-muted/20 w-full min-w-0">
                        <div class="flex flex-wrap items-center justify-between sm:justify-start w-full sm:w-auto gap-3 text-xs text-muted-foreground font-medium min-w-0">
                            <span class="text-center sm:text-left truncate">
                                Showing <strong class="text-foreground">{{ expenses.from || 0 }}</strong> to <strong class="text-foreground">{{ expenses.to || 0 }}</strong> of <strong class="text-foreground">{{ expenses.total }}</strong>
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span>Per page:</span>
                                <Select v-model="perPage">
                                    <SelectTrigger class="h-8 w-18 text-xs">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="15">15</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Pagination Links: Mobile Prev/Next vs Desktop Full Numbers -->
                        <div v-if="expenses.links && expenses.links.length > 3" class="w-full sm:w-auto flex items-center justify-center gap-1 min-w-0">
                            <!-- Desktop full links -->
                            <div class="hidden sm:flex flex-wrap items-center gap-1">
                                <Button 
                                    v-for="(link, i) in expenses.links" 
                                    :key="i"
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    :disabled="!link.url"
                                    as-child
                                    class="h-8 min-w-[32px] px-2 text-xs font-semibold rounded-md"
                                >
                                    <Link v-if="link.url" :href="link.url" preserve-scroll preserve-state v-html="link.label" />
                                    <span v-else v-html="link.label"></span>
                                </Button>
                            </div>

                            <!-- Mobile Compact Prev / Next links -->
                            <div class="flex sm:hidden items-center justify-between w-full gap-2 min-w-0">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="!expenses.links[0]?.url"
                                    as-child
                                    class="h-8 flex-1 text-xs font-semibold min-w-0 truncate"
                                >
                                    <Link v-if="expenses.links[0]?.url" :href="expenses.links[0].url" preserve-scroll preserve-state>
                                        &larr; Previous
                                    </Link>
                                    <span v-else>&larr; Previous</span>
                                </Button>
                                <span class="text-xs font-bold text-muted-foreground px-2 shrink-0">
                                    {{ expenses.current_page }} / {{ expenses.last_page }}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="!expenses.links[expenses.links.length - 1]?.url"
                                    as-child
                                    class="h-8 flex-1 text-xs font-semibold min-w-0 truncate"
                                >
                                    <Link v-if="expenses.links[expenses.links.length - 1]?.url" :href="expenses.links[expenses.links.length - 1].url" preserve-scroll preserve-state>
                                        Next &rarr;
                                    </Link>
                                    <span v-else>Next &rarr;</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Expense Details Modal with Inline Confirmation (Responsive & Touch Scrollable) -->
            <Dialog v-model:open="isDetailsOpen">
                <DialogContent class="max-w-xl max-h-[90vh] overflow-y-auto p-4 sm:p-6">
                    <DialogHeader>
                        <DialogTitle class="text-lg sm:text-xl font-bold flex items-center justify-between">
                            <span>Expense Overview</span>
                            <Badge 
                                :variant="getStatusBadgeVariant(selectedExpense?.status || '')"
                                :class="[
                                    'capitalize text-xs font-bold px-2.5 py-0.5',
                                    selectedExpense?.status === 'approved' ? 'bg-emerald-600 text-white' : '',
                                    selectedExpense?.status === 'pending' ? 'bg-amber-500/20 text-amber-700 dark:text-amber-400 border-amber-500/30' : '',
                                    selectedExpense?.status === 'rejected' ? 'bg-rose-500/20 text-rose-700 dark:text-rose-400 border-rose-500/30' : ''
                                ]"
                            >
                                {{ selectedExpense?.status }}
                            </Badge>
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Complete details, breakdown, and audit trail for this expenditure request.
                        </DialogDescription>
                    </DialogHeader>

                    <div v-if="selectedExpense" class="space-y-3 sm:space-y-4 py-1">
                        <!-- Top Banner / Amount Highlight -->
                        <div class="rounded-xl sm:rounded-2xl bg-muted/50 border p-3 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground block">
                                    Total Amount
                                </span>
                                <div class="text-xl sm:text-2xl font-black text-foreground font-mono mt-0.5">
                                    {{ formatCurrency(selectedExpense.amount) }}
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground block">
                                    Category
                                </span>
                                <Badge variant="outline" class="font-bold text-xs mt-0.5">
                                    {{ selectedExpense.category?.name || 'Uncategorized' }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Title and Justification -->
                        <div class="space-y-1.5">
                            <h4 class="text-sm font-bold text-foreground">
                                {{ selectedExpense.title }}
                            </h4>
                            <div class="bg-card rounded-xl p-3 border text-xs text-muted-foreground leading-relaxed whitespace-pre-wrap">
                                {{ selectedExpense.description || 'No detailed description provided for this expense.' }}
                            </div>
                        </div>

                        <!-- Claimant and Requester Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-xs">
                            <div class="rounded-xl border p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground block">
                                    Beneficiary (Claimant)
                                </span>
                                <div class="font-bold text-foreground">
                                    {{ selectedExpense.user?.name || 'Unknown' }}
                                </div>
                                <div v-if="selectedExpense.user?.staff?.staff_number" class="text-muted-foreground font-mono">
                                    Staff #: {{ selectedExpense.user.staff.staff_number }}
                                </div>
                                <div class="text-muted-foreground truncate">
                                    {{ selectedExpense.user?.email }}
                                </div>
                            </div>

                            <div class="rounded-xl border p-3 space-y-1">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground block">
                                    Requested By
                                </span>
                                <div class="font-bold text-foreground">
                                    {{ selectedExpense.requester?.name || selectedExpense.user?.name || 'Self' }}
                                </div>
                                <div class="text-muted-foreground">
                                    Date: {{ selectedExpense.date ? format(new Date(selectedExpense.date), 'MMM d, yyyy') : 'N/A' }}
                                </div>
                                <div class="text-muted-foreground font-mono text-[11px]">
                                    Submitted: {{ selectedExpense.created_at ? format(new Date(selectedExpense.created_at), 'MMM d, yyyy HH:mm') : 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <!-- Rejection Reason Callout (if already rejected) -->
                        <div v-if="selectedExpense.status === 'rejected' && selectedExpense.rejection_reason" class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-800 dark:text-rose-300">
                            <div class="flex items-center gap-1.5 font-bold mb-1">
                                <AlertCircle class="h-4 w-4 shrink-0" />
                                <span>Rejection Reason:</span>
                            </div>
                            <p class="leading-normal">{{ selectedExpense.rejection_reason }}</p>
                        </div>

                        <!-- Approver Audit Notice (if already approved) -->
                        <div v-if="selectedExpense.status === 'approved' && selectedExpense.approver" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 shrink-0 text-emerald-600" />
                            <span>Approved by <strong>{{ selectedExpense.approver.name }}</strong> ({{ selectedExpense.approver.email }}).</span>
                        </div>

                        <!-- INLINE ACTION: Approve Confirmation -->
                        <div v-if="pendingAction === 'approve'" class="rounded-xl sm:rounded-2xl border-2 border-emerald-500/40 bg-emerald-500/10 p-3.5 sm:p-4 space-y-3 animate-in fade-in zoom-in-95 duration-200">
                            <div class="flex items-start gap-2.5 sm:gap-3">
                                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                    <CheckCircle class="h-4 w-4 sm:h-5 sm:w-5" />
                                </div>
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Confirm Expense Approval</h5>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        Are you sure you want to approve "{{ selectedExpense.title }}" for <strong>{{ formatCurrency(selectedExpense.amount) }}</strong>?
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2 border-t border-emerald-500/20">
                                <Button size="sm" variant="outline" @click="cancelPendingAction" :disabled="isProcessingAction" class="w-full sm:w-auto text-xs">
                                    Cancel
                                </Button>
                                <Button size="sm" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs gap-1.5 shadow-sm" :disabled="isProcessingAction" @click="confirmApprove(selectedExpense)">
                                    <Check class="h-3.5 w-3.5" />
                                    {{ isProcessingAction ? 'Approving...' : 'Yes, Confirm Approval' }}
                                </Button>
                            </div>
                        </div>

                        <!-- INLINE ACTION: Reject Form -->
                        <div v-if="pendingAction === 'reject'" class="rounded-xl sm:rounded-2xl border-2 border-rose-500/40 bg-rose-500/10 p-3.5 sm:p-4 space-y-3 animate-in fade-in zoom-in-95 duration-200">
                            <div class="flex items-start gap-2.5 sm:gap-3">
                                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                    <XCircle class="h-4 w-4 sm:h-5 sm:w-5" />
                                </div>
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Reject Expense Request</h5>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        Please specify the reason for rejection. This will be displayed to the claimant.
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <Textarea
                                    v-model="rejectionReason"
                                    placeholder="Enter reason for rejection..."
                                    rows="3"
                                    class="text-xs bg-background"
                                    required
                                />
                                <p v-if="rejectionError" class="text-[11px] text-destructive font-medium">{{ rejectionError }}</p>
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2 border-t border-rose-500/20">
                                <Button size="sm" variant="outline" @click="cancelPendingAction" :disabled="isProcessingAction" class="w-full sm:w-auto text-xs">
                                    Cancel
                                </Button>
                                <Button size="sm" variant="destructive" class="w-full sm:w-auto font-bold text-xs gap-1.5 shadow-sm" :disabled="isProcessingAction" @click="confirmReject(selectedExpense)">
                                    <X class="h-3.5 w-3.5" />
                                    {{ isProcessingAction ? 'Rejecting...' : 'Yes, Reject Expense' }}
                                </Button>
                            </div>
                        </div>

                        <!-- INLINE ACTION: Delete Confirmation -->
                        <div v-if="pendingAction === 'delete'" class="rounded-xl sm:rounded-2xl border-2 border-rose-500/40 bg-rose-500/10 p-3.5 sm:p-4 space-y-3 animate-in fade-in zoom-in-95 duration-200">
                            <div class="flex items-start gap-2.5 sm:gap-3">
                                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                    <Trash2 class="h-4 w-4 sm:h-5 sm:w-5" />
                                </div>
                                <div>
                                    <h5 class="text-sm font-bold text-foreground">Delete Expense Record</h5>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        Are you sure you want to permanently delete "{{ selectedExpense.title }}"? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2 pt-2 border-t border-rose-500/20">
                                <Button size="sm" variant="outline" @click="cancelPendingAction" :disabled="isProcessingAction" class="w-full sm:w-auto text-xs">
                                    Cancel
                                </Button>
                                <Button size="sm" variant="destructive" class="w-full sm:w-auto font-bold text-xs gap-1.5 shadow-sm" :disabled="isProcessingAction" @click="confirmDelete(selectedExpense)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                    {{ isProcessingAction ? 'Deleting...' : 'Yes, Delete Record' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Dialog Footer -->
                    <DialogFooter class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2 border-t pt-4">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="() => { cancelPendingAction(); isDetailsOpen = false; }" 
                            class="w-full sm:w-auto text-xs"
                        >
                            {{ pendingAction ? 'Back' : 'Close' }}
                        </Button>

                        <!-- Standard Action Buttons (when no inline confirmation is active) -->
                        <div v-if="selectedExpense && selectedExpense.status === 'pending' && !pendingAction" class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                            <Button
                                v-if="canApproveExpenses"
                                size="sm"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs gap-1.5 flex-1 sm:flex-initial"
                                @click="pendingAction = 'approve'"
                            >
                                <Check class="h-3.5 w-3.5" /> Approve
                            </Button>
                            <Button
                                v-if="canApproveExpenses"
                                size="sm"
                                variant="destructive"
                                class="font-bold text-xs gap-1.5 flex-1 sm:flex-initial"
                                @click="pendingAction = 'reject'"
                            >
                                <X class="h-3.5 w-3.5" /> Reject
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                class="text-xs flex-1 sm:flex-initial"
                                @click="openEdit(selectedExpense)"
                            >
                                <Edit class="h-3.5 w-3.5" /> Edit
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-xs text-destructive hover:bg-destructive/10 flex-1 sm:flex-initial"
                                @click="pendingAction = 'delete'"
                            >
                                <Trash2 class="h-3.5 w-3.5" /> Delete
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Create / Edit Expense Modal (Responsive & Scrollable) -->
            <Dialog v-model:open="isModalOpen">
                <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto p-4 sm:p-6">
                    <DialogHeader>
                        <DialogTitle class="text-lg font-bold">
                            {{ isEditing ? 'Edit Expense Record' : 'Submit New Expense' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            {{ isEditing ? 'Update the details for this pending expenditure request.' : 'Fill in the information below to record or request an expense.' }}
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitExpense" class="space-y-3 sm:space-y-4 py-2">
                        <!-- Title -->
                        <div class="space-y-1">
                            <Label for="exp-title" class="text-xs font-bold">Expense Title *</Label>
                            <Input
                                id="exp-title"
                                v-model="form.title"
                                placeholder="e.g. Science Laboratory Reagents Replenishment"
                                class="text-xs"
                                required
                            />
                            <p v-if="form.errors.title" class="text-[11px] text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <!-- Amount & Date (Stack on small screens, 2-col on sm) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label for="exp-amount" class="text-xs font-bold">Amount (₦) *</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-muted-foreground text-xs">₦</span>
                                    <Input
                                        id="exp-amount"
                                        type="text"
                                        v-model="form.amount"
                                        placeholder="0.00"
                                        class="pl-7 text-xs font-mono font-bold"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.amount" class="text-[11px] text-destructive">{{ form.errors.amount }}</p>
                            </div>

                            <div class="space-y-1">
                                <Label for="exp-date" class="text-xs font-bold">Date *</Label>
                                <Input
                                    id="exp-date"
                                    type="date"
                                    v-model="form.date"
                                    class="text-xs"
                                    required
                                />
                                <p v-if="form.errors.date" class="text-[11px] text-destructive">{{ form.errors.date }}</p>
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="space-y-1">
                            <Label for="exp-cat" class="text-xs font-bold">Expense Category *</Label>
                            <Select v-model="form.expense_category_id" required>
                                <SelectTrigger id="exp-cat" class="text-xs">
                                    <SelectValue placeholder="Select Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.expense_category_id" class="text-[11px] text-destructive">{{ form.errors.expense_category_id }}</p>
                        </div>

                        <!-- Beneficiary (Claimant) -->
                        <div v-if="canRequestForOthers" class="space-y-1">
                            <Label class="text-xs font-bold">Requested For (Beneficiary / Claimant)</Label>
                            <SearchableSelect
                                v-model="form.user_id"
                                :items="staffOptions"
                                placeholder="Self (Logged In User)"
                                search-placeholder="Search staff by name or email..."
                            />
                            <p class="text-[10px] text-muted-foreground">
                                Leave blank to record as a personal expense for yourself.
                            </p>
                            <p v-if="form.errors.user_id" class="text-[11px] text-destructive">{{ form.errors.user_id }}</p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1">
                            <Label for="exp-desc" class="text-xs font-bold">Justification & Notes</Label>
                            <Textarea
                                id="exp-desc"
                                v-model="form.description"
                                rows="3"
                                placeholder="Provide brief rationale or breakdown of this expenditure..."
                                class="text-xs"
                            />
                            <p v-if="form.errors.description" class="text-[11px] text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <DialogFooter class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 border-t pt-4">
                            <Button type="button" variant="outline" @click="isModalOpen = false" class="w-full sm:w-auto text-xs">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto text-xs font-bold">
                                {{ form.processing ? 'Saving...' : (isEditing ? 'Update Expense' : 'Submit Request') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
