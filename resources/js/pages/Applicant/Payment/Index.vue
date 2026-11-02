<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import NoRefundPolicyAgreement from '@/components/Payment/NoRefundPolicyAgreement.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import {
    ShieldCheck,
    CreditCard,
    Receipt,
    GraduationCap,
    ArrowLeft,
    CheckCircle2,
    Lock,
    HelpCircle,
    Building2,
} from 'lucide-vue-next';

const props = defineProps<{
    invoice: any;
    currentSession?: { id?: string; name?: string } | null;
    applicant?: any;
    programme?: any;
}>();

const loading = ref(false);
const hasAgreedToPolicy = ref(false);

const sessionDisplayName = computed(() => {
    return props.invoice?.session?.name || props.currentSession?.name || 'Current Academic Session';
});

const effectiveDate = computed(() => {
    const name = props.invoice?.session?.name || props.currentSession?.name;
    return name ? `${name} Academic Session` : 'Current Academic Session';
});

const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(Number(amount) || 0);
};

const payNow = () => {
    if (!hasAgreedToPolicy.value) return;

    loading.value = true;
    router.post(route('applicant.payment.pay'), {
        agreed_to_policy: hasAgreedToPolicy.value,
    }, {
        onFinish: () => loading.value = false
    });
};
</script>

