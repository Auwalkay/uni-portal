<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
import { format } from 'date-fns';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';
import { ArrowLeft, CheckCircle, Printer, Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import Pagination from '@/components/Pagination.vue';
import { Input } from '@/components/ui/input';
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
    filters?: {
        search?: string;
        status?: string;
        per_page?: string;
    };
}>();

const formatCurrency = (val: any) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);

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
                        <p class="text-muted-foreground">Status: {{ payroll.status }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="print"><Printer class="mr-2 h-4 w-4" /> Print</Button>
                    <Button v-if="payroll.status !== 'paid'" @click="markAsPaid" class="bg-emerald-600 hover:bg-emerald-700">
                        <CheckCircle class="mr-2 h-4 w-4" /> Mark as Paid
                    </Button>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Total Amount</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(payroll.total_amount) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium">Total Staff</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ items.total }}</div>
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
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
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
 
            <Card>
                <CardHeader>
                    <CardTitle>Staff Payments</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Staff Name</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Basic Salary</TableHead>
                                <TableHead>Allowances</TableHead>
                                <TableHead>Deductions</TableHead>
                                <TableHead>Net Salary</TableHead>
                                <TableHead>Bank Details</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in items.data" :key="item.id">
                                <TableCell class="font-medium">{{ item.staff?.user?.name }}</TableCell>
                                <TableCell>{{ item.staff?.department?.name || 'N/A' }}</TableCell>
                                <TableCell>{{ formatCurrency(item.basic_salary) }}</TableCell>
                                <TableCell class="text-emerald-600">+{{ formatCurrency(item.total_allowances) }}</TableCell>
                                <TableCell class="text-rose-600">-{{ formatCurrency(item.total_deductions) }}</TableCell>
                                <TableCell class="font-bold">{{ formatCurrency(item.net_salary) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    <div v-if="item.staff?.bank_name">
                                        {{ item.staff.bank_name }} - {{ item.staff.account_number }}
                                    </div>
                                    <div v-else>Not Set</div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="p-4 border-t flex justify-end no-print">
                        <Pagination :links="items.links" />
                    </div>
                </CardContent>
            </Card>
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
