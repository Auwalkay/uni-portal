<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { 
    Filter, Search, CheckCircle, FileText, TrendingUp, TrendingDown, DollarSign, PieChart, Plus, Trash2, CreditCard,
    ArrowUpDown, ArrowUp, ArrowDown
} from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
  Avatar, AvatarFallback, AvatarImage,
} from '@/components/ui/avatar';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import Swal from 'sweetalert2';
import { format as formatDate } from 'date-fns';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
} from 'chart.js';
import { Line, Doughnut } from 'vue-chartjs';
import Pagination from '@/components/Pagination.vue';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger 
} from '@/components/ui/dialog';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, PointElement, LineElement, ArcElement);

const props = defineProps<{
    invoices: {
        data: any[],
        links: any[],
        meta: any
    };
    sessions: any[];
    filters: any;
    analytics: {
        total_expected: number;
        total_collected: number;
        total_outstanding: number;
        collection_rate: number;
        charts: {
            status_distribution: { labels: string[], datasets: any[] };
            revenue_trend: { labels: string[], datasets: any[] };
        };
    };
}>();

const filterForm = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    type: props.filters.type || '',
    session_id: props.filters.session_id || '',
    sort_field: props.filters.sort_field || 'created_at',
    sort_order: props.filters.sort_order || 'desc',
});

const manualPaymentForm = ref({
    invoice_id: '',
    invoice_ref: '',
    amount: 0,
    max_amount: 0,
    paid_at: new Date().toISOString().split('T')[0],
    channel: 'transfer',
    processing: false
});

const isPaymentDialogOpen = ref(false);

const openPaymentDialog = (invoice: any) => {
    manualPaymentForm.value = {
        invoice_id: invoice.id,
        invoice_ref: invoice.reference,
        amount: invoice.amount - (invoice.paid_amount || 0),
        max_amount: invoice.amount - (invoice.paid_amount || 0),
        paid_at: new Date().toISOString().split('T')[0],
        channel: 'transfer',
        processing: false
    };
    isPaymentDialogOpen.value = true;
};

const submitManualPayment = () => {
    manualPaymentForm.value.processing = true;
    router.post(route('admin.invoices.mark-as-paid', manualPaymentForm.value.invoice_id), {
        amount: manualPaymentForm.value.amount,
        paid_at: manualPaymentForm.value.paid_at,
        channel: manualPaymentForm.value.channel,
    }, {
        onSuccess: () => {
            isPaymentDialogOpen.value = false;
            Swal.fire('Paid!', 'Payment has been recorded.', 'success');
        },
        onFinish: () => {
            manualPaymentForm.value.processing = false;
        }
    });
};