<template>
    <Head title="Application Fee Payment" />

    <ApplicantLayout :breadcrumbs="[
        { title: 'My Application', href: '/applicant/application/preview' },
        { title: 'Application Payment', href: '/applicant/payment' }
    ]">
        <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Executive Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-border/70">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <Badge variant="outline" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary border-primary/20">
                            {{ programme?.name ? (programme.academic_stream === 'PG' ? 'Postgraduate Application' : 'Undergraduate Application') : 'Application Payment' }}
                        </Badge>
                        <span class="text-xs text-muted-foreground font-medium">
                            • {{ sessionDisplayName }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Pending Payment
                        </span>
                    </div>

                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-foreground tracking-tight">
                        Complete Your Application
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground">
                        Authorize the mandatory institutional application fee to file and submit your admission dossier.
                    </p>
                </div>

                <div class="shrink-0">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-xs text-muted-foreground hover:text-foreground h-9"
                        as-child
                    >
                        <Link :href="route('applicant.dashboard')">
                            <ArrowLeft class="w-3.5 h-3.5 mr-1.5" />
                            Return to Dashboard
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Two-Column Checkout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- Left Column: Official Invoice Dossier & Breakdown -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- Invoice Card -->
                    <Card class="border border-border/70 shadow-sm rounded-2xl bg-card">
                        <CardHeader class="pb-4 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <Receipt class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-base font-bold">Official Application Invoice</CardTitle>
                                        <CardDescription class="text-xs font-mono">Reference: {{ invoice.reference }}</CardDescription>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-md border border-emerald-200 dark:border-emerald-800">
                                    Verified Invoice
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent class="p-6 space-y-6">
                            <!-- Applicant & Target Programme Details -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-muted/20 border border-border/60 text-xs">
                                <div>
                                    <span class="text-muted-foreground block text-[10px] uppercase font-bold tracking-wider">Applicant Name</span>
                                    <span class="font-semibold text-foreground text-sm">
                                        {{ applicant ? `${applicant.title || ''} ${applicant.first_name} ${applicant.last_name}`.trim() : 'Applicant' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[10px] uppercase font-bold tracking-wider">Target Programme</span>
                                    <span class="font-semibold text-foreground text-sm">
                                        {{ programme?.name || applicant?.programme?.name || 'Academic Degree' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[10px] uppercase font-bold tracking-wider">Academic Session</span>
                                    <span class="font-medium text-foreground">{{ sessionDisplayName }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[10px] uppercase font-bold tracking-wider">Application Mode</span>
                                    <span class="font-medium text-foreground uppercase">
                                        {{ applicant?.application_mode || 'POSTGRADUATE' }}
                                        <span v-if="applicant?.study_mode"> ({{ applicant.study_mode }})</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Itemized Billing Breakdown -->
                            <div class="space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Itemized Fee Schedule</h3>

                                <div class="rounded-xl border border-border/70 overflow-hidden text-xs">
                                    <div class="p-3 bg-muted/30 font-semibold text-muted-foreground grid grid-cols-12 border-b">
                                        <span class="col-span-8">Description</span>
                                        <span class="col-span-4 text-right">Amount</span>
                                    </div>

                                    <div class="divide-y divide-border/60">
                                        <div
                                            v-for="(item, i) in (invoice.items?.length ? invoice.items : [{ description: 'Postgraduate Application & Screening Fee', amount: invoice.amount }])"
                                            :key="i"
                                            class="p-3.5 grid grid-cols-12 items-center"
                                        >
                                            <div class="col-span-8 space-y-0.5">
                                                <span class="font-medium text-foreground block">{{ item.description }}</span>
                                                <span class="text-[11px] text-muted-foreground">Institutional admission processing & document verification</span>
                                            </div>
                                            <span class="col-span-4 text-right font-semibold font-mono text-foreground">
                                                {{ formatCurrency(item.amount) }}
                                            </span>
                                        </div>

                                        <div class="p-3.5 grid grid-cols-12 items-center bg-muted/10">
                                            <div class="col-span-8 space-y-0.5">
                                                <span class="font-medium text-foreground block">Electronic Portal Gateway Charge</span>
                                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Subsidized by Institution</span>
                                            </div>
                                            <span class="col-span-4 text-right font-medium text-muted-foreground">
                                                ₦0.00
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-4 bg-muted/20 border-t border-border/80 flex items-center justify-between text-sm">
                                        <span class="font-bold text-foreground">Total Payable</span>
                                        <span class="font-bold font-mono text-base text-foreground">
                                            {{ formatCurrency(invoice.amount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Trust & Security Badges -->
                    <div class="p-5 rounded-2xl border border-border/70 bg-card space-y-3">
                        <div class="flex items-center gap-2 text-foreground font-bold text-xs uppercase tracking-wider">
                            <ShieldCheck class="w-4 h-4 text-primary" />
                            <span>Institutional Payment Protections</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 text-xs">
                            <div class="p-3 rounded-xl bg-muted/20 space-y-1">
                                <p class="font-semibold text-foreground">Instant Reconciliation</p>
                                <p class="text-[11px] text-muted-foreground">Application form status updates in real-time immediately after authorization.</p>
                            </div>
                            <div class="p-3 rounded-xl bg-muted/20 space-y-1">
                                <p class="font-semibold text-foreground">Bank-Grade Encryption</p>
                                <p class="text-[11px] text-muted-foreground">Processed through 256-bit SSL secured and PCI-DSS certified gateway.</p>
                            </div>
                            <div class="p-3 rounded-xl bg-muted/20 space-y-1">
                                <p class="font-semibold text-foreground">Official Receipt</p>
                                <p class="text-[11px] text-muted-foreground">Downloadable official payment receipt and transaction code generated instantly.</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Amount Payable, Policy Consent & Authorization CTA -->
                <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-6">

                    <!-- Checkout Authorization Card -->
                    <Card class="border border-border/70 shadow-sm rounded-2xl bg-card overflow-hidden">
                        <!-- Total Due Highlight Box -->
                        <div class="p-6 bg-gradient-to-br from-primary/10 via-primary/5 to-transparent border-b border-border/70 text-center space-y-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                Total Amount Payable
                            </span>
                            <div class="text-3xl sm:text-4xl font-extrabold text-foreground font-mono tracking-tight pt-1">
                                {{ formatCurrency(invoice.amount) }}
                            </div>
                            <p class="text-[11px] text-muted-foreground pt-0.5">
                                Ref: <span class="font-mono">{{ invoice.reference }}</span>
                            </p>
                        </div>

                        <CardContent class="p-6 space-y-6">
                            <!-- Non-Refundable Policy Agreement Component -->
                            <div class="space-y-2">
                                <NoRefundPolicyAgreement 
                                    v-model="hasAgreedToPolicy" 
                                    checkbox-id="applicant-policy-agree"
                                    :effective-date="effectiveDate"
                                />
                            </div>

                            <!-- Primary Pay Action Button -->
                            <div class="space-y-3 pt-2">
                                <Button 
                                    class="w-full h-12 text-sm font-bold shadow-md shadow-primary/20 hover:shadow-lg transition-all" 
                                    @click="payNow" 
                                    :disabled="loading || !hasAgreedToPolicy"
                                >
                                    <Lock class="w-4 h-4 mr-2" />
                                    {{ loading ? 'Redirecting to Gateway...' : `Authorize Payment • ${formatCurrency(invoice.amount)}` }}
                                </Button>

                                <p v-if="!hasAgreedToPolicy" class="text-[11px] text-center text-amber-600 dark:text-amber-400 font-medium">
                                    Please accept the Non-Refundable Payment Policy above to proceed.
                                </p>
                            </div>

                            <!-- Supported Payment Options -->
                            <div class="pt-2 border-t text-center space-y-2">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block">
                                    Accepted Payment Channels
                                </span>
                                <div class="flex items-center justify-center gap-2 text-xs text-muted-foreground font-medium flex-wrap">
                                    <span class="px-2 py-0.5 rounded bg-muted/40 border text-[11px]">Mastercard</span>
                                    <span class="px-2 py-0.5 rounded bg-muted/40 border text-[11px]">Visa</span>
                                    <span class="px-2 py-0.5 rounded bg-muted/40 border text-[11px]">Verve</span>
                                    <span class="px-2 py-0.5 rounded bg-muted/40 border text-[11px]">Bank Transfer</span>
                                    <span class="px-2 py-0.5 rounded bg-muted/40 border text-[11px]">USSD</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admissions Desk Hotline / Help -->
                    <div class="p-4 rounded-xl bg-muted/20 border border-border/60 text-xs space-y-1.5 text-center sm:text-left">
                        <div class="flex items-center gap-1.5 text-foreground font-semibold justify-center sm:justify-start">
                            <HelpCircle class="w-3.5 h-3.5 text-primary" />
                            <span>Payment Inquiries or Discrepancies?</span>
                        </div>
                        <p class="text-[11px] text-muted-foreground leading-relaxed">
                            If you encounter any issues during authorization, quote your invoice reference <strong>{{ invoice.reference }}</strong> when contacting bursary support.
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </ApplicantLayout>
</template>
