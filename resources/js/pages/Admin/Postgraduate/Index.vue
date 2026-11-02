
<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from 'axios';
import AdminLayout from '@/layouts/AdminLayout.vue';
import PostgraduateHeaderNav from '@/components/Postgraduate/PostgraduateHeaderNav.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    GraduationCap,
    Users,
    Calendar,
    Award,
    Plus,
    Search,
    FileSpreadsheet,
    FileCheck,
    CheckCircle2,
    XCircle,
    UserCheck,
    ScrollText,
    Loader2,
    Eye,
    FileText,
    Download,
    ExternalLink,
    ShieldCheck,
    Clock,
    Building2,
    BookOpen,
} from 'lucide-vue-next';

const props = defineProps<{
    projects: {
        data: Array<any>;
        links?: Array<any>;
        current_page?: number;
        total?: number;
    };
    pgStudents: Array<any>;
    supervisors: Array<any>;
    stats: {
        total: number;
        in_progress: number;
        defense_scheduled: number;
        completed: number;
        pending_petitions?: number;
        pending_aprs?: number;
    };
    pendingPetitions?: Array<any>;
    pathwayTypes: Record<string, string>;
    filters: {
        search?: string;
        pathway_type?: string;
        status?: string;
    };
}>();

const createDialogOpen = ref(false);
const supervisorDialogOpen = ref(false);
const defenseDialogOpen = ref(false);
const verdictDialogOpen = ref(false);
const examinerDialogOpen = ref(false);
const detailsDialogOpen = ref(false);
const selectedProject = ref<any>(null);

const openDetailsModal = (project: any) => {
    selectedProject.value = project;
    detailsDialogOpen.value = true;
};

// 5M Scale Async Candidate Search State
const candidateSearchQuery = ref('');
const candidateSearchResults = ref<Array<any>>([...props.pgStudents]);
const isSearchingCandidates = ref(false);
let candidateSearchDebounce: any = null;

watch(candidateSearchQuery, (val) => {
    clearTimeout(candidateSearchDebounce);
    if (!val || val.length < 2) {
        candidateSearchResults.value = [...props.pgStudents];
        return;
    }
    candidateSearchDebounce = setTimeout(async () => {
        isSearchingCandidates.value = true;
        try {
            const resp = await axios.get(route('admin.postgraduate.research.search-candidates'), {
                params: { q: val }
            });
            candidateSearchResults.value = resp.data;
        } catch (e) {
            console.error(e);
        } finally {
            isSearchingCandidates.value = false;
        }
    }, 300);
});

const createForm = useForm({
    student_id: '',
    title: '',
    research_area: '',
    abstract: '',
    pathway_type: 'academic_dissertation',
    major_supervisor_id: '',
    co_supervisor_id: '',
});

const supervisorForm = useForm({
    major_supervisor_id: '',
    co_supervisor_id: '',
    co_supervisor_2_id: '',
    external_examiner_name: '',
    external_examiner_institution: '',
    external_examiner_email: '',
});

const defenseForm = useForm({
    defense_date: '',
    defense_venue: '',
    external_examiner_name: '',
    external_examiner_institution: '',
    external_examiner_email: '',
    defense_panel_notes: '',
});

const verdictForm = useForm({
    final_score: '',
    defense_status: 'passed',
    defense_panel_notes: '',
    repository_url: '',
});

const examinerForm = useForm({
    examiner_type: 'external_examiner',
    title: 'Prof.',
    full_name: '',
    institution_or_department: '',
    email: '',
    phone: '',
    specialization: '',
});

const submitCreate = () => {
    createForm.post(route('admin.postgraduate.research.store'), {
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
        },
    });
};

const openSupervisorModal = (project: any) => {
    selectedProject.value = project;
    supervisorForm.major_supervisor_id = project.major_supervisor_id || '';
    supervisorForm.co_supervisor_id = project.co_supervisor_id || '';
    supervisorForm.co_supervisor_2_id = project.co_supervisor_2_id || '';
    supervisorForm.external_examiner_name = project.external_examiner_name || '';
    supervisorForm.external_examiner_institution = project.external_examiner_institution || '';
    supervisorForm.external_examiner_email = project.external_examiner_email || '';
    supervisorDialogOpen.value = true;
};

