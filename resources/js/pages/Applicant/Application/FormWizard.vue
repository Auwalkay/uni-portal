<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
import {
  Check,
  ChevronLeft,
  ChevronRight,
  Send,
  ArrowLeft,
  AlertCircle,
  Plus,
  Trash2,
  Save,
  Loader2,
  FileCheck,
} from 'lucide-vue-next';

interface StepItem {
    id: string;
    label: string;
}

const props = defineProps<{
    applicant?: any;
    mode?: string;
    isPostgraduate?: boolean;
    pgTier?: string | null;
    programme_id?: string;
    programme?: { id: string; name: string; academic_stream?: string; calendar_type?: string } | null;
    states?: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
    scholarships?: Array<{ id: string; name: string; percentage: string }>;
    currentSession?: { id: string; name: string } | null;
    qualifications?: string[];
    degreeClasses?: string[];
}>();

const isPg = computed(() => {
    return !!(
        props.isPostgraduate ||
        props.mode === 'PG' ||
        props.programme?.academic_stream === 'PG'
    );
});

const steps = computed<StepItem[]>(() =>
    isPg.value
        ? [
            { id: 'pathway', label: 'Pathway' },
            { id: 'biodata', label: 'Biodata' },
            { id: 'academics', label: 'Qualifications & Referees' },
            { id: 'documents', label: 'Documents' },
            { id: 'review', label: 'Review' },
        ]
        : [
            { id: 'jamb', label: 'JAMB' },
            { id: 'biodata', label: 'Biodata' },
            { id: 'academics', label: 'Academic History' },
            { id: 'documents', label: 'Documents' },
            { id: 'review', label: 'Review' },
        ]
);

const currentStep = ref(0);

// JAMB Fetch State
const jambNumber = ref(props.applicant?.jamb_registration_number || '');
const isFetching = ref(false);
const jambError = ref('');
const fetchedJambData = ref<any>(null);

const defaultQualifications = [
    'ND', 'HND', 'B.Sc', 'B.A', 'B.Eng', 'B.Tech', 'B.Ed', 'LL.B',
    'PGD', 'MBA', 'MIM', 'M.Sc', 'M.A', 'M.Eng', 'M.Ed', 'M.Phil',
    'ICAN', 'ANAN', 'ACCA', 'CIB', 'CIBN', 'Other',
];

const defaultDegreeClasses = [
    'First Class',
    'Second Class Upper (2:1)',
    'Second Class Lower (2:2)',
    'Third Class',
    'Distinction',
    'Upper Credit',
    'Lower Credit',
    'Merit',
    'Credit',
    'Pass',
    'Associate / Member',
    'Passed / Qualified',
    'Fellow',
];

const qualificationOptions = computed(() => props.qualifications || defaultQualifications);
const degreeClassOptions = computed(() => props.degreeClasses || defaultDegreeClasses);

const savedApplicant = ref<any>(props.applicant || {});
const savedDocuments = computed(() => savedApplicant.value?.documents || []);

const getExistingDoc = (type: string) => {
    return savedDocuments.value?.find((d: any) => d.type === type);
};

const initialQualifications = computed(() => {
    if (props.applicant?.qualifications && props.applicant.qualifications.length > 0) {
        return props.applicant.qualifications.map((q: any) => ({
            qualification_type: q.qualification_type || '',
            other_qualification: '',
            institution_name: q.institution_name || '',
            course_of_study: q.course_of_study || '',
            class_of_degree: q.class_of_degree || '',
            cgpa: q.cgpa != null ? String(q.cgpa) : '',
            scale_max: q.scale_max != null ? String(q.scale_max) : '5.00',
            graduation_year: q.graduation_year ? String(q.graduation_year) : String(new Date().getFullYear() - 1),
            certificate_number: q.certificate_number || '',
        }));
    }
    return [
        {
            qualification_type: '',
            other_qualification: '',
            institution_name: '',
            course_of_study: '',
            class_of_degree: '',
            cgpa: '',
            scale_max: '5.00',
            graduation_year: String(new Date().getFullYear() - 1),
            certificate_number: '',
        },
    ];
});

const initialEmployments = computed(() => {
    if (props.applicant?.employments && props.applicant.employments.length > 0) {
        return props.applicant.employments.map((e: any) => ({
            employer_name: e.employer_name || '',
            job_title: e.job_title || '',
            department_or_unit: e.department_or_unit || '',
            start_date: e.start_date || '',
            end_date: e.end_date || '',
            is_current: Boolean(e.is_current),
            responsibilities: e.responsibilities || '',
        }));
    }
    return [];
});

const initialReferees = computed(() => {
    if (props.applicant?.referees && props.applicant.referees.length > 0) {
        return props.applicant.referees.map((r: any) => ({
            name: r.name || '',
            email: r.email || '',
            phone: r.phone || '',
            institution_or_organization: r.institution_or_organization || '',
            position_or_rank: r.position_or_rank || '',
            relationship: r.relationship || '',
        }));
    }
    return [
        { name: '', email: '', phone: '', institution_or_organization: '', position_or_rank: '', relationship: 'Academic Supervisor' },
        { name: '', email: '', phone: '', institution_or_organization: '', position_or_rank: '', relationship: 'Head of Department / Lecturer' },
        { name: '', email: '', phone: '', institution_or_organization: '', position_or_rank: '', relationship: 'Academic / Professional Referee' },
    ];
});

