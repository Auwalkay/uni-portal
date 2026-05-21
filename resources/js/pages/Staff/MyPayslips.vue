<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    Download, FileText, Calendar, CreditCard, Banknote, Wallet, DollarSign, Clock
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import { route } from 'ziggy-js';

const props = defineProps<{
    payslips: Array<any>;
}>();

const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(Number(amount));
};

const getMonthName = (month: number) => {
    return new Date(2000, month - 1).toLocaleString('default', { month: 'long' });
};

const breadcrumbs = [
    { title: 'Personal', href: '#' },
    { title: 'My Payslips', href: '#' }
];
</script>

<template>
    <Head title="My Payslips" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-[1200px] mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">My Payslips</h1>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">View and download your official salary records.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="w-5 h-5 text-indigo-600" />
                        Payslip History
                    </CardTitle>
                    <CardDescription>All your generated payslips organized by month and year.</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Period</TableHead>
                                <TableHead class="hidden md:table-cell">Basic Salary</TableHead>
                                <TableHead class="hidden md:table-cell">Allowances</TableHead>
                                <TableHead class="hidden md:table-cell">Deductions</TableHead>
                                <TableHead>Net Salary</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payslip in payslips" :key="payslip.id">
                                <TableCell class="font-bold text-slate-900 dark:text-slate-100">
                                    {{ getMonthName(payslip.payroll.month) }} {{ payslip.payroll.year }}
                                </TableCell>
                                <TableCell class="hidden md:table-cell">{{ formatCurrency(payslip.basic_salary) }}</TableCell>
                                <TableCell class="hidden md:table-cell text-green-600 font-medium">+ {{ formatCurrency(payslip.total_allowances) }}</TableCell>
                                <TableCell class="hidden md:table-cell text-red-600 font-medium">- {{ formatCurrency(payslip.total_deductions) }}</TableCell>
                                <TableCell class="font-extrabold text-indigo-600 dark:text-indigo-400">{{ formatCurrency(payslip.net_salary) }}</TableCell>
                                <TableCell>
                                    <Badge :variant="payslip.payroll.paid_at ? 'default' : 'secondary'">
                                        {{ payslip.payroll.paid_at ? 'Paid' : 'Processing' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <a :href="route('staff.payslips.download', { payrollItem: payslip.id })" target="_blank">
                                        <Button variant="outline" size="sm" class="hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950/30">
                                            <Download class="w-4 h-4 mr-1.5" />
                                            Download PDF
                                        </Button>
                                    </a>
                                </TableCell>
                            </TableRow>
                            
                            <TableRow v-if="!payslips || payslips.length === 0">
                                <TableCell colspan="7" class="h-48 text-center">
                                    <div class="flex flex-col items-center justify-center text-muted-foreground">
                                        <Clock class="w-10 h-10 mb-4 opacity-20" />
                                        <p class="font-medium">No payslips found</p>
                                        <p class="text-xs">Your payslips will appear here once payroll is generated.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div class="grid md:grid-cols-2 gap-6 pt-4">
                <Card class="bg-indigo-50/50 dark:bg-indigo-950/10 border-indigo-100 dark:border-indigo-900">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-bold text-indigo-900 dark:text-indigo-400 uppercase tracking-wider">Salary Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-indigo-700 dark:text-indigo-300">
                            Payslips are typically generated at the end of each month. If you notice any discrepancies in your records, please contact the Bursary department.
                        </p>
                    </CardContent>
                </Card>
                
                <Card class="bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Security Notice</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Your payslips contain sensitive financial information. Always ensure you are on a secure connection before downloading and do not share these documents.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