const applyFilters = () => {
    router.get(route('admin.invoices.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const sortBy = (field: string) => {
    let order = 'asc';
    if (filterForm.value.sort_field === field) {
        order = filterForm.value.sort_order === 'asc' ? 'desc' : 'asc';
    }
    filterForm.value.sort_field = field;
    filterForm.value.sort_order = order;
    applyFilters();
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const formatInvoiceType = (type: string) => {
    if (!type) return 'N/A';
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'partial': return 'bg-blue-100 text-blue-800 border-blue-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const deleteInvoice = (id: string, ref: string) => {
    Swal.fire({
        title: 'Delete Invoice?',
        text: `Are you sure you want to delete invoice ${ref}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#3b82f6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.invoices.destroy', id), {
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Invoice has been deleted.', 'success');
                }
            });
        }
    });
};

const markAsPaid = (id: string, ref: string) => {
    Swal.fire({
        title: 'Mark as Paid?',
        text: `Are you sure you want to manually mark invoice ${ref} as fully paid? This will create a manual payment record.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark as paid!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.invoices.mark-as-paid', id), {}, {
                onSuccess: () => {
                     Swal.fire('Paid!', 'Invoice has been marked as paid.', 'success');
                }
            });
        }
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Invoices', href: '/admin/invoices' },
];
</script>

<template>
    <Head title="Invoice Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Invoice Management</h1>
                    <p class="text-muted-foreground">View and manage student invoices.</p>
                </div>
                <Button as-child>
                    <Link :href="route('admin.invoices.create')">
                        <Plus class="w-4 h-4 mr-2" /> Create Invoice
                    </Link>
                </Button>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(analytics.total_expected) }}</div>
                        <p class="text-xs text-muted-foreground">Expected from all invoices</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Collected</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(analytics.total_collected) }}</div>
                        <p class="text-xs text-muted-foreground">Total payments received</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Outstanding</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(analytics.total_outstanding) }}</div>
                        <p class="text-xs text-muted-foreground">Pending payments</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Collection Rate</CardTitle>
                        <PieChart class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ analytics.collection_rate }}%</div>
                        <p class="text-xs text-muted-foreground">Of expected revenue collected</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="grid gap-4 md:grid-cols-7">
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Revenue Trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px]">
                            <Line 
                                :data="analytics.charts.revenue_trend" 
                                :options="{ responsive: true, maintainAspectRatio: false }" 
                            />
                        </div>
                    </CardContent>
                </Card>
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Invoice Status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px] flex items-center justify-center">
                            <Doughnut 
                                :data="analytics.charts.status_distribution" 
                                :options="{ responsive: true, maintainAspectRatio: false }" 
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="space-y-1">
                    <Label>Search</Label>
                    <div class="relative">
                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="filterForm.search" placeholder="Ref, Name, Matric..." class="pl-8" />
                    </div>
                </div>
                <div class="space-y-1">
                    <Label>Session</Label>
                    <Select v-model="filterForm.session_id">
                         <SelectTrigger><SelectValue placeholder="All Sessions" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Sessions</SelectItem>
                             <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                <div class="space-y-1">
                    <Label>Status</Label>
                    <Select v-model="filterForm.status">
                         <SelectTrigger><SelectValue placeholder="All Status" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Status</SelectItem>
                             <SelectItem value="pending">Pending</SelectItem>
                             <SelectItem value="partial">Partial</SelectItem>
                             <SelectItem value="paid">Paid</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                <div class="space-y-1">
                    <Label>Type</Label>
                    <Select v-model="filterForm.type">
                         <SelectTrigger><SelectValue placeholder="All Types" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Types</SelectItem>
                             <SelectItem value="application_fee">Application Fee</SelectItem>
                             <SelectItem value="acceptance_fee">Acceptance Fee</SelectItem>
                             <SelectItem value="school_fee">School Fees</SelectItem>
                             <SelectItem value="hostel_fee">Hostel Fee</SelectItem>
                             <SelectItem value="late_payment_fine">Late Payment Fine</SelectItem>
                             <SelectItem value="other_fee">Other Fee</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                <Button variant="secondary" @click="applyFilters">
                    <Filter class="w-4 h-4 mr-2" /> Filter
                </Button>
            </div>

            <!-- Invoice List -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('reference')">
                                <div class="flex items-center gap-1">
                                    Reference
                                    <template v-if="filterForm.sort_field === 'reference'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('student')">
                                <div class="flex items-center gap-1">
                                    Student
                                    <template v-if="filterForm.sort_field === 'student'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('type')">
                                <div class="flex items-center gap-1">
                                    Type
                                    <template v-if="filterForm.sort_field === 'type'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Generated
                                    <template v-if="filterForm.sort_field === 'created_at'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('due_date')">
                                <div class="flex items-center gap-1">
                                    Due Date
                                    <template v-if="filterForm.sort_field === 'due_date'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('session')">
                                <div class="flex items-center gap-1">
                                    Session
                                    <template v-if="filterForm.sort_field === 'session'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('amount')">
                                <div class="flex items-center gap-1">
                                    Amount
                                    <template v-if="filterForm.sort_field === 'amount'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('paid_amount')">
                                <div class="flex items-center gap-1">
                                    Paid
                                    <template v-if="filterForm.sort_field === 'paid_amount'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('balance')">
                                <div class="flex items-center gap-1">
                                    Balance
                                    <template v-if="filterForm.sort_field === 'balance'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer select-none hover:bg-slate-50 transition-colors" @click="sortBy('status')">
                                <div class="flex items-center gap-1">
                                    Status
                                    <template v-if="filterForm.sort_field === 'status'">
                                        <ArrowUp v-if="filterForm.sort_order === 'asc'" class="w-3.5 h-3.5" />
                                        <ArrowDown v-else class="w-3.5 h-3.5" />
                                    </template>
                                    <ArrowUpDown v-else class="w-3 h-3 opacity-40" />
                                </div>
                            </TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                         <TableRow v-for="invoice in invoices.data" :key="invoice.id">
                            <TableCell class="font-mono font-medium text-xs">{{ invoice.reference }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Link :href="invoice.user?.student ? route('admin.students.show', invoice.user.student.id) : '#'" class="block shrink-0">
                                        <Avatar class="h-8 w-8 hover:opacity-80 transition-opacity">
                                            <AvatarImage :src="invoice.user?.profile_photo_url || ''" :alt="invoice.user?.name" />
                                            <AvatarFallback>{{ invoice.user?.name?.charAt(0) || 'U' }}</AvatarFallback>
                                        </Avatar>
                                    </Link>
                                    <div>
                                        <Link :href="invoice.user?.student ? route('admin.students.show', invoice.user.student.id) : '#'" class="hover:underline hover:text-indigo-600 transition-colors block">
                                            <div class="font-medium text-slate-900 dark:text-slate-100 text-sm">{{ invoice.user?.name }}</div>
                                        </Link>
                                        <div class="text-[10px] text-muted-foreground uppercase">{{ invoice.user?.student?.matriculation_number || invoice.user?.email }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-xs">
                                <Badge variant="secondary" class="bg-slate-100 hover:bg-slate-100/80 text-slate-800 border-slate-200">
                                    {{ formatInvoiceType(invoice.type) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-xs">{{ formatDate(new Date(invoice.created_at), 'MMM dd, yyyy') }}</TableCell>
                            <TableCell class="text-xs">{{ invoice.due_date ? formatDate(new Date(invoice.due_date), 'MMM dd, yyyy') : 'N/A' }}</TableCell>
                            <TableCell class="text-xs">{{ invoice.session?.name }}</TableCell>
                            <TableCell class="font-bold text-sm">{{ formatCurrency(invoice.amount) }}</TableCell>
                            <TableCell class="text-green-600 text-sm">{{ formatCurrency(invoice.paid_amount || 0) }}</TableCell>
                            <TableCell class="text-red-600 text-sm">{{ formatCurrency(invoice.amount - (invoice.paid_amount || 0)) }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" :class="getStatusColor(invoice.status)">
                                    {{ invoice.status.toUpperCase() }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button 
                                        v-if="invoice.status !== 'paid'" 
                                        size="sm" 
                                        variant="outline"
                                        class="text-green-600 border-green-200 hover:bg-green-50 hover:text-green-700 h-8" 
                                        @click="openPaymentDialog(invoice)"
                                    >
                                        <CreditCard class="w-4 h-4 mr-1" /> Pay
                                    </Button>
                                    <Button size="sm" variant="secondary" as-child title="View Details" class="h-8">
                                        <Link :href="route('admin.invoices.show', invoice.id)">
                                            View
                                        </Link>
                                    </Button>
                                    <Button 
                                        v-if="invoice.paid_amount == 0" 
                                        size="sm" 
                                        variant="ghost" 
                                        class="text-red-500 hover:text-red-600 hover:bg-red-50 h-8 w-8 p-0"
                                        @click="deleteInvoice(invoice.id, invoice.reference)"
                                        title="Delete Invoice"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="invoices.data.length === 0">
                            <TableCell colspan="11" class="h-24 text-center text-muted-foreground">
                                No invoices found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            
            <div class="flex justify-center py-4">
                <Pagination :links="invoices.links" />
            </div>

            <!-- Manual Payment Dialog -->
            <Dialog v-model:open="isPaymentDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Record Manual Payment</DialogTitle>
                        <DialogDescription>
                            Recording payment for invoice <strong>{{ manualPaymentForm.invoice_ref }}</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-4 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="paid_at" class="text-sm font-semibold">Payment Date</Label>
                                <Input 
                                    id="paid_at"
                                    type="date" 
                                    v-model="manualPaymentForm.paid_at" 
                                    class="w-full"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="channel" class="text-sm font-semibold">Payment Method</Label>
                                <Select v-model="manualPaymentForm.channel">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Method" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="transfer">Bank Transfer</SelectItem>
                                        <SelectItem value="pos">POS Terminal</SelectItem>
                                        <SelectItem value="cash">Cash Payment</SelectItem>
                                        <SelectItem value="manual">Manual Register</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="amount" class="text-sm font-semibold">Amount to Record (₦)</Label>
                            <div class="relative">
                                <Input 
                                    id="amount" 
                                    type="number" 
                                    v-model="manualPaymentForm.amount"
                                    :max="manualPaymentForm.max_amount"
                                    class="text-lg font-bold pl-8"
                                    step="0.01"
                                />
                                <span class="absolute left-3 top-2.5 text-slate-400 font-bold">₦</span>
                            </div>
                            <div v-if="manualPaymentForm.amount" class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider mt-1">
                                Confirming: {{ formatCurrency(manualPaymentForm.amount) }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Maximum allowed: {{ formatCurrency(manualPaymentForm.max_amount) }}
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isPaymentDialogOpen = false">Cancel</Button>
                        <Button 
                            :disabled="manualPaymentForm.processing || manualPaymentForm.amount <= 0 || manualPaymentForm.amount > manualPaymentForm.max_amount" 
                            @click="submitManualPayment"
                        >
                            {{ manualPaymentForm.processing ? 'Processing...' : 'Confirm Payment' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
