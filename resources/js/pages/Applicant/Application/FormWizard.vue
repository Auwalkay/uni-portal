<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FileUploader from '@/components/FileUploader.vue';
import { Separator } from '@/components/ui/separator';
import axios from 'axios';
import { route } from 'ziggy-js';

const props = defineProps<{
    mode?: string;
    programme_id?: string;
}>();

const steps = ['JAMB Details', 'Personal Info', 'Academic History', 'Document Uploads', 'Review'];
const currentStep = ref(0);

// JAMB Fetch State
const jambNumber = ref('');
const isFetching = ref(false);
const jambError = ref('');
const fetchedJambData = ref<any>(null);

const form = useForm({
    first_name: '',
    last_name: '',
    dob: '',
    phone: '',
    jamb_score: '',
    previous_institution: '',
    mode: props.mode || 'UTME',
    programme_id: props.programme_id || '',
    jamb_number: '',
    passport_photo: null as File | null,
    waec_result: null as File | null,
});

const fetchJambDetails = async () => {
    isFetching.value = true;
    jambError.value = '';
    fetchedJambData.value = null;

    try {
        const response = await axios.post('/api/external/jamb/fetch', { jamb_number: jambNumber.value });
        fetchedJambData.value = response.data.data;
    } catch (error) {
        jambError.value = 'Could not fetch JAMB details. Please check the number and try again.';
    } finally {
        isFetching.value = false;
    }
};

const confirmJambData = () => {
    if (fetchedJambData.value) {
        form.first_name = fetchedJambData.value.first_name;
        form.last_name = fetchedJambData.value.last_name;
        form.dob = fetchedJambData.value.dob;
        form.jamb_score = fetchedJambData.value.jamb_score;
        form.jamb_number = fetchedJambData.value.jamb_number;
        // Move to next step
        currentStep.value = 1;
    }
};

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const submitApplication = () => {
    form.post(route('applicant.apply.store'), {
        forceFormData: true,
        onSuccess: () => {
    alert('Submitting application...');
        },
    });
};
</script>

