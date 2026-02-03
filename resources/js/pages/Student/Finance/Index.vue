<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format as formatDate } from 'date-fns';
import { CreditCard, ChevronDown, ChevronUp, FileText } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

defineProps<{
    invoices: Array<{
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
        };
        payments?: Array<{
            paid_at: string;
        }>;
    }>;
    canGenerateInvoice: boolean;
}>();

const expandedInvoices = ref<string[]>([]);

const toggleExpand = (id: string) => {
    const index = expandedInvoices.value.indexOf(id);
    if (index === -1) {
        expandedInvoices.value.push(id);
    } else {
        expandedInvoices.value.splice(index, 1);
    }
};

const isPaymentModalOpen = ref(false);
const selectedInvoice = ref<any>(null);
const paymentOption = ref('full'); // 'full' or 'half'
const paymentAmount = ref('');

const openPaymentModal = (invoice: any) => {
    selectedInvoice.value = invoice;
    paymentOption.value = 'full';
    calculateAmount();
    isPaymentModalOpen.value = true;
};

const calculateAmount = () => {
    if (!selectedInvoice.value) return;
    const balance = selectedInvoice.value.amount - (selectedInvoice.value.paid_amount || 0);
    
    if (paymentOption.value === 'half') {
        const half = selectedInvoice.value.amount / 2;
        // Ensure we don't pay more than balance (edge case if already partially paid weird amount)
        paymentAmount.value = (Math.min(half, balance)).toString();
    } else {
        paymentAmount.value = balance.toString();
    }
};

watch(paymentOption, () => {
    calculateAmount();
});

const canPayHalf = computed(() => {
    if (!selectedInvoice.value) return false;
    // Only allow half payment if nothing has been paid yet
    return Number(selectedInvoice.value.paid_amount || 0) === 0;
});

const submitPayment = () => {
    if (!selectedInvoice.value) return;
    
    router.post(route('student.payments.pay', selectedInvoice.value.id), {
        amount: paymentAmount.value
    }, {
        onFinish: () => {
            isPaymentModalOpen.value = false;
        }
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'partial': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'cancelled': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const formatType = (type: string) => {
    return type.replace('_', ' ').toUpperCase();
};

const getPaymentDate = (invoice: any) => {
    if (invoice.status !== 'paid') return 'Pending';
    // Use the first successful payment or invoice updated_at as fallback
    const payment = invoice.payments?.[0];
    if (payment?.paid_at) {
        return formatDate(new Date(payment.paid_at), 'MMM d, yyyy');
    }
    return formatDate(new Date(invoice.updated_at), 'MMM d, yyyy');
};
</script>

<template>
    <Head title="My Finances" />

    <StudentLayout>
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Financials</h2>
                    <p class="text-muted-foreground">Manage your invoices and payments.</p>
                </div>
                <div v-if="canGenerateInvoice">
                     <Button @click="router.post(route('student.payments.create_school_fee'))">
                        Pay School Fees
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Invoices</CardTitle>
                    <CardDescription>Outstanding and paid bills.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[50px]"></TableHead>
                                <TableHead>Reference</TableHead>
                                <TableHead>Session</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Paid</TableHead>
                                <TableHead>Balance</TableHead>
                                <TableHead>Due Date</TableHead>
                                <TableHead>Paid Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <tr v-if="invoices.length === 0">
                                <td colspan="9" class="p-8 text-center text-muted-foreground">
                                    No invoices found.
                                </td>
                            </tr>
                            <template v-for="invoice in invoices" :key="invoice.id">
                                <TableRow class="cursor-pointer hover:bg-muted/50" @click="toggleExpand(invoice.id)">
                                    <TableCell>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 p-0">
                                            <ChevronDown v-if="!expandedInvoices.includes(invoice.id)" class="h-4 w-4" />
                                            <ChevronUp v-else class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                    <TableCell class="font-mono font-medium">{{ invoice.reference }}</TableCell>
                                    <TableCell>{{ invoice.session?.name || 'N/A' }}</TableCell>
                                    <TableCell>{{ formatType(invoice.type) }}</TableCell>
                                    <TableCell class="font-bold">{{ formatCurrency(invoice.amount) }}</TableCell>
                                    <TableCell class="text-green-600">{{ formatCurrency(Number(invoice.paid_amount || 0)) }}</TableCell>
                                    <TableCell class="text-red-600 font-medium">{{ formatCurrency(invoice.amount - Number(invoice.paid_amount || 0)) }}</TableCell>
                                    <TableCell>{{ invoice.due_date ? formatDate(new Date(invoice.due_date), 'MMM d, yyyy') : 'N/A' }}</TableCell>
                                    <TableCell>{{ getPaymentDate(invoice) }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="getStatusColor(invoice.status)">
                                            {{ invoice.status.toUpperCase() }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button 
                                            v-if="invoice.status !== 'paid'" 
                                            @click.stop="openPaymentModal(invoice)"
                                            size="sm"
                                        >
                                            <CreditCard class="mr-2 h-4 w-4" />
                                            Pay Now
                                        </Button>
                                        <span v-else class="text-muted-foreground text-sm font-medium">Paid</span>
                                    </TableCell>
                                </TableRow>
                                <!-- Expanded Details Row -->
                                <TableRow v-if="expandedInvoices.includes(invoice.id)" class="bg-muted/30">
                                    <TableCell colspan="9" class="p-4">
                                        <div class="rounded-lg border bg-background p-4 shadow-sm">
                                            <h4 class="mb-2 font-semibold flex items-center gap-2">
                                                <FileText class="h-4 w-4" /> Invoice Breakdown
                                            </h4>
                                            <div v-if="invoice.items && invoice.items.length > 0">
                                                <div v-for="item in invoice.items" :key="item.id" class="flex justify-between py-2 border-b last:border-0 text-sm">
                                                    <span>{{ item.description }}</span>
                                                    <span class="font-mono">{{ formatCurrency(item.amount) }}</span>
                                                </div>
                                                <div class="flex justify-between pt-4 font-bold border-t mt-2">
                                                    <span>Total</span>
                                                    <span>{{ formatCurrency(invoice.amount) }}</span>
                                                </div>
                                            </div>
                                            <p v-else class="text-sm text-muted-foreground italic">No detailed breakdown available for this invoice.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="isPaymentModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Make Payment</DialogTitle>
                    <DialogDescription>
                        Enter the amount you wish to pay.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div v-if="selectedInvoice" class="space-y-4">
                        <RadioGroup v-model="paymentOption" class="grid gap-4">
                            <div>
                                <RadioGroupItem
                                    id="full"
                                    value="full"
                                    class="peer sr-only"
                                />
                                <Label
                                    for="full"
                                    class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                                >
                                    <span class="mb-2 text-lg font-semibold">Pay Balance</span>
                                    <span class="text-sm text-muted-foreground">Clear remaining debt</span>
                                    <span class="mt-2 text-xl font-bold">{{ formatCurrency(selectedInvoice.amount - (selectedInvoice.paid_amount || 0)) }}</span>
                                </Label>
                            </div>
                            
                            <div v-if="canPayHalf">
                                <RadioGroupItem
                                    id="half"
                                    value="half"
                                    class="peer sr-only"
                                />
                                <Label
                                    for="half"
                                    class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer"
                                >
                                    <span class="mb-2 text-lg font-semibold">Pay 50%</span>
                                    <span class="text-sm text-muted-foreground">First Installment</span>
                                    <span class="mt-2 text-xl font-bold">{{ formatCurrency(selectedInvoice.amount / 2) }}</span>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="submitPayment">Proceed to Payment</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </StudentLayout>
</template>
