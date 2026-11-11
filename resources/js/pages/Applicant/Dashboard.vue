<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    FileText,
    Calendar,
    CheckCircle2,
    Clock,
    AlertCircle,
    CreditCard,
    ArrowRight,
    GraduationCap,
    BookOpen,
    Building2,
    ShieldCheck,
    Mail,
    Phone,
    MapPin,
    Download,
    Award,
    Sparkles,
    UserCheck,
    HelpCircle,
    ChevronRight,
    ExternalLink
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    applicant?: {
        id: string;
        status: string;
        jamb_registration_number?: string;
        application_mode?: string;
        application_number?: string;
        created_at?: string;
        updated_at?: string;
        admitted_level?: string;
        programme?: {
            id: string;
            name: string;
            type?: string;
            duration?: number;
            department?: {
                name: string;
                faculty?: {
                    name: string;
                };
            };
        } | null;
        documents?: Array<{ type: string; path: string }>;
        qualifications?: Array<any>;
        referees?: Array<{ id: string; status: string }>;
    } | null;
    applicationInvoice?: {
        id: string;
        reference: string;
        amount: number;
        status: string;
        due_date?: string;
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
    user?: {
        name: string;
        email: string;
    } | null;
    support?: {
        email: string;
        phone: string;
        alt_phone: string;
        address: string;
        portal_url: string;
    } | null;
}>();

const breadcrumbs = [
    { title: 'Applicant Portal', href: route('applicant.dashboard') },
    { title: 'Dashboard', href: '#' },
];

const userName = computed(() => props.user?.name || 'Applicant');

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
});

const isPaymentPending = computed(() => {
    if (!props.applicant) return false;
    if (props.hasPaidApplicationFee) return false;
    if (props.applicant.status === 'pending_payment') return true;
    if (props.applicationInvoice && props.applicationInvoice.status !== 'paid') return true;
    return false;
});

const isApplicationInProgress = computed(() => {
    return props.applicant &&
        !isPaymentPending.value &&
        !['submitted', 'screening', 'admitted', 'enrolled', 'rejected'].includes(props.applicant.status);
});

const isPostgraduate = computed(() => {
    const mode = props.applicant?.application_mode?.toUpperCase() || '';
    const progType = props.applicant?.programme?.type?.toUpperCase() || '';
    return ['PG', 'POSTGRADUATE'].includes(mode) || ['MSC', 'MBA', 'MIM', 'PHD', 'PGD'].includes(progType);
});

const statusBadgeConfig = computed(() => {
    if (!props.applicant) {
        return {
            label: 'Not Started',
            color: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300',
            dot: 'bg-slate-400',
            description: 'Begin by selecting your preferred programme and admission pathway.',
        };
    }
    if (isPaymentPending.value) {
        return {
            label: 'Payment Pending',
            color: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/50 dark:text-amber-300',
            dot: 'bg-amber-500 animate-pulse',
            description: 'Pay the application fee to proceed with filling your application details.',
        };
    }
    if (isApplicationInProgress.value) {
        return {
            label: 'Form In Progress',
            color: 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/50 dark:text-blue-300',
            dot: 'bg-blue-500',
            description: 'Application fee confirmed. Please complete and submit your application form.',
        };
    }
    if (props.applicant.status === 'submitted') {
        return {
            label: 'Under Review',
            color: 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-300',
            dot: 'bg-indigo-500',
            description: 'Your application has been submitted and is awaiting academic screening.',
        };
    }
    if (props.applicant.status === 'screening') {
        return {
            label: 'In Screening',
            color: 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/50 dark:text-purple-300',
            dot: 'bg-purple-500 animate-pulse',
            description: 'Your credentials are under active screening by the admissions committee.',
        };
    }
    if (props.applicant.status === 'admitted') {
        return {
            label: 'Admitted',
            color: 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-300',
            dot: 'bg-emerald-500',
            description: 'Congratulations! You have received a Provisional Offer of Admission.',
        };
    }
    if (props.applicant.status === 'enrolled') {
        return {
            label: 'Enrolled',
            color: 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-300',
            dot: 'bg-emerald-600',
            description: 'Official student enrollment confirmed. Welcome to the university!',
        };
    }
    if (props.applicant.status === 'rejected') {
        return {
            label: 'Not Admitted',
            color: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/50 dark:text-rose-300',
            dot: 'bg-rose-500',
            description: 'Your application did not meet the entry criteria for this admission cycle.',
        };
    }
    return {
        label: props.applicant.status,
        color: 'bg-slate-100 text-slate-700 border-slate-200',
        dot: 'bg-slate-400',
        description: 'Current admission status.',
    };
});

