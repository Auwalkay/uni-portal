<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PostgraduateHeaderNav from '@/components/Postgraduate/PostgraduateHeaderNav.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    GraduationCap,
    Users,
    Clock,
    Award,
    CheckCircle2,
    XCircle,
    AlertCircle,
    FileText,
    MessageSquare,
    Send,
    Eye,
    ChevronRight,
    Calendar,
    BookOpen,
    Sparkles,
    Loader2,
} from 'lucide-vue-next';

interface StudentUser {
    id: number;
    name: string;
    email: string;
}

interface StudentDepartment {
    id: number;
    name: string;
}

interface StudentProgram {
    id: number;
    name: string;
    department?: StudentDepartment;
}

interface Student {
    id: string;
    matriculation_number: string;
    user?: StudentUser;
    program?: StudentProgram;
}

interface Supervisor {
    id: string;
    user?: {
        id: number;
        name: string;
    };
}

interface Milestone {
    id: string;
    title: string;
    stage_order?: number;
    sequence_order?: number;
    status: string;
    submission_path?: string | null;
    submitted_at?: string | null;
    score?: number | null;
    supervisor_remarks?: string | null;
}

interface LogEntry {
    id: string;
    entry_date: string;
    hours_spent: number;
    work_summary: string;
    progress_made?: string | null;
    challenges_faced?: string | null;
    supervisor_status: string;
    supervisor_feedback?: string | null;
    reviewed_at?: string | null;
    student?: Student;
    project?: {
        id: string;
        title: string;
    };
}

interface ProgressReport {
    id: string;
    academic_session_id?: string;
    report_period: string;
    progress_summary: string;
    supervisor_rating?: string | null;
    supervisor_comments?: string | null;
    dean_status: string;
    created_at: string;
}

interface Project {
    id: string;
    title: string;
    abstract?: string | null;
    status: string;
    field_of_study?: string | null;
    major_supervisor_id: string;
    co_supervisor_id?: string | null;
    co_supervisor_2_id?: string | null;
    student?: Student;
    majorSupervisor?: Supervisor;
    major_supervisor?: Supervisor;
    coSupervisor?: Supervisor;
    co_supervisor?: Supervisor;
    milestones?: Milestone[];
    logs?: LogEntry[];
    progressReports?: ProgressReport[];
    progress_reports?: ProgressReport[];
}

