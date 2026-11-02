<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    BookOpen, Search, ArrowLeft, CheckCircle2, Clock, XCircle, AlertCircle,
    Lock, Sparkles, Building2, Send, X, Layers, AlertTriangle
} from 'lucide-vue-next';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';

interface CourseItem {
    id: string;
    code: string;
    title: string;
    units: number;
    level: number;
    semester: string;
    status: 'available' | 'allocated_to_me' | 'pending_by_me' | 'taken_by_other';
    holder_name?: string | null;
    department?: {
        id: string;
        name: string;
        code: string;
    } | null;
}

interface RequestItem {
    id: string;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    notes?: string | null;
    rejection_reason?: string | null;
    created_at: string;
    course?: {
        id: string;
        code: string;
        title: string;
        units: number;
    };
    reviewer?: {
        name: string;
    } | null;
}

const props = defineProps<{
    hasDepartment: boolean;
    department?: { id: string; name: string; code: string } | null;
    departments?: Array<{ id: string; name: string; code: string }>;
    isOpen: boolean;
    courses: {
        data: CourseItem[];
        links: any[];
        total?: number;
    };
    requests: RequestItem[];
    stats: {
        allocated_units: number;
        pending_units: number;
        total_units: number;
        min_units: number;
        max_units: number;
        max_courses: number;
        allocated_courses_count: number;
        pending_courses_count: number;
    };
    currentSession?: { id: string; name: string } | null;
    filters: { search?: string; level?: string; semester?: string; department_id?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'My Courses', href: route().has('admin.teaching.courses.index') ? route('admin.teaching.courses.index') : '/admin/staff/courses' },
    { title: 'Self Course Allocation', href: '#' },
];

const search = ref(props.filters.search || '');
const selectedLevel = ref(props.filters.level || 'all');
const selectedSemester = ref(props.filters.semester || 'all');
const selectedDepartment = ref(props.filters.department_id || props.department?.id || 'all');

const updateFilters = debounce(() => {
    router.get(route('admin.teaching.courses.self_allocation'), {
        search: search.value || undefined,
        level: selectedLevel.value === 'all' ? undefined : selectedLevel.value,
        semester: selectedSemester.value === 'all' ? undefined : selectedSemester.value,
        department_id: selectedDepartment.value === 'all' ? undefined : selectedDepartment.value,
    }, { preserveState: true, replace: true });
}, 300);

watch([search, selectedLevel, selectedSemester, selectedDepartment], () => {
    updateFilters();
});

// Modal state for picking course
const selectedCourseForPick = ref<CourseItem | null>(null);
const isPickModalOpen = ref(false);

const pickForm = useForm({
    course_id: '',
    notes: '',
});

const openPickModal = (course: CourseItem) => {
    selectedCourseForPick.value = course;
    pickForm.course_id = course.id;
    pickForm.notes = '';
    isPickModalOpen.value = true;
};

const submitRequest = () => {
    pickForm.post(route('admin.teaching.courses.self_allocation.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isPickModalOpen.value = false;
            pickForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Request Submitted',
                text: 'Your course request was sent to the course department HOD and Dean for review.',
                confirmButtonColor: '#4f46e5',
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire({
                icon: 'error',
                title: 'Cannot Request Course',
                html: msg || 'An error occurred while submitting your request.',
                confirmButtonColor: '#ef4444',
            });
        }
    });
};

