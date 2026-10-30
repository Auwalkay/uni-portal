<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import {
  Search, Plus, Trash2, User, Upload, Download, FileSpreadsheet, X, Inbox,
  BookOpen, Layers, Users, Clock, ArrowRight, GraduationCap, Calendar,
  Building2, CheckCircle2, AlertCircle, RefreshCw
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import Pagination from '@/components/Pagination.vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle
} from '@/components/ui/card';
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

const props = defineProps<{
  allocations: { data: Array<any>; links: Array<any>; from: number; to: number; total: number };
  stats?: {
    total_allocations: number;
    unique_courses: number;
    unique_lecturers: number;
    pending_requests: number;
  };
  sessions: Array<{ id: string; name: string }>;
  faculties: Array<{ id: string; name: string; departments: Array<any> }>;
  courses: Array<{ id: string; code: string; title: string; units?: number; level?: number; semester?: string; department_id: string }>;
  programmes: Array<{ id: string; name: string }>;
  lecturers: Array<{ id: string; name: string; email?: string; staff_number?: string }>;
  filters: { session_id?: string; department_id?: string; search?: string; faculty_id?: string };
  currentSessionId?: string;
}>();

const sessionItems = computed(() => props.sessions.map(s => ({ value: String(s.id), label: s.name })));
const courseItems = computed(() => props.courses.map(c => ({ 
  value: String(c.id), 
  label: `${c.code} - ${c.title}` + (c.units ? ` (${c.units} Units)` : '')
})));
const lecturerItems = computed(() => props.lecturers.map(l => ({ value: String(l.id), label: l.name })));
const departmentItems = computed(() => {
  const list: Array<{ value: string; label: string }> = [];
  props.faculties.forEach(f => {
    f.departments.forEach(d => {
      list.push({ value: String(d.id), label: `${d.name} (${f.name})` });
    });
  });
  return list;
});
const programmeItems = computed(() => props.programmes.map(p => ({ value: String(p.id), label: p.name })));

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || props.currentSessionId || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');
const isAssignOpen = ref(false);
const isImportOpen = ref(false);

const form = useForm({
  course_id: '',
  staff_id: '',
  session_id: props.currentSessionId || '',
  department_id: '',
  program_id: '',
});

const importForm = useForm({
  file: null as File | null,
});

const activeSessionName = computed(() => {
  const s = props.sessions.find(item => String(item.id) === String(selectedSession.value));
  return s?.name || 'All Sessions';
});

const filteredDepartments = computed(() => {
  if (!selectedFaculty.value || selectedFaculty.value === 'ALL') return [];
  const faculty = props.faculties.find(f => String(f.id) === String(selectedFaculty.value));
  return faculty ? faculty.departments : [];
});

const activeFilterCount = computed(() => {
  let count = 0;
  if (search.value.trim()) count++;
  if (selectedFaculty.value && selectedFaculty.value !== 'ALL') count++;
  if (selectedDepartment.value && selectedDepartment.value !== 'ALL') count++;
  if (selectedSession.value && selectedSession.value !== props.currentSessionId) count++;
  return count;
});

const updateFilters = debounce(() => {
  router.get(route('admin.course-allocations.index'), {
    search: search.value,
    session_id: selectedSession.value,
    faculty_id: selectedFaculty.value,
    department_id: selectedDepartment.value,
  }, { preserveState: true, replace: true });
}, 300);

watch([search, selectedSession, selectedFaculty, selectedDepartment], () => {
  if (selectedFaculty.value === 'ALL') selectedDepartment.value = '';
  updateFilters();
});

const resetFilters = () => {
  search.value = '';
  selectedSession.value = props.currentSessionId || '';
  selectedFaculty.value = '';
  selectedDepartment.value = '';
};

const submitAssign = () => {
  if (!form.session_id) form.session_id = selectedSession.value;

  form.post(route('admin.course-allocations.store'), {
    onSuccess: () => {
      isAssignOpen.value = false;
      form.reset('course_id', 'staff_id', 'department_id', 'program_id');
      Swal.fire({
        title: 'Assigned!',
        text: 'Course has been successfully allocated.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false,
      });
    },
  });
};