// Journey milestone steps
const currentStepNumber = computed(() => {
    if (!props.applicant) return 1;
    if (isPaymentPending.value) return 2;
    if (isApplicationInProgress.value) return 3;
    if (props.applicant.status === 'submitted' || props.applicant.status === 'screening') return 4;
    if (['admitted', 'enrolled'].includes(props.applicant.status)) return 5;
    return 1;
});

const steps = computed(() => [
    {
        number: 1,
        title: 'Choose Programme',
        subtitle: props.applicant?.programme?.name || 'Mode & Degree',
        status: !props.applicant ? 'active' : 'completed',
    },
    {
        number: 2,
        title: 'Application Fee',
        subtitle: props.hasPaidApplicationFee ? 'Paid' : '₦100k / ₦50k',
        status: !props.applicant
            ? 'pending'
            : isPaymentPending.value
            ? 'active'
            : 'completed',
    },
    {
        number: 3,
        title: 'Complete Details',
        subtitle: ['submitted', 'screening', 'admitted', 'enrolled'].includes(props.applicant?.status || '') ? 'Submitted' : 'Biodata & Docs',
        status: !props.applicant || isPaymentPending.value
            ? 'pending'
            : isApplicationInProgress.value
            ? 'active'
            : 'completed',
    },
    {
        number: 4,
        title: 'Admissions Screening',
        subtitle: ['admitted', 'enrolled'].includes(props.applicant?.status || '') ? 'Cleared' : 'Faculty Review',
        status: ['admitted', 'enrolled'].includes(props.applicant?.status || '')
            ? 'completed'
            : ['submitted', 'screening'].includes(props.applicant?.status || '')
            ? 'active'
            : 'pending',
    },
    {
        number: 5,
        title: 'Offer & Enrollment',
        subtitle: props.applicant?.status === 'enrolled' ? 'Enrolled' : 'Provisional Offer',
        status: props.applicant?.status === 'enrolled'
            ? 'completed'
            : props.applicant?.status === 'admitted'
            ? 'active'
            : 'pending',
    },
]);

const applicationFeeAmount = computed(() => {
    return Number(props.applicationInvoice?.amount || props.invoice?.amount || (isPostgraduate.value ? 50000 : 100000));
});

const modeDisplay = computed(() => {
    if (!props.applicant?.application_mode) return 'Pending Selection';
    const m = props.applicant.application_mode.toUpperCase();
    if (m === 'DE') return 'Direct Entry';
    if (m === 'PG') return 'Postgraduate';
    return props.applicant.application_mode;
});
</script>

