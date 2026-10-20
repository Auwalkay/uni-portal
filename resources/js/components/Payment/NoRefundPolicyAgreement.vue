<template>
    <div class="space-y-3">
        <!-- Important Notice Callout Banner -->
        <div class="rounded-lg border border-amber-300/80 bg-amber-50/90 p-3 text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200">
            <div class="flex items-start gap-2.5">
                <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                <div class="space-y-1 text-xs">
                    <p class="font-bold tracking-tight uppercase">
                        Important Notice: All Payments Are Non-Refundable
                    </p>
                    <p class="text-[11px] leading-relaxed text-amber-800/90 dark:text-amber-300/90">
                        Students and payers must verify their department, programme, academic session, payment type, and amount before making payment.
                    </p>
                </div>
            </div>
        </div>

        <!-- Policy Metadata Header -->
        <div class="flex items-center justify-between text-[11px] text-muted-foreground px-1">
            <span class="inline-flex items-center gap-1">
                <Building2 class="h-3 w-3" />
                <strong>Institution:</strong> {{ institutionName }}
            </span>
            <span class="inline-flex items-center gap-1">
                <Calendar class="h-3 w-3" />
                <strong>Effective:</strong> {{ effectiveDate }}
            </span>
        </div>

        <!-- Scrollable Policy Container -->
        <div 
            class="max-h-44 overflow-y-auto rounded-md border border-border bg-slate-50/75 p-3 text-xs leading-relaxed text-slate-700 dark:bg-slate-900/60 dark:text-slate-300 space-y-3 shadow-inner"
            tabindex="0"
            role="region"
            aria-label="Payment and Refund Policy Document"
        >
            <div>
                <h4 class="font-semibold text-foreground text-xs">1. General Policy</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    All fees paid to the institution through the official school portal or other approved payment channels are non-refundable and non-transferable, except where expressly approved by the institution or required by applicable law. Students are responsible for verifying their payment information before completing any transaction.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">2. Non-Refundable Fees</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    The following fees are generally non-refundable after successful payment:
                </p>
                <ul class="list-disc pl-4 mt-1 space-y-0.5 text-[11px] text-muted-foreground">
                    <li>Application and admission fees</li>
                    <li>Acceptance fees</li>
                    <li>Registration and tuition fees</li>
                    <li>Examination fees</li>
                    <li>Departmental and faculty fees</li>
                    <li>Administrative and processing fees</li>
                    <li>Accommodation and hostel fees, subject to applicable accommodation terms</li>
                    <li>Other approved institutional charges</li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">3. Change of Department or Programme</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    Students who voluntarily apply for a change of department, faculty, or academic programme after making payment shall not be entitled to an automatic refund of fees already paid.
                </p>
                <ol class="list-decimal pl-4 mt-1 space-y-0.5 text-[11px] text-muted-foreground">
                    <li>All changes of department or programme must receive official approval from the appropriate academic authorities.</li>
                    <li>Payments made under the previous department or programme shall not automatically be transferred or credited to the new department.</li>
                    <li>Where the new department or programme attracts higher fees, the student shall be responsible for paying the applicable difference or any additional charges determined by the institution.</li>
                    <li>Where the new department or programme attracts lower fees, the student shall not automatically be entitled to a refund of the difference. Any adjustment shall be subject to management approval and applicable law.</li>
                    <li>Any approved transfer or adjustment of previously paid fees must be processed by the Bursary or Finance Department.</li>
                    <li>Students are strongly advised to confirm their department and programme before making payment.</li>
                </ol>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">4. Withdrawal, Deferment and Discontinuation</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    Students who voluntarily withdraw, defer their studies, abandon registration, or discontinue their academic programme after making payment shall not automatically be entitled to a refund. Exceptional circumstances shall be considered in accordance with approved institutional regulations and applicable law.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">5. Incorrect and Duplicate Payments</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    Students are responsible for confirming their payment details before completing transactions. Duplicate payments, verified payment errors, or excess payments shall be reviewed by the Bursary or Finance Department. Where an error is confirmed, the institution may approve a correction, account credit, transfer, or refund, as appropriate.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">6. Failed Transactions</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    Students whose accounts have been debited without successful payment confirmation should report the transaction to the appropriate support unit. Such transactions shall be investigated and reconciled with the relevant payment service provider.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">7. Exceptional Refund Requests</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    Refund requests may be considered where:
                </p>
                <ul class="list-disc pl-4 mt-1 space-y-0.5 text-[11px] text-muted-foreground">
                    <li>A verified duplicate or excess payment has occurred.</li>
                    <li>The institution has collected payment for a service it cannot provide.</li>
                    <li>An error attributable to the institution or payment system has occurred.</li>
                    <li>A refund is required by applicable law.</li>
                    <li>The institution's management approves an exceptional request.</li>
                </ul>
                <p class="mt-1 text-[11px] text-muted-foreground italic">
                    All requests must be supported by relevant payment evidence and shall be subject to verification.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-foreground text-xs">8. Acceptance of Policy</h4>
                <p class="mt-0.5 text-[11px] text-muted-foreground leading-normal">
                    By proceeding with payment, the student or payer confirms that they have read, understood, and accepted the institution's payment and refund policy, subject to applicable legal rights.
                </p>
            </div>
        </div>

        <!-- Agreement Checkbox Card -->
        <label 
            :for="checkboxId" 
            class="flex items-start gap-3 rounded-lg border p-3 transition-colors cursor-pointer select-none"
            :class="modelValue 
                ? 'border-primary/60 bg-primary/5 dark:bg-primary/10' 
                : 'border-border bg-card hover:bg-accent/40'"
        >
            <Checkbox 
                :id="checkboxId" 
                :model-value="modelValue"
                @update:model-value="$emit('update:modelValue', Boolean($event))"
                class="mt-0.5 shrink-0" 
            />
            <div class="space-y-0.5">
                <span class="text-xs font-semibold text-foreground leading-snug block">
                    I have read, understood, and agree to the Non-Refundable Payment Policy
                </span>
                <p class="text-[11px] text-muted-foreground leading-relaxed">
                    I confirm that I have verified my student details, programme, academic session, fee type, and amount, and understand that fees are non-refundable and non-transferable.
                </p>
            </div>
        </label>
    </div>
</template>

<script setup lang="ts">
import { AlertTriangle, Building2, Calendar } from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';

withDefaults(defineProps<{
    modelValue: boolean;
    institutionName?: string;
    effectiveDate?: string;
    checkboxId?: string;
}>(), {
    institutionName: 'Mewar International University',
    effectiveDate: 'Current Academic Session',
    checkboxId: 'policy-agreement-checkbox'
});

defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();
</script>
