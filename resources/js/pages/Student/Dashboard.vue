<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { 
    GraduationCap, 
    BookOpen, 
    CreditCard, 
    Activity, 
    CalendarDays, 
    Clock, 
    AlertCircle, 
    IdCard, 
    Calendar, 
    CalendarClock, 
    MapPin, 
    FileText, 
    Home, 
    Megaphone, 
    Download, 
    Pin, 
    Lock, 
    ShieldAlert,
    FlaskConical,
    Award,
    UserCheck,
    ChevronRight,
    CheckCircle2,
    ArrowRight,
    ShieldCheck,
    Sparkles,
    Building2,
    Layers,
    FileCheck
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    student?: any;
    user?: any;
    isPostgraduate?: boolean;
    pgDossier?: any;
    isProfileComplete?: boolean;
    isReturningStudent?: boolean;
    schoolFeeStatus?: string; // 'paid', 'partial', 'pending', 'cancelled', etc.
    showRegistrationNotification?: boolean;
    registrationMessage?: string;
    isRegistrationActive?: boolean;
    showHostelNotification?: boolean;
    hostelNotificationMessage?: string;
    stats?: {
        cgpa: string;
        totalUnits: number;
        level: string;
        status: string;
        session: string;
        semester: string;
    };
    timetable?: Array<any>;
    pendingSession?: string;
    announcements?: Array<any>;
    activeSession?: {
        id: string;
        name: string;
        school_fee_payment_enabled: boolean;
        late_payment_deadline: string | null;
        late_fee_amount: number;
    };
}>();

const pinnedAnnouncements = computed(() => {
    return props.announcements?.filter(bulletin => bulletin.is_pinned) || [];
});

const hasPaidEnough = computed(() => {
    return props.schoolFeeStatus === 'paid' || props.schoolFeeStatus === 'partial';
});

const isSecondSemester = computed(() => {
    return props.stats?.semester && (props.stats.semester.toLowerCase().includes('second') || props.stats.semester === '2');
});

const paymentStatusText = computed(() => {
    switch (props.schoolFeeStatus) {
        case 'paid': return 'Fully Paid';
        case 'partial': return 'Partially Paid';
        case 'pending': return 'Unpaid (Pending)';
        case 'cancelled': return 'Cancelled';
        default: return 'Unpaid';
    }
});

const isLateFeeOverdue = computed(() => {
    if (!props.activeSession?.late_payment_deadline) return false;
    return new Date(props.activeSession.late_payment_deadline) < new Date();
});

const formattedLateDeadline = computed(() => {
    if (!props.activeSession?.late_payment_deadline) return '';
    try {
        return new Date(props.activeSession.late_payment_deadline).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return '';
    }
});

