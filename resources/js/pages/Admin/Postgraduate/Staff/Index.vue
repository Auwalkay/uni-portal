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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    Users,
    GraduationCap,
    Award,
    Clock,
    Search,
    Plus,
    CheckCircle2,
    XCircle,
    AlertCircle,
    ExternalLink,
    ShieldCheck,
    Building2,
    Edit3,
} from 'lucide-vue-next';

interface StaffMember {
    id: string;
    staff_number: string;
    name: string;
    email: string;
    designation: string;
    department: string;
    is_phd_accredited: boolean;
    is_masters_accredited: boolean;
    is_pgd_accredited: boolean;
    max_phd_quota: number;
    max_masters_quota: number;
    active_phd_count: number;
    active_masters_count: number;
    specialization_areas?: string[] | null;
    scopus_id?: string | null;
    orcid_id?: string | null;
    google_scholar_url?: string | null;
    avg_turnaround_days: number;
}

const props = defineProps<{
    staffMembers: {
        data: StaffMember[];
        links?: Array<any>;
        current_page?: number;
        total?: number;
    };
    externalExaminers: {
        data: Array<any>;
        links?: Array<any>;
    };
    departments: Array<{ id: string; name: string }>;
    stats: {
        total_academic_staff: number;
        phd_accredited: number;
        masters_accredited: number;
        total_external_examiners: number;
        tenure_warning_examiners: number;
    };
    filters: {
        search?: string;
        department_id?: string;
        accreditation?: string;
    };
}>();

const activeTab = ref<'faculty' | 'examiners'>('faculty');
const accreditationDialogOpen = ref(false);
const examinerDialogOpen = ref(false);
const selectedStaff = ref<StaffMember | null>(null);

const searchInput = ref(props.filters.search || '');
const departmentFilter = ref(props.filters.department_id || 'all');
const accreditationFilter = ref(props.filters.accreditation || 'all');

const applyFilters = () => {
    router.get(route('admin.postgraduate.staff.index'), {
        search: searchInput.value || undefined,
        department_id: departmentFilter.value !== 'all' ? departmentFilter.value : undefined,
        accreditation: accreditationFilter.value !== 'all' ? accreditationFilter.value : undefined,
    }, { preserveState: true });
};

// Accreditation Form
const accreditationForm = useForm({
    is_phd_accredited: false,
    is_masters_accredited: true,
    is_pgd_accredited: true,
    max_phd_quota: 5,
    max_masters_quota: 8,
    specialization_areas: '',
    scopus_id: '',
    orcid_id: '',
    google_scholar_url: '',
});

const openAccreditationModal = (staff: StaffMember) => {
    selectedStaff.value = staff;
    accreditationForm.is_phd_accredited = staff.is_phd_accredited;
    accreditationForm.is_masters_accredited = staff.is_masters_accredited;
    accreditationForm.is_pgd_accredited = staff.is_pgd_accredited;
    accreditationForm.max_phd_quota = staff.max_phd_quota;
    accreditationForm.max_masters_quota = staff.max_masters_quota;
    accreditationForm.specialization_areas = staff.specialization_areas ? staff.specialization_areas.join(', ') : '';
    accreditationForm.scopus_id = staff.scopus_id || '';
    accreditationForm.orcid_id = staff.orcid_id || '';
    accreditationForm.google_scholar_url = staff.google_scholar_url || '';
    accreditationDialogOpen.value = true;
};

const submitAccreditation = () => {
    if (!selectedStaff.value) return;

    const specs = accreditationForm.specialization_areas
        ? accreditationForm.specialization_areas.split(',').map(s => s.trim()).filter(Boolean)
        : [];

    accreditationForm.transform((data) => ({
        ...data,
        specialization_areas: specs,
    })).put(route('admin.postgraduate.staff.accreditation.update', selectedStaff.value.id), {
        onSuccess: () => {
            accreditationDialogOpen.value = false;
        }
    });
};

// External Examiner Form
const examinerForm = useForm({
    title: 'Prof.',
    full_name: '',
    institution: '',
    department: '',
    email: '',
    phone: '',
    specialization: '',
});

const submitExaminer = () => {
    examinerForm.post(route('admin.postgraduate.external-examiners.store'), {
        onSuccess: () => {
            examinerDialogOpen.value = false;
            examinerForm.reset();
        }
    });
};

