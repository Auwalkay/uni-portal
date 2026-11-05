<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import debounce from 'lodash/debounce';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { 
    Eye, 
    Search, 
    FileText, 
    CheckCircle, 
    XCircle, 
    Clock, 
    Filter,
    GraduationCap,
    Users,
    Award
} from 'lucide-vue-next';
import StatsCard from '@/components/StatsCard.vue';
import { format } from 'date-fns';
import { type BreadcrumbItem } from '@/types';

interface ApplicantItem {
    id: string;
    application_number?: string;
    jamb_registration_number?: string;
    status: string;
    has_paid_application?: boolean;
    created_at: string;
    previous_institution?: string;
    previous_qualification?: string;
    previous_class_of_degree?: string;
    previous_cgpa?: number | string | null;
    user: {
        name: string;
        email: string;
    };
    programme?: {
        name: string;
        type: string;
    };
    referees?: Array<{
        id: string;
        status: string;
    }>;
    qualifications?: Array<{
        institution_name: string;
        qualification_type: string;
        class_of_degree: string;
        cgpa?: string | number | null;
    }>;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedApplicants {
    data: Array<ApplicantItem>;
    links: Array<PaginationLink>;
    current_page: number;
    first_page_url?: string;
    from: number | null;
    last_page: number;
    last_page_url?: string;
    next_page_url?: string | null;
    path?: string;
    per_page: number;
    prev_page_url?: string | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    applicants: PaginatedApplicants;
    stats: {
        total: number;
        admitted: number;
        rejected: number;
        pending: number;
        screening: number;
    };
    filters: {
        search?: string;
        status?: string;
        programme_id?: string;
        tier?: string;
        per_page?: string | number;
    };
    programmes: Array<{ id: string; name: string }>;
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
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const programme = ref(props.filters.programme_id || '');
const tier = ref(props.filters.tier || '');
const perPage = ref(String(props.filters.per_page || '15'));

const updateFilters = debounce(() => {
    const targetRoute = route().has('admin.admissions.pg.index')
        ? route('admin.admissions.pg.index')
        : '/admin/admissions/pg';

    router.get(
        targetRoute,
        { 
            search: search.value, 
            status: status.value,
            programme_id: programme.value,
            tier: tier.value,
            per_page: perPage.value
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch([search, status, programme, tier, perPage], updateFilters);

const getStatusColorClass = (status: string) => {
    switch (status) {
        case 'admitted': return 'bg-green-100 text-green-700 hover:bg-green-100/80 border-green-200';
        case 'rejected': return 'bg-red-100 text-red-700 hover:bg-red-100/80 border-red-200';
        case 'pending': return 'bg-yellow-100 text-yellow-700 hover:bg-yellow-100/80 border-yellow-200';
        case 'screening': return 'bg-purple-100 text-purple-700 hover:bg-purple-100/80 border-purple-200';
        case 'submitted': return 'bg-blue-100 text-blue-700 hover:bg-blue-100/80 border-blue-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

const getTierColorClass = (type?: string) => {
    switch (type) {
        case 'PHD': return 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-300 dark:border-purple-800';
        case 'MPHIL': return 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-800';
        case 'MSC':
        case 'MASTERS': return 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-800';
        case 'MBA':
        case 'MIM': return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-800';
        case 'PGD': return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800';
        default: return 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-300';
    }
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    programme.value = '';
    tier.value = '';
    perPage.value = '15';
};
</script>

<template>
    <Head title="Postgraduate Applicants (SPGS)" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight">Postgraduate Applicants</h1>
                        <Badge variant="outline" class="bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800 text-xs">
                            SPGS Operations
                        </Badge>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        School of Postgraduate Studies application review, referee evaluations, and admissions screening.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link v-if="user_permissions?.can_view_ug" href="/admin/admissions/ug">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                            ← Switch to UG Applicants
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total PG Applicants"
                    :value="stats.total.toLocaleString()"
                    description="All registered PG candidates"
                    :icon="FileText"
                />
                <StatsCard
                    title="Admitted to SPGS"
                    :value="stats.admitted.toLocaleString()"
                    description="Official offers granted"
                    :icon="CheckCircle"
                />
                <StatsCard
                    title="Under Screening"
                    :value="stats.screening.toLocaleString()"
                    description="Application & referee review"
                    :icon="GraduationCap"
                />
                <StatsCard
                    title="Pending Review"
                    :value="stats.pending.toLocaleString()"
                    description="Awaiting initial processing"
                    :icon="Clock"
                />
            </div>

            <!-- Main Content Area -->
            <div class="space-y-4">
                <!-- Filters Bar -->
                <div class="flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between bg-card p-4 rounded-lg border">
                    <div class="flex flex-1 flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                        <div class="relative flex-1">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search by name, email, or application number..."
                                class="pl-9 h-9"
                            />
                        </div>
                        
                        <!-- Status Filter -->
                        <select 
                            v-model="status" 
                            class="h-9 w-full sm:w-[150px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="screening">Screening</option>
                            <option value="admitted">Admitted</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <!-- PG Tier Filter -->
                        <select 
                            v-model="tier" 
                            class="h-9 w-full sm:w-[160px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="">All PG Tiers</option>
                            <option value="PGD">PGD (700L)</option>
                            <option value="MSC">Academic Masters (M.Sc)</option>
                            <option value="MBA">Professional Masters</option>
                            <option value="MPHIL">M.Phil</option>
                            <option value="PHD">Doctorate (Ph.D)</option>
                        </select>

                        <!-- Programme Filter -->
                        <select 
                            v-model="programme" 
                            class="h-9 w-full sm:w-[200px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring truncate"
                        >
                            <option value="">All Programmes</option>
                            <option v-for="prog in programmes" :key="prog.id" :value="prog.id">
                                {{ prog.name }}
                            </option>
                        </select>

                        <!-- Per Page Filter -->
                        <select 
                            v-model="perPage" 
                            class="h-9 w-full sm:w-[110px] rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            title="Rows per page"
                        >
                            <option value="10">10 / page</option>
                            <option value="15">15 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                    </div>

                    <Button 
                        v-if="search || status || programme || tier || perPage !== '15'" 
                        variant="ghost" 
                        size="sm"
                        @click="clearFilters"
                        class="h-9 px-3 text-xs"
                    >
                        Reset Filters
                    </Button>
                </div>

                <!-- Table -->
                <div class="rounded-md border bg-card overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/40">
                                <TableHead>Candidate</TableHead>
                                <TableHead>Programme & Tier</TableHead>
                                <TableHead>Previous Degree / CGPA</TableHead>
                                <TableHead>Referees</TableHead>
                                <TableHead>Applied Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="applicant in applicants.data" :key="applicant.id" class="hover:bg-muted/50 transition">
                                <TableCell>
                                    <div class="font-medium text-sm text-foreground">{{ applicant.user.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ applicant.user.email }}</div>
                                    <Badge variant="outline" class="font-mono text-[10px] mt-1">
                                        {{ applicant.application_number || applicant.jamb_registration_number || 'N/A' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-1 max-w-[240px]">
                                        <span class="text-xs font-semibold text-foreground truncate" :title="applicant.programme?.name">
                                            {{ applicant.programme?.name || 'N/A' }}
                                        </span>
                                        <Badge variant="outline" :class="getTierColorClass(applicant.programme?.type)" class="w-fit text-[10px] uppercase font-bold">
                                            {{ applicant.programme?.type || 'POSTGRADUATE' }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="applicant.qualifications && applicant.qualifications.length > 0" class="text-xs">
                                        <div class="font-medium truncate max-w-[200px]">
                                            {{ applicant.qualifications[0].qualification_type }} — {{ applicant.qualifications[0].class_of_degree }}
                                        </div>
                                        <div class="text-[11px] text-muted-foreground truncate max-w-[200px]">
                                            {{ applicant.qualifications[0].institution_name }}
                                            <span v-if="applicant.qualifications[0].cgpa" class="font-mono font-semibold text-foreground ml-1">
                                                (CGPA: {{ applicant.qualifications[0].cgpa }})
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else-if="applicant.previous_qualification" class="text-xs">
                                        <div class="font-medium truncate max-w-[200px]">
                                            {{ applicant.previous_qualification }} — {{ applicant.previous_class_of_degree || 'N/A' }}
                                        </div>
                                        <div class="text-[11px] text-muted-foreground truncate max-w-[200px]">
                                            {{ applicant.previous_institution || 'N/A' }}
                                            <span v-if="applicant.previous_cgpa" class="font-mono font-semibold text-foreground ml-1">
                                                ({{ applicant.previous_cgpa }})
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-muted-foreground italic">
                                        No previous qualification logged
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="applicant.referees && applicant.referees.length > 0" class="flex items-center gap-1.5">
                                        <Badge 
                                            :variant="applicant.referees.filter(r => r.status === 'submitted').length >= 3 ? 'default' : 'secondary'"
                                            class="text-xs font-mono"
                                        >
                                            {{ applicant.referees.filter(r => r.status === 'submitted').length }} / {{ applicant.referees.length }}
                                        </Badge>
                                        <span class="text-[11px] text-muted-foreground">Submitted</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground italic">— None</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-xs whitespace-nowrap">
                                    {{ format(new Date(applicant.created_at), 'MMM d, yyyy') }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <Badge :class="getStatusColorClass(applicant.status)" class="capitalize shadow-none text-xs w-fit">
                                            {{ applicant.status }}
                                        </Badge>
                                        <span class="text-[10px] font-medium" :class="applicant.has_paid_application ? 'text-green-600' : 'text-amber-600'">
                                            {{ applicant.has_paid_application ? '● Fee Paid' : '○ Fee Unpaid' }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route().has('admin.admissions.pg.show') ? route('admin.admissions.pg.show', applicant.id) : `/admin/admissions/pg/${applicant.id}`">
                                        <Button variant="ghost" size="sm" class="h-8 w-8 p-0" title="Review PG Application">
                                            <Eye class="h-4 w-4 text-muted-foreground hover:text-primary" />
                                            <span class="sr-only">View</span>
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="applicants.data.length === 0">
                                <TableCell colspan="7" class="h-28 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <GraduationCap class="w-8 h-8 opacity-30" />
                                        <p class="font-medium">No postgraduate applications found</p>
                                        <p class="text-xs">Adjust your search, status, or tier filters.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination Footer -->
                    <div v-if="applicants.total > 0" class="flex flex-col sm:flex-row items-center justify-between border-t p-4 px-6 gap-4 bg-muted/20">
                        <div class="text-xs text-muted-foreground font-medium">
                            Showing <span class="font-bold text-foreground">{{ applicants.from || 0 }}</span> to <span class="font-bold text-foreground">{{ applicants.to || 0 }}</span> of <span class="font-bold text-foreground">{{ applicants.total }}</span> postgraduate applications
                        </div>
                        <div v-if="applicants.links && applicants.links.length > 3" class="flex flex-wrap items-center gap-1">
                            <Button 
                                v-for="(link, i) in applicants.links" 
                                :key="i"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                as-child
                                class="h-8 min-w-[32px] px-2 text-xs font-semibold rounded-md"
                            >
                                <Link v-if="link.url" :href="link.url" preserve-scroll preserve-state v-html="link.label" />
                                <span v-else v-html="link.label"></span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

