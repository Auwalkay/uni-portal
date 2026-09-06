<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import SearchableSelect from '@/components/SearchableSelect.vue';
import Pagination from '@/components/Pagination.vue';
import { 
    CalendarRange, Plus, Trash2, Filter, X, Search, Upload, Download,
    LayoutGrid, List, Clock, MapPin, BookOpen, Building2, Sparkles,
    CalendarDays, GraduationCap, RefreshCw, CheckCircle2, Eye, EyeOff
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import Swal from 'sweetalert2';

const props = defineProps<{
    timetables: any;
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    filters: any;
    currentSession: any;
}>();

const viewMode = ref<'grid' | 'table'>('grid');
const selectedDayTab = ref<string>('all');
const searchQuery = ref<string>('');

const sessionItems = computed(() => props.sessions.map(s => ({ value: String(s.id), label: s.name })));
const semesterItems = computed(() => props.semesters.map(s => ({ value: String(s.id), label: s.name })));
const departmentItems = computed(() => props.departments.map(d => ({ value: String(d.id), label: d.name })));
const courseItems = computed(() => props.courses.map(c => ({ value: String(c.id), label: `${c.code} - ${c.title}` })));

const levelItems = [
    { value: '100', label: '100 Level' },
    { value: '200', label: '200 Level' },
    { value: '300', label: '300 Level' },
    { value: '400', label: '400 Level' },
    { value: '500', label: '500 Level' },
];

const dayItems = [
    { value: 'Monday', label: 'Monday' },
    { value: 'Tuesday', label: 'Tuesday' },
    { value: 'Wednesday', label: 'Wednesday' },
    { value: 'Thursday', label: 'Thursday' },
    { value: 'Friday', label: 'Friday' },
    { value: 'Saturday', label: 'Saturday' },
];

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const form = useForm({
    session_id: props.currentSession?.id || '',
    semester_id: '',
    department_id: '',
    level: '',
    course_id: '',
    day: 'Monday',
    start_time: '',
    end_time: '',
    venue: '',
});

const filterForm = ref({
    session_id: props.filters.session_id || props.currentSession?.id || '',
    semester_id: props.filters.semester_id || '',
    department_id: props.filters.department_id || '',
    level: props.filters.level || '',
});

const isCreateOpen = ref(false);
const isImportOpen = ref(false);

const importForm = useForm({
    file: null as File | null,
});

const applyFilters = () => {
    router.get(route('admin.timetables.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        session_id: props.currentSession?.id || '',
        semester_id: '',
        department_id: '',
        level: '',
    };
    applyFilters();
};

const submit = () => {
    form.post(route('admin.timetables.store'), {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset('course_id', 'day', 'start_time', 'end_time', 'venue');
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Timetable Entry Saved',
            });
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'error',
                title: 'Validation Failed',
                text: 'Please check the form for errors.'
            });
        }
    });
};

const deleteEntry = (id: string) => {
    Swal.fire({
        title: 'Delete Class Entry?',
        text: "This will remove this class from the schedule permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.timetables.destroy', id), {
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500,
                        icon: 'success',
                        title: 'Entry Deleted',
                    });
                }
            });
        }
    });
};

