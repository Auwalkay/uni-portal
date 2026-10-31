<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Pagination from '@/components/Pagination.vue';
import {
    Search, Check, X, Clock, CheckCircle2, XCircle, ArrowLeft,
    CheckCheck, FileText, AlertCircle, Building2, User
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
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    sessions: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string; departments: Array<any> }>;
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

// Single Approve
const approveRequest = (req: AllocationRequestItem) => {
    Swal.fire({
        title: 'Approve Course Request?',
        text: `Assign ${req.course?.code} (${req.course?.title}) to ${req.staff?.user?.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Approve',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.course-allocations.requests.approve', req.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Approved!', 'Course has been allocated to the lecturer.', 'success');
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
            Swal.fire('Declined', 'The course request has been declined.', 'info');
        }
    });
};

// Batch Selection
const selectedRequestIds = ref<string[]>([]);
const selectAll = ref(false);

const pendingRequestsOnPage = computed(() => {
    return props.requests.data.filter(r => r.status === 'pending');
});

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedRequestIds.value = pendingRequestsOnPage.value.map(r => r.id);
    } else {
        selectedRequestIds.value = [];
    }
};

const batchApprove = () => {
    if (selectedRequestIds.value.length === 0) return;

    Swal.fire({
        title: 'Batch Approve Requests?',
        text: `Approve ${selectedRequestIds.value.length} course allocation request(s)?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Approve All',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.course-allocations.requests.batch-approve'), {
                request_ids: selectedRequestIds.value,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedRequestIds.value = [];
                    selectAll.value = false;
                    Swal.fire('Batch Approved!', 'Selected course requests were processed.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Course Allocation Requests" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-6 w-full max-w-[1600px] mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Button variant="ghost" size="sm" as-child class="h-8 px-2 -ml-2 text-muted-foreground hover:text-foreground">
                            <Link :href="route('admin.course-allocations.index')">
                                <ArrowLeft class="w-4 h-4 mr-1" /> Direct Allocations
                            </Link>
                        </Button>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        Course Allocation Requests
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        Review, approve, or decline self course requests submitted by lecturers.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="selectedRequestIds.length > 0"
                        @click="batchApprove"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white"
                    >
                        <CheckCheck class="w-4 h-4 mr-2" /> Approve Selected ({{ selectedRequestIds.length }})
                    </Button>
                </div>
            </div>

            <!-- Filter Controls -->
            <Card>
                <CardContent class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="relative">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Search course or lecturer..."
                                class="pl-9 w-full"
                            />
                        </div>

                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Statuses</SelectItem>
                                <SelectItem value="pending">Pending Review</SelectItem>
                                <SelectItem value="approved">Approved</SelectItem>
                                <SelectItem value="rejected">Declined</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="selectedSession">
                            <SelectTrigger>
                                <SelectValue placeholder="Session" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Sessions</SelectItem>
                                <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">
                                    {{ s.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div v-if="!isHod && !isDean" class="flex gap-2">
                            <Select v-model="selectedFaculty">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="All Faculties" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Faculties</SelectItem>
                                    <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                                        {{ f.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table of Requests -->
            <Card class="overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-10">
                                <input
                                    type="checkbox"
                                    v-model="selectAll"
                                    @change="toggleSelectAll"
                                    :disabled="pendingRequestsOnPage.length === 0"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </TableHead>
                            <TableHead>Lecturer</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Units / Level</TableHead>
                            <TableHead>Session</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Date / Notes</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="req in requests.data" :key="req.id">
                            <TableCell>
                                <input
                                    type="checkbox"
                                    :value="req.id"
                                    v-model="selectedRequestIds"
                                    :disabled="req.status !== 'pending'"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="font-medium text-foreground">{{ req.staff?.user?.name || 'N/A' }}</div>
                                <div class="text-xs text-muted-foreground">{{ req.staff?.staff_number }} &bull; {{ req.staff?.user?.email }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm font-medium">{{ req.department?.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ req.department?.faculty?.name }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="font-mono text-sm font-bold text-indigo-600">{{ req.course?.code }}</div>
                                <div class="text-xs text-muted-foreground line-clamp-1">{{ req.course?.title }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm">{{ req.course?.units }} Units</div>
                                <div class="text-xs text-muted-foreground">{{ req.course?.level }}L &bull; {{ req.course?.semester === '1' ? '1st Sem' : '2nd Sem' }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ req.session?.name }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Badge v-if="req.status === 'pending'" variant="outline" class="border-amber-400 text-amber-600 bg-amber-50">
                                    <Clock class="w-3 h-3 mr-1" /> Pending
                                </Badge>
                                <Badge v-else-if="req.status === 'approved'" variant="outline" class="border-emerald-400 text-emerald-600 bg-emerald-50">
                                    <CheckCircle2 class="w-3 h-3 mr-1" /> Approved
                                </Badge>
                                <Badge v-else-if="req.status === 'rejected'" variant="outline" class="border-rose-400 text-rose-600 bg-rose-50">
                                    <XCircle class="w-3 h-3 mr-1" /> Declined
                                </Badge>
                                <Badge v-else variant="outline">
                                    Cancelled
                                </Badge>
                            </TableCell>
                            <TableCell class="text-xs max-w-xs">
                                <div class="text-muted-foreground">{{ new Date(req.created_at).toLocaleDateString() }}</div>
                                <div v-if="req.notes" class="text-foreground italic mt-0.5 truncate" :title="req.notes">
                                    "{{ req.notes }}"
                                </div>
                                <div v-if="req.rejection_reason" class="text-rose-600 mt-0.5">
                                    Reason: {{ req.rejection_reason }}
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <div v-if="req.status === 'pending'" class="flex items-center justify-end gap-1.5">
                                    <Button
                                        size="sm"
                                        @click="approveRequest(req)"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white h-8 px-2.5"
                                    >
                                        <Check class="w-3.5 h-3.5 mr-1" /> Approve
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="openRejectModal(req)"
                                        class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 h-8 px-2.5 border-rose-200"
                                    >
                                        <X class="w-3.5 h-3.5 mr-1" /> Decline
                                    </Button>
                                </div>
                                <div v-else class="text-xs text-muted-foreground">
                                    Reviewed by {{ req.reviewer?.name || 'Admin' }}
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="requests.data.length === 0">
                            <TableCell colspan="9" class="text-center py-10 text-muted-foreground">
                                <Clock class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                                <p>No course allocation requests found matching the selected filters.</p>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <Pagination :links="requests.links" :from="requests.from" :to="requests.to" :total="requests.total" />
        </div>

        <!-- Rejection Dialog -->
        <Dialog v-model:open="isRejectModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Decline Course Request</DialogTitle>
                    <DialogDescription>
                        Specify a reason why this course cannot be assigned to this lecturer.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="activeRequestForReject" class="py-3 space-y-3">
                    <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-lg border text-sm">
                        <div><strong>Course:</strong> {{ activeRequestForReject.course?.code }} - {{ activeRequestForReject.course?.title }}</div>
                        <div class="text-muted-foreground"><strong>Lecturer:</strong> {{ activeRequestForReject.staff?.user?.name }}</div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="reason">Reason for Declining</Label>
                        <Textarea
                            id="reason"
                            v-model="rejectForm.reason"
                            placeholder="e.g., Course already covered by senior faculty, workload balance, etc."
                            rows="3"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isRejectModalOpen = false" :disabled="rejectForm.processing">
                        Cancel
                    </Button>
                    <Button @click="submitReject" :disabled="rejectForm.processing" class="bg-rose-600 hover:bg-rose-700 text-white">
                        Confirm Decline
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
