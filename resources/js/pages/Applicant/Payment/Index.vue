<template>
    <Head title="Application Payment" />

    <div class="min-h-screen bg-slate-50 relative flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 space-y-6">
                
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-foreground">Complete Your Application</h2>
                    <p class="text-muted-foreground mt-2">
                        Please pay the application fee to submit your form.
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
                        <span class="font-medium">2025/2026</span> 
                     </div>
                </div>

                <Button class="w-full h-11 text-lg" @click="payNow" :disabled="loading">
                    {{ loading ? 'Processing...' : 'Pay with Paystack' }}
                </Button>

                <p class="text-xs text-center text-muted-foreground">
                    Secured by Paystack
                </p>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    invoice: any;
}>();

const loading = ref(false);

const payNow = () => {
    loading.value = true;
    router.post(route('applicant.payment.pay'), {}, {
        onFinish: () => loading.value = false
    });
};
</script>