const submitImport = () => {
    importForm.post(route('admin.timetables.import'), {
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
            } else {
                Swal.fire({
                    title: 'Timetable Imported',
                    text: flash?.success || 'Data imported successfully.',
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

const downloadTemplate = () => {
    window.location.href = route('admin.timetables.template');
};

const togglePublish = () => {
    router.post(route('admin.timetables.toggle_publish'), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = (page.props as any).flash;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: flash?.success || 'Publication status updated',
            });
        }
    });
};

const format12Hour = (time: string | null) => {
    if (!time) return '---';
    const parts = time.split(':');
    let h = parseInt(parts[0], 10);
    if (isNaN(h)) return time;
    const m = parts[1] || '00';
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h.toString().padStart(2, '0')}:${m} ${ampm}`;
};

// Filtered Entries for Grid/Table View
const allEntries = computed(() => props.timetables?.data || []);

const filteredEntries = computed(() => {
    let list = allEntries.value;

    if (selectedDayTab.value !== 'all') {
        list = list.filter((item: any) => item.day === selectedDayTab.value);
    }

    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase();
        list = list.filter((item: any) => 
            (item.course?.code || '').toLowerCase().includes(q) ||
            (item.course?.title || '').toLowerCase().includes(q) ||
            (item.venue || '').toLowerCase().includes(q) ||
            (item.department?.name || '').toLowerCase().includes(q) ||
            String(item.level || '').includes(q)
        );
    }

    return list;
});

// Grouped Entries by Day for Weekly Grid View
const entriesByDay = computed(() => {
    const grouped: Record<string, any[]> = {
        Monday: [],
        Tuesday: [],
        Wednesday: [],
        Thursday: [],
        Friday: [],
        Saturday: []
    };

    filteredEntries.value.forEach((entry: any) => {
        if (grouped[entry.day]) {
            grouped[entry.day].push(entry);
        }
    });

    // Sort each day by start_time
    Object.keys(grouped).forEach(day => {
        grouped[day].sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));
    });

    return grouped;
});

const getDayTheme = (day: string) => {
    switch (day) {
        case 'Monday': return { bg: 'bg-indigo-50/70 dark:bg-indigo-950/30', border: 'border-indigo-200 dark:border-indigo-900', text: 'text-indigo-700 dark:text-indigo-300', badge: 'bg-indigo-600' };
        case 'Tuesday': return { bg: 'bg-emerald-50/70 dark:bg-emerald-950/30', border: 'border-emerald-200 dark:border-emerald-900', text: 'text-emerald-700 dark:text-emerald-300', badge: 'bg-emerald-600' };
        case 'Wednesday': return { bg: 'bg-amber-50/70 dark:bg-amber-950/30', border: 'border-amber-200 dark:border-amber-900', text: 'text-amber-700 dark:text-amber-300', badge: 'bg-amber-600' };
        case 'Thursday': return { bg: 'bg-purple-50/70 dark:bg-purple-950/30', border: 'border-purple-200 dark:border-purple-900', text: 'text-purple-700 dark:text-purple-300', badge: 'bg-purple-600' };
        case 'Friday': return { bg: 'bg-rose-50/70 dark:bg-rose-950/30', border: 'border-rose-200 dark:border-rose-900', text: 'text-rose-700 dark:text-rose-300', badge: 'bg-rose-600' };
        case 'Saturday': return { bg: 'bg-cyan-50/70 dark:bg-cyan-950/30', border: 'border-cyan-200 dark:border-cyan-900', text: 'text-cyan-700 dark:text-cyan-300', badge: 'bg-cyan-600' };
        default: return { bg: 'bg-slate-50 dark:bg-slate-900', border: 'border-slate-200 dark:border-slate-800', text: 'text-slate-700 dark:text-slate-300', badge: 'bg-slate-600' };
    }
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Timetable Schedule', href: '/admin/timetables' },
];
</script>

<template>
    <Head title="Academic Timetable & Class Schedule" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-6 space-y-8 w-full max-w-[1700px] mx-auto">

            <!-- Hero Banner Header -->
            <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl overflow-hidden shadow-xl text-white border border-slate-800">
                <div class="absolute inset-0 bg-grid-white/[0.05] bg-[length:24px_24px]"></div>
                
                <div class="relative p-8 md:p-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" class="bg-indigo-500/20 text-indigo-200 border-indigo-500/30 px-3 py-1 font-bold">
                                <Sparkles class="w-3.5 h-3.5 mr-1 text-amber-400" /> Academic Timetable Engine
                            </Badge>
                            <Badge variant="outline" class="border-emerald-500/40 text-emerald-300 bg-emerald-500/10 px-3 py-1 font-semibold">
                                {{ currentSession?.name || 'Active Session' }}
                            </Badge>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white flex items-center gap-3">
                            Class Schedule Matrix
                        </h1>
                        <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                            Organize and oversee lecture schedules, venues, and time slot allocations across departments and academic levels.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button variant="outline" @click="isImportOpen = true" class="bg-white/10 hover:bg-white/20 text-white border-white/20 font-bold backdrop-blur-sm shadow-sm">
                            <Upload class="w-4 h-4 mr-2" /> Bulk Import
                        </Button>
                        <Button @click="isCreateOpen = true" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30 transition-all">
                            <Plus class="w-4 h-4 mr-2" /> Add Lecture Entry
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card class="border shadow-sm bg-card hover:shadow-md transition-all">
                    <CardContent class="p-5 flex items-center gap-4">
                        <div class="p-3 rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                            <CalendarDays class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Total Scheduled</div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ timetables?.total || allEntries.length }}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border shadow-sm bg-card hover:shadow-md transition-all">
                    <CardContent class="p-5 flex items-center gap-4">
                        <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300">
                            <Building2 class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Departments</div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ departments.length }}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border shadow-sm bg-card hover:shadow-md transition-all">
                    <CardContent class="p-5 flex items-center gap-4">
                        <div class="p-3 rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-300">
                            <BookOpen class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Available Courses</div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ courses.length }}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border shadow-sm bg-card hover:shadow-md transition-all">
                    <CardContent class="p-5 flex items-center gap-4">
                        <div class="p-3 rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-300">
                            <GraduationCap class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Active Level</div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ filterForm.level ? `${filterForm.level} L` : 'All Levels' }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Smart Filter & Search Control Panel -->
            <Card class="border shadow-sm">
                <CardHeader class="pb-4 border-b bg-muted/20">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div>
                            <CardTitle class="text-lg font-bold flex items-center gap-2">
                                <Filter class="w-5 h-5 text-indigo-600" /> Filter & Search Schedule
                            </CardTitle>
                            <CardDescription>Refine lecture entries by session, semester, department, or academic level.</CardDescription>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="flex items-center gap-2">
                            <div class="bg-muted p-1 rounded-xl flex items-center gap-1 border">
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="viewMode = 'grid'"
                                    :class="viewMode === 'grid' ? 'bg-background shadow-sm font-bold text-indigo-600' : 'text-muted-foreground'"
                                    class="h-8 rounded-lg px-3 transition-all"
                                >
                                    <LayoutGrid class="w-4 h-4 mr-1.5" /> Weekly Grid
                                </Button>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="viewMode = 'table'"
                                    :class="viewMode === 'table' ? 'bg-background shadow-sm font-bold text-indigo-600' : 'text-muted-foreground'"
                                    class="h-8 rounded-lg px-3 transition-all"
                                >
                                    <List class="w-4 h-4 mr-1.5" /> List View
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-6 space-y-6">
                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase text-muted-foreground">Session</Label>
                            <Select v-model="filterForm.session_id">
                                 <SelectTrigger class="h-10 font-semibold"><SelectValue placeholder="Select Session" /></SelectTrigger>
                                 <SelectContent>
                                     <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                 </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase text-muted-foreground">Semester</Label>
                            <Select v-model="filterForm.semester_id">
                                 <SelectTrigger class="h-10 font-semibold"><SelectValue placeholder="All Semesters" /></SelectTrigger>
                                 <SelectContent>
                                     <SelectItem value="all">All Semesters</SelectItem>
                                     <SelectItem v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                 </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase text-muted-foreground">Department</Label>
                            <Select v-model="filterForm.department_id">
                                 <SelectTrigger class="h-10 font-semibold"><SelectValue placeholder="All Departments" /></SelectTrigger>
                                 <SelectContent>
                                     <SelectItem value="all">All Departments</SelectItem>
                                     <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                                 </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-bold uppercase text-muted-foreground">Level</Label>
                            <Select v-model="filterForm.level">
                                 <SelectTrigger class="h-10 font-semibold"><SelectValue placeholder="All Levels" /></SelectTrigger>
                                 <SelectContent>
                                     <SelectItem value="all">All Levels</SelectItem>
                                     <SelectItem value="100">100 Level</SelectItem>
                                     <SelectItem value="200">200 Level</SelectItem>
                                     <SelectItem value="300">300 Level</SelectItem>
                                     <SelectItem value="400">400 Level</SelectItem>
                                     <SelectItem value="500">500 Level</SelectItem>
                                 </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Action Bar & Quick Search -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 border-t">
                        <!-- Quick Search Input -->
                        <div class="relative w-full sm:w-80">
                            <Search class="w-4 h-4 absolute left-3 top-3 text-muted-foreground" />
                            <Input 
                                v-model="searchQuery" 
                                placeholder="Search course code, venue, title..." 
                                class="pl-9 h-10 font-medium" 
                            />
                        </div>

                        <!-- Filter Action Buttons -->
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <Button variant="ghost" size="sm" @click="resetFilters" class="font-semibold text-muted-foreground">
                                Reset
                            </Button>
                            <Button variant="default" @click="applyFilters" class="bg-indigo-600 hover:bg-indigo-700 font-bold px-6">
                                <Filter class="w-4 h-4 mr-2" /> Apply Filter
                            </Button>
                        </div>
                    </div>

                    <!-- Day Quick Filters -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
                        <span class="text-xs font-bold uppercase text-muted-foreground mr-2 shrink-0">Day Filter:</span>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            @click="selectedDayTab = 'all'"
                            :class="selectedDayTab === 'all' ? 'bg-indigo-600 text-white font-bold border-indigo-600' : 'bg-background font-medium'"
                            class="rounded-full text-xs px-4 h-8 transition-all shrink-0"
                        >
                            All Days
                        </Button>
                        <Button 
                            v-for="day in daysOfWeek" 
                            :key="day" 
                            variant="outline" 
                            size="sm" 
                            @click="selectedDayTab = day"
                            :class="selectedDayTab === day ? 'bg-indigo-600 text-white font-bold border-indigo-600' : 'bg-background font-medium'"
                            class="rounded-full text-xs px-4 h-8 transition-all shrink-0"
                        >
                            {{ day }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- VIEW MODE 1: WEEKLY GRID MATRIX -->
            <div v-if="viewMode === 'grid'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5">
                    <div 
                        v-for="day in daysOfWeek" 
                        :key="day"
                        v-show="selectedDayTab === 'all' || selectedDayTab === day"
                        class="rounded-2xl border flex flex-col transition-all overflow-hidden shadow-sm hover:shadow-md bg-card"
                        :class="getDayTheme(day).border"
                    >
                        <!-- Day Column Header -->
                        <div class="p-4 border-b flex items-center justify-between font-bold" :class="getDayTheme(day).bg">
                            <span class="text-sm tracking-wide uppercase font-black" :class="getDayTheme(day).text">{{ day }}</span>
                            <Badge class="font-bold text-[11px] px-2.5 py-0.5 text-white" :class="getDayTheme(day).badge">
                                {{ entriesByDay[day]?.length || 0 }} {{ entriesByDay[day]?.length === 1 ? 'Class' : 'Classes' }}
                            </Badge>
                        </div>

                        <!-- Class Entries List in Day Column -->
                        <div class="p-3 space-y-3 flex-1 min-h-[300px]">
                            <!-- Empty State for Day -->
                            <div v-if="!entriesByDay[day] || entriesByDay[day].length === 0" class="h-full flex flex-col items-center justify-center p-6 text-center text-muted-foreground opacity-60">
                                <Clock class="w-8 h-8 mb-2 opacity-30" />
                                <span class="text-xs font-semibold">No classes scheduled</span>
                            </div>

                            <!-- Class Entry Card -->
                            <div 
                                v-for="entry in entriesByDay[day]" 
                                :key="entry.id"
                                class="bg-background rounded-xl p-4 border shadow-sm hover:shadow-md transition-all border-l-4 group relative space-y-2.5"
                                :class="getDayTheme(day).border"
                            >
                                <!-- Time Badge & Actions -->
                                <div class="flex items-center justify-between gap-2">
                                    <Badge variant="outline" class="font-mono text-[11px] font-bold bg-muted/40">
                                        <Clock class="w-3 h-3 mr-1 text-indigo-500" />
                                        {{ format12Hour(entry.start_time) }} - {{ format12Hour(entry.end_time) }}
                                    </Badge>
                                    <Button 
                                        variant="ghost" 
                                        size="icon" 
                                        class="h-7 w-7 text-muted-foreground hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-lg transition-colors opacity-70 group-hover:opacity-100"
                                        @click="deleteEntry(entry.id)"
                                        title="Delete Class Entry"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>

                                <!-- Course Code & Title -->
                                <div>
                                    <div class="font-black text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                        {{ entry.course?.code || 'N/A' }}
                                        <Badge variant="secondary" class="text-[10px] py-0 px-1.5 font-bold bg-slate-100 dark:bg-slate-800">
                                            {{ entry.level }}L
                                        </Badge>
                                    </div>
                                    <div class="text-xs font-medium text-muted-foreground line-clamp-2 mt-0.5">
                                        {{ entry.course?.title || 'Course Details' }}
                                    </div>
                                </div>

                                <!-- Venue & Department Footer -->
                                <div class="pt-2 border-t flex flex-col gap-1 text-[11px] text-muted-foreground font-medium">
                                    <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-semibold">
                                        <MapPin class="w-3 h-3 text-indigo-500 shrink-0" />
                                        <span class="truncate">{{ entry.venue || 'TBA' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500">
                                        <Building2 class="w-3 h-3 text-slate-400 shrink-0" />
                                        <span class="truncate">{{ entry.department?.name || 'Department' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW MODE 2: ENHANCED TABLE LIST VIEW -->
            <div v-else class="bg-card rounded-2xl border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/30 text-xs font-bold">
                            <TableHead class="font-bold">Day</TableHead>
                            <TableHead class="font-bold">Time Slot</TableHead>
                            <TableHead class="font-bold">Course Info</TableHead>
                            <TableHead class="font-bold">Venue</TableHead>
                            <TableHead class="font-bold">Department / Level</TableHead>
                            <TableHead class="text-right font-bold pr-6">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="entry in filteredEntries" :key="entry.id" class="hover:bg-muted/30 transition-colors">
                            <TableCell class="font-bold">
                                <Badge variant="outline" class="font-bold uppercase text-[11px] px-2.5 py-1">
                                    {{ entry.day }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary" class="font-mono text-xs font-bold py-1 px-2.5">
                                    <Clock class="w-3.5 h-3.5 mr-1 text-indigo-600" />
                                    {{ format12Hour(entry.start_time) }} - {{ format12Hour(entry.end_time) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="font-black text-sm text-slate-900 dark:text-white font-mono">{{ entry.course?.code }}</div>
                                <div class="text-xs text-muted-foreground font-medium">{{ entry.course?.title }}</div>
                            </TableCell>
                            <TableCell class="font-semibold text-xs text-slate-800 dark:text-slate-200">
                                <div class="flex items-center gap-1.5">
                                    <MapPin class="w-3.5 h-3.5 text-indigo-500" />
                                    {{ entry.venue || 'TBA' }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ entry.department?.name }}</div>
                                <div class="text-[11px] text-muted-foreground font-medium">{{ entry.level }} Level</div>
                            </TableCell>
                            <TableCell class="text-right pr-6">
                                <Button 
                                    variant="ghost" 
                                    size="icon" 
                                    class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-lg transition-colors"
                                    @click="deleteEntry(entry.id)"
                                    title="Delete Entry"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredEntries.length === 0">
                            <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <Clock class="w-8 h-8 opacity-20" />
                                    <p class="font-bold text-sm">No lecture entries match the selected criteria.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <Pagination :links="timetables.links" class="mt-6" />

            <!-- Create Modal -->
            <Dialog v-model:open="isCreateOpen">
                <DialogContent class="max-w-xl rounded-2xl">
                    <DialogHeader>
                        <DialogTitle class="text-xl font-bold flex items-center gap-2">
                            <Plus class="w-5 h-5 text-indigo-600" /> Add Timetable Lecture Entry
                        </DialogTitle>
                    </DialogHeader>
                    
                    <div class="grid gap-5 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2 flex flex-col">
                                <Label class="mb-1 text-xs font-bold uppercase text-muted-foreground">Session</Label>
                                <SearchableSelect
                                    v-model="form.session_id"
                                    :items="sessionItems"
                                    placeholder="Select Session"
                                    search-placeholder="Search sessions..."
                                    :error-class="!!form.errors.session_id"
                                />
                                <p v-if="form.errors.session_id" class="text-xs text-red-500">{{ form.errors.session_id }}</p>
                            </div>
                            <div class="space-y-2 flex flex-col">
                                <Label class="mb-1 text-xs font-bold uppercase text-muted-foreground">Semester</Label>
                                <SearchableSelect
                                    v-model="form.semester_id"
                                    :items="semesterItems"
                                    placeholder="Select Semester"
                                    search-placeholder="Search semesters..."
                                    :error-class="!!form.errors.semester_id"
                                />
                                <p v-if="form.errors.semester_id" class="text-xs text-red-500">{{ form.errors.semester_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2 flex flex-col">
                                <Label class="mb-1 text-xs font-bold uppercase text-muted-foreground">Department</Label>
                                <SearchableSelect
                                    v-model="form.department_id"
                                    :items="departmentItems"
                                    placeholder="Select Department"
                                    search-placeholder="Search departments..."
                                    :error-class="!!form.errors.department_id"
                                />
                                <p v-if="form.errors.department_id" class="text-xs text-red-500">{{ form.errors.department_id }}</p>
                            </div>
                            <div class="space-y-2 flex flex-col">
                                <Label class="mb-1 text-xs font-bold uppercase text-muted-foreground">Level</Label>
                                <SearchableSelect
                                    v-model="form.level"
                                    :items="levelItems"
                                    placeholder="Select Level"
                                    search-placeholder="Search levels..."
                                    :error-class="!!form.errors.level"
                                />
                                <p v-if="form.errors.level" class="text-xs text-red-500">{{ form.errors.level }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div class="space-y-2 flex flex-col">
                                <Label class="mb-1 text-xs font-bold uppercase text-muted-foreground">Course</Label>
                                <SearchableSelect
                                    v-model="form.course_id"
                                    :items="courseItems"
                                    placeholder="Select Course"
                                    search-placeholder="Search courses..."
                                    :error-class="!!form.errors.course_id"
                                />
                                <p v-if="form.errors.course_id" class="text-xs text-red-500">{{ form.errors.course_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-2 flex flex-col">
                                <Label class="mb-1 text-xs font-bold uppercase text-muted-foreground">Day</Label>
                                <SearchableSelect
                                    v-model="form.day"
                                    :items="dayItems"
                                    placeholder="Select Day"
                                    search-placeholder="Search days..."
                                    :error-class="!!form.errors.day"
                                />
                                <p v-if="form.errors.day" class="text-xs text-red-500">{{ form.errors.day }}</p>
                            </div>
                             <div class="space-y-2">
                                <Label class="text-xs font-bold uppercase text-muted-foreground">Start Time</Label>
                                <Input type="time" v-model="form.start_time" class="h-10 font-semibold" :class="{'border-red-500': form.errors.start_time}" />
                                <p v-if="form.errors.start_time" class="text-xs text-red-500">{{ form.errors.start_time }}</p>
                            </div>
                             <div class="space-y-2">
                                <Label class="text-xs font-bold uppercase text-muted-foreground">End Time</Label>
                                <Input type="time" v-model="form.end_time" class="h-10 font-semibold" :class="{'border-red-500': form.errors.end_time}" />
                                <p v-if="form.errors.end_time" class="text-xs text-red-500">{{ form.errors.end_time }}</p>
                            </div>
                        </div>

                         <div class="space-y-2">
                            <Label class="text-xs font-bold uppercase text-muted-foreground">Venue / Lecture Hall</Label>
                            <Input v-model="form.venue" placeholder="e.g. Lecture Hall A, Science Complex" class="h-10 font-medium" :class="{'border-red-500': form.errors.venue}" />
                            <p v-if="form.errors.venue" class="text-xs text-red-500">{{ form.errors.venue }}</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isCreateOpen = false">Cancel</Button>
                        <Button @click="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 font-bold">Save Entry</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Import Modal -->
            <Dialog v-model:open="isImportOpen">
                <DialogContent class="max-w-md rounded-2xl">
                    <DialogHeader>
                        <DialogTitle class="text-xl font-bold flex items-center gap-2">
                            <Upload class="w-5 h-5 text-indigo-600" /> Import Bulk Timetable
                        </DialogTitle>
                    </DialogHeader>
                    
                    <div class="space-y-4 py-4">
                        <div class="p-4 bg-muted/50 rounded-xl flex items-center justify-between border">
                            <div class="text-sm">
                                <p class="font-bold">CSV Template Format</p>
                                <p class="text-xs text-muted-foreground">Download standard schedule template</p>
                            </div>
                            <Button variant="outline" size="sm" @click="downloadTemplate" class="font-bold">
                                <Download class="w-4 h-4 mr-2" /> Template
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <Label class="text-xs font-bold uppercase text-muted-foreground">Upload Schedule File (CSV / XLSX)</Label>
                            <Input 
                                type="file" 
                                accept=".csv,.xlsx,.xls"
                                @change="(e: any) => importForm.file = e.target.files[0]"
                                class="h-10"
                            />
                            <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isImportOpen = false">Cancel</Button>
                        <Button @click="submitImport" :disabled="importForm.processing" class="bg-indigo-600 hover:bg-indigo-700 font-bold">
                            <Upload class="w-4 h-4 mr-2" /> Upload & Process
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
