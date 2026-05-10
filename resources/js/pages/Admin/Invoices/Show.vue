<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { 
    ArrowLeft, Printer, CreditCard, CheckCircle2, Clock, Calendar, 
    User, Mail, School, Building, RefreshCw, Download, ShieldCheck,
    AlertCircle, Wallet, History
} from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { useForm } from '@inertiajs/vue3';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger 
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref, computed } from 'vue';

const props = defineProps<{
    auth: {
        user: {
            permissions: string[];
        };
    };
    invoice: any;
    payments: any[];
}>();

const hasPermission = (permission: string) => {
    return props.auth.user.permissions.includes(permission);
};

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
        case 'paid': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'partial': return 'bg-blue-50 text-blue-700 border-blue-200';
        default: return 'bg-amber-50 text-amber-700 border-amber-200';
    }
};

const balance = computed(() => props.invoice.amount - props.invoice.paid_amount);
const progressPercentage = computed(() => Math.min(100, (props.invoice.paid_amount / props.invoice.amount) * 100));

const printInvoice = () => {
    window.print();
};

const manualPaymentForm = useForm({
    amount: balance.value,
});

const isDialogOpen = ref(false);

const markAsPaid = () => {
    manualPaymentForm.post(route('admin.invoices.mark-as-paid', props.invoice.id), {
        onSuccess: () => {
            isDialogOpen.value = false;
        },
    });
};

const verifyPayment = (paymentId: string) => {
    router.post(route('admin.payments.verify', paymentId), {}, {
        onSuccess: () => {
            // Toast handled by layout
        }
    });
};

const downloadReceipt = (paymentId: string) => {
    window.open(route('student.payments.download', paymentId), '_blank');
};
</script>