const form = useForm({
    title: props.applicant?.title || 'Mr.',
    first_name: props.applicant?.first_name || '',
    last_name: props.applicant?.last_name || '',
    dob: props.applicant?.dob ? String(props.applicant.dob).substring(0, 10) : '',
    phone: props.applicant?.phone || '',
    gender: props.applicant?.gender || 'male',
    marital_status: props.applicant?.marital_status || 'Single',
    nationality: props.applicant?.nationality || 'Nigerian',
    has_name_change: Boolean(props.applicant?.has_name_change),
    previous_full_name: props.applicant?.previous_full_name || '',
    address: props.applicant?.address || '',
    state_id: props.applicant?.state_id ? String(props.applicant.state_id) : '',
    lga_id: props.applicant?.lga_id ? String(props.applicant.lga_id) : '',
    next_of_kin_name: props.applicant?.next_of_kin_name || '',
    next_of_kin_phone: props.applicant?.next_of_kin_phone || '',
    next_of_kin_relationship: props.applicant?.next_of_kin_relationship || '',
    jamb_score: props.applicant?.jamb_score ? String(props.applicant.jamb_score) : '',
    study_mode: props.applicant?.study_mode || 'full_time',
    area_of_specialization: props.applicant?.area_of_specialization || '',
    sponsorship_type: props.applicant?.sponsorship_type || 'self',
    sponsor_name: props.applicant?.sponsor_name || '',
    previous_institution: props.applicant?.previous_institution || '',
    previous_qualification: props.applicant?.previous_qualification || '',
    previous_course_of_study: props.applicant?.previous_course_of_study || '',
    previous_class_of_degree: props.applicant?.previous_class_of_degree || '',
    previous_cgpa: props.applicant?.previous_cgpa ? String(props.applicant.previous_cgpa) : '',
    graduation_year: props.applicant?.graduation_year ? String(props.applicant.graduation_year) : '',
    nysc_status: props.applicant?.nysc_status || 'Completed',
    nysc_certificate_number: props.applicant?.nysc_certificate_number || '',
    official_transcript_status: props.applicant?.official_transcript_status || 'not_requested',
    work_experience_years: props.applicant?.work_experience_years ? String(props.applicant.work_experience_years) : '0',
    statement_of_purpose: props.applicant?.statement_of_purpose || '',
    research_proposal_title: props.applicant?.research_proposal_title || '',
    mode: props.mode || props.applicant?.application_mode || (isPg.value ? 'PG' : 'UTME'),
    programme_id: props.programme_id || props.applicant?.program_choice_1 || '',
    jamb_number: props.applicant?.jamb_registration_number || '',
    passport_photo: null as File | null,
    waec_result: null as File | null,
    academic_transcript: null as File | null,
    degree_certificate: null as File | null,
    nysc_certificate: null as File | null,
    cv_resume: null as File | null,
    concept_note: null as File | null,
    name_change_evidence: null as File | null,
    qualifications: initialQualifications.value,
    employments: initialEmployments.value,
    referees: initialReferees.value,
});

const addQualification = () => {
    form.qualifications.push({
        qualification_type: '',
        other_qualification: '',
        institution_name: '',
        course_of_study: '',
        class_of_degree: '',
        cgpa: '',
        scale_max: '5.00',
        graduation_year: String(new Date().getFullYear()),
        certificate_number: '',
    });
};

const removeQualification = (index: number) => {
    if (form.qualifications.length > 1) {
        form.qualifications.splice(index, 1);
    }
};

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

watch(jambNumber, (val) => {
    form.jamb_number = val ? val.trim().toUpperCase() : '';
});

watch(() => form.jamb_number, (val) => {
    if (val && jambNumber.value !== val) {
        jambNumber.value = val;
    }
});

const confirmJambData = () => {
    if (fetchedJambData.value) {
        form.first_name = fetchedJambData.value.first_name;
        form.last_name = fetchedJambData.value.last_name;
        form.dob = fetchedJambData.value.dob;
        form.jamb_score = String(fetchedJambData.value.jamb_score);
        form.jamb_number = fetchedJambData.value.jamb_number;
        jambNumber.value = fetchedJambData.value.jamb_number;
        goToStep(1);
    }
};

const stepIsValid = computed(() => {
    switch (currentStep.value) {
        case 0:
            return true;
        case 1:
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
            if (isPg.value) {
                const hasMultiQuals = form.qualifications.length > 0 && form.qualifications.every(
                    q => q.institution_name.trim() &&
                        (q.qualification_type === 'Other' ? (q.other_qualification && q.other_qualification.trim()) : q.qualification_type.trim()) &&
                        q.class_of_degree.trim() &&
                        q.course_of_study.trim()
                );
                const has3Referees = form.referees.length === 3 && form.referees.every(
                    r => r.name.trim() && r.email.trim() && r.institution_or_organization.trim()
                );
                return hasMultiQuals && has3Referees;
            }
            return !!(form.jamb_score && (form.jamb_number || jambNumber.value));
        case 3:
            return true;
        case 4:
            return true;
        default:
            return true;
    }
});

const isSavingStep = ref(false);
const lastSavedAt = ref<string | null>(null);