const formatTime = (time: string | null) => {
    if (!time) return '---';
    const parts = time.split(':');
    let h = parseInt(parts[0], 10);
    if (isNaN(h)) return time;
    const m = parts[1] || '00';
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h.toString().padStart(2, '0')}:${m} ${ampm}`;
};

const getClassesForDay = (day: string) => {
    if (!props.timetable) return [];
    return props.timetable.filter((t: any) => t.day === day);
};

const getTodaysClasses = () => {
    const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
    return getClassesForDay(today);
};

console.log('Student', props.student?.program)

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/student/dashboard',
    },
];

const greeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good Morning';
    if (hour < 18) return 'Good Afternoon';
    return 'Good Evening';
};
</script>

<template>
    <Head title="Student Dashboard" />

    <StudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-6">
            <!-- ========================================== -->
            <!-- POSTGRADUATE SCHOLAR DASHBOARD EXPERIENCE -->
            <!-- ========================================== -->
            <template v-if="isPostgraduate">
                <!-- PG Executive Welcome Banner -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 shadow-xl border border-slate-800">
                    <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6 text-white text-center sm:text-left">
                        <img 
                            :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : `https://ui-avatars.com/api/?name=${user?.name}&background=1e1b4b&color=ffffff`" 
                            alt="Scholar Photo" 
                            class="h-24 w-24 sm:h-20 sm:w-20 rounded-full border-4 border-indigo-500/30 object-cover shadow-lg flex-shrink-0"
                        />
                        <div class="flex-1 min-w-0 w-full">
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                                <Badge variant="outline" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-200 border-indigo-400/30">
                                    School of Postgraduate Studies (SPGS)
                                </Badge>
                                
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white leading-tight break-words font-serif">
                                {{ greeting() }}, {{ user?.name }}
                            </h1>
                            <p class="mt-1.5 text-sm sm:text-base text-indigo-200 break-words font-medium">
                                {{ student?.matriculation_number || 'Matric Pending' }} &bull; 
                         
                                {{ student?.program?.name || 'Postgraduate Program' }}
                            </p>
                            <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-2">
                                <div class="inline-flex items-center rounded-full bg-white/10 border border-white/15 px-3 py-1 text-xs backdrop-blur-sm text-indigo-100 font-medium">
                                    <GraduationCap class="mr-1.5 h-3.5 w-3.5 text-indigo-300" />
                                    Level {{ stats?.level }} &bull; {{ pgDossier?.degree_type || 'PG' }}
                                </div>
                                <div class="inline-flex items-center rounded-full bg-white/10 border border-white/15 px-3 py-1 text-xs backdrop-blur-sm text-indigo-100 font-medium">
                                    <CalendarDays class="mr-1.5 h-3.5 w-3.5 text-indigo-300" />
                                    {{ stats?.session }} Session &bull; {{ stats?.semester }}
                                </div>
                                <div v-if="pgDossier?.candidacy_expires_at" class="inline-flex items-center rounded-full bg-amber-500/20 border border-amber-400/30 text-amber-200 px-3 py-1 text-xs backdrop-blur-sm font-medium">
                                    <Clock class="mr-1.5 h-3.5 w-3.5 text-amber-300" />
                                    Residency through {{ pgDossier.candidacy_expires_at }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative Glow -->
                    <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
                </div>

                <!-- PG Research & Dissertation Trajectory Showcase Card -->
                <Card class="border border-border/70 shadow-sm rounded-2xl bg-card overflow-hidden">
                    <div class="p-6 bg-gradient-to-r from-muted/40 via-background to-muted/20 border-b border-border/60">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <FlaskConical class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <span class="text-xs uppercase tracking-wider font-bold text-muted-foreground">Postgraduate Dissertation & Candidacy</span>
                                        <h2 class="text-lg sm:text-xl font-bold text-foreground">
                                            {{ pgDossier?.research_project?.title || 'Research Topic Not Registered' }}
                                        </h2>
                                    </div>
                                </div>
                            </div>

                            <Button size="sm" class="shrink-0 font-semibold shadow-sm" as-child>
                                <Link :href="route('student.research.index')">
                                    Open Research Hub
                                    <ChevronRight class="w-4 h-4 ml-1" />
                                </Link>
                            </Button>
                        </div>

                        <!-- Trajectory Progress Bar (if project registered) -->
                        <div v-if="pgDossier?.research_project" class="mt-5 space-y-2">
                            <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                    Milestone Progress: <strong>{{ pgDossier.research_project.milestones_completed }} of {{ pgDossier.research_project.milestones_count }} Cleared</strong>
                                </span>
                                <span class="font-bold text-foreground">{{ pgDossier.research_project.progress_percent }}%</span>
                            </div>
                            <div class="w-full bg-muted/60 h-2.5 rounded-full overflow-hidden border border-border/50">
                                <div 
                                    class="bg-primary h-full rounded-full transition-all duration-500 ease-out" 
                                    :style="{ width: `${pgDossier.research_project.progress_percent}%` }"
                                ></div>
                            </div>
                            <div v-if="pgDossier.research_project.current_milestone_title" class="pt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                <span class="font-semibold text-foreground">Next Active Milestone:</span>
                                <Badge variant="outline" class="text-[11px] font-mono border-primary/30 text-primary bg-primary/5">
                                    {{ pgDossier.research_project.current_milestone_title }}
                                </Badge>
                            </div>
                        </div>

                        <div v-else class="mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-start gap-3">
                            <AlertCircle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                            <div class="flex-1">
                                <p class="font-bold">No Capstone / Research Topic Registered Yet</p>
                                <p class="mt-0.5">Register your dissertation title, abstract, and research concentration in the Research Hub to initialize your supervisory committee and milestones.</p>
                            </div>
                            <Button size="sm" variant="outline" class="shrink-0 text-xs border-amber-300 dark:border-amber-700 hover:bg-amber-100 dark:hover:bg-amber-900/40" as-child>
                                <Link :href="route('student.research.index')">Register Topic</Link>
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- PG Stats Grid -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- CGPA & NUC Standing -->
                    <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">SPGS CGPA</span>
                            <GraduationCap class="h-4 w-4 text-primary" />
                        </div>
                        <div class="text-3xl font-extrabold font-mono text-foreground tracking-tight">{{ stats?.cgpa }}</div>
                        <div class="flex items-center gap-1.5 text-xs font-semibold" :class="Number(stats?.cgpa) >= 3.0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                            <CheckCircle2 class="w-3.5 h-3.5" />
                            <span>{{ stats?.status }}</span>
                        </div>
                    </div>

                    <!-- Research Logbook -->
                    <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Digital Logbook</span>
                            <FlaskConical class="h-4 w-4 text-blue-500" />
                        </div>
                        <div class="text-3xl font-extrabold font-mono text-foreground tracking-tight">{{ pgDossier?.logs_count || 0 }} <span class="text-sm font-sans font-medium text-muted-foreground">Entries</span></div>
                        <p class="text-xs text-muted-foreground truncate">
                            {{ pgDossier?.last_log_date ? `Last entry: ${pgDossier.last_log_date}` : 'No research logs recorded' }}
                        </p>
                    </div>

                    <!-- Publications -->
                    <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Peer-Reviewed Papers</span>
                            <Award class="h-4 w-4 text-purple-500" />
                        </div>
                        <div class="text-3xl font-extrabold font-mono text-foreground tracking-tight">{{ pgDossier?.publications_count || 0 }} <span class="text-sm font-sans font-medium text-muted-foreground">Published</span></div>
                        <p class="text-xs text-muted-foreground">Indexed for SPGS Defense clearance</p>
                    </div>

                    <!-- Fees / Financial Clearance -->
                    <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Financial Clearance</span>
                            <CreditCard class="h-4 w-4 text-emerald-500" />
                        </div>
                        <div class="text-2xl font-bold font-mono text-foreground tracking-tight">{{ paymentStatusText }}</div>
                        <p v-if="!hasPaidEnough" class="text-xs text-red-500 font-semibold">Action Required for Session</p>
                        <p v-else class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">Session Standing Cleared</p>
                    </div>
                </div>

                <!-- PG 2-Column Core Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7 items-start">
                    <!-- Left: PG Action Center (Col 4) -->
                    <div class="lg:col-span-4 rounded-2xl border border-border/70 bg-card shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-border/60">
                            <h3 class="font-bold text-lg text-foreground">Postgraduate Academic & Research Actions</h3>
                            <p class="text-xs text-muted-foreground mt-0.5">Direct portals for thesis governance, coursework, and SPGS clearances.</p>
                        </div>

                        <div class="grid gap-4 p-6 sm:grid-cols-2">
                            <!-- Research Hub Action -->
                            <div class="group flex flex-col justify-between rounded-xl border border-border/60 p-4 hover:border-primary/40 hover:bg-muted/30 transition-all">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                            <FlaskConical class="w-3.5 h-3.5" />
                                        </div>
                                        <h4 class="font-bold text-sm text-foreground">Research & Thesis Hub</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Manage dissertation drafts, plagiarism screening, bi-weekly logbooks, and APRs.
                                    </p>
                                </div>
                                <Link :href="route('student.research.index')" class="mt-4 inline-flex items-center text-xs font-bold text-primary hover:underline">
                                    Enter Research Hub &rarr;
                                </Link>
                            </div>

                            <!-- Course Registration Action -->
                            <div class="group flex flex-col justify-between rounded-xl border border-border/60 p-4 hover:border-primary/40 hover:bg-muted/30 transition-all">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center">
                                            <BookOpen class="w-3.5 h-3.5" />
                                        </div>
                                        <h4 class="font-bold text-sm text-foreground">Course Registration</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Register advanced doctoral/masters coursework, departmental seminars, and research credits.
                                    </p>
                                </div>
                                <Link :href="route('student.courses.index')" class="mt-4 inline-flex items-center text-xs font-bold text-blue-600 hover:underline">
                                    Register Courses &rarr;
                                </Link>
                            </div>

                            <!-- Payments Action -->
                            <div class="group flex flex-col justify-between rounded-xl border border-border/60 p-4 hover:border-primary/40 hover:bg-muted/30 transition-all">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                                            <CreditCard class="w-3.5 h-3.5" />
                                        </div>
                                        <h4 class="font-bold text-sm text-foreground">Bursary & Payments</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Settle composite session tuition, bench fees, and external examiner evaluation fees.
                                    </p>
                                </div>
                                <Link :href="route('student.payments.index')" class="mt-4 inline-flex items-center text-xs font-bold text-emerald-600 hover:underline">
                                    Bursary Center &rarr;
                                </Link>
                            </div>

                            <!-- Results & Transcripts Action -->
                            <div class="group flex flex-col justify-between rounded-xl border border-border/60 p-4 hover:border-primary/40 hover:bg-muted/30 transition-all">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center">
                                            <GraduationCap class="w-3.5 h-3.5" />
                                        </div>
                                        <h4 class="font-bold text-sm text-foreground">Results & Broadsheet</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Inspect graded semester results, weighted GPAs, and Viva defense verdicts.
                                    </p>
                                </div>
                                <Link :href="route('student.results.index')" class="mt-4 inline-flex items-center text-xs font-bold text-purple-600 hover:underline">
                                    View Results &rarr;
                                </Link>
                            </div>

                            <!-- Official Transcript Forwarding Label -->
                            <div class="group flex flex-col justify-between rounded-xl border border-border/60 p-4 hover:border-primary/40 hover:bg-muted/30 transition-all">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                            <FileCheck class="w-3.5 h-3.5" />
                                        </div>
                                        <h4 class="font-bold text-sm text-foreground">Transcript Request Label</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Download the official SPGS transcript routing slip for your former institution.
                                    </p>
                                </div>
                                <a :href="route('applicant.apply.transcript_label')" target="_blank" class="mt-4 inline-flex items-center text-xs font-bold text-amber-600 hover:underline">
                                    Download Cover Sheet &rarr;
                                </a>
                            </div>

                            <!-- Postgraduate ID Card -->
                            <div class="group flex flex-col justify-between rounded-xl border border-border/60 p-4 hover:border-primary/40 hover:bg-muted/30 transition-all">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-500/10 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                                            <IdCard class="w-3.5 h-3.5" />
                                        </div>
                                        <h4 class="font-bold text-sm text-foreground">Scholar ID Card</h4>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Access and print your verified institutional postgraduate student identity card.
                                    </p>
                                </div>
                                <a :href="route('student.id_card.show')" target="_blank" class="mt-4 inline-flex items-center text-xs font-bold text-foreground hover:underline">
                                    View ID Card &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Supervisory Committee & Candidacy Dossier (Col 3) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Supervisory Panel Card -->
                        <Card class="border border-border/70 rounded-2xl bg-card shadow-sm overflow-hidden">
                            <CardHeader class="pb-3 border-b border-border/60">
                                <CardTitle class="text-sm font-bold flex items-center gap-2">
                                    <UserCheck class="w-4 h-4 text-primary" />
                                    Supervisory Committee
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-5 space-y-4 text-xs">
                                <div v-if="pgDossier?.research_project?.major_supervisor" class="space-y-3">
                                    <div class="p-3 rounded-xl bg-muted/20 border border-border/50 space-y-1">
                                        <span class="text-[10px] uppercase font-bold text-primary tracking-wider block">Major Supervisor</span>
                                        <p class="font-bold text-sm text-foreground">{{ pgDossier.research_project.major_supervisor }}</p>
                                        <p v-if="pgDossier.research_project.major_supervisor_designation" class="text-muted-foreground">
                                            {{ pgDossier.research_project.major_supervisor_designation }}
                                        </p>
                                    </div>

                                    <div v-if="pgDossier.research_project.co_supervisor" class="p-3 rounded-xl bg-muted/20 border border-border/50 space-y-1">
                                        <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block">Co-Supervisor</span>
                                        <p class="font-bold text-sm text-foreground">{{ pgDossier.research_project.co_supervisor }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted-foreground">
                                    <p class="font-medium">No supervisors appointed yet.</p>
                                    <p class="text-[11px] mt-1">Supervisors are confirmed by the Departmental PG Committee upon topic proposal.</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Candidacy Governance & Petitions Card -->
                        <Card class="border border-border/70 rounded-2xl bg-card shadow-sm overflow-hidden">
                            <CardHeader class="pb-3 border-b border-border/60">
                                <CardTitle class="text-sm font-bold flex items-center gap-2">
                                    <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                    Candidacy Governance
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="p-5 space-y-3 text-xs">
                                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                                    <span class="text-muted-foreground">SPGS File Ref</span>
                                    <span class="font-mono font-bold text-foreground">{{ pgDossier?.spgs_file_number || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                                    <span class="text-muted-foreground">Degree Level</span>
                                    <span class="font-semibold text-foreground">Level {{ stats?.level }} ({{ pgDossier?.degree_type || 'PG' }})</span>
                                </div>
                                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                                    <span class="text-muted-foreground">Candidacy Status</span>
                                    <Badge variant="outline" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300">
                                        {{ pgDossier?.candidacy_status || 'Active' }}
                                    </Badge>
                                </div>
                                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                                    <span class="text-muted-foreground">Residency Limit</span>
                                    <span class="font-semibold text-foreground">{{ pgDossier?.candidacy_expires_at || 'In Normal Residency' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-1.5">
                                    <span class="text-muted-foreground">Active SPGS Petitions</span>
                                    <span class="font-bold text-foreground">{{ pgDossier?.pending_requests_count || 0 }} Pending</span>
                                </div>

                                <div class="pt-2">
                                    <Button variant="outline" size="sm" class="w-full text-xs font-semibold" as-child>
                                        <Link :href="route('student.research.index')">
                                            Submit Sundry Petition (Extension/Title)
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Announcements & Bulletins -->
                        <Card class="border border-border/70 rounded-2xl bg-card shadow-sm overflow-hidden">
                            <CardHeader class="flex flex-row items-center justify-between pb-3 border-b border-border/60">
                                <CardTitle class="text-sm font-bold flex items-center gap-2">
                                    <Megaphone class="w-4 h-4 text-primary" /> SPGS Bulletins
                                </CardTitle>
                                <Link href="/student/announcements" class="text-xs text-primary hover:underline font-medium">
                                    View All
                                </Link>
                            </CardHeader>
                            <CardContent class="p-5 space-y-3.5">
                                <div v-if="!announcements || announcements.length === 0" class="text-center py-4 text-muted-foreground text-xs">
                                    No bulletins published.
                                </div>
                                <div v-else class="space-y-3">
                                    <div v-for="bulletin in announcements" :key="bulletin.id" class="text-xs space-y-1 pb-2 border-b last:border-0 last:pb-0">
                                        <h4 class="font-bold text-foreground line-clamp-1">{{ bulletin.title }}</h4>
                                        <p class="text-muted-foreground line-clamp-2">{{ bulletin.content || 'Scanned memo attached.' }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </template>

            <!-- ============================================== -->
            <!-- UNDERGRADUATE SCHOLAR DASHBOARD EXPERIENCE      -->
            <!-- ============================================== -->
            <template v-else>
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-6 sm:p-8 shadow-lg">
                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6 text-white text-center sm:text-left">
                    <img 
                        :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : `https://ui-avatars.com/api/?name=${user?.name}&background=random`" 
                        alt="Profile Photo" 
                        class="h-24 w-24 sm:h-20 sm:w-20 rounded-full border-4 border-white/30 object-cover shadow-md flex-shrink-0"
                    />
                    <div class="flex-1 min-w-0 w-full">
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white leading-tight break-words">{{ greeting() }}, {{ user?.name.split(' ')[0] }}!</h1>
                        <p class="mt-2 text-sm sm:text-base text-blue-100 break-words">
                            {{ student?.matriculation_number || 'Matriculation Pending' }} &bull; {{ student?.program?.name || 'Program N/A' }}
                        </p>
                        <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-2">
                            <div class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs sm:text-sm backdrop-blur-sm">
                                <CalendarDays class="mr-2 h-4 w-4" />
                                {{ stats?.session }} Session &bull; {{ stats?.semester }}
                            </div>
                            <div v-if="pendingSession" class="inline-flex items-center rounded-full bg-amber-500/30 border border-amber-400/40 text-amber-100 px-3 py-1 text-xs sm:text-sm backdrop-blur-sm font-medium">
                                <Clock class="mr-2 h-4 w-4 text-amber-300" />
                                Promotion Pending to {{ pendingSession }}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Decorative Circle -->
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <!-- Pending Promotion Notification -->
            <div v-if="pendingSession" class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm flex items-start gap-4 text-amber-900">
                <div class="rounded-full bg-amber-100 p-2">
                    <AlertCircle class="h-6 w-6 text-amber-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-800">Academic Promotion Pending</h3>
                    <p class="mt-1 text-sm text-amber-700">
                        Your academic promotion to the **{{ pendingSession }}** session is currently pending. Please resolve any outstanding school fees for the previous session ({{ stats?.session }}) to automatically complete your promotion and register courses.
                    </p>
                </div>
            </div>

            <!-- School Fee Payments Closed Notification -->
            <div v-if="activeSession && !activeSession.school_fee_payment_enabled && schoolFeeStatus !== 'paid'" class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm flex items-start gap-4 text-red-900">
                <div class="rounded-full bg-red-100 p-2">
                    <AlertCircle class="h-6 w-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800">School Fee Payments Suspended</h3>
                    <p class="mt-1 text-sm text-red-700">
                        School fee payment for the <strong>{{ activeSession.name }}</strong> session is currently disabled. Please contact the Bursary department for assistance.
                    </p>
                </div>
            </div>

            <!-- Unpaid School Fees Notification -->
            <div v-if="activeSession && activeSession.school_fee_payment_enabled && schoolFeeStatus !== 'paid' && schoolFeeStatus !== 'partial'" class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm flex items-start gap-4 text-red-900">
                <div class="rounded-full bg-red-100 p-2">
                    <CreditCard class="h-6 w-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800">Outstanding School Fees</h3>
                    <p class="mt-1 text-sm text-red-700">
                        You have not paid your school fees for the <strong>{{ activeSession.name }}</strong> session. Please generate your invoice and make a payment to secure your student registration.
                    </p>
                    <Link :href="route('student.payments.index')" class="mt-3 inline-flex items-center text-sm font-medium text-red-800 hover:text-red-900 underline underline-offset-4">
                        Pay Fees Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Late Payment Fine Notification -->
            <div v-if="isReturningStudent && activeSession?.late_payment_deadline && schoolFeeStatus !== 'paid' && schoolFeeStatus !== 'partial'" 
                :class="[
                    'rounded-xl border p-4 shadow-sm flex items-start gap-4',
                    isLateFeeOverdue 
                        ? 'border-red-200 bg-red-50 text-red-900' 
                        : 'border-orange-200 bg-orange-50 text-orange-900'
                ]"
            >
                <div :class="['rounded-full p-2', isLateFeeOverdue ? 'bg-red-100' : 'bg-orange-100']">
                    <CalendarClock :class="['h-6 w-6', isLateFeeOverdue ? 'text-red-600' : 'text-orange-600']" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">{{ isLateFeeOverdue ? 'Late Registration Fine Applied' : 'Late Payment Deadline Warning' }}</h3>
                    <p :class="['mt-1 text-sm', isLateFeeOverdue ? 'text-red-700' : 'text-orange-700']">
                        <span v-if="isLateFeeOverdue">
                            The late registration deadline of <strong>{{ formattedLateDeadline }}</strong> has passed. A late fine of <strong>₦{{ new Intl.NumberFormat().format(activeSession.late_fee_amount) }}</strong> has been added to your unpaid school fee invoice.
                        </span>
                        <span v-else>
                            Please note that the deadline to pay school fees without penalty is <strong>{{ formattedLateDeadline }}</strong>. A late registration fine of <strong>₦{{ new Intl.NumberFormat().format(activeSession.late_fee_amount) }}</strong> will be applied after this date.
                        </span>
                    </p>
                </div>
            </div>

            <!-- Outstanding Second Semester School Fees (Partial Payment) -->
            <div v-if="activeSession && isSecondSemester && schoolFeeStatus === 'partial'" class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 shadow-sm flex items-start gap-4 text-amber-950 mb-4">
                <div class="rounded-full bg-amber-100 p-2">
                    <ShieldAlert class="h-6 w-6 text-amber-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-900">Second Semester Registration Locked</h3>
                    <p class="mt-1 text-sm text-amber-800">
                        You have only made a partial payment of your school fees for the current session. Course registration/editing and exam card downloads for the Second Semester are locked until your payment is fully cleared.
                    </p>
                    <Link :href="route('student.payments.index')" class="mt-3 inline-flex items-center text-sm font-medium text-amber-850 hover:text-amber-900 underline underline-offset-4">
                        Clear Outstanding Balance Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Registration Notification -->
            <div v-if="showRegistrationNotification" 
                :class="[
                    'rounded-xl border p-4 shadow-sm flex items-start gap-4',
                    isRegistrationActive 
                        ? 'border-blue-200 bg-blue-50 text-blue-900' 
                        : 'border-amber-200 bg-amber-50 text-amber-900'
                ]"
            >
                <div :class="['rounded-full p-2', isRegistrationActive ? 'bg-blue-100' : 'bg-amber-100']">
                    <AlertCircle :class="['h-6 w-6', isRegistrationActive ? 'text-blue-600' : 'text-amber-600']" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">{{ isRegistrationActive ? 'Course Registration Open' : 'Course Registration Notice' }}</h3>
                    <p :class="['mt-1 text-sm', isRegistrationActive ? 'text-blue-700' : 'text-amber-700']">
                        {{ registrationMessage }}
                    </p>
                    <Link v-if="isRegistrationActive && !(isSecondSemester && schoolFeeStatus === 'partial')" :href="route('student.courses.create')" class="mt-3 inline-flex items-center text-sm font-medium text-blue-800 hover:text-blue-900 underline underline-offset-4">
                        Register Courses Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Hostel Booking Notification -->
            <div v-if="showHostelNotification" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm flex items-start gap-4 text-emerald-900">
                <div class="rounded-full bg-emerald-100 p-2">
                    <Home class="h-6 w-6 text-emerald-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">Hostel Booking Open</h3>
                    <p class="mt-1 text-sm text-emerald-700">
                        {{ hostelNotificationMessage }}
                    </p>
                    <Link :href="route('student.accommodation.index')" class="mt-3 inline-flex items-center text-sm font-medium text-emerald-800 hover:text-emerald-900 underline underline-offset-4">
                        Book Your Room Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Pinned Announcements Banner -->
            <div v-if="pinnedAnnouncements.length > 0" class="space-y-3">
                <div 
                    v-for="bulletin in pinnedAnnouncements" 
                    :key="bulletin.id" 
                    class="rounded-xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm flex items-start gap-4 text-amber-950 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100"
                >
                    <div class="rounded-full bg-amber-100 dark:bg-amber-950 p-2">
                        <Pin class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <h3 class="font-bold text-base flex items-center gap-2">
                                {{ bulletin.title }}
                                <Badge class="bg-amber-600 hover:bg-amber-700 text-white border-0 text-[10px] py-0.5 px-2">Important Notice</Badge>
                            </h3>
                            <span class="text-xs opacity-75 whitespace-nowrap">{{ new Date(bulletin.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</span>
                        </div>
                        <div v-if="bulletin.content" class="mt-2 text-sm opacity-90 leading-relaxed" v-html="bulletin.content"></div>
                        <div v-if="bulletin.document_path" class="mt-3">
                            <a 
                                :href="`/storage/${bulletin.document_path}`" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-900 hover:text-amber-950 dark:text-amber-300 dark:hover:text-amber-200 underline underline-offset-4"
                            >
                                <Download class="h-4 w-4" /> View Scanned Document Attachment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
 
            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Current CGPA</h3>
                        <GraduationCap class="h-4 w-4 text-primary" />
                    </div>
                    <div class="text-2xl font-bold">{{ stats?.cgpa }}</div>
                    <p class="text-xs text-muted-foreground">{{ stats?.status }}</p>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Registered Units</h3>
                        <BookOpen class="h-4 w-4 text-blue-500" />
                    </div>
                    <div class="text-2xl font-bold">{{ stats?.totalUnits }}</div>
                    <p class="text-xs text-muted-foreground">Units this session</p>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Current Level</h3>
                        <Activity class="h-4 w-4 text-green-500" />
                    </div>
                    <div class="text-2xl font-bold">{{ stats?.level }}</div>
                    <p class="text-xs text-muted-foreground">{{ stats?.session }}</p>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Pending Fees</h3>
                        <CreditCard class="h-4 w-4 text-red-500" />
                    </div>
                    <div class="text-2xl font-bold">{{ paymentStatusText }}</div>
                     <p v-if="!hasPaidEnough" class="text-xs text-red-500 font-medium">Action Required</p>
                     <p v-else-if="schoolFeeStatus === 'partial'" class="text-xs text-blue-600 font-medium">Split Payment Active</p>
                     <p v-else class="text-xs text-green-600 font-medium">Cleared</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                <!-- Action Center -->
                <div class="col-span-4 rounded-xl border bg-card shadow-sm">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg">Quick Actions</h3>
                        <p class="text-sm text-muted-foreground">Common tasks you might need to perform.</p>
                    </div>
                    <div class="grid gap-4 p-6 pt-0 sm:grid-cols-2">
                        <!-- Profile Action -->
                        <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <div :class="['h-2 w-2 rounded-full', isProfileComplete ? 'bg-green-500' : 'bg-yellow-500']"></div>
                                    Update Profile
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ isProfileComplete ? 'Your profile is up to date.' : 'Complete your bio data to proceed.' }}
                                </p>
                            </div>
                            <Link :href="route('student.profile.edit')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                {{ isProfileComplete ? 'View Profile' : 'Complete Profile' }} &rarr;
                            </Link>
                        </div>

                        <!-- Course Reg Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                     <BookOpen class="h-4 w-4 text-muted-foreground" />
                                    Course Registration
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Register your courses for the current semester.
                                </p>
                            </div>
                            <Link :href="route('student.courses.index')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                Register Courses &rarr;
                            </Link>
                        </div>

                         <!-- Payment Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                                    Make Payment
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Pay school fees and other outstanding invoices.
                                </p>
                            </div>
                            <Link :href="route('student.payments.index')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                Go to Payments &rarr;
                            </Link>
                        </div>

                        <!-- Results Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <GraduationCap class="h-4 w-4 text-muted-foreground" />
                                    Check Results
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    View your academic performance and transcript.
                                </p>
                            </div>
                            <Link :href="route('student.results.index')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                View Results &rarr;
                            </Link>
                        </div>

                        <!-- ID Card Action -->
                        <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <IdCard class="h-4 w-4 text-muted-foreground" />
                                    Student ID Card
                                </h4>
                                <p v-if="hasPaidEnough && student?.passport_photo_path" class="text-sm text-muted-foreground mt-1">
                                    View and print your Student ID Card.
                                </p>
                                <p v-else-if="!hasPaidEnough" class="text-sm text-red-500 mt-1">
                                    Pay School Fees to access ID Card.
                                </p>
                                <p v-else class="text-sm text-yellow-600 mt-1">
                                    Upload Passport Photo to access ID Card.
                                </p>
                            </div>
                            
                            <div v-if="hasPaidEnough && student?.passport_photo_path">
                                <a :href="route('student.id_card.show')" target="_blank" class="mt-4 block text-sm font-medium text-primary underline-offset-4 hover:underline">
                                    View ID Card &rarr;
                                </a>
                            </div>
                             <div v-else-if="!hasPaidEnough">
                                <Link :href="route('student.payments.index')" class="mt-4 text-sm font-medium text-red-600 underline-offset-4 hover:underline">
                                    Pay Now &rarr;
                                </Link>
                            </div>
                             <div v-else>
                                <Link :href="route('student.profile.edit')" class="mt-4 text-sm font-medium text-yellow-600 underline-offset-4 hover:underline">
                                    Upload Photo &rarr;
                                </Link>
                            </div>
                        </div>
                         <!-- Admission Letter Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                    Admission Letter
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Download your official admission letter.
                                </p>
                            </div>
                            <a :href="route('student.admission_letter.download')" target="_blank" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                Download PDF &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity / Notifications -->
                <!-- Recent Activity / Notifications -->
                <div class="col-span-3 space-y-6">
                     <!-- Today's Schedule (Mini) -->
                     <!-- Today's Schedule (Mini) -->
                     <Card class="bg-indigo-900 text-white border-0 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-800/50 rounded-full blur-3xl -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-purple-600/30 rounded-full blur-2xl -ml-5 -mb-5"></div>
                        
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2 text-indigo-100 relative z-10">
                                    <CalendarClock class="w-5 h-5" /> Today's Schedule
                                </CardTitle>
                                <!-- View Full Button will go here -->
                            </div>
                        </CardHeader>
                        <CardContent class="relative z-10 space-y-3">
                             <div v-if="getTodaysClasses().length === 0" class="text-center py-6 text-indigo-200/60">
                                <div class="bg-indigo-800/50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <Calendar class="w-6 h-6" />
                                </div>
                                <p class="text-sm">No classes scheduled for today.</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div v-for="(cls, index) in getTodaysClasses()" :key="cls.id" 
                                    class="group flex items-start gap-3 bg-white/10 p-3 rounded-lg border border-indigo-400/20 hover:bg-white/20 transition-colors"
                                >
                                    <div class="flex flex-col items-center bg-indigo-950/50 rounded p-2 min-w-[3.5rem] border border-indigo-400/10">
                                        <span class="text-xs font-bold">{{ formatTime(cls.start_time) }}</span>
                                        <div class="w-px h-2 bg-indigo-400/30 my-0.5"></div>
                                        <span class="text-[10px] opacity-70">{{ formatTime(cls.end_time) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <h4 class="font-bold text-white truncate pr-2">{{ cls.course.title }}</h4>
                                            <Badge variant="outline" class="text-[10px] h-5 border-indigo-300/30 text-indigo-100 bg-indigo-500/20">
                                                {{ cls.course.code }}
                                            </Badge>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1.5 text-xs text-indigo-200">
                                            <span class="flex items-center gap-1"><MapPin class="w-3 h-3 opacity-70" /> {{ cls.venue }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Latest Announcements Card -->
                    <Card class="border border-border bg-card shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-3 border-b">
                            <CardTitle class="text-md font-semibold flex items-center gap-2 text-foreground">
                                <Megaphone class="w-4 h-4 text-primary" /> Latest Announcements
                            </CardTitle>
                            <Link href="/student/announcements" class="text-xs text-primary hover:underline font-medium">
                                View All
                            </Link>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-4">
                            <div v-if="!announcements || announcements.length === 0" class="text-center py-6 text-muted-foreground text-sm">
                                No announcements published.
                            </div>
                            <div v-else class="space-y-3.5">
                                <div v-for="bulletin in announcements" :key="bulletin.id" class="relative pl-4 border-l-2 border-primary/20 pb-3 last:pb-0 last:border-0">
                                    <!-- Pin Indicator -->
                                    <div v-if="bulletin.is_pinned" class="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full bg-amber-500 ring-4 ring-background"></div>
                                    
                                    <h4 class="font-semibold text-sm text-foreground line-clamp-1 flex items-center gap-1.5">
                                        {{ bulletin.title }}
                                        <Badge v-if="bulletin.is_pinned" variant="outline" class="h-4 px-1 text-[9px] border-amber-500 text-amber-600 bg-amber-500/5">
                                            Pinned
                                        </Badge>
                                        <Badge v-if="bulletin.document_path" variant="outline" class="h-4 px-1 text-[9px] border-blue-500 text-blue-600 bg-blue-500/5">
                                            File
                                        </Badge>
                                    </h4>
                                    <p class="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                        {{ bulletin.content || 'Scanned document attached.' }}
                                    </p>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[10px] text-muted-foreground">
                                            {{ new Date(bulletin.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                        </span>
                                        <a 
                                            v-if="bulletin.document_path"
                                            :href="`/storage/${bulletin.document_path}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-primary hover:underline"
                                        >
                                            <Download class="h-3 w-3" /> Scanned File
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="rounded-xl border bg-card shadow-sm">
                    <div class="p-6 border-b">
                        <h3 class="font-semibold text-lg">Notifications</h3>
                    </div>
                    <div class="p-6 space-y-4">
                         <div v-if="!hasPaidEnough || schoolFeeStatus === 'partial'" class="flex items-start gap-3 rounded-lg border" :class="schoolFeeStatus === 'partial' ? 'border-blue-100 bg-blue-50' : 'border-red-100 bg-red-50'">
                            <CreditCard class="h-4 w-4 mt-0.5" :class="schoolFeeStatus === 'partial' ? 'text-blue-600' : 'text-red-600'" />
                            <div class="text-sm">
                                <p class="font-medium" :class="schoolFeeStatus === 'partial' ? 'text-blue-900' : 'text-red-900'">
                                    {{ schoolFeeStatus === 'partial' ? 'Fee Balance Outstanding' : 'School Fees Unpaid' }}
                                </p>
                                <p :class="schoolFeeStatus === 'partial' ? 'text-blue-700' : 'text-red-700'">
                                    {{ schoolFeeStatus === 'partial' ? 'You have a balance remaining on your split payment.' : 'You have not paid your school fees for this session.' }}
                                </p>
                                <Link :href="route('student.payments.index')" class="mt-2 text-xs font-semibold underline" :class="schoolFeeStatus === 'partial' ? 'text-blue-800 hover:text-blue-900' : 'text-red-800 hover:text-red-900'">
                                    {{ schoolFeeStatus === 'partial' ? 'Pay Balance' : 'Pay Now' }}
                                </Link>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border p-3 text-sm">
                            <Clock class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="font-medium">Course Registration Deadline</p>
                                <p class="text-muted-foreground">Registration closes on Feb 28th, 2026.</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            </template>
        </div>
    </StudentLayout>
</template>
