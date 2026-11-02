<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import SearchableSelect from '@/components/SearchableSelect.vue';
import {
    Search, Check, X, Clock, CheckCircle2, XCircle, ArrowLeft,
    CheckCheck, FileText, Building2, ChevronLeft,
    ChevronRight, RotateCcw, Layers, Calendar, CheckSquare
} from 'lucide-vue-next';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';

interface AllocationRequestItem {
    id: string;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    notes?: string | null;
    rejection_reason?: string | null;
    created_at: string;
    staff?: {
        id: string;
        staff_number: string;
        user?: {
            id: string;
            name: string;
            email: string;
        };
        department?: {
            id: string;
            name: string;
            code: string;
        } | null;
    };
    course?: {
        id: string;
        code: string;
        title: string;
        units: number;
        level: number;
        semester: string;
    };
    department?: {
        id: string;
        name: string;
        code: string;
        faculty?: {
            id: string;
            name: string;
        };
    };
    session?: {
        id: string;
        name: string;
    };
    reviewer?: {
        id: string;
        name: string;
    } | null;
}

const props = defineProps<{
    requests: {
        data: AllocationRequestItem[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
        current_page: number;
        last_page: number;
        per_page: number;
    };
    sessions: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string; departments: Array<{ id: string; name: string; code?: string }> }>;
    stats?: {
        pending: number;
        approved: number;
        rejected: number;
        total: number;
    };
    filters: {
        session_id?: string;
        status?: string;
        department_id?: string;
        faculty_id?: string;
        search?: string;
    };
    currentSessionId?: string;
    isHod: boolean;
    isDean: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Course Allocations', href: route().has('admin.course-allocations.index') ? route('admin.course-allocations.index') : '/admin/course-allocations' },
    { title: 'Allocation Requests', href: '#' },
];

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || props.currentSessionId || 'ALL');
const selectedStatus = ref(props.filters.status || 'pending');
const selectedFaculty = ref(props.filters.faculty_id || 'ALL');
const selectedDepartment = ref(props.filters.department_id || 'ALL');

// Select options
const sessionOptions = computed(() => [
    { value: 'ALL', label: 'All Sessions' },
    ...props.sessions.map(s => ({ value: String(s.id), label: s.name }))
]);

