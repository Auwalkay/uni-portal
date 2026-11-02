<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    GraduationCap,
    BookOpen,
    FileText,
    CheckCircle2,
    Clock,
    Upload,
    AlertCircle,
    Calendar,
    Send,
    Award,
    ShieldCheck,
    FileSpreadsheet,
    FileCheck,
    MessageSquare,
    ExternalLink,
    Building2,
    Users,
    UserCheck,
    ScrollText,
    Plus,
    X,
    Sparkles,
    ChevronRight,
    ArrowRight,
    HelpCircle,
    CreditCard,
    Lock,
} from 'lucide-vue-next';

const props = defineProps<{
    student: any;
    project?: any | null;
    logs?: any | null;
    progressReports?: any | null;
    candidacyRequests?: any | null;
    publications?: any | null;
    defaultPathway: string;
    pathwayTypes: Record<string, string>;
    milestoneFees?: Array<any>;
    schoolFeeStatus?: string;
    hasPaidFees?: boolean;
}>();

// 3 Streamlined Pillars
const activeTab = ref<'milestones' | 'activities' | 'governance'>('milestones');

// 1. Topic Registration Form
const isTopicModalOpen = ref(false);
const topicForm = useForm({
    title: props.project?.title || '',
    research_area: props.project?.research_area || '',
    abstract: props.project?.abstract || '',
    thesis_document: null as File | null,
});

const handleThesisFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        topicForm.thesis_document = target.files[0];
    }
};

const submitTopic = () => {
    topicForm.post(route('student.research.topic.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isTopicModalOpen.value = false;
        },
    });
};

// 2. Milestone Submission Form
const activeMilestone = ref<any | null>(null);
const isMilestoneModalOpen = ref(false);
const milestoneForm = useForm({
    submission_notes: '',
    plagiarism_similarity_index: '',
    document: null as File | null,
});

const openMilestoneModal = (milestone: any) => {
    activeMilestone.value = milestone;
    milestoneForm.submission_notes = milestone.submission_notes || '';
    milestoneForm.plagiarism_similarity_index = milestone.plagiarism_similarity_index != null ? String(milestone.plagiarism_similarity_index) : '';
    milestoneForm.document = null;
    isMilestoneModalOpen.value = true;
};

const handleMilestoneFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        milestoneForm.document = target.files[0];
    }
};

const submitMilestone = () => {
    if (!activeMilestone.value) return;
    milestoneForm.post(route('student.research.milestones.submit', activeMilestone.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isMilestoneModalOpen.value = false;
            milestoneForm.reset();
        },
    });
};

// 3. Digital Research Logbook Form
const isLogModalOpen = ref(false);
const logForm = useForm({
    entry_date: new Date().toISOString().split('T')[0],
    hours_spent: 4,
    work_summary: '',
    progress_made: '',
    challenges_faced: '',
});

const submitLog = () => {
    logForm.post(route('student.research.logs.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isLogModalOpen.value = false;
            logForm.reset();
        },
    });
};

// 4. Annual Progress Report (APR) Form
const isAprModalOpen = ref(false);
const aprForm = useForm({
    academic_session_id: '',
    report_period: '2026/2027 Academic Year',
    progress_summary: '',
    research_achievements: '',
    publications_during_period: '',
    challenges_and_delays: '',
    next_year_work_plan: '',
    candidate_signature: props.student?.user?.name || '',
});

const submitApr = () => {
    aprForm.post(route('student.research.progress-reports.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isAprModalOpen.value = false;
            aprForm.reset();
        },
    });
};

// 5. Candidacy Petition (Sundry Form)
const isPetitionModalOpen = ref(false);
const petitionForm = useForm({
    request_type: 'extension_of_time',
    proposed_title: '',
    extension_months: 6,
    justification_and_timeline: '',
    supporting_document: null as File | null,
});

const handlePetitionFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        petitionForm.supporting_document = target.files[0];
    }
};

const submitPetition = () => {
    petitionForm.post(route('student.research.candidacy-requests.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isPetitionModalOpen.value = false;
            petitionForm.reset();
        },
    });
};

// 6. Publication Form
const isPubModalOpen = ref(false);
const pubForm = useForm({
    article_title: '',
    journal_name: '',
    issn_isbn: '',
    doi_or_url: '',
    indexing_agency: 'Scopus / NUC-Recognized',
    publication_status: 'published',
    evidence_document: null as File | null,
});

const handlePubFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        pubForm.evidence_document = target.files[0];
    }
};

const submitPub = () => {
    pubForm.post(route('student.research.publications.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isPubModalOpen.value = false;
            pubForm.reset();
        },
    });
};

// Computed Progress Percentage
const completionRate = computed(() => {
    if (!props.project?.milestones || props.project.milestones.length === 0) return 0;
    const completed = props.project.milestones.filter((m: any) =>
        ['supervisor_approved', 'spgs_cleared', 'completed'].includes(m.status)
    ).length;
    return Math.round((completed / props.project.milestones.length) * 100);
});

// Total research hours logged
const totalHoursLogged = computed(() => {
    if (!props.logs?.data) return 0;
    return props.logs.data.reduce((acc: number, log: any) => acc + (Number(log.hours_spent) || 0), 0);
});

