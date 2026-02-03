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
import { ArrowLeft, CheckCircle, Printer } from 'lucide-vue-next';

const props = defineProps<{
    payroll: any;
    items: any;
}>();

const formatCurrency = (val: any) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);

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
