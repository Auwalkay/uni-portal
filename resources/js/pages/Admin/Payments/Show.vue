<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Printer, 
    ArrowLeft, 
    CreditCard, 
    Calendar, 
    User, 
    Hash,
    CheckCircle,
    XCircle,
    Building2,
    Briefcase,
    Download,
    ShieldCheck,
    Globe,
    CreditCard as PaymentIcon,
    History,
    ExternalLink,
    AlertCircle,
    Receipt
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';
import { route } from 'ziggy-js';

const props = defineProps<{
    payment: any;
}>();

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', 
        month: 'short', 
        year: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit'
    });
};

const formatCurrency = (amount: number) => {
    return '₦' + new Intl.NumberFormat('en-NG').format(amount);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'success': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'failed': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

const downloadReceipt = () => {
    window.open(route('admin.payments.download_receipt', props.payment.id), '_blank');
};

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Payment ${payment.gateway_reference || payment.reference}`" />

    <AdminLayout>
        <div class="w-full space-y-8 p-4 md:p-8 max-w-[1600px] mx-auto animate-in fade-in duration-500">
            
            <!-- Executive Header & Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 no-print">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <Button variant="ghost" size="icon" as-child class="rounded-full h-8 w-8">
                            <Link :href="route('admin.payments.index')">
                                <ArrowLeft class="w-4 h-4" />
                            </Link>
                        </Button>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Payment Audit</h1>
                    </div>
                    <p class="text-slate-500 flex items-center gap-2 pl-11">
                        <span class="font-mono text-xs">{{ payment.id }}</span>
                        <span class="text-slate-300">•</span>
                        <span>Recorded on {{ formatDate(payment.created_at) }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <Button variant="outline" @click="printPage" class="flex-1 md:flex-none">
                        <Printer class="w-4 h-4 mr-2" /> Print Audit
                    </Button>
                    
                    <Button 
                        v-if="payment.status === 'success'" 
                        @click="downloadReceipt" 
                        class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-700 text-white"
                    >
                        <Download class="w-4 h-4 mr-2" /> Download Receipt
                    </Button>
                </div>
            </div>

            <!-- Dashboard Stats Recap -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 no-print">
                <Card class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm border-slate-200 shadow-sm">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Transaction Value</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(payment.amount) }}</p>
                            </div>
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl text-blue-600">
                                <PaymentIcon class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="bg-white/50 dark:bg-slate-900/50 border-slate-200 shadow-sm">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Gateway</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white uppercase">{{ payment.gateway || 'Manual' }}</p>
                            </div>
                            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl text-slate-600">
                                <Globe class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="bg-white/50 dark:bg-slate-900/50 border-slate-200 shadow-sm">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Channel</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white capitalize">{{ payment.channel || 'Direct' }}</p>
                            </div>
                            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl text-slate-600">
                                <CreditCard class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card :class="[getStatusColor(payment.status), 'border shadow-sm transition-all duration-300']">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider opacity-70">Payment Status</p>
                                <p class="text-2xl font-black uppercase tracking-tight">{{ payment.status }}</p>
                            </div>
                            <div class="p-3 bg-white/50 rounded-2xl">
                                <CheckCircle v-if="payment.status === 'success'" class="w-6 h-6 text-emerald-600" />
                                <AlertCircle v-else-if="payment.status === 'pending'" class="w-6 h-6 text-amber-600" />
                                <XCircle v-else class="w-6 h-6 text-rose-600" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Main Audit Log (Center) -->
                <div class="lg:col-span-8 space-y-6">
                    <Card class="border-none shadow-xl bg-white dark:bg-slate-900 overflow-hidden relative">
                        <div class="h-1.5 w-full bg-blue-600"></div>
                        
                        <CardHeader class="px-8 pt-10 pb-8 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex flex-col md:flex-row justify-between gap-8">
                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <Badge variant="outline" class="text-xs px-3 py-1 font-bold bg-blue-50 text-blue-700 border-blue-200">
                                            PAYMENT LOG
                                        </Badge>
                                        <h2 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white uppercase">Reference</h2>
                                        <p class="font-mono text-xl text-blue-600 font-bold tracking-tighter">{{ payment.gateway_reference }}</p>
                                    </div>
                                </div>

                                <div class="text-left md:text-right space-y-6">
                                    <div class="space-y-1">
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Internal identifiers</p>
                                        <div class="space-y-1 text-sm">
                                            <p class="text-slate-600 dark:text-slate-400">Transaction ID: <span class="font-mono font-bold">{{ payment.transaction_id || 'N/A' }}</span></p>
                                            <p class="text-slate-600 dark:text-slate-400">Gateway ID: <span class="font-mono font-bold">{{ payment.gateway_id || 'N/A' }}</span></p>
                                            <p class="text-slate-600 dark:text-slate-400 font-mono">Ref: {{ payment.gateway_reference || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="p-0">
                            <div class="px-8 py-6 bg-slate-50/50 dark:bg-slate-950/20 border-b">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Invoice Association</h3>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200">
                                            <Receipt class="w-5 h-5 text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ payment.invoice?.reference || 'Direct Payment' }}</p>
                                            <p class="text-xs text-slate-500 capitalize">{{ payment.invoice?.type?.replace('_', ' ') || 'Miscellaneous' }}</p>
                                        </div>
                                    </div>
                                    <Button variant="outline" size="sm" as-child v-if="payment.invoice">
                                        <Link :href="route('admin.invoices.show', payment.invoice.id)">
                                            View Invoice <ExternalLink class="w-3.5 h-3.5 ml-2" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            <!-- Line Items (if breakdown exists) -->
                            <div v-if="payment.invoice?.items?.length > 0">
                                <Table>
                                    <TableHeader class="bg-slate-50 dark:bg-slate-950/50">
                                        <TableRow class="hover:bg-transparent border-b-0">
                                            <TableHead class="pl-8 h-12 text-slate-500 font-bold uppercase text-[10px] tracking-wider">Allocation / Description</TableHead>
                                            <TableHead class="h-12 text-right pr-8 text-slate-500 font-bold uppercase text-[10px] tracking-wider">Amount (NGN)</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="item in payment.invoice.items" :key="item.id" class="border-b-slate-100 dark:border-b-slate-800">
                                            <TableCell class="pl-8 py-5">
                                                <div class="font-semibold text-slate-700 dark:text-slate-200">{{ item.description }}</div>
                                            </TableCell>
                                            <TableCell class="text-right pr-8 py-5 font-mono text-slate-900 dark:text-white">
                                                {{ formatCurrency(item.amount) }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <div class="p-8 bg-slate-900 dark:bg-white flex flex-col items-end gap-3 text-white dark:text-slate-900">
                                <div class="flex justify-between w-full sm:w-80 text-sm opacity-70">
                                    <span>Total Captured Amount</span>
                                    <span class="font-mono font-bold">{{ formatCurrency(payment.amount) }}</span>
                                </div>
                                <Separator class="my-2 w-full sm:w-80 bg-white/20 dark:bg-slate-200 h-[1px]" />
                                <div class="flex justify-between w-full sm:w-80">
                                    <span class="font-black text-lg uppercase tracking-tight opacity-50">Settlement</span>
                                    <span class="font-black text-2xl">{{ formatCurrency(payment.amount) }}</span>
                                </div>
                            </div>
                        </CardContent>

                        <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center opacity-50 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-300 no-print">
                            <div class="flex items-center gap-3 text-xs font-medium text-slate-500">
                                <ShieldCheck class="w-4 h-4 text-emerald-500" />
                                <span>Verified Transaction Record</span>
                            </div>
                            <p class="text-[10px] font-mono text-slate-400 uppercase tracking-widest">Digital Audit Log #{{ (payment.gateway_reference || payment.reference || '').split('-')[1] || payment.id.split('-')[0] }}</p>
                        </div>
                    </Card>
                </div>

                <!-- Right Sidebar (Student & Info) -->
                <div class="lg:col-span-4 space-y-6 no-print">
                    
                    <!-- Student Identity Card -->
                    <Card class="border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader class="bg-slate-50/80 dark:bg-slate-900/80 border-b py-4">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <User class="w-4 h-4 text-slate-400" /> Payee Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6">
                            <div class="flex flex-col items-center text-center space-y-4">
                                <div class="relative">
                                    <img 
                                        class="h-24 w-24 rounded-full border-4 border-white dark:border-slate-800 shadow-xl" 
                                        :src="`https://ui-avatars.com/api/?name=${payment.user.name}&background=020617&color=fff&size=128`" 
                                        alt="Avatar" 
                                    />
                                    <div class="absolute bottom-0 right-0 bg-emerald-500 border-4 border-white dark:border-slate-900 h-6 w-6 rounded-full shadow-sm"></div>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ payment.user.name }}</h4>
                                    <p class="text-sm text-slate-500 font-medium">{{ payment.user.email }}</p>
                                </div>
                            </div>

                            <Separator class="my-6" />

                            <div class="space-y-4">
                                <div v-if="payment.user.student" class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 flex items-center gap-2">
                                        <Hash class="w-3.5 h-3.5" /> Matric No
                                    </span>
                                    <span class="font-bold font-mono text-slate-900 dark:text-slate-100">{{ payment.user.student.matriculation_number }}</span>
                                </div>

                                <div v-if="payment.user.student?.academic_department" class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <Briefcase class="w-3 h-3" /> Department
                                    </p>
                                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100 leading-tight">
                                        {{ payment.user.student.academic_department.name }}
                                    </p>
                                </div>

                                <div v-if="payment.user.student?.academic_department?.faculty" class="space-y-1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <Building2 class="w-3 h-3" /> Faculty
                                    </p>
                                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100 leading-tight">
                                        {{ payment.user.student.academic_department.faculty.name }}
                                    </p>
                                </div>

                                <div class="pt-4">
                                    <Button variant="outline" class="w-full text-xs font-bold" as-child>
                                        <Link :href="route('admin.students.show', payment.user.student?.id || '#')">
                                            View Student Profile
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Meta & Session -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-slate-50/50 dark:bg-slate-900/50 space-y-4">
                        <div class="flex items-center gap-2 text-slate-900 dark:text-slate-100">
                            <History class="w-4 h-4 text-slate-400" />
                            <h4 class="text-xs font-bold uppercase tracking-wider">Academic Meta</h4>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-500">Session</span>
                                <span class="font-bold text-blue-600">{{ payment.invoice?.session?.name || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                <span class="text-slate-500">Paid At</span>
                                <span class="text-slate-900 dark:text-slate-100">{{ formatDate(payment.paid_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Verification</span>
                                <span class="text-emerald-600 font-bold flex items-center gap-1">
                                    <ShieldCheck class="w-3 h-3" /> System Verified
                                </span>
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
    .max-w-\[1600px\] {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}

.transition-all {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
