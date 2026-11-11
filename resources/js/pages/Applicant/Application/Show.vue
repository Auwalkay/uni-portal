<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    CheckCircle2,
    Clock,
    AlertCircle,
    CreditCard,
    Printer,
    Download,
    Copy,
    Check,
    ExternalLink,
    FileText,
    Building2,
    GraduationCap,
    UserCheck,
    Mail,
    Phone,
    MapPin,
    Calendar,
    ShieldCheck,
    ArrowLeft,
    Sparkles,
    FileCheck2,
    Share2,
    School,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    applicant: any;
    invoice?: any;
    hasPaidApplicationFee?: boolean;
    currentSession?: any;
}>();

const copiedNumber = ref(false);
const copyApplicationNumber = async () => {
    if (!props.applicant?.application_number) return;
    try {
        await navigator.clipboard.writeText(props.applicant.application_number);
        copiedNumber.value = true;
        setTimeout(() => { copiedNumber.value = false; }, 2000);
    } catch {
        // fallback
    }
};

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

const passportFailed = ref(false);
const passportUrl = computed(() => {
    if (!props.applicant?.documents) return null;
    const doc = props.applicant.documents.find((d: any) => d.type === 'passport_photo');
    return doc?.path ? `/storage/${doc.path}` : null;
});

const isPaymentPending = computed(() => {
    if (!props.applicant) return false;
    if (props.applicant.status === 'pending_payment') return true;
    if (props.invoice && props.invoice.status !== 'paid') return true;
    if (props.hasPaidApplicationFee === false) return true;
    return !props.applicant.application_number;
});

const isPg = computed(() => {
    return props.applicant?.application_mode === 'PG' || props.applicant?.programme?.type === 'MSC' || props.applicant?.programme?.type === 'PHD' || props.applicant?.programme?.type === 'PGD';
});

const applicantFullName = computed(() => {
    const title = props.applicant?.title ? `${props.applicant.title} ` : '';
    const first = props.applicant?.first_name || '';
    const other = props.applicant?.other_names ? ` ${props.applicant.other_names}` : '';
    const last = props.applicant?.last_name || '';
    return `${title}${last} ${first}${other}`.trim().toUpperCase() || 'APPLICANT';
});

const initials = computed(() => {
    const f = props.applicant?.first_name?.[0] || 'A';
    const l = props.applicant?.last_name?.[0] || 'P';
    return (f + l).toUpperCase();
});

