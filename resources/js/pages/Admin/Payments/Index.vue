<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    Search, 
    Filter, 
    CreditCard, 
    X,
    ChevronDown,
    TrendingUp,
    CheckCircle,
    Clock,
    AlertCircle,
    Download,
    RefreshCw,
    Loader2
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
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
import { Label } from '@/components/ui/label';
const props = defineProps<{
    payments: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        session_id?: string;
        faculty_id?: string;
        department_id?: string;
        status?: string;
        method?: string;
        period?: string;
        start_date?: string;
        end_date?: string;
        sort_by?: string;
        sort_order?: string;
    };
    sessions: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string }>;
    departments: Array<{ id: string; name: string; faculty_id: string }>;
    stats: {
        total_revenue: number;
        today_revenue: number;
        successful_count: number;
        pending_count: number;
        failed_count: number;
    };
}>();

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');
const selectedStatus = ref(props.filters.status || 'ALL');
const selectedMethod = ref(props.filters.method || 'ALL');
const selectedPeriod = ref(props.filters.period || 'monthly');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const sortBy = ref(props.filters.sort_by || 'date');
const sortOrder = ref(props.filters.sort_order || 'desc');

// Derived state for stats
const totalAmount = computed(() => {
    return props.payments.data.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0);
});

// Computed departments based on selected faculty
const filteredDepartments = computed(() => {
    if (!selectedFaculty.value || selectedFaculty.value === 'ALL_FACULTIES_RESET_VALUE') return props.departments;
    return props.departments.filter(dept => dept.faculty_id === selectedFaculty.value);
});

