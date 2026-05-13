<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FileUploader from '@/components/FileUploader.vue';
import { Separator } from '@/components/ui/separator';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import axios from 'axios';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';

const props = defineProps<{
    mode?: string;
    programme_id?: string;
    states?: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
    scholarships?: Array<{ id: string; name: string; percentage: string }>;
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
    address: '',
    state_id: '',
    lga_id: '',
    next_of_kin_name: '',
    next_of_kin_phone: '',
    next_of_kin_relationship: '',
    jamb_score: '',
    previous_institution: '',
    mode: props.mode || 'UTME',
    programme_id: props.programme_id || '',
    jamb_number: '',
    passport_photo: null as File | null,
    waec_result: null as File | null,
});

const filteredLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states?.find(s => s.id === Number(form.state_id));
    return state ? state.lgas : [];
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

const stepIsValid = computed(() => {
    switch (currentStep.value) {
        case 0:
            // Step 0: JAMB check. Can be skipped or completed. We allow next if they skip or if logic permits.
            // Since we allow skipping, we'll return true.
            return true;
        case 1:
            // Step 1: Personal Details. Require core fields.
            return !!(
                form.first_name &&
                form.last_name &&
                form.dob &&
                form.phone &&
                form.address &&
                form.state_id &&
                form.lga_id
            );
        case 2:
            // Step 2: Academic History.
            return !!form.jamb_score;
        case 3:
            // Step 3: Document Uploads. Usually passport and WAEC are required.
            return !!(form.passport_photo && form.waec_result);
        default:
            return true;
    }
});

