<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { 
    Table, 
    TableHeader, 
    TableBody, 
    TableHead, 
    TableRow, 
    TableCell 
} from '@/components/ui/table';
import { 
    Search, 
    BookOpen, 
    Calendar, 
    ArrowRight, 
    BookPlus, 
    Users, 
    Building2, 
    Layers, 
    GraduationCap, 
    FilterX,
    LayoutGrid,
    LayoutList
} from 'lucide-vue-next';
import debounce from 'lodash/debounce';
import { route } from 'ziggy-js';

const props = defineProps<{
    courses: {
        data: Array<{
            id: string;
            code: string;
            title: string;
            units: number;
            department?: { id: string; name: string; code: string };
            allocations?: Array<{ id: string; staff?: { user?: { name: string } } }>;
            registrations_count?: number;
        }>;
        links: any[];
        total?: number;
        current_page?: number;
        per_page?: number;
        from?: number;
        to?: number;
    };
    sessions: Array<{ id: string; name: string }>;
    filters: { 
        session_id?: string; 
        search?: string; 
        scope?: string; 
        department_id?: string;
    };
    currentSessionId?: string;
    userRole: {
        isAdmin: boolean;
        isDean: boolean;
        isHod: boolean;
        canViewOtherCourses?: boolean;
        hasAllocations: boolean;
        scope: string;
        scopeTitle: string;
    };
    departments?: Array<{ id: string; name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Courses & Rosters', href: '/admin/staff/courses' },
];

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || props.currentSessionId || 'all');
const selectedDepartment = ref(props.filters.department_id || 'all');
const selectedScope = ref(props.filters.scope || props.userRole.scope || 'assigned');
const viewMode = ref<'table' | 'grid'>('table');

function applyFilters() {
    router.get(route('admin.teaching.courses.index'), {
        search: search.value || undefined,
        session_id: selectedSession.value,
        department_id: selectedDepartment.value !== 'all' ? selectedDepartment.value : undefined,
        scope: selectedScope.value,
    }, { 
        preserveState: true, 
        replace: true 
    });
}

watch(search, debounce(() => {
    applyFilters();
}, 300));

watch([selectedSession, selectedDepartment, selectedScope], () => {
    applyFilters();
});

function clearFilters() {
    search.value = '';
    selectedSession.value = props.currentSessionId || 'all';
    selectedDepartment.value = 'all';
    selectedScope.value = props.userRole.hasAllocations || !props.userRole.canViewOtherCourses ? 'assigned' : 'managed';
    applyFilters();
}
</script>