const cancelRequest = (id: string, code: string) => {
    Swal.fire({
        title: 'Cancel Request?',
        text: `Are you sure you want to withdraw your request for ${code}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, cancel it',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.teaching.courses.self_allocation.cancel', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Cancelled', 'Your request has been withdrawn.', 'success');
                }
            });
        }
    });
};

// Unit percentage progress
const unitPercentage = computed(() => {
    if (!props.stats.max_units) return 0;
    return Math.min(100, Math.round((props.stats.total_units / props.stats.max_units) * 100));
});

const isBelowMin = computed(() => {
    return props.stats.total_units < props.stats.min_units;
});

const isMaxReached = computed(() => {
    return props.stats.total_units >= props.stats.max_units;
});
</script>

<template>
    <Head title="Self Course Allocation" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <Button variant="ghost" size="sm" as-child class="h-8 px-2 -ml-2 text-muted-foreground hover:text-foreground">
                            <Link :href="route('admin.teaching.courses.index')">
                                <ArrowLeft class="w-4 h-4 mr-1" /> Back to My Courses
                            </Link>
                        </Button>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100 mt-1 flex items-center gap-2">
                        Self Course Allocation
                        <Badge v-if="isOpen" variant="outline" class="border-emerald-500 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30">
                            Window Open
                        </Badge>
                        <Badge v-else variant="outline" class="border-amber-500 text-amber-600 bg-amber-50 dark:bg-amber-950/30">
                            Window Closed
                        </Badge>
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        Select and request courses for the current academic session ({{ currentSession?.name || 'Active Session' }}).
                    </p>
                </div>

                <div v-if="department" class="flex items-center gap-2 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 px-3 py-2 rounded-lg text-sm text-indigo-700 dark:text-indigo-300">
                    <Building2 class="w-4 h-4" />
                    <span>Department: <strong>{{ department.name }} ({{ department.code }})</strong></span>
                </div>
            </div>

            <!-- Window Closed Banner -->
            <div v-if="!isOpen" class="p-4 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-200 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" />
                <div>
                    <h4 class="font-semibold">Self Course Allocation Is Currently Closed</h4>
                    <p class="text-sm mt-0.5">
                        The administration has temporarily closed self course allocation for this session. You can view your requested courses below, but new requests cannot be submitted at this time.
                    </p>
                </div>
            </div>

            <!-- Missing Department Notice (informational) -->
            <div v-if="!hasDepartment" class="p-4 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-200 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" />
                <div>
                    <h4 class="font-semibold">No Primary Department Assigned</h4>
                    <p class="text-sm mt-0.5">
                        Your profile does not have an assigned primary department, but you can browse and request courses across any academic department below.
                    </p>
                </div>
            </div>

            <!-- Workload Status & Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-950/20 dark:to-slate-900 border-indigo-100 dark:border-indigo-900">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-indigo-700 dark:text-indigo-400">Total Workload</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-baseline justify-between">
                            <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
                                {{ stats.total_units }} <span class="text-sm font-normal text-muted-foreground">/ {{ stats.max_units }} Units</span>
                            </div>
                            <span class="text-xs font-semibold text-indigo-600">{{ unitPercentage }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-2 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="isMaxReached ? 'bg-amber-500' : 'bg-indigo-600'"
                                :style="{ width: `${unitPercentage}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Confirmed Allocations</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ stats.allocated_units }} Units
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ stats.allocated_courses_count }} approved course(s)
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Pending Requests</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ stats.pending_units }} Units
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ stats.pending_courses_count }} awaiting HOD/Dean approval
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Workload Policy</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Min Required:</span>
                                <span class="font-semibold">{{ stats.min_units }} Units</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Max Allowed:</span>
                                <span class="font-semibold">{{ stats.max_units }} Units</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Warning if below min units -->
            <div v-if="isBelowMin" class="p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-lg text-sm text-blue-800 dark:text-blue-200 flex items-center gap-2">
                <AlertTriangle class="w-4 h-4 text-blue-600 shrink-0" />
                <span>
                    You have currently selected <strong>{{ stats.total_units }} units</strong>. The minimum required workload for this session is <strong>{{ stats.min_units }} units</strong>.
                </span>
            </div>

            <!-- Main Content Tabs -->
            <Tabs default-value="available" class="w-full">
                <TabsList class="grid w-full md:w-[400px] grid-cols-2">
                    <TabsTrigger value="available">Available Courses</TabsTrigger>
                    <TabsTrigger value="requests">
                        My Requests ({{ requests.length }})
                    </TabsTrigger>
                </TabsList>

                <!-- Available Courses Tab -->
                <TabsContent value="available" class="space-y-4 pt-4">
                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row gap-3 flex-wrap">
                        <div class="relative flex-1 min-w-[200px]">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Search by course code or title..."
                                class="pl-9 w-full"
                            />
                        </div>

                        <Select v-model="selectedDepartment">
                            <SelectTrigger class="w-full md:w-[220px]">
                                <SelectValue placeholder="All Departments" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Departments</SelectItem>
                                <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }} ({{ dept.code }})
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="selectedLevel">
                            <SelectTrigger class="w-[140px]">
                                <SelectValue placeholder="All Levels" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Levels</SelectItem>
                                <SelectItem value="100">100 Level</SelectItem>
                                <SelectItem value="200">200 Level</SelectItem>
                                <SelectItem value="300">300 Level</SelectItem>
                                <SelectItem value="400">400 Level</SelectItem>
                                <SelectItem value="500">500 Level</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="selectedSemester">
                            <SelectTrigger class="w-[150px]">
                                <SelectValue placeholder="All Semesters" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Semesters</SelectItem>
                                <SelectItem value="1">1st Semester</SelectItem>
                                <SelectItem value="2">2nd Semester</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Courses Grid -->
                    <div v-if="courses.data.length > 0">
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <Card
                                v-for="course in courses.data"
                                :key="course.id"
                                class="transition-all duration-200 overflow-hidden flex flex-col justify-between"
                                :class="{
                                    'border-emerald-300 dark:border-emerald-800 bg-emerald-50/20': course.status === 'allocated_to_me',
                                    'border-amber-300 dark:border-amber-800 bg-amber-50/20': course.status === 'pending_by_me',
                                    'opacity-70 bg-slate-50 dark:bg-slate-900': course.status === 'taken_by_other',
                                }"
                            >
                                <CardHeader class="pb-3">
                                    <div class="flex justify-between items-start gap-2 flex-wrap">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <Badge variant="secondary" class="font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-100">
                                                {{ course.code }}
                                            </Badge>
                                            <Badge v-if="course.department" variant="outline" class="text-xs bg-slate-50 dark:bg-slate-900 border-slate-200">
                                                {{ course.department.code }}
                                            </Badge>
                                        </div>
                                        <Badge variant="outline" class="text-xs">
                                            {{ course.semester === '1' ? '1st Sem' : '2nd Sem' }} &bull; {{ course.level }}L
                                        </Badge>
                                    </div>
                                    <CardTitle class="text-base mt-2 line-clamp-2">
                                        {{ course.title }}
                                    </CardTitle>
                                    <div class="flex items-center justify-between text-xs text-muted-foreground mt-1">
                                        <span>{{ course.units }} Credit Units</span>
                                        <span v-if="course.department" class="text-[11px] truncate max-w-[150px]" :title="course.department.name">
                                            {{ course.department.name }}
                                        </span>
                                    </div>
                                </CardHeader>

                                <CardFooter class="pt-2 border-t flex items-center justify-between">
                                    <!-- Status Badge -->
                                    <div v-if="course.status === 'allocated_to_me'" class="flex items-center text-xs font-semibold text-emerald-600">
                                        <CheckCircle2 class="w-3.5 h-3.5 mr-1" /> Allocated to You
                                    </div>
                                    <div v-else-if="course.status === 'pending_by_me'" class="flex items-center text-xs font-semibold text-amber-600">
                                        <Clock class="w-3.5 h-3.5 mr-1" /> Request Pending
                                    </div>
                                    <div v-else-if="course.status === 'taken_by_other'" class="flex items-center text-xs text-muted-foreground" :title="`Held by ${course.holder_name}`">
                                        <Lock class="w-3.5 h-3.5 mr-1" /> {{ course.holder_name }}
                                    </div>
                                    <div v-else class="text-xs text-muted-foreground font-medium">
                                        Available
                                    </div>

                                    <!-- Action Button -->
                                    <Button
                                        v-if="course.status === 'available'"
                                        size="sm"
                                        :disabled="!isOpen || isMaxReached"
                                        @click="openPickModal(course)"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white"
                                    >
                                        <Send class="w-3.5 h-3.5 mr-1.5" /> Request
                                    </Button>

                                    <Button
                                        v-else-if="course.status === 'allocated_to_me'"
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link :href="route('admin.teaching.courses.show', course.id)">
                                            Manage Course
                                        </Link>
                                    </Button>
                                </CardFooter>
                            </Card>
                        </div>

                        <!-- Pagination -->
                        <Pagination :links="courses.links" class="mt-6" />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center border rounded-lg border-dashed text-muted-foreground">
                        <BookOpen class="w-10 h-10 mx-auto text-slate-300 mb-2" />
                        <h3 class="font-semibold text-lg">No courses found</h3>
                        <p class="text-sm mt-1 max-w-sm mx-auto">
                            No courses match your filter criteria. Try adjusting your search, department, or level filter.
                        </p>
                    </div>
                </TabsContent>

                    <!-- My Requests Tab -->
                    <TabsContent value="requests" class="pt-4">
                        <div v-if="requests.length > 0" class="border rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-900 border-b">
                                    <tr>
                                        <th class="p-3 text-left font-medium text-muted-foreground">Course</th>
                                        <th class="p-3 text-left font-medium text-muted-foreground">Units</th>
                                        <th class="p-3 text-left font-medium text-muted-foreground">Requested On</th>
                                        <th class="p-3 text-left font-medium text-muted-foreground">Status</th>
                                        <th class="p-3 text-left font-medium text-muted-foreground">Notes / Review</th>
                                        <th class="p-3 text-right font-medium text-muted-foreground">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="req in requests" :key="req.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50">
                                        <td class="p-3 font-medium">
                                            <div class="font-mono text-indigo-600">{{ req.course?.code }}</div>
                                            <div class="text-xs text-muted-foreground">{{ req.course?.title }}</div>
                                        </td>
                                        <td class="p-3">{{ req.course?.units }} Units</td>
                                        <td class="p-3 text-muted-foreground text-xs">
                                            {{ new Date(req.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="p-3">
                                            <Badge v-if="req.status === 'pending'" variant="outline" class="border-amber-400 text-amber-600 bg-amber-50">
                                                <Clock class="w-3 h-3 mr-1" /> Pending
                                            </Badge>
                                            <Badge v-else-if="req.status === 'approved'" variant="outline" class="border-emerald-400 text-emerald-600 bg-emerald-50">
                                                <CheckCircle2 class="w-3 h-3 mr-1" /> Approved
                                            </Badge>
                                            <Badge v-else-if="req.status === 'rejected'" variant="outline" class="border-rose-400 text-rose-600 bg-rose-50">
                                                <XCircle class="w-3 h-3 mr-1" /> Declined
                                            </Badge>
                                            <Badge v-else variant="outline" class="text-slate-500">
                                                Cancelled
                                            </Badge>
                                        </td>
                                        <td class="p-3 text-xs max-w-xs">
                                            <div v-if="req.status === 'rejected' && req.rejection_reason" class="text-rose-600">
                                                <strong>Reason:</strong> {{ req.rejection_reason }}
                                            </div>
                                            <div v-else-if="req.notes" class="text-muted-foreground truncate" :title="req.notes">
                                                "{{ req.notes }}"
                                            </div>
                                            <span v-else class="text-muted-foreground italic">None</span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <Button
                                                v-if="req.status === 'pending'"
                                                size="sm"
                                                variant="ghost"
                                                @click="cancelRequest(req.id, req.course?.code || '')"
                                                class="text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                            >
                                                <X class="w-3.5 h-3.5 mr-1" /> Cancel
                                            </Button>
                                            <Button
                                                v-else-if="req.status === 'approved' && req.course"
                                                size="sm"
                                                variant="outline"
                                                as-child
                                            >
                                                <Link :href="route('admin.teaching.courses.show', req.course.id)">
                                                    View
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="p-8 text-center text-muted-foreground border rounded-lg border-dashed">
                            <Clock class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                            <p class="text-sm font-medium">You haven't submitted any course requests for this session yet.</p>
                        </div>
                    </TabsContent>
                </Tabs>
        </div>

        <!-- Request Confirmation Modal -->
        <Dialog v-model:open="isPickModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Request Course Allocation</DialogTitle>
                    <DialogDescription>
                        Submit a request to teach this course for the {{ currentSession?.name }} session.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedCourseForPick" class="py-4 space-y-4">
                    <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-lg border">
                        <div class="font-mono text-sm font-bold text-indigo-600">{{ selectedCourseForPick.code }}</div>
                        <div class="text-sm font-medium text-foreground">{{ selectedCourseForPick.title }}</div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ selectedCourseForPick.units }} Units &bull; {{ selectedCourseForPick.level }} Level &bull;
                            {{ selectedCourseForPick.semester === '1' ? 'First' : 'Second' }} Semester
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Optional Note to HOD / Dean</Label>
                        <Textarea
                            id="notes"
                            v-model="pickForm.notes"
                            placeholder="Add any relevant teaching experience, preferences, or notes..."
                            rows="3"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isPickModalOpen = false" :disabled="pickForm.processing">
                        Cancel
                    </Button>
                    <Button @click="submitRequest" :disabled="pickForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Send class="w-4 h-4 mr-2" /> Submit Request
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