const submitApplication = () => {
    form.post(route('applicant.apply.store'), {
        forceFormData: true,
        onStart: () => {
            Swal.fire({
                title: 'Submitting application...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });
        },
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Submitted',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
            });
        },
        onError: (errors) => {
            // errors is an object: { field: "message", field2: "message" }
            const firstKey = Object.keys(errors)[0];
            const firstMsg = firstKey ? errors[firstKey] : 'Something went wrong.';

            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: firstMsg,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
        },
        // onFinish: () => Swal.close(),
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
            <Card class="flex-1 shadow-xl border-0 ring-1 ring-border/50 bg-card/95 backdrop-blur-sm relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 -m-20 w-64 h-64 bg-primary/5 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -m-20 w-64 h-64 bg-secondary/5 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

                <CardHeader class="border-b bg-muted/20 px-8 py-8">
                    <CardTitle class="font-serif text-3xl font-bold text-primary">{{ steps[currentStep] }}</CardTitle>
                    <p class="text-base text-muted-foreground mt-2">Please provide accurate information for your application.</p>
                </CardHeader>

                <CardContent class="p-8 space-y-8 min-h-[500px] relative">

                     <!-- Step 0: JAMB Check (New Step) -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-x-8" enter-to-class="opacity-100 translate-x-0" leave-active-class="absolute inset-x-8 transition-all duration-300 ease-in" leave-from-class="opacity-100 -translate-x-0" leave-to-class="opacity-0 -translate-x-8">
                    <div v-if="currentStep === 0" class="space-y-8 max-w-md mx-auto py-10">
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

                        <Button variant="outline" class="w-full h-12 border-primary/20 hover:bg-primary/5 hover:text-primary transition-colors font-medium mt-6" @click="currentStep++">Skip & Fill Manually</Button>
                    </div>
                    </Transition>

                    <!-- Step 1: Personal Details -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-x-8" enter-to-class="opacity-100 translate-x-0" leave-active-class="absolute inset-x-8 transition-all duration-300 ease-in" leave-from-class="opacity-100 -translate-x-0" leave-to-class="opacity-0 -translate-x-8">
                    <div v-if="currentStep === 1" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <Label class="font-semibold text-foreground">First Name <span class="text-destructive">*</span></Label>
                                <Input v-model="form.first_name" placeholder="Enter first name" class="h-12 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-3">
                                <Label class="font-semibold text-foreground">Last Name <span class="text-destructive">*</span></Label>
                                <Input v-model="form.last_name" placeholder="Enter last name" class="h-12 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-3">
                                <Label class="font-semibold text-foreground">Date of Birth <span class="text-destructive">*</span></Label>
                                <Input type="date" v-model="form.dob" class="h-12 focus-visible:ring-primary/30" />
                            </div>
                            <div class="space-y-3">
                                <Label class="font-semibold text-foreground">Phone Number <span class="text-destructive">*</span></Label>
                                <Input type="tel" v-model="form.phone" placeholder="+234..." class="h-12 focus-visible:ring-primary/30" />
                            </div>

                            <div class="space-y-3 md:col-span-2">
                                <Label class="font-semibold text-foreground">Residential Address <span class="text-destructive">*</span></Label>
                                 <textarea
                                    v-model="form.address"
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-4 py-3 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Enter your full residential address"
                                ></textarea>
                            </div>

                            <div class="space-y-3">
                                <Label class="font-semibold text-foreground">State of Origin <span class="text-destructive">*</span></Label>
                                <Select v-model="form.state_id">
                                    <SelectTrigger class="h-12">
                                        <SelectValue placeholder="Select State" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                            {{ state.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-3">
                                <Label class="font-semibold text-foreground">LGA of Origin <span class="text-destructive">*</span></Label>
                                <Select v-model="form.lga_id" :disabled="!form.state_id">
                                    <SelectTrigger class="h-12">
                                        <SelectValue placeholder="Select LGA" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="lga in filteredLgas" :key="lga.id" :value="String(lga.id)">
                                            {{ lga.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="bg-muted/30 p-6 rounded-xl border border-border/50 space-y-6">
                            <h4 class="font-serif text-lg font-bold text-primary flex items-center gap-2">
                                <span class="i-lucide-users w-5 h-5"></span> Next of Kin Details
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <Label class="font-semibold text-foreground">Full Name</Label>
                                    <Input v-model="form.next_of_kin_name" placeholder="Name of next of kin" class="h-12 bg-background" />
                                </div>
    
                                 <div class="space-y-3">
                                    <Label class="font-semibold text-foreground">Phone Number</Label>
                                    <Input v-model="form.next_of_kin_phone" placeholder="Phone number" class="h-12 bg-background" />
                                </div>
    
                                 <div class="space-y-3 md:col-span-2">
                                    <Label class="font-semibold text-foreground">Relationship</Label>
                                     <Select v-model="form.next_of_kin_relationship">
                                        <SelectTrigger class="h-12 bg-background">
                                            <SelectValue placeholder="Select Relationship" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Father">Father</SelectItem>
                                            <SelectItem value="Mother">Mother</SelectItem>
                                            <SelectItem value="Sibling">Sibling</SelectItem>
                                            <SelectItem value="Spouse">Spouse</SelectItem>
                                            <SelectItem value="Guardian">Guardian</SelectItem>
                                            <SelectItem value="Other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>
                    </Transition>

                    <!-- Step 2: Academic History -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-x-8" enter-to-class="opacity-100 translate-x-0" leave-active-class="absolute inset-x-8 transition-all duration-300 ease-in" leave-from-class="opacity-100 -translate-x-0" leave-to-class="opacity-0 -translate-x-8">
                    <div v-if="currentStep === 2" class="space-y-8">
                        <div class="bg-primary/5 p-6 rounded-xl border border-primary/10">
                            <div class="space-y-3 max-w-md">
                                <Label class="font-semibold text-foreground">JAMB Score <span class="text-destructive">*</span></Label>
                                <Input type="number" v-model="form.jamb_score" placeholder="e.g 280" class="h-12 text-lg font-medium" />
                                <p class="text-xs text-muted-foreground mt-1">Please enter your exact JAMB score for verification.</p>
                            </div>
                        </div>
                        
                        <Separator />
                    </div>
                    </Transition>

                    <!-- Step 3: Uploads -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-x-8" enter-to-class="opacity-100 translate-x-0" leave-active-class="absolute inset-x-8 transition-all duration-300 ease-in" leave-from-class="opacity-100 -translate-x-0" leave-to-class="opacity-0 -translate-x-8">
                    <div v-if="currentStep === 3" class="space-y-8">
                        <div class="bg-muted/20 p-6 rounded-xl border border-border/50">
                            <FileUploader
                                label="Passport Photograph *"
                                accept="image/*"
                                @update:file="(file) => form.passport_photo = file"
                            />
                            <p class="text-xs text-muted-foreground mt-3">Upload a recent, clear passport photograph. Max size: 2MB.</p>
                        </div>
                        
                        <div class="bg-muted/20 p-6 rounded-xl border border-border/50">
                            <FileUploader
                                label="O'Level Result (WAEC/NECO) *"
                                accept=".pdf,image/*"
                                @update:file="(file) => form.waec_result = file"
                            />
                            <p class="text-xs text-muted-foreground mt-3">Upload a scanned copy of your result or statement of result.</p>
                        </div>
                    </div>
                    </Transition>

                    <!-- Step 4: Review -->
                    <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-x-8" enter-to-class="opacity-100 translate-x-0" leave-active-class="absolute inset-x-8 transition-all duration-300 ease-in" leave-from-class="opacity-100 -translate-x-0" leave-to-class="opacity-0 -translate-x-8">
                    <div v-if="currentStep === 4" class="space-y-8">
                        <div class="bg-card shadow-sm border border-border/50 rounded-xl overflow-hidden">
                            <div class="bg-primary/5 px-6 py-4 border-b border-border/50 flex items-center gap-3">
                                <span class="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center font-bold">1</span>
                                <h3 class="font-serif text-xl font-bold text-foreground">Personal Information Summary</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                                <div class="space-y-1">
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider font-semibold">Full Name</span>
                                    <span class="font-medium text-base text-foreground bg-muted/30 px-3 py-2 rounded-md block border border-transparent hover:border-border transition-colors">{{ form.first_name }} {{ form.last_name }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider font-semibold">Date of Birth</span>
                                    <span class="font-medium text-base text-foreground bg-muted/30 px-3 py-2 rounded-md block border border-transparent hover:border-border transition-colors">{{ form.dob }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider font-semibold">Phone</span>
                                    <span class="font-medium text-base text-foreground bg-muted/30 px-3 py-2 rounded-md block border border-transparent hover:border-border transition-colors">{{ form.phone }}</span>
                                </div>
                                <div class="space-y-1 sm:col-span-2 lg:col-span-3">
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider font-semibold">Address</span>
                                    <span class="font-medium text-base text-foreground bg-muted/30 px-3 py-2 rounded-md block border border-transparent hover:border-border transition-colors break-words">{{ form.address }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-card shadow-sm border border-border/50 rounded-xl overflow-hidden">
                            <div class="bg-primary/5 px-6 py-4 border-b border-border/50 flex items-center gap-3">
                                <span class="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center font-bold">2</span>
                                <h3 class="font-serif text-xl font-bold text-foreground">Academic Information</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                                <div class="space-y-1">
                                    <span class="block text-muted-foreground text-xs uppercase tracking-wider font-semibold">JAMB Score</span>
                                    <span class="font-medium text-base text-foreground bg-muted/30 px-3 py-2 rounded-md block border border-transparent hover:border-border transition-colors flex items-center gap-2">
                                        {{ form.jamb_score || 'N/A' }}
                                        <span v-if="form.jamb_score" class="i-lucide-badge-check text-green-500 w-4 h-4"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-card shadow-sm border border-border/50 rounded-xl overflow-hidden">
                            <div class="bg-primary/5 px-6 py-4 border-b border-border/50 flex items-center gap-3">
                                <span class="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center font-bold">3</span>
                                <h3 class="font-serif text-xl font-bold text-foreground">Documents Attached</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div class="flex items-center gap-3 p-3 rounded-lg border" :class="form.passport_photo ? 'bg-green-50/50 border-green-200' : 'bg-destructive/5 border-destructive/20'">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="form.passport_photo ? 'bg-green-100 text-green-600' : 'bg-destructive/10 text-destructive'">
                                        <span v-if="form.passport_photo" class="text-lg">✓</span>
                                        <span v-else class="text-lg">✗</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-foreground">Passport Photo</p>
                                        <p class="text-xs text-muted-foreground">{{ form.passport_photo ? form.passport_photo.name : 'Missing document' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-lg border" :class="form.waec_result ? 'bg-green-50/50 border-green-200' : 'bg-destructive/5 border-destructive/20'">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="form.waec_result ? 'bg-green-100 text-green-600' : 'bg-destructive/10 text-destructive'">
                                        <span v-if="form.waec_result" class="text-lg">✓</span>
                                        <span v-else class="text-lg">✗</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-foreground">O'Level Result</p>
                                        <p class="text-xs text-muted-foreground">{{ form.waec_result ? form.waec_result.name : 'Missing document' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-5 bg-yellow-50 text-yellow-900 rounded-xl border border-yellow-200 shadow-sm mt-8">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-yellow-600"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            </div>
                             <div class="space-y-1">
                                <h4 class="font-bold">Declaration of Authenticity</h4>
                                <p class="text-sm leading-relaxed text-yellow-800/90">
                                    By submitting this application, I solemnly declare that all information provided is true to the best of my knowledge.
                                    I understand that any false declaration or forgery will result in immediate disqualification, expulsion, and possible prosecution under the law.
                                </p>
                            </div>
                        </div>
                    </div>
                    </Transition>

                </CardContent>
                <CardFooter class="flex justify-between border-t border-border/50 bg-muted/10 p-6 sm:p-8 mt-auto rounded-b-xl relative z-10">
                    <Button variant="outline" @click="prevStep" :disabled="currentStep === 0" class="w-32 h-12 font-medium">
                        Previous
                    </Button>

                    <Button v-if="currentStep < steps.length - 1" @click="nextStep" :disabled="!stepIsValid" class="w-32 h-12 shadow-lg hover:shadow-primary/30 font-semibold transition-all">
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