const submitSupervisors = () => {
    if (!selectedProject.value) return;
    supervisorForm.put(route('admin.postgraduate.research.supervisors', selectedProject.value.id), {
        onSuccess: () => {
            supervisorDialogOpen.value = false;
        },
    });
};

const openDefenseModal = (project: any) => {
    selectedProject.value = project;
    defenseForm.defense_date = project.defense_date ? project.defense_date.slice(0, 16) : '';
    defenseForm.defense_venue = project.defense_venue || 'SPGS Senate Chambers';
    defenseForm.external_examiner_name = project.external_examiner_name || '';
    defenseForm.external_examiner_institution = project.external_examiner_institution || '';
    defenseForm.external_examiner_email = project.external_examiner_email || '';
    defenseForm.defense_panel_notes = project.defense_panel_notes || '';
    defenseDialogOpen.value = true;
};

const submitDefense = () => {
    if (!selectedProject.value) return;
    defenseForm.put(route('admin.postgraduate.research.defense.schedule', selectedProject.value.id), {
        onSuccess: () => {
            defenseDialogOpen.value = false;
        },
    });
};

const openVerdictModal = (project: any) => {
    selectedProject.value = project;
    verdictForm.final_score = project.final_score ? String(project.final_score) : '';
    verdictForm.defense_status = project.defense_status !== 'unscheduled' && project.defense_status !== 'scheduled'
        ? project.defense_status
        : 'passed';
    verdictForm.defense_panel_notes = project.defense_panel_notes || '';
    verdictForm.repository_url = project.repository_url || '';
    verdictDialogOpen.value = true;
};

const submitVerdict = () => {
    if (!selectedProject.value) return;
    verdictForm.post(route('admin.postgraduate.research.defense.verdict', selectedProject.value.id), {
        onSuccess: () => {
            verdictDialogOpen.value = false;
        },
    });
};

const openExaminerModal = (project: any) => {
    selectedProject.value = project;
    examinerForm.reset();
    examinerDialogOpen.value = true;
};

const submitExaminer = () => {
    if (!selectedProject.value) return;
    examinerForm.post(route('admin.postgraduate.research.examiners.store', selectedProject.value.id), {
        onSuccess: () => {
            examinerDialogOpen.value = false;
            examinerForm.reset();
        },
    });
};

const approveMilestone = (milestone: any, status: string) => {
    router.put(route('admin.postgraduate.research.milestones.review', milestone.id), {
        status,
        spgs_remarks: 'Approved by SPGS Board.',
    });
};

const processPetition = (petitionId: string, action: 'approve' | 'reject') => {
    router.post(route('admin.postgraduate.research.candidacy-requests.process', petitionId), {
        action,
    });
};

// Filter search state
const searchInput = ref(props.filters.search || '');
const pathwayFilter = ref(props.filters.pathway_type || 'all');
const statusFilter = ref(props.filters.status || 'all');

