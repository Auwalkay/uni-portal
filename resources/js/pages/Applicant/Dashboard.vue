<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { FileText, Calendar, CheckCircle, AlertCircle, CreditCard } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    applicant?: {
        status: string;
        jamb_registration_number: string;
        application_mode: string;
        application_number?: string;
    } | null;
    applicationInvoice?: {
        id: string;
        reference: string;
        amount: number;
        status: string;
        session?: { name: string };
    } | null;
    invoice?: {
        id: string;
        reference?: string;
        amount: number;
        status?: string;
    } | null;
    hasPaidApplicationFee?: boolean;
    currentSession?: {
        id: string;
        name: string;
    } | null;
}>();

const isPaymentPending = computed(() => {
    if (!props.applicant) return false;
    if (props.applicant.status === 'pending_payment') return true;
    if (props.applicationInvoice && props.applicationInvoice.status !== 'paid') return true;
    if (props.hasPaidApplicationFee === false) return true;
    return false;
});

const statusDisplay = computed(() => {
    if (!props.applicant) return 'Not Started';
    if (isPaymentPending.value) return 'Payment Pending';
    if (props.applicant.status === 'submitted') return 'Under Review';
    return props.applicant.status.replace('_', ' ');
});

const statusDescription = computed(() => {
    if (!props.applicant) return 'Begin your admission application';
    if (isPaymentPending.value) return 'Fee payment required to proceed with review';
    if (props.applicant.status === 'submitted') return 'Application under admissions review';
    if (props.applicant.status === 'admitted') return 'Congratulations! Offer received';
    if (props.applicant.status === 'enrolled') return 'Enrolled student';
    return 'Current stage of your admission';
});
</script>

<template>
    <Head title="Applicant Dashboard" />

    <ApplicantLayout>
        <div class="p-6 space-y-8">
            <!-- Welcome Section with Classic Typography -->
            <div class="space-y-2">
                <h2 class="text-3xl font-serif font-bold text-gray-900 tracking-tight">Welcome, Applicant</h2>
                <p class="text-gray-500">Manage your admission progress and tasks.</p>
            </div>

            <!-- Pending Payment Alert Banner -->
            <div v-if="isPaymentPending" class="bg-amber-50 border border-amber-200/90 rounded-xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="h-11 w-11 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                        <AlertCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-amber-950 text-base">Application Fee Payment Pending</h3>
                        <p class="text-sm text-amber-800 mt-0.5">
                            Your application form has been received. Please complete your application fee payment of <strong>₦{{ Number(applicationInvoice?.amount || invoice?.amount || 100000).toLocaleString() }}</strong> to submit your file for admission screening.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('applicant.payment.index')">
                        <Button class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-semibold shadow">
                            <CreditCard class="w-4 h-4 mr-2" />
                            Pay Application Fee
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Status Overview -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card :class="isPaymentPending ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-primary'">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Application Status</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold capitalize" :class="isPaymentPending ? 'text-amber-700' : ''">
                            {{ statusDisplay }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ statusDescription }}
                        </p>
                    </CardContent>
                </Card>

                 <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Admission Mode</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ applicant?.application_mode || 'UTME' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Entry qualification route
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Academic Session</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ currentSession?.name || 'N/A' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Active admission session
                        </p>
                    </CardContent>
                </Card>
            </div>


            <!-- Main Action Area -->
            <Card class="bg-slate-50 border-dashed">
                <CardHeader>
                    <CardTitle class="font-serif">Next Steps</CardTitle>
                    <CardDescription>Complete these tasks to move your application forward.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- ENROLLED STATE -->
                    <div v-if="applicant?.status === 'enrolled'" class="flex items-center justify-between bg-green-50 p-4 rounded-lg border border-green-200">
                         <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <CheckCircle class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-900">Enrollment Complete!</h4>
                                <p class="text-sm text-green-700">You are now a student. Proceed to your student dashboard.</p>
                            </div>
                        </div>
                        <a href="/student/dashboard" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            Go to Student Dashboard
                        </a>
                    </div>

                    <!-- ADMITTED STATE -->
                    <div v-else-if="applicant?.status === 'admitted'" class="space-y-4">
                        <div class="flex items-center justify-between bg-blue-50 p-4 rounded-lg border border-blue-200">
                             <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <CheckCircle class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-blue-900">Congratulations! You have been admitted.</h4>
                                    <p class="text-sm text-blue-700">Please accept your admission to proceed.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <CheckCircle class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Accept Admission</h4>
                                    <p class="text-sm text-gray-500">Confirm your intent to join the university.</p>
                                </div>
                            </div>
                            <Link :href="route('applicant.accept.offer')" method="post" as="button">
                                <Button size="lg" class="font-serif">Accept Offer</Button>
                            </Link>
                        </div>
                    </div>

                    <!-- PENDING PAYMENT STATE (APPLICATION SUBMITTED BUT UNPAID) -->
                    <div v-else-if="isPaymentPending" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-amber-50/70 p-5 rounded-xl border border-amber-200/80 gap-4">
                            <div class="flex items-start sm:items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                                    <CreditCard class="h-6 w-6" />
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-semibold text-amber-950 text-base">Pay Application Fee to Complete Submission</h4>
                                    <p class="text-sm text-amber-800">
                                        Your application has been received, but admission screening requires application fee confirmation.
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 pt-1">
                                        <Badge variant="outline" class="bg-white/80 text-amber-900 border-amber-300">
                                            Amount Due: ₦{{ Number(applicationInvoice?.amount || invoice?.amount || 100000).toLocaleString() }}
                                        </Badge>
                                        <Badge v-if="applicationInvoice?.reference || invoice?.reference" variant="outline" class="font-mono text-xs bg-white/80 text-amber-900 border-amber-300">
                                            Ref: {{ applicationInvoice?.reference || invoice?.reference }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                                <Link :href="route('applicant.apply.show')">
                                    <Button variant="outline" class="w-full sm:w-auto">
                                        Review Details
                                    </Button>
                                </Link>
                                <Link :href="route('applicant.payment.index')">
                                    <Button class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-semibold shadow-md">
                                        <CreditCard class="w-4 h-4 mr-2" />
                                        Pay Application Fee
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- SUBMITTED AND PAID STATE (UNDER REVIEW) -->
                    <div v-else-if="applicant" class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                        <div class="flex items-center gap-4">
                             <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <CheckCircle class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Application Submitted</h4>
                                <p class="text-sm text-gray-500">Your application has been submitted and is currently under review by the admissions committee.</p>
                            </div>
                        </div>
                         <div class="flex gap-2">
                             <Link :href="route('applicant.apply.show')">
                                <Button variant="outline">View Application</Button>
                             </Link>
                             <Button variant="ghost" disabled class="text-green-600 bg-green-50 font-semibold">Under Review</Button>
                         </div>
                    </div>

                    <!-- START NEW -->
                    <div v-else class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                1
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Start New Application</h4>
                                <p class="text-sm text-gray-500">Begin your journey to join the university.</p>
                            </div>
                        </div>
                        <Link href="/applicant/apply/start">
                            <Button size="lg" class="font-serif">Start Application</Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ApplicantLayout>
</template>