<template>
    <Head :title="`Invoice ${invoice.reference}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="w-full space-y-8 p-4 md:p-8">
            
            <!-- Executive Header & Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 no-print">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <Button variant="ghost" size="icon" as-child class="rounded-full h-8 w-8">
                            <Link :href="route('admin.invoices.index')">
                                <ArrowLeft class="w-4 h-4" />
                            </Link>
                        </Button>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Invoice Review</h1>
                    </div>
                    <p class="text-slate-500 flex items-center gap-2 pl-11">
                        <span class="font-mono">{{ invoice.reference }}</span>
                        <span class="text-slate-300">•</span>
                        <span>Issued on {{ formatDate(invoice.created_at) }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <Button variant="outline" @click="printInvoice" class="flex-1 md:flex-none">
                        <Printer class="w-4 h-4 mr-2" /> Print
                    </Button>
                    
                    <Dialog v-if="invoice.status !== 'paid' && hasPermission('manual_payment_override')" v-model:open="isDialogOpen">
                        <DialogTrigger as-child>
                            <Button class="flex-1 md:flex-none bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-50 dark:hover:bg-slate-200 dark:text-slate-900">
                                <CreditCard class="w-4 h-4 mr-2" /> Record Payment
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Manual Payment Entry</DialogTitle>
                                <DialogDescription>
                                    Record a payment received via offline channels.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-lg border space-y-2 mb-4">
                                    <div class="flex justify-between text-sm text-slate-500">
                                        <span>Current Balance:</span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(balance) }}</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label for="amount" class="text-sm font-semibold">Amount to Record (₦)</Label>
                                    <div class="relative">
                                        <Input 
                                            id="amount" 
                                            type="number" 
                                            v-model="manualPaymentForm.amount"
                                            :max="balance"
                                            class="text-lg font-bold pl-8"
                                        />
                                        <span class="absolute left-3 top-2.5 text-slate-400 font-bold">₦</span>
                                    </div>
                                    <div v-if="manualPaymentForm.amount" class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">
                                        Confirming: {{ formatCurrency(manualPaymentForm.amount) }}
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Note: This will create a permanent manual payment record for this student.
                                    </p>
                                </div>
                            </div>
                            <DialogFooter class="sm:justify-between">
                                <Button variant="ghost" @click="isDialogOpen = false">Discard</Button>
                                <Button :disabled="manualPaymentForm.processing" @click="markAsPaid" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                                    {{ manualPaymentForm.processing ? 'Recording...' : 'Confirm & Post Payment' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Smart Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 no-print">
                <Card class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border-slate-200 shadow-sm">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Billed</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(invoice.amount) }}</p>
                            </div>
                            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl text-slate-600 dark:text-slate-400">
                                <Wallet class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="bg-emerald-50/50 dark:bg-emerald-950/10 border-emerald-100 shadow-sm">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600/80">Total Collected</p>
                                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ formatCurrency(invoice.paid_amount) }}</p>
                            </div>
                            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-2xl text-emerald-600">
                                <CheckCircle2 class="w-6 h-6" />
                            </div>
                        </div>
                        <div class="mt-4 w-full bg-emerald-100 dark:bg-emerald-900/30 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full transition-all duration-500" :style="{ width: `${progressPercentage}%` }"></div>
                        </div>
                    </CardContent>
                </Card>

                <Card :class="[balance > 0 ? 'bg-amber-50/50 border-amber-100' : 'bg-slate-50 border-slate-100', 'shadow-sm']">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Balance</p>
                                <p :class="['text-2xl font-bold', balance > 0 ? 'text-amber-700' : 'text-slate-400']">
                                    {{ formatCurrency(balance) }}
                                </p>
                            </div>
                            <div :class="['p-3 rounded-2xl', balance > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400']">
                                <AlertCircle class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Main Content (Invoice View) -->
                <div class="lg:col-span-8 space-y-6">
                    <Card class="border-none shadow-xl bg-white dark:bg-slate-900 overflow-hidden relative">
                        <!-- Decorative top bar -->
                        <div class="h-1.5 w-full bg-slate-900 dark:bg-slate-50"></div>
                        
                        <CardHeader class="px-8 pt-10 pb-8 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex flex-col md:flex-row justify-between gap-8">
                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <Badge variant="outline" :class="['text-xs px-3 py-1 font-bold', getStatusColor(invoice.status)]">
                                            {{ invoice.status.toUpperCase() }}
                                        </Badge>
                                        <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white uppercase">Invoice</h2>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Student Information</h3>
                                        <div class="space-y-0.5">
                                            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ invoice.user.name }}</p>
                                            <p class="text-sm text-slate-500">{{ invoice.user.student?.matriculation_number || 'N/A' }}</p>
                                            <p class="text-sm text-slate-500">{{ invoice.user.student?.department?.name || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-left md:text-right space-y-6">
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Invoice Meta</p>
                                        <div class="space-y-1 text-sm">
                                            <p class="text-slate-600 dark:text-slate-400 font-mono"># {{ invoice.reference }}</p>
                                            <p class="text-slate-600 dark:text-slate-400">Session: <span class="font-bold">{{ invoice.session?.name || 'N/A' }}</span></p>
                                            <p class="text-slate-600 dark:text-slate-400">Category: <span class="capitalize font-bold">{{ invoice.type.replace('_', ' ') }}</span></p>
                                        </div>
                                    </div>
                                    
                                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 md:border-t-0 md:pt-0">
                                        <div class="flex md:flex-col justify-between gap-1">
                                            <span class="text-xs font-bold text-slate-400 uppercase">Payment Due</span>
                                            <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatDate(invoice.due_date) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="p-0">
                            <!-- Line Items Table -->
                            <Table>
                                <TableHeader class="bg-slate-50 dark:bg-slate-950/50">
                                    <TableRow class="hover:bg-transparent border-b-0">
                                        <TableHead class="pl-8 h-12 text-slate-500 font-bold uppercase text-[10px] tracking-wider">Service / Description</TableHead>
                                        <TableHead class="h-12 text-right pr-8 text-slate-500 font-bold uppercase text-[10px] tracking-wider">Amount (NGN)</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in invoice.items" :key="item.id" class="border-b-slate-100 dark:border-b-slate-800">
                                        <TableCell class="pl-8 py-5">
                                            <div class="font-semibold text-slate-700 dark:text-slate-200">{{ item.description }}</div>
                                            <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Academic Charge</div>
                                        </TableCell>
                                        <TableCell class="text-right pr-8 py-5 font-mono text-slate-900 dark:text-white">
                                            {{ formatCurrency(item.amount) }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!invoice.items || invoice.items.length === 0" class="border-b-slate-100 dark:border-b-slate-800">
                                        <TableCell class="pl-8 py-5">
                                            <div class="font-semibold text-slate-700 dark:text-slate-200 capitalize">{{ invoice.type.replace('_', ' ') }}</div>
                                            <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Full Payment Requirement</div>
                                        </TableCell>
                                        <TableCell class="text-right pr-8 py-5 font-mono text-slate-900 dark:text-white">
                                            {{ formatCurrency(invoice.amount) }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <!-- Financial Recap -->
                            <div class="p-8 bg-slate-50/50 dark:bg-slate-950/20 flex flex-col items-end gap-3">
                                <div class="flex justify-between w-full sm:w-80 text-sm">
                                    <span class="text-slate-500 font-medium">Subtotal</span>
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ formatCurrency(invoice.amount) }}</span>
                                </div>
                                <div class="flex justify-between w-full sm:w-80 text-sm">
                                    <span class="text-slate-500 font-medium">Total Paid to Date</span>
                                    <span class="font-mono font-bold text-emerald-600">{{ formatCurrency(invoice.paid_amount) }}</span>
                                </div>
                                <Separator class="my-2 w-full sm:w-80 bg-slate-200 dark:bg-slate-800 h-[2px]" />
                                <div class="flex justify-between w-full sm:w-80">
                                    <span class="font-black text-lg uppercase tracking-tight text-slate-400">Balance Due</span>
                                    <span class="font-black text-2xl text-slate-900 dark:text-white">{{ formatCurrency(balance) }}</span>
                                </div>
                            </div>
                        </CardContent>

                        <!-- Footer Note -->
                        <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center opacity-50 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-300 no-print">
                            <div class="flex items-center gap-3 text-xs font-medium text-slate-500">
                                <ShieldCheck class="w-4 h-4 text-emerald-500" />
                                <span>Verified MIU Digital Document</span>
                            </div>
                            <p class="text-[10px] font-mono text-slate-400 uppercase tracking-widest">Powered by MIU ERP System</p>
                        </div>
                    </Card>
                </div>

                <!-- Transaction History Sidebar -->
                <div class="lg:col-span-4 space-y-6 no-print">
                    
                    <Card class="border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader class="bg-slate-50/80 dark:bg-slate-900/80 border-b py-4">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <History class="w-4 h-4 text-slate-400" /> Transaction History
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                <div v-for="payment in payments" :key="payment.id" class="p-5 space-y-3 hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-0.5">
                                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(payment.amount) }}</p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ formatDate(payment.paid_at) }}</p>
                                        </div>
                                        <Badge 
                                            variant="secondary" 
                                            :class="['text-[10px] font-bold uppercase px-2', payment.status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']"
                                        >
                                            {{ payment.status }}
                                        </Badge>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Badge variant="outline" class="text-[10px] h-5 font-mono text-slate-500 capitalize">
                                            {{ payment.channel }}
                                        </Badge>
                                        <div v-if="payment.recorder" class="text-[10px] text-slate-400 flex items-center gap-1">
                                            <User class="w-3 h-3" /> Recorded by {{ payment.recorder.name.split(' ')[0] }}
                                        </div>
                                    </div>

                                    <div class="pt-2 flex items-center gap-2">
                                        <Button 
                                            v-if="payment.status === 'success'"
                                            size="sm" 
                                            variant="secondary" 
                                            class="h-8 w-full text-xs font-bold"
                                            @click="downloadReceipt(payment.id)"
                                        >
                                            <Download class="w-3.5 h-3.5 mr-2" /> Receipt
                                        </Button>
                                        <Button 
                                            v-if="payment.status !== 'success'" 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 w-full text-xs font-bold text-blue-600 border-blue-100 hover:bg-blue-50"
                                            @click="verifyPayment(payment.id)"
                                        >
                                            <RefreshCw class="w-3.5 h-3.5 mr-2" /> Verify Status
                                        </Button>
                                    </div>
                                </div>
                                <div v-if="payments.length === 0" class="p-10 text-center space-y-2">
                                    <div class="mx-auto w-10 h-10 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center text-slate-300">
                                        <Clock class="w-5 h-5" />
                                    </div>
                                    <p class="text-sm text-slate-400">No payments detected.</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Administrative Metadata -->
                    <div class="p-6 rounded-xl border border-slate-200 bg-slate-50/50 dark:bg-slate-900/50 space-y-4">
                        <div class="flex items-center gap-2 text-slate-900 dark:text-slate-100">
                            <ShieldCheck class="w-4 h-4 text-slate-400" />
                            <h4 class="text-xs font-bold uppercase tracking-wider">System Info</h4>
                        </div>
                        <div class="space-y-2 text-[11px]">
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-500">Internal ID</span>
                                <span class="font-mono text-slate-900 dark:text-slate-100">{{ invoice.id.split('-')[0] }}...</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-500">Last Modified</span>
                                <span class="text-slate-900 dark:text-slate-100">{{ formatDate(invoice.updated_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Origin</span>
                                <span class="text-slate-900 dark:text-slate-100">Student Portal</span>
                            </div>
                        </div>
                    </div>
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
        background: white !important;
    }
    .max-w-7xl {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}

/* Custom transitions */
.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