const applyFilters = () => {
    router.get(route('admin.postgraduate.research.index'), {
        search: searchInput.value || undefined,
        pathway_type: pathwayFilter.value !== 'all' ? pathwayFilter.value : undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="SPGS Research & Candidacy Administration" />

    <AdminLayout>
        <PostgraduateHeaderNav active-tab="operations" />
        <div class="space-y-8 p-6 max-w-7xl mx-auto font-sans">

            <!-- Executive Institutional Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-border/70">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-xs px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 border-purple-200">
                            Postgraduate College / SPGS Board
                        </Badge>
                        <span class="text-xs text-muted-foreground font-mono">
                            Enterprise Research Subsystem
                        </span>
                    </div>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-foreground">
                        Postgraduate Research & Dissertation Governance
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground">
                        Supervisory committee allocations, progress monitoring, plagiarism clearance, and Viva Voce defense broadsheets.
                    </p>
                </div>

                <div class="shrink-0 flex items-center gap-3">
                    <Button @click="createDialogOpen = true" class="bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold h-10 shadow-sm cursor-pointer">
                        <Plus class="w-4 h-4 mr-1.5" /> Initialize Research Project
                    </Button>
                </div>
            </div>

            <!-- Stats KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <div class="p-4 rounded-xl border border-border/70 bg-card shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold text-muted-foreground">Total Projects</span>
                    <p class="text-2xl font-bold font-mono text-foreground">{{ stats.total }}</p>
                </div>
                <div class="p-4 rounded-xl border border-border/70 bg-card shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold text-blue-600">Active Research</span>
                    <p class="text-2xl font-bold font-mono text-blue-600">{{ stats.in_progress }}</p>
                </div>
                <div class="p-4 rounded-xl border border-border/70 bg-card shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold text-amber-600">Viva Scheduled</span>
                    <p class="text-2xl font-bold font-mono text-amber-600">{{ stats.defense_scheduled }}</p>
                </div>
                <div class="p-4 rounded-xl border border-border/70 bg-card shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold text-emerald-600">Defended & Cleared</span>
                    <p class="text-2xl font-bold font-mono text-emerald-600">{{ stats.completed }}</p>
                </div>
                <div class="p-4 rounded-xl border border-border/70 bg-card shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold text-purple-600">Pending Petitions</span>
                    <p class="text-2xl font-bold font-mono text-purple-600">{{ stats.pending_petitions || 0 }}</p>
                </div>
                <div class="p-4 rounded-xl border border-border/70 bg-card shadow-sm space-y-1">
                    <span class="text-[10px] uppercase font-bold text-indigo-600">Pending APRs</span>
                    <p class="text-2xl font-bold font-mono text-indigo-600">{{ stats.pending_aprs || 0 }}</p>
                </div>
            </div>

            <!-- Pending Sundry Petitions Review Desk (if any exist) -->
            <div v-if="pendingPetitions && pendingPetitions.length > 0" class="p-5 rounded-2xl bg-purple-50/40 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <FileSpreadsheet class="w-4 h-4 text-purple-700 dark:text-purple-300" />
                        <h3 class="text-sm font-bold text-foreground">
                            SPGS Board Action Required: Pending Candidacy Petitions
                        </h3>
                    </div>
                    <Badge variant="outline" class="text-xs font-semibold bg-purple-100 text-purple-800 border-purple-300">
                        {{ pendingPetitions.length }} Action Items
                    </Badge>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <div
                        v-for="pet in pendingPetitions"
                        :key="pet.id"
                        class="p-3.5 rounded-xl bg-card border border-border/70 shadow-xs flex flex-col justify-between gap-3"
                    >
                        <div class="space-y-1 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold uppercase text-[11px] text-purple-700 dark:text-purple-300">
                                    {{ pet.request_type.replaceAll('_', ' ') }}
                                </span>
                                <span class="text-[10px] text-muted-foreground font-mono">
                                    {{ pet.student?.matriculation_number }}
                                </span>
                            </div>
                            <p class="font-bold text-foreground">{{ pet.student?.user?.name }} ({{ pet.student?.program?.name }})</p>
                            <p v-if="pet.proposed_title" class="text-muted-foreground">Proposed: "{{ pet.proposed_title }}"</p>
                            <p v-if="pet.extension_months" class="text-muted-foreground">Extension: {{ pet.extension_months }} Months</p>
                            <p class="text-muted-foreground/90 line-clamp-2 italic">"{{ pet.justification_and_timeline }}"</p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-border/40">
                            <Button size="sm" variant="outline" class="h-8 text-xs text-rose-600 hover:text-rose-700" @click="processPetition(pet.id, 'reject')">
                                <XCircle class="w-3.5 h-3.5 mr-1" /> Reject
                            </Button>
                            <Button size="sm" class="h-8 text-xs bg-purple-700 hover:bg-purple-800 text-white" @click="processPetition(pet.id, 'approve')">
                                <CheckCircle2 class="w-3.5 h-3.5 mr-1" /> Approve Petition
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Controls -->
            <div class="p-4 rounded-xl bg-card border border-border/70 shadow-sm flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <Search class="w-4 h-4 absolute left-3 top-3 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Search by student name, matric number, or thesis title..."
                        class="pl-9 text-xs h-10"
                        @keydown.enter="applyFilters"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="pathwayFilter" class="text-xs h-10 rounded-lg border border-input px-3 bg-background" @change="applyFilters">
                        <option value="all">All Degree Pathways</option>
                        <option v-for="(label, key) in pathwayTypes" :key="key" :value="key">{{ label }}</option>
                    </select>

                    <select v-model="statusFilter" class="text-xs h-10 rounded-lg border border-input px-3 bg-background" @change="applyFilters">
                        <option value="all">All Candidacy Statuses</option>
                        <option value="topic_proposed">Topic Proposed</option>
                        <option value="in_progress">Active Research</option>
                        <option value="defense_scheduled">Defense Scheduled</option>
                        <option value="completed">Completed / Cleared</option>
                    </select>

                    <Button size="sm" variant="secondary" class="h-10 text-xs px-4" @click="applyFilters">
                        Filter
                    </Button>
                </div>
            </div>

            <!-- Projects Roster -->
            <div class="space-y-4">
                <div v-if="projects.data.length === 0" class="text-center py-16 rounded-2xl border border-dashed border-border/80 bg-card">
                    <GraduationCap class="w-12 h-12 text-muted-foreground mx-auto opacity-30 mb-2" />
                    <h3 class="text-base font-bold text-foreground">No Research Projects Matching Criteria</h3>
                    <p class="text-xs text-muted-foreground mt-1">Try broadening your search query or adjusting filters.</p>
                </div>

                <Card v-for="project in projects.data" :key="project.id" class="border border-border/70 shadow-sm rounded-2xl overflow-hidden hover:border-purple-300 dark:hover:border-purple-800 transition-all">
                    <CardHeader class="pb-3 border-b border-border/60 bg-muted/10">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <Badge variant="outline" class="text-[11px] font-semibold bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 border-purple-200">
                                        {{ pathwayTypes[project.pathway_type] || project.pathway_type }}
                                    </Badge>
                                    <Badge variant="secondary" class="text-[10px] uppercase font-semibold">
                                        {{ project.status.replaceAll('_', ' ') }}
                                    </Badge>
                                    <Badge v-if="project.final_grade" class="bg-emerald-700 text-white text-[11px] font-bold">
                                        Score: {{ project.final_score }}% (Grade {{ project.final_grade }})
                                    </Badge>
                                </div>
                                <CardTitle class="text-base font-bold font-serif pt-1 text-foreground">
                                    {{ project.title }}
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    <strong>{{ project.student?.user?.name }}</strong> ({{ project.student?.matriculation_number }}) •
                                    {{ project.student?.program?.department?.name || project.student?.program?.name }}
                                </CardDescription>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <Button size="sm" variant="secondary" class="text-xs h-8 bg-purple-100 hover:bg-purple-200 text-purple-900 dark:bg-purple-950 dark:text-purple-200 border border-purple-300 font-semibold" @click="openDetailsModal(project)">
                                    <Eye class="w-3.5 h-3.5 mr-1 text-purple-700 dark:text-purple-300" /> View Details
                                </Button>
                                <a
                                    v-if="project.thesis_document_path"
                                    :href="`/storage/${project.thesis_document_path}`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200 text-xs font-semibold hover:bg-emerald-100 transition shadow-sm"
                                    title="Download / View Candidate's Uploaded Thesis PDF"
                                >
                                    <FileText class="w-3.5 h-3.5 text-emerald-600" />
                                    PDF
                                </a>
                                <Button size="sm" variant="outline" class="text-xs h-8" @click="openSupervisorModal(project)">
                                    <Users class="w-3.5 h-3.5 mr-1" /> Supervisors
                                </Button>
                                <Button size="sm" variant="outline" class="text-xs h-8" @click="openExaminerModal(project)">
                                    <ScrollText class="w-3.5 h-3.5 mr-1" /> Examiners
                                </Button>
                                <Button size="sm" variant="outline" class="text-xs h-8" @click="openDefenseModal(project)">
                                    <Calendar class="w-3.5 h-3.5 mr-1" /> Viva Schedule
                                </Button>
                                <Button size="sm" class="bg-purple-700 hover:bg-purple-800 text-white text-xs h-8" @click="openVerdictModal(project)">
                                    <Award class="w-3.5 h-3.5 mr-1" /> Broadsheet Verdict
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="p-4 sm:p-5 space-y-4 text-xs">
                        <!-- Committee & Examiners Overview -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-xl bg-muted/20 border border-border/50">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-muted-foreground block">Major Supervisor</span>
                                <span class="font-semibold text-foreground">{{ project.major_supervisor?.user?.name || 'Pending Allocation' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-muted-foreground block">Co-Supervisor</span>
                                <span class="font-semibold text-foreground">{{ project.co_supervisor?.user?.name || 'None Assigned' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-muted-foreground block">Oral Defense Schedule</span>
                                <span class="font-semibold text-foreground">
                                    {{ project.defense_date ? new Date(project.defense_date).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Unscheduled' }}
                                </span>
                            </div>
                        </div>

                        <!-- Milestones Progression Grid -->
                        <div class="space-y-2">
                            <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block">
                                Milestone Evaluation & Plagiarism Clearances
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                <div
                                    v-for="m in project.milestones"
                                    :key="m.id"
                                    class="p-3 rounded-xl border border-border/60 bg-card flex flex-col justify-between gap-2 text-xs"
                                >
                                    <div>
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="font-bold text-foreground truncate">{{ m.sequence_order }}. {{ m.title }}</span>
                                            <Badge
                                                variant="outline"
                                                class="text-[9px] px-1.5 py-0 uppercase font-semibold shrink-0"
                                                :class="m.status === 'spgs_cleared' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : (m.status === 'submitted' ? 'bg-amber-50 text-amber-700 border-amber-300' : '')"
                                            >
                                                {{ m.status.replace('_', ' ') }}
                                            </Badge>
                                        </div>
                                        <p v-if="m.plagiarism_similarity_index != null" class="text-[11px] text-muted-foreground">
                                            Turnitin Similarity: <strong>{{ m.plagiarism_similarity_index }}%</strong>
                                        </p>
                                    </div>

                                    <div v-if="m.status === 'submitted'" class="flex items-center gap-1.5 pt-1 border-t border-border/40">
                                        <Button size="sm" variant="outline" class="h-7 text-xs flex-1 cursor-pointer" @click="approveMilestone(m, 'spgs_cleared')">
                                            Clear Milestone
                                        </Button>
                                        <Button size="sm" variant="ghost" class="h-7 text-xs text-rose-600 hover:text-rose-700 cursor-pointer" @click="approveMilestone(m, 'revision_requested')">
                                            Request Edits
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- MODAL: INITIALIZE PROJECT (5M Scale Async Search Safe) -->
            <Dialog :open="createDialogOpen" @update:open="createDialogOpen = $event">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle class="font-serif">Initialize PG Research Project</DialogTitle>
                        <DialogDescription class="text-xs">
                            Search for an admitted postgraduate candidate and register their dissertation workspace.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCreate" class="space-y-4 pt-2">
                        <!-- Candidate Search -->
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Search Candidate *</Label>
                            <div class="relative">
                                <Search class="w-3.5 h-3.5 absolute left-3 top-3 text-muted-foreground" />
                                <Input
                                    v-model="candidateSearchQuery"
                                    placeholder="Type matric number or student name..."
                                    class="pl-9 text-xs h-9"
                                />
                                <Loader2 v-if="isSearchingCandidates" class="w-3.5 h-3.5 absolute right-3 top-3 animate-spin text-muted-foreground" />
                            </div>

                            <select v-model="createForm.student_id" required class="w-full text-xs rounded-md border border-input p-2 bg-background mt-1.5">
                                <option value="" disabled>Select from matching candidates</option>
                                <option v-for="s in candidateSearchResults" :key="s.id" :value="s.id">
                                    {{ s.matriculation_number }} — {{ s.user?.name }} ({{ s.program?.name }})
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Degree Pathway</Label>
                            <select v-model="createForm.pathway_type" class="w-full text-xs rounded-md border border-input p-2 bg-background">
                                <option v-for="(label, key) in pathwayTypes" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Approved Thesis / Project Title *</Label>
                            <Input v-model="createForm.title" required placeholder="Enter research title" />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Specialization Area</Label>
                            <Input v-model="createForm.research_area" placeholder="e.g. Telecommunications, Applied Geophysics" />
                        </div>

                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="createDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" class="bg-purple-700 hover:bg-purple-800 text-white" :disabled="createForm.processing">
                                Create Workspace
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- MODAL: ALLOCATE SUPERVISORS -->
            <Dialog :open="supervisorDialogOpen" @update:open="supervisorDialogOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="font-serif">Constitute Supervisory Committee</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitSupervisors" class="space-y-4 pt-2">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Major Supervisor *</Label>
                            <select v-model="supervisorForm.major_supervisor_id" required class="w-full text-xs rounded-md border border-input p-2 bg-background">
                                <option value="" disabled>Select Major Supervisor</option>
                                <option v-for="st in supervisors" :key="st.id" :value="st.id">
                                    {{ st.user?.name || st.staff_number }} ({{ st.department?.name || 'Faculty' }})
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Co-Supervisor I</Label>
                            <select v-model="supervisorForm.co_supervisor_id" class="w-full text-xs rounded-md border border-input p-2 bg-background">
                                <option value="">None</option>
                                <option v-for="st in supervisors" :key="st.id" :value="st.id">
                                    {{ st.user?.name || st.staff_number }}
                                </option>
                            </select>
                        </div>

                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="supervisorDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" class="bg-purple-700 hover:bg-purple-800 text-white" :disabled="supervisorForm.processing">
                                Save Committee
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- MODAL: NOMINATE EXAMINER -->
            <Dialog :open="examinerDialogOpen" @update:open="examinerDialogOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="font-serif">Nominate Examiner</DialogTitle>
                        <DialogDescription class="text-xs">
                            Add an external or internal oral examiner to the project's examination dossier.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitExaminer" class="space-y-3 pt-2">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Examiner Role *</Label>
                            <select v-model="examinerForm.examiner_type" class="w-full text-xs rounded-md border border-input p-2 bg-background">
                                <option value="external_examiner">External Examiner (Outside Institution)</option>
                                <option value="internal_examiner">Internal Examiner (Within University)</option>
                                <option value="spgs_representative">SPGS Dean's Representative</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold">Title</Label>
                                <Input v-model="examinerForm.title" placeholder="Prof." />
                            </div>
                            <div class="col-span-2 space-y-1.5">
                                <Label class="text-xs font-semibold">Full Name *</Label>
                                <Input v-model="examinerForm.full_name" required placeholder="e.g. Adebayo Ogunlesi" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">University / Institution *</Label>
                            <Input v-model="examinerForm.institution_or_department" required placeholder="e.g. University of Ibadan" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold">Email *</Label>
                                <Input v-model="examinerForm.email" type="email" required placeholder="examiner@univ.edu" />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold">Phone</Label>
                                <Input v-model="examinerForm.phone" placeholder="+234..." />
                            </div>
                        </div>
                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="examinerDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" class="bg-purple-700 hover:bg-purple-800 text-white" :disabled="examinerForm.processing">
                                Save Examiner
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- MODAL: SCHEDULE VIVA DEFENSE -->
            <Dialog :open="defenseDialogOpen" @update:open="defenseDialogOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="font-serif">Schedule Oral Defense (Viva Voce)</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitDefense" class="space-y-4 pt-2">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Defense Date & Time *</Label>
                            <Input type="datetime-local" v-model="defenseForm.defense_date" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Venue / Virtual Conference Link *</Label>
                            <Input v-model="defenseForm.defense_venue" required placeholder="e.g. SPGS Boardroom or Google Meet URL" />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Panel Guidelines & Instructions</Label>
                            <textarea v-model="defenseForm.defense_panel_notes" rows="2" class="w-full text-xs rounded-md border border-input p-2 bg-background"></textarea>
                        </div>
                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="defenseDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" class="bg-purple-700 hover:bg-purple-800 text-white" :disabled="defenseForm.processing">
                                Schedule Viva Voce
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- MODAL: RECORD BROADSHEET VERDICT -->
            <Dialog :open="verdictDialogOpen" @update:open="verdictDialogOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="font-serif">Record Viva Voce Verdict & Score</DialogTitle>
                        <DialogDescription class="text-xs">
                            Panel scoring automatically maps to NUC Postgraduate grading and synchronizes with the student's academic transcript.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitVerdict" class="space-y-4 pt-2">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Composite Defense Score (0 - 100%) *</Label>
                            <Input type="number" min="0" max="100" step="0.5" v-model="verdictForm.final_score" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Board of Examiners Verdict *</Label>
                            <select v-model="verdictForm.defense_status" class="w-full text-xs rounded-md border border-input p-2 bg-background">
                                <option value="passed">Passed Clean (No Corrections)</option>
                                <option value="passed_minor_corrections">Passed (Subject to Minor Corrections)</option>
                                <option value="passed_major_corrections">Major Corrections (Panel Re-review)</option>
                                <option value="referred">Referred (Re-Defense Required)</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">External Examiner / Panel Remarks</Label>
                            <textarea v-model="verdictForm.defense_panel_notes" rows="3" class="w-full text-xs rounded-md border border-input p-2 bg-background" placeholder="Recommendations, required corrections, and contribution to knowledge..."></textarea>
                        </div>
                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="verdictDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" class="bg-purple-700 hover:bg-purple-800 text-white" :disabled="verdictForm.processing">
                                Save to SPGS Broadsheet
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- MODAL: VIEW FULL PROJECT DOSSIER -->
            <Dialog :open="detailsDialogOpen" @update:open="detailsDialogOpen = $event">
                <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader v-if="selectedProject">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Badge variant="outline" class="text-xs px-2.5 py-0.5 bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950 dark:text-purple-300">
                                {{ pathwayTypes[selectedProject.pathway_type] || selectedProject.pathway_type }}
                            </Badge>
                            <Badge variant="secondary" class="text-xs uppercase font-semibold">
                                {{ selectedProject.status.replaceAll('_', ' ') }}
                            </Badge>
                            <span class="text-xs text-muted-foreground font-mono">
                                ID: {{ selectedProject.id.slice(0, 8) }}...
                            </span>
                        </div>
                        <DialogTitle class="font-serif text-xl sm:text-2xl pt-2">
                            {{ selectedProject.title }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Candidate: <strong>{{ selectedProject.student?.user?.name }}</strong> ({{ selectedProject.student?.matriculation_number }}) •
                            Dept: {{ selectedProject.student?.program?.department?.name || selectedProject.student?.program?.name }}
                        </DialogDescription>
                    </DialogHeader>

                    <div v-if="selectedProject" class="space-y-6 pt-2 text-xs">
                        <!-- Thesis Abstract & PDF Download Banner -->
                        <div class="p-4 rounded-xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 block">
                                        Thesis Manuscript & Synopsis
                                    </span>
                                    <p v-if="selectedProject.research_area" class="text-xs text-muted-foreground mt-0.5">
                                        Specialization Area: <strong class="text-foreground">{{ selectedProject.research_area }}</strong>
                                    </p>
                                </div>

                                <div v-if="selectedProject.thesis_document_path">
                                    <a
                                        :href="`/storage/${selectedProject.thesis_document_path}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-sm"
                                    >
                                        <Download class="w-4 h-4" />
                                        Download Thesis PDF Manuscript
                                    </a>
                                </div>
                                <div v-else class="text-muted-foreground italic text-[11px]">
                                    No thesis PDF uploaded yet by candidate.
                                </div>
                            </div>

                            <div v-if="selectedProject.abstract" class="pt-2 border-t border-purple-200/60 dark:border-purple-800/60 space-y-1">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground block">Abstract / Research Synopsis:</span>
                                <p class="text-foreground leading-relaxed whitespace-pre-line text-xs bg-background/80 p-3 rounded-lg border">
                                    {{ selectedProject.abstract }}
                                </p>
                            </div>
                        </div>

                        <!-- Committee & Examiners Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Supervisory Committee -->
                            <div class="p-4 rounded-xl border bg-card space-y-2">
                                <h4 class="font-bold text-xs flex items-center gap-1.5 text-foreground">
                                    <Users class="w-3.5 h-3.5 text-purple-600" />
                                    Supervisory Committee
                                </h4>
                                <div class="space-y-1.5 pt-1 text-[11px]">
                                    <div class="flex justify-between border-b pb-1">
                                        <span class="text-muted-foreground">Major Supervisor (Lead):</span>
                                        <strong class="text-foreground">{{ selectedProject.major_supervisor?.user?.name || 'Pending' }}</strong>
                                    </div>
                                    <div v-if="selectedProject.major_supervisor?.user?.email" class="text-muted-foreground text-[10px] text-right">
                                        {{ selectedProject.major_supervisor.user.email }}
                                    </div>
                                    <div class="flex justify-between border-b pb-1 pt-1">
                                        <span class="text-muted-foreground">Co-Supervisor I:</span>
                                        <strong class="text-foreground">{{ selectedProject.co_supervisor?.user?.name || 'None' }}</strong>
                                    </div>
                                    <div v-if="selectedProject.second_co_supervisor" class="flex justify-between pt-1">
                                        <span class="text-muted-foreground">Co-Supervisor II:</span>
                                        <strong class="text-foreground">{{ selectedProject.second_co_supervisor?.user?.name }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Board of Examiners -->
                            <div class="p-4 rounded-xl border bg-card space-y-2">
                                <h4 class="font-bold text-xs flex items-center gap-1.5 text-foreground">
                                    <Award class="w-3.5 h-3.5 text-indigo-600" />
                                    Board of Examiners Panel
                                </h4>
                                <div class="space-y-1.5 pt-1 text-[11px]">
                                    <div class="flex justify-between border-b pb-1">
                                        <span class="text-muted-foreground">External Examiner:</span>
                                        <strong class="text-foreground">{{ selectedProject.external_examiner_name || 'Pending Nomination' }}</strong>
                                    </div>
                                    <div v-if="selectedProject.external_examiner_institution" class="text-muted-foreground text-[10px] text-right">
                                        Institution: {{ selectedProject.external_examiner_institution }}
                                    </div>
                                    <div class="flex justify-between border-b pb-1 pt-1">
                                        <span class="text-muted-foreground">Oral Defense Date:</span>
                                        <strong class="text-foreground">
                                            {{ selectedProject.defense_date ? new Date(selectedProject.defense_date).toLocaleString('en-GB') : 'Unscheduled' }}
                                        </strong>
                                    </div>
                                    <div v-if="selectedProject.defense_venue" class="flex justify-between pt-1">
                                        <span class="text-muted-foreground">Venue:</span>
                                        <strong class="text-foreground">{{ selectedProject.defense_venue }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone Trajectory & Plagiarism Clearance Table -->
                        <div class="p-4 rounded-xl border bg-card space-y-3">
                            <h4 class="font-bold text-xs flex items-center justify-between text-foreground">
                                <span class="flex items-center gap-1.5">
                                    <FileCheck class="w-3.5 h-3.5 text-emerald-600" />
                                    Research Milestones & Turnitin Quality Checks
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    {{ selectedProject.milestones?.length || 0 }} Prescribed Stages
                                </span>
                            </h4>

                            <div v-if="!selectedProject.milestones || selectedProject.milestones.length === 0" class="text-muted-foreground text-center py-4">
                                No milestones initialized.
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="ms in selectedProject.milestones"
                                    :key="ms.id"
                                    class="p-2.5 rounded-lg border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                >
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-muted">
                                                Stage {{ ms.sequence_order || ms.stage_order }}
                                            </span>
                                            <span class="font-semibold text-foreground">{{ ms.title }}</span>
                                            <Badge variant="outline" class="text-[9px] uppercase">
                                                {{ ms.status.replaceAll('_', ' ') }}
                                            </Badge>
                                        </div>
                                        <div v-if="ms.supervisor_remarks" class="text-[11px] text-muted-foreground italic pl-6">
                                            "{{ ms.supervisor_remarks }}"
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <span v-if="ms.score != null" class="font-bold text-foreground bg-muted px-2 py-0.5 rounded text-[11px]">
                                            {{ ms.score }}%
                                        </span>
                                        <span v-if="ms.plagiarism_similarity_index != null" class="text-[11px] font-semibold" :class="ms.plagiarism_similarity_index <= 20 ? 'text-emerald-600' : 'text-rose-600'">
                                            Turnitin: {{ ms.plagiarism_similarity_index }}%
                                        </span>
                                        <a
                                            v-if="ms.document_path || ms.submission_path"
                                            :href="`/storage/${ms.document_path || ms.submission_path}`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline font-medium"
                                        >
                                            <FileText class="w-3 h-3" />
                                            Draft
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Final Broadsheet & Senate Certification (If graded) -->
                        <div v-if="selectedProject.final_grade || selectedProject.defense_status !== 'unscheduled'" class="p-4 rounded-xl border border-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-xs text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                    Viva Voce Final Result & Senate Certification
                                </h4>
                                <Badge class="bg-emerald-700 text-white text-xs">
                                    Score: {{ selectedProject.final_score }}% (Grade {{ selectedProject.final_grade }})
                                </Badge>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-muted-foreground pt-1">
                                <div>Verdict: <strong class="capitalize text-foreground">{{ selectedProject.defense_status }}</strong></div>
                                <div>Grade Point: <strong class="text-foreground">{{ selectedProject.final_grade_point }}</strong></div>
                            </div>
                            <div v-if="selectedProject.defense_panel_notes" class="text-[11px] text-muted-foreground pt-1">
                                <strong>Examiners Panel Notes:</strong> {{ selectedProject.defense_panel_notes }}
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" size="sm" @click="detailsDialogOpen = false">
                            Close Dossier
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
