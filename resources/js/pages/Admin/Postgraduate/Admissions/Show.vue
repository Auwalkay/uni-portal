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
    XCircle, 
    GraduationCap, 
    Building2, 
    Briefcase, 
    Award,
    ShieldAlert,
    ArrowLeft,
    UserCheck,
    Mail,
    Phone,
    MapPin,
    Calendar,
    Copy,
    Check,
    ExternalLink,
    FileCheck,
    ScrollText,
    Star,
    AlertCircle,
    Clock,
    FileSpreadsheet,
    Eye
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    applicant: {
        id: string;
        user_id?: string;
        title?: string;
        first_name?: string;
        last_name?: string;
        other_names?: string;
        jamb_registration_number?: string;
        application_number?: string;
        status: string;
        created_at: string;
        updated_at?: string;
        application_mode: string;
        study_mode?: string;
        area_of_specialization?: string;
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
        nysc_status?: string | null;
        nysc_certificate_number?: string | null;
        statement_of_purpose?: string | null;
        research_proposal_title?: string | null;
        work_experience_years?: number | string | null;
        official_transcript_status?: string | null;
        
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
        referees?: Array<{
            id: string;
            name: string;
            email: string;
            phone?: string | null;
            institution_or_organization: string;
            position_or_rank?: string | null;
            relationship?: string | null;
            status: string;
            token: string;
            academic_capacity_rating?: number | null;
            research_potential_rating?: number | null;
            character_integrity_rating?: number | null;
            communication_rating?: number | null;
            recommendation_strength?: string | null;
            comments?: string | null;
            submitted_at?: string | null;
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
            certificate_number?: string | null;
            is_highest_qualification?: boolean;
        }>;
        employments?: Array<{
            id: string;
            employer_name: string;
            job_title?: string;
            position_title?: string;
            department_or_unit?: string | null;
            start_date?: string | null;
            end_date?: string | null;
            is_current?: boolean;
            responsibilities?: string | null;
            job_description?: string | null;
        }>;
    };
    pg_eligibility?: {
        eligible: boolean;
        tier: string;
        default_level: number;
        message: string;
    } | null;
    pg_tier?: string | null;
    default_level?: number;
    payment_info?: {
        amount: number;
        status: string;
        reference: string;
    };
    is_paid?: boolean;
    programmes: Array<{ id: string; name: string; type?: string }>;
    scholarships: Array<{ id: string; name: string }>;
    user_permissions?: {
        can_view_ug?: boolean;
        can_view_pg?: boolean;
        can_review?: boolean;
        can_admit?: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applicants', href: '/admin/admissions' },
    { title: 'Postgraduate (SPGS)', href: route().has('admin.admissions.pg.index') ? route('admin.admissions.pg.index') : '/admin/admissions/pg' },
    { title: props.applicant.user.name, href: '#' },
];

const form = useForm({
    status: props.applicant.status,
    admitted_level: props.applicant.admitted_level ?? String(props.default_level ?? 700),
    admitted_programme_id: props.applicant.admitted_programme_id ?? props.applicant.program_choice_1 ?? '',
    scholarship_id: props.applicant.scholarship_id ?? '',
});

const isLocked = computed(() => ['admitted', 'enrolled'].includes(props.applicant.status));
const isEnrolled = computed(() => props.applicant.status === 'enrolled');
const isAdmitting = computed(() => form.status === 'admitted');

