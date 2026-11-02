<template>
    <Head title="Application Preview" />

    <div class="min-h-screen bg-slate-50 relative flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <div class="sm:mx-auto sm:w-full sm:max-w-[45rem]">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 space-y-8">
                
                <!-- Status Header -->
                <div class="text-center space-y-2">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4" :class="isPaymentPending ? 'bg-amber-100 text-amber-600' : 'bg-green-100 text-green-600'">
                        <AlertCircle v-if="isPaymentPending" class="h-6 w-6" />
                        <Check v-else class="h-6 w-6" />
                    </div>
                    <h2 class="text-3xl font-extrabold text-foreground">
                        {{ isPaymentPending ? 'Application Submitted — Payment Pending' : 'Application Submitted' }}
                    </h2>
                    <p class="text-muted-foreground max-w-lg mx-auto">
                        {{ isPaymentPending 
                            ? 'Your form details have been received and saved. Please complete the application fee payment below to finalize your submission for admission review.' 
                            : 'Your application has been received. Please keep your Application Number safe.' 
                        }}
                    </p>
                </div>

                <!-- Application Badge / Payment Action -->
                <div class="flex flex-col items-center gap-4">
                     <div v-if="passportUrl" class="h-32 w-32 rounded-lg border-2 border-gray-100 overflow-hidden shadow-sm">
                        <img :src="passportUrl" class="w-full h-full object-cover" alt="Passport Photo" />
                    </div>

                    <!-- Verified Application Number (if paid) -->
                    <div v-if="applicant.application_number" class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col items-center justify-center gap-1 w-full">
                        <span class="text-xs uppercase tracking-wider font-semibold text-blue-600">Application Number</span>
                        <span class="text-2xl font-mono font-bold text-blue-900 tracking-widest">{{ applicant.application_number }}</span>
                    </div>

                    <!-- Payment Pending Box (if not paid) -->
                    <div v-else-if="isPaymentPending" class="bg-amber-50 border border-amber-200 rounded-xl p-6 flex flex-col items-center justify-center gap-3 w-full text-center">
                        <span class="text-xs uppercase tracking-wider font-semibold text-amber-800">Application Fee Due</span>
                        <span class="text-3xl font-bold text-amber-950">₦{{ Number(invoice?.amount || 100000).toLocaleString() }}</span>
                        <p class="text-xs text-amber-800 max-w-md">
                            Your application is currently on hold. Settle the application fee to generate your application number and submit for screening.
                        </p>
                        <Link :href="route('applicant.payment.index')" class="mt-2 w-full sm:w-auto">
                            <Button size="lg" class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-semibold shadow-md px-8 py-5">
                                <CreditCard class="w-4 h-4 mr-2" />
                                Pay Application Fee Now
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="border-t pt-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">Full Name</h3>
                            <p class="font-medium text-lg">{{ applicant.last_name }} {{ applicant.first_name }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">Phone</h3>
                            <p class="font-medium text-lg">{{ applicant.phone }}</p>
                        </div>
                         <div>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">
                                {{ applicant.application_mode === 'PG' ? 'PG Application Reference' : 'JAMB Registration' }}
                            </h3>
                            <p class="font-medium text-lg font-mono">{{ applicant.jamb_registration_number }}</p>
                        </div>
                        <div v-if="applicant.application_mode === 'PG'">
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">Previous Qualification</h3>
                            <p class="font-medium text-lg">
                                {{ applicant.previous_qualification || 'N/A' }}
                                <span v-if="applicant.previous_class_of_degree" class="text-sm text-muted-foreground">({{ applicant.previous_class_of_degree }})</span>
                            </p>
                        </div>
                        <div v-else>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">JAMB Score</h3>
                            <p class="font-medium text-lg">{{ applicant.jamb_score }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">Program Choice</h3>
                            <p class="font-medium text-lg">{{ applicant.programme?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">Application Session</h3>
                            <p class="font-medium text-lg">{{ invoice?.session?.name || currentSession?.name || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Postgraduate Qualifications & Transcript Request Label Section -->
                    <div v-if="applicant.application_mode === 'PG'" class="border-t pt-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl bg-indigo-50 border border-indigo-200">
                            <div>
                                <h3 class="text-sm font-bold text-indigo-950">Official Academic Transcript Request Label</h3>
                                <p class="text-xs text-indigo-800">
                                    Download this official SPGS cover sheet and forward it to your former institution(s) to send your official transcript directly to SPGS.
                                </p>
                            </div>
                            <a :href="route('applicant.apply.transcript_label')" target="_blank" class="shrink-0">
                                <Button size="sm" class="bg-indigo-700 hover:bg-indigo-800 text-white font-semibold">
                                    Download Transcript Label (PDF)
                                </Button>
                            </a>
                        </div>

                        <div v-if="applicant.qualifications && applicant.qualifications.length > 0" class="space-y-2">
                            <h3 class="text-base font-bold text-foreground">Submitted Academic Qualifications</h3>
                            <div class="grid grid-cols-1 gap-2">
                                <div
                                    v-for="qual in applicant.qualifications"
                                    :key="qual.id"
                                    class="p-3 rounded-lg border bg-slate-50/70 text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                >
                                    <div>
                                        <p class="font-semibold">{{ qual.qualification_type }} in {{ qual.course_of_study }} — {{ qual.institution_name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ qual.class_of_degree }}
                                            <span v-if="qual.cgpa"> • CGPA: {{ qual.cgpa }} / {{ qual.scale_max || '5.00' }}</span>
                                            • Graduated: {{ qual.graduation_year }}
                                        </p>
                                    </div>
                                    <span v-if="qual.is_highest_qualification" class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-100 text-purple-800">
                                        Highest Qualification
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Postgraduate Referees Section -->
                    <div v-if="applicant.referees && applicant.referees.length > 0" class="border-t pt-6 space-y-4">
                        <h3 class="text-base font-bold text-foreground">Referee Recommendations ({{ applicant.referees.filter((r: any) => r.status === 'submitted').length }} / {{ applicant.referees.length }} Submitted)</h3>
                        <div class="grid grid-cols-1 gap-3">
                            <div
                                v-for="referee in applicant.referees"
                                :key="referee.id"
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3.5 rounded-lg border bg-slate-50/70 text-sm"
                            >
                                <div>
                                    <p class="font-semibold">{{ referee.name }} <span class="text-xs font-normal text-muted-foreground">({{ referee.institution_or_organization }})</span></p>
                                    <p class="text-xs text-muted-foreground">{{ referee.email }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase"
                                        :class="referee.status === 'submitted' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
                                    >
                                        {{ referee.status }}
                                    </span>
                                    <a
                                        v-if="referee.status !== 'submitted' && referee.token"
                                        :href="route('referee.recommendation.show', referee.token)"
                                        target="_blank"
                                        class="text-xs font-medium text-blue-600 hover:underline"
                                    >
                                        Open Referee Link
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <Link :href="route('applicant.dashboard')">
                        <Button variant="outline" class="w-full sm:w-auto">
                            Return to Dashboard
                        </Button>
                    </Link>
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto justify-end">
                        <Button variant="secondary" @click="printValues" class="w-full sm:w-auto">
                            Print Application
                        </Button>
                        <Link v-if="isPaymentPending" :href="route('applicant.payment.index')" class="w-full sm:w-auto">
                            <Button class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-semibold shadow-sm">
                                <CreditCard class="w-4 h-4 mr-2" />
                                Pay Application Fee
                            </Button>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Check, AlertCircle, CreditCard } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { computed } from 'vue';

const props = defineProps<{
    applicant: any;
    invoice?: any;
    hasPaidApplicationFee?: boolean;
    currentSession?: any;
}>();

const isPaymentPending = computed(() => {
    if (!props.applicant) return false;
    if (props.applicant.status === 'pending_payment') return true;
    if (props.invoice && props.invoice.status !== 'paid') return true;
    if (props.hasPaidApplicationFee === false) return true;
    return !props.applicant.application_number;
});

const passportUrl = computed(() => {
    if (!props.applicant.documents) return null;
    const doc = props.applicant.documents.find((d: any) => d.type === 'passport_photo');
    return doc ? `/storage/${doc.path}` : null;
});

const printValues = () => {
    window.print();
};
</script>
