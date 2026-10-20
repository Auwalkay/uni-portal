<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { 
    Calendar, Clock, Building2, Plus, Trash2, ArrowLeft, 
    CheckCircle2, AlertCircle, BookOpen, Check
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';

interface Props {
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    buildings: any[];
    exams?: any[];
    preselectedExamId?: string;
    currentSessionId?: string;
    currentSemesterId?: string;
    exam?: any;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.exam);

// Reactive State
const selectedCourseIds = ref<string[]>(
    props.exam?.course_id ? [props.exam.course_id] : []
);
const tempCourseSelect = ref<string>('');

const parseInitialVenues = () => {
    if (props.exam?.venue) {
        const parts = props.exam.venue.split(',').map((s: string) => s.trim()).filter(Boolean);
        return parts.map((p: string) => ({
            name: p,
            capacity: props.exam.max_capacity || 100
        }));
    }
    return [{ name: '', capacity: 100 }];
};

const venueList = ref<Array<{ name: string; capacity: number | string }>>(parseInitialVenues());

// Form Initialization
const form = useForm({
    exam_id: props.exam?.exam_id || props.preselectedExamId || '',
    session_id: props.exam?.session_id || props.currentSessionId || props.sessions[0]?.id || '',
    semester_id: props.exam?.semester_id || props.currentSemesterId || props.semesters[0]?.id || '',
    department_id: props.exam?.department_id || '',
    level: props.exam?.level || '100',
    course_id: props.exam?.course_id || '',
    course_ids: props.exam?.course_id ? [props.exam.course_id] : [] as string[],
    exam_date: props.exam?.exam_date ? props.exam.exam_date.substring(0, 10) : '',
    start_time: props.exam?.start_time || '09:00',
    end_time: props.exam?.end_time || '12:00',
    venue: props.exam?.venue || '',
    max_capacity: props.exam?.max_capacity || 100,
    instructions: props.exam?.instructions || '',
});

// Smart coupling: When exam_id is selected, automatically lock and sync session_id and semester_id
watch(() => form.exam_id, (newExamId) => {
    if (newExamId && props.exams) {
        const found = props.exams.find(e => e.id === newExamId);
        if (found) {
            form.session_id = found.session_id;
            form.semester_id = found.semester_id;
        }
    }
}, { immediate: true });

// Auto-filter Semesters based on selected Session
const semesterOptions = computed(() => {
    let list = props.semesters || [];
    if (form.session_id) {
        list = list.filter(s => s.session_id === form.session_id);
    }
    return list.map(sem => ({ value: sem.id, label: sem.name }));
});

// Dropdown Options
const sessionOptions = computed(() => [
    ...(props.sessions || []).map(s => ({ value: s.id, label: `${s.name}${s.is_current ? ' (Current)' : ''}` }))
]);

const examExerciseOptions = computed(() => {
    const list = props.exams || [];
    return [
        { value: '', label: 'None (Standalone Schedule)' },
        ...list.map(e => {
            const sess = e.session?.name || '';
            const sem = e.semester?.name || '';
            const info = [sess, sem].filter(Boolean).join(' • ');
            return { 
                value: e.id, 
                label: info ? `${e.title} (${info})` : e.title 
            };
        })
    ];
});

const selectedExamDetails = computed(() => {
    if (!form.exam_id || !props.exams) return null;
    return props.exams.find(e => e.id === form.exam_id) || null;
});

const departmentOptions = computed(() => [
    { value: '', label: 'All Departments / General' },
    ...(props.departments || []).map(d => ({ value: d.id, label: d.name }))
]);

const levelOptions = computed(() => [
    { value: '100', label: '100 Level' },
    { value: '200', label: '200 Level' },
    { value: '300', label: '300 Level' },
    { value: '400', label: '400 Level' },
    { value: '500', label: '500 Level' },
    { value: '600', label: '600 Level' },
    { value: '700', label: 'PG / 700 Level' },
]);

const courseOptions = computed(() => [
    { value: '', label: '+ Select Course to Schedule...' },
    ...(props.courses || []).map(c => ({ value: c.id, label: `${c.code} - ${c.title}` }))
]);

const buildingSelectOptions = computed(() => [
    { value: '', label: 'Quick Select Campus Building...' },
    ...(props.buildings || []).map(b => ({
        value: b.name,
        label: `${b.name} (${b.code}) — Capacity: ${b.capacity}`
    }))
]);

const selectedBuildingQuick = ref('');

// Helper Functions
const addCoursePill = (val: string) => {
    if (!val) return;
    if (!selectedCourseIds.value.includes(val)) {
        selectedCourseIds.value.push(val);
    }
    tempCourseSelect.value = '';
};

const removeCoursePill = (courseId: string) => {
    selectedCourseIds.value = selectedCourseIds.value.filter(id => id !== courseId);
};

const getCourseLabel = (courseId: string) => {
    const found = (props.courses || []).find(c => c.id === courseId);
    return found ? `${found.code} - ${found.title}` : courseId;
};

const addVenueRow = () => {
    venueList.value.push({ name: '', capacity: 100 });
};

const removeVenueRow = (index: number) => {
    if (venueList.value.length > 1) {
        venueList.value.splice(index, 1);
    }
};

const handleBuildingQuickSelect = (val: string) => {
    if (!val) return;
    const found = (props.buildings || []).find(b => b.name === val);
    if (found) {
        const lastIndex = venueList.value.length - 1;
        if (venueList.value[lastIndex] && !venueList.value[lastIndex].name.trim()) {
            venueList.value[lastIndex].name = found.name;
            venueList.value[lastIndex].capacity = found.capacity;
        } else {
            venueList.value.push({ name: found.name, capacity: found.capacity });
        }
    }
    selectedBuildingQuick.value = '';
};

const totalSeatingCapacity = computed(() => {
    return venueList.value.reduce((acc, curr) => acc + (Number(curr.capacity) || 0), 0);
});

const activeHallsCount = computed(() => {
    const named = venueList.value.filter(v => v.name && v.name.trim() !== '').length;
    return named > 0 ? named : venueList.value.length;
});

// Submit Form
const handleSubmit = () => {
    if (selectedCourseIds.value.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Course Required',
            text: 'Please select at least one course for this exam timetable slot.',
        });
        return;
    }

    const validVenues = venueList.value.filter(v => v.name && v.name.trim() !== '');
    if (validVenues.length === 0 && venueList.value.some(v => !v.name.trim())) {
        venueList.value.forEach((v, idx) => {
            if (!v.name.trim()) v.name = `Main Exam Hall #${idx + 1}`;
        });
    }

    const finalVenues = venueList.value.filter(v => v.name && v.name.trim() !== '');

    form.course_id = selectedCourseIds.value[0];
    (form as any).course_ids = selectedCourseIds.value;
    form.venue = finalVenues.map(v => v.name.trim()).join(', ');
    form.max_capacity = totalSeatingCapacity.value > 0 ? totalSeatingCapacity.value : 100;

    if (isEditing.value && props.exam?.id) {
        form.put(route('admin.exams.update', props.exam.id), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Exam schedule updated successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    if (form.exam_id) {
                        router.visit(route('admin.exams.exercises.show', form.exam_id));
                    } else {
                        router.visit(route('admin.exams.index'));
                    }
                });
            },
        });
    } else {
        form.post(route('admin.exams.store'), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Scheduled!',
                    text: 'Exam timetable slot created successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    if (form.exam_id) {
                        router.visit(route('admin.exams.exercises.show', form.exam_id));
                    } else {
                        router.visit(route('admin.exams.index'));
                    }
                });
            },
        });
    }
};