const updateStatus = () => {
    const targetRoute = route().has('admin.admissions.pg.update')
        ? route('admin.admissions.pg.update', props.applicant.id)
        : `/admin/admissions/pg/${props.applicant.id}`;

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
    const f = props.applicant.first_name?.[0] || props.applicant.user.name?.[0] || 'P';
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

const submittedRefereesCount = computed(() => {
    return (props.applicant.referees || []).filter(r => r.status === 'submitted').length;
});

const copiedRefereeToken = ref<string | null>(null);
const copyRefereeLink = async (token: string) => {
    try {
        const url = route('referee.recommendation.show', token);
        await navigator.clipboard.writeText(url);
        copiedRefereeToken.value = token;
        setTimeout(() => { copiedRefereeToken.value = null; }, 2000);
    } catch {
        // fallback
    }
};

const getDocumentLabel = (type: string) => {
    const labels: Record<string, string> = {
        passport_photo: 'Passport Photograph',
        academic_transcript: 'Official Academic Transcript',
        degree_certificate: 'Degree Certificate / Statement of Result',
        nysc_certificate: 'NYSC Certificate / Exemption Letter',
        cv_resume: 'Curriculum Vitae (CV / Resume)',
        concept_note: 'Research Concept Note / Proposal',
        name_change_evidence: 'Marriage Certificate / Evidence of Name Change',
        waec_result: 'O\'Level Result (WAEC / NECO)',
    };
    return labels[type] || type.replace(/_/g, ' ').toUpperCase();
};
</script>

<template>
    <Head :title="`SPGS Application: ${applicantFullName}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            
            <!-- Top Bar Nav & Header Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border/60">
                <Link :href="route().has('admin.admissions.pg.index') ? route('admin.admissions.pg.index') : '/admin/admissions/pg'">
                    <Button variant="ghost" size="sm" class="gap-2 text-xs text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="w-4 h-4" />
                        Back to PG Applicants
                    </Button>
                </Link>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="outline" class="bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800 text-xs font-semibold px-2.5 py-1">
                        SPGS Candidate: {{ applicant.application_number || applicant.jamb_registration_number || 'PG' }}
                    </Badge>

                    <a 
                        :href="route().has('admin.admissions.pg.transcript_label') ? route('admin.admissions.pg.transcript_label', applicant.id) : `/admin/admissions/pg/${applicant.id}/transcript-label`" 
                        target="_blank"
                    >
                        <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/30">
                            <Download class="w-3.5 h-3.5" />
                            SPGS Transcript Cover Sheet
                        </Button>
                    </a>

                    <a 
                        v-if="['admitted', 'enrolled'].includes(applicant.status)" 
                        :href="route().has('admin.admissions.pg.letter') ? route('admin.admissions.pg.letter', applicant.id) : `/admin/admissions/pg/${applicant.id}/letter`" 
                        target="_blank"
                    >
                        <Button size="sm" class="h-8 gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">
                            <Download class="w-3.5 h-3.5" />
                            Download Admission Letter
                        </Button>
                    </a>
                </div>
            </div>

            <!-- 2-Column Executive Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- ============================================== -->
                <!-- LEFT COLUMN: Profile, Actions & Assessment      -->
                <!-- ============================================== -->
                <div class="space-y-6">
                    
                    <!-- Candidate Identity & Verdict Card -->
                    <Card class="overflow-hidden border border-border/80 shadow-sm">
                        <div class="bg-gradient-to-r from-purple-600/20 via-indigo-600/20 to-primary/20 h-24"></div>
                        <div class="px-6 pb-6 -mt-12 text-center">
                            <!-- Passport Photograph Frame -->
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
                                    {{ applicant.programme?.type || 'POSTGRADUATE' }}
                                </Badge>
                                <Badge 
                                    :class="applicant.status === 'enrolled' 
                                        ? 'bg-emerald-700 text-white' 
                                        : (applicant.status === 'admitted' 
                                            ? 'bg-emerald-600 text-white' 
                                            : (applicant.status === 'screening' ? 'bg-blue-600 text-white' : 'bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800'))" 
                                    class="capitalize text-[10px] font-semibold"
                                >
                                    {{ applicant.status }}
                                </Badge>
                            </div>
                        </div>

                        <Separator />

                        <!-- Screening & Admission Verdict Form -->
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
                                            ? 'This candidate has officially accepted admission and completed enrolment. The candidate record is permanently locked and cannot be modified.' 
                                            : 'Provisional admission offer has been extended. Admission status is locked.' }}
                                    </p>

                                    <div class="pt-2 border-t border-border/60 flex flex-col gap-1.5 text-xs">
                                        <div class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Admitted Level:</span>
                                            <span class="font-semibold">{{ applicant.admitted_level || default_level || 700 }}L</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-muted-foreground">Admitted Programme:</span>
                                            <span class="font-semibold text-right max-w-[170px] truncate">
                                                {{ programmes.find(p => p.id === applicant.admitted_programme_id)?.name || applicant.programme?.name || 'Assigned Programme' }}
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
                                    :href="route().has('admin.admissions.pg.letter') ? route('admin.admissions.pg.letter', applicant.id) : `/admin/admissions/pg/${applicant.id}/letter`" 
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
                                        <AlertCircle class="w-3.5 h-3.5 shrink-0" /> Fee Unpaid
                                    </span>
                                    <p class="text-[11px] leading-relaxed">
                                        Postgraduate application & enrolment fee has not been paid. Admission cannot be granted.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="status" class="text-xs font-medium">Application Status</Label>
                                    <Select v-model="form.status" :disabled="!user_permissions?.can_review">
                                        <SelectTrigger id="status" class="w-full h-10 bg-background">
                                            <SelectValue placeholder="Select Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="submitted">Submitted (Under Review)</SelectItem>
                                            <SelectItem value="screening">In SPGS Screening</SelectItem>
                                            <SelectItem 
                                                value="admitted" 
                                                :disabled="!user_permissions?.can_admit || !is_paid"
                                            >
                                                Admitted {{ !is_paid ? '(Application fee unpaid)' : (!user_permissions?.can_admit ? '(Requires admit_pg_students)' : '') }}
                                            </SelectItem>
                                            <SelectItem value="rejected">Rejected / Ineligible</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Admitted Extra Fields -->
                                <div v-if="isAdmitting" class="space-y-4 pt-2 border-t border-border/70">
                                    <div class="space-y-2">
                                        <Label for="admitted_level" class="text-xs font-medium">Admitted Level (PG Tier)</Label>
                                        <Select v-model="form.admitted_level" :disabled="!user_permissions?.can_admit">
                                            <SelectTrigger id="admitted_level" class="w-full h-10 bg-background">
                                                <SelectValue placeholder="Select Level" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="700">700 Level (PGD)</SelectItem>
                                                <SelectItem value="800">800 Level (Masters / M.Phil)</SelectItem>
                                                <SelectItem value="900">900 Level (Doctorate - Ph.D)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="admitted_programme_id" class="text-xs font-medium">Admitted Programme</Label>
                                        <Select v-model="form.admitted_programme_id" :disabled="!user_permissions?.can_admit">
                                            <SelectTrigger id="admitted_programme_id" class="w-full h-10 bg-background">
                                                <SelectValue placeholder="Select Programme" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="p in programmes" :key="p.id" :value="p.id">
                                                    {{ p.name }} ({{ p.type || 'PG' }})
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div v-if="scholarships && scholarships.length > 0" class="space-y-2">
                                        <Label for="scholarship_id" class="text-xs font-medium">Scholarship Award (Optional)</Label>
                                        <Select v-model="form.scholarship_id" :disabled="!user_permissions?.can_admit">
                                            <SelectTrigger id="scholarship_id" class="w-full h-10 bg-background">
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

                                <Button 
                                    @click="updateStatus" 
                                    :disabled="form.processing || !user_permissions?.can_review || (form.status === 'admitted' && !is_paid)" 
                                    class="w-full h-10 text-xs font-semibold"
                                >
                                    <span v-if="form.processing">Updating...</span>
                                    <span v-else>Update Verdict</span>
                                </Button>
                            </template>
                        </CardContent>
                    </Card>

                    <!-- SPGS Automated Eligibility Assessment -->
                    <Card v-if="pg_eligibility" class="border-purple-200 dark:border-purple-800 shadow-sm">
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-sm font-bold flex items-center gap-1.5 text-foreground">
                                    <Award class="w-4 h-4 text-purple-600" />
                                    SPGS Eligibility Assessment
                                </CardTitle>
                                <Badge :variant="pg_eligibility.eligible ? 'default' : 'destructive'" class="text-xs">
                                    {{ pg_eligibility.eligible ? 'Qualified' : 'Requires Waiver' }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="text-xs space-y-2.5 pt-2">
                            <p class="text-muted-foreground leading-relaxed">{{ pg_eligibility.message }}</p>
                            <div v-if="pg_eligibility.default_level" class="text-foreground font-semibold flex items-center gap-1.5 pt-1 border-t border-border/60">
                                <span>Recommended Placement:</span>
                                <span class="font-mono text-purple-600 dark:text-purple-400 font-bold">
                                    {{ pg_eligibility.default_level }}L ({{ pg_eligibility.tier }})
                                </span>
                            </div>
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
                                <span class="text-muted-foreground">Payment Status</span>
                                <Badge :variant="payment_info.status === 'paid' ? 'default' : 'destructive'" class="capitalize text-xs">
                                    {{ payment_info.status }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Invoice Reference</span>
                                <span class="font-mono text-[11px] text-foreground font-semibold">{{ payment_info.reference }}</span>
                            </div>
                        </CardContent>
                    </Card>

                </div>

                <!-- ============================================== -->
                <!-- RIGHT COLUMN: Everything the Applicant Submitted -->
                <!-- ============================================== -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Section 1: Prospective Programme & Academic Pathway -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground">Prospective Programme & Academic Pathway</CardTitle>
                                    <CardDescription class="text-xs">Applied postgraduate degree track and specialization</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs uppercase font-bold text-primary bg-primary/5">
                                    {{ applicant.programme?.type || 'PG' }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-5 text-xs">
                            <!-- Main Programme Box -->
                            <div class="p-4 rounded-xl bg-purple-500/5 border border-purple-200/50 dark:border-purple-800/50 space-y-1">
                                <h4 class="text-base font-bold text-foreground">{{ applicant.programme?.name || 'Programme Not Assigned' }}</h4>
                                <p class="text-xs text-muted-foreground">
                                    {{ applicant.programme?.department?.name }} • {{ applicant.programme?.department?.faculty?.name || 'School of Postgraduate Studies' }}
                                </p>
                            </div>

                            <!-- Pathway Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Mode of Study</span>
                                    <span class="font-bold text-foreground uppercase">{{ applicant.study_mode || 'Full-Time' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Area of Specialization</span>
                                    <span class="font-bold text-foreground">{{ applicant.area_of_specialization || 'General Track' }}</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Sponsorship Type</span>
                                    <span class="font-bold text-foreground capitalize">{{ applicant.sponsorship_type || 'Self' }}</span>
                                    <span v-if="applicant.sponsor_name" class="block text-[10px] text-muted-foreground">({{ applicant.sponsor_name }})</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Work Experience</span>
                                    <span class="font-bold text-foreground">{{ applicant.work_experience_years ?? 0 }} Years</span>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Official Transcript Status</span>
                                    <Badge variant="outline" class="text-[10px] capitalize font-medium">
                                        {{ applicant.official_transcript_status ? applicant.official_transcript_status.replace(/_/g, ' ') : 'Not Requested' }}
                                    </Badge>
                                </div>

                                <div class="space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">Application Mode</span>
                                    <span class="font-bold text-foreground">{{ applicant.application_mode }}</span>
                                </div>
                            </div>

                            <!-- Proposed Research / Dissertation Topic -->
                            <div v-if="applicant.research_proposal_title" class="p-3.5 bg-muted/20 rounded-xl border border-border/70 space-y-1">
                                <span class="font-bold text-muted-foreground uppercase text-[10px] tracking-wider block">PROPOSED RESEARCH / DISSERTATION TOPIC</span>
                                <p class="font-serif text-sm font-semibold text-foreground italic">
                                    "{{ applicant.research_proposal_title }}"
                                </p>
                            </div>

                            <!-- Statement of Purpose -->
                            <div v-if="applicant.statement_of_purpose" class="p-4 bg-muted/20 rounded-xl border border-border/70 space-y-1.5">
                                <span class="font-bold text-muted-foreground uppercase text-[10px] tracking-wider block">STATEMENT OF PURPOSE / ACADEMIC MOTIVATION</span>
                                <p class="text-xs text-foreground leading-relaxed whitespace-pre-line">
                                    {{ applicant.statement_of_purpose }}
                                </p>
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
                            <CardDescription class="text-xs">Candidate's verified personal profile, legal identity, and address</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-4 text-xs">
                            <!-- Name Change Alert -->
                            <div v-if="applicant.has_name_change" class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl flex items-start gap-2 text-amber-800 dark:text-amber-300">
                                <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-bold">Candidate Has Legal Name Change:</span>
                                    <p class="text-[11px] mt-0.5">
                                        Previous Full Name: <strong class="underline">{{ applicant.previous_full_name || 'Specified in uploaded documents' }}</strong>. Check attached marriage certificate / deed poll.
                                    </p>
                                </div>
                            </div>

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

                    <!-- Section 3: National Youth Service Corps (NYSC) -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                <ScrollText class="w-4 h-4 text-primary" />
                                National Service (NYSC) Discharge Status
                            </CardTitle>
                            <CardDescription class="text-xs">Mandatory statutory clearance for Nigerian tertiary graduates</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                <div class="p-3.5 rounded-xl border border-border/70 bg-muted/10 space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">NYSC Status</span>
                                    <Badge variant="outline" class="text-xs font-bold capitalize">
                                        {{ applicant.nysc_status || 'Not Specified' }}
                                    </Badge>
                                </div>

                                <div class="p-3.5 rounded-xl border border-border/70 bg-muted/10 space-y-1">
                                    <span class="text-muted-foreground block text-[11px]">NYSC Certificate / Exemption Number</span>
                                    <span class="font-mono font-bold text-sm text-foreground">
                                        {{ applicant.nysc_certificate_number || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 4: Academic Credentials & Prior Tertiary Degrees -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                        <GraduationCap class="w-4 h-4 text-primary" />
                                        Academic Credentials & Prior Tertiary Degrees
                                    </CardTitle>
                                    <CardDescription class="text-xs">All tertiary qualifications evaluated for postgraduate entry</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs">
                                    {{ applicant.qualifications?.length || 1 }} Degree(s) Logged
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-3">
                            <div 
                                v-if="applicant.qualifications && applicant.qualifications.length > 0"
                                class="space-y-3"
                            >
                                <div 
                                    v-for="qual in applicant.qualifications" 
                                    :key="qual.id"
                                    class="p-4 rounded-xl border border-border/70 bg-muted/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <Badge variant="outline" class="font-bold text-[10px] text-primary bg-primary/5">
                                                {{ qual.qualification_type }}
                                            </Badge>
                                            <p class="font-bold text-foreground text-sm">
                                                {{ qual.course_of_study }}
                                            </p>
                                        </div>
                                        <p class="text-muted-foreground">
                                            {{ qual.institution_name }} • Graduated: <strong class="text-foreground">{{ qual.graduation_year || 'N/A' }}</strong>
                                        </p>
                                        <p v-if="qual.certificate_number" class="text-[11px] font-mono text-muted-foreground">
                                            Certificate Number: {{ qual.certificate_number }}
                                        </p>
                                    </div>
                                    <div class="text-left sm:text-right shrink-0">
                                        <Badge variant="outline" class="font-semibold text-xs">{{ qual.class_of_degree }}</Badge>
                                        <p v-if="qual.cgpa" class="text-xs font-mono font-bold mt-1 text-primary">
                                            CGPA: {{ qual.cgpa }} / {{ qual.scale_max || '5.00' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="applicant.previous_qualification" class="p-4 rounded-xl border border-border/70 bg-muted/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <p class="font-bold text-foreground text-sm">{{ applicant.previous_qualification }} — {{ applicant.previous_course_of_study || 'N/A' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ applicant.previous_institution || 'N/A' }} ({{ applicant.graduation_year || 'N/A' }})</p>
                                </div>
                                <div class="text-left sm:text-right shrink-0">
                                    <Badge variant="outline">{{ applicant.previous_class_of_degree || 'N/A' }}</Badge>
                                    <p v-if="applicant.previous_cgpa" class="text-xs font-mono font-bold mt-1 text-primary">
                                        CGPA: {{ applicant.previous_cgpa }}
                                    </p>
                                </div>
                            </div>
                            <div v-else class="text-center py-6 text-xs text-muted-foreground italic">
                                No prior qualifications entered by applicant.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 5: Academic & Professional Referees -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                        <UserCheck class="w-4 h-4 text-primary" />
                                        Academic & Professional Referees
                                    </CardTitle>
                                    <CardDescription class="text-xs">Confidential online recommendations submitted by referees</CardDescription>
                                </div>
                                <Badge :variant="submittedRefereesCount >= 3 ? 'default' : 'secondary'" class="text-xs font-mono">
                                    {{ submittedRefereesCount }} / {{ applicant.referees?.length || 0 }} Submitted
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-4">
                            <div v-if="!applicant.referees || applicant.referees.length === 0" class="text-center py-6 text-xs text-muted-foreground italic">
                                No referees registered.
                            </div>
                            <div
                                v-for="(referee, idx) in applicant.referees"
                                :key="referee.id"
                                class="p-4 rounded-xl border border-border/70 bg-card space-y-3 text-xs shadow-none"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold uppercase text-muted-foreground">Referee {{ idx + 1 }}</span>
                                            <p class="font-bold text-foreground text-sm">
                                                {{ referee.name }} 
                                            </p>
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            {{ referee.position_or_rank || referee.relationship }} • {{ referee.institution_or_organization }}
                                        </p>
                                        <p class="text-xs text-muted-foreground font-mono">
                                            {{ referee.email }} <span v-if="referee.phone">• {{ referee.phone }}</span>
                                        </p>
                                    </div>
                                    <Badge :variant="referee.status === 'submitted' ? 'default' : 'secondary'" class="uppercase text-[10px] font-semibold">
                                        {{ referee.status }}
                                    </Badge>
                                </div>

                                <!-- Submitted Referee Detailed Evaluation Ratings -->
                                <div v-if="referee.status === 'submitted'" class="border-t border-border/60 pt-3 space-y-2.5">
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center">
                                        <div class="p-2 rounded-lg bg-muted/20 border border-border/60">
                                            <span class="text-[9px] uppercase tracking-wider text-muted-foreground block">Verdict</span>
                                            <span class="font-bold text-foreground text-[11px] truncate block">
                                                {{ referee.recommendation_strength?.replaceAll('_', ' ').toUpperCase() || 'RECOMMENDED' }}
                                            </span>
                                        </div>
                                        <div class="p-2 rounded-lg bg-muted/20 border border-border/60">
                                            <span class="text-[9px] uppercase tracking-wider text-muted-foreground block">Academic</span>
                                            <span class="font-bold text-foreground text-xs">{{ referee.academic_capacity_rating ?? '—' }} / 5</span>
                                        </div>
                                        <div class="p-2 rounded-lg bg-muted/20 border border-border/60">
                                            <span class="text-[9px] uppercase tracking-wider text-muted-foreground block">Research</span>
                                            <span class="font-bold text-foreground text-xs">{{ referee.research_potential_rating ?? '—' }} / 5</span>
                                        </div>
                                        <div class="p-2 rounded-lg bg-muted/20 border border-border/60">
                                            <span class="text-[9px] uppercase tracking-wider text-muted-foreground block">Integrity</span>
                                            <span class="font-bold text-foreground text-xs">{{ referee.character_integrity_rating ?? '—' }} / 5</span>
                                        </div>
                                        <div class="p-2 rounded-lg bg-muted/20 border border-border/60">
                                            <span class="text-[9px] uppercase tracking-wider text-muted-foreground block">Communication</span>
                                            <span class="font-bold text-foreground text-xs">{{ referee.communication_rating ?? '—' }} / 5</span>
                                        </div>
                                    </div>
                                    <div v-if="referee.comments" class="text-xs text-muted-foreground italic bg-muted/10 p-3 rounded-lg border border-border/60 leading-relaxed">
                                        "{{ referee.comments }}"
                                    </div>
                                </div>

                                <!-- Pending Referee Copy Link Action -->
                                <div v-else-if="referee.token" class="border-t border-border/60 pt-2 flex items-center justify-between text-[11px]">
                                    <span class="text-muted-foreground">Confidential recommendation pending submission.</span>
                                    <button
                                        type="button"
                                        @click="copyRefereeLink(referee.token)"
                                        class="text-primary hover:underline flex items-center gap-1 font-medium"
                                    >
                                        <Check v-if="copiedRefereeToken === referee.token" class="w-3.5 h-3.5 text-emerald-600" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        {{ copiedRefereeToken === referee.token ? 'Copied Form Link!' : 'Copy Form Link' }}
                                    </button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 6: Employment History & Industry Experience -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                        <Briefcase class="w-4 h-4 text-primary" />
                                        Employment & Professional Experience
                                    </CardTitle>
                                    <CardDescription class="text-xs">Professional background and industry practice</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs">
                                    {{ applicant.employments?.length || 0 }} Record(s)
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-3">
                            <div v-if="!applicant.employments || applicant.employments.length === 0" class="text-center py-6 text-xs text-muted-foreground italic">
                                No employment history entered by candidate.
                            </div>
                            <div 
                                v-for="emp in applicant.employments" 
                                :key="emp.id"
                                class="p-4 rounded-xl border border-border/70 bg-muted/10 text-xs space-y-2"
                            >
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <p class="font-bold text-foreground text-sm">{{ emp.job_title || emp.position_title }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ emp.employer_name }} <span v-if="emp.department_or_unit">• {{ emp.department_or_unit }}</span>
                                        </p>
                                    </div>
                                    <Badge :variant="emp.is_current ? 'default' : 'outline'" class="text-[10px]">
                                        {{ emp.is_current ? 'Current Job' : 'Past' }}
                                    </Badge>
                                </div>
                                <p class="text-[11px] text-muted-foreground font-mono">
                                    Duration: {{ emp.start_date || '—' }} to {{ emp.is_current ? 'Present' : (emp.end_date || '—') }}
                                </p>
                                <p v-if="emp.responsibilities || emp.job_description" class="text-muted-foreground text-xs pt-1 border-t border-border/60 leading-relaxed whitespace-pre-line">
                                    {{ emp.responsibilities || emp.job_description }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Section 7: Submitted Documents & Uploads -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3 border-b border-border/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold text-foreground flex items-center gap-2">
                                        <FileText class="w-4 h-4 text-primary" />
                                        Submitted Documents & Uploads
                                    </CardTitle>
                                    <CardDescription class="text-xs">Original academic credentials, certificates, and research files</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs">
                                    {{ applicant.documents?.length || 0 }} Document(s)
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div v-if="!applicant.documents || applicant.documents.length === 0" class="text-center py-6 text-xs text-muted-foreground italic">
                                No documents uploaded.
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
