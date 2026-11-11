<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import { 
    FileText, 
    Download, 
    CheckCircle2, 
    GraduationCap, 
    Building2, 
    UserCheck, 
    Mail, 
    Phone, 
    MapPin, 
    Calendar, 
    AlertCircle, 
    Copy, 
    Check, 
    ArrowLeft, 
    Eye,
    FileCheck,
    CreditCard,
    Award
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    applicant: {
        id: string;
        user_id?: string;
        title?: string;
        first_name?: string;
        last_name?: string;
        other_names?: string;
        jamb_registration_number?: string;
        jamb_score?: string | number | null;
        application_number?: string;
        status: string;
        created_at: string;
        updated_at?: string;
        application_mode: string;
        study_mode?: string;
        sponsorship_type?: string;
        sponsor_name?: string;
        program_choice_1?: string;
        scholarship_id?: string;
        admitted_level?: string;
        admitted_programme_id?: string;
        
        phone?: string;
        address?: string;
        dob?: string;
        gender?: string;
        marital_status?: string;
        nationality?: string;
        has_name_change?: boolean;
        previous_full_name?: string;
        
        previous_institution?: string;
        previous_qualification?: string;
        previous_course_of_study?: string;
        previous_class_of_degree?: string;
        previous_cgpa?: number | string | null;
        graduation_year?: number | string | null;
        
        user: { name: string; email: string; avatar_url?: string };
        state?: { id: number; name: string };
        lga?: { id: number; name: string };
        programme?: { 
            name: string; 
            type?: string;
            academic_stream?: string;
            department?: { 
                name?: string;
                faculty?: { name: string } 
            } 
        };
        
        next_of_kin_name?: string;
        next_of_kin_phone?: string;
        next_of_kin_relationship?: string;
        
        documents: Array<{
            id: string;
            type: string;
            original_name: string;
            path: string;
        }>;
        qualifications?: Array<{
            id: string;
            institution_name: string;
            qualification_type: string;
            course_of_study: string;
            class_of_degree: string;
            cgpa?: string | number | null;
            scale_max?: string | number | null;
            graduation_year?: number | string | null;
        }>;
    };
    payment_info?: {
        amount: number;
        status: string;
        reference: string;
    };
    is_paid?: boolean;
    programmes: Array<{ id: string; name: string }>;
    scholarships: Array<{ id: string; name: string }>;
    user_permissions?: {
        can_view_ug?: boolean;
        can_view_pg?: boolean;
        can_review?: boolean;
        can_admit?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Applicants', href: '/admin/admissions' },
    { title: 'Undergraduate', href: route().has('admin.admissions.ug.index') ? route('admin.admissions.ug.index') : '/admin/admissions/ug' },
    { title: props.applicant.user.name, href: '#' },
];

const form = useForm({
    status: props.applicant.status,
    admitted_level: props.applicant.admitted_level ?? '100',
    admitted_programme_id: props.applicant.admitted_programme_id ?? props.applicant.program_choice_1 ?? '',
    scholarship_id: props.applicant.scholarship_id ?? '',
});

const isLocked = computed(() => ['admitted', 'enrolled'].includes(props.applicant.status));
const isEnrolled = computed(() => props.applicant.status === 'enrolled');
const isAdmitting = computed(() => form.status === 'admitted');

const updateStatus = () => {
    const targetRoute = route().has('admin.admissions.ug.update')
        ? route('admin.admissions.ug.update', props.applicant.id)
        : route('admin.admissions.update', props.applicant.id);

    form.put(targetRoute, {
        preserveScroll: true,
    });
};

const passportFailed = ref(false);
const passportUrl = computed(() => {
    const doc = props.applicant.documents.find(d => d.type === 'passport_photo');
    return doc?.path ? `/storage/${doc.path}` : null;
});

const applicantFullName = computed(() => {
    const title = props.applicant.title ? `${props.applicant.title} ` : '';
    const first = props.applicant.first_name || '';
    const other = props.applicant.other_names ? ` ${props.applicant.other_names}` : '';
    const last = props.applicant.last_name || '';
    const combined = `${title}${first}${other} ${last}`.trim();
    return combined.length > 0 ? combined : props.applicant.user.name;
});

const candidateInitials = computed(() => {
    const f = props.applicant.first_name?.[0] || props.applicant.user.name?.[0] || 'U';
    const l = props.applicant.last_name?.[0] || 'G';
    return (f + l).toUpperCase();
});

const applicantAge = computed(() => {
    if (!props.applicant.dob) return null;
    try {
        const birthDate = new Date(props.applicant.dob);
        const diff = Date.now() - birthDate.getTime();
        const ageDate = new Date(diff);
        return Math.abs(ageDate.getUTCFullYear() - 1970);
    } catch {
        return null;
    }
});

const copiedJamb = ref(false);
const copyJambNumber = async () => {
    if (!props.applicant.jamb_registration_number) return;
    try {
        await navigator.clipboard.writeText(props.applicant.jamb_registration_number);
        copiedJamb.value = true;
        setTimeout(() => { copiedJamb.value = false; }, 2000);
    } catch {
        // fallback
    }
};

const getDocumentLabel = (type: string) => {
    const labels: Record<string, string> = {
        passport_photo: 'Passport Photograph',
        waec_result: 'O\'Level Result (WAEC / NECO / NABTEB)',
        jamb_result: 'Official JAMB UTME Result Slip',
        direct_entry_diploma: 'Direct Entry Diploma / IJMB / JUPEB',
        state_of_origin: 'Certificate of State of Origin',
        birth_certificate: 'Birth Certificate / Age Declaration',
    };
    return labels[type] || type.replace(/_/g, ' ').toUpperCase();
};
</script>

<template>
    <Head :title="`UG Application: ${applicantFullName}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            
            <!-- Top Bar Nav & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border/60">
                <Link :href="route().has('admin.admissions.ug.index') ? route('admin.admissions.ug.index') : '/admin/admissions/ug'">
                    <Button variant="ghost" size="sm" class="gap-2 text-xs text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="w-4 h-4" />
                        Back to Undergraduate Applicants
                    </Button>
                </Link>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="outline" class="bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800 text-xs font-semibold px-2.5 py-1">
                        Application: {{ applicant.application_number || 'PENDING' }}
                    </Badge>

                    <a 
                        v-if="['admitted', 'enrolled'].includes(applicant.status)" 
                        :href="route('admin.admissions.letter', applicant.id)" 
                        target="_blank"
                    >
                        <Button size="sm" class="h-8 gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                            <Download class="w-3.5 h-3.5" />
                            Download Admission Letter
                        </Button>
                    </a>
                </div>
            </div>

            <!-- 2-Column Responsive Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- ============================================== -->
                <!-- LEFT COLUMN: Profile, Actions & Assessment      -->
                <!-- ============================================== -->
                <div class="space-y-6">
                    
                    <!-- Candidate Identity & Status Card -->
                    <Card class="overflow-hidden border border-border/80 shadow-sm">
                        <div class="bg-gradient-to-r from-blue-600/20 via-sky-600/20 to-primary/20 h-24"></div>
                        <div class="px-6 pb-6 -mt-12 text-center">
                            <!-- Passport Photo Frame -->
                            <div class="relative w-28 h-28 mx-auto rounded-2xl border-4 border-background bg-muted flex items-center justify-center overflow-hidden shadow-md">
                                <img 
                                    v-if="passportUrl && !passportFailed" 
                                    :src="passportUrl" 
                                    @error="passportFailed = true"
                                    class="object-cover w-full h-full"
                                    :alt="applicantFullName"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-muted-foreground p-2 text-center">
                                    <span class="text-2xl font-bold font-serif text-primary">{{ candidateInitials }}</span>
                                    <span class="text-[9px] uppercase tracking-wider text-muted-foreground mt-0.5">Photo</span>
                                </div>
                            </div>

                            <h3 class="mt-3 text-lg font-bold tracking-tight text-foreground leading-tight">
                                {{ applicantFullName }}
                            </h3>
                            <p class="text-xs text-muted-foreground font-mono mt-0.5">{{ applicant.user.email }}</p>
                            <p v-if="applicant.phone" class="text-xs text-muted-foreground mt-0.5 font-mono">{{ applicant.phone }}</p>

                            <div class="mt-2.5 flex items-center justify-center gap-1.5 flex-wrap">
                                <Badge variant="secondary" class="text-[10px] uppercase font-semibold">
                                    {{ applicant.application_mode || 'UTME' }}
                                </Badge>
                                <Badge 
                                    :class="{
                                        'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200': applicant.status === 'draft',
                                        'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200': applicant.status === 'submitted',
                                        'bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200': applicant.status === 'screening',
                                        'bg-emerald-600 text-white': applicant.status === 'admitted',
                                        'bg-emerald-700 text-white': applicant.status === 'enrolled',
                                        'bg-destructive text-destructive-foreground': applicant.status === 'rejected',
                                    }" 
                                    class="capitalize text-[10px] font-semibold"
                                >
                                    {{ applicant.status }}
                                </Badge>
                            </div>
                        </div>

                        <Separator />

                        <!-- Admission Verdict Panel -->
                        <CardContent class="p-6 text-left space-y-4 bg-muted/5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Screening & Admission Verdict</h4>

                            <!-- Locked Status (Enrolled or Admitted) -->
                            <div v-if="isLocked" class="space-y-4">
                                <div 
                                    :class="isEnrolled 
                                        ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-900 dark:text-emerald-200' 
                                        : 'bg-blue-500/10 border-blue-500/20 text-blue-900 dark:text-blue-200'"
                                    class="p-4 rounded-xl border space-y-2.5"
                                >
                                    <div class="flex items-center gap-2 font-semibold text-xs">
                                        <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" v-if="isEnrolled" />
                                        <Award class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" v-else />
                                        <span>{{ isEnrolled ? 'Candidate Enrolled & Matriculated' : 'Provisional Admission Granted' }}</span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed text-muted-foreground">
                                        {{ isEnrolled 
                                            ? 'This candidate has officially accepted admission and completed enrolment. The candidate record is permanently locked and cannot be updated.' 
                                            : 'Provisional admission offer has been extended. Admission status is locked.' }}
                                    </p>

                                    <div class="pt-2 border-t border-border/60 flex flex-col gap-1.5 text-xs">
                                        <div class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Admitted Level:</span>
                                            <span class="font-semibold">{{ applicant.admitted_level ?? '100' }} Level</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Admitted Programme:</span>
                                            <span class="font-semibold text-right max-w-[170px] truncate">
                                                {{ programmes.find(p => p.id === applicant.admitted_programme_id)?.name ?? applicant.programme?.name ?? 'Assigned Programme' }}
                                            </span>
                                        </div>
                                        <div v-if="applicant.scholarship_id" class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Scholarship:</span>
                                            <span class="font-semibold text-right max-w-[170px] truncate">
                                                {{ scholarships.find(s => s.id === applicant.scholarship_id)?.name || 'Awarded' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <a 
                                    :href="route('admin.admissions.letter', applicant.id)" 
                                    target="_blank"
                                    class="block"
                                >
                                    <Button variant="outline" class="w-full h-10 text-xs font-semibold gap-1.5 border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30">
                                        <Download class="w-4 h-4" />
                                        Download Admission Letter
                                    </Button>
                                </a>
                            </div>

                            <!-- Editable Verdict Form (Only when NOT locked) -->
                            <template v-else>
                                <div v-if="!is_paid && applicant.status !== 'admitted'" class="p-3 text-xs bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20 rounded-xl space-y-1">
                                    <span class="font-bold flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5 shrink-0" /> Application Fee Unpaid
                                    </span>
                                    <p class="text-[11px] leading-relaxed">
                                        Undergraduate application fee has not been settled. Admission cannot be granted until payment is confirmed.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="status" class="text-xs font-medium">Application Status</Label>
                                    <Select v-model="form.status" :disabled="user_permissions && !user_permissions.can_review">
                                        <SelectTrigger id="status" class="w-full h-10 bg-background">
                                            <SelectValue placeholder="Change Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">Draft (Incomplete)</SelectItem>
                                            <SelectItem value="submitted">Submitted (Under Review)</SelectItem>
                                            <SelectItem value="screening">Under Screening</SelectItem>
                                            <SelectItem 
                                                value="admitted" 
                                                :disabled="(user_permissions && !user_permissions.can_admit) || !is_paid"
                                            >
                                                Admitted {{ !is_paid ? '(Application fee unpaid)' : (user_permissions && !user_permissions.can_admit ? '(Requires admit_students)' : '') }}
                                            </SelectItem>
                                            <SelectItem value="rejected">Reject Application</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Admission Details when Admitting -->
                                <template v-if="isAdmitting">
                                    <div class="space-y-4 pt-2 border-t border-border/70">
                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-medium">Level Admitted <span class="text-destructive">*</span></Label>
                                            <Select v-model="form.admitted_level">
                                                <SelectTrigger class="w-full h-10 bg-background">
                                                    <SelectValue placeholder="Select Level" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="100">100 Level</SelectItem>
                                                    <SelectItem value="200">200 Level (Direct Entry)</SelectItem>
                                                    <SelectItem value="300">300 Level (Transfer)</SelectItem>
                                                    <SelectItem value="400">400 Level</SelectItem>
                                                    <SelectItem value="500">500 Level</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label class="text-xs font-medium">Programme Admitted <span class="text-destructive">*</span></Label>
                                            <Select v-model="form.admitted_programme_id">
                                                <SelectTrigger class="w-full h-10 bg-background">
                                                    <SelectValue placeholder="Select Programme" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="prog in programmes" :key="prog.id" :value="prog.id">
                                                        {{ prog.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div v-if="scholarships && scholarships.length > 0" class="space-y-1.5">
                                            <Label class="text-xs font-medium">Scholarship Award (Optional)</Label>
                                            <Select v-model="form.scholarship_id">
                                                <SelectTrigger class="w-full h-10 bg-background">
                                                    <SelectValue placeholder="Select Scholarship" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">No Scholarship</SelectItem>
                                                    <SelectItem v-for="s in scholarships" :key="s.id" :value="s.id">
                                                        {{ s.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </template>

                                <Button 
                                    @click="updateStatus" 
                                    :disabled="form.processing || (user_permissions && !user_permissions.can_review) || (isAdmitting && !is_paid)" 
                                    class="w-full h-10 text-xs font-semibold"
                                >
                                    <span v-if="form.processing">Updating...</span>
                                    <span v-else>Update Verdict</span>
                                </Button>
                            </template>
                        </CardContent>
                    </Card>

                    <!-- Application Fee Payment Card -->
                    <Card v-if="payment_info" class="shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-bold text-foreground">Application Fee Payment</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-xs">
                            <div class="flex justify-between items-center border-b border-border/60 pb-2">
                                <span class="text-muted-foreground">Amount</span>
                                <span class="font-bold text-foreground">₦{{ Number(payment_info.amount).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-border/60 pb-2">
                                <span class="text-muted-foreground">Status</span>
                                <Badge :variant="payment_info.status === 'paid' ? 'default' : 'destructive'" class="capitalize text-xs">
                                    {{ payment_info.status }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Reference</span>
                                <span class="font-mono text-[11px] text-foreground font-semibold">{{ payment_info.reference }}</span>
                            </div>
                        </CardContent>
                    </Card>

                </div>

                <!-- ============================================== -->
                <!-- RIGHT COLUMN: Full Submitted Candidate Application -->
                <!-- ============================================== -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Section 1: Academic Profile & JAMB Details -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                        <GraduationCap class="w-4 h-4 text-primary" />
                                        Academic Profile & JAMB Examination Details
                                    </CardTitle>
                                    <CardDescription class="text-xs">Unified Tertiary Matriculation Examination (UTME) verification</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs uppercase font-bold text-primary bg-primary/5">
                                    {{ applicant.application_mode || 'UTME' }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-5 text-xs">
                            <!-- Main Programme Box -->
                            <div class="p-4 rounded-xl bg-blue-500/5 border border-blue-200/50 dark:border-blue-800/50 space-y-1">
                                <h4 class="text-base font-bold text-foreground">{{ applicant.programme?.name || 'Programme Not Selected' }}</h4>
                                <p class="text-xs text-muted-foreground">
                                    {{ applicant.programme?.department?.name }} • {{ applicant.programme?.department?.faculty?.name || 'Faculty' }}
                                </p>
                            </div>

                            <!-- JAMB Verification Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- JAMB Reg Number Card -->
                                <div class="p-3.5 rounded-xl border border-border/70 bg-muted/10 space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">JAMB Registration Number</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-sm text-foreground">
                                            {{ applicant.jamb_registration_number || 'NOT PROVIDED' }}
                                        </span>
                                        <button
                                            v-if="applicant.jamb_registration_number"
                                            type="button"
                                            @click="copyJambNumber"
                                            class="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground"
                                            title="Copy JAMB Number"
                                        >
                                            <Check v-if="copiedJamb" class="w-3.5 h-3.5 text-emerald-600" />
                                            <Copy v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Official JAMB Score Card -->
                                <div class="p-3.5 rounded-xl border border-border/70 bg-muted/10 space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Official JAMB Score</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl font-bold font-mono text-primary">
                                            {{ applicant.jamb_score || '—' }}
                                        </span>
                                        <Badge 
                                            v-if="applicant.jamb_score"
                                            :variant="Number(applicant.jamb_score) >= 200 ? 'default' : 'secondary'"
                                            class="text-[10px]"
                                        >
                                            {{ Number(applicant.jamb_score) >= 200 ? 'Eligible' : 'Below 200' }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Application Number Card -->
                                <div class="p-3.5 rounded-xl border border-border/70 bg-muted/10 space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">University Application Number</span>
                                    <span class="font-mono font-bold text-sm text-foreground">
                                        {{ applicant.application_number || 'PENDING' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Additional Study Route Details -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-1">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Entry Mode</span>
                                    <span class="font-bold text-foreground">{{ applicant.application_mode }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Mode of Study</span>
                                    <span class="font-bold text-foreground uppercase">{{ applicant.study_mode || 'Full-Time' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Sponsorship</span>
                                    <span class="font-bold text-foreground capitalize">{{ applicant.sponsorship_type || 'Self' }}</span>
                                    <span v-if="applicant.sponsor_name" class="block text-[10px] text-muted-foreground">({{ applicant.sponsor_name }})</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Application Date</span>
                                    <span class="font-bold text-foreground">{{ applicant.created_at ? new Date(applicant.created_at).toLocaleDateString() : '—' }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 2: Personal Information & Contact Details -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                <UserCheck class="w-4 h-4 text-primary" />
                                Personal Information & Biodata
                            </CardTitle>
                            <CardDescription class="text-xs">Candidate's verified personal profile, legal identity, and home address</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-4 text-xs">
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Title</span>
                                    <span class="font-semibold text-foreground">{{ applicant.title || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">First Name</span>
                                    <span class="font-semibold text-foreground">{{ applicant.first_name || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Last Name</span>
                                    <span class="font-semibold text-foreground">{{ applicant.last_name || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Other Names</span>
                                    <span class="font-semibold text-foreground">{{ applicant.other_names || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Date of Birth</span>
                                    <span class="font-semibold text-foreground">
                                        {{ applicant.dob || '—' }}
                                        <span v-if="applicantAge" class="text-muted-foreground font-normal">({{ applicantAge }} yrs)</span>
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Gender</span>
                                    <span class="font-semibold text-foreground capitalize">{{ applicant.gender || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Marital Status</span>
                                    <span class="font-semibold text-foreground capitalize">{{ applicant.marital_status || 'Single' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Nationality</span>
                                    <span class="font-semibold text-foreground">{{ applicant.nationality || 'Nigerian' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Phone Number</span>
                                    <span class="font-semibold text-foreground font-mono">{{ applicant.phone || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Email Address</span>
                                    <span class="font-semibold text-foreground font-mono">{{ applicant.user.email }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">State of Origin</span>
                                    <span class="font-semibold text-foreground">{{ applicant.state?.name || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">L.G.A</span>
                                    <span class="font-semibold text-foreground">{{ applicant.lga?.name || '—' }}</span>
                                </div>

                                <div class="col-span-2 space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Residential Address</span>
                                    <span class="font-semibold text-foreground">{{ applicant.address || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Next of Kin</span>
                                    <span class="font-semibold text-foreground">{{ applicant.next_of_kin_name || '—' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Next of Kin Phone / Rel.</span>
                                    <span class="font-semibold text-foreground">
                                        {{ applicant.next_of_kin_phone || '—' }}
                                        <span v-if="applicant.next_of_kin_relationship" class="text-muted-foreground font-normal">({{ applicant.next_of_kin_relationship }})</span>
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 3: Prior Academic History (Direct Entry / Transfer / Diplomas if applicable) -->
                    <Card v-if="applicant.previous_institution || applicant.previous_qualification || (applicant.qualifications && applicant.qualifications.length > 0)" class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-primary" />
                                Prior Academic History (Direct Entry / Transfer)
                            </CardTitle>
                            <CardDescription class="text-xs">Prior diploma, IJMB, or tertiary coursework evaluated for entry</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4 text-xs space-y-3">
                            <div v-if="applicant.qualifications && applicant.qualifications.length > 0" class="space-y-2">
                                <div 
                                    v-for="qual in applicant.qualifications" 
                                    :key="qual.id"
                                    class="p-3.5 rounded-xl border border-border/70 bg-muted/10 flex justify-between items-center"
                                >
                                    <div>
                                        <p class="font-bold text-foreground">{{ qual.qualification_type }} in {{ qual.course_of_study }}</p>
                                        <p class="text-muted-foreground text-[11px]">{{ qual.institution_name }} ({{ qual.graduation_year }})</p>
                                    </div>
                                    <div class="text-right">
                                        <Badge variant="outline">{{ qual.class_of_degree }}</Badge>
                                        <p v-if="qual.cgpa" class="font-mono text-primary font-bold mt-0.5">CGPA: {{ qual.cgpa }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Previous Institution</span>
                                    <span class="font-semibold text-foreground">{{ applicant.previous_institution || '—' }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Previous Qualification</span>
                                    <span class="font-semibold text-foreground">{{ applicant.previous_qualification || '—' }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Grade / Class</span>
                                    <span class="font-semibold text-foreground">{{ applicant.previous_class_of_degree || '—' }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 4: Submitted Documents & Credential Gallery -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                        <FileText class="w-4 h-4 text-primary" />
                                        Submitted Documents & Credential Uploads
                                    </CardTitle>
                                    <CardDescription class="text-xs">Original O'Level, JAMB slip, certificates, and ID documents</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs">
                                    {{ applicant.documents?.length || 0 }} File(s)
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div v-if="!applicant.documents || applicant.documents.length === 0" class="text-center py-8 text-xs text-muted-foreground italic">
                                No documents uploaded by applicant.
                            </div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div 
                                    v-for="doc in applicant.documents" 
                                    :key="doc.id" 
                                    class="p-3.5 border border-border/70 rounded-xl flex items-center justify-between gap-3 bg-muted/10 hover:bg-muted/20 transition"
                                >
                                    <div class="overflow-hidden space-y-0.5">
                                        <div class="flex items-center gap-1.5 text-primary">
                                            <FileCheck class="w-3.5 h-3.5 shrink-0" />
                                            <p class="font-semibold text-xs truncate">
                                                {{ getDocumentLabel(doc.type) }}
                                            </p>
                                        </div>
                                        <p class="text-[11px] text-muted-foreground truncate" :title="doc.original_name">
                                            {{ doc.original_name }}
                                        </p>
                                    </div>
                                    <a :href="`/admin/documents/${doc.id}`" target="_blank" class="shrink-0">
                                        <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs">
                                            <Eye class="w-3.5 h-3.5" />
                                            View
                                        </Button>
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>

            </div>

        </div>
    </AdminLayout>
</template>