const submitImport = () => {
  importForm.post(route('admin.course-allocations.import'), {
    onSuccess: (page) => {
      isImportOpen.value = false;
      importForm.reset();
      const flash = (page.props as any).flash;
      if (flash?.warning) {
        Swal.fire({
          title: 'Import Summary',
          html: flash.warning,
          icon: 'warning',
          confirmButtonColor: '#F59E0B',
        });
      } else if (flash?.success) {
        Swal.fire({
          title: 'Import Successful',
          text: flash.success,
          icon: 'success',
          confirmButtonColor: '#10B981',
        });
      } else {
        Swal.fire({
          title: 'Course Allocations Imported',
          text: 'Data imported successfully.',
          icon: 'success',
          confirmButtonColor: '#10B981',
        });
      }
    },
    onError: () => {
      const errors = Object.values(importForm.errors).flat();
      Swal.fire({
        title: 'Import Failed',
        html: errors.length > 0 ? errors.join('<br>') : 'Please check the file and try again.',
        icon: 'error',
        confirmButtonColor: '#EF4444',
      });
    }
  });
};

const deleteAllocation = (alloc: any) => {
  const courseCode = alloc.course?.code ?? 'this course';
  const lecturerName = alloc.staff?.user?.name ?? 'this lecturer';

  Swal.fire({
    title: 'Remove Allocation?',
    html: `Are you sure you want to remove <strong>${courseCode}</strong> from <strong>${lecturerName}</strong>? This will revoke the course assignment.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Yes, Remove Assignment',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('admin.course-allocations.destroy', alloc.id), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            title: 'Removed!',
            text: 'Course assignment has been removed.',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false,
          });
        },
      });
    }
  });
};

const getInitials = (name?: string) => {
  if (!name) return '??';
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(n => n[0].toUpperCase())
    .join('');
};

const getAvatarColor = (name?: string) => {
  const colors = [
    'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200 dark:border-blue-800',
    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
    'bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 border-violet-200 dark:border-violet-800',
    'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200 dark:border-amber-800',
    'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200 dark:border-rose-800',
  ];
  if (!name) return colors[0];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
};
</script>

<template>
  <Head title="Course Allocations" />

  <AdminLayout>
    <div class="py-8 px-4 sm:px-6 lg:px-8 space-y-6 w-full max-w-[1600px] mx-auto">

      <!-- Header Section -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
          <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
              Course Allocations
            </h1>
            <Badge variant="outline" class="font-medium text-xs bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700">
              <Calendar class="w-3 h-3 mr-1 text-primary" />
              {{ activeSessionName }}
            </Badge>
          </div>
          <p class="text-sm text-muted-foreground max-w-2xl">
            Assign courses to lecturers, manage teaching allocations, and review pending lecturer self-selection requests.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          <!-- Allocation Requests Button with Live Badge -->
          <Button 
            variant="outline" 
            as-child 
            class="relative border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 shadow-sm"
          >
            <Link :href="route('admin.course-allocations.requests.index')">
              <Inbox class="w-4 h-4 mr-2 text-indigo-600 dark:text-indigo-400" />
              <span>Allocation Requests</span>
              <span 
                v-if="(stats?.pending_requests ?? 0) > 0"
                class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500 text-white animate-pulse"
              >
                {{ stats?.pending_requests }}
              </span>
            </Link>
          </Button>

          <!-- CSV Import Dialog -->
          <Dialog v-model:open="isImportOpen">
            <DialogTrigger as-child>
              <Button variant="outline" class="shadow-sm">
                <Upload class="w-4 h-4 mr-2 text-slate-600 dark:text-slate-400" /> Import CSV
              </Button>
            </DialogTrigger>

            <DialogContent class="sm:max-w-[460px]">
              <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                  <FileSpreadsheet class="w-5 h-5 text-emerald-600" />
                  Import Course Allocations
                </DialogTitle>
                <DialogDescription>
                  Upload a CSV file to bulk assign courses to academic staff.
                </DialogDescription>
              </DialogHeader>

              <div class="grid gap-5 py-4">
                <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-xl text-sm space-y-2 border border-slate-200 dark:border-slate-800">
                  <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                    <span>Required CSV Headers</span>
                    <span class="text-xs font-normal text-muted-foreground">UTF-8 Encoded</span>
                  </div>
                  <div class="flex gap-2">
                    <code class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-mono">course_code</code>
                    <code class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded text-xs font-mono">staff_number</code>
                  </div>
                  <p class="text-xs text-muted-foreground pt-1">
                    Existing allocations for the same course and lecturer in this session will be safely skipped.
                  </p>

                  <Button variant="secondary" size="sm" class="w-full mt-2 text-xs" as-child>
                    <a :href="route('admin.course-allocations.template')">
                      <Download class="w-3.5 h-3.5 mr-1.5" /> Download Sample Template
                    </a>
                  </Button>
                </div>

                <div class="space-y-2">
                  <Label for="csv-file">Select CSV File</Label>
                  <Input 
                    id="csv-file" 
                    type="file" 
                    accept=".csv,.xlsx,.xls"
                    @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" 
                    class="cursor-pointer file:cursor-pointer"
                  />
                  <p class="text-xs text-destructive" v-if="importForm.errors.file">
                    {{ importForm.errors.file }}
                  </p>
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" @click="isImportOpen = false">Cancel</Button>
                <Button type="button" @click="submitImport" :disabled="importForm.processing || !importForm.file">
                  <Upload class="w-4 h-4 mr-2" /> Upload &amp; Process
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <!-- Assign Modal -->
          <Dialog v-model:open="isAssignOpen">
            <DialogTrigger as-child>
              <Button class="shadow-sm">
                <Plus class="w-4 h-4 mr-2" /> Assign Course
              </Button>
            </DialogTrigger>

            <DialogContent class="sm:max-w-[500px]">
              <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                  <GraduationCap class="w-5 h-5 text-primary" />
                  Assign Course to Lecturer
                </DialogTitle>
                <DialogDescription>
                  Choose a session, course, and lecturer to establish an allocation.
                </DialogDescription>
              </DialogHeader>

              <div class="grid gap-4 py-3">
                <!-- Session -->
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">Academic Session <span class="text-red-500">*</span></Label>
                  <SearchableSelect
                    v-model="form.session_id"
                    :items="sessionItems"
                    placeholder="Select Session"
                    search-placeholder="Search sessions..."
                    :error-class="!!form.errors.session_id"
                  />
                  <p class="text-xs text-destructive" v-if="form.errors.session_id">{{ form.errors.session_id }}</p>
                </div>

                <!-- Course -->
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">Course <span class="text-red-500">*</span></Label>
                  <SearchableSelect
                    v-model="form.course_id"
                    :items="courseItems"
                    placeholder="Search by code or title..."
                    search-placeholder="Type course code or title..."
                    :error-class="!!form.errors.course_id"
                  />
                  <p class="text-xs text-destructive" v-if="form.errors.course_id">{{ form.errors.course_id }}</p>
                </div>

                <!-- Lecturer -->
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">Lecturer <span class="text-red-500">*</span></Label>
                  <SearchableSelect
                    v-model="form.staff_id"
                    :items="lecturerItems"
                    placeholder="Search lecturer name..."
                    search-placeholder="Type lecturer name..."
                    :error-class="!!form.errors.staff_id"
                  />
                  <p class="text-xs text-destructive" v-if="form.errors.staff_id">{{ form.errors.staff_id }}</p>
                </div>

                <!-- Department Override -->
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">Department <span class="text-muted-foreground font-normal">(Optional Override)</span></Label>
                  <SearchableSelect
                    v-model="form.department_id"
                    :items="departmentItems"
                    placeholder="Default (Course Department)"
                    search-placeholder="Filter departments..."
                    :error-class="!!form.errors.department_id"
                  />
                  <p class="text-[0.75rem] text-muted-foreground">
                    Only specify if assigning for a specific servicing department.
                  </p>
                </div>

                <!-- Programme Override -->
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">Programme <span class="text-muted-foreground font-normal">(Optional Override)</span></Label>
                  <SearchableSelect
                    v-model="form.program_id"
                    :items="programmeItems"
                    placeholder="Default (General)"
                    search-placeholder="Filter programmes..."
                    :error-class="!!form.errors.program_id"
                  />
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" @click="isAssignOpen = false">Cancel</Button>
                <Button type="button" @click="submitAssign" :disabled="form.processing || !form.course_id || !form.staff_id">
                  Save Assignment
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <!-- KPI Summary Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Allocations -->
        <Card class="border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden relative">
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Total Allocations
            </CardTitle>
            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-200/50 dark:border-blue-800/50">
              <BookOpen class="w-4 h-4" />
            </div>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100">
              {{ stats?.total_allocations ?? allocations.total }}
            </div>
            <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
              Active assignments in session
            </p>
          </CardContent>
        </Card>

        <!-- Unique Courses -->
        <Card class="border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden relative">
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Courses Allocated
            </CardTitle>
            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-200/50 dark:border-indigo-800/50">
              <Layers class="w-4 h-4" />
            </div>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100">
              {{ stats?.unique_courses ?? 0 }}
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              Distinct curriculum courses covered
            </p>
          </CardContent>
        </Card>

        <!-- Active Lecturers -->
        <Card class="border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden relative">
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Assigned Lecturers
            </CardTitle>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-200/50 dark:border-emerald-800/50">
              <Users class="w-4 h-4" />
            </div>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100">
              {{ stats?.unique_lecturers ?? 0 }}
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              Academic staff taking active courses
            </p>
          </CardContent>
        </Card>

        <!-- Pending Allocation Requests -->
        <Card 
          class="border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden relative transition-all"
          :class="{
            'ring-1 ring-amber-400/50 dark:ring-amber-500/40 bg-amber-50/20 dark:bg-amber-950/10': (stats?.pending_requests ?? 0) > 0
          }"
        >
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
              Allocation Requests
            </CardTitle>
            <div 
              class="w-9 h-9 rounded-xl flex items-center justify-center border"
              :class="(stats?.pending_requests ?? 0) > 0 
                ? 'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 border-amber-300 dark:border-amber-800' 
                : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'"
            >
              <Clock class="w-4 h-4" />
            </div>
          </CardHeader>
          <CardContent>
            <div class="flex items-baseline justify-between">
              <div class="text-2xl font-black text-slate-900 dark:text-slate-100">
                {{ stats?.pending_requests ?? 0 }}
              </div>
              <Link 
                v-if="(stats?.pending_requests ?? 0) > 0"
                :href="route('admin.course-allocations.requests.index')"
                class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-0.5"
              >
                Review <ArrowRight class="w-3 h-3" />
              </Link>
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ (stats?.pending_requests ?? 0) > 0 ? 'Pending Dean / HOD approvals' : 'All requests processed' }}
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Filters Bar -->
      <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div class="flex flex-1 gap-2.5 flex-wrap items-center">
          <!-- Search Input -->
          <div class="relative w-full sm:w-[320px]">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input 
              v-model="search" 
              placeholder="Search course code, title, or lecturer..." 
              class="pl-9 pr-8 h-10 rounded-xl bg-slate-50/50 dark:bg-slate-950" 
            />
            <button 
              v-if="search" 
              @click="search = ''" 
              class="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Session Selector -->
          <Select v-model="selectedSession">
            <SelectTrigger class="w-full sm:w-[190px] h-10 rounded-xl bg-slate-50/50 dark:bg-slate-950">
              <SelectValue placeholder="Session" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">
                {{ s.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Faculty Filter -->
          <Select v-model="selectedFaculty">
            <SelectTrigger class="w-full sm:w-[200px] h-10 rounded-xl bg-slate-50/50 dark:bg-slate-950">
              <SelectValue placeholder="All Faculties" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="ALL">All Faculties</SelectItem>
              <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                {{ f.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <!-- Department Filter -->
          <Select v-model="selectedDepartment" :disabled="!selectedFaculty || selectedFaculty === 'ALL'">
            <SelectTrigger class="w-full sm:w-[220px] h-10 rounded-xl bg-slate-50/50 dark:bg-slate-950">
              <SelectValue placeholder="Department" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="ALL">All Departments</SelectItem>
              <SelectItem v-for="d in filteredDepartments" :key="d.id" :value="String(d.id)">
                {{ d.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Filter Reset / Active Counter -->
        <div class="flex items-center gap-2 pt-2 md:pt-0 justify-end">
          <Badge v-if="activeFilterCount > 0" variant="secondary" class="text-xs px-2.5 py-1">
            {{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filter active' : 'filters active' }}
          </Badge>
          <Button 
            v-if="activeFilterCount > 0" 
            variant="ghost" 
            size="sm"
            @click="resetFilters" 
            class="h-9 px-3 flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground rounded-xl"
          >
            <X class="w-3.5 h-3.5" /> Clear Filters
          </Button>
        </div>
      </div>

      <!-- Main Data Table Card -->
      <Card class="border-slate-200/80 dark:border-slate-800 shadow-sm rounded-2xl overflow-hidden bg-white dark:bg-slate-900">
        <Table>
          <TableHeader class="bg-slate-50/75 dark:bg-slate-950/50 border-b border-slate-200/80 dark:border-slate-800">
            <TableRow>
              <TableHead class="w-[30%] font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3.5 pl-6">Course</TableHead>
              <TableHead class="w-[28%] font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3.5">Lecturer</TableHead>
              <TableHead class="w-[14%] font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3.5">Session</TableHead>
              <TableHead class="w-[20%] font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3.5">Department &amp; Faculty</TableHead>
              <TableHead class="w-[8%] font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3.5 pr-6 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow 
              v-for="alloc in allocations.data" 
              :key="alloc.id"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
            >
              <!-- Course Column -->
              <TableCell class="py-4 pl-6">
                <div class="flex items-start gap-3">
                  <div class="pt-0.5">
                    <span class="inline-flex items-center font-mono text-xs font-bold px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 border border-slate-200 dark:border-slate-700 shadow-2xs">
                      {{ alloc.course?.code }}
                    </span>
                  </div>
                  <div class="space-y-1">
                    <div class="font-semibold text-sm text-foreground leading-snug">
                      {{ alloc.course?.title }}
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <Badge 
                        v-if="alloc.course?.units" 
                        variant="secondary" 
                        class="text-[10px] px-1.5 py-0 font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                      >
                        {{ alloc.course.units }} {{ alloc.course.units === 1 ? 'Unit' : 'Units' }}
                      </Badge>
                      <Badge 
                        v-if="alloc.course?.level" 
                        variant="outline" 
                        class="text-[10px] px-1.5 py-0 font-medium text-muted-foreground"
                      >
                        {{ alloc.course.level }} Level
                      </Badge>
                      <Badge 
                        v-if="alloc.course?.semester" 
                        variant="outline" 
                        class="text-[10px] px-1.5 py-0 font-medium text-muted-foreground"
                      >
                        Sem {{ alloc.course.semester }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </TableCell>

              <!-- Lecturer Column -->
              <TableCell class="py-4">
                <div class="flex items-center gap-3">
                  <div 
                    class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs border shrink-0 shadow-2xs"
                    :class="getAvatarColor(alloc.staff?.user?.name)"
                  >
                    {{ getInitials(alloc.staff?.user?.name) }}
                  </div>
                  <div class="space-y-0.5 min-w-0">
                    <div class="font-semibold text-sm text-foreground truncate">
                      {{ alloc.staff?.user?.name || 'Unassigned Staff' }}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground truncate">
                      <span v-if="alloc.staff?.staff_number" class="font-mono text-[11px] font-medium text-slate-600 dark:text-slate-400">
                        {{ alloc.staff.staff_number }}
                      </span>
                      <span v-if="alloc.staff?.staff_number && alloc.staff?.department?.name">•</span>
                      <span v-if="alloc.staff?.department?.name" class="truncate">
                        {{ alloc.staff.department.name }}
                      </span>
                    </div>
                  </div>
                </div>
              </TableCell>

              <!-- Session Column -->
              <TableCell class="py-4">
                <div class="flex items-center gap-1.5">
                  <Badge 
                    variant="outline" 
                    class="font-medium text-xs px-2.5 py-1 rounded-md"
                    :class="alloc.session_id === currentSessionId ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-slate-800/60'"
                  >
                    <Calendar class="w-3 h-3 mr-1 text-muted-foreground" />
                    {{ alloc.session?.name }}
                  </Badge>
                </div>
              </TableCell>

              <!-- Department & Faculty Column -->
              <TableCell class="py-4">
                <div class="space-y-0.5">
                  <div class="font-medium text-sm text-foreground">
                    {{ alloc.department?.name || alloc.course?.department?.name || 'General / Inter-departmental' }}
                  </div>
                  <div class="text-xs text-muted-foreground flex items-center gap-1.5 flex-wrap">
                    <span v-if="alloc.department?.faculty?.name || alloc.course?.department?.faculty?.name">
                      {{ alloc.department?.faculty?.name || alloc.course?.department?.faculty?.name }}
                    </span>
                    <Badge 
                      v-if="alloc.programme?.name" 
                      variant="outline" 
                      class="text-[10px] px-1.5 py-0 text-primary border-primary/20"
                    >
                      {{ alloc.programme.name }}
                    </Badge>
                  </div>
                </div>
              </TableCell>

              <!-- Actions Column -->
              <TableCell class="py-4 pr-6 text-right">
                <Button 
                  variant="ghost" 
                  size="icon" 
                  class="h-8 w-8 text-slate-400 hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
                  title="Remove Allocation"
                  @click="deleteAllocation(alloc)"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </TableCell>
            </TableRow>

            <!-- Empty State -->
            <TableRow v-if="allocations.data.length === 0">
              <TableCell colspan="5" class="text-center py-16">
                <div class="flex flex-col items-center justify-center space-y-3 max-w-sm mx-auto">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center">
                    <BookOpen class="w-6 h-6" />
                  </div>
                  <div class="space-y-1">
                    <h3 class="text-base font-semibold text-foreground">No course allocations found</h3>
                    <p class="text-xs text-muted-foreground">
                      {{ activeFilterCount > 0 ? 'Try clearing some filters to see matching allocations.' : 'No courses have been allocated for this academic session yet.' }}
                    </p>
                  </div>
                  <div class="flex gap-2 pt-2">
                    <Button v-if="activeFilterCount > 0" variant="outline" size="sm" @click="resetFilters">
                      Clear Filters
                    </Button>
                    <Button size="sm" @click="isAssignOpen = true">
                      <Plus class="w-3.5 h-3.5 mr-1.5" /> Assign First Course
                    </Button>
                  </div>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Pagination & Footer -->
        <CardFooter 
          class="border-t border-slate-200/80 dark:border-slate-800 p-4 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50 dark:bg-slate-950/30" 
          v-if="allocations.total > 0"
        >
          <div class="text-xs text-muted-foreground">
            Showing <span class="font-medium text-foreground">{{ allocations.from }}</span> to 
            <span class="font-medium text-foreground">{{ allocations.to }}</span> of 
            <span class="font-medium text-foreground">{{ allocations.total }}</span> total allocations
          </div>
          <Pagination :links="allocations.links" />
        </CardFooter>
      </Card>

    </div>
  </AdminLayout>
</template>