// Apply filters handler
const applyFilters = () => {
    router.get(route('admin.payments.index'), { 
        search: search.value,
        session_id: selectedSession.value,
        faculty_id: selectedFaculty.value,
        department_id: selectedDepartment.value,
        status: selectedStatus.value,
        method: selectedMethod.value,
        period: selectedPeriod.value,
        start_date: startDate.value,
        end_date: endDate.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

const reconciliationExportUrl = computed(() => {
    const params = new URLSearchParams();
    params.append('export', 'reconciliation');
    
    if (search.value) params.append('search', search.value);
    if (selectedSession.value) params.append('session_id', selectedSession.value);
    if (selectedFaculty.value) params.append('faculty_id', selectedFaculty.value);
    if (selectedDepartment.value) params.append('department_id', selectedDepartment.value);
    if (selectedStatus.value) params.append('status', selectedStatus.value);
    if (selectedMethod.value) params.append('method', selectedMethod.value);
    if (selectedPeriod.value) params.append('period', selectedPeriod.value);
    if (startDate.value) params.append('start_date', startDate.value);
    if (endDate.value) params.append('end_date', endDate.value);
    if (sortBy.value) params.append('sort_by', sortBy.value);
    if (sortOrder.value) params.append('sort_order', sortOrder.value);

    return route('admin.payments.index') + '?' + params.toString();
});

const handleSort = (column: string) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
};

// Auto search with debounce
watch(search, debounce(() => {
    applyFilters();
}, 400));

// Auto trigger when select filters change
watch([selectedSession, selectedFaculty, selectedDepartment, selectedStatus, selectedMethod, selectedPeriod, startDate, endDate], () => {
    applyFilters();
});

// Auto clear department if faculty mismatch
watch(selectedFaculty, () => {
    if (selectedFaculty.value && selectedDepartment.value) {
         const dept = props.departments.find(d => d.id === selectedDepartment.value);
         if (dept && dept.faculty_id !== selectedFaculty.value) {
              selectedDepartment.value = '';
         }
    }
});

// Sort immediately when headers are clicked
watch([sortBy, sortOrder], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedSession.value = '';
    selectedFaculty.value = '';
    selectedDepartment.value = '';
    selectedStatus.value = 'ALL';
    selectedMethod.value = 'ALL';
    selectedPeriod.value = 'monthly';
    startDate.value = '';
    endDate.value = '';
    sortBy.value = 'date';
    sortOrder.value = 'desc';
    applyFilters();
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};

const getStatusVariant = (status: string) => {
    switch(status) {
        case 'paid': return 'default'; // Using deafult/green if configured, or we can use specific classes
        case 'pending': return 'secondary';
        case 'failed': return 'destructive';
        default: return 'outline';
    }
};

const getStatusClass = (status: string) => {
     switch(status) {
        case 'success': return 'bg-green-100 text-green-800 hover:bg-green-200 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-yellow-200';
        case 'failed': return 'bg-red-100 text-red-800 hover:bg-red-200 border-red-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const downloadReceipt = (paymentId: string) => {
    window.open(route('admin.payments.download_receipt', paymentId), '_blank');
};

const requeryingId = ref<string | null>(null);

const hasPermission = (permission: string) => {
    const user = usePage().props.auth?.user as any;
    if (!user) return false;
    return user.permissions?.includes(permission) || user.roles?.includes('admin') || user.roles?.includes('super_admin');
};

const canRequery = computed(() => {
    return hasPermission('verify_payments') || hasPermission('manual_payment_override') || hasPermission('manage_payments');
});

const requeryPayment = (paymentId: string) => {
    requeryingId.value = paymentId;
    router.post(route('payments.verify', paymentId), {}, {
        preserveScroll: true,
        onFinish: () => {
            requeryingId.value = null;
        }
    });
};
</script>

<template>
    <Head title="Payments Management" />

    <AdminLayout>
        <div class="py-8 px-6 space-y-6 w-full max-w-none">
            
            <!-- Header & Stats -->
            <div class="flex flex-col gap-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">Payments</h1>
                        <p class="text-muted-foreground mt-1">Manage, search, and track all student payment records.</p>
                    </div>
                    <Button as-child variant="outline" class="border-primary/20 text-primary hover:bg-primary/5 shadow-sm">
                        <a :href="reconciliationExportUrl">
                            <Download class="w-4 h-4 mr-2" /> Export Reconciliation Report
                        </a>
                    </Button>
                </div>
                
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <Card class="bg-primary/5 border-primary/20 shadow-none">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <CreditCard class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats?.total_revenue || 0) }}</div>
                        <p class="text-xs text-muted-foreground">All time collected</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Revenue</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats?.today_revenue || 0) }}</div>
                        <p class="text-xs text-muted-foreground">Collected today</p>
                        </CardContent>
                    </Card>

                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Successful</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.successful_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">Paid transactions</p>
                        </CardContent>
                    </Card>

                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.pending_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">Awaiting payment</p>
                        </CardContent>
                    </Card>

                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Failed</CardTitle>
                        <AlertCircle class="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.failed_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">Unsuccessful attempts</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-card p-4 rounded-xl border shadow-sm space-y-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="relative w-full sm:w-[250px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Search Reference/Name</Label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                              type="search"
                              placeholder="Search reference, name, matric no, invoice..."
                              class="pl-8"
                              v-model="search"
                              @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>
                    
                    <div class="w-[180px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Academic Session</Label>
                        <Select v-model="selectedSession">
                            <SelectTrigger class="w-full">
                              <SelectValue placeholder="Session" />
                            </SelectTrigger>
                            <SelectContent>
                               <SelectItem value="ALL_SESSIONS_RESET_VALUE">All Sessions</SelectItem>
                              <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.name }}
                              </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-[180px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Faculty</Label>
                        <Select v-model="selectedFaculty">
                            <SelectTrigger class="w-full">
                              <SelectValue placeholder="Faculty" />
                            </SelectTrigger>
                            <SelectContent>
                               <SelectItem value="ALL_FACULTIES_RESET_VALUE">All Faculties</SelectItem>
                              <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                                {{ faculty.name }}
                              </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-[200px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Department</Label>
                        <Select v-model="selectedDepartment" :disabled="!selectedFaculty || selectedFaculty === 'ALL_FACULTIES_RESET_VALUE'">
                            <SelectTrigger class="w-full">
                              <SelectValue placeholder="Department" />
                            </SelectTrigger>
                            <SelectContent>
                               <SelectItem value="ALL_DEPARTMENTS_RESET_VALUE">All Departments</SelectItem>
                              <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                              </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-[140px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Payment Status</Label>
                        <Select v-model="selectedStatus">
                            <SelectTrigger class="w-full">
                              <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Status</SelectItem>
                                <SelectItem value="success">Successful</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="failed">Failed</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-[160px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Payment Method</Label>
                        <Select v-model="selectedMethod">
                            <SelectTrigger class="w-full">
                              <SelectValue placeholder="Method" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Methods</SelectItem>
                                <SelectItem value="card">Card Payment</SelectItem>
                                <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                                <SelectItem value="squadco">Squadco Gateway</SelectItem>
                                <SelectItem value="manual">Manual Register</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-[140px]">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Period</Label>
                        <Select v-model="selectedPeriod">
                            <SelectTrigger class="w-full">
                              <SelectValue placeholder="Period" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Time</SelectItem>
                                <SelectItem value="daily">Daily</SelectItem>
                                <SelectItem value="weekly">Weekly</SelectItem>
                                <SelectItem value="monthly">Monthly</SelectItem>
                                <SelectItem value="yearly">Yearly</SelectItem>
                                <SelectItem value="custom">Custom Date</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="w-[150px]" v-if="selectedPeriod === 'custom'">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">Start Date</Label>
                        <Input type="date" v-model="startDate" class="w-full" />
                    </div>

                    <div class="w-[150px]" v-if="selectedPeriod === 'custom'">
                        <Label class="text-xs font-semibold text-muted-foreground mb-1.5 block">End Date</Label>
                        <Input type="date" v-model="endDate" class="w-full" />
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <Button 
                            variant="default" 
                            @click="applyFilters"
                            class="h-10 px-4 font-bold bg-primary hover:bg-primary/90"
                        >
                            Apply Filters
                        </Button>
                        <Button 
                            v-if="search || selectedSession || selectedFaculty || selectedDepartment || selectedStatus !== 'ALL' || selectedMethod !== 'ALL' || selectedPeriod !== 'monthly' || startDate || endDate" 
                            variant="ghost" 
                            @click="clearFilters"
                            class="text-destructive hover:text-destructive hover:bg-destructive/10 h-10"
                        >
                            <X class="w-4 h-4 mr-2" />
                            Clear Filters
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Reference</TableHead>
                            <TableHead>
                                <div class="flex items-center gap-2">
                                    <span>Student</span>
                                    <button @click="handleSort('name')" class="text-slate-400 hover:text-primary transition-colors font-bold text-[9px] border border-slate-200 px-1.5 py-0.5 rounded" :class="sortBy === 'name' ? 'bg-primary/5 text-primary border-primary/20' : ''">
                                        Name {{ sortBy === 'name' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                                    </button>
                                    <button @click="handleSort('reg_number')" class="text-slate-400 hover:text-primary transition-colors font-bold text-[9px] border border-slate-200 px-1.5 py-0.5 rounded" :class="sortBy === 'reg_number' ? 'bg-primary/5 text-primary border-primary/20' : ''">
                                        Reg No {{ sortBy === 'reg_number' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                                    </button>
                                </div>
                            </TableHead>
                            <TableHead>Type / Session</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead class="cursor-pointer hover:text-primary transition-colors" @click="handleSort('status')">
                                Status {{ sortBy === 'status' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                            </TableHead>
                            <TableHead class="cursor-pointer hover:text-primary transition-colors" @click="handleSort('date')">
                                Date {{ sortBy === 'date' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                            </TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="payment in payments.data" :key="payment.id">
                            <TableCell>
                                <div class="font-medium font-mono">{{ payment.gateway_reference }}</div>
                                <div class="text-xs text-muted-foreground capitalize">{{ payment.channel || 'Manual' }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                   <!-- Avatar fallback -->
                                   <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center text-xs font-bold text-muted-foreground overflow-hidden">
                                        <img :src="`https://ui-avatars.com/api/?name=${payment.user.name}&background=random`" alt="Avatar" />
                                   </div>
                                   <div>
                                       <div class="font-medium">{{ payment.user.name }}</div>
                                       <div class="text-xs text-muted-foreground flex flex-col">
                                           <span>{{ payment.user.student?.matriculation_number || payment.user.email }}</span>
                                           <span v-if="payment.user.student?.academic_department" class="text-[10px] opacity-75">
                                               {{ payment.user.student?.academic_department?.name }}
                                           </span>
                                       </div>
                                   </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="capitalize text-sm">{{ payment.invoice?.type?.replace('_', ' ') || 'Payment' }}</div>
                                <Badge variant="outline" class="mt-1 text-[10px]">
                                    {{ payment.invoice?.session?.name || 'N/A' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="font-bold">
                                {{ formatCurrency(payment.amount) }}
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusClass(payment.status)" variant="outline">
                                    {{ payment.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">
                                {{ formatDate(payment.paid_at) }}
                            </TableCell>
                            <TableCell class="text-right flex items-center justify-end gap-2">
                                <Button 
                                    v-if="payment.status !== 'success' && canRequery" 
                                    variant="outline" 
                                    size="sm" 
                                    @click="requeryPayment(payment.id)" 
                                    :disabled="requeryingId === payment.id"
                                    title="Requery Payment Status from Gateway"
                                    class="text-indigo-600 border-indigo-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                >
                                    <Loader2 v-if="requeryingId === payment.id" class="w-4 h-4 animate-spin" />
                                    <RefreshCw v-else class="w-4 h-4" />
                                </Button>
                                <Button v-if="payment.status === 'success'" variant="outline" size="sm" @click="downloadReceipt(payment.id)" title="Download Receipt">
                                    <Download class="w-4 h-4" />
                                </Button>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="route('admin.payments.show', payment.id)">
                                        View
                                    </Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="payments.data.length === 0">
                            <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                No payments found. Try adjusting your filters.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                 <!-- Pagination -->
                <CardFooter class="flex items-center justify-between border-t p-4" v-if="payments.total > 0">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ payments.from }}</strong>-<strong>{{ payments.to }}</strong> of <strong>{{ payments.total }}</strong>
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in payments.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AdminLayout>
</template>