const saveStep = async (showNotification = false): Promise<boolean> => {
    isSavingStep.value = true;
    try {
        if (jambNumber.value && !form.jamb_number) {
            form.jamb_number = jambNumber.value.trim().toUpperCase();
        }

        const formData = new FormData();
        const currentStepObj = steps.value[currentStep.value];
        formData.append('step', currentStepObj?.id || String(currentStep.value));

        // Append form fields
        Object.keys(form.data()).forEach(key => {
            const val = (form as any)[key];
            if (val === null || val === undefined) return;

            if (Array.isArray(val)) {
                val.forEach((item, idx) => {
                    if (typeof item === 'object' && item !== null) {
                        Object.keys(item).forEach(subKey => {
                            formData.append(`${key}[${idx}][${subKey}]`, item[subKey] ?? '');
                        });
                    } else {
                        formData.append(`${key}[${idx}]`, String(item));
                    }
                });
            } else if (val instanceof File) {
                formData.append(key, val);
            } else if (typeof val === 'boolean') {
                formData.append(key, val ? '1' : '0');
            } else {
                formData.append(key, String(val));
            }
        });

        const response = await axios.post(route('applicant.apply.save_step'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (response.data?.success) {
            const now = new Date();
            lastSavedAt.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            if (response.data.applicant) {
                savedApplicant.value = response.data.applicant;
                if (props.applicant) {
                    props.applicant.documents = response.data.applicant.documents;
                }
            }

            if (showNotification) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Draft saved successfully',
                    showConfirmButton: false,
                    timer: 2000,
                });
            }
            return true;
        }
        return false;
    } catch (err: any) {
        console.error('Failed saving step draft:', err);
        if (showNotification) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: err?.response?.data?.error || 'Failed to save draft',
                showConfirmButton: false,
                timer: 3000,
            });
        }
        return false;
    } finally {
        isSavingStep.value = false;
    }
};

const canNavigateToStep = (targetIndex: number) => {
    if (targetIndex <= currentStep.value) return true;
    if (targetIndex === currentStep.value + 1) return stepIsValid.value;
    return false;
};

