<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { XCircle, RefreshCw, HelpCircle, ArrowLeft } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    error?: string;
    reference?: string;
}>();
</script>

<template>
    <Head title="Payment Failed" />

    <StudentLayout>
        <div class="min-h-[80vh] flex items-center justify-center p-6">
            <Card class="w-full max-w-lg border-none shadow-2xl overflow-hidden bg-white dark:bg-slate-950">
                <div class="h-2 bg-red-500 w-full"></div>
                <CardHeader class="text-center pt-10 pb-6">
                    <div class="mx-auto mb-4 bg-red-100 dark:bg-red-900/30 w-20 h-20 rounded-full flex items-center justify-center text-red-600 dark:text-red-400">
                        <XCircle class="w-12 h-12" />
                    </div>
                    <CardTitle class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Payment Failed</CardTitle>
                    <CardDescription class="text-lg font-medium text-slate-500 mt-2">
                        Something went wrong while processing your payment.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-8 px-8 pb-10">
                    <!-- Error Box -->
                    <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/20 rounded-2xl p-6 space-y-3">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-300 flex items-center gap-2">
                            <HelpCircle class="w-4 h-4" /> Reason for Failure:
                        </p>
                        <p class="text-base text-red-600 dark:text-red-400 font-medium italic">
                            {{ error || 'Your transaction could not be verified. This may be due to insufficient funds, network issues, or a cancelled payment session.' }}
                        </p>
                        <div v-if="reference" class="pt-2 border-t border-red-200/50 dark:border-red-900/30 mt-4">
                             <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest">Transaction Ref: {{ reference }}</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-4">
                        <Button as-child class="w-full h-14 text-lg font-bold bg-slate-900 hover:bg-slate-800 dark:bg-slate-50 dark:text-slate-900 dark:hover:bg-slate-200" size="lg">
                            <Link :href="route('student.payments.index')">
                                <RefreshCw class="mr-2 w-5 h-5" /> Try Payment Again
                            </Link>
                        </Button>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <Button variant="outline" as-child class="h-12 border-slate-200 dark:border-slate-800">
                                <Link :href="route('student.dashboard')">
                                     Dashboard
                                </Link>
                            </Button>
                            
                            <Button variant="ghost" class="h-12 text-slate-600 dark:text-slate-400">
                                Contact Support
                            </Button>
                        </div>
                    </div>

                    <div class="text-center space-y-2">
                         <p class="text-xs text-slate-400 font-medium">
                            If you have been debited but your payment shows as failed, please wait 30 minutes for an automatic reversal or contact the Bursary with your transaction reference.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </StudentLayout>
</template>
