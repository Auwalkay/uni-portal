<template>
    <Head title="Application Preview" />

    <div class="min-h-screen bg-slate-50 relative flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
        <div class="sm:mx-auto sm:w-full sm:max-w-[45rem]">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 space-y-8">
                
                <!-- Success State -->
                <div class="text-center space-y-2">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                        <Check class="h-6 w-6 text-green-600" />
                    </div>
                    <h2 class="text-3xl font-extrabold text-foreground">Application Submitted</h2>
                    <p class="text-muted-foreground">
                        Your application has been received. Please keep your Application Number safe.
                    </p>
                </div>

                <!-- Application Badge -->
                <div class="flex flex-col items-center gap-4">
                     <div v-if="passportUrl" class="h-32 w-32 rounded-lg border-2 border-gray-100 overflow-hidden shadow-sm">
                        <img :src="passportUrl" class="w-full h-full object-cover" alt="Passport Photo" />
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col items-center justify-center gap-1 w-full">
                        <span class="text-xs uppercase tracking-wider font-semibold text-blue-600">Application Number</span>
                        <span class="text-2xl font-mono font-bold text-blue-900 tracking-widest">{{ applicant.application_number }}</span>
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
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">JAMB Registration</h3>
                            <p class="font-medium text-lg font-mono">{{ applicant.jamb_registration_number }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">JAMB Score</h3>
                            <p class="font-medium text-lg">{{ applicant.jamb_score }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-medium text-muted-foreground mb-1">Program Choice</h3>
                            <p class="font-medium text-lg">{{ applicant.programme?.name || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t pt-6 flex justify-between items-center">
                    <Link :href="route('applicant.dashboard')">
                        <Button variant="outline">
                            Return to Dashboard
                        </Button>
                    </Link>
                    <Button @click="printValues">
                        Print Application
                    </Button>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Check } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { route } from 'ziggy-js';

const props = defineProps<{
    applicant: any;
}>();

import { computed } from 'vue';

const passportUrl = computed(() => {
    if (!props.applicant.documents) return null;
    const doc = props.applicant.documents.find((d: any) => d.type === 'passport_photo');
    return doc ? `/storage/${doc.path}` : null;
});

const printValues = () => {
    window.print();
};
</script>