<template>
    <Head title="Course Rosters & Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                            Course Rosters & Attendance
                        </h1>
                        <Badge 
                            variant="secondary" 
                            class="px-2.5 py-0.5 text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                        >
                            {{ userRole.scopeTitle }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Access course details, real-time student registration lists, and lecture attendance records.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Button as-child class="bg-indigo-600 hover:bg-indigo-700 text-white min-h-[40px] shadow-sm">
                        <Link :href="route('admin.teaching.courses.self_allocation')">
                            <BookPlus class="w-4 h-4 mr-2" /> Self Course Allocation
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between flex-wrap">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search by course code or title..."
                        class="pl-9 w-full bg-slate-50/50 dark:bg-slate-950/50"
                    />
                </div>

                <!-- Scope Switch (defaults to My Allocated Courses; shows All/Faculty/Dept Courses if permitted) -->
                <div
                    v-if="userRole.canViewOtherCourses"
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-950"
                >
                    <button
                        type="button"
                        @click="selectedScope = 'assigned'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                            selectedScope === 'assigned'
                                ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-800 dark:text-indigo-300'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                        ]"
                    >
                        My Allocated Courses
                    </button>
                    <button
                        type="button"
                        @click="selectedScope = 'managed'"
                        :class="[
                            'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                            selectedScope === 'managed' || selectedScope === 'all'
                                ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-800 dark:text-indigo-300'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200',
                        ]"
                    >
                        {{ userRole.isAdmin ? 'All University Courses' : (userRole.isHod ? 'Dept Courses' : 'Faculty Courses') }}
                    </button>
                </div>

                <!-- Department Filter (for Admin and Dean) -->
                <div v-if="departments && departments.length > 0" class="w-full sm:w-auto">
                    <Select v-model="selectedDepartment">
                        <SelectTrigger class="w-full sm:w-[210px] bg-slate-50/50 dark:bg-slate-950/50">
                            <SelectValue placeholder="All Departments" />
                        </SelectTrigger>
                        <SelectContent class="max-h-[300px]">
                            <SelectItem value="all">All Departments</SelectItem>
                            <SelectItem v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
                                {{ dept.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Session Select -->
                <div class="w-full sm:w-auto">
                    <Select v-model="selectedSession">
                        <SelectTrigger class="w-full sm:w-[190px] bg-slate-50/50 dark:bg-slate-950/50">
                            <SelectValue placeholder="All Sessions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Sessions</SelectItem>
                            <SelectItem v-for="session in sessions" :key="session.id" :value="String(session.id)">
                                {{ session.name }} Session
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Clear Filters button if filters are active -->
                <Button 
                    v-if="search || selectedDepartment !== 'all' || (selectedSession !== currentSessionId && selectedSession !== 'all')" 
                    variant="ghost" 
                    size="sm" 
                    class="text-xs text-muted-foreground hover:text-foreground"
                    @click="clearFilters"
                >
                    <FilterX class="w-3.5 h-3.5 mr-1" /> Reset
                </Button>
            </div>

            <!-- Summary KPI Bar -->
            <div class="grid gap-3 grid-cols-2 md:grid-cols-3">
                <Card class="bg-indigo-50/70 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900/60 shadow-none">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 p-3.5 pb-1">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Total Courses</CardTitle>
                        <BookOpen class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                    </CardHeader>
                    <CardContent class="p-3.5 pt-1">
                        <div class="text-2xl font-bold text-indigo-950 dark:text-indigo-100">
                            {{ courses.total ?? courses.data.length }}
                        </div>
                        <p class="text-[11px] text-indigo-600/80 dark:text-indigo-400/80 mt-0.5 truncate">
                            In current view
                        </p>
                    </CardContent>
                </Card>

                <Card class="bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/60 shadow-none">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 p-3.5 pb-1">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Student Rosters</CardTitle>
                        <Users class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    </CardHeader>
                    <CardContent class="p-3.5 pt-1">
                        <div class="text-2xl font-bold text-emerald-950 dark:text-emerald-100">
                            {{ courses.data.reduce((acc, c) => acc + (c.registrations_count || 0), 0) }}
                        </div>
                        <p class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">
                            Registered on this page
                        </p>
                    </CardContent>
                </Card>

                <Card class="col-span-2 md:col-span-1 bg-slate-50/80 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 shadow-none">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 p-3.5 pb-1">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Selected Session</CardTitle>
                        <Calendar class="h-4 w-4 text-slate-500" />
                    </CardHeader>
                    <CardContent class="p-3.5 pt-1">
                        <div class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                            {{ sessions.find(s => String(s.id) === String(selectedSession))?.name ?? 'All Sessions' }}
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-0.5">
                            Academic period
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- View Toggle & Summary Count Bar -->
            <div class="flex items-center justify-between gap-3 text-xs text-muted-foreground flex-wrap">
                <span class="font-medium">
                    Showing <strong class="text-foreground">{{ courses.from ?? 1 }}</strong> to <strong class="text-foreground">{{ courses.to ?? courses.data.length }}</strong> of <strong class="text-foreground">{{ courses.total ?? courses.data.length }}</strong> courses
                </span>

                <!-- View Switcher -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all cursor-pointer',
                            viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-100'
                        ]"
                        @click="viewMode = 'table'"
                        title="Tabular View"
                    >
                        <LayoutList class="w-3.5 h-3.5" />
                        <span>Table</span>
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-2.5 py-1 text-xs font-semibold rounded-md flex items-center gap-1.5 transition-all cursor-pointer',
                            viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-100'
                        ]"
                        @click="viewMode = 'grid'"
                        title="Grid Card View"
                    >
                        <LayoutGrid class="w-3.5 h-3.5" />
                        <span>Grid</span>
                    </button>
                </div>
            </div>

            <!-- Tabular View (Default) -->
            <div v-if="courses.data.length > 0 && viewMode === 'table'" class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-900 shadow-xs">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="w-12 text-center text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">#</TableHead>
                                <TableHead class="w-28 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Course Code</TableHead>
                                <TableHead class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase min-w-[240px]">Course Title</TableHead>
                                <TableHead class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase min-w-[180px]">Department</TableHead>
                                <TableHead class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase min-w-[180px]">Assigned Lecturer</TableHead>
                                <TableHead class="w-20 text-center text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Units</TableHead>
                                <TableHead class="w-32 text-center text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Enrolled Roster</TableHead>
                                <TableHead class="w-36 text-right pr-4 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <TableRow 
                                v-for="(course, index) in courses.data" 
                                :key="course.id"
                                class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition-colors group cursor-pointer"
                                @click="router.visit(route('admin.teaching.courses.show', course.id))"
                            >
                                <!-- S/N -->
                                <TableCell class="text-center font-mono text-xs text-slate-400 font-semibold">
                                    {{ ((courses.current_page || 1) - 1) * (courses.per_page || 20) + index + 1 }}
                                </TableCell>

                                <!-- Course Code -->
                                <TableCell class="font-mono">
                                    <Badge variant="secondary" class="font-bold text-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60">
                                        {{ course.code }}
                                    </Badge>
                                </TableCell>

                                <!-- Course Title -->
                                <TableCell>
                                    <div class="font-semibold text-slate-900 dark:text-slate-100 text-sm group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ course.title }}
                                    </div>
                                </TableCell>

                                <!-- Department -->
                                <TableCell>
                                    <div v-if="course.department" class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                        <Building2 class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                        <span class="truncate">{{ course.department.name }}</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic">General / Unassigned</span>
                                </TableCell>

                                <!-- Assigned Lecturer -->
                                <TableCell>
                                    <div v-if="course.allocations && course.allocations.length > 0" class="flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-200 font-medium">
                                        <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] flex items-center justify-center shrink-0">
                                            {{ (course.allocations[0]?.staff?.user?.name || 'S').substring(0, 1).toUpperCase() }}
                                        </div>
                                        <span class="truncate">{{ course.allocations[0]?.staff?.user?.name || 'Staff' }}</span>
                                    </div>
                                    <Badge v-else variant="outline" class="text-[10px] text-slate-400 border-dashed font-normal">
                                        Unassigned
                                    </Badge>
                                </TableCell>

                                <!-- Units -->
                                <TableCell class="text-center font-bold text-xs text-slate-700 dark:text-slate-300">
                                    {{ course.units }} {{ course.units === 1 ? 'Unit' : 'Units' }}
                                </TableCell>

                                <!-- Enrolled Students Count -->
                                <TableCell class="text-center">
                                    <Badge 
                                        :class="[
                                            (course.registrations_count || 0) > 0
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                                : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                                            'font-semibold text-xs px-2.5 py-0.5 gap-1 shadow-2xs'
                                        ]"
                                    >
                                        <Users class="w-3 h-3 shrink-0" />
                                        <span>{{ course.registrations_count ?? 0 }}</span>
                                    </Badge>
                                </TableCell>

                                <!-- Action -->
                                <TableCell class="text-right pr-4" @click.stop>
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 px-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-2xs"
                                    >
                                        <Link :href="route('admin.teaching.courses.show', course.id)">
                                            <span>Manage Roster</span>
                                            <ArrowRight class="w-3.5 h-3.5 ml-1" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Alternative Grid View -->
            <div v-else-if="courses.data.length > 0 && viewMode === 'grid'" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link 
                    v-for="course in courses.data" 
                    :key="course.id"
                    :href="route('admin.teaching.courses.show', course.id)"
                    class="group block h-full focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl"
                >
                    <Card class="h-full bg-white dark:bg-slate-900 hover:shadow-md transition-all duration-200 border border-slate-200 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 flex flex-col justify-between overflow-hidden">
                        <CardHeader class="p-4 pb-3">
                            <div class="flex items-center justify-between gap-2">
                                <Badge variant="secondary" class="font-mono font-bold text-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    {{ course.code }}
                                </Badge>
                                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                    {{ course.units }} {{ course.units === 1 ? 'Unit' : 'Units' }}
                                </span>
                            </div>

                            <CardTitle class="text-base font-semibold leading-snug mt-2.5 text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                {{ course.title }}
                            </CardTitle>

                            <!-- Department & Lecturer Details -->
                            <div class="mt-2 space-y-1 text-xs text-muted-foreground">
                                <div v-if="course.department" class="flex items-center gap-1.5 truncate">
                                    <Building2 class="w-3.5 h-3.5 shrink-0 text-slate-400" />
                                    <span class="truncate">{{ course.department.name }}</span>
                                </div>
                                <div v-if="course.allocations && course.allocations.length > 0" class="flex items-center gap-1.5 truncate">
                                    <GraduationCap class="w-3.5 h-3.5 shrink-0 text-slate-400" />
                                    <span class="truncate">Lecturer: {{ course.allocations[0]?.staff?.user?.name || 'Staff' }}</span>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="p-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/40 dark:bg-slate-950/30 flex items-center justify-between">
                            <!-- Registered Students Badge -->
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80">
                                <Users class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span>{{ course.registrations_count ?? 0 }} Registered</span>
                            </div>

                            <!-- Action hint -->
                            <div class="flex items-center text-xs font-semibold text-indigo-600 dark:text-indigo-400 group-hover:translate-x-0.5 transition-transform">
                                Manage Roster <ArrowRight class="ml-1 h-3.5 w-3.5" />
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-16 px-4 text-center bg-white dark:bg-slate-900 border border-dashed rounded-xl border-slate-200 dark:border-slate-800">
                <div class="bg-indigo-50 dark:bg-indigo-950/50 p-4 rounded-full mb-3 text-indigo-600 dark:text-indigo-400">
                    <BookOpen class="w-8 h-8" />
                </div>
                <h3 class="font-semibold text-lg text-slate-900 dark:text-slate-100">No courses found</h3>
                <p class="text-sm text-muted-foreground max-w-md mt-1">
                    No courses matched your current scope or filters. Try adjusting your search term, department, or session filter.
                </p>
                <div class="mt-4">
                    <Button variant="outline" size="sm" @click="clearFilters">
                        Clear All Filters
                    </Button>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="courses.links && courses.links.length > 3" class="flex flex-wrap items-center justify-center gap-1.5 mt-2">
                <template v-for="(link, i) in courses.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 text-xs rounded-md border transition-colors min-h-[36px] flex items-center justify-center font-medium"
                        :class="[
                            link.active 
                                ? 'bg-indigo-600 text-white border-indigo-600 font-semibold shadow-sm' 
                                : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span 
                        v-else 
                        class="px-3 py-1.5 text-xs text-slate-400 border border-transparent select-none min-h-[36px] flex items-center justify-center"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