const breadcrumbs = [
    { title: 'Academic Management', href: '#' },
    { title: 'Examination Exercises', href: route('admin.exams.index') },
    { title: isEditing.value ? 'Edit Paper Slot' : 'Create Paper Slot', href: '#' },
];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEditing ? 'Edit Exam Slot' : 'Schedule Exam Slot'" />

        <div class="p-6 md:p-8 space-y-8 w-full max-w-5xl mx-auto pb-28">
            
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-7 px-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 text-xs font-medium" 
                            @click="router.visit(route('admin.exams.index'))"
                        >
                            <ArrowLeft class="w-3.5 h-3.5 mr-1" /> Back to Exercises Hub
                        </Button>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2.5">
                        <Calendar class="w-7 h-7 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        {{ isEditing ? 'Edit Examination Timetable Slot' : 'Schedule Exam Paper Slot' }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-2xl">
                        Configure course paper slots, select target course(s), date & time schedule, and assign exam halls.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <Button variant="outline" size="sm" class="h-10 text-xs rounded-xl border-slate-200 dark:border-slate-800" @click="router.visit(route('admin.exams.index'))">
                        Cancel
                    </Button>
                    <Button 
                        size="sm" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs h-10 px-5 rounded-xl shadow-xs gap-1.5"
                        :disabled="form.processing"
                        @click="handleSubmit"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        {{ isEditing ? 'Save Changes' : 'Save & Publish Slot' }}
                    </Button>
                </div>
            </div>

            <!-- Form Cards Flow -->
            <form @submit.prevent="handleSubmit" class="space-y-6">
                
                <!-- Card 1: Main Exam Exercise Selection -->
                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 rounded-2xl">
                    <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold flex items-center justify-center">1</span>
                                Parent Examination Exercise
                            </CardTitle>
                            <Badge v-if="selectedExamDetails" class="bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border-indigo-200 text-[10px] font-bold">
                                Auto-Linked
                            </Badge>
                        </div>
                        <CardDescription class="text-xs text-slate-500">
                            Select the parent exercise event. Academic session and semester automatically sync.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-5 space-y-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Target Examination Exercise *
                            </Label>
                            <SearchableSelect
                                v-model="form.exam_id"
                                :items="examExerciseOptions"
                                placeholder="Select Examination Exercise..."
                                search-placeholder="Search exam exercises..."
                                trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                            />
                        </div>

                        <!-- Auto-filled Info Badge when Exercise is selected -->
                        <div v-if="selectedExamDetails" class="p-3.5 bg-indigo-50/70 dark:bg-indigo-950/40 rounded-xl border border-indigo-200/80 dark:border-indigo-800 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <Check class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-slate-100 text-xs block">{{ selectedExamDetails.title }}</span>
                                    <span v-if="selectedExamDetails.session?.name || selectedExamDetails.semester?.name" class="text-slate-500 font-medium text-[11px] block mt-0.5">
                                        {{ selectedExamDetails.session?.name }} <span v-if="selectedExamDetails.session?.name && selectedExamDetails.semester?.name">•</span> {{ selectedExamDetails.semester?.name }}
                                    </span>
                                </div>
                            </div>
                            <Badge class="bg-indigo-600 text-white text-[10px] font-semibold px-2 py-0.5">Linked</Badge>
                        </div>

                        <!-- Manual Session & Semester Dropdowns if Standalone -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Academic Session *</Label>
                                <SearchableSelect
                                    v-model="form.session_id"
                                    :items="sessionOptions"
                                    placeholder="Select Session"
                                    search-placeholder="Search sessions..."
                                    trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Semester *</Label>
                                <SearchableSelect
                                    v-model="form.semester_id"
                                    :items="semesterOptions"
                                    placeholder="Select Semester"
                                    search-placeholder="Search semesters..."
                                    trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2: Course Selection -->
                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 rounded-2xl">
                    <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold flex items-center justify-center">2</span>
                                    Course & Department
                                </CardTitle>
                                <CardDescription class="text-xs text-slate-500">Select course(s) taking this examination paper.</CardDescription>
                            </div>
                            <Badge v-if="selectedCourseIds.length > 0" class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border-indigo-200 text-xs font-semibold">
                                {{ selectedCourseIds.length }} Course(s) Selected
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="p-5 space-y-4">
                        <SearchableSelect
                            v-model="tempCourseSelect"
                            :items="courseOptions"
                            placeholder="+ Select Course to Schedule..."
                            search-placeholder="Search course code or title..."
                            trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                            @update:model-value="addCoursePill"
                        />

                        <!-- Selected Courses Pills -->
                        <div v-if="selectedCourseIds.length > 0" class="space-y-2 pt-1">
                            <Label class="text-[11px] font-semibold text-slate-400">Selected Courses</Label>
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="cId in selectedCourseIds"
                                    :key="cId"
                                    class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-200 border border-indigo-200/80 dark:border-indigo-800 rounded-lg py-1 px-3 text-xs font-semibold shadow-xs"
                                >
                                    <BookOpen class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                                    <span>{{ getCourseLabel(cId) }}</span>
                                    <button
                                        type="button"
                                        @click="removeCoursePill(cId)"
                                        class="text-indigo-400 hover:text-rose-600 font-bold ml-1 rounded-full w-4 h-4 inline-flex items-center justify-center transition-colors"
                                        title="Remove course"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-3 bg-amber-50/80 dark:bg-amber-950/30 rounded-xl border border-amber-200/80 dark:border-amber-900 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2.5">
                            <AlertCircle class="w-4 h-4 text-amber-600 shrink-0" />
                            <span>Select at least one course for this timetable slot from the dropdown above.</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Department (Optional)</Label>
                                <SearchableSelect
                                    v-model="form.department_id"
                                    :items="departmentOptions"
                                    placeholder="All Departments"
                                    search-placeholder="Search departments..."
                                    trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Level</Label>
                                <SearchableSelect
                                    v-model="form.level"
                                    :items="levelOptions"
                                    placeholder="Select Level"
                                    search-placeholder="Search levels..."
                                    trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 3: Date, Time & Venues -->
                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 rounded-2xl">
                    <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                        <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold flex items-center justify-center">3</span>
                            Date, Time & Venue Allocation
                        </CardTitle>
                        <CardDescription class="text-xs text-slate-500">Specify exam date, start & end time, and allocate hall venues.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Exam Date *</Label>
                                <Input v-model="form.exam_date" type="date" class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800" required />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Start Time *</Label>
                                <Input v-model="form.start_time" type="time" class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800" required />
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">End Time *</Label>
                                <Input v-model="form.end_time" type="time" class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800" required />
                            </div>
                        </div>

                        <div class="space-y-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Exam Venues & Capacity</Label>
                                <Button type="button" variant="outline" size="sm" class="h-7 text-xs gap-1 border-indigo-200 text-indigo-700 dark:border-indigo-800 dark:text-indigo-300 rounded-lg font-medium" @click="addVenueRow">
                                    <Plus class="w-3.5 h-3.5" /> Add Venue
                                </Button>
                            </div>

                            <!-- Quick Select Building -->
                            <div v-if="buildingSelectOptions.length > 1">
                                <SearchableSelect
                                    v-model="selectedBuildingQuick"
                                    :items="buildingSelectOptions"
                                    placeholder="Quick select campus building..."
                                    search-placeholder="Search campus building..."
                                    trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                                    @update:model-value="handleBuildingQuickSelect"
                                />
                            </div>

                            <!-- Venue Rows -->
                            <div class="space-y-2">
                                <div 
                                    v-for="(vItem, idx) in venueList" 
                                    :key="idx" 
                                    class="flex items-center gap-2 bg-slate-50/80 dark:bg-slate-950/60 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800"
                                >
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 w-5 text-center">#{{ idx + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <Input
                                            v-model="vItem.name"
                                            placeholder="Venue / Hall Name (e.g. LT 1)"
                                            class="h-8 text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-lg"
                                            required
                                        />
                                    </div>
                                    <div class="w-24">
                                        <Input
                                            v-model="vItem.capacity"
                                            type="number"
                                            min="1"
                                            placeholder="Seats"
                                            class="h-8 text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-lg"
                                            required
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-0 text-slate-400 hover:text-rose-600 rounded-lg"
                                        :disabled="venueList.length <= 1"
                                        @click="removeVenueRow(idx)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Seating Capacity Summary -->
                            <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/40 rounded-xl border border-indigo-200/80 dark:border-indigo-800 flex items-center justify-between text-xs">
                                <span class="text-slate-600 dark:text-slate-300 font-medium">
                                    Total Seating Capacity ({{ activeHallsCount }} hall(s)):
                                </span>
                                <strong class="text-indigo-600 dark:text-indigo-400 text-sm font-bold">{{ totalSeatingCapacity }} Seats</strong>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 4: Instructions -->
                <Card class="border border-slate-200/80 dark:border-slate-800/80 shadow-xs bg-white dark:bg-slate-900 rounded-2xl">
                    <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                        <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold flex items-center justify-center">4</span>
                            Hall Rules & Remarks (Optional)
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-5">
                        <Textarea 
                            v-model="form.instructions" 
                            rows="2" 
                            placeholder="E.g. Candidates must bring valid student ID card and exam docket. No mobile phones allowed." 
                            class="text-xs rounded-lg border-slate-200 dark:border-slate-800" 
                        />
                    </CardContent>
                </Card>
            </form>
        </div>
    </AdminLayout>
</template>
