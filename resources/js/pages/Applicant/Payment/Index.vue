<template>
    <Head title="Application Payment" />

    <div class="min-h-screen bg-slate-50 relative flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <div class="sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 space-y-6">
                
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-foreground">Complete Your Application</h2>
                    <p class="text-muted-foreground mt-2">
                        Please review the institutional payment terms and pay the application fee to submit your form.
                    </p>
                </div>

                <div class="border rounded-lg p-6 bg-slate-50 flex flex-col items-center gap-2">
                    <span class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Due</span>
                    <span class="text-4xl font-bold text-green-600">₦{{ Number(invoice.amount).toLocaleString() }}</span>
                </div>

                <div class="space-y-4">
                     <div class="flex justify-between text-sm py-2 border-b">
                        <span class="text-muted-foreground">Reference</span>
                        <span class="font-mono">{{ invoice.reference }}</span>
                     </div>
                     <div class="flex justify-between text-sm py-2 border-b">
                        <span class="text-muted-foreground">Session</span>
                        <span class="font-medium text-foreground">{{ sessionDisplayName }}</span> 
                     </div>
                </div>

                <!-- Non-Refundable Payment Policy -->
                <NoRefundPolicyAgreement 
                    v-model="hasAgreedToPolicy" 
                    checkbox-id="applicant-policy-agree"
                    :effective-date="effectiveDate"
                />

                <Button 
                    class="w-full h-11 text-base font-semibold" 
                    @click="payNow" 
                    :disabled="loading || !hasAgreedToPolicy"
                >
                    {{ loading ? 'Processing...' : 'Pay with Gateway' }}
                </Button>

                <p class="text-xs text-center text-muted-foreground">
                    Secured Transaction
                </p>

                <div class="pt-2 text-center">
                    <Link :href="route('applicant.dashboard')" class="text-xs text-muted-foreground hover:text-primary transition-colors underline">
                        ← Return to Applicant Dashboard
                    </Link>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import NoRefundPolicyAgreement from '@/components/Payment/NoRefundPolicyAgreement.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    invoice: any;
    currentSession?: { id?: string; name?: string } | null;
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