<template>
    <Head title="Application Form" />

    <ApplicantLayout>
        <div class="flex flex-col lg:flex-row gap-8 max-w-6xl mx-auto p-4 lg:p-8">
            <!-- Sidebar Stepper -->
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="sticky top-8 bg-card border rounded-xl p-6 shadow-sm">
                    <h2 class="font-serif text-xl font-bold text-primary mb-6">Application Progress</h2>
                    <div class="relative flex flex-col gap-6">
                         <!-- Vertical Line -->
                        <div class="absolute left-[15px] top-4 bottom-4 w-0.5 bg-muted"></div>

                        <div 
                            v-for="(step, index) in steps" 
                            :key="index"
                            class="relative flex items-center gap-4 group cursor-pointer"
                            @click="index < currentStep ? currentStep = index : null"
                        >
                             <div 
                                class="z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 transition-all duration-300"
                                :class="[
                                    index <= currentStep 
                                        ? 'border-primary bg-primary text-primary-foreground' 
                                        : 'border-muted-foreground/30 bg-background text-muted-foreground'
                                ]"
                            >
                                <span v-if="index < currentStep" class="i-lucide-check w-4 h-4">✓</span>
                                <span v-else class="text-xs font-medium">{{ index + 1 }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span 
                                    class="text-sm font-medium transition-colors"
                                    :class="index === currentStep ? 'text-foreground' : 'text-muted-foreground'"
                                >
                                    {{ step }}
                                </span>
                                <span v-if="index === currentStep" class="text-xs text-primary animate-pulse">In Progress</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Form -->
            <Card class="flex-1 shadow-md border-0 ring-1 ring-black/5">
                <CardHeader class="border-b bg-muted/20 px-8 py-6">
                    <CardTitle class="font-serif text-2xl text-primary">{{ steps[currentStep] }}</CardTitle>
                    <p class="text-sm text-muted-foreground">Please provide accurate information for your application.</p>
                </CardHeader>
                
                <CardContent class="p-8 space-y-8 min-h-[500px]">
                    
                     <!-- Step 0: JAMB Check (New Step) -->
                    <div v-if="currentStep === 0" class="space-y-6 max-w-md mx-auto py-10">
                        <div class="text-center space-y-2">
                            <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-2xl">🎓</span>
                            </div>
                            <h3 class="text-lg font-semibold">Import JAMB Details</h3>
                            <p class="text-sm text-muted-foreground">Enter your JAMB Registration Number to fetch your records.</p>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label>JAMB Registration Number</Label>
                                <div class="flex gap-2">
                                    <Input v-model="jambNumber" placeholder="e.g 2024123456AB" class="uppercase" />
                                    <Button @click="fetchJambDetails" :disabled="isFetching || !jambNumber">
                                        {{ isFetching ? 'Fetching...' : 'Fetch' }}
                                    </Button>
                                </div>
                                <p v-if="jambError" class="text-xs text-destructive">{{ jambError }}</p>
                            </div>

                            <div v-if="fetchedJambData" class="bg-primary/5 rounded-lg p-4 border border-primary/10 space-y-3">
                                <div class="flex items-center gap-3">
                                    <img :src="fetchedJambData.passport_url" class="w-12 h-12 rounded-full border" />
                                    <div>
                                        <p class="font-bold">{{ fetchedJambData.first_name }} {{ fetchedJambData.last_name }}</p>
                                        <p class="text-xs text-muted-foreground">Score: {{ fetchedJambData.jamb_score }}</p>
                                    </div>
                                </div>
                                <Button class="w-full" variant="secondary" @click="confirmJambData">Continue with these details</Button>
                            </div>
                        </div>
                        
                         <div class="relative">
                            <div class="absolute inset-0 flex items-center"><span class="w-full border-t"></span></div>
                            <div class="relative flex justify-center text-xs uppercase"><span class="bg-background px-2 text-muted-foreground">Or fill manually</span></div>
                        </div>
                        
                        <Button variant="outline" class="w-full" @click="currentStep++">Skip & Fill Manually</Button>
                    </div>

                    <!-- Step 1: Personal Details -->
                    <div v-if="currentStep === 1" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in slide-in-from-right-4 duration-500">
                        <div class="space-y-2">
                            <Label>First Name</Label>
                            <Input v-model="form.first_name" placeholder="Enter first name" />
                        </div>
                        <div class="space-y-2">
                            <Label>Last Name</Label>
                            <Input v-model="form.last_name" placeholder="Enter last name" />
                        </div>
                        <div class="space-y-2">
                            <Label>Date of Birth</Label>
                            <Input type="date" v-model="form.dob" />
                        </div>
                        <div class="space-y-2">
                            <Label>Phone Number</Label>
                            <Input type="tel" v-model="form.phone" placeholder="+234..." />
                        </div>
                    </div>

                    <!-- Step 2: Academic History -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-500">
                        <div class="space-y-2">
                            <Label>JAMB Score</Label>
                            <Input type="number" v-model="form.jamb_score" placeholder="e.g 280" />
                        </div>
                         <div class="space-y-2">
                            <Label>Previous Institution (if applicable)</Label>
                            <Input v-model="form.previous_institution" placeholder="For Direct Entry or PG" />
                        </div>
                    </div>

                    <!-- Step 3: Uploads -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-500">
                        <FileUploader 
                            label="Passport Photograph" 
                            accept="image/*"
                            @update:file="(file) => form.passport_photo = file"
                        />
                        <Separator />
                        <FileUploader 
                            label="O'Level Result (WAEC/NECO)" 
                            accept=".pdf,image/*"
                            @update:file="(file) => form.waec_result = file"
                        />
                    </div>

                    <!-- Step 4: Review -->
                    <div v-if="currentStep === 4" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-500">
                        <div class="bg-muted/30 p-6 rounded-xl space-y-4 border">
                            <h3 class="font-serif text-lg font-bold text-primary border-b pb-2">Application Summary</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider">Full Name</span>
                                    <span class="font-medium">{{ form.first_name }} {{ form.last_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider">Date of Birth</span>
                                    <span class="font-medium">{{ form.dob }}</span>
                                </div>
                                <div>
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider">JAMB Score</span>
                                    <span class="font-medium">{{ form.jamb_score || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200">
                            <span class="text-xl">⚠️</span>
                             <p class="text-sm">
                                By submitting this application, I solemnly declare that all information provided is true. 
                                I understand that any false declaration will result in immediate disqualification and possible prosecution.
                            </p>
                        </div>
                    </div>

                </CardContent>
                <CardFooter class="flex justify-between border-t bg-muted/10 p-8">
                    <Button variant="outline" @click="prevStep" :disabled="currentStep === 0" class="w-32">
                        Previous
                    </Button>
                    
                    <Button v-if="currentStep < steps.length - 1" @click="nextStep" class="w-32 shadow-lg hover:shadow-xl transition-all">
                        Next Step
                    </Button>
                    
                    <Button v-else @click="submitApplication" :disabled="form.processing" class="w-48 bg-primary text-primary-foreground shadow-lg hover:shadow-xl transition-all">
                        Submit Application
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </ApplicantLayout>
</template>