const submittedDate = computed(() => {
    if (!props.applicant?.updated_at) return 'Recently';
    try {
        return new Date(props.applicant.updated_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return 'Recently';
    }
});

const sessionName = computed(() => {
    return props.invoice?.session?.name || props.currentSession?.name || '2026/2027';
});

const refereeStats = computed(() => {
    const refs = props.applicant?.referees || [];
    const completed = refs.filter((r: any) => r.status === 'submitted').length;
    return {
        total: refs.length,
        completed,
        pending: refs.length - completed,
    };
});

const printSlip = () => {
    window.print();
};
</script>

<template>
    <Head title="Application Summary Slip" />

    <ApplicantLayout :breadcrumbs="[
        { title: 'Admissions Dashboard', href: route('applicant.dashboard') },
        { title: 'Application Slip', href: '#' }
    ]">
        <!-- Screen-only Action Toolbar -->
        <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 pt-6 no-print print:hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border/70">
                <Link :href="route('applicant.dashboard')">
                    <Button variant="ghost" size="sm" class="text-xs text-muted-foreground hover:text-foreground -ml-2 h-8">
                        <ArrowLeft class="w-3.5 h-3.5 mr-1.5" />
                        Back to Dashboard
                    </Button>
                </Link>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Button
                        v-if="applicant?.application_number"
                        variant="outline"
                        size="sm"
                        @click="copyApplicationNumber"
                        class="text-xs h-9"
                    >
                        <Check v-if="copiedNumber" class="w-3.5 h-3.5 mr-1.5 text-emerald-600" />
                        <Copy v-else class="w-3.5 h-3.5 mr-1.5" />
                        {{ copiedNumber ? 'Copied Number!' : 'Copy App No.' }}
                    </Button>

                    <a
                        v-if="isPg"
                        :href="route('applicant.apply.transcript_label')"
                        target="_blank"
                    >
                        <Button variant="outline" size="sm" class="text-xs h-9 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/40">
                            <Download class="w-3.5 h-3.5 mr-1.5" />
                            Transcript Request (PDF)
                        </Button>
                    </a>

                    <Button
                        variant="default"
                        size="sm"
                        @click="printSlip"
                        class="text-xs h-9 shadow-sm"
                    >
                        <Printer class="w-3.5 h-3.5 mr-1.5" />
                        Print Application Slip
                    </Button>
                </div>
            </div>
        </div>

        <!-- Main Content Canvas -->
        <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">

            <!-- Celebration / Success Screen Banner (Screen only) -->
            <div
                v-if="!isPaymentPending"
                class="no-print print:hidden rounded-2xl p-6 sm:p-7 border transition-all relative overflow-hidden bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border-emerald-500/20 shadow-sm"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6 relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-600/20 ring-4 ring-emerald-500/10">
                        <CheckCircle2 class="w-6 h-6 stroke-[2.5]" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl sm:text-2xl font-bold text-foreground tracking-tight">
                                Application Successfully Received
                            </h2>
                            <Badge class="bg-emerald-600 text-white border-none text-[10px] uppercase font-semibold">
                                Confirmed
                            </Badge>
                        </div>
                        <p class="text-xs sm:text-sm text-muted-foreground max-w-2xl leading-relaxed">
                            Your application for <strong class="text-foreground">{{ applicant.programme?.name }}</strong> has been officially logged for the <span class="text-foreground font-medium">{{ sessionName }}</span> admission exercise. Review your verified admission summary slip below.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Urgent Payment Warning (if fee still pending) -->
            <div
                v-else
                class="rounded-2xl p-6 border bg-amber-500/10 border-amber-500/30 space-y-3"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                    <div class="space-y-1 flex-1">
                        <h3 class="text-base font-semibold text-foreground">Application Fee Payment Required</h3>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            Your application details have been saved, but your file cannot be released to the Admissions Screening Committee until the non-refundable application fee is settled.
                        </p>
                        <div class="pt-3">
                            <Link :href="route('applicant.payment.index')">
                                <Button size="sm" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold">
                                    <CreditCard class="w-3.5 h-3.5 mr-1.5" />
                                    Pay ₦{{ Number(invoice?.amount || 100000).toLocaleString() }} Application Fee
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- THE OFFICIAL ADMISSION ACKNOWLEDGMENT SLIP (PRINT & VIEW) -->
            <!-- ======================================================== -->
            <div class="bg-card border border-border/80 rounded-2xl shadow-sm print:shadow-none print:border print:rounded-none overflow-hidden print-slip-container">

                <!-- Slip Header: University Branding -->
                <div class="border-b border-border/70 p-6 sm:p-8 bg-muted/20 print:bg-white relative">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        
                        <!-- University Crest / Identity -->
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-white p-1 border border-border/80 flex items-center justify-center shrink-0 shadow-sm overflow-hidden">
                                <img src="/miu-logo.png" alt="Mewar International University Logo" class="w-full h-full object-contain" />
                            </div>
                            <div class="space-y-0.5">
                                <h1 class="text-lg sm:text-xl font-serif font-bold text-foreground tracking-tight uppercase">
                                    Mewar International University
                                </h1>
                                <p class="text-xs font-medium text-muted-foreground">
                                    {{ isPg ? 'School of Postgraduate Studies (SPGS)' : 'Office of Admissions & Records' }} • Nigeria
                                </p>
                                <p class="text-[11px] text-muted-foreground/80">
                                    Official Application Acknowledgment & Summary Slip
                                </p>
                            </div>
                        </div>

                        <!-- Session & Document Badges -->
                        <div class="flex sm:flex-col items-start sm:items-end justify-between sm:justify-center gap-1.5 text-xs text-muted-foreground">
                            <Badge variant="outline" class="font-mono text-xs px-2.5 py-0.5 border-primary/30 text-primary bg-primary/5">
                                {{ sessionName }} Session
                            </Badge>
                            <span class="text-[11px] text-muted-foreground">
                                Filed: <strong class="text-foreground">{{ submittedDate }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Primary Identity Bar -->
                <div class="p-6 sm:p-8 border-b border-border/70 bg-background">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        
                        <!-- Left: Passport & Personal Summary -->
                        <div class="flex items-start sm:items-center gap-4 sm:gap-5">
                            <!-- Passport Photo Frame -->
                            <div class="relative w-24 h-28 sm:w-28 sm:h-32 rounded-xl border-2 border-border overflow-hidden bg-muted/30 shrink-0 shadow-sm flex items-center justify-center">
                                <img
                                    v-if="passportUrl && !passportFailed"
                                    :src="passportUrl"
                                    @error="passportFailed = true"
                                    alt="Passport Photograph"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-muted-foreground p-2 text-center">
                                    <span class="text-2xl font-bold font-serif text-primary">{{ initials }}</span>
                                    <span class="text-[9px] uppercase tracking-wider mt-1 text-muted-foreground">Passport Photo</span>
                                </div>
                            </div>

                            <!-- Name & Programme Choice -->
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <Badge variant="secondary" class="text-[10px] font-semibold uppercase tracking-wider">
                                        {{ applicant.application_mode || (isPg ? 'Postgraduate' : 'UTME') }}
                                    </Badge>
                                    <Badge
                                        class="text-[10px] font-medium"
                                        :class="applicant.status === 'admitted' ? 'bg-emerald-600 text-white' : 'bg-primary/10 text-primary border border-primary/20'"
                                    >
                                        {{ applicant.status === 'admitted' ? 'Admitted' : (applicant.status === 'screening' ? 'Under Screening' : 'Under Review') }}
                                    </Badge>
                                </div>

                                <h2 class="text-xl sm:text-2xl font-bold text-foreground tracking-tight leading-tight">
                                    {{ applicantFullName }}
                                </h2>

                                <p class="text-sm font-semibold text-primary">
                                    {{ applicant.programme?.name || 'Programme Not Assigned' }}
                                </p>

                                <p class="text-xs text-muted-foreground flex items-center gap-1.5 flex-wrap">
                                    <Building2 class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ applicant.programme?.department?.name || 'Department' }}</span>
                                    <span class="text-muted-foreground/40">•</span>
                                    <span>{{ applicant.programme?.department?.faculty?.name || 'Faculty' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Right: Application Number Box -->
                        <div class="shrink-0 w-full md:w-auto">
                            <div class="p-4 sm:p-5 rounded-xl border border-primary/20 bg-primary/5 flex flex-col items-start md:items-end justify-center gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-primary">
                                    Application Number
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xl sm:text-2xl font-mono font-bold text-foreground tracking-wider">
                                        {{ applicant.application_number || 'PENDING ASSIGNMENT' }}
                                    </span>
                                    <button
                                        v-if="applicant.application_number"
                                        type="button"
                                        @click="copyApplicationNumber"
                                        class="no-print print:hidden p-1.5 rounded-md hover:bg-primary/10 text-muted-foreground hover:text-foreground transition-colors"
                                        title="Copy Application Number"
                                    >
                                        <Check v-if="copiedNumber" class="w-4 h-4 text-emerald-600" />
                                        <Copy v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <span class="text-[11px] text-muted-foreground mt-0.5">
                                    Ref: {{ applicant.jamb_registration_number || applicant.id?.slice(0, 8).toUpperCase() }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Next Steps Pipeline (Screen only) -->
                <div class="no-print print:hidden p-6 sm:p-8 border-b border-border/70 bg-muted/10">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground mb-4">
                        Admissions Journey & Review Milestones
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/5 space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
                                <CheckCircle2 class="w-4 h-4" />
                                <span>1. Submission</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground">Application submitted and fee verified.</p>
                        </div>

                        <div class="p-3 rounded-xl border border-blue-500/30 bg-blue-500/5 space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-blue-600">
                                <Clock class="w-4 h-4 animate-pulse" />
                                <span>2. Screening</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground">Credentials under verification.</p>
                        </div>

                        <div class="p-3 rounded-xl border border-border/70 bg-card/60 space-y-1 opacity-75">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                                <School class="w-4 h-4" />
                                <span>3. Academic Board</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground">Departmental recommendation.</p>
                        </div>

                        <div class="p-3 rounded-xl border border-border/70 bg-card/60 space-y-1 opacity-75">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                                <GraduationCap class="w-4 h-4" />
                                <span>4. Admission Offer</span>
                            </div>
                            <p class="text-[11px] text-muted-foreground">Provisional letter & enrolment.</p>
                        </div>
                    </div>
                </div>

                <!-- Slip Body: Grid of Application Sections -->
                <div class="p-6 sm:p-8 space-y-8">

                    <!-- Section 1: Personal & Contact Information -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-border/60">
                            <UserCheck class="w-4 h-4 text-primary" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                                Section A: Personal & Contact Details
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-xs">
                            <div class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Full Name</span>
                                <span class="font-semibold text-foreground">{{ applicantFullName }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Date of Birth</span>
                                <span class="font-semibold text-foreground">{{ applicant.dob || '—' }}</span>
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
                                <span class="text-muted-foreground block text-[11px]">Phone Number</span>
                                <span class="font-semibold text-foreground font-mono">{{ applicant.phone || '—' }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Nationality</span>
                                <span class="font-semibold text-foreground">{{ applicant.nationality || 'Nigerian' }}</span>
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
                                <span class="text-muted-foreground block text-[11px]">Next of Kin Phone</span>
                                <span class="font-semibold text-foreground font-mono">{{ applicant.next_of_kin_phone || '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Programme & Study Route -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-border/60">
                            <GraduationCap class="w-4 h-4 text-primary" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                                Section B: Programme Choice & Academic Pathway
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 text-xs">
                            <div class="col-span-2 space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Chosen Programme</span>
                                <span class="font-semibold text-foreground text-sm">{{ applicant.programme?.name || '—' }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Mode of Study</span>
                                <span class="font-semibold text-foreground uppercase">{{ applicant.study_mode || 'Full-Time' }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Sponsorship</span>
                                <span class="font-semibold text-foreground capitalize">{{ applicant.sponsorship_type || 'Self' }}</span>
                            </div>

                            <div v-if="!isPg" class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">JAMB Registration No.</span>
                                <span class="font-semibold text-foreground font-mono">{{ applicant.jamb_registration_number || '—' }}</span>
                            </div>

                            <div v-if="!isPg" class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">JAMB Official Score</span>
                                <span class="font-semibold text-foreground font-bold text-sm">{{ applicant.jamb_score || '—' }}</span>
                            </div>

                            <div v-if="isPg" class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">NYSC Discharge Status</span>
                                <span class="font-semibold text-foreground">{{ applicant.nysc_status || 'Completed' }}</span>
                            </div>

                            <div v-if="isPg" class="space-y-1">
                                <span class="text-muted-foreground block text-[11px]">NYSC Certificate No.</span>
                                <span class="font-semibold text-foreground font-mono">{{ applicant.nysc_certificate_number || '—' }}</span>
                            </div>

                            <div v-if="isPg && applicant.research_proposal_title" class="col-span-2 sm:col-span-4 space-y-1">
                                <span class="text-muted-foreground block text-[11px]">Proposed Research / Capstone Title</span>
                                <span class="font-semibold text-foreground italic">{{ applicant.research_proposal_title }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Academic Qualifications (For PG) -->
                    <div v-if="isPg && applicant.qualifications && applicant.qualifications.length > 0" class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-border/60">
                            <Building2 class="w-4 h-4 text-primary" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                                Section C: Prior Academic Qualifications
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <div
                                v-for="qual in applicant.qualifications"
                                :key="qual.id"
                                class="p-3.5 rounded-xl border border-border/70 bg-muted/10 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="text-[10px] font-bold text-primary bg-primary/5">
                                            {{ qual.qualification_type }}
                                        </Badge>
                                        <span class="font-bold text-foreground text-sm">
                                            {{ qual.course_of_study }}
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground">
                                        {{ qual.institution_name }} • Class of Degree: <strong class="text-foreground">{{ qual.class_of_degree }}</strong>
                                        <span v-if="qual.cgpa"> • CGPA: <strong class="text-foreground">{{ qual.cgpa }} / {{ qual.scale_max || '5.00' }}</strong></span>
                                        • Graduated: <strong class="text-foreground">{{ qual.graduation_year }}</strong>
                                    </p>
                                </div>
                                <div v-if="qual.certificate_number" class="text-[11px] text-muted-foreground shrink-0 font-mono">
                                    Cert No: {{ qual.certificate_number }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: SPGS Transcript Request Sheet Banner (For PG) -->
                    <div v-if="isPg" class="p-4 sm:p-5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <FileCheck2 class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                    <h4 class="text-sm font-bold text-foreground">Official Academic Transcript Request Label</h4>
                                </div>
                                <p class="text-xs text-muted-foreground max-w-2xl leading-relaxed">
                                    Postgraduate applicants are required to dispatch their official academic transcripts directly from their graduating institution to the SPGS Admissions Board. Use this official dispatch cover sheet.
                                </p>
                            </div>
                            <a :href="route('applicant.apply.transcript_label')" target="_blank" class="shrink-0 no-print print:hidden">
                                <Button size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm">
                                    <Download class="w-3.5 h-3.5 mr-1.5" />
                                    Download Transcript Sheet
                                </Button>
                            </a>
                        </div>
                    </div>

                    <!-- Section 5: Referees & Recommendation Status (For PG) -->
                    <div v-if="isPg && applicant.referees && applicant.referees.length > 0" class="space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-border/60">
                            <div class="flex items-center gap-2">
                                <UserCheck class="w-4 h-4 text-primary" />
                                <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                                    Section D: Confidential Referees
                                </h3>
                            </div>
                            <span class="text-xs font-medium text-muted-foreground">
                                {{ refereeStats.completed }} of {{ refereeStats.total }} Received
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <div
                                v-for="(referee, idx) in applicant.referees"
                                :key="referee.id"
                                class="p-4 rounded-xl border border-border/70 bg-card space-y-2 text-xs flex flex-col justify-between"
                            >
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-bold uppercase text-muted-foreground">
                                            Referee {{ idx + 1 }}
                                        </span>
                                        <Badge
                                            class="text-[9px] font-semibold uppercase tracking-wider"
                                            :class="referee.status === 'submitted' ? 'bg-emerald-600 text-white' : 'bg-amber-500/10 text-amber-600 border border-amber-500/30'"
                                        >
                                            {{ referee.status === 'submitted' ? 'Submitted' : 'Pending' }}
                                        </Badge>
                                    </div>
                                    <h5 class="font-bold text-foreground text-sm">{{ referee.name }}</h5>
                                    <p class="text-muted-foreground text-[11px]">{{ referee.institution_or_organization }}</p>
                                    <p class="text-muted-foreground font-mono text-[11px] truncate">{{ referee.email }}</p>
                                </div>

                                <!-- Action if referee is still pending -->
                                <div v-if="referee.status !== 'submitted' && referee.token" class="pt-2 border-t border-border/60 no-print print:hidden flex items-center justify-between gap-2">
                                    <button
                                        type="button"
                                        @click="copyRefereeLink(referee.token)"
                                        class="text-[11px] text-primary hover:underline flex items-center gap-1 font-medium"
                                    >
                                        <Check v-if="copiedRefereeToken === referee.token" class="w-3 h-3 text-emerald-600" />
                                        <Copy v-else class="w-3 h-3" />
                                        {{ copiedRefereeToken === referee.token ? 'Copied Link!' : 'Copy Form Link' }}
                                    </button>
                                    <a
                                        :href="route('referee.recommendation.show', referee.token)"
                                        target="_blank"
                                        class="text-[11px] text-muted-foreground hover:text-foreground inline-flex items-center"
                                        title="Preview Form"
                                    >
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 6: Verified Uploaded Documents -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-border/60">
                            <FileText class="w-4 h-4 text-primary" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-foreground">
                                Supporting Document Check
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div
                                v-for="doc in (applicant.documents || [])"
                                :key="doc.id"
                                class="p-3 rounded-xl border border-border/70 bg-muted/10 space-y-1"
                            >
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
                                    <span class="font-semibold text-[11px] capitalize truncate">
                                        {{ doc.type?.replace(/_/g, ' ') }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-muted-foreground truncate" :title="doc.original_name">
                                    {{ doc.original_name }}
                                </p>
                            </div>
                            <div v-if="!applicant.documents || applicant.documents.length === 0" class="col-span-full text-xs text-muted-foreground italic py-2">
                                No additional documents uploaded.
                            </div>
                        </div>
                    </div>

                    <!-- Official Certification Box -->
                    <div class="p-4 sm:p-5 rounded-xl border border-border/80 bg-muted/10 text-xs text-muted-foreground leading-relaxed flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <p class="font-semibold text-foreground">Applicant Certification & Declaration</p>
                            <p class="text-[11px]">
                                I hereby certify that the information entered on this application slip is authentic and accurate. Mewar International University reserves the right to withdraw any admission granted based on false declarations.
                            </p>
                        </div>
                        <div class="shrink-0 text-right font-mono text-[10px] text-muted-foreground border-t sm:border-t-0 sm:border-l sm:pl-4 border-border/70 pt-2 sm:pt-0">
                            <span>PORTAL-VERIFIED</span><br />
                            <span class="text-foreground font-bold">{{ applicant.application_number || 'N/A' }}</span>
                        </div>
                    </div>

                </div>

                <!-- Slip Footer Note -->
                <div class="border-t border-border/70 p-4 sm:p-6 bg-muted/20 text-center text-[11px] text-muted-foreground">
                    Mewar International University Nigeria • KM 21, Abuja-Keffi Expressway, Masaka, FCT/Nasarawa Corridor • admission@miu.edu.ng
                </div>

            </div>

        </div>
    </ApplicantLayout>
</template>

<style>
@media print {
    /* Hide layout navigation, sidebars, breadcrumbs, action buttons */
    nav, aside, header, .no-print, button, .print\:hidden {
        display: none !important;
    }
    body, .min-h-screen, .bg-background, .bg-muted\/30 {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .print-slip-container {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
    }
    .print\:block {
        display: block !important;
    }
}
</style>
