<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { AlertCircle, RefreshCw, ArrowLeft, Copy, Check, ShieldAlert, FileText, LayoutDashboard, HelpCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { ref } from 'vue';

const props = defineProps<{
    error?: string;
    reference?: string;
}>();

const isRequerying = ref(false);
const isCopied = ref(false);

const copyReference = () => {
    if (!props.reference) return;
    navigator.clipboard.writeText(props.reference);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
};

const requeryTransaction = () => {
    if (!props.reference) return;
    isRequerying.value = true;
    router.post(route('student.payments.requery_reference'), { reference: props.reference }, {
        onFinish: () => {
            isRequerying.value = false;
        }
    });
};
</script>

<template>
    <Head title="Payment Unsuccessful" />

    <StudentLayout>
        <div class="min-h-[85vh] flex items-center justify-center p-4 sm:p-6">
            <Card class="w-full max-w-lg border border-border/60 shadow-xl overflow-hidden bg-card transition-all">
                <!-- Top Accent Line -->
                <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-500 to-rose-600 w-full"></div>

                <CardHeader class="text-center pt-8 pb-4 space-y-3">
                    <!-- Failure Hero Icon with Soft Ring Glow -->
                    <div class="relative mx-auto w-20 h-20 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full bg-rose-500/10 dark:bg-rose-500/20 animate-pulse"></div>
                        <div class="relative w-16 h-16 rounded-full bg-rose-100 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 flex items-center justify-center text-rose-600 dark:text-rose-400 shadow-sm">
                            <ShieldAlert class="w-9 h-9 stroke-[2.2]" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-center gap-2">
                            <Badge variant="outline" class="border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300 font-mono text-[10px] uppercase tracking-wider px-2.5 py-0.5">
                                Transaction Unsuccessful
                            </Badge>
                        </div>
                        <CardTitle class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                            Payment Not Completed
                        </CardTitle>
                        <CardDescription class="text-sm font-medium text-muted-foreground max-w-sm mx-auto">
                            The payment session was cancelled or could not be verified by the provider.
                        </CardDescription>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6 px-6 sm:px-8 pb-8">
                    <!-- Diagnostic Reason Box -->
                    <div class="rounded-xl border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/50 dark:bg-rose-950/20 p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                            <div class="space-y-1 text-xs">
                                <h4 class="font-bold text-rose-900 dark:text-rose-200 uppercase tracking-wide text-[11px]">Failure Diagnostic</h4>
                                <p class="text-rose-700 dark:text-rose-300 font-medium leading-relaxed">
                                    {{ error || 'Payment was not completed at checkout. If you cancelled or closed the payment gateway tab, you may retry or verify status.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Reference Code Pill with Copy Action -->
                        <div v-if="reference" class="pt-3 border-t border-rose-200/60 dark:border-rose-900/40 flex items-center justify-between gap-2">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-rose-500/80 dark:text-rose-400 uppercase tracking-wider">Transaction Reference</span>
                                <span class="font-mono font-bold text-xs text-foreground tracking-wide">{{ reference }}</span>
                            </div>
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                @click="copyReference" 
                                class="h-8 text-xs font-semibold text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/40 px-2.5"
                            >
                                <component :is="isCopied ? Check : Copy" class="w-3.5 h-3.5 mr-1" />
                                {{ isCopied ? 'Copied' : 'Copy' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Primary & Secondary Action CTAs -->
                    <div class="space-y-2.5">
                        <!-- Primary Requery Button -->
                        <Button 
                            v-if="reference" 
                            variant="default"
                            class="w-full h-12 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-md transition-all flex items-center justify-center gap-2" 
                            size="lg"
                            :disabled="isRequerying"
                            @click="requeryTransaction"
                        >
                            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isRequerying }" /> 
                            {{ isRequerying ? 'Verifying Provider Status...' : 'Requery / Verify Payment Status' }}
                        </Button>

                        <!-- Back to Invoices -->
                        <Button 
                            as-child 
                            variant="outline"
                            class="w-full h-11 text-sm font-semibold border-border hover:bg-accent flex items-center justify-center gap-2" 
                        >
                            <Link :href="route('student.payments.index')">
                                <FileText class="w-4 h-4 text-primary" /> Return to Financial Invoices
                            </Link>
                        </Button>

                        <!-- Secondary Nav Row -->
                        <div class="grid grid-cols-2 gap-2.5 pt-1">
                            <Button variant="ghost" as-child class="h-10 text-xs font-semibold text-muted-foreground hover:text-foreground">
                                <Link :href="route('student.dashboard')" class="flex items-center justify-center gap-1.5">
                                    <LayoutDashboard class="w-3.5 h-3.5" /> Dashboard
                                </Link>
                            </Button>
                            
                            <Button 
                                variant="ghost" 
                                as-child 
                                class="h-10 text-xs font-semibold text-muted-foreground hover:text-foreground"
                            >
                                <a href="mailto:bursary@university.edu.ng?subject=Payment%20Verification%20Assistance" class="flex items-center justify-center gap-1.5">
                                    <HelpCircle class="w-3.5 h-3.5" /> Bursary Support
                                </a>
                            </Button>
                        </div>
                    </div>

                    <!-- Informational Footnote -->
                    <div class="pt-4 border-t border-border/40 text-center">
                        <p class="text-[11px] leading-relaxed text-muted-foreground">
                            <strong>Note:</strong> If your bank account was debited, payment providers typically automatically reverse unconfirmed transactions within 24–48 hours. You can also quote your reference code above to the Bursary office for manual confirmation.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </StudentLayout>
</template>