const goToStep = async (targetIndex: number) => {
    if (targetIndex === currentStep.value) return;
    if (canNavigateToStep(targetIndex)) {
        await saveStep(false);
        currentStep.value = targetIndex;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const nextStep = async () => {
    if (currentStep.value < steps.value.length - 1 && stepIsValid.value) {
        await saveStep(false);
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = async () => {
    if (currentStep.value > 0) {
        await saveStep(false);
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submitApplication = () => {
    if (jambNumber.value && !form.jamb_number) {
        form.jamb_number = jambNumber.value.trim().toUpperCase();
    }

    if (isPg.value && form.qualifications.length > 0) {
        // If 'Other' was chosen, normalize qualification_type before posting
        form.qualifications.forEach(q => {
            if (q.qualification_type === 'Other' && q.other_qualification) {
                q.qualification_type = q.other_qualification.trim();
            }
        });
        const primary = form.qualifications[form.qualifications.length - 1];
        form.previous_institution = primary.institution_name;
        form.previous_qualification = primary.qualification_type;
        form.previous_course_of_study = primary.course_of_study;
        form.previous_class_of_degree = primary.class_of_degree;
        form.previous_cgpa = primary.cgpa;
        form.graduation_year = primary.graduation_year;
    }

    form.post(route('applicant.apply.store'), {
        forceFormData: true,
        onStart: () => {
            Swal.fire({
                title: 'Submitting application...',
                text: 'Please wait while we file your application.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });
        },
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Application Submitted',
                text: 'Your application has been received successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onError: (errors) => {
            const firstKey = Object.keys(errors)[0];
            const firstMsg = firstKey ? errors[firstKey] : 'Please verify all required fields.';

            Swal.fire({
                icon: 'error',
                title: 'Submission Incomplete',
                text: firstMsg,
                confirmButtonColor: '#0f172a',
            });
        },
    });
};
</script>

<template>
    <Head title="Application Form" />

    <ApplicantLayout :breadcrumbs="[
        { title: 'Admissions', href: route('applicant.apply.start') },
        { title: programme?.name || 'Application Form', href: '#' }
    ]">
        <!-- Full-Page Executive Application Canvas -->
        <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Simple Top Header -->
            <div class="space-y-6">
                <div class="flex items-center justify-between gap-4">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-xs text-muted-foreground hover:text-foreground -ml-2 h-8"
                        @click="router.visit(route('applicant.apply.start'))"
                    >
                        <ArrowLeft class="w-3.5 h-3.5 mr-1.5" />
                        Change Programme
                    </Button>

                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-xs font-medium text-muted-foreground">
                            {{ isPg ? 'Postgraduate' : 'Undergraduate' }}
                        </Badge>
                        <span v-if="currentSession" class="text-xs text-muted-foreground">
                            {{ currentSession.name }} Session
                        </span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <div>
                        <h1 class="font-serif text-3xl sm:text-4xl font-bold text-foreground tracking-tight">
                            {{ programme?.name || 'Application Form' }}
                        </h1>
                        <p class="text-sm text-muted-foreground mt-1">
                            Step {{ currentStep + 1 }} of {{ steps.length }}: {{ steps[currentStep]?.label }}
                        </p>
                    </div>

                    <!-- Auto-save / Draft status -->
                    <div class="flex items-center gap-2 text-xs">
                        <span v-if="isSavingStep" class="inline-flex items-center text-primary font-medium">
                            <Loader2 class="w-3.5 h-3.5 mr-1.5 animate-spin" /> Saving draft...
                        </span>
                        <span v-else-if="lastSavedAt" class="inline-flex items-center text-muted-foreground">
                            <Check class="w-3.5 h-3.5 mr-1.5 text-emerald-600" /> Draft saved at {{ lastSavedAt }}
                        </span>
                    </div>
                </div>

                <!-- Sleek Minimal Horizontal Stepper -->
                <div class="pt-2">
                    <nav aria-label="Progress">
                        <ol class="flex items-center w-full">
                            <li
                                v-for="(step, index) in steps"
                                :key="step.id"
                                class="relative flex-1 flex items-center"
                                :class="{ 'pr-4 sm:pr-8': index < steps.length - 1 }"
                            >
                                <button
                                    type="button"
                                    class="group flex items-center gap-2 text-left focus:outline-none"
                                    :disabled="!canNavigateToStep(index)"
                                    @click="goToStep(index)"
                                >
                                    <span
                                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 transition-colors"
                                        :class="[
                                            index < currentStep
                                                ? 'bg-primary text-primary-foreground'
                                                : index === currentStep
                                                ? 'border-2 border-primary text-primary bg-background'
                                                : 'border border-border text-muted-foreground bg-muted/30'
                                        ]"
                                    >
                                        <Check v-if="index < currentStep" class="w-3.5 h-3.5 stroke-[2.5]" />
                                        <span v-else>{{ index + 1 }}</span>
                                    </span>
                                    <span
                                        class="hidden md:inline text-xs font-medium transition-colors"
                                        :class="index === currentStep ? 'text-foreground font-semibold' : 'text-muted-foreground'"
                                    >
                                        {{ step.label }}
                                    </span>
                                </button>

                                <!-- Connecting Line -->
                                <div
                                    v-if="index < steps.length - 1"
                                    class="flex-1 ml-3 sm:ml-4 h-0.5 transition-colors"
                                    :class="index < currentStep ? 'bg-primary' : 'bg-border'"
                                ></div>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Main Form Card (Uncluttered, Focused Workspace) -->
            <Card class="border border-border/70 shadow-sm rounded-2xl bg-card">
                <CardContent class="p-6 sm:p-10 space-y-8">

                    <!-- ========================================== -->
                    <!-- STEP 0: Pathway & Mode (PG) OR JAMB (UG)   -->
                    <!-- ========================================== -->
                    <div v-if="currentStep === 0" class="space-y-6">
                        <!-- Postgraduate Stream Overview -->
                        <div v-if="isPg" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-foreground">Study Mode & Specialization</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">Please indicate your preferred enrollment schedule and research focus.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-sm">Mode of Study <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.study_mode">
                                        <SelectTrigger class="h-11"><SelectValue placeholder="Select mode of study" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="full_time">Full-Time</SelectItem>
                                            <SelectItem value="part_time">Part-Time</SelectItem>
                                            <SelectItem value="executive">Executive / Weekend</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-sm">Sponsorship Type <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.sponsorship_type">
                                        <SelectTrigger class="h-11"><SelectValue placeholder="Select sponsorship type" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="self">Self-Sponsored</SelectItem>
                                            <SelectItem value="tetfund">TETFund</SelectItem>
                                            <SelectItem value="ptdf">PTDF</SelectItem>
                                            <SelectItem value="employer">Employer / Corporate</SelectItem>
                                            <SelectItem value="scholarship">University Scholarship</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div v-if="form.sponsorship_type !== 'self'" class="space-y-2">
                                    <Label class="text-sm">Sponsoring Organization</Label>
                                    <Input
                                        v-model="form.sponsor_name"
                                        placeholder="e.g. Central Bank of Nigeria / TETFund"
                                        class="h-11"
                                    />
                                </div>

                                <div class="space-y-2 md:col-span-2 lg:col-span-3">
                                    <Label class="text-sm">Area of Specialization / Concentration</Label>
                                    <Input
                                        v-model="form.area_of_specialization"
                                        placeholder="e.g. Artificial Intelligence, Commercial Law, Corporate Finance"
                                        class="h-11"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Undergraduate JAMB Verification -->
                        <div v-else class="space-y-6 max-w-md mx-auto py-6">
                            <div class="text-center space-y-1">
                                <h3 class="text-lg font-semibold text-foreground">Import JAMB Details</h3>
                                <p class="text-xs text-muted-foreground">Enter your JAMB registration number to verify your score.</p>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-sm">JAMB Registration Number</Label>
                                    <div class="flex gap-2">
                                        <Input v-model="jambNumber" placeholder="e.g. 202612345678AB" class="h-11 uppercase font-mono" />
                                        <Button class="h-11 px-5" @click="fetchJambDetails" :disabled="isFetching || !jambNumber">
                                            {{ isFetching ? 'Fetching...' : 'Verify' }}
                                        </Button>
                                    </div>
                                    <p v-if="jambError" class="text-xs text-destructive flex items-center gap-1 mt-1">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                        {{ jambError }}
                                    </p>
                                </div>

                                <div v-if="fetchedJambData" class="p-4 rounded-xl border bg-muted/30 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <img :src="fetchedJambData.passport_url" class="w-12 h-12 rounded-full border" />
                                        <div>
                                            <p class="font-semibold text-sm">{{ fetchedJambData.first_name }} {{ fetchedJambData.last_name }}</p>
                                            <p class="text-xs text-muted-foreground">Score: <strong class="text-foreground">{{ fetchedJambData.jamb_score }}</strong></p>
                                        </div>
                                    </div>
                                    <Button class="w-full" size="sm" variant="secondary" @click="confirmJambData">
                                        Continue with these details
                                    </Button>
                                </div>

                                <Button variant="outline" class="w-full h-11 text-xs" @click="() => { if (jambNumber) { form.jamb_number = jambNumber.trim().toUpperCase(); } goToStep(1); }">
                                    Skip & Enter Manually
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 1: Personal Biodata                   -->
                    <!-- ========================================== -->
                    <div v-if="currentStep === 1" class="space-y-8">
                        <!-- Identity & Legal Name -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-base font-semibold text-foreground">Personal Details</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">Please provide your official names as they appear on your credentials.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                                <div v-if="isPg" class="space-y-1.5">
                                    <Label class="text-xs">Title <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.title">
                                        <SelectTrigger class="h-10"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Mr.">Mr.</SelectItem>
                                            <SelectItem value="Mrs.">Mrs.</SelectItem>
                                            <SelectItem value="Miss">Miss</SelectItem>
                                            <SelectItem value="Ms.">Ms.</SelectItem>
                                            <SelectItem value="Dr.">Dr.</SelectItem>
                                            <SelectItem value="Engr.">Engr.</SelectItem>
                                            <SelectItem value="Barr.">Barr.</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">First Name <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.first_name" placeholder="First Name" class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Last Name <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.last_name" placeholder="Last Name" class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Date of Birth <span class="text-destructive">*</span></Label>
                                    <Input type="date" v-model="form.dob" class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Gender <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.gender">
                                        <SelectTrigger class="h-10"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Marital Status</Label>
                                    <Select v-model="form.marital_status">
                                        <SelectTrigger class="h-10"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Single">Single</SelectItem>
                                            <SelectItem value="Married">Married</SelectItem>
                                            <SelectItem value="Divorced">Divorced</SelectItem>
                                            <SelectItem value="Widowed">Widowed</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Phone Number <span class="text-destructive">*</span></Label>
                                    <Input type="tel" v-model="form.phone" placeholder="+234..." class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Nationality</Label>
                                    <Input v-model="form.nationality" class="h-10" />
                                </div>
                            </div>

                            <!-- Name change toggle -->
                            <div v-if="isPg" class="pt-2">
                                <div class="flex items-center gap-2">
                                    <input
                                        id="has_name_change"
                                        type="checkbox"
                                        v-model="form.has_name_change"
                                        class="h-4 w-4 rounded border-border text-primary"
                                    />
                                    <Label for="has_name_change" class="text-xs text-muted-foreground cursor-pointer">
                                        I have changed my name since my previous degree was issued (e.g. by marriage)
                                    </Label>
                                </div>
                                <div v-if="form.has_name_change" class="pt-3 pl-6 max-w-md space-y-1">
                                    <Label class="text-xs">Previous Full Name</Label>
                                    <Input v-model="form.previous_full_name" placeholder="Name as printed on degree" class="h-10" />
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <!-- Contact & Domicile -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-base font-semibold text-foreground">Contact & Origin</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">Your residential address and state of origin.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                                <div class="space-y-1.5 md:col-span-2">
                                    <Label class="text-xs">Residential Address <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.address" placeholder="Full residential address" class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">State of Origin <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.state_id">
                                        <SelectTrigger class="h-10"><SelectValue placeholder="Select State" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                                {{ state.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">LGA of Origin <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.lga_id" :disabled="!form.state_id">
                                        <SelectTrigger class="h-10"><SelectValue placeholder="Select LGA" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="lga in filteredLgas" :key="lga.id" :value="String(lga.id)">
                                                {{ lga.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <!-- Next of Kin -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-base font-semibold text-foreground">Next of Kin</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">Emergency contact person details.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Full Name <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.next_of_kin_name" placeholder="Full Name" class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Phone Number <span class="text-destructive">*</span></Label>
                                    <Input v-model="form.next_of_kin_phone" placeholder="+234..." class="h-10" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs">Relationship <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.next_of_kin_relationship">
                                        <SelectTrigger class="h-10"><SelectValue placeholder="Select" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Father">Father</SelectItem>
                                            <SelectItem value="Mother">Mother</SelectItem>
                                            <SelectItem value="Spouse">Spouse</SelectItem>
                                            <SelectItem value="Sibling">Sibling</SelectItem>
                                            <SelectItem value="Guardian">Guardian</SelectItem>
                                            <SelectItem value="Other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 2: Qualifications & 3 Referees        -->
                    <!-- ========================================== -->
                    <div v-if="currentStep === 2" class="space-y-8">
                        <template v-if="isPg">
                            <!-- Tertiary Degrees -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-foreground">Tertiary Academic Qualifications</h3>
                                        <p class="text-xs text-muted-foreground mt-0.5">Add your bachelor's degree, HND, or postgraduate diploma.</p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" class="text-xs h-8" @click="addQualification">
                                        <Plus class="w-3.5 h-3.5 mr-1" />
                                        Add Degree
                                    </Button>
                                </div>

                                <p v-if="form.errors.previous_qualification" class="text-xs text-destructive bg-destructive/10 p-3 rounded-lg">
                                    {{ form.errors.previous_qualification }}
                                </p>

                                <div class="space-y-4">
                                    <div
                                        v-for="(qual, qIdx) in form.qualifications"
                                        :key="qIdx"
                                        class="p-5 rounded-xl border border-border/80 bg-muted/10 space-y-4"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-semibold text-foreground">
                                                {{ qIdx === 0 ? 'Primary Qualifying Degree' : `Additional Degree #${qIdx + 1}` }}
                                            </span>
                                            <Button
                                                v-if="form.qualifications.length > 1"
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                class="text-destructive h-7 text-xs px-2"
                                                @click="removeQualification(qIdx)"
                                            >
                                                <Trash2 class="w-3.5 h-3.5 mr-1" />
                                                Remove
                                            </Button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div class="space-y-1.5 sm:col-span-2 lg:col-span-3">
                                                <Label class="text-xs">Awarding Institution <span class="text-destructive">*</span></Label>
                                                <Input v-model="qual.institution_name" placeholder="e.g. University of Lagos" class="h-10 bg-background" />
                                            </div>

                                            <div class="space-y-1.5">
                                                <Label class="text-xs">Qualification Type <span class="text-destructive">*</span></Label>
                                                <Select v-model="qual.qualification_type">
                                                    <SelectTrigger class="h-10 bg-background"><SelectValue placeholder="Select type" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="q in qualificationOptions" :key="q" :value="q">
                                                            {{ q === 'Other' ? 'Other (Specify below)' : q }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div v-if="qual.qualification_type === 'Other'" class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                                                <Label class="text-xs">Specify Qualification <span class="text-destructive">*</span></Label>
                                                <Input
                                                    v-model="qual.other_qualification"
                                                    placeholder="e.g. CPA, CFA, CIPM, CIS"
                                                    class="h-10 bg-background"
                                                    required
                                                />
                                            </div>

                                            <div class="space-y-1.5">
                                                <Label class="text-xs">Class of Degree <span class="text-destructive">*</span></Label>
                                                <Select v-model="qual.class_of_degree">
                                                    <SelectTrigger class="h-10 bg-background"><SelectValue placeholder="Select grade" /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="c in degreeClassOptions" :key="c" :value="c">{{ c }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-1.5">
                                                <Label class="text-xs">Discipline / Course <span class="text-destructive">*</span></Label>
                                                <Input v-model="qual.course_of_study" placeholder="e.g. Law" class="h-10 bg-background" />
                                            </div>

                                            <div class="space-y-1.5">
                                                <Label class="text-xs">CGPA (out of 5.00)</Label>
                                                <Input type="number" step="0.01" min="0" max="5" v-model="qual.cgpa" placeholder="e.g. 4.15" class="h-10 bg-background" />
                                            </div>

                                            <div class="space-y-1.5">
                                                <Label class="text-xs">Year of Graduation <span class="text-destructive">*</span></Label>
                                                <Input type="number" min="1970" max="2030" v-model="qual.graduation_year" placeholder="2022" class="h-10 bg-background" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <Separator />

                            <!-- NYSC & Research Intent -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-base font-semibold text-foreground">National Service & Research Focus</h3>
                                    <p class="text-xs text-muted-foreground mt-0.5">NYSC discharge details and proposed thesis area.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                                    <div class="space-y-1.5">
                                        <Label class="text-xs">NYSC Status <span class="text-destructive">*</span></Label>
                                        <Select v-model="form.nysc_status">
                                            <SelectTrigger class="h-10"><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Completed">Completed (Discharged)</SelectItem>
                                                <SelectItem value="Exempted">Exempted</SelectItem>
                                                <SelectItem value="Excluded">Excluded</SelectItem>
                                                <SelectItem value="Non-Nigerian">International Non-Nigerian</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label class="text-xs">NYSC Certificate / Exemption Number</Label>
                                        <Input v-model="form.nysc_certificate_number" placeholder="Certificate Number" class="h-10" />
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label class="text-xs">Proposed Dissertation / Research Title</Label>
                                        <Input v-model="form.research_proposal_title" placeholder="Brief research or capstone project title" class="h-10" />
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label class="text-xs">Statement of Purpose</Label>
                                        <textarea
                                            v-model="form.statement_of_purpose"
                                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                            placeholder="State your academic motivation and background..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>


                            <Separator />

                            <!-- 3 Referees -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-base font-semibold text-foreground">Three (3) Referees <span class="text-destructive">*</span></h3>
                                    <p class="text-xs text-muted-foreground mt-0.5">Provide 3 referees who will submit online confidential recommendations.</p>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                                    <div
                                        v-for="(referee, idx) in form.referees"
                                        :key="idx"
                                        class="p-4 rounded-xl border border-border/80 bg-muted/10 space-y-3"
                                    >
                                        <span class="text-xs font-semibold text-foreground block">
                                            Referee {{ idx + 1 }} {{ idx === 0 ? '(Academic Supervisor)' : (idx === 1 ? '(Lecturer / HOD)' : '(Academic / Professional)') }}
                                        </span>

                                        <div class="space-y-2.5">
                                            <div class="space-y-1">
                                                <Label class="text-xs">Full Name <span class="text-destructive">*</span></Label>
                                                <Input v-model="referee.name" placeholder="e.g. Prof. J. A. Bello" class="h-9 bg-background text-xs" />
                                            </div>

                                            <div class="space-y-1">
                                                <Label class="text-xs">Email Address <span class="text-destructive">*</span></Label>
                                                <Input type="email" v-model="referee.email" placeholder="bello@university.edu.ng" class="h-9 bg-background text-xs font-mono" />
                                            </div>

                                            <div class="space-y-1">
                                                <Label class="text-xs">Phone Number</Label>
                                                <Input v-model="referee.phone" placeholder="+234..." class="h-9 bg-background text-xs" />
                                            </div>

                                            <div class="space-y-1">
                                                <Label class="text-xs">Institution / Organization <span class="text-destructive">*</span></Label>
                                                <Input v-model="referee.institution_or_organization" placeholder="e.g. University of Lagos" class="h-9 bg-background text-xs" />
                                            </div>

                                            <div class="space-y-1">
                                                <Label class="text-xs">Relationship</Label>
                                                <Input v-model="referee.relationship" placeholder="e.g. Supervisor" class="h-9 bg-background text-xs" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </template>

                        <!-- Undergraduate Academic Details -->
                        <template v-else>
                            <div class="space-y-6 max-w-lg">
                                <div>
                                    <h3 class="text-base font-semibold text-foreground">JAMB Examination Details</h3>
                                    <p class="text-xs text-muted-foreground mt-0.5">Please confirm your official Unified Tertiary Matriculation Examination (UTME) credentials.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label class="text-xs font-medium">JAMB Registration Number <span class="text-destructive">*</span></Label>
                                        <Input
                                            v-model="form.jamb_number"
                                            @input="form.jamb_number = form.jamb_number.toUpperCase()"
                                            placeholder="e.g. 202612345678AB"
                                            class="h-11 font-mono uppercase font-semibold"
                                            required
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label class="text-xs font-medium">Official JAMB Score <span class="text-destructive">*</span></Label>
                                        <Input
                                            type="number"
                                            v-model="form.jamb_score"
                                            min="0"
                                            max="400"
                                            placeholder="e.g. 275"
                                            class="h-11 font-semibold"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                        </template>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 3: Document Uploads                   -->
                    <!-- ========================================== -->
                    <div v-if="currentStep === 3" class="space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-foreground">Supporting Documents</h3>
                            <p class="text-xs text-muted-foreground mt-0.5">Upload clear PDF or image scans (max 2MB each).</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                            <div class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    label="Passport Photograph *"
                                    accept="image/*"
                                    @update:file="(file) => form.passport_photo = file"
                                />
                                <div v-if="getExistingDoc('passport_photo')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('passport_photo')?.original_name }}</span>
                                </div>
                            </div>

                            <div v-if="isPg" class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    label="Academic Transcript *"
                                    accept=".pdf,image/*"
                                    @update:file="(file) => form.academic_transcript = file"
                                />
                                <div v-if="getExistingDoc('academic_transcript')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('academic_transcript')?.original_name }}</span>
                                </div>
                            </div>

                            <div v-if="isPg" class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    label="Degree Certificate (Optional)"
                                    accept=".pdf,image/*"
                                    @update:file="(file) => form.degree_certificate = file"
                                />
                                <div v-if="getExistingDoc('degree_certificate')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('degree_certificate')?.original_name }}</span>
                                </div>
                            </div>

                            <div v-if="isPg" class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    label="NYSC Certificate (Optional)"
                                    accept=".pdf,image/*"
                                    @update:file="(file) => form.nysc_certificate = file"
                                />
                                <div v-if="getExistingDoc('nysc_certificate')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('nysc_certificate')?.original_name }}</span>
                                </div>
                            </div>

                            <div v-if="isPg" class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    label="Curriculum Vitae (CV) (Optional)"
                                    accept=".pdf,.doc,.docx"
                                    @update:file="(file) => form.cv_resume = file"
                                />
                                <div v-if="getExistingDoc('cv_resume')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('cv_resume')?.original_name }}</span>
                                </div>
                            </div>

                            <div v-if="isPg" class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    label="Research Concept Note (Optional)"
                                    accept=".pdf,.doc,.docx"
                                    @update:file="(file) => form.concept_note = file"
                                />
                                <div v-if="getExistingDoc('concept_note')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('concept_note')?.original_name }}</span>
                                </div>
                            </div>

                            <div v-if="isPg && form.has_name_change" class="p-4 rounded-xl border border-border/80 bg-background space-y-2 sm:col-span-2">
                                <FileUploader
                                    label="Marriage Certificate / Change of Name Document *"
                                    accept=".pdf,image/*"
                                    @update:file="(file) => form.name_change_evidence = file"
                                />
                                <div v-if="getExistingDoc('name_change_evidence')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('name_change_evidence')?.original_name }}</span>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl border border-border/80 bg-background space-y-2">
                                <FileUploader
                                    :label="isPg ? 'O\'Level Result (Optional)' : 'O\'Level Result *'"
                                    accept=".pdf,image/*"
                                    @update:file="(file) => form.waec_result = file"
                                />
                                <div v-if="getExistingDoc('waec_result')" class="flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded px-2.5 py-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                    <span class="truncate font-medium">Uploaded: {{ getExistingDoc('waec_result')?.original_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 4: Review & Submit                    -->
                    <!-- ========================================== -->
                    <div v-if="currentStep === 4" class="space-y-6">
                        <div>
                            <h3 class="text-base font-semibold text-foreground">Review Application</h3>
                            <p class="text-xs text-muted-foreground mt-0.5">Please confirm your details before final submission.</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Personal Details Summary -->
                            <div class="p-4 rounded-xl border border-border/80 bg-muted/10 space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Personal Information</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Name</span>
                                        <span class="font-medium text-foreground">{{ form.title }} {{ form.first_name }} {{ form.last_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Date of Birth</span>
                                        <span class="font-medium text-foreground">{{ form.dob }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Phone</span>
                                        <span class="font-medium text-foreground">{{ form.phone }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Next of Kin</span>
                                        <span class="font-medium text-foreground">{{ form.next_of_kin_name }}</span>
                                    </div>
                                    <div class="col-span-2 sm:col-span-4">
                                        <span class="text-muted-foreground block text-[10px]">Address</span>
                                        <span class="font-medium text-foreground">{{ form.address }}</span>
                                    </div>
                                </div>
                            </div>


                            <!-- Programme & Pathway Summary -->
                            <div class="p-4 rounded-xl border border-border/80 bg-muted/10 space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Programme Choice</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Programme</span>
                                        <span class="font-medium text-foreground">{{ programme?.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Mode</span>
                                        <span class="font-medium text-foreground uppercase">{{ form.study_mode }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">Sponsorship</span>
                                        <span class="font-medium text-foreground uppercase">{{ form.sponsorship_type }}</span>
                                    </div>
                                </div>
                            </div>


                            <!-- Qualifications & Referees Summary (PG) -->
                            <div v-if="isPg" class="p-4 rounded-xl border border-border/80 bg-muted/10 space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Qualifications & Referees</h4>
                                <div class="space-y-2 text-xs">
                                    <div v-for="(q, idx) in form.qualifications" :key="idx" class="border-b pb-2 last:border-b-0">
                                        <span class="font-medium text-foreground">{{ (q.qualification_type === 'Other' && q.other_qualification) ? q.other_qualification : q.qualification_type }} in {{ q.course_of_study }}</span>
                                        <span class="text-muted-foreground"> — {{ q.institution_name }} ({{ q.class_of_degree }})</span>
                                    </div>
                                </div>
                                <div class="pt-1">
                                    <span class="text-muted-foreground block text-[10px] mb-1">Referees:</span>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                        <div v-for="(r, i) in form.referees" :key="i" class="p-2 rounded bg-background border text-[11px]">
                                            <span class="font-medium block">{{ r.name }}</span>
                                            <span class="text-muted-foreground font-mono truncate block">{{ r.email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- JAMB Examination Summary (UG) -->
                            <div v-else class="p-4 rounded-xl border border-border/80 bg-muted/10 space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">JAMB Examination Details</h4>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">JAMB Registration Number</span>
                                        <span class="font-mono font-bold text-foreground">{{ form.jamb_number }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground block text-[10px]">JAMB Score</span>
                                        <span class="font-bold text-primary">{{ form.jamb_score }} / 400</span>
                                    </div>
                                </div>
                            </div>


                            <!-- Declaration -->
                            <div class="p-4 rounded-xl bg-muted/30 border border-border/80 text-xs text-muted-foreground leading-relaxed">
                                By clicking <strong>Submit Application</strong>, I confirm that all information provided is accurate and authentic to the best of my knowledge.
                            </div>
                        </div>
                    </div>

                </CardContent>

                <!-- Clean Card Footer -->
                <CardFooter class="flex items-center justify-between border-t border-border/70 p-6 bg-muted/5">
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="prevStep"
                            :disabled="currentStep === 0 || isSavingStep"
                            class="text-xs h-10 px-4"
                        >
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Previous
                        </Button>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="saveStep(true)"
                            :disabled="isSavingStep"
                            class="text-xs h-10 px-3 text-muted-foreground hover:text-foreground border border-dashed border-border"
                        >
                            <Loader2 v-if="isSavingStep" class="w-3.5 h-3.5 mr-1.5 animate-spin" />
                            <Save v-else class="w-3.5 h-3.5 mr-1.5" />
                            Save Draft
                        </Button>
                    </div>

                    <Button
                        v-if="currentStep < steps.length - 1"
                        size="sm"
                        @click="nextStep"
                        :disabled="!stepIsValid || isSavingStep"
                        class="text-xs h-10 px-5"
                    >
                        Continue
                        <ChevronRight class="w-4 h-4 ml-1" />
                    </Button>

                    <Button
                        v-else
                        size="sm"
                        @click="submitApplication"
                        :disabled="form.processing || isSavingStep"
                        class="text-xs h-10 px-6 font-semibold"
                    >
                        <Send class="w-4 h-4 mr-2" />
                        Submit Application
                    </Button>
                </CardFooter>
            </Card>

        </div>
    </ApplicantLayout>
</template>
