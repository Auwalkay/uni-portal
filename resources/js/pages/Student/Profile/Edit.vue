<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { route } from 'ziggy-js';
import { Camera, CheckCircle, FileText, User, MapPin, School, ShieldAlert, Save, X, ChevronRight, ChevronLeft, Check, Lock } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps<{
    student: {
        id: string;
        gender: string;
        dob: string;
        phone_number: string;
        address: string;
        state_id: number;
        lga_id: number;
        next_of_kin_name: string;
        next_of_kin_phone: string;
        next_of_kin_address: string;
        passport_photo_path?: string;
        indigene_letter_path?: string;
        matriculation_number: string;
        current_level: string;
        program_duration: string;
        user: {
            name: string;
            email: string;
        };
        state?: { name: string };
        lga?: { name: string };
        o_level_results?: Array<{
            id?: number;
            exam_type: string;
            exam_year: string;
            exam_number: string;
            subjects: Array<{ subject: string; grade: string }>;
            scanned_copy_path?: string;
        }>;
    };
    states: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
    allSubjects: Array<{ id: number; name: string }>;
    canEditProfile: boolean;
    status?: string;
}>();

const page = usePage();

// Stepper Logic
const currentStep = ref(1);
const steps = [
    { id: 1, label: 'Personal Info', icon: User },
    { id: 2, label: 'Origin & Docs', icon: MapPin },
    { id: 3, label: 'O-Level', icon: School },
    { id: 4, label: 'Next of Kin', icon: ShieldAlert },
    { id: 5, label: 'Security', icon: Lock },
];

const nextStep = () => {
    if (currentStep.value < steps.length) currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const goToStep = (step: number) => {
    currentStep.value = step;
};

// Initializes Sittings from Props or Default
const initialSittings = (props.student.o_level_results && props.student.o_level_results.length > 0)
    ? props.student.o_level_results.map(result => ({
        id: result.id,
        exam_type: result.exam_type,
        exam_year: result.exam_year,
        exam_number: result.exam_number,
        subjects: result.subjects || [],
        scanned_copy: null as File | null,
        scanned_copy_path: result.scanned_copy_path
      }))
    : [{
        id: undefined,
        exam_type: '',
        exam_year: '',
        exam_number: '',
        subjects: [] as Array<{ subject: string; grade: string }>,
        scanned_copy: null as File | null,
        scanned_copy_path: undefined
      }];

const form = useForm({
    _method: 'PATCH',
    gender: props.student.gender || '',
    dob: props.student.dob || '',
    phone_number: props.student.phone_number || '',
    address: props.student.address || '',
    state_id: props.student.state_id ? String(props.student.state_id) : '',
    lga_id: props.student.lga_id ? String(props.student.lga_id) : '',
    next_of_kin_name: props.student.next_of_kin_name || '',
    next_of_kin_phone: props.student.next_of_kin_phone || '',
    next_of_kin_address: props.student.next_of_kin_address || '',
    passport_photograph: null as File | null,
    indigene_letter: null as File | null,
    // O-Level Arrays
    o_level_sittings: initialSittings,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// O-Level Logic
const activeSitting = ref(0);

const addSitting = () => {
    if (form.o_level_sittings.length < 2) {
        form.o_level_sittings.push({
            id: undefined,
            exam_type: '',
            exam_year: '',
            exam_number: '',
            subjects: [] as Array<{ subject: string; grade: string }>,
            scanned_copy: null,
            scanned_copy_path: undefined
        });
        activeSitting.value = form.o_level_sittings.length - 1;
    }
};

const removeSitting = (index: number) => {
    form.o_level_sittings.splice(index, 1);
    if (activeSitting.value >= form.o_level_sittings.length) {
        activeSitting.value = Math.max(0, form.o_level_sittings.length - 1);
    }
    // Ensure at least one sitting exists
    if (form.o_level_sittings.length === 0) {
        addSitting();
    }
};


// Smart Address Toggle
const useSameAddress = ref(false);

watch(useSameAddress, (val) => {
    if (val) {
        form.next_of_kin_address = form.address;
    }
});

watch(() => form.address, (val) => {
    if (useSameAddress.value) {
        form.next_of_kin_address = val;
    }
});

const addSubject = () => {
    const sitting = form.o_level_sittings[activeSitting.value];
    if (sitting) {
        sitting.subjects.push({ subject: '', grade: '' });
    }
};

const removeSubject = (index: number) => {
    const sitting = form.o_level_sittings[activeSitting.value];
    if (sitting) {
        sitting.subjects.splice(index, 1);
    }
};

const updatePassword = () => {
    passwordForm.put(route('user-password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            Swal.fire({
                title: "Password Updated",
                text: "Your password has been updated successfully.",
                icon: "success",
                confirmButtonColor: "#10B981" // Green-500
            });
        },
        onError: () => {
             const errors = Object.values(passwordForm.errors).flat();
             const errorMessage = errors.length > 0 
                ? errors.join('<br>') 
                : "Failed to update password. Please check your inputs.";

             Swal.fire({
                title: "Validation Error",
                html: errorMessage,
                icon: "error",
                confirmButtonColor: "#EF4444" // Red-500
            });
        },
    });
};