<template>
    <Head title="Applicant Dashboard" />

    <ApplicantLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8">
            
            <!-- HEADER / WELCOME SECTION -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/40">
                <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/4 -bottom-16 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2 max-w-2xl">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Badge class="bg-indigo-500/20 text-indigo-200 border-indigo-400/30 backdrop-blur-sm text-xs font-medium">
                                <Sparkles class="w-3 h-3 mr-1 text-indigo-400" />
                                Admissions Portal
                            </Badge>
                            <Badge v-if="applicant?.programme?.type" class="bg-blue-500/20 text-blue-200 border-blue-400/30 text-xs uppercase font-mono">
                                {{ applicant.programme.type }}
                            </Badge>
                            <Badge v-if="currentSession" class="bg-white/10 text-white/90 border-white/20 text-xs">
                                {{ currentSession.name }} Session
                            </Badge>
                        </div>

                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-serif font-bold tracking-tight text-white">
                            {{ greeting }}, <span class="text-indigo-200">{{ userName }}</span>
                        </h1>

                        <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                            {{ statusBadgeConfig.description }}
                        </p>
                    </div>

                    <!-- Header Quick Info Pill -->
                    <div class="flex flex-col sm:flex-row md:flex-col items-start md:items-end gap-3 shrink-0">
                        <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full rounded-full opacity-75" :class="statusBadgeConfig.dot"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="statusBadgeConfig.dot"></span>
                            </span>
                            <span class="text-xs uppercase tracking-wider font-semibold text-slate-200">
                                {{ statusBadgeConfig.label }}
                            </span>
                        </div>

                        <div v-if="applicant?.application_number" class="text-xs text-slate-300 font-mono flex items-center gap-1.5">
                            <span class="text-slate-400">App No:</span>
                            <span class="font-bold text-white">{{ applicant.application_number }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADMISSIONS JOURNEY TRACKER / STEPPER -->
            <Card class="border shadow-sm">
                <CardHeader class="pb-3 border-b bg-muted/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base sm:text-lg font-serif">Admissions Journey</CardTitle>
                            <CardDescription class="text-xs">
                                Stage {{ currentStepNumber }} of 5: Follow your roadmap from application to official matriculation.
                            </CardDescription>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary/10 text-primary">
                            {{ Math.round(((currentStepNumber - 1) / 5) * 100) }}% Complete
                        </span>
                    </div>
                </CardHeader>
                <CardContent class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <div
                            v-for="step in steps"
                            :key="step.number"
                            class="relative p-3.5 rounded-xl border transition-all"
                            :class="{
                                'bg-emerald-50/80 border-emerald-300 dark:bg-emerald-950/20 dark:border-emerald-800': step.status === 'completed',
                                'bg-indigo-50/80 border-indigo-400 ring-2 ring-indigo-500/20 dark:bg-indigo-950/30 dark:border-indigo-700': step.status === 'active',
                                'bg-muted/30 border-muted-foreground/15 opacity-70': step.status === 'pending',
                            }"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <span
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold font-mono"
                                    :class="{
                                        'bg-emerald-600 text-white': step.status === 'completed',
                                        'bg-indigo-600 text-white': step.status === 'active',
                                        'bg-muted text-muted-foreground': step.status === 'pending',
                                    }"
                                >
                                    <CheckCircle2 v-if="step.status === 'completed'" class="w-4 h-4" />
                                    <span v-else>{{ step.number }}</span>
                                </span>

                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300': step.status === 'completed',
                                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300': step.status === 'active',
                                        'bg-slate-100 text-slate-500 dark:bg-slate-800': step.status === 'pending',
                                    }"
                                >
                                    {{ step.status }}
                                </span>
                            </div>

                            <h4 class="text-xs font-bold text-foreground line-clamp-1">
                                {{ step.title }}
                            </h4>
                            <p class="text-[11px] text-muted-foreground truncate mt-0.5" :title="step.subtitle">
                                {{ step.subtitle }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- 4 KEY STATS OVERVIEW CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Stat 1: Status -->
                <Card class="border shadow-sm hover:shadow transition">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Application Status</CardTitle>
                        <FileText class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-bold tracking-tight text-foreground truncate">
                            {{ statusBadgeConfig.label }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1 line-clamp-1">
                            {{ statusBadgeConfig.description }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Stat 2: Programme -->
                <Card class="border shadow-sm hover:shadow transition">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Programme Choice</CardTitle>
                        <GraduationCap class="h-4 w-4 text-indigo-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-base font-bold text-foreground truncate" :title="applicant?.programme?.name || 'Not Selected'">
                            {{ applicant?.programme?.name || 'Not Selected' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1 truncate">
                            {{ applicant?.programme?.department?.faculty?.name || 'Select preferred degree' }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Stat 3: Route -->
                <Card class="border shadow-sm hover:shadow transition">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Admission Route</CardTitle>
                        <Award class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-bold text-foreground truncate">
                            {{ modeDisplay }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1 truncate">
                            {{ isPostgraduate ? 'Postgraduate Stream (SPGS)' : 'Undergraduate Pathway' }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Stat 4: Session -->
                <Card class="border shadow-sm hover:shadow transition">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Academic Session</CardTitle>
                        <Calendar class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-xl font-bold text-foreground truncate">
                            {{ currentSession?.name || '2026/2027' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1 truncate">
                            Active Admissions Cycle
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- PRIMARY CONTEXTUAL HERO ACTION CARD -->
            
            <!-- STATE 1: NOT STARTED -->
            <div v-if="!applicant" class="rounded-2xl border-2 border-indigo-200 dark:border-indigo-900/60 bg-gradient-to-br from-indigo-50/70 via-white to-blue-50/50 dark:from-slate-900 dark:to-indigo-950/40 p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <Badge class="bg-indigo-600 text-white font-medium text-xs">
                            Step 1 of 3: Initiate Application
                        </Badge>
                        <h3 class="text-xl sm:text-2xl font-serif font-bold text-slate-900 dark:text-white">
                            Begin Your Academic Journey at Mewar International University
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Select your entry mode (UTME, Direct Entry, Transfer, or Postgraduate), choose your desired faculty & programme, and take the first step towards world-class higher education.
                        </p>
                        
                        <!-- Checklist of prerequisites -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2">
                            <div class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>Valid Email & Phone</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>O'Level / Degree Results</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>Digital Passport Photo</span>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 w-full sm:w-auto">
                        <Link :href="route('applicant.apply.start')">
                            <Button size="lg" class="w-full sm:w-auto bg-primary hover:bg-primary/90 text-white font-serif font-bold text-base px-8 py-6 shadow-lg shadow-primary/20 gap-2">
                                Start New Application
                                <ArrowRight class="w-5 h-5" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- STATE 2: PROGRAMME CHOSEN, PAYMENT PENDING -->
            <div v-else-if="isPaymentPending" class="rounded-2xl border-2 border-amber-300 dark:border-amber-700/60 bg-gradient-to-br from-amber-50 via-white to-amber-50/40 dark:from-amber-950/30 dark:to-slate-900 p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <Badge class="bg-amber-600 text-white font-medium text-xs">
                                Action Required: Application Fee
                            </Badge>
                            <span class="text-xs font-mono text-amber-900 dark:text-amber-200" v-if="applicationInvoice?.reference || invoice?.reference">
                                Ref: {{ applicationInvoice?.reference || invoice?.reference }}
                            </span>
                        </div>

                        <h3 class="text-xl sm:text-2xl font-serif font-bold text-amber-950 dark:text-amber-100">
                            Complete Application Fee Payment to Unlock Your Form
                        </h3>

                        <p class="text-sm text-amber-900/80 dark:text-amber-200/80 leading-relaxed">
                            You have selected <strong>{{ applicant?.programme?.name || 'your chosen programme' }}</strong> ({{ modeDisplay }}). Pay the non-refundable processing fee of <strong>₦{{ applicationFeeAmount.toLocaleString() }}</strong> to proceed to fill your personal details, qualifications, and document uploads.
                        </p>

                        <div class="flex flex-wrap items-center gap-3 pt-1 text-xs">
                            <div class="px-3 py-1.5 rounded-lg bg-amber-100/80 dark:bg-amber-900/50 text-amber-950 dark:text-amber-200 font-semibold">
                                Amount Due: ₦{{ applicationFeeAmount.toLocaleString() }}
                            </div>
                            <div class="text-amber-800 dark:text-amber-300">
                                Accepted via Card, Bank Transfer, USSD & Instant Gateway
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 shrink-0 w-full lg:w-auto">
                        <Link :href="route('applicant.apply.start')">
                            <Button variant="outline" class="w-full sm:w-auto border-amber-300 text-amber-900 hover:bg-amber-100">
                                Change Programme
                            </Button>
                        </Link>
                        <Link :href="route('applicant.payment.index')">
                            <Button size="lg" class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-serif font-bold text-base px-7 py-6 shadow-lg shadow-amber-600/20 gap-2">
                                <CreditCard class="w-5 h-5" />
                                Pay Fee (₦{{ applicationFeeAmount.toLocaleString() }})
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- STATE 3: FEE PAID, FORM IN PROGRESS -->
            <div v-else-if="isApplicationInProgress" class="rounded-2xl border-2 border-blue-300 dark:border-blue-700/60 bg-gradient-to-br from-blue-50 via-white to-indigo-50/40 dark:from-blue-950/30 dark:to-slate-900 p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <Badge class="bg-blue-600 text-white font-medium text-xs">
                                Step 3 of 5: Form In Progress
                            </Badge>
                            <Badge class="bg-emerald-100 text-emerald-800 border-emerald-300 text-xs">
                                <CheckCircle2 class="w-3 h-3 mr-1 text-emerald-600" />
                                Application Fee Paid
                            </Badge>
                        </div>

                        <h3 class="text-xl sm:text-2xl font-serif font-bold text-blue-950 dark:text-blue-100">
                            Continue Filling Your Application Details
                        </h3>

                        <p class="text-sm text-blue-900/80 dark:text-blue-200/80 leading-relaxed">
                            Your payment is confirmed! Please complete your Biodata, Academic Qualifications, Referees, and upload scanned credentials to finalize your admission file.
                        </p>

                        <div class="flex flex-wrap items-center gap-3 pt-1 text-xs text-blue-900 dark:text-blue-200">
                            <span class="font-semibold">Selected Programme:</span>
                            <span class="px-2.5 py-1 rounded-md bg-blue-100 dark:bg-blue-900/50 font-bold">
                                {{ applicant?.programme?.name || 'Programme Chosen' }}
                            </span>
                        </div>
                    </div>

                    <div class="shrink-0 w-full sm:w-auto">
                        <Link :href="route('applicant.apply.form')">
                            <Button size="lg" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-serif font-bold text-base px-8 py-6 shadow-lg shadow-blue-600/20 gap-2">
                                Continue Application Form
                                <ArrowRight class="w-5 h-5" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- STATE 4: SUBMITTED / IN SCREENING -->
            <div v-else-if="applicant && ['submitted', 'screening'].includes(applicant.status)" class="rounded-2xl border-2 border-indigo-300 dark:border-indigo-700/60 bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/40 dark:from-indigo-950/30 dark:to-slate-900 p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <Badge class="bg-indigo-600 text-white font-medium text-xs">
                                {{ applicant.status === 'screening' ? 'In Screening' : 'Under Review' }}
                            </Badge>
                            <span class="text-xs font-mono font-bold text-indigo-950 dark:text-indigo-200" v-if="applicant.application_number">
                                Reg No: {{ applicant.application_number }}
                            </span>
                        </div>

                        <h3 class="text-xl sm:text-2xl font-serif font-bold text-indigo-950 dark:text-indigo-100">
                            Your Application is Under Academic Review
                        </h3>

                        <p class="text-sm text-indigo-900/80 dark:text-indigo-200/80 leading-relaxed">
                            Your file for <strong>{{ applicant.programme?.name }}</strong> has been officially received. The faculty screening committee and Admissions Office are validating your credentials. You will be notified via email immediately upon verdict.
                        </p>

                        <div class="flex items-center gap-4 text-xs text-indigo-800 dark:text-indigo-300 pt-1">
                            <div class="flex items-center gap-1.5">
                                <Clock class="w-4 h-4 text-indigo-600" />
                                <span>Typical decision turnaround: 48 - 72 hours</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 shrink-0 w-full lg:w-auto">
                        <Link :href="route('applicant.apply.show')">
                            <Button variant="outline" size="lg" class="w-full sm:w-auto border-indigo-300 text-indigo-950 hover:bg-indigo-50 font-serif">
                                <FileText class="w-4 h-4 mr-2" />
                                View Application Slip
                            </Button>
                        </Link>
                        <Link v-if="isPostgraduate" :href="route('applicant.apply.transcript_label')">
                            <Button size="lg" variant="secondary" class="w-full sm:w-auto gap-2">
                                <Download class="w-4 h-4" />
                                Transcript Request Label
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- STATE 5: ADMITTED -->
            <div v-else-if="applicant?.status === 'admitted'" class="rounded-2xl border-2 border-emerald-400 dark:border-emerald-700 bg-gradient-to-br from-emerald-50 via-white to-teal-50/50 dark:from-emerald-950/40 dark:to-slate-900 p-6 sm:p-8 shadow-md">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <Badge class="bg-emerald-600 text-white font-medium text-xs">
                                <Sparkles class="w-3 h-3 mr-1 text-emerald-200" />
                                Provisional Offer Granted
                            </Badge>
                            <span class="text-xs font-mono font-bold text-emerald-900 dark:text-emerald-200">
                                {{ applicant.application_number }}
                            </span>
                        </div>

                        <h3 class="text-xl sm:text-2xl font-serif font-bold text-emerald-950 dark:text-emerald-100">
                            Congratulations! You Have Received an Offer of Admission
                        </h3>

                        <p class="text-sm text-emerald-900/80 dark:text-emerald-200/80 leading-relaxed">
                            Mewar International University is pleased to offer you provisional admission into <strong>{{ applicant.programme?.name }}</strong> ({{ applicant.admitted_level || '100' }} Level). Download your official Provisional Offer letter below and accept your offer to secure your place.
                        </p>

                        <div class="p-3 rounded-lg bg-emerald-100/80 dark:bg-emerald-900/40 text-xs text-emerald-950 dark:text-emerald-200 flex items-center gap-2">
                            <Clock class="w-4 h-4 text-emerald-700 shrink-0" />
                            <span>Offers must be accepted within two (2) weeks of issuance.</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 shrink-0 w-full lg:w-auto">
                        <a :href="route('student.admission_letter.download')" target="_blank" class="w-full sm:w-auto">
                            <Button variant="outline" size="lg" class="w-full border-emerald-400 text-emerald-950 hover:bg-emerald-100 font-serif gap-2">
                                <Download class="w-4 h-4" />
                                Download Offer Letter (PDF)
                            </Button>
                        </a>
                        <Link :href="route('applicant.accept.offer')" method="post" as="button" class="w-full sm:w-auto">
                            <Button size="lg" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-serif font-bold text-base px-8 py-6 shadow-lg shadow-emerald-600/20 gap-2">
                                <UserCheck class="w-5 h-5" />
                                Accept Admission Offer
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- STATE 6: ENROLLED -->
            <div v-else-if="applicant?.status === 'enrolled'" class="rounded-2xl border-2 border-emerald-500 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-950/40 dark:to-slate-900 p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-md">
                            <CheckCircle2 class="w-8 h-8" />
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-xl font-serif font-bold text-emerald-950 dark:text-emerald-100">
                                Student Enrollment Completed!
                            </h3>
                            <p class="text-sm text-emerald-800 dark:text-emerald-300">
                                Your matriculation record has been generated. Please proceed to the full Student Portal to manage course registration and fees.
                            </p>
                        </div>
                    </div>

                    <a href="/student/dashboard">
                        <Button size="lg" class="bg-emerald-600 hover:bg-emerald-700 text-white font-serif font-bold px-8 py-6 gap-2">
                            Go to Student Dashboard
                            <ArrowRight class="w-5 h-5" />
                        </Button>
                    </a>
                </div>
            </div>

            <!-- TWO-COLUMN DETAIL & HELPDESK SECTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left 2 Columns: Application Details & Checklist -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Selected Programme Details Card -->
                    <Card v-if="applicant?.programme" class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base font-serif flex items-center gap-2">
                                    <BookOpen class="w-4 h-4 text-primary" />
                                    Academic Programme Information
                                </CardTitle>
                                <Badge variant="outline" class="uppercase font-mono text-[10px]">
                                    {{ applicant.programme.type || 'Degree' }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="p-5">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                                <div class="space-y-1">
                                    <dt class="text-muted-foreground text-xs uppercase font-medium">Programme Name</dt>
                                    <dd class="font-bold text-foreground">{{ applicant.programme.name }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="text-muted-foreground text-xs uppercase font-medium">Faculty / School</dt>
                                    <dd class="font-semibold text-foreground">{{ applicant.programme.department?.faculty?.name || 'N/A' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="text-muted-foreground text-xs uppercase font-medium">Department</dt>
                                    <dd class="font-semibold text-foreground">{{ applicant.programme.department?.name || 'N/A' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="text-muted-foreground text-xs uppercase font-medium">Standard Duration</dt>
                                    <dd class="font-semibold text-foreground">{{ applicant.programme.duration || 4 }} Academic Years</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <!-- Document Uploads & Credentials Summary -->
                    <Card v-if="applicant" class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-base font-serif flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                Required Documents & Credentials
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Document verification status for your admission file.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-5">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 rounded-lg border bg-card">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">
                                            <FileText class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-foreground">Digital Passport Photograph</h5>
                                            <p class="text-[11px] text-muted-foreground">White background portrait</p>
                                        </div>
                                    </div>
                                    <Badge :class="applicant.documents?.some(d => d.type === 'passport_photo') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="text-[11px]">
                                        {{ applicant.documents?.some(d => d.type === 'passport_photo') ? 'Uploaded' : 'Pending' }}
                                    </Badge>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-lg border bg-card">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">
                                            <Award class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-foreground">
                                                {{ isPostgraduate ? 'Degree / HND Certificate' : "O'Level Result (WAEC/NECO)" }}
                                            </h5>
                                            <p class="text-[11px] text-muted-foreground">Academic credentials for verification</p>
                                        </div>
                                    </div>
                                    <Badge :class="applicant.documents?.some(d => ['olevel_result', 'degree_certificate'].includes(d.type)) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="text-[11px]">
                                        {{ applicant.documents?.some(d => ['olevel_result', 'degree_certificate'].includes(d.type)) ? 'Uploaded' : 'Pending' }}
                                    </Badge>
                                </div>

                                <div v-if="isPostgraduate" class="flex items-center justify-between p-3 rounded-lg border bg-card">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">
                                            <UserCheck class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-foreground">Confidential Referees</h5>
                                            <p class="text-[11px] text-muted-foreground">3 confidential academic / professional recommendations</p>
                                        </div>
                                    </div>
                                    <Badge variant="outline" class="text-[11px] font-mono">
                                        {{ applicant.referees?.filter(r => r.status === 'submitted').length || 0 }} / 3 Submitted
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- New Applicant Guide (Shown when applicant has not started) -->
                    <Card v-else class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-base font-serif flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-primary" />
                                Why Study at Mewar International University?
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                <div class="p-3.5 rounded-xl border bg-slate-50 dark:bg-slate-900/40 space-y-1.5">
                                    <h5 class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                        NUC Licensed & Accredited
                                    </h5>
                                    <p class="text-muted-foreground leading-relaxed">
                                        Formally licensed by the National Universities Commission (NUC) with world-class faculty.
                                    </p>
                                </div>
                                <div class="p-3.5 rounded-xl border bg-slate-50 dark:bg-slate-900/40 space-y-1.5">
                                    <h5 class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                        Global Heritage & Innovation
                                    </h5>
                                    <p class="text-muted-foreground leading-relaxed">
                                        The first Indian-established university in Africa, offering cutting-edge IT and modern infrastructure.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Admissions Office Contact & Notice -->
                <div class="space-y-6">
                    
                    <!-- Admissions Helpdesk Card -->
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-sm font-serif flex items-center gap-2">
                                <HelpCircle class="w-4 h-4 text-primary" />
                                Admissions Helpdesk
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Direct assistance with your application
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-4 space-y-3.5 text-xs">
                            <div class="flex items-start gap-3">
                                <Mail class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Email Support</span>
                                    <a :href="`mailto:${support?.email || 'admission@miu.edu.ng'}`" class="font-semibold text-primary hover:underline">
                                        {{ support?.email || 'admission@miu.edu.ng' }}
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Phone class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Helpline Telephone</span>
                                    <a :href="`tel:${support?.phone || '+2348183012911'}`" class="font-semibold hover:underline block">
                                        {{ support?.phone || '+234 818 301 2911' }}
                                    </a>
                                    <a :href="`tel:${support?.alt_phone || '+2348108040392'}`" class="font-semibold hover:underline block text-muted-foreground">
                                        {{ support?.alt_phone || '+234 810 804 0392' }}
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <MapPin class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Main Campus Location</span>
                                    <span class="text-foreground">
                                        Km21, Kuchikau I, Abuja - Keffi Expressway, Nasarawa State
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Clock class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5" />
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Admissions Office Hours</span>
                                    <span class="text-foreground">Monday – Friday: 8:00 AM – 5:00 PM</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Important Policy Notice Card -->
                    <Card class="border bg-slate-50/70 dark:bg-slate-900/30">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs uppercase tracking-wider text-muted-foreground font-bold">Important Notice</CardTitle>
                        </CardHeader>
                        <CardContent class="text-xs text-muted-foreground space-y-2">
                            <p>
                                1. All application and acceptance fees paid to Mewar International University are non-refundable.
                            </p>
                            <p>
                                2. Inconsistency in names across credentials or falsification of academic documents will result in immediate offer withdrawal.
                            </p>
                            <p>
                                3. Original credentials must be presented during physical verification and clearance.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </ApplicantLayout>
</template>