const toggleExaminer = (id: string) => {
    router.put(route('admin.postgraduate.external-examiners.toggle', id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="PG Faculty & Supervisory Governance" />

    <AdminLayout :breadcrumbs="[
        { title: 'Postgraduate School', href: route('admin.postgraduate.research.index') },
        { title: 'Faculty & Supervisory Capacity', href: route('admin.postgraduate.staff.index') }
    ]">
        <PostgraduateHeaderNav active-tab="quotas" />
        <div class="space-y-6 p-6">
            <!-- Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-border/70">
                <div>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-primary/10 text-primary border-primary/20">
                            School of Postgraduate Studies (SPGS)
                        </Badge>
                        <span class="text-xs text-muted-foreground font-medium">• Supervisory Governance</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground font-serif mt-1">
                        Faculty Accreditation & Supervisory Quotas
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground">
                        NUC supervisory capacity caps, academic accreditations, turnaround SLAs, and the master external examiners registry.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button size="sm" variant="outline" class="font-semibold text-xs" @click="examinerDialogOpen = true">
                        <Plus class="w-3.5 h-3.5 mr-1.5" />
                        Add External Examiner
                    </Button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="p-5 rounded-2xl border border-border/70 bg-card shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-bold uppercase tracking-wider">Ph.D Accredited Faculty</span>
                        <GraduationCap class="h-4 w-4 text-primary" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono text-foreground">{{ stats.phd_accredited }}</div>
                    <p class="text-xs text-muted-foreground">Eligible for doctoral supervision</p>
                </div>

                <div class="p-5 rounded-2xl border border-border/70 bg-card shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-bold uppercase tracking-wider">Master's Supervisors</span>
                        <Users class="h-4 w-4 text-blue-500" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono text-foreground">{{ stats.masters_accredited }}</div>
                    <p class="text-xs text-muted-foreground">Accredited for M.Sc/LL.M/PGD</p>
                </div>

                <div class="p-5 rounded-2xl border border-border/70 bg-card shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-bold uppercase tracking-wider">External Examiners</span>
                        <Award class="h-4 w-4 text-purple-500" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono text-foreground">{{ stats.total_external_examiners }}</div>
                    <p class="text-xs text-muted-foreground">Accredited peer professors</p>
                </div>

                <div class="p-5 rounded-2xl border border-border/70 bg-card shadow-sm space-y-2">
                    <div class="flex items-center justify-between text-muted-foreground">
                        <span class="text-xs font-bold uppercase tracking-wider">Tenure Rotation Alerts</span>
                        <Clock class="h-4 w-4 text-amber-500" />
                    </div>
                    <div class="text-3xl font-extrabold font-mono text-amber-600">{{ stats.tenure_warning_examiners }}</div>
                    <p class="text-xs text-muted-foreground">&ge; 3 consecutive sessions served</p>
                </div>
            </div>

            <!-- Workspace Tabs Switcher -->
            <div class="flex items-center gap-2 border-b border-border">
                <button
                    class="px-4 py-2 text-sm font-bold border-b-2 transition-colors"
                    :class="activeTab === 'faculty' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="activeTab = 'faculty'"
                >
                    Academic Staff Directory & Capacity ({{ staffMembers.total || 0 }})
                </button>
                <button
                    class="px-4 py-2 text-sm font-bold border-b-2 transition-colors"
                    :class="activeTab === 'examiners' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="activeTab = 'examiners'"
                >
                    External Examiners Master Registry ({{ stats.total_external_examiners }})
                </button>
            </div>

            <!-- TAB 1: ACADEMIC FACULTY DIRECTORY -->
            <div v-if="activeTab === 'faculty'" class="space-y-4">
                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchInput"
                            placeholder="Search by staff name, number, or rank..."
                            class="pl-9 h-9 text-xs"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <Select v-model="departmentFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-[200px] h-9 text-xs">
                                <SelectValue placeholder="All Departments" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Departments</SelectItem>
                                <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="accreditationFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-[180px] h-9 text-xs">
                                <SelectValue placeholder="All Accreditations" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Accreditation Ranks</SelectItem>
                                <SelectItem value="phd">Ph.D Accredited</SelectItem>
                                <SelectItem value="masters">Master's Accredited</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Staff Table -->
                <div class="rounded-2xl border border-border/70 bg-card overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-muted/40 border-b text-muted-foreground font-semibold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3 px-4">Staff Details</th>
                                    <th class="py-3 px-4">Accreditation</th>
                                    <th class="py-3 px-4">Ph.D Load (Max {{ 5 }})</th>
                                    <th class="py-3 px-4">Master's Load (Max {{ 8 }})</th>
                                    <th class="py-3 px-4">SLA Turnaround</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <tr v-for="staff in staffMembers.data" :key="staff.id" class="hover:bg-muted/20 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-foreground text-sm">{{ staff.name }}</div>
                                        <div class="text-[11px] text-muted-foreground">{{ staff.staff_number }} &bull; {{ staff.designation }}</div>
                                        <div class="text-[10px] text-muted-foreground">{{ staff.department }}</div>
                                    </td>
                                    <td class="py-3 px-4 space-y-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <Badge v-if="staff.is_phd_accredited" variant="outline" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300">
                                                Ph.D Approved
                                            </Badge>
                                            <Badge v-else variant="outline" class="text-[10px] text-muted-foreground bg-muted/40">
                                                Ph.D Ineligible
                                            </Badge>
                                            <Badge v-if="staff.is_masters_accredited" variant="outline" class="text-[10px] font-bold text-blue-600 bg-blue-50 dark:bg-blue-950/40 border-blue-300">
                                                M.Sc Approved
                                            </Badge>
                                        </div>
                                        <div v-if="staff.specialization_areas && staff.specialization_areas.length > 0" class="text-[10px] text-muted-foreground truncate max-w-xs">
                                            {{ staff.specialization_areas.join(', ') }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-1">
                                            <div class="flex justify-between font-mono text-[11px]">
                                                <span>{{ staff.active_phd_count }} / {{ staff.max_phd_quota }}</span>
                                                <span :class="staff.active_phd_count >= staff.max_phd_quota ? 'text-red-500 font-bold' : 'text-muted-foreground'">
                                                    {{ Math.round((staff.active_phd_count / staff.max_phd_quota) * 100) }}%
                                                </span>
                                            </div>
                                            <div class="w-28 bg-muted/60 h-2 rounded-full overflow-hidden border">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="staff.active_phd_count >= staff.max_phd_quota ? 'bg-red-500' : 'bg-primary'"
                                                    :style="{ width: `${Math.min((staff.active_phd_count / staff.max_phd_quota) * 100, 100)}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-1">
                                            <div class="flex justify-between font-mono text-[11px]">
                                                <span>{{ staff.active_masters_count }} / {{ staff.max_masters_quota }}</span>
                                                <span :class="staff.active_masters_count >= staff.max_masters_quota ? 'text-red-500 font-bold' : 'text-muted-foreground'">
                                                    {{ Math.round((staff.active_masters_count / staff.max_masters_quota) * 100) }}%
                                                </span>
                                            </div>
                                            <div class="w-28 bg-muted/60 h-2 rounded-full overflow-hidden border">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="staff.active_masters_count >= staff.max_masters_quota ? 'bg-red-500' : 'bg-blue-600'"
                                                    :style="{ width: `${Math.min((staff.active_masters_count / staff.max_masters_quota) * 100, 100)}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-mono font-semibold text-foreground">
                                            {{ staff.avg_turnaround_days > 0 ? `${staff.avg_turnaround_days} Days` : 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <Button size="sm" variant="outline" class="h-8 text-xs font-semibold" @click="openAccreditationModal(staff)">
                                            <Edit3 class="w-3.5 h-3.5 mr-1" />
                                            Accreditation
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: EXTERNAL EXAMINERS REGISTRY -->
            <div v-if="activeTab === 'examiners'" class="space-y-4">
                <div class="rounded-2xl border border-border/70 bg-card overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-muted/40 border-b text-muted-foreground font-semibold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="py-3 px-4">External Examiner</th>
                                    <th class="py-3 px-4">Institution & Department</th>
                                    <th class="py-3 px-4">Field of Specialization</th>
                                    <th class="py-3 px-4">Tenure / Service History</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <tr v-for="exam in externalExaminers.data" :key="exam.id" class="hover:bg-muted/20 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-foreground text-sm">{{ exam.title }} {{ exam.full_name }}</div>
                                        <div class="text-[11px] text-muted-foreground">{{ exam.email }} &bull; {{ exam.phone || 'No phone' }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-foreground">{{ exam.institution }}</div>
                                        <div class="text-muted-foreground">{{ exam.department }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-foreground">
                                        {{ exam.specialization }}
                                    </td>
                                    <td class="py-3 px-4 space-y-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <Badge
                                                variant="outline"
                                                class="text-[10px] font-bold"
                                                :class="exam.consecutive_sessions_count >= 3 ? 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 border-amber-300' : 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300'"
                                            >
                                                {{ exam.consecutive_sessions_count }} Sessions Served
                                            </Badge>
                                        </div>
                                        <div class="text-[10px] text-muted-foreground">
                                            Total Theses Examined: <strong>{{ exam.total_theses_examined }}</strong>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span v-if="exam.is_active" class="inline-flex items-center gap-1 text-emerald-600 font-bold text-[11px]">
                                            <CheckCircle2 class="w-3.5 h-3.5" /> Active
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-muted-foreground font-semibold text-[11px]">
                                            <XCircle class="w-3.5 h-3.5" /> Inactive
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <Button size="sm" variant="ghost" class="h-7 text-xs" @click="toggleExaminer(exam.id)">
                                            {{ exam.is_active ? 'Deactivate' : 'Activate' }}
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal: Edit Staff Accreditation -->
            <Dialog v-model:open="accreditationDialogOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>SPGS Supervisory Accreditation</DialogTitle>
                        <DialogDescription>
                            Configure doctoral/masters supervisory eligibility and NUC quota caps for {{ selectedStaff?.name }}.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitAccreditation" class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-4 p-3 rounded-xl bg-muted/20 border">
                            <label class="flex items-center gap-2 cursor-pointer font-semibold">
                                <input type="checkbox" v-model="accreditationForm.is_phd_accredited" class="rounded border-border text-primary" />
                                Ph.D Doctoral Supervisor
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer font-semibold">
                                <input type="checkbox" v-model="accreditationForm.is_masters_accredited" class="rounded border-border text-primary" />
                                Master's Supervisor
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label class="text-xs">Max Ph.D Quota (NUC Cap: 5)</Label>
                                <Input type="number" v-model="accreditationForm.max_phd_quota" min="1" max="20" class="h-9" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Max Master's Quota (NUC Cap: 8)</Label>
                                <Input type="number" v-model="accreditationForm.max_masters_quota" min="1" max="30" class="h-9" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs">Specialization Areas (Comma Separated)</Label>
                            <Input v-model="accreditationForm.specialization_areas" placeholder="e.g. Machine Learning, Cloud Systems, Telecommunications" class="h-9" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label class="text-xs">Scopus Author ID</Label>
                                <Input v-model="accreditationForm.scopus_id" placeholder="e.g. 57201948210" class="h-9" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">ORCID ID</Label>
                                <Input v-model="accreditationForm.orcid_id" placeholder="e.g. 0000-0002-1825-0097" class="h-9" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs">Google Scholar Profile URL</Label>
                            <Input v-model="accreditationForm.google_scholar_url" placeholder="https://scholar.google.com/citations?user=..." class="h-9" />
                        </div>

                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="accreditationDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" :disabled="accreditationForm.processing">Save Accreditation</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Modal: Add External Examiner -->
            <Dialog v-model:open="examinerDialogOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Catalog External Examiner</DialogTitle>
                        <DialogDescription>
                            Add an accredited professor from a peer institution to the institutional external examiners database.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitExaminer" class="space-y-4 text-xs">
                        <div class="grid grid-cols-4 gap-3">
                            <div class="col-span-1 space-y-1">
                                <Label class="text-xs">Title</Label>
                                <Input v-model="examinerForm.title" placeholder="Prof." class="h-9" />
                            </div>
                            <div class="col-span-3 space-y-1">
                                <Label class="text-xs">Full Name *</Label>
                                <Input v-model="examinerForm.full_name" placeholder="Folashade B. Ogundipe" required class="h-9" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label class="text-xs">Awarding Institution *</Label>
                                <Input v-model="examinerForm.institution" placeholder="University of Ibadan" required class="h-9" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Department *</Label>
                                <Input v-model="examinerForm.department" placeholder="Department of Biochemistry" required class="h-9" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label class="text-xs">Official Email Address *</Label>
                                <Input type="email" v-model="examinerForm.email" placeholder="f.ogundipe@ui.edu.ng" required class="h-9" />
                            </div>
                            <div class="space-y-1">
                                <Label class="text-xs">Phone Number</Label>
                                <Input v-model="examinerForm.phone" placeholder="+234..." class="h-9" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-xs">Field of Specialization / Concentration *</Label>
                            <Input v-model="examinerForm.specialization" placeholder="e.g. Molecular Proteomics & Drug Delivery" required class="h-9" />
                        </div>

                        <DialogFooter class="pt-2">
                            <Button type="button" variant="outline" size="sm" @click="examinerDialogOpen = false">Cancel</Button>
                            <Button type="submit" size="sm" :disabled="examinerForm.processing">Add to Registry</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>