const previewPassport = ref<string | null>(props.student.passport_photo_path ? `/storage/${props.student.passport_photo_path}` : null);

const filteredLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => s.id === Number(form.state_id));
    return state ? state.lgas : [];
});

const handlePassportUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.passport_photograph = target.files[0];
        previewPassport.value = URL.createObjectURL(target.files[0]);
    }
};

const updateProfile = () => {
    form.post(route('student.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Flash message handled globally
        },
        onError: () => {
             const errors = Object.values(form.errors).flat();
             const errorMessage = errors.length > 0 
                ? errors.join('<br>') 
                : "Failed to update profile. Please check your inputs.";

             Swal.fire({
                title: "Validation Error",
                html: errorMessage,
                icon: "error",
                confirmButtonColor: "#EF4444" // Red-500
            });
        }
    });
};
</script>

<template>
    <Head title="Update Profile" />

    <StudentLayout>
        <!-- Removed max-w class to use full width -->
        <div class="w-full space-y-6 pb-10">
            <!-- Header Section -->
            <div class="relative h-64 md:h-56 rounded-none md:rounded-b-2xl bg-gradient-to-r from-primary/90 to-primary overflow-hidden shadow-md">
                <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]"></div>
                <!-- Adjusted positioning to avoid overlap with invalid negative margin cards -->
                <div class="absolute top-10 left-6 md:left-10 text-white z-10 max-w-2xl">
                    <Badge variant="outline" class="mb-3 text-white border-white/30 bg-white/10 hover:bg-white/20">Student Portal</Badge>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-2">Update Profile</h1>
                    <p class="text-primary-foreground/90 text-lg">Ensure your personal records and documents are up to date.</p>
                </div>
            </div>

            <!-- Content Grid with Z-Index fix to appear ON TOP of header -->
            <div class="relative z-30 grid grid-cols-1 lg:grid-cols-12 gap-8 -mt-20 px-4 md:px-8 pb-12">
                <!-- Left Column: Sticky Profile Card -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="sticky top-6 space-y-6">
                        <Card class="shadow-lg border-none relative overflow-visible z-20">
                            <CardContent class="pt-6 text-center space-y-4">
                                <div class="relative w-32 h-32 mx-auto group">
                                    <Avatar class="w-32 h-32 border-4 border-background shadow-md transition-transform group-hover:scale-105">
                                        <AvatarImage v-if="previewPassport" :src="previewPassport" class="object-cover" />
                                        <AvatarFallback class="text-4xl bg-muted">{{ student.user.name.charAt(0) }}</AvatarFallback>
                                    </Avatar>
                                    <label for="passport-upload" class="absolute bottom-0 right-0 p-2 bg-primary text-primary-foreground rounded-full cursor-pointer hover:bg-primary/90 transition shadow-sm" title="Upload Passport">
                                        <Camera class="w-4 h-4" />
                                        <input id="passport-upload" type="file" class="hidden" accept="image/*" @change="handlePassportUpload">
                                    </label>
                                </div>
                                
                                <div>
                                    <h2 class="text-xl font-bold text-foreground">{{ student.user.name }}</h2>
                                    <p class="text-sm text-muted-foreground">{{ student.matriculation_number }}</p>
                                    <div class="flex justify-center gap-2 mt-2">
                                         <Badge variant="secondary" class="text-xs">{{ student.current_level || '100' }} Level</Badge>
                                         <Badge variant="outline" class="text-xs">{{ student.user.email }}</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Document Status Summary -->
                        <Card>
                            <CardHeader class="pb-3">
                                <CardTitle class="text-base flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-primary" />
                                    Required Documents
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg border">
                                    <div class="flex items-center gap-3">
                                        <CheckCircle v-if="student.passport_photo_path" class="w-5 h-5 text-green-500" />
                                        <div v-else class="w-5 h-5 rounded-full border-2 border-muted-foreground/30"></div>
                                        <span class="text-sm font-medium">Passport Photo</span>
                                    </div>
                                    <Badge v-if="!student.passport_photo_path" variant="destructive" class="text-[10px]">Missing</Badge>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg border">
                                    <div class="flex items-center gap-3">
                                        <CheckCircle v-if="student.indigene_letter_path" class="w-5 h-5 text-green-500" />
                                        <div v-else class="w-5 h-5 rounded-full border-2 border-muted-foreground/30"></div>
                                        <span class="text-sm font-medium">Indigene Letter</span>
                                    </div>
                                    <Badge v-if="!student.indigene_letter_path" variant="outline" class="text-[10px] border-yellow-500 text-yellow-600">Pending</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Right Column: Wizard Form -->
                <div class="lg:col-span-8">
                    <form @submit.prevent="updateProfile">
                        
                        <!-- Stepper -->
                        <div class="mb-8 p-6 bg-card rounded-xl border shadow-sm sticky top-6 z-40">
                            <div class="flex justify-between items-center relative">
                                <!-- Progress Line -->
                                <div class="absolute left-0 right-0 top-1/2 h-0.5 bg-muted -z-0"></div>
                                <div class="absolute left-0 top-1/2 h-0.5 bg-primary -z-0 transition-all duration-500" :style="{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }"></div>

                                <!-- Icons -->
                                <div v-for="(step, index) in steps" :key="step.id" class="relative z-10 flex flex-col items-center gap-2 cursor-pointer group" @click="goToStep(step.id)">
                                    <div 
                                        class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 border-2"
                                        :class="[
                                            currentStep > step.id ? 'bg-primary border-primary text-primary-foreground' : 
                                            currentStep === step.id ? 'bg-background border-primary text-primary ring-4 ring-primary/20' : 
                                            'bg-background border-muted text-muted-foreground group-hover:border-primary/50'
                                        ]"
                                    >
                                        <Check v-if="currentStep > step.id" class="w-5 h-5" />
                                        <component :is="step.icon" v-else class="w-5 h-5" />
                                    </div>
                                    <span 
                                        class="text-xs font-medium hidden md:block transition-colors" 
                                        :class="currentStep >= step.id ? 'text-foreground' : 'text-muted-foreground'"
                                    >
                                        {{ step.label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 1: Personal Info -->
                        <div v-show="currentStep === 1" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <Card>
                                <CardHeader>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-primary/10 rounded-lg">
                                            <User class="w-5 h-5 text-primary" />
                                        </div>
                                        <div>
                                            <CardTitle>Personal Information</CardTitle>
                                            <CardDescription>Basic personal details.</CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label>Full Name</Label>
                                        <Input :model-value="student.user.name" disabled class="bg-muted opacity-80" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Email</Label>
                                        <Input :model-value="student.user.email" disabled class="bg-muted opacity-80" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Date of Birth</Label>
                                        <Input type="date" v-model="form.dob" :disabled="!canEditProfile" :class="{'opacity-75': !canEditProfile}" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Gender</Label>
                                        <Select v-model="form.gender" :disabled="!canEditProfile">
                                            <SelectTrigger :class="{'opacity-75': !canEditProfile}">
                                                <SelectValue placeholder="Select Gender" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Male">Male</SelectItem>
                                                <SelectItem value="Female">Female</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Phone Number</Label>
                                        <Input v-model="form.phone_number" placeholder="Enter phone number" />
                                    </div>
                                    <div class="space-y-2 md:col-span-2">
                                        <Label>Residential Address</Label>
                                        <Input v-model="form.address" placeholder="Enter your full address" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Step 2: Origin & Docs -->
                        <div v-show="currentStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <Card>
                                <CardHeader>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-orange-500/10 rounded-lg">
                                            <MapPin class="w-5 h-5 text-orange-600" />
                                        </div>
                                        <div>
                                            <CardTitle>Origin & Documents</CardTitle>
                                            <CardDescription>Confirm your origin and upload files.</CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label>State of Origin</Label>
                                        <Select v-model="form.state_id" :disabled="!canEditProfile">
                                            <SelectTrigger :class="{'opacity-75': !canEditProfile}">
                                                <SelectValue placeholder="Select State" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                                    {{ state.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Local Govt. Area</Label>
                                        <Select v-model="form.lga_id" :disabled="!canEditProfile || !form.state_id">
                                            <SelectTrigger :class="{'opacity-75': !canEditProfile}">
                                                <SelectValue placeholder="Select LGA" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="lga in filteredLgas" :key="lga.id" :value="String(lga.id)">
                                                    {{ lga.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    
                                    <Separator class="md:col-span-2 my-2" />

                                    <div class="space-y-4 md:col-span-2">
                                        <Label>Indigene Letter Upload</Label>
                                        <div v-if="canEditProfile" class="border-2 border-dashed rounded-lg p-8 flex flex-col items-center justify-center text-center hover:bg-muted/50 transition cursor-pointer relative" :class="{'bg-green-50 border-green-200': form.indigene_letter}">
                                            <input type="file" @input="form.indigene_letter = ($event.target as HTMLInputElement).files?.[0] || null" accept=".pdf,.jpg,.png" class="absolute inset-0 opacity-0 cursor-pointer" />
                                            <div class="bg-orange-100 p-3 rounded-full mb-3">
                                                <FileText class="w-6 h-6 text-orange-600" />
                                            </div>
                                            <p class="text-sm font-medium" v-if="!form.indigene_letter">
                                                Click to upload Indigene Letter
                                            </p>
                                            <p class="text-sm font-medium text-green-600" v-else>
                                                Selected: {{ form.indigene_letter.name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                Supported: PDF, JPG, PNG (Max 2MB)
                                            </p>
                                        </div>
                                        <div v-else class="p-4 bg-muted/50 rounded-lg border text-center text-muted-foreground text-sm">
                                            Updating Indigene Letter is restricted for returning students.
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Step 3: O-Level Results -->
                        <div v-show="currentStep === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <Card>
                                <CardHeader>
                                    <div class="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center">
                                        <div>
                                            <CardTitle>O-Level Results</CardTitle>
                                            <CardDescription>Enter your examination details. You can add up to 2 sittings.</CardDescription>
                                        </div>
                                        
                                        <!-- Sitting Tabs -->
                                        <div class="flex items-center gap-2">
                                            <div class="flex bg-muted p-1 rounded-lg">
                                                <button 
                                                    v-for="(sitting, idx) in form.o_level_sittings" 
                                                    :key="idx"
                                                    type="button"
                                                    @click="activeSitting = idx"
                                                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
                                                    :class="activeSitting === idx ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                                >
                                                    Sitting {{ idx + 1 }}
                                                </button>
                                            </div>
                                            
                                            <Button 
                                                v-if="form.o_level_sittings.length < 2 && canEditProfile" 
                                                type="button" 
                                                variant="outline" 
                                                size="sm" 
                                                @click="addSitting"
                                                class="h-9 px-3"
                                                title="Add another sitting"
                                            >
                                                + Add Sitting
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="grid gap-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-2">
                                            <Label>Exam Type</Label>
                                            <Select v-model="form.o_level_sittings[activeSitting].exam_type" :disabled="!canEditProfile">
                                                <SelectTrigger :class="{'opacity-75': !canEditProfile}">
                                                    <SelectValue placeholder="Select Exam" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="WAEC">WAEC</SelectItem>
                                                    <SelectItem value="NECO">NECO</SelectItem>
                                                    <SelectItem value="GCE">GCE</SelectItem>
                                                    <SelectItem value="NABTEB">NABTEB</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Exam Year</Label>
                                             <Select v-model="form.o_level_sittings[activeSitting].exam_year" :disabled="!canEditProfile">
                                                <SelectTrigger :class="{'opacity-75': !canEditProfile}">
                                                    <SelectValue placeholder="Year" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="year in Array.from({length: 15}, (_, i) => String(new Date().getFullYear() - i))" :key="year" :value="year">
                                                        {{ year }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Exam Number</Label>
                                            <Input v-model="form.o_level_sittings[activeSitting].exam_number" placeholder="Enter Exam No." :disabled="!canEditProfile" :class="{'opacity-75': !canEditProfile}" />
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <Label>Subjects & Grades</Label>
                                            <Button v-if="canEditProfile" type="button" variant="ghost" size="sm" class="text-primary hover:text-primary/80 h-auto p-0" @click="addSubject">
                                                + Add Subject
                                            </Button>
                                        </div>
                                        
                                        <div class="bg-muted/30 rounded-lg p-2 space-y-2">
                                            <div v-for="(item, index) in form.o_level_sittings[activeSitting].subjects" :key="index" class="flex gap-2 items-start animate-in fade-in slide-in-from-top-2 duration-200">
                                                <div class="flex-1">
                                                    <Select v-model="item.subject" :disabled="!canEditProfile">
                                                        <SelectTrigger class="bg-background" :class="{'opacity-75': !canEditProfile}">
                                                            <SelectValue placeholder="Select Subject" />
                                                        </SelectTrigger>
                                                        <SelectContent class="max-h-60">
                                                            <SelectItem v-for="subj in allSubjects" :key="subj.id" :value="subj.name">
                                                                {{ subj.name }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div class="w-28 md:w-32">
                                                    <Select v-model="item.grade" :disabled="!canEditProfile">
                                                        <SelectTrigger class="bg-background" :class="{'opacity-75': !canEditProfile}">
                                                            <SelectValue placeholder="Grade" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="g in ['A1','B2','B3','C4','C5','C6','D7','E8','F9']" :key="g" :value="g">{{ g }}</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <Button v-if="canEditProfile" type="button" variant="ghost" size="icon" class="text-destructive hover:bg-destructive/10 shrink-0" @click="removeSubject(index)">
                                                    <X class="w-4 h-4" />
                                                </Button>
                                            </div>
                                            <div v-if="form.o_level_sittings[activeSitting].subjects.length === 0" class="text-sm text-muted-foreground text-center py-6 border-2 border-dashed rounded-md">
                                                <span v-if="canEditProfile">No subjects added. Click "+ Add Subject" to begin.</span>
                                                <span v-else>No subjects recorded.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div class="space-y-4">
                                         <Label>Upload Result Verification (Scanned Copy)</Label>
                                         <div v-if="canEditProfile" class="border-2 border-dashed rounded-lg p-6 flex flex-col items-center justify-center text-center hover:bg-muted/50 transition cursor-pointer relative" :class="{'bg-purple-50 border-purple-200': form.o_level_sittings[activeSitting].scanned_copy}">
                                            <input type="file" @input="form.o_level_sittings[activeSitting].scanned_copy = ($event.target as HTMLInputElement).files?.[0] || null" accept=".pdf,.jpg,.png" class="absolute inset-0 opacity-0 cursor-pointer" />
                                            <div class="bg-purple-100 p-2 rounded-full mb-2">
                                                <FileText class="w-5 h-5 text-purple-600" />
                                            </div>
                                            
                                            <!-- Logic to show existing file or selected file -->
                                            <div v-if="form.o_level_sittings[activeSitting].scanned_copy">
                                                <p class="text-sm font-medium text-purple-600">New File Selected</p>
                                                <p class="text-xs text-muted-foreground">{{ form.o_level_sittings[activeSitting].scanned_copy?.name }}</p>
                                            </div>
                                            <div v-else-if="form.o_level_sittings[activeSitting].scanned_copy_path">
                                                 <p class="text-sm font-medium text-green-600">File Already Uploaded</p>
                                                 <p class="text-xs text-muted-foreground">Click to replace</p>
                                            </div>
                                            <div v-else>
                                                <p class="text-sm font-medium">Upload Scanned Result</p>
                                                <p class="text-xs text-muted-foreground">PDF, JPG, PNG</p>
                                            </div>
                                        </div>
                                        <div v-else class="p-4 bg-muted/50 rounded-lg border text-center text-muted-foreground text-sm">
                                            <span v-if="form.o_level_sittings[activeSitting].scanned_copy_path" class="text-green-600 flex items-center justify-center gap-2">
                                                <CheckCircle class="w-4 h-4" /> Scanned Copy Uploaded
                                            </span>
                                            <span v-else>No scanned copy uploaded. Updates restricted.</span>
                                        </div>
                                    </div>

                                    <!-- Remove Sitting Button -->
                                    <div v-if="form.o_level_sittings.length > 1 && canEditProfile" class="flex justify-end pt-2">
                                        <Button type="button" variant="ghost" size="sm" class="text-destructive hover:bg-destructive/10 hover:text-destructive" @click="removeSitting(activeSitting)">
                                            Remove this Sitting
                                        </Button>
                                    </div>

                                </CardContent>
                            </Card>
                        </div>

                        <!-- Step 4: Next of Kin -->
                        <div v-show="currentStep === 4" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <Card>
                                <CardHeader>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-500/10 rounded-lg">
                                            <ShieldAlert class="w-5 h-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <CardTitle>Next of Kin</CardTitle>
                                            <CardDescription>In case of emergency.</CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label>Full Name</Label>
                                        <Input v-model="form.next_of_kin_name" placeholder="Name of relative" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Phone Number</Label>
                                        <Input v-model="form.next_of_kin_phone" placeholder="Contact number" />
                                    </div>
                                    <div class="space-y-2 md:col-span-2">
                                        <div class="flex items-center justify-between mb-2">
                                            <Label>Address</Label>
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" id="sameAddress" v-model="useSameAddress" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary h-4 w-4">
                                                <label for="sameAddress" class="text-xs text-muted-foreground cursor-pointer select-none">Same as Residential Address</label>
                                            </div>
                                        </div>
                                        <Input v-model="form.next_of_kin_address" placeholder="Address (Optional)" :disabled="useSameAddress" :class="{'opacity-75': useSameAddress}" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                         <!-- Step 5: Account Security -->
                         <div v-show="currentStep === 5" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <Card>
                                <CardHeader>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-red-500/10 rounded-lg">
                                            <Lock class="w-5 h-5 text-red-600" />
                                        </div>
                                        <div>
                                            <CardTitle>Account Security</CardTitle>
                                            <CardDescription>Update your login password.</CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-4 max-w-lg">
                                    <div class="space-y-2">
                                        <Label>Current Password</Label>
                                        <Input type="password" v-model="passwordForm.current_password" placeholder="Current Password" />
                                        <p v-if="passwordForm.errors.current_password" class="text-sm text-destructive">{{ passwordForm.errors.current_password }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>New Password</Label>
                                        <Input type="password" v-model="passwordForm.password" placeholder="New Password" />
                                         <p v-if="passwordForm.errors.password" class="text-sm text-destructive">{{ passwordForm.errors.password }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Confirm New Password</Label>
                                        <Input type="password" v-model="passwordForm.password_confirmation" placeholder="Confirm New Password" />
                                    </div>
                                    
                                    <div class="pt-2">
                                        <Button type="button" @click="updatePassword" :disabled="passwordForm.processing" variant="destructive">
                                            <span v-if="passwordForm.processing">Updating...</span>
                                            <span v-else>Update Password</span>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Actions Bar -->
                        <div class="flex justify-between items-center pt-8 border-t mt-8">
                            <Button type="button" variant="outline" @click="prevStep" :disabled="currentStep === 1">
                                <ChevronLeft class="w-4 h-4 mr-2" /> Previous
                            </Button>
                            <div class="flex gap-2">
                                <Button type="button" v-if="currentStep < 5" @click="nextStep">
                                    Next <ChevronRight class="w-4 h-4 ml-2" />
                                </Button>
                                <Button type="submit" v-if="currentStep === 4" :disabled="form.processing" class="bg-green-600 hover:bg-green-700">
                                    <Save class="w-4 h-4 mr-2" />
                                    {{ form.processing ? 'Saving...' : 'Save Profile' }}
                                </Button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
