<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { format as formatDate } from 'date-fns';
import { CreditCard, FileText, Download, Clock, RefreshCw, ArrowLeft, CheckCircle2, XCircle, AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const page = usePage();

const props = defineProps<{
    invoice: {
        id: string;
        reference: string;
        type: string;
        amount: number;
        paid_amount: number;
        status: string;
        due_date: string;
        created_at: string;
        updated_at: string;
        items: Array<{
            id: number;
            description: string;
            amount: number;
        }>;
        session?: {
            id: string;
            name: string;
            school_fee_payment_enabled?: boolean;
        };
        payments?: Array<{
            id: string;
            gateway_reference: string;
            amount: number;
            paid_at?: string;
            created_at?: string;
            channel?: string;
            status: string;
            gateway?: string;
        }>;
    };
    admin_charge_splittable: boolean;
}>();

const isPaymentModalOpen = ref(false);
const paymentOption = ref('full');
const requeryingPaymentId = ref<string | null>(null);

const isRequeryModalOpen = ref(false);
const manualReferenceInput = ref('');
const isRequeryingRef = ref(false);

const hasDepartment = computed(() => {
    const student = (page.props.auth as any)?.user?.student;
    return Boolean(student?.department_id || student?.program?.department_id || student?.programme?.department_id);
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid':
        case 'success':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'pending':
            return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'partial':
            return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'cancelled':
        case 'failed':
            return 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatType = (type: string) => {
    return type.replace('_', ' ').toUpperCase();
};

const formatGatewayName = (gateway?: string) => {
    if (!gateway) return 'Gateway';
    switch (gateway.toLowerCase()) {
        case 'seerbit': return 'SeerBit';
        case 'paystack': return 'Paystack';
        case 'squadco':
        case 'squad': return 'Squad';
        case 'flutterwave': return 'Flutterwave';
        case 'remita': return 'Remita';
        case 'manual': return 'Manual / Admin';
        case 'scholarship': return 'Scholarship';
        default: return gateway.charAt(0).toUpperCase() + gateway.slice(1);
    }
};

const fullBalance = computed(() => {
    return parseFloat(String(props.invoice.amount)) - parseFloat(String(props.invoice.paid_amount || 0));
});

const minPaymentAmount = computed(() => {
    const totalNetInvoice = parseFloat(String(props.invoice.amount));
    if (props.invoice.type === 'hostel_fee') {
        return totalNetInvoice * 0.75;
    }

    const isSplittable = String(props.admin_charge_splittable) === 'true' || props.admin_charge_splittable === true;
    const adminChargeItem = props.invoice.items?.find((i: any) => 
        i.description.toLowerCase().includes('administrative') || 
        i.description.toLowerCase().includes('admin charge')
    );
    
    const adminAmount = adminChargeItem ? parseFloat(adminChargeItem.amount) : 0;
    const academicNetPortion = totalNetInvoice - adminAmount;
    
    let minPayment = totalNetInvoice / 2;

    if (!isSplittable && adminAmount > 0) {
        minPayment = (academicNetPortion / 2) + adminAmount;
    }

    return minPayment;
});

const installmentAmount = computed(() => {
    return Math.min(minPaymentAmount.value, fullBalance.value);
});

const activePaymentAmount = computed(() => {
    return paymentOption.value === 'half' ? installmentAmount.value : fullBalance.value;
});

const canPayHalf = computed(() => {
    if (props.invoice.type !== 'school_fee' && props.invoice.type !== 'hostel_fee') {
        return false;
    }
    
    const balance = Number(props.invoice.amount) - Number(props.invoice.paid_amount || 0);
    if (balance <= 1) return false;

    if (Number(props.invoice.paid_amount || 0) >= minPaymentAmount.value) {
        return false;
    }

    return true;
});

const requeryPayment = (paymentId: string) => {
    requeryingPaymentId.value = paymentId;
    router.post(route('student.payments.requery', paymentId), {}, {
        onFinish: () => {
            requeryingPaymentId.value = null;
        }
    });
};

const submitRequeryReference = () => {
    if (!manualReferenceInput.value.trim()) return;
    isRequeryingRef.value = true;
    router.post(route('student.payments.requery_reference'), {
        reference: manualReferenceInput.value.trim()
    }, {
        onFinish: () => {
            isRequeryingRef.value = false;
            isRequeryModalOpen.value = false;
            manualReferenceInput.value = '';
        }
    });
};

const submitPayment = () => {
    router.post(route('student.payments.pay', props.invoice.id), {
        amount: activePaymentAmount.value.toFixed(2)
    }, {
        onFinish: () => {
            isPaymentModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Invoice ${invoice.reference}`" />

    <StudentLayout>
        <div class="space-y-6 p-6">
            <!-- Top Navigation & Title Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <Link 
                        :href="route('student.payments.index')" 
                        class="inline-flex items-center text-xs font-semibold text-muted-foreground hover:text-foreground transition-colors mb-1"
                    >
                        <ArrowLeft class="w-3.5 h-3.5 mr-1" /> Back to Financials
                    </Link>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl md:text-3xl font-bold tracking-tight font-mono">{{ invoice.reference }}</h2>
                        <Badge variant="outline" :class="getStatusColor(invoice.status)" class="text-xs uppercase px-2.5 py-0.5">
                            {{ invoice.status }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Session: <span class="font-medium text-foreground">{{ invoice.session?.name || 'N/A' }}</span> • 
                        Type: <span class="font-medium text-foreground">{{ formatType(invoice.type) }}</span> • 
                        Created: <span class="font-medium text-foreground">{{ formatDate(new Date(invoice.created_at), 'MMM d, yyyy') }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="isRequeryModalOpen = true">
                        <RefreshCw class="mr-2 h-4 w-4" /> Verify Reference
                    </Button>
                    <Button 
                        v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'" 
                        :disabled="!hasDepartment"
                        @click="isPaymentModalOpen = true"
                        size="sm"
                    >
                        <CreditCard class="mr-2 h-4 w-4" /> Pay Now
                    </Button>
                </div>
            </div>

            <!-- Overview Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="bg-card">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Invoice Amount</p>
                        <p class="text-2xl font-extrabold mt-1 font-mono text-foreground">{{ formatCurrency(invoice.amount) }}</p>
                    </CardContent>
                </Card>
                <Card class="bg-card">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Amount Paid</p>
                        <p class="text-2xl font-extrabold mt-1 font-mono text-emerald-600 dark:text-emerald-400">
                            {{ formatCurrency(Number(invoice.paid_amount || 0)) }}
                        </p>
                    </CardContent>
                </Card>
                <Card class="bg-card">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Remaining Balance</p>
                        <p class="text-2xl font-extrabold mt-1 font-mono text-rose-600 dark:text-rose-400">
                            {{ formatCurrency(fullBalance) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Invoice Line Items Breakdown (5 Columns) -->
                <Card class="lg:col-span-5">
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <FileText class="h-4 w-4 text-primary" /> Invoice Line Items
                        </CardTitle>
                        <CardDescription>Itemized breakdown of billing components.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="text-right">Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <tr v-if="!invoice.items || invoice.items.length === 0">
                                    <td colspan="2" class="p-4 text-center text-xs text-muted-foreground italic">
                                        No item breakdown available.
                                    </td>
                                </tr>
                                <TableRow v-for="item in invoice.items" :key="item.id">
                                    <TableCell class="text-sm font-medium">{{ item.description }}</TableCell>
                                    <TableCell class="text-right font-mono text-sm">{{ formatCurrency(item.amount) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <div class="mt-4 pt-3 border-t flex justify-between items-center text-sm font-bold">
                            <span>Total Net Amount</span>
                            <span class="font-mono text-base">{{ formatCurrency(invoice.amount) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Attempts List (7 Columns) -->
                <Card class="lg:col-span-7">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <CreditCard class="h-4 w-4 text-primary" /> Payment Attempts History
                                </CardTitle>
                                <CardDescription>All attempted transaction records for this invoice.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!invoice.payments || invoice.payments.length === 0" class="p-8 text-center border border-dashed rounded-lg">
                            <Clock class="mx-auto h-8 w-8 text-muted-foreground/60 mb-2" />
                            <p class="text-sm font-medium text-foreground">No payment attempts found</p>
                            <p class="text-xs text-muted-foreground mt-1">You have not initiated any payment transactions for this invoice yet.</p>
                        </div>
                        <div v-else class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
                            <div 
                                v-for="payment in invoice.payments" 
                                :key="payment.id" 
                                class="p-4 rounded-xl border bg-card hover:bg-accent/40 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-mono font-bold text-sm">{{ payment.gateway_reference }}</span>
                                        <Badge variant="outline" :class="getStatusColor(payment.status)" class="text-[10px] px-2 py-0 uppercase">
                                            {{ payment.status }}
                                        </Badge>
                                        <Badge variant="secondary" class="text-[10px] px-2 py-0 font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ formatGatewayName(payment.gateway) }}
                                        </Badge>
                                    </div>
                                    <div class="text-xs text-muted-foreground flex flex-wrap items-center gap-2 mt-1">
                                        <span>Amount: <strong class="text-foreground font-mono">{{ formatCurrency(payment.amount) }}</strong></span>
                                        <span>•</span>
                                        <span>Gateway: <strong class="text-foreground">{{ formatGatewayName(payment.gateway) }}</strong></span>
                                        <span>•</span>
                                        <span>Channel: <strong>{{ payment.channel || 'Card / Web' }}</strong></span>
                                        <span>•</span>
                                        <span>{{ payment.paid_at ? formatDate(new Date(payment.paid_at), 'MMM d, yyyy HH:mm') : (payment.created_at ? formatDate(new Date(payment.created_at), 'MMM d, yyyy HH:mm') : 'N/A') }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <Button 
                                        v-if="payment.status === 'pending' || payment.status === 'failed'"
                                        variant="outline"
                                        size="sm"
                                        :disabled="requeryingPaymentId === payment.id"
                                        @click="requeryPayment(payment.id)"
                                        class="h-8 text-xs border-amber-500/50 text-amber-700 dark:text-amber-300 hover:bg-amber-500/10"
                                    >
                                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="{ 'animate-spin': requeryingPaymentId === payment.id }" />
                                        Requery Status
                                    </Button>

                                    <a 
                                        v-if="payment.status === 'success'"
                                        :href="route('student.payments.download', payment.id)" 
                                        target="_blank"
                                        class="inline-flex items-center px-3 py-1.5 bg-background border border-input rounded-md text-xs font-medium hover:bg-accent transition-colors shadow-sm"
                                    >
                                        <Download class="w-3.5 h-3.5 mr-1.5" /> Receipt
                                    </a>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Payment Modal -->
        <Dialog v-model:open="isPaymentModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Make Payment</DialogTitle>
                    <DialogDescription>
                        Select payment option for invoice <span class="font-mono font-bold">{{ invoice.reference }}</span>.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <RadioGroup v-model="paymentOption" class="grid gap-4">
                        <div>
                            <RadioGroupItem id="full-show" value="full" class="peer sr-only" />
                            <Label
                                for="full-show"
                                class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                            >
                                <span class="mb-1 text-base font-semibold">Pay Full Balance</span>
                                <span class="text-xs text-muted-foreground">Clear remaining balance</span>
                                <span class="mt-2 text-xl font-bold font-mono">{{ formatCurrency(fullBalance) }}</span>
                            </Label>
                        </div>
                        
                        <div v-if="canPayHalf">
                            <RadioGroupItem id="half-show" value="half" class="peer sr-only" />
                            <Label
                                for="half-show"
                                class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                            >
                                <span class="mb-1 text-base font-semibold">
                                    {{ invoice.type === 'hostel_fee' ? 'Pay 75% Installment' : 'Pay Installment' }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ invoice.type === 'hostel_fee' ? '75% Upfront payment' : 'Mandatory Upfront Portion' }}
                                </span>
                                <span class="mt-2 text-xl font-bold font-mono">{{ formatCurrency(installmentAmount) }}</span>
                            </Label>
                        </div>
                    </RadioGroup>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="submitPayment">Proceed to Payment</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Requery Reference Modal -->
        <Dialog v-model:open="isRequeryModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Verify Payment Reference</DialogTitle>
                    <DialogDescription>
                        Enter transaction reference code to query payment provider.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="manual-ref-show">Transaction Reference</Label>
                        <Input 
                            id="manual-ref-show" 
                            v-model="manualReferenceInput" 
                            placeholder="e.g. PAY-6AB9417BDC5C8" 
                            @keydown.enter="submitRequeryReference"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isRequeryModalOpen = false">Cancel</Button>
                    <Button type="submit" @click="submitRequeryReference" :disabled="!manualReferenceInput.trim() || isRequeryingRef">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isRequeryingRef }" />
                        {{ isRequeryingRef ? 'Verifying...' : 'Verify Status' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </StudentLayout>
</template>
