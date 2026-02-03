<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, Printer, CreditCard, CheckCircle2, Clock, Calendar, User, Mail, School, Building, RefreshCw } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

const props = defineProps<{
    invoice: any;
    payments: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Invoices', href: '/admin/invoices' },
    { title: props.invoice.reference, href: `/admin/invoices/${props.invoice.id}` },
];

const formatCurrency = (value: number) => {
    return '₦' + new Intl.NumberFormat('en-NG').format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid': return 'bg-emerald-500 hover:bg-emerald-600 text-white border-transparent';
        case 'partial': return 'bg-blue-500 hover:bg-blue-600 text-white border-transparent';
        default: return 'bg-amber-500 hover:bg-amber-600 text-white border-transparent';
    }
};

const printInvoice = () => {
    window.print();
};

const markAsPaid = () => {
    if (confirm('Are you sure you want to mark this invoice as fully paid manually?')) {
        router.post(route('admin.invoices.mark-as-paid', props.invoice.id));
    }
};

const verifyPayment = (paymentId: string) => {
    router.post(route('admin.payments.verify', paymentId), {}, {
        onSuccess: () => {
            // Toast handled by layout
        }
    });
};
</script>

<template>
    <Head :title="`Invoice ${invoice.reference}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="route('admin.invoices.index')">
                            <ArrowLeft class="w-4 h-4 mr-2" /> Back
                        </Link>
                    </Button>
                    <h1 class="text-2xl font-bold tracking-tight">Invoice Details</h1>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="printInvoice">
                        <Printer class="w-4 h-4 mr-2" /> Print
                    </Button>
                    <Button v-if="invoice.status !== 'paid'" @click="markAsPaid">
                        <CreditCard class="w-4 h-4 mr-2" /> Mark as Paid
                    </Button>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6 print:block print:space-y-6">
                <!-- Main Invoice Card -->
                <div class="md:col-span-2 space-y-6">
                    <Card class="print:shadow-none print:border-none">
                        <CardHeader class="border-b pb-6">
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Invoice Reference</p>
                                    <h2 class="text-3xl font-mono font-bold">{{ invoice.reference }}</h2>
                                    <Badge :class="['mt-2', getStatusColor(invoice.status)]">
                                        {{ invoice.status.toUpperCase() }}
                                    </Badge>
                                </div>
                                <div class="text-right space-y-1">
                                    <p class="text-sm text-muted-foreground">Issue Date</p>
                                    <p class="font-medium">{{ formatDate(invoice.created_at) }}</p>
                                    <p class="text-sm text-muted-foreground mt-2">Due Date</p>
                                    <p class="font-medium">{{ formatDate(invoice.due_date) }}</p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-6">
                            <!-- Bill To Section -->
                            <div class="grid sm:grid-cols-2 gap-8 mb-8 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
                                <div class="space-y-3">
                                    <h3 class="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-2">
                                        <User class="w-3.5 h-3.5" /> Billed To
                                    </h3>
                                    <div class="space-y-1">
                                        <p class="font-bold text-lg">{{ invoice.user.name }}</p>
                                        <div class="text-sm text-muted-foreground flex items-center gap-2">
                                            <Mail class="w-3.5 h-3.5" /> {{ invoice.user.email }}
                                        </div>
                                        <div v-if="invoice.user.student" class="text-sm text-muted-foreground space-y-1 mt-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                                            <p><span class="font-medium text-foreground">Matric No:</span> {{ invoice.user.student.matriculation_number }}</p>
                                            <p class="flex items-center gap-2">
                                                <Building class="w-3.5 h-3.5" /> {{ invoice.user.student.department?.name || 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                     <h3 class="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-2">
                                        <School class="w-3.5 h-3.5" /> Session Details
                                    </h3>
                                    <div class="space-y-1">
                                        <p class="font-medium">{{ invoice.session?.name || 'N/A' }} Session</p>
                                        <p class="text-sm text-muted-foreground capitalize">{{ invoice.type.replace('_', ' ') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Line Items -->
                            <h3 class="text-sm font-semibold mb-4">Invoice Items</h3>
                            <div class="rounded-md border overflow-hidden">
                                <Table>
                                    <TableHeader class="bg-slate-50 dark:bg-slate-900">
                                        <TableRow>
                                            <TableHead>Description</TableHead>
                                            <TableHead class="text-right">Amount</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="item in invoice.items" :key="item.id">
                                            <TableCell class="font-medium">{{ item.description }}</TableCell>
                                            <TableCell class="text-right">{{ formatCurrency(item.amount) }}</TableCell>
                                        </TableRow>
                                        <TableRow v-if="!invoice.items || invoice.items.length === 0">
                                            <TableCell class="font-medium capitalize">{{ invoice.type.replace('_', ' ') }}</TableCell>
                                            <TableCell class="text-right">{{ formatCurrency(invoice.amount) }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <!-- Totals -->
                            <div class="flex flex-col items-end gap-2 mt-6 pt-6 border-t">
                                <div class="flex justify-between w-full sm:w-64">
                                    <span class="text-muted-foreground">Subtotal:</span>
                                    <span class="font-medium">{{ formatCurrency(invoice.amount) }}</span>
                                </div>
                                <div class="flex justify-between w-full sm:w-64">
                                    <span class="text-muted-foreground">Paid Amount:</span>
                                    <span class="font-medium text-emerald-600">- {{ formatCurrency(invoice.paid_amount) }}</span>
                                </div>
                                <Separator class="my-2 w-full sm:w-64" />
                                <div class="flex justify-between w-full sm:w-64">
                                    <span class="font-bold text-lg">Balance Due:</span>
                                    <span class="font-bold text-lg">{{ formatCurrency(invoice.amount - invoice.paid_amount) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar / Payment History -->
                <div class="space-y-6">
                    <Card class="print:hidden">
                        <CardHeader>
                            <CardTitle class="text-base flex items-center gap-2">
                                <Clock class="w-4 h-4 text-blue-500" /> Payment History
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="relative pl-4 border-l-2 border-slate-100 dark:border-slate-800 space-y-6">
                                <div v-for="payment in payments" :key="payment.id" class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-950"></div>
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="font-bold">{{ formatCurrency(payment.amount) }}</p>
                                            <Button 
                                                v-if="payment.status !== 'success'" 
                                                variant="outline" 
                                                size="sm" 
                                                class="h-6 px-2 text-xs"
                                                @click="verifyPayment(payment.id)"
                                            >
                                                <RefreshCw class="w-3 h-3 mr-1" /> Verify
                                            </Button>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ formatDate(payment.paid_at) }}</p>
                                        <Badge variant="outline" class="text-[10px] h-5 px-1.5 uppercase font-mono">
                                            {{ payment.channel }}
                                        </Badge>
                                        <p class="text-[10px] text-muted-foreground font-mono truncate w-full" :title="payment.gateway_reference">
                                            ref: {{ payment.gateway_reference }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="payments.length === 0" class="text-sm text-muted-foreground italic">
                                    No payments recorded yet.
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Contact/Support Info (Optional) -->
                    <Card class="bg-indigo-50 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900 print:hidden">
                        <CardContent class="p-4 flex items-start gap-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg text-indigo-600 dark:text-indigo-400">
                                <Mail class="w-4 h-4" />
                            </div>
                            <div>
                                <h4 class="font-medium text-sm text-indigo-900 dark:text-indigo-200">Need Help?</h4>
                                <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-0.5">
                                    Issues with this invoice? Contact the Bursary department.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white;
    }
}
</style>