// Format dates
const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Smart Next-Action Status
const smartAction = computed(() => {
    if (props.hasPaidFees === false) {
        return {
            type: 'payment_required',
            title: 'School Fees Payment Required for Thesis Candidacy',
            description: 'Your research candidacy and thesis submission are locked until school fees for the current academic session are settled.',
            badge: 'Payment Required',
            badgeClass: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-200 animate-pulse',
            buttonLabel: 'Pay Academic School Fees',
            action: () => { router.visit(route('student.payments.index')); },
            icon: CreditCard,
            color: 'rose',
        };
    }

    if (!props.project) {
        return {
            type: 'register_topic',
            title: 'Welcome to SPGS Candidacy: Register Your Thesis Topic',
            description: 'Begin your research journey by submitting your provisional thesis title and research abstract for departmental committee review.',
            badge: 'Initiation Step',
            badgeClass: 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/60 dark:text-purple-200',
            buttonLabel: 'Register Thesis Topic',
            action: () => { isTopicModalOpen.value = true; },
            icon: ScrollText,
            color: 'purple',
        };
    }

    if (!props.project.major_supervisor_id && !props.project.co_supervisor_id) {
        return {
            type: 'awaiting_supervisors',
            title: 'Supervisory Committee Allocation in Progress',
            description: 'Your thesis synopsis has been recorded. The Departmental PG Committee (DPGC) is currently vetting and assigning your supervisory team.',
            badge: 'Committee Review Pending',
            badgeClass: 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/60 dark:text-blue-200',
            buttonLabel: 'Review Synopsis',
            action: () => { isTopicModalOpen.value = true; },
            icon: Users,
            color: 'blue',
        };
    }

    // Check if any milestone needs revision
    const revisionMilestone = props.project.milestones?.find((m: any) => m.status === 'revision_requested' || m.status === 'needs_revision');
    if (revisionMilestone) {
        return {
            type: 'revision_requested',
            title: `Revisions Requested: ${revisionMilestone.title}`,
            description: revisionMilestone.supervisor_remarks
                ? `Supervisor directive: "${revisionMilestone.supervisor_remarks.slice(0, 140)}..."`
                : 'Your supervisory committee has requested revisions on your research stage submission.',
            badge: 'Action Required',
            badgeClass: 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-200 animate-pulse',
            buttonLabel: 'View Supervisor Feedback',
            action: () => { activeTab.value = 'milestones'; },
            icon: AlertCircle,
            color: 'rose',
        };
    }

    // Check if oral defense is scheduled
    if (props.project.status === 'defense_scheduled' || props.project.defense_date) {
        return {
            type: 'defense_scheduled',
            title: 'Viva Voce Oral Defense Confirmed',
            description: `Scheduled for ${formatDate(props.project.defense_date)} at ${props.project.defense_venue || 'SPGS Senate Chambers'}. Panel examiners appointed.`,
            badge: 'Viva Voce Active',
            badgeClass: 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-950/60 dark:text-indigo-200',
            buttonLabel: 'View Defense Dossier',
            action: () => { activeTab.value = 'governance'; },
            icon: GraduationCap,
            color: 'indigo',
        };
    }

    // Check if any milestone is under review
    const reviewingMilestone = props.project.milestones?.find((m: any) => m.status === 'submitted' || m.status === 'under_review');
    if (reviewingMilestone) {
        return {
            type: 'under_review',
            title: `Draft Under Supervisory Assessment: ${reviewingMilestone.title}`,
            description: 'Your stage submission is currently under review by your supervisory team. Turnaround SLA: 3–5 working days.',
            badge: 'Under Evaluation',
            badgeClass: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-200',
            buttonLabel: 'View Stage Progress',
            action: () => { activeTab.value = 'milestones'; },
            icon: Clock,
            color: 'amber',
        };
    }

    // Next pending milestone
    const nextMilestone = props.project.milestones?.find((m: any) => m.status === 'pending');
    if (nextMilestone) {
        return {
            type: 'next_milestone',
            title: `Current Research Target: ${nextMilestone.title}`,
            description: 'Collaborate with your lead supervisor to prepare the methodology and draft requirements for this stage.',
            badge: 'Current Stage',
            badgeClass: 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/60 dark:text-purple-200',
            buttonLabel: 'View Stage Checklist',
            action: () => { activeTab.value = 'milestones'; },
            icon: Award,
            color: 'purple',
        };
    }

    // All milestones cleared
    if (props.project.status === 'completed') {
        return {
            type: 'completed',
            title: 'Degree Certified: Research Successfully Defended',
            description: 'Congratulations! Your thesis has been cleared by the Board of Examiners and certified for Senate award of degree.',
            badge: 'Senate Certified',
            badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-200',
            buttonLabel: 'View Broadsheet Dossier',
            action: () => { activeTab.value = 'governance'; },
            icon: CheckCircle2,
            color: 'emerald',
        };
    }

    return {
        type: 'in_progress',
        title: 'Research Candidacy in Good Standing',
        description: 'Keep your research velocity active. Remember to log your bi-weekly laboratory/fieldwork entries for APR certification.',
        badge: 'Candidacy Active',
        badgeClass: 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/60 dark:text-blue-200',
        buttonLabel: 'Record Research Hours',
        action: () => { isLogModalOpen.value = true; },
        icon: BookOpen,
        color: 'blue',
    };
});
</script>