interface Props {
    projects: {
        data: Project[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    pendingLogs: LogEntry[];
    stats: {
        total_candidates: number;
        co_supervised: number;
        pending_logs: number;
        defense_ready: number;
    };
    staff: {
        id?: string | null;
        staff_number: string;
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
    isAdminView?: boolean;
}

const props = defineProps<Props>();

// State for Log Review Modal
const selectedLog = ref<LogEntry | null>(null);
const isLogReviewOpen = ref(false);
const logReviewForm = useForm({
    supervisor_status: 'reviewed',
    supervisor_feedback: '',
});

const openLogReviewModal = (log: LogEntry) => {
    selectedLog.value = log;
    logReviewForm.clearErrors();
    logReviewForm.supervisor_status = 'reviewed';
    logReviewForm.supervisor_feedback = log.supervisor_feedback || 'Candidate research log entry verified and endorsed.';
    isLogReviewOpen.value = true;
};

const submitLogReview = () => {
    if (!selectedLog.value) return;
    logReviewForm.post(route('staff.postgraduate.supervision.logs.review', selectedLog.value.id), {
        onSuccess: () => {
            isLogReviewOpen.value = false;
            selectedLog.value = null;
        },
    });
};

// State for Milestone Review Modal
const selectedMilestone = ref<Milestone | null>(null);
const selectedMilestoneProject = ref<Project | null>(null);
const isMilestoneReviewOpen = ref(false);
const milestoneReviewForm = useForm({
    status: 'supervisor_approved',
    supervisor_remarks: '',
    score: '',
    plagiarism_similarity_index: '',
    document: null as File | null,
});

const openMilestoneReviewModal = (milestone: Milestone, project: Project) => {
    selectedMilestone.value = milestone;
    selectedMilestoneProject.value = project;
    milestoneReviewForm.clearErrors();
    milestoneReviewForm.status = milestone.status === 'supervisor_approved' ? 'supervisor_approved' : (milestone.status === 'revision_requested' ? 'revision_requested' : 'supervisor_approved');
    milestoneReviewForm.supervisor_remarks = milestone.supervisor_remarks || 'Milestone evaluated and cleared by supervisory committee.';
    milestoneReviewForm.score = milestone.score ? String(milestone.score) : '';
    milestoneReviewForm.plagiarism_similarity_index = (milestone as any).plagiarism_similarity_index != null ? String((milestone as any).plagiarism_similarity_index) : '';
    milestoneReviewForm.document = null;
    isMilestoneReviewOpen.value = true;
};

const handleMilestoneFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        milestoneReviewForm.document = target.files[0];
    }
};

const submitMilestoneReview = () => {
    if (!selectedMilestone.value) return;
    milestoneReviewForm.post(route('staff.postgraduate.supervision.milestones.review', selectedMilestone.value.id), {
        forceFormData: true,
        onSuccess: () => {
            isMilestoneReviewOpen.value = false;
            selectedMilestone.value = null;
            selectedMilestoneProject.value = null;
        },
    });
};

// State for APR Endorsement Modal
const selectedApr = ref<ProgressReport | null>(null);
const isAprModalOpen = ref(false);
const aprForm = useForm({
    supervisor_rating: 'satisfactory',
    supervisor_comments: '',
});

const openAprModal = (report: ProgressReport) => {
    selectedApr.value = report;
    aprForm.supervisor_rating = (report.supervisor_rating as any) || 'satisfactory';
    aprForm.supervisor_comments = report.supervisor_comments || '';
    isAprModalOpen.value = true;
};

const submitAprEndorsement = () => {
    if (!selectedApr.value) return;
    aprForm.post(route('staff.postgraduate.supervision.progress-reports.endorse', selectedApr.value.id), {
        onSuccess: () => {
            isAprModalOpen.value = false;
            selectedApr.value = null;
        },
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'completed':
        case 'supervisor_approved':
        case 'approved':
            return { label: 'Approved', variant: 'default' as const, class: 'bg-emerald-600 text-white' };
        case 'submitted':
        case 'under_review':
        case 'in_progress':
            return { label: 'Under Review', variant: 'secondary' as const, class: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' };
        case 'revision_requested':
        case 'needs_revision':
            return { label: 'Needs Revision', variant: 'destructive' as const, class: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200' };
        case 'defense_scheduled':
            return { label: 'Defense Scheduled', variant: 'default' as const, class: 'bg-indigo-600 text-white' };
        default:
            return { label: status.replace(/_/g, ' '), variant: 'outline' as const, class: 'capitalize' };
    }
};
</script>

<template>
    <Head title="PG Supervisory Workbench | Staff Portal" />

    <AdminLayout>
        <PostgraduateHeaderNav active-tab="supervision" />
        <div class="space-y-8 p-6 max-w-7xl mx-auto font-sans">
            <!-- Executive Header -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 text-white shadow-xl">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <span v-if="isAdminView" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-indigo-300 ring-1 ring-indigo-500/40">
                                <Sparkles class="h-3.5 w-3.5" />
                                SPGS Institutional Oversight Desk
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-300 ring-1 ring-emerald-500/40">
                                <Sparkles class="h-3.5 w-3.5" />
                                NUC Accredited Supervisor
                            </span>
                            <span class="text-xs text-slate-300">Staff ID: {{ staff.staff_number }}</span>
                        </div>
                        <h1 class="text-3xl font-extrabold tracking-tight text-white">
                            {{ isAdminView ? 'Postgraduate Supervisory Oversight Workbench' : 'Postgraduate Supervisory Workbench' }}
                        </h1>
                        <p class="max-w-2xl text-sm text-slate-300">
                            {{ isAdminView 
                                ? 'Executive governance portal. Overseeing all postgraduate dissertations, research logs, and milestone evaluations across the university.' 
                                : `Welcome, ${staff.user.name}. Oversee doctoral dissertations and master\'s theses, endorse student lab/field logs, evaluate research chapters, and certify readiness for oral viva defenses.` 
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Metric Telemetry Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-l-4 border-l-blue-600 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Major Candidates</CardTitle>
                        <Users class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_candidates }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Primary dissertation mentees</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-purple-600 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Co-Supervised</CardTitle>
                        <GraduationCap class="h-4 w-4 text-purple-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.co_supervised }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Secondary / Committee roles</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-amber-500 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Pending Log Reviews</CardTitle>
                        <Clock class="h-4 w-4 text-amber-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-amber-600">{{ stats.pending_logs }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Awaiting your feedback & signoff</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-emerald-600 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Viva Scheduled</CardTitle>
                        <Award class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">{{ stats.defense_ready }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Candidates cleared for oral defense</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Action Required: Unreviewed Logbook Submissions -->
            <div v-if="pendingLogs.length > 0" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <AlertCircle class="h-5 w-5 text-amber-500" />
                        <h2 class="text-lg font-bold tracking-tight">Logbook Entries Awaiting Review</h2>
                        <Badge variant="outline" class="bg-amber-50 text-amber-800 border-amber-300">
                            {{ pendingLogs.length }} pending
                        </Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card v-for="log in pendingLogs" :key="log.id" class="border-amber-200 bg-amber-50/30 dark:bg-amber-950/10">
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-bold text-sm text-foreground">
                                        {{ log.student?.user?.name ?? 'Candidate' }}
                                    </h3>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        {{ log.project?.title ?? 'Research Project' }}
                                    </p>
                                </div>
                                <Badge variant="secondary" class="bg-amber-100 text-amber-900 border-amber-200 text-xs">
                                    {{ log.hours_spent }} Hours
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="bg-background/80 p-3 rounded-lg border text-xs space-y-1.5">
                                <span class="font-semibold text-muted-foreground uppercase tracking-wide">Work Summary:</span>
                                <p class="text-foreground line-clamp-2">{{ log.work_summary }}</p>
                                <div v-if="log.progress_made" class="pt-1">
                                    <span class="font-semibold text-muted-foreground uppercase tracking-wide">Progress:</span>
                                    <p class="text-foreground line-clamp-1">{{ log.progress_made }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-muted-foreground pt-1">
                                <span>Entry Date: {{ log.entry_date }}</span>
                                <Button size="sm" class="gap-1.5" @click="openLogReviewModal(log)">
                                    <FileText class="h-3.5 w-3.5" />
                                    Review & Endorse
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Supervised Dissertations & Theses Roster -->
            <Card class="shadow-sm">
                <CardHeader>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <CardTitle class="text-xl">Supervised Postgraduate Candidates</CardTitle>
                            <CardDescription>
                                Track milestones, progress reports, and viva readiness for all assigned M.Sc, M.Phil, and Ph.D candidates.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="projects.data.length === 0" class="text-center py-12 text-muted-foreground">
                        <GraduationCap class="h-12 w-12 mx-auto stroke-1 mb-3 text-slate-400" />
                        <p class="text-base font-semibold">No assigned postgraduate candidates currently.</p>
                        <p class="text-xs">Candidates allocated to you by your Departmental PG Committee will appear here.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <div
                            v-for="project in projects.data"
                            :key="project.id"
                            class="rounded-xl border bg-card p-6 shadow-sm transition hover:shadow-md space-y-5"
                        >
                            <!-- Candidate & Research Overview -->
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Badge :variant="getStatusBadge(project.status).variant" :class="getStatusBadge(project.status).class">
                                            {{ getStatusBadge(project.status).label }}
                                        </Badge>
                                        <Badge v-if="staff.id && project.major_supervisor_id === staff.id" variant="default" class="bg-blue-600 text-white text-xs">
                                            Major Supervisor
                                        </Badge>
                                        <Badge v-else-if="staff.id && (project.co_supervisor_id === staff.id || project.co_supervisor_2_id === staff.id)" variant="outline" class="text-xs">
                                            Co-Supervisor
                                        </Badge>
                                        <Badge v-else variant="outline" class="text-xs bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            SPGS Oversight
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            Dept: {{ project.student?.program?.department?.name ?? 'Postgraduate School' }}
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-bold text-foreground tracking-tight">
                                        {{ project.title }}
                                    </h3>
                                    <p class="text-xs text-muted-foreground line-clamp-2">
                                        {{ project.abstract || 'Abstract pending submission by candidate.' }}
                                    </p>
                                </div>

                                <div class="bg-muted/50 rounded-lg p-3 text-right flex flex-col justify-between min-w-[200px]">
                                    <div>
                                        <div class="font-bold text-sm text-foreground">
                                            {{ project.student?.user?.name ?? 'Candidate Name' }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            Matric: {{ project.student?.matriculation_number }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-2">
                                        Program: <span class="font-medium text-foreground">{{ project.student?.program?.name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Milestone Trajectory Progression -->
                            <div class="space-y-2 border-t pt-4">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold uppercase tracking-wider text-muted-foreground">Milestone Trajectory:</span>
                                    <span class="text-muted-foreground">{{ (project.milestones ?? []).length }} Stages</span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                    <div
                                        v-for="ms in (project.milestones ?? [])"
                                        :key="ms.id"
                                        class="p-2.5 rounded-lg border text-xs space-y-1.5 transition"
                                        :class="{
                                            'bg-emerald-50/50 border-emerald-300 dark:bg-emerald-950/20': ms.status === 'supervisor_approved' || ms.status === 'completed',
                                            'bg-amber-50/50 border-amber-300 dark:bg-amber-950/20': ms.status === 'submitted' || ms.status === 'under_review',
                                            'bg-red-50/50 border-red-300 dark:bg-red-950/20': ms.status === 'revision_requested',
                                            'bg-muted/30 border-dashed': ms.status === 'pending',
                                        }"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-[11px] truncate" :title="ms.title">
                                                {{ ms.sequence_order ?? ms.stage_order ?? 1 }}. {{ ms.title }}
                                            </span>
                                            <CheckCircle2 v-if="ms.status === 'supervisor_approved' || ms.status === 'completed'" class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                            <Clock v-else-if="ms.status === 'submitted' || ms.status === 'under_review'" class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                                            <XCircle v-else-if="ms.status === 'revision_requested'" class="h-3.5 w-3.5 text-red-500 shrink-0" />
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] text-muted-foreground">
                                            <span class="capitalize">{{ ms.status.replace(/_/g, ' ') }}</span>
                                            <span v-if="ms.score" class="font-semibold text-foreground">{{ ms.score }}%</span>
                                        </div>

                                        <!-- Supervisory Action on Milestone -->
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="w-full h-6 text-[10px] py-0 px-1 mt-1 bg-white dark:bg-slate-900 border-border hover:bg-muted"
                                            @click="openMilestoneReviewModal(ms, project)"
                                        >
                                            {{ ms.status === 'supervisor_approved' || ms.status === 'completed' ? 'Update Grade' : (ms.status === 'submitted' || ms.status === 'under_review' ? 'Evaluate Draft' : 'Upload & Grade') }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Annual Progress Reports (APR) Signoff & Recent Logs Bar -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t pt-3 text-xs text-muted-foreground">
                                <div class="flex items-center gap-4">
                                    <span>
                                        Recent Logs: <strong class="text-foreground">{{ (project.logs ?? []).length }}</strong> recorded
                                    </span>
                                    <span>
                                        Progress Reports: <strong class="text-foreground">{{ (project.progress_reports ?? project.progressReports ?? []).length }}</strong> filed
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div v-for="apr in (project.progress_reports ?? project.progressReports ?? [])" :key="apr.id">
                                        <Button
                                            v-if="!apr.supervisor_rating"
                                            size="sm"
                                            variant="secondary"
                                            class="h-7 text-xs gap-1.5 bg-purple-50 text-purple-900 border border-purple-200 hover:bg-purple-100"
                                            @click="openAprModal(apr)"
                                        >
                                            <Award class="h-3.5 w-3.5" />
                                            Sign APR ({{ apr.report_period }})
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="projects.total > 12" class="mt-6 flex items-center justify-between border-t pt-4">
                        <p class="text-xs text-muted-foreground">
                            Showing {{ projects.data.length }} of {{ projects.total }} supervised candidates
                        </p>
                        <div class="flex gap-2">
                            <Button
                                v-for="(link, i) in projects.links"
                                :key="i"
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                @click="link.url && router.visit(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Log Review Modal -->
        <Dialog :open="isLogReviewOpen" @update:open="isLogReviewOpen = $event">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Review Candidate Research Log</DialogTitle>
                    <DialogDescription>
                        Evaluate research activities and provide constructive mentorship comments.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedLog" class="space-y-4 py-2">
                    <div class="rounded-lg bg-muted/40 p-3 space-y-2 text-xs">
                        <div class="flex justify-between text-muted-foreground">
                            <span>Candidate: <strong class="text-foreground">{{ selectedLog.student?.user?.name }}</strong></span>
                            <span>Hours: <strong class="text-foreground">{{ selectedLog.hours_spent }}h</strong></span>
                            <span>Date: <strong class="text-foreground">{{ selectedLog.entry_date }}</strong></span>
                        </div>
                        <div>
                            <span class="font-semibold text-muted-foreground">Work Executed:</span>
                            <p class="mt-0.5 text-foreground">{{ selectedLog.work_summary }}</p>
                        </div>
                        <div v-if="selectedLog.progress_made">
                            <span class="font-semibold text-muted-foreground">Key Findings / Progress:</span>
                            <p class="mt-0.5 text-foreground">{{ selectedLog.progress_made }}</p>
                        </div>
                        <div v-if="selectedLog.challenges_faced">
                            <span class="font-semibold text-muted-foreground">Challenges Encountered:</span>
                            <p class="mt-0.5 text-red-600 dark:text-red-400">{{ selectedLog.challenges_faced }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-1.5">
                            <Label for="supervisor_status">Review Determination</Label>
                            <Select v-model="logReviewForm.supervisor_status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select verdict" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="reviewed">Approved & Endorsed</SelectItem>
                                    <SelectItem value="needs_revision">Revision / Elaboration Requested</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="supervisor_feedback">Supervisory Feedback & Directives</Label>
                            <Textarea
                                id="supervisor_feedback"
                                v-model="logReviewForm.supervisor_feedback"
                                rows="4"
                                placeholder="Provide specific guidance, recommended citations, or experimental suggestions..."
                                required
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isLogReviewOpen = false">Cancel</Button>
                    <Button :disabled="logReviewForm.processing" @click="submitLogReview">
                        <Send class="h-4 w-4 mr-2" />
                        Submit Evaluation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Milestone Review Modal -->
        <Dialog :open="isMilestoneReviewOpen" @update:open="isMilestoneReviewOpen = $event">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Evaluate Dissertation Milestone Stage</DialogTitle>
                    <DialogDescription>
                        Review candidate draft submissions, assign progress scores, and certify advancement.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedMilestone" class="space-y-4 py-2">
                    <div v-if="milestoneReviewForm.hasErrors" class="rounded-lg bg-red-50 p-3 text-xs text-red-700 border border-red-200">
                        Please review the highlighted fields below.
                    </div>

                    <div class="rounded-lg bg-muted/40 p-3 space-y-1 text-xs">
                        <div class="font-bold text-sm text-foreground">
                            {{ selectedMilestone.sequence_order ?? selectedMilestone.stage_order ?? 1 }}. {{ selectedMilestone.title }}
                        </div>
                        <p class="text-muted-foreground">
                            Project: {{ selectedMilestoneProject?.title }}
                        </p>
                        <div v-if="selectedMilestone.document_path || selectedMilestone.submission_path" class="pt-2">
                            <a
                                :href="`/storage/${selectedMilestone.document_path || selectedMilestone.submission_path}`"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-blue-600 hover:underline font-medium"
                            >
                                <FileText class="h-3.5 w-3.5" />
                                Download Candidate Draft Manuscript
                            </a>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-1.5">
                            <Label for="milestone_doc">Upload Candidate Manuscript Draft / Defense Report (.PDF, .DOCX)</Label>
                            <Input
                                id="milestone_doc"
                                type="file"
                                accept=".pdf,.doc,.docx"
                                @change="handleMilestoneFileChange"
                            />
                            <p class="text-[11px] text-muted-foreground">Attach the verified candidate draft or formal committee defense report (max 25MB).</p>
                            <p v-if="milestoneReviewForm.errors.document" class="text-xs text-red-500">{{ milestoneReviewForm.errors.document }}</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <Label for="milestone_status">Evaluation Verdict</Label>
                                <Select v-model="milestoneReviewForm.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select verdict" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="supervisor_approved">Approved & Passed</SelectItem>
                                        <SelectItem value="revision_requested">Revisions Required</SelectItem>
                                        <SelectItem value="in_progress">Under Progress</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="milestoneReviewForm.errors.status" class="text-xs text-red-500">{{ milestoneReviewForm.errors.status }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="score">Stage Score (0-100%)</Label>
                                <Input
                                    id="score"
                                    type="number"
                                    v-model="milestoneReviewForm.score"
                                    min="0"
                                    max="100"
                                    placeholder="e.g. 78"
                                />
                                <p v-if="milestoneReviewForm.errors.score" class="text-xs text-red-500">{{ milestoneReviewForm.errors.score }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="sim_index">Turnitin Index (%)</Label>
                                <Input
                                    id="sim_index"
                                    type="number"
                                    v-model="milestoneReviewForm.plagiarism_similarity_index"
                                    min="0"
                                    max="100"
                                    placeholder="e.g. 14"
                                />
                                <p v-if="milestoneReviewForm.errors.plagiarism_similarity_index" class="text-xs text-red-500">{{ milestoneReviewForm.errors.plagiarism_similarity_index }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="supervisor_remarks">Supervisory Feedback & Directives</Label>
                            <Textarea
                                id="supervisor_remarks"
                                v-model="milestoneReviewForm.supervisor_remarks"
                                rows="3"
                                placeholder="State corrections required or formal commendations for DPGC clearance..."
                            />
                            <p v-if="milestoneReviewForm.errors.supervisor_remarks" class="text-xs text-red-500">{{ milestoneReviewForm.errors.supervisor_remarks }}</p>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isMilestoneReviewOpen = false">Cancel</Button>
                    <Button :disabled="milestoneReviewForm.processing" @click="submitMilestoneReview">
                        <Loader2 v-if="milestoneReviewForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4 mr-2" />
                        {{ milestoneReviewForm.processing ? 'Saving Evaluation...' : 'Confirm Milestone Evaluation' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- APR Endorsement Modal -->
        <Dialog :open="isAprModalOpen" @update:open="isAprModalOpen = $event">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Endorse Annual Progress Report (APR)</DialogTitle>
                    <DialogDescription>
                        Certify candidate research velocity for the SPGS Board of Examiners.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedApr" class="space-y-4 py-2">
                    <div class="rounded-lg bg-muted/40 p-3 space-y-2 text-xs">
                        <div class="font-bold text-foreground">
                            Reporting Period: {{ selectedApr.report_period }}
                        </div>
                        <div>
                            <span class="font-semibold text-muted-foreground">Candidate Progress Summary:</span>
                            <p class="mt-0.5 text-foreground">{{ selectedApr.progress_summary }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-1.5">
                            <Label for="supervisor_rating">Annual Performance Rating</Label>
                            <Select v-model="aprForm.supervisor_rating">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select rating" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="excellent">Excellent Velocity</SelectItem>
                                    <SelectItem value="satisfactory">Satisfactory Progress</SelectItem>
                                    <SelectItem value="needs_improvement">Needs Improvement</SelectItem>
                                    <SelectItem value="unsatisfactory">Unsatisfactory (At-Risk)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="supervisor_comments">Supervisory Committee Remarks</Label>
                            <Textarea
                                id="supervisor_comments"
                                v-model="aprForm.supervisor_comments"
                                rows="4"
                                placeholder="Enter confidential performance appraisal for the SPGS Board..."
                                required
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isAprModalOpen = false">Cancel</Button>
                    <Button :disabled="aprForm.processing" @click="submitAprEndorsement">
                        <Award class="h-4 w-4 mr-2" />
                        Sign & Endorse APR
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>

