<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Button } from '@/components/ui/button';
import { Check, Download, LayoutDashboard, ReceiptText, ArrowRight } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    payment: {
        id: string;
        gateway_reference: string;
        amount: number;
        paid_at: string;
    };
    invoice: {
        type: string;
        reference: string;
    };
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const formatType = (type: string) => {
    return type.replace('_', ' ').toUpperCase();
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Payment Successful" />

    <StudentLayout>
        <div class="min-h-[85vh] flex items-center justify-center bg-slate-50/50 dark:bg-slate-950/50 px-4 py-12">
            <div class="w-full max-w-xl">
                <!-- Success Header -->
                <div class="text-center mb-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-600 text-white mb-6 shadow-xl shadow-emerald-600/20">
                        <Check class="w-8 h-8 stroke-[3]" />
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Payment Successful</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium">Your transaction has been verified and recorded.</p>
                </div>

                <!-- Transaction Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden mb-8">
                    <div class="px-8 py-10">
                        <!-- Receipt Visual -->
                        <div class="relative">
                            <div class="flex flex-col items-center border-b border-dashed border-slate-200 dark:border-slate-800 pb-8 mb-8">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">Total Amount Paid</span>
                                <div class="text-4xl font-black text-slate-900 dark:text-white">{{ formatCurrency(payment.amount) }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Transaction Ref</span>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 font-mono">{{ payment.gateway_reference }}</p>
                                </div>
                                <div class="space-y-1 md:text-right">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Payment Category</span>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ formatType(invoice.type) }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date & Time</span>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ formatDate(payment.paid_at) }}</p>
                                </div>
                                <div class="space-y-1 md:text-right">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Invoice Ref</span>
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ invoice.reference }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-8 py-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
                         <div class="flex items-center gap-2 text-slate-500 text-xs font-medium italic">
                            <ReceiptText class="w-4 h-4" /> Official receipt sent to your email
                         </div>
                         <a :href="route('student.payments.download', payment.id)" target="_blank">
                             <Button variant="link" class="text-emerald-600 dark:text-emerald-400 font-bold p-0 h-auto hover:no-underline flex items-center gap-2 group">
                                <Download class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" /> Download PDF Receipt
                             </Button>
                         </a>
                    </div>
                </div>

                <!-- Secondary Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <Link :href="route('student.dashboard')" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-2">
                        <LayoutDashboard class="w-4 h-4" /> Back to Dashboard
                    </Link>
                    <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                    <Link :href="route('student.payments.index')" class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 group">
                        Manage Finances <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