<template>
    <Head title="Postgraduate Research & Dissertation Hub" />

    <StudentLayout>
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 font-sans">

            <!-- Executive Institutional Header Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-purple-500/20">
                <div class="absolute -right-12 -top-12 w-64 h-64 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <Badge variant="outline" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-200 border-purple-400/30">
                                School of Postgraduate Studies (SPGS)
                            </Badge>
                            <span class="text-xs text-purple-300 font-mono">
                                {{ student?.spgs_file_number || `SPGS/${student?.matriculation_number}` }}
                            </span>
                            <span class="text-xs text-purple-400">•</span>
                            <span class="text-xs text-purple-200 font-medium">
                                {{ pathwayTypes[project?.pathway_type || defaultPathway] }}
                            </span>
                        </div>

                        <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-white">
                            {{ project?.title || 'Postgraduate Research & Dissertation Hub' }}
                        </h1>
                        <p v-if="project?.research_area" class="text-xs sm:text-sm text-purple-200/90 font-medium">
                            Specialization Area: <span class="text-white">{{ project.research_area }}</span>
                        </p>
                    </div>

                    <!-- Right Quick Actions & Progress Indicator -->
                    <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end justify-between gap-3 shrink-0">
                        <div class="text-left lg:text-right space-y-1">
                            <span class="text-[11px] uppercase tracking-wider font-bold text-purple-300 block">
                                Research Trajectory
                            </span>
                            <div class="flex items-center gap-2.5">
                                <div class="w-32 sm:w-40 h-2.5 bg-purple-950 rounded-full overflow-hidden border border-purple-800">
                                    <div class="h-full bg-gradient-to-r from-purple-400 to-emerald-400 rounded-full transition-all duration-500" :style="{ width: `${completionRate}%` }"></div>
                                </div>
                                <span class="font-mono text-sm font-bold text-white">{{ completionRate }}%</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                v-if="project?.thesis_document_path"
                                :href="`/storage/${project.thesis_document_path}`"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-200 border border-emerald-500/40 text-xs font-semibold hover:bg-emerald-500/30 transition shadow-sm"
                            >
                                <FileText class="w-3.5 h-3.5" />
                                Thesis PDF
                            </a>

                            <Button
                                variant="outline"
                                size="sm"
                                class="bg-white/10 hover:bg-white/20 text-white border-white/20 text-xs font-medium cursor-pointer"
                                @click="hasPaidFees === false ? router.visit(route('student.payments.index')) : (isTopicModalOpen = true)"
                            >
                                <Lock v-if="hasPaidFees === false" class="w-3.5 h-3.5 mr-1.5 text-rose-300" />
                                <ScrollText v-else class="w-3.5 h-3.5 mr-1.5" />
                                {{ hasPaidFees === false ? 'Fees Payment Required' : (project ? 'Modify Title & Synopsis' : 'Register Thesis Topic') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYMENT ALERT BANNER (If Fees Outstanding) -->
            <div v-if="hasPaidFees === false" class="rounded-2xl border border-rose-300 bg-rose-50/70 dark:bg-rose-950/30 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-600 shrink-0">
                        <Lock class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                            Academic School Fees Payment Required
                            <Badge variant="destructive" class="text-[10px]">Candidacy Locked</Badge>
                        </h4>
                        <p class="text-xs text-rose-700 dark:text-rose-300 mt-0.5">
                            Under SPGS guidelines, research topic registration, supervisory allocations, and dissertation submissions are locked until school fees for the current session are paid.
                        </p>
                    </div>
                </div>

                <Button
                    size="sm"
                    class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shrink-0 gap-1.5"
                    @click="router.visit(route('student.payments.index'))"
                >
                    <CreditCard class="w-3.5 h-3.5" />
                    Pay School Fees
                </Button>
            </div>

            <!-- SMART NEXT-ACTION HERO BANNER -->
            <div class="rounded-2xl border bg-card p-5 sm:p-6 shadow-sm transition-all flex flex-col md:flex-row md:items-center justify-between gap-4"
                :class="{
                    'border-purple-300 bg-purple-50/40 dark:bg-purple-950/20': smartAction.color === 'purple',
                    'border-blue-300 bg-blue-50/40 dark:bg-blue-950/20': smartAction.color === 'blue',
                    'border-amber-300 bg-amber-50/40 dark:bg-amber-950/20': smartAction.color === 'amber',
                    'border-rose-300 bg-rose-50/40 dark:bg-rose-950/20': smartAction.color === 'rose',
                    'border-emerald-300 bg-emerald-50/40 dark:bg-emerald-950/20': smartAction.color === 'emerald',
                    'border-indigo-300 bg-indigo-50/40 dark:bg-indigo-950/20': smartAction.color === 'indigo',
                }"
            >
                <div class="flex items-start gap-4">
                    <div class="p-3 rounded-xl shrink-0"
                        :class="{
                            'bg-purple-500/10 text-purple-600': smartAction.color === 'purple',
                            'bg-blue-500/10 text-blue-600': smartAction.color === 'blue',
                            'bg-amber-500/10 text-amber-600': smartAction.color === 'amber',
                            'bg-rose-500/10 text-rose-600': smartAction.color === 'rose',
                            'bg-emerald-500/10 text-emerald-600': smartAction.color === 'emerald',
                            'bg-indigo-500/10 text-indigo-600': smartAction.color === 'indigo',
                        }"
                    >
                        <component :is="smartAction.icon" class="w-6 h-6" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Badge variant="outline" :class="smartAction.badgeClass" class="text-xs font-semibold px-2 py-0.5 rounded-full">
                                {{ smartAction.badge }}
                            </Badge>
                            <span class="text-xs text-muted-foreground font-medium">Candidate Action Center</span>
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-foreground">
                            {{ smartAction.title }}
                        </h2>
                        <p class="text-xs sm:text-sm text-muted-foreground max-w-2xl">
                            {{ smartAction.description }}
                        </p>
                    </div>
                </div>

                <div class="shrink-0 flex items-center md:self-center">
                    <Button
                        size="sm"
                        class="gap-1.5 shadow-sm font-semibold text-xs"
                        :class="{
                            'bg-purple-700 hover:bg-purple-800 text-white': smartAction.color === 'purple',
                            'bg-blue-700 hover:bg-blue-800 text-white': smartAction.color === 'blue',
                            'bg-amber-700 hover:bg-amber-800 text-white': smartAction.color === 'amber',
                            'bg-rose-700 hover:bg-rose-800 text-white': smartAction.color === 'rose',
                            'bg-emerald-700 hover:bg-emerald-800 text-white': smartAction.color === 'emerald',
                            'bg-indigo-700 hover:bg-indigo-800 text-white': smartAction.color === 'indigo',
                        }"
                        @click="smartAction.action"
                    >
                        {{ smartAction.buttonLabel }}
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Button>
                </div>
            </div>

            <!-- STREAMLINED 3-PILLAR TAB NAVIGATION -->
            <Tabs v-model="activeTab" class="w-full space-y-6">
                <TabsList class="grid grid-cols-1 sm:grid-cols-3 h-auto p-1.5 bg-muted/60 rounded-2xl border border-border/70 max-w-2xl mx-auto">
                    <TabsTrigger value="milestones" class="text-xs sm:text-sm py-2.5 font-bold rounded-xl data-[state=active]:bg-card data-[state=active]:shadow-sm">
                        <Award class="w-4 h-4 mr-2 text-purple-600" />
                        1. Dissertation & Milestones
                    </TabsTrigger>
                    <TabsTrigger value="activities" class="text-xs sm:text-sm py-2.5 font-bold rounded-xl data-[state=active]:bg-card data-[state=active]:shadow-sm">
                        <BookOpen class="w-4 h-4 mr-2 text-blue-600" />
                        2. Activity & Output Log
                    </TabsTrigger>
                    <TabsTrigger value="governance" class="text-xs sm:text-sm py-2.5 font-bold rounded-xl data-[state=active]:bg-card data-[state=active]:shadow-sm">
                        <ShieldCheck class="w-4 h-4 mr-2 text-emerald-600" />
                        3. Candidacy & Defense
                    </TabsTrigger>
                </TabsList>

                <!-- PILLAR 1: DISSERTATION & MILESTONES -->
                <TabsContent value="milestones" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Left: Milestone Checklist & Drafts -->
                        <div class="lg:col-span-8 space-y-4">
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <Award class="w-4 h-4 text-purple-600" />
                                                Candidacy Milestones & Manuscripts
                                            </CardTitle>
                                            <CardDescription class="text-xs">
                                                Advance through each research stage with supervisory approval and SPGS similarity clearance (<20%).
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-4 sm:p-6 space-y-4">
                                    <div v-if="!project || !project.milestones || project.milestones.length === 0" class="text-center py-10 space-y-2">
                                        <ScrollText class="w-10 h-10 text-muted-foreground mx-auto opacity-40" />
                                        <h4 class="text-sm font-bold text-foreground">No Research Milestones Initialized</h4>
                                        <p class="text-xs text-muted-foreground max-w-sm mx-auto">
                                            Register your thesis topic to automatically generate the NUC-aligned milestone trajectory for your programme.
                                        </p>
                                        <Button size="sm" class="mt-2 text-xs" @click="isTopicModalOpen = true">
                                            Register Thesis Topic
                                        </Button>
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="milestone in project.milestones"
                                            :key="milestone.id"
                                            class="p-4 rounded-xl border border-border/60 bg-card hover:border-purple-300 dark:hover:border-purple-800 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                                            :class="{
                                                'border-rose-300 bg-rose-50/20 dark:bg-rose-950/10': milestone.status === 'revision_requested',
                                                'border-emerald-300 bg-emerald-50/20 dark:bg-emerald-950/10': milestone.status === 'supervisor_approved' || milestone.status === 'completed',
                                                'border-amber-300 bg-amber-50/20 dark:bg-amber-950/10': milestone.status === 'submitted' || milestone.status === 'under_review',
                                            }"
                                        >
                                            <div class="space-y-1.5 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-muted text-foreground">
                                                        Stage {{ milestone.sequence_order || milestone.stage_order }}
                                                    </span>
                                                    <h4 class="text-sm font-bold text-foreground">{{ milestone.title }}</h4>
                                                    <Badge v-if="milestone.status === 'supervisor_approved' || milestone.status === 'completed'" class="bg-emerald-600 text-white text-[10px]">
                                                        Passed
                                                    </Badge>
                                                    <Badge v-else-if="milestone.status === 'revision_requested'" variant="destructive" class="text-[10px]">
                                                        Revisions Needed
                                                    </Badge>
                                                    <Badge v-else-if="milestone.status === 'submitted' || milestone.status === 'under_review'" variant="secondary" class="bg-amber-100 text-amber-900 border-amber-300 text-[10px]">
                                                        Under Review
                                                    </Badge>
                                                </div>

                                                <div class="flex items-center gap-3 text-xs text-muted-foreground flex-wrap pt-0.5">
                                                    <span v-if="milestone.submitted_at" class="flex items-center gap-1">
                                                        <Clock class="w-3 h-3 text-muted-foreground" />
                                                        Submitted: {{ formatDate(milestone.submitted_at) }}
                                                    </span>
                                                    <span v-if="milestone.plagiarism_similarity_index != null" class="flex items-center gap-1 font-semibold" :class="milestone.plagiarism_similarity_index <= 20 ? 'text-emerald-600' : 'text-rose-600'">
                                                        <ShieldCheck class="w-3.5 h-3.5" />
                                                        Turnitin: {{ milestone.plagiarism_similarity_index }}%
                                                    </span>
                                                    <span v-if="milestone.score != null" class="font-bold text-foreground">
                                                        Score: {{ milestone.score }}%
                                                    </span>
                                                </div>

                                                <div v-if="milestone.supervisor_remarks" class="text-xs text-muted-foreground bg-muted/40 p-3 rounded-lg border border-border/50 mt-2 space-y-1">
                                                    <span class="font-semibold text-foreground uppercase tracking-wider text-[10px] block">Supervisor Directive:</span>
                                                    <p class="text-foreground italic">"{{ milestone.supervisor_remarks }}"</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2 shrink-0">
                                                <a
                                                    v-if="milestone.submission_path || milestone.document_path"
                                                    :href="`/storage/${milestone.submission_path || milestone.document_path}`"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold rounded-lg border border-border bg-background hover:bg-muted transition-colors text-foreground shadow-sm"
                                                    title="Download Stage Manuscript / Clearance Report"
                                                >
                                                    <FileText class="w-3.5 h-3.5 text-purple-600" />
                                                    Download Manuscript
                                                </a>
                                                <span v-else class="text-[11px] text-muted-foreground italic flex items-center gap-1 bg-muted/40 px-2.5 py-1 rounded-md border">
                                                    <Clock class="w-3 h-3 text-muted-foreground" />
                                                    Managed by Supervisor
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Institutional Supervisory Note -->
                                    <div class="mt-4 p-3.5 rounded-xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/40 flex items-start gap-3 text-xs text-muted-foreground">
                                        <ShieldCheck class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0 mt-0.5" />
                                        <p>
                                            <strong>SPGS Quality Assurance:</strong> Stage manuscripts, seminar defense scores, and official Turnitin similarity certificates (&le; 20%) are vetted, uploaded, and cleared by your accredited Supervisory Committee and the Departmental PG Committee (DPGC).
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Right: Supervisory Committee Card -->
                        <div class="lg:col-span-4 space-y-6">
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                                        <UserCheck class="w-4 h-4 text-purple-600" />
                                        Supervisory Committee
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        Accredited faculty assigned by DPGC & the SPGS Board.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-4 space-y-4">
                                    <!-- Major Supervisor -->
                                    <div class="p-3.5 rounded-xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/40 space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 block">
                                            Major Supervisor (Lead Chair)
                                        </span>
                                        <p class="text-sm font-bold text-foreground">
                                            {{ project?.major_supervisor?.user?.name || project?.majorSupervisor?.user?.name || 'Awaiting Formal Allocation' }}
                                        </p>
                                        <p v-if="project?.major_supervisor?.user?.email || project?.majorSupervisor?.user?.email" class="text-xs text-muted-foreground">
                                            {{ project?.major_supervisor?.user?.email || project?.majorSupervisor?.user?.email }}
                                        </p>
                                    </div>

                                    <!-- Co-Supervisor I -->
                                    <div class="p-3.5 rounded-xl bg-muted/30 border border-border/60 space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground block">
                                            Co-Supervisor I (Methodology / Co-Chair)
                                        </span>
                                        <p class="text-sm font-bold text-foreground">
                                            {{ project?.co_supervisor?.user?.name || project?.coSupervisor?.user?.name || 'None Assigned' }}
                                        </p>
                                        <p v-if="project?.co_supervisor?.user?.email || project?.coSupervisor?.user?.email" class="text-xs text-muted-foreground">
                                            {{ project?.co_supervisor?.user?.email || project?.coSupervisor?.user?.email }}
                                        </p>
                                    </div>

                                    <!-- Co-Supervisor II (if any) -->
                                    <div v-if="project?.second_co_supervisor || project?.secondCoSupervisor" class="p-3.5 rounded-xl bg-muted/30 border border-border/60 space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground block">
                                            Co-Supervisor II / Industry Specialist
                                        </span>
                                        <p class="text-sm font-bold text-foreground">
                                            {{ project?.second_co_supervisor?.user?.name || project?.secondCoSupervisor?.user?.name }}
                                        </p>
                                    </div>

                                    <!-- Petition Notice -->
                                    <div class="text-[11px] text-muted-foreground bg-muted/20 p-3 rounded-lg border border-border/40 leading-relaxed">
                                        Need to request a change of supervisor or topic revision? File a request under the <strong>Candidacy & Defense</strong> tab.
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- PILLAR 2: ACTIVITY & EVIDENCE LOG -->
                <TabsContent value="activities" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Left: Digital Research Logbook -->
                        <div class="lg:col-span-7 space-y-4">
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div>
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <BookOpen class="w-4 h-4 text-blue-600" />
                                                Digital Research Logbook
                                            </CardTitle>
                                            <CardDescription class="text-xs">
                                                Bi-weekly laboratory fieldwork, citations, and supervisor consultation logs.
                                            </CardDescription>
                                        </div>
                                        <Button size="sm" class="bg-blue-600 hover:bg-blue-700 text-white shrink-0 text-xs" @click="isLogModalOpen = true">
                                            <Plus class="w-3.5 h-3.5 mr-1" />
                                            Record Research Hours
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-4 sm:p-6 space-y-4">
                                    <!-- Log Summary Stats -->
                                    <div class="grid grid-cols-3 gap-3 p-3 rounded-xl bg-muted/40 border text-center">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Total Hours</span>
                                            <p class="text-lg font-bold font-mono text-foreground">{{ totalHoursLogged }}h</p>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-muted-foreground">Logged Entries</span>
                                            <p class="text-lg font-bold font-mono text-foreground">{{ logs?.total || logs?.data?.length || 0 }}</p>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-emerald-600">Supervisor Endorsed</span>
                                            <p class="text-lg font-bold font-mono text-emerald-600">
                                                {{ logs?.data?.filter((l: any) => l.supervisor_status === 'reviewed').length || 0 }}
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="!logs || logs.data.length === 0" class="text-center py-10 space-y-2">
                                        <BookOpen class="w-10 h-10 text-muted-foreground mx-auto opacity-40" />
                                        <h4 class="text-sm font-bold text-foreground">No Research Entries Recorded</h4>
                                        <p class="text-xs text-muted-foreground max-w-sm mx-auto">
                                            Regular log entries provide verified evidence of research velocity for your Annual Progress Report (APR).
                                        </p>
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="log in logs.data"
                                            :key="log.id"
                                            class="p-4 rounded-xl border border-border/60 bg-card hover:border-blue-300 dark:hover:border-blue-800 transition-all space-y-2"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-mono font-bold text-muted-foreground">
                                                        {{ formatDate(log.entry_date) }}
                                                    </span>
                                                    <Badge variant="outline" class="text-[10px] font-mono">
                                                        {{ log.hours_spent }} Hours Logged
                                                    </Badge>
                                                </div>
                                                <Badge
                                                    :variant="log.supervisor_status === 'reviewed' ? 'default' : 'secondary'"
                                                    class="text-[10px] capitalize"
                                                    :class="log.supervisor_status === 'reviewed' ? 'bg-emerald-600 text-white' : ''"
                                                >
                                                    {{ log.supervisor_status === 'reviewed' ? 'Endorsed' : log.supervisor_status || 'Pending Review' }}
                                                </Badge>
                                            </div>

                                            <p class="text-xs text-foreground font-medium">{{ log.work_summary || log.activity_summary }}</p>

                                            <div v-if="log.progress_made" class="text-[11px] text-muted-foreground bg-muted/30 p-2 rounded border">
                                                <strong>Progress / Findings:</strong> {{ log.progress_made }}
                                            </div>

                                            <div v-if="log.supervisor_feedback" class="text-[11px] text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 p-2 rounded border border-emerald-200">
                                                <strong>Supervisor Feedback:</strong> {{ log.supervisor_feedback }}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Right: Peer-Reviewed Publications -->
                        <div class="lg:col-span-5 space-y-4">
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <ScrollText class="w-4 h-4 text-purple-600" />
                                                Peer-Reviewed Publications
                                            </CardTitle>
                                            <CardDescription class="text-xs">
                                                Scopus, Web of Science, or NUC-indexed journal articles.
                                            </CardDescription>
                                        </div>
                                        <Button size="sm" variant="outline" class="text-xs h-8" @click="isPubModalOpen = true">
                                            <Plus class="w-3.5 h-3.5 mr-1" />
                                            Add
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-4 sm:p-6 space-y-4">
                                    <div v-if="!publications || publications.data.length === 0" class="text-center py-8 space-y-2">
                                        <ScrollText class="w-8 h-8 text-muted-foreground mx-auto opacity-40" />
                                        <h4 class="text-sm font-bold text-foreground">No Publications Recorded</h4>
                                        <p class="text-xs text-muted-foreground max-w-xs mx-auto">
                                            Doctoral and master's candidates must publish at least 1 peer-reviewed article prior to final defense.
                                        </p>
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="pub in publications.data"
                                            :key="pub.id"
                                            class="p-3.5 rounded-xl border border-border/60 bg-card space-y-1.5"
                                        >
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="text-xs font-bold text-foreground leading-snug">
                                                    {{ pub.article_title }}
                                                </h4>
                                                <Badge variant="outline" class="text-[10px] shrink-0 bg-purple-50 text-purple-800 border-purple-200">
                                                    {{ pub.indexing_agency }}
                                                </Badge>
                                            </div>
                                            <p class="text-[11px] text-muted-foreground">
                                                Journal: <span class="font-medium text-foreground">{{ pub.journal_name }}</span>
                                            </p>
                                            <div v-if="pub.doi_or_url" class="pt-1">
                                                <a :href="pub.doi_or_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline">
                                                    <ExternalLink class="w-3 h-3" />
                                                    DOI: {{ pub.doi_or_url }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- PILLAR 3: CANDIDACY & GOVERNANCE -->
                <TabsContent value="governance" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Left: Annual Progress Reports (APR) -->
                        <div class="lg:col-span-7 space-y-4">
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div>
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <FileCheck class="w-4 h-4 text-emerald-600" />
                                                Annual Progress Reports (APR)
                                            </CardTitle>
                                            <CardDescription class="text-xs">
                                                Mandatory annual appraisal submitted to DPGC and Dean of SPGS.
                                            </CardDescription>
                                        </div>
                                        <Button size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white shrink-0 text-xs" @click="isAprModalOpen = true">
                                            <Plus class="w-3.5 h-3.5 mr-1" />
                                            File Annual APR
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-4 sm:p-6 space-y-4">
                                    <div v-if="!progressReports || !progressReports.data || progressReports.data.length === 0" class="text-center py-8 space-y-2">
                                        <FileCheck class="w-8 h-8 text-muted-foreground mx-auto opacity-40" />
                                        <h4 class="text-sm font-bold text-foreground">No Progress Reports Filed</h4>
                                        <p class="text-xs text-muted-foreground max-w-sm mx-auto">
                                            Each candidate must file an annual progress review endorsed by the supervisory team to renew candidacy.
                                        </p>
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div
                                            v-for="apr in progressReports.data"
                                            :key="apr.id"
                                            class="p-4 rounded-xl border border-border/60 bg-card space-y-2"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-foreground">
                                                    {{ apr.report_period }}
                                                </span>
                                                <Badge
                                                    :variant="apr.supervisor_rating === 'excellent' || apr.supervisor_rating === 'satisfactory' ? 'default' : 'secondary'"
                                                    class="text-[10px] capitalize"
                                                >
                                                    Rating: {{ apr.supervisor_rating || 'Awaiting Rating' }}
                                                </Badge>
                                            </div>
                                            <p class="text-xs text-muted-foreground">{{ apr.progress_summary }}</p>
                                            <div v-if="apr.supervisor_comments" class="text-[11px] bg-muted/40 p-2.5 rounded border text-foreground">
                                                <strong>Supervisor Review:</strong> {{ apr.supervisor_comments }}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Right: Sundry Petitions & Viva Defense -->
                        <div class="lg:col-span-5 space-y-4">
                            <!-- Oral Defense Card -->
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                                        <GraduationCap class="w-4 h-4 text-indigo-600" />
                                        Oral Defense & Senate Broadsheet
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        Viva Voce examination clearance status.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-4 space-y-3">
                                    <div v-if="project?.status === 'completed'" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 text-center space-y-2">
                                        <CheckCircle2 class="w-8 h-8 text-emerald-600 mx-auto" />
                                        <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">
                                            Viva Voce Passed & Senate Certified
                                        </h4>
                                        <p class="text-xs text-muted-foreground">
                                            Score: <strong class="text-foreground">{{ project.final_score }}%</strong> • Verdict: <strong class="capitalize text-foreground">{{ project.defense_status }}</strong>
                                        </p>
                                    </div>

                                    <div v-else-if="project?.status === 'defense_scheduled'" class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200">Oral Defense Confirmed</span>
                                            <Badge class="bg-indigo-600 text-white text-[10px]">Ready</Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Date: <strong class="text-foreground">{{ formatDate(project.defense_date) }}</strong><br>
                                            Venue: <strong class="text-foreground">{{ project.defense_venue || 'SPGS Senate Chambers' }}</strong>
                                        </p>
                                    </div>

                                    <div v-else class="text-center py-6 text-xs text-muted-foreground">
                                        <Clock class="w-6 h-6 mx-auto mb-1 opacity-40" />
                                        Oral defense scheduling is unlocked once all chapter drafts and plagiarism checks (<20%) are cleared.
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Candidacy Sundry Petitions -->
                            <Card class="border border-border/70 shadow-sm rounded-2xl">
                                <CardHeader class="pb-3 border-b border-border/60">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                                <FileSpreadsheet class="w-4 h-4 text-purple-600" />
                                                Candidacy Petitions
                                            </CardTitle>
                                            <CardDescription class="text-xs">
                                                Time extension, title change, or deferment.
                                            </CardDescription>
                                        </div>
                                        <Button size="sm" variant="outline" class="text-xs h-8" @click="isPetitionModalOpen = true">
                                            <Plus class="w-3.5 h-3.5 mr-1" />
                                            Petition
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-4 space-y-3">
                                    <div v-if="!candidacyRequests || candidacyRequests.data.length === 0" class="text-center py-6 text-xs text-muted-foreground">
                                        No formal petitions filed.
                                    </div>

                                    <div v-else class="space-y-2.5">
                                        <div
                                            v-for="req in candidacyRequests.data"
                                            :key="req.id"
                                            class="p-3 rounded-xl border bg-card text-xs space-y-1"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="font-bold text-foreground capitalize">
                                                    {{ req.request_type ? req.request_type.replace(/_/g, ' ') : 'Petition' }}
                                                </span>
                                                <Badge variant="outline" class="text-[10px] capitalize">
                                                    {{ req.status ? req.status.replace(/_/g, ' ') : 'Pending' }}
                                                </Badge>
                                            </div>
                                            <p class="text-muted-foreground line-clamp-2">
                                                {{ req.justification_and_timeline || req.justification }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- 1. Topic Registration Modal -->
        <Dialog :open="isTopicModalOpen" @update:open="isTopicModalOpen = $event">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Register / Modify Thesis Topic & Synopsis</DialogTitle>
                    <DialogDescription>
                        State your research title, problem statement synopsis, and specialization area for the SPGS Board.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitTopic" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="title">Dissertation / Thesis Title</Label>
                        <Input
                            id="title"
                            v-model="topicForm.title"
                            placeholder="e.g. Distributed Deep Learning for Secure Healthcare Systems"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="research_area">Research Specialization Area</Label>
                        <Input
                            id="research_area"
                            v-model="topicForm.research_area"
                            placeholder="e.g. Artificial Intelligence, Cryptography, Renewable Power"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="abstract">Research Synopsis & Abstract</Label>
                        <Textarea
                            id="abstract"
                            v-model="topicForm.abstract"
                            rows="4"
                            placeholder="Provide a 250-500 word overview covering problem context, objectives, methodology, and expected contribution to knowledge..."
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="thesis_doc">Upload Thesis / Proposal Manuscript (PDF Document)</Label>
                        <Input
                            id="thesis_doc"
                            type="file"
                            accept=".pdf"
                            @change="handleThesisFileChange"
                        />
                        <p class="text-[11px] text-muted-foreground">Upload your complete thesis draft or research synopsis document in PDF format (max 30MB).</p>
                        <div v-if="project?.thesis_document_path" class="pt-1 flex items-center gap-2 text-xs">
                            <span class="text-emerald-600 font-semibold flex items-center gap-1">
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                Current PDF on file:
                            </span>
                            <a :href="`/storage/${project.thesis_document_path}`" target="_blank" class="text-blue-600 underline font-medium inline-flex items-center gap-1">
                                <FileText class="w-3.5 h-3.5" />
                                View Uploaded Manuscript
                            </a>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="isTopicModalOpen = false">Cancel</Button>
                        <Button :disabled="topicForm.processing" type="submit">
                            <Send class="w-4 h-4 mr-2" />
                            Submit to DPGC Committee
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 2. Milestone Draft Submission Modal -->
        <Dialog :open="isMilestoneModalOpen" @update:open="isMilestoneModalOpen = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Submit Manuscript Draft</DialogTitle>
                    <DialogDescription>
                        {{ activeMilestone?.title }} • Upload draft document and declare plagiarism index.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitMilestone" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="milestone_doc">Manuscript Document (.PDF or .DOCX)</Label>
                        <Input
                            id="milestone_doc"
                            type="file"
                            accept=".pdf,.doc,.docx"
                            @change="handleMilestoneFileChange"
                            required
                        />
                        <p class="text-[11px] text-muted-foreground">Maximum file size: 25MB.</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="sim_index">Turnitin Plagiarism Similarity Index (%)</Label>
                        <Input
                            id="sim_index"
                            type="number"
                            v-model="milestoneForm.plagiarism_similarity_index"
                            min="0"
                            max="100"
                            placeholder="e.g. 14"
                        />
                        <p class="text-[11px] text-muted-foreground">NUC Benchmark: Must not exceed 20% similarity.</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="notes">Candidate Submission Remarks</Label>
                        <Textarea
                            id="notes"
                            v-model="milestoneForm.submission_notes"
                            rows="3"
                            placeholder="Briefly state chapters or key sections included in this draft..."
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="isMilestoneModalOpen = false">Cancel</Button>
                        <Button :disabled="milestoneForm.processing" type="submit">
                            <Upload class="w-4 h-4 mr-2" />
                            Submit Manuscript
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 3. Digital Research Log Modal -->
        <Dialog :open="isLogModalOpen" @update:open="isLogModalOpen = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Record Research Work & Hours</DialogTitle>
                    <DialogDescription>
                        Log your weekly research milestones, lab experiments, or field activities.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitLog" class="space-y-4 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="entry_date">Activity Date</Label>
                            <Input
                                id="entry_date"
                                type="date"
                                v-model="logForm.entry_date"
                                required
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="hours_spent">Hours Spent</Label>
                            <Input
                                id="hours_spent"
                                type="number"
                                v-model="logForm.hours_spent"
                                min="1"
                                max="80"
                                required
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="work_summary">Work Completed</Label>
                        <Textarea
                            id="work_summary"
                            v-model="logForm.work_summary"
                            rows="3"
                            placeholder="Describe experiments run, literature reviewed, datasets collected..."
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="progress_made">Key Findings / Discoveries (Optional)</Label>
                        <Input
                            id="progress_made"
                            v-model="logForm.progress_made"
                            placeholder="e.g. Model achieved 94% precision on validation dataset"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="challenges_faced">Challenges / Blockers Encountered (Optional)</Label>
                        <Input
                            id="challenges_faced"
                            v-model="logForm.challenges_faced"
                            placeholder="e.g. GPU compute quota bottleneck"
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="isLogModalOpen = false">Cancel</Button>
                        <Button :disabled="logForm.processing" type="submit">
                            <Send class="w-4 h-4 mr-2" />
                            Log Research Entry
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 4. Annual Progress Report (APR) Modal -->
        <Dialog :open="isAprModalOpen" @update:open="isAprModalOpen = $event">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>File Annual Progress Report (APR)</DialogTitle>
                    <DialogDescription>
                        Formal annual report submitted to the SPGS Board for candidacy renewal.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitApr" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="apr_period">Reporting Academic Session / Period</Label>
                        <Input
                            id="apr_period"
                            v-model="aprForm.report_period"
                            placeholder="e.g. 2026/2027 Academic Session"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="apr_summary">Cumulative Progress Summary</Label>
                        <Textarea
                            id="apr_summary"
                            v-model="aprForm.progress_summary"
                            rows="4"
                            placeholder="Comprehensive review of research activities, chapters completed, and lab benchmarks..."
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="apr_achievements">Key Research Achievements & Papers</Label>
                        <Input
                            id="apr_achievements"
                            v-model="aprForm.research_achievements"
                            placeholder="e.g. Published 1 Scopus conference paper, finished data collection"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="apr_plan">Next Session Work Plan</Label>
                        <Input
                            id="apr_plan"
                            v-model="aprForm.next_year_work_plan"
                            placeholder="e.g. Submit Chapter 4 & 5, schedule Departmental Internal Defense"
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="isAprModalOpen = false">Cancel</Button>
                        <Button :disabled="aprForm.processing" type="submit">
                            <Award class="w-4 h-4 mr-2" />
                            Submit Annual APR
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 5. Candidacy Sundry Petition Modal -->
        <Dialog :open="isPetitionModalOpen" @update:open="isPetitionModalOpen = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>File Formal Candidacy Petition</DialogTitle>
                    <DialogDescription>
                        Submit a petition to the Dean of SPGS (Subject to DPGC & Senate approval).
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitPetition" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="petition_type">Petition Category</Label>
                        <Select v-model="petitionForm.request_type">
                            <SelectTrigger>
                                <SelectValue placeholder="Select petition type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="extension_of_time">Extension of Candidacy Period</SelectItem>
                                <SelectItem value="change_of_title">Modification of Thesis Title</SelectItem>
                                <SelectItem value="change_of_supervisor">Change of Supervisory Committee</SelectItem>
                                <SelectItem value="deferment">Temporary Candidacy Deferment</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div v-if="petitionForm.request_type === 'change_of_title'" class="space-y-1.5">
                        <Label for="proposed_title">Proposed New Thesis Title</Label>
                        <Input
                            id="proposed_title"
                            v-model="petitionForm.proposed_title"
                            placeholder="Enter proposed revised title..."
                            required
                        />
                    </div>

                    <div v-if="petitionForm.request_type === 'extension_of_time'" class="space-y-1.5">
                        <Label for="extension_months">Requested Extension (Months)</Label>
                        <Input
                            id="extension_months"
                            type="number"
                            v-model="petitionForm.extension_months"
                            min="1"
                            max="24"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="justification">Academic Justification & Evidence</Label>
                        <Textarea
                            id="justification"
                            v-model="petitionForm.justification_and_timeline"
                            rows="4"
                            placeholder="Provide formal rationale and clear timeline to completion..."
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="supporting_doc">Supporting Document (Optional PDF)</Label>
                        <Input
                            id="supporting_doc"
                            type="file"
                            accept=".pdf"
                            @change="handlePetitionFileChange"
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="isPetitionModalOpen = false">Cancel</Button>
                        <Button :disabled="petitionForm.processing" type="submit">
                            <Send class="w-4 h-4 mr-2" />
                            Submit Formal Petition
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- 6. Peer-Reviewed Publication Modal -->
        <Dialog :open="isPubModalOpen" @update:open="isPubModalOpen = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Record Peer-Reviewed Publication</DialogTitle>
                    <DialogDescription>
                        Provide article citations and DOI for NUC graduation compliance.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitPub" class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="pub_title">Article Title</Label>
                        <Input
                            id="pub_title"
                            v-model="pubForm.article_title"
                            placeholder="e.g. Transformer Models in Low-Resource African Languages"
                            required
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="journal_name">Journal / Conference Name</Label>
                        <Input
                            id="journal_name"
                            v-model="pubForm.journal_name"
                            placeholder="e.g. IEEE Transactions on Knowledge & Data Engineering"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="indexing">Indexing Agency</Label>
                            <Select v-model="pubForm.indexing_agency">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select agency" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Scopus">Scopus</SelectItem>
                                    <SelectItem value="Web of Science">Web of Science</SelectItem>
                                    <SelectItem value="NUC-Recognized">NUC-Recognized</SelectItem>
                                    <SelectItem value="PubMed">PubMed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="doi">DOI or Publication URL</Label>
                            <Input
                                id="doi"
                                v-model="pubForm.doi_or_url"
                                placeholder="https://doi.org/10.1109/..."
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="evidence">Publication Evidence Document (PDF)</Label>
                        <Input
                            id="evidence"
                            type="file"
                            accept=".pdf"
                            @change="handlePubFileChange"
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="isPubModalOpen = false">Cancel</Button>
                        <Button :disabled="pubForm.processing" type="submit">
                            <Upload class="w-4 h-4 mr-2" />
                            Save Publication
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </StudentLayout>
</template>