const statusOptions = [
    { value: 'ALL', label: 'All Statuses' },
    { value: 'pending', label: 'Pending Review' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Declined' },
];

const facultyOptions = computed(() => [
    { value: 'ALL', label: 'All Faculties' },
    ...props.faculties.map(f => ({ value: String(f.id), label: f.name }))
]);

const departmentOptions = computed(() => {
    if (selectedFaculty.value && selectedFaculty.value !== 'ALL') {
        const fac = props.faculties.find(f => String(f.id) === String(selectedFaculty.value));
        const depts = fac?.departments || [];
        return [
            { value: 'ALL', label: 'All Departments in Faculty' },
            ...depts.map(d => ({ value: String(d.id), label: d.name }))
        ];
    }
    // Flatten all departments from all faculties
    const allDepts: Array<{ id: string; name: string }> = [];
    props.faculties.forEach(f => {
        if (f.departments) {
            allDepts.push(...f.departments);
        }
    });
    return [
        { value: 'ALL', label: 'All Departments' },
        ...allDepts.map(d => ({ value: String(d.id), label: d.name }))
    ];
});

// Watch faculty change to reset department if it doesn't belong
watch(selectedFaculty, (newFac) => {
    if (newFac !== 'ALL') {
        const fac = props.faculties.find(f => String(f.id) === String(newFac));
        const hasDept = fac?.departments?.some(d => String(d.id) === String(selectedDepartment.value));
        if (!hasDept && selectedDepartment.value !== 'ALL') {
            selectedDepartment.value = 'ALL';
        }
    }
});

const isFilterActive = computed(() => {
    return Boolean(
        search.value ||
        (selectedStatus.value && selectedStatus.value !== 'pending') ||
        (selectedFaculty.value && selectedFaculty.value !== 'ALL') ||
        (selectedDepartment.value && selectedDepartment.value !== 'ALL') ||
        (selectedSession.value && selectedSession.value !== (props.currentSessionId || 'ALL'))
    );
});

const resetFilters = () => {
    search.value = '';
    selectedStatus.value = 'pending';
    selectedFaculty.value = 'ALL';
    selectedDepartment.value = 'ALL';
    selectedSession.value = props.currentSessionId || 'ALL';
};

const updateFilters = debounce(() => {
    router.get(route('admin.course-allocations.requests.index'), {
        search: search.value || undefined,
        session_id: selectedSession.value === 'ALL' ? undefined : selectedSession.value,
        status: selectedStatus.value === 'ALL' ? undefined : selectedStatus.value,
        faculty_id: selectedFaculty.value === 'ALL' ? undefined : selectedFaculty.value,
        department_id: selectedDepartment.value === 'ALL' ? undefined : selectedDepartment.value,
    }, { preserveState: true, replace: true });
}, 300);

watch([search, selectedSession, selectedStatus, selectedFaculty, selectedDepartment], () => {
    updateFilters();
});

// Quick status filter helper
const setStatusFilter = (status: string) => {
    selectedStatus.value = status;
};

// Avatar & styling helpers
const getInitials = (name?: string) => {
    if (!name) return '??';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const getAvatarColor = (name?: string) => {
    const palette = [
        'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-900',
        'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-900',
        'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-900',
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900',
        'bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 border-teal-200 dark:border-teal-900',
        'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-900',
    ];
    if (!name) return palette[0];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return palette[Math.abs(hash) % palette.length];
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

// Single Approve
const approvingId = ref<string | null>(null);

const approveRequest = (req: AllocationRequestItem) => {
    Swal.fire({
        title: 'Approve Course Request?',
        html: `<p class="text-sm text-slate-600 dark:text-slate-300">Assign <strong>${req.course?.code} - ${req.course?.title}</strong> to <strong>${req.staff?.user?.name}</strong>?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Approve Allocation',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            approvingId.value = req.id;
            router.post(route('admin.course-allocations.requests.approve', req.id), {}, {
                preserveScroll: true,
                onFinish: () => {
                    approvingId.value = null;
                },
                onSuccess: () => {
                    Swal.fire({
                        title: 'Approved!',
                        text: `${req.course?.code} has been successfully allocated.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};

// Rejection Modal
const isRejectModalOpen = ref(false);
const activeRequestForReject = ref<AllocationRequestItem | null>(null);
const rejectForm = useForm({
    reason: '',
});

const openRejectModal = (req: AllocationRequestItem) => {
    activeRequestForReject.value = req;
    rejectForm.reason = '';
    isRejectModalOpen.value = true;
};

const submitReject = () => {
    if (!activeRequestForReject.value) return;

    rejectForm.post(route('admin.course-allocations.requests.reject', activeRequestForReject.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isRejectModalOpen.value = false;
            rejectForm.reset();
            Swal.fire({
                title: 'Declined',
                text: 'The course allocation request has been declined.',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false,
            });
        }
    });
};

// Batch Selection
const selectedRequestIds = ref<string[]>([]);
const isBatchProcessing = ref(false);

const pendingRequestsOnPage = computed(() => {
    return props.requests.data.filter(r => r.status === 'pending');
});

const isAllSelected = computed(() => {
    return pendingRequestsOnPage.value.length > 0 &&
        pendingRequestsOnPage.value.every(r => selectedRequestIds.value.includes(r.id));
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedRequestIds.value = [];
    } else {
        selectedRequestIds.value = pendingRequestsOnPage.value.map(r => r.id);
    }
};

const clearSelection = () => {
    selectedRequestIds.value = [];
};

watch(() => props.requests.data, () => {
    selectedRequestIds.value = [];
});

const batchApprove = () => {
    if (selectedRequestIds.value.length === 0) return;

    Swal.fire({
        title: 'Batch Approve Requests?',
        html: `<p class="text-sm text-slate-600 dark:text-slate-300">Are you sure you want to approve <strong>${selectedRequestIds.value.length}</strong> selected course allocation request(s)?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Yes, Approve All (${selectedRequestIds.value.length})`,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            isBatchProcessing.value = true;
            router.post(route('admin.course-allocations.requests.batch-approve'), {
                request_ids: selectedRequestIds.value,
            }, {
                preserveScroll: true,
                onFinish: () => {
                    isBatchProcessing.value = false;
                },
                onSuccess: () => {
                    selectedRequestIds.value = [];
                    Swal.fire({
                        title: 'Batch Approved!',
                        text: 'Selected course requests were processed successfully.',
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Course Allocation Requests" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-4 sm:px-6 lg:px-8 space-y-6 w-full max-w-[1600px] mx-auto">
            <!-- Top Hero Bar -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <Button variant="ghost" size="sm" as-child class="h-8 px-2.5 -ml-2 text-muted-foreground hover:text-foreground">
                            <Link :href="route('admin.course-allocations.index')">
                                <ArrowLeft class="w-4 h-4 mr-1.5" /> Back to Direct Allocations
                            </Link>
                        </Button>
                        <Badge variant="outline" class="font-normal text-xs bg-muted/40">
                            Academic Workflow
                        </Badge>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
                        Course Allocation Requests
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Review, verify, and approve course allocations requested by academic lecturers.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="selectedRequestIds.length > 0"
                        @click="batchApprove"
                        :disabled="isBatchProcessing"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                    >
                        <CheckCheck class="w-4 h-4 mr-2" />
                        Approve Selected ({{ selectedRequestIds.length }})
                    </Button>
                </div>
            </div>

            <!-- Smart KPI Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Pending Card -->
                <Card
                    @click="setStatusFilter('pending')"
                    class="cursor-pointer transition-all hover:shadow-md border-l-4 border-l-amber-500 relative overflow-hidden group"
                    :class="{ 'ring-2 ring-amber-500/50 shadow-sm bg-amber-50/20 dark:bg-amber-950/10': selectedStatus === 'pending' }"
                >
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Pending Review</span>
                            <span class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <Clock class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-foreground tracking-tight">
                                {{ stats?.pending ?? 0 }}
                            </span>
                            <Badge v-if="(stats?.pending ?? 0) > 0" variant="outline" class="text-[10px] bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 animate-pulse">
                                Requires Action
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Awaiting department & faculty approval
                        </p>
                    </CardContent>
                </Card>

                <!-- Approved Card -->
                <Card
                    @click="setStatusFilter('approved')"
                    class="cursor-pointer transition-all hover:shadow-md border-l-4 border-l-emerald-500 relative overflow-hidden group"
                    :class="{ 'ring-2 ring-emerald-500/50 shadow-sm bg-emerald-50/20 dark:bg-emerald-950/10': selectedStatus === 'approved' }"
                >
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Approved</span>
                            <span class="h-8 w-8 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <CheckCircle2 class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-foreground tracking-tight">
                                {{ stats?.approved ?? 0 }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Confirmed courses assigned to lecturers
                        </p>
                    </CardContent>
                </Card>

                <!-- Declined Card -->
                <Card
                    @click="setStatusFilter('rejected')"
                    class="cursor-pointer transition-all hover:shadow-md border-l-4 border-l-rose-500 relative overflow-hidden group"
                    :class="{ 'ring-2 ring-rose-500/50 shadow-sm bg-rose-50/20 dark:bg-rose-950/10': selectedStatus === 'rejected' }"
                >
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Declined</span>
                            <span class="h-8 w-8 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <XCircle class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-foreground tracking-tight">
                                {{ stats?.rejected ?? 0 }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Turned down with documented reasons
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Submissions Card -->
                <Card
                    @click="setStatusFilter('ALL')"
                    class="cursor-pointer transition-all hover:shadow-md border-l-4 border-l-indigo-500 relative overflow-hidden group"
                    :class="{ 'ring-2 ring-indigo-500/50 shadow-sm bg-indigo-50/20 dark:bg-indigo-950/10': selectedStatus === 'ALL' }"
                >
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Requests</span>
                            <span class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <Layers class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-foreground tracking-tight">
                                {{ stats?.total ?? 0 }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            All submissions within session scope
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Intelligent Filter Bar -->
            <Card class="shadow-sm border-border">
                <CardContent class="p-4 sm:p-5">
                    <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
                        <!-- Search Box -->
                        <div class="relative flex-1 min-w-[260px]">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Search by course code, title, lecturer name, or staff ID..."
                                class="pl-9 pr-9 h-10 w-full text-sm bg-background"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Dropdown Filters Row -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Status Filter -->
                            <div class="w-full sm:w-44">
                                <SearchableSelect
                                    v-model="selectedStatus"
                                    :options="statusOptions"
                                    placeholder="Filter Status"
                                    searchPlaceholder="Search status..."
                                    triggerClass="h-10 text-xs font-medium"
                                />
                            </div>

                            <!-- Session Filter -->
                            <div class="w-full sm:w-44">
                                <SearchableSelect
                                    v-model="selectedSession"
                                    :options="sessionOptions"
                                    placeholder="Academic Session"
                                    searchPlaceholder="Search session..."
                                    triggerClass="h-10 text-xs font-medium"
                                />
                            </div>

                            <!-- Faculty Filter (Admin only) -->
                            <div v-if="!isHod && !isDean" class="w-full sm:w-48">
                                <SearchableSelect
                                    v-model="selectedFaculty"
                                    :options="facultyOptions"
                                    placeholder="All Faculties"
                                    searchPlaceholder="Search faculty..."
                                    triggerClass="h-10 text-xs font-medium"
                                />
                            </div>

                            <!-- Department Filter (Admin & Dean) -->
                            <div v-if="!isHod" class="w-full sm:w-52">
                                <SearchableSelect
                                    v-model="selectedDepartment"
                                    :options="departmentOptions"
                                    placeholder="All Departments"
                                    searchPlaceholder="Search department..."
                                    triggerClass="h-10 text-xs font-medium"
                                />
                            </div>

                            <!-- Reset Button -->
                            <Button
                                v-if="isFilterActive"
                                variant="ghost"
                                size="sm"
                                @click="resetFilters"
                                class="h-10 px-3 text-xs text-muted-foreground hover:text-foreground hover:bg-muted"
                                title="Reset all filters"
                            >
                                <RotateCcw class="w-3.5 h-3.5 mr-1.5" />
                                Reset
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table of Requests -->
            <Card class="shadow-sm border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/30">
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-12 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        :disabled="pendingRequestsOnPage.length === 0"
                                        class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer disabled:cursor-not-allowed"
                                        title="Select all pending on this page"
                                    />
                                </TableHead>
                                <TableHead class="min-w-[220px]">Lecturer</TableHead>
                                <TableHead class="min-w-[240px]">Course Details</TableHead>
                                <TableHead class="min-w-[170px]">Department / Faculty</TableHead>
                                <TableHead class="min-w-[120px] text-center">Workload</TableHead>
                                <TableHead class="min-w-[110px] text-center">Session</TableHead>
                                <TableHead class="min-w-[130px] text-center">Status</TableHead>
                                <TableHead class="min-w-[180px]">Request Info</TableHead>
                                <TableHead class="min-w-[160px] text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="req in requests.data"
                                :key="req.id"
                                class="transition-colors hover:bg-muted/20"
                                :class="{ 'bg-muted/10': selectedRequestIds.includes(req.id) }"
                            >
                                <!-- Checkbox -->
                                <TableCell class="text-center align-middle">
                                    <input
                                        type="checkbox"
                                        :value="req.id"
                                        v-model="selectedRequestIds"
                                        :disabled="req.status !== 'pending'"
                                        class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                    />
                                </TableCell>

                                <!-- Lecturer -->
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs border shrink-0"
                                            :class="getAvatarColor(req.staff?.user?.name)"
                                        >
                                            {{ getInitials(req.staff?.user?.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-sm text-foreground truncate">
                                                {{ req.staff?.user?.name || 'Unknown Staff' }}
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                                <span class="font-mono bg-muted/60 px-1 py-0.5 rounded text-[11px] font-medium">
                                                    {{ req.staff?.staff_number || 'N/A' }}
                                                </span>
                                                <span class="truncate">&bull; {{ req.staff?.user?.email }}</span>
                                            </div>
                                            <div v-if="req.staff?.department" class="text-[11px] text-primary font-medium mt-0.5 flex items-center gap-1">
                                                <Building2 class="w-3 h-3 text-muted-foreground" />
                                                <span>{{ req.staff.department.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Course Details -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20">
                                                {{ req.course?.code }}
                                            </span>
                                            <span class="text-xs font-semibold text-muted-foreground">
                                                {{ req.course?.level }}L
                                            </span>
                                            <Badge variant="secondary" class="text-[10px] font-normal px-1.5 py-0">
                                                {{ req.course?.semester === '1' ? '1st Sem' : (req.course?.semester === '2' ? '2nd Sem' : 'All Sem') }}
                                            </Badge>
                                        </div>
                                        <div class="text-sm font-medium text-foreground line-clamp-1" :title="req.course?.title">
                                            {{ req.course?.title }}
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Department / Faculty -->
                                <TableCell>
                                    <div class="text-xs space-y-0.5">
                                        <div class="font-medium text-foreground">
                                            {{ req.department?.name || 'N/A' }}
                                        </div>
                                        <div class="text-muted-foreground text-[11px] truncate">
                                            {{ req.department?.faculty?.name }}
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Workload / Units -->
                                <TableCell class="text-center align-middle">
                                    <Badge variant="outline" class="font-semibold text-xs border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                                        {{ req.course?.units ?? 0 }} Units
                                    </Badge>
                                </TableCell>

                                <!-- Session -->
                                <TableCell class="text-center align-middle">
                                    <Badge variant="secondary" class="text-xs font-normal">
                                        {{ req.session?.name || 'N/A' }}
                                    </Badge>
                                </TableCell>

                                <!-- Status Badge -->
                                <TableCell class="text-center align-middle">
                                    <Badge
                                        v-if="req.status === 'pending'"
                                        variant="outline"
                                        class="border-amber-300 text-amber-700 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-400 font-medium text-xs px-2.5 py-0.5 inline-flex items-center gap-1.5"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Pending Review
                                    </Badge>
                                    <Badge
                                        v-else-if="req.status === 'approved'"
                                        variant="outline"
                                        class="border-emerald-300 text-emerald-700 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 font-medium text-xs px-2.5 py-0.5 inline-flex items-center gap-1.5"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                        Approved
                                    </Badge>
                                    <Badge
                                        v-else-if="req.status === 'rejected'"
                                        variant="outline"
                                        class="border-rose-300 text-rose-700 bg-rose-50 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-400 font-medium text-xs px-2.5 py-0.5 inline-flex items-center gap-1.5"
                                    >
                                        <XCircle class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                                        Declined
                                    </Badge>
                                    <Badge v-else variant="outline" class="text-xs">
                                        {{ req.status }}
                                    </Badge>
                                </TableCell>

                                <!-- Request Info & Notes -->
                                <TableCell class="text-xs">
                                    <div class="space-y-1 max-w-[220px]">
                                        <div class="text-muted-foreground flex items-center gap-1">
                                            <Calendar class="w-3 h-3 text-muted-foreground/70" />
                                            <span>{{ formatDate(req.created_at) }}</span>
                                        </div>
                                        <div
                                            v-if="req.notes"
                                            class="text-foreground text-[11px] italic bg-muted/40 p-1.5 rounded border border-border/50 truncate"
                                            :title="req.notes"
                                        >
                                            "{{ req.notes }}"
                                        </div>
                                        <div
                                            v-if="req.rejection_reason"
                                            class="text-rose-600 dark:text-rose-400 text-[11px] bg-rose-50 dark:bg-rose-950/30 p-1.5 rounded border border-rose-200 dark:border-rose-900"
                                            :title="req.rejection_reason"
                                        >
                                            <span class="font-semibold">Reason:</span> {{ req.rejection_reason }}
                                        </div>
                                        <div v-if="req.reviewer?.name && req.status !== 'pending'" class="text-[11px] text-muted-foreground">
                                            Reviewed by: <span class="font-medium text-foreground">{{ req.reviewer.name }}</span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right align-middle">
                                    <div v-if="req.status === 'pending'" class="flex items-center justify-end gap-1.5">
                                        <Button
                                            size="sm"
                                            @click="approveRequest(req)"
                                            :disabled="approvingId === req.id"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white h-8 px-2.5 text-xs font-semibold shadow-xs"
                                        >
                                            <Check class="w-3.5 h-3.5 mr-1" />
                                            Approve
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="openRejectModal(req)"
                                            class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 h-8 px-2.5 text-xs border-rose-200 dark:border-rose-900"
                                        >
                                            <X class="w-3.5 h-3.5 mr-1" />
                                            Decline
                                        </Button>
                                    </div>
                                    <div v-else class="text-xs text-muted-foreground font-medium">
                                        Completed
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="requests.data.length === 0">
                                <TableCell colspan="9" class="text-center py-16 text-muted-foreground">
                                    <div class="max-w-md mx-auto space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mx-auto text-muted-foreground">
                                            <Clock class="w-6 h-6" />
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="font-semibold text-foreground text-base">No course allocation requests found</h3>
                                            <p class="text-xs text-muted-foreground">
                                                {{ isFilterActive ? 'Try adjusting your search query or filter options.' : 'No allocation requests have been submitted by lecturers yet.' }}
                                            </p>
                                        </div>
                                        <div v-if="isFilterActive" class="pt-2">
                                            <Button variant="outline" size="sm" @click="resetFilters" class="h-8 text-xs">
                                                <RotateCcw class="w-3.5 h-3.5 mr-1.5" /> Reset Filters
                                            </Button>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Comprehensive Pagination & Table Footer -->
                <CardFooter
                    class="border-t border-border p-4 px-6 flex flex-col sm:flex-row justify-between items-center gap-4 bg-muted/20"
                >
                    <!-- Left: Results Range and Summary -->
                    <div class="text-xs text-muted-foreground flex items-center gap-2">
                        <span>
                            Showing
                            <span class="font-semibold text-foreground">{{ requests.total > 0 ? requests.from || 1 : 0 }}</span>
                            to
                            <span class="font-semibold text-foreground">{{ requests.to || 0 }}</span>
                            of
                            <span class="font-semibold text-foreground">{{ requests.total }}</span>
                            course requests
                        </span>
                        <span v-if="requests.last_page > 1" class="text-muted-foreground/60 hidden sm:inline">&bull;</span>
                        <Badge v-if="requests.last_page > 1" variant="outline" class="text-[10px] font-normal py-0 hidden sm:inline-flex">
                            Page {{ requests.current_page }} of {{ requests.last_page }}
                        </Badge>
                    </div>

                    <!-- Right: Responsive Navigation Controls -->
                    <div class="flex items-center gap-1.5">
                        <template v-for="(link, i) in requests.links" :key="i">
                            <!-- Prev Link -->
                            <Button
                                v-if="link.label.includes('Previous') || link.label.includes('&laquo;')"
                                :variant="'outline'"
                                size="sm"
                                :disabled="!link.url"
                                as-child
                                class="h-8 px-2.5 text-xs font-medium"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center"
                                >
                                    <ChevronLeft class="w-4 h-4 mr-1" />
                                    <span class="hidden sm:inline">Previous</span>
                                </Link>
                                <span v-else class="inline-flex items-center text-muted-foreground">
                                    <ChevronLeft class="w-4 h-4 mr-1" />
                                    <span class="hidden sm:inline">Previous</span>
                                </span>
                            </Button>

                            <!-- Next Link -->
                            <Button
                                v-else-if="link.label.includes('Next') || link.label.includes('&raquo;')"
                                :variant="'outline'"
                                size="sm"
                                :disabled="!link.url"
                                as-child
                                class="h-8 px-2.5 text-xs font-medium"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <ChevronRight class="w-4 h-4 ml-1" />
                                </Link>
                                <span v-else class="inline-flex items-center text-muted-foreground">
                                    <span class="hidden sm:inline">Next</span>
                                    <ChevronRight class="w-4 h-4 ml-1" />
                                </span>
                            </Button>

                            <!-- Page Numbers -->
                            <Button
                                v-else
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                as-child
                                class="h-8 min-w-[32px] px-2 text-xs font-semibold rounded-md"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    v-html="link.label"
                                />
                                <span v-else v-html="link.label"></span>
                            </Button>
                        </template>
                    </div>
                </CardFooter>
            </Card>

            <!-- Floating Batch Action Bar -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform translate-y-4 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-4 opacity-0"
            >
                <div
                    v-if="selectedRequestIds.length > 0"
                    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 rounded-full bg-slate-900/95 dark:bg-slate-100/95 text-white dark:text-slate-900 shadow-2xl border border-slate-700/50 backdrop-blur-md"
                >
                    <div class="flex items-center gap-2 text-xs font-medium pr-2 border-r border-slate-700 dark:border-slate-300">
                        <CheckSquare class="w-4 h-4 text-emerald-400 dark:text-emerald-600" />
                        <span><strong>{{ selectedRequestIds.length }}</strong> requests selected</span>
                    </div>

                    <Button
                        size="sm"
                        @click="batchApprove"
                        :disabled="isBatchProcessing"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white h-7 px-3 text-xs font-semibold rounded-full shadow-sm"
                    >
                        <CheckCheck class="w-3.5 h-3.5 mr-1.5" />
                        Approve All Selected
                    </Button>

                    <Button
                        size="sm"
                        variant="ghost"
                        @click="clearSelection"
                        class="h-7 px-2 text-xs text-slate-300 hover:text-white dark:text-slate-600 dark:hover:text-slate-900 rounded-full hover:bg-slate-800 dark:hover:bg-slate-200"
                    >
                        Deselect
                    </Button>
                </div>
            </transition>
        </div>

        <!-- Rejection Dialog -->
        <Dialog v-model:open="isRejectModalOpen">
            <DialogContent class="sm:max-w-[460px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-rose-600">
                        <XCircle class="w-5 h-5" />
                        Decline Course Request
                    </DialogTitle>
                    <DialogDescription>
                        Specify a clear reason why this course request cannot be approved for this lecturer.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="activeRequestForReject" class="py-3 space-y-4">
                    <div class="bg-muted/50 p-3.5 rounded-lg border text-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground font-medium">Course:</span>
                            <span class="font-mono font-bold text-primary">{{ activeRequestForReject.course?.code }}</span>
                        </div>
                        <div class="text-foreground font-medium">
                            {{ activeRequestForReject.course?.title }}
                        </div>
                        <div class="pt-1 border-t border-border/50 flex items-center justify-between text-muted-foreground">
                            <span>Lecturer:</span>
                            <span class="font-semibold text-foreground">{{ activeRequestForReject.staff?.user?.name }}</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="reason" class="text-xs font-semibold">
                            Reason for Declining <span class="text-rose-500">*</span>
                        </Label>
                        <Textarea
                            id="reason"
                            v-model="rejectForm.reason"
                            placeholder="e.g., Course already covered by senior faculty, lecturer exceeds semester workload, etc."
                            rows="3"
                            class="text-xs"
                            required
                        />
                        <p class="text-[11px] text-muted-foreground">
                            This reason will be visible to the lecturer on their course allocation portal.
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" size="sm" @click="isRejectModalOpen = false" :disabled="rejectForm.processing">
                        Cancel
                    </Button>
                    <Button
                        size="sm"
                        @click="submitReject"
                        :disabled="rejectForm.processing || !rejectForm.reason.trim()"
                        class="bg-rose-600 hover:bg-rose-700 text-white"
                    >
                        Confirm Decline
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
