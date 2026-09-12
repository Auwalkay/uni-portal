<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { 
    Calendar, Clock, Plus, Search, Trash2, Edit3, ShieldAlert, 
    FileText, CheckCircle, Eye, CalendarRange, QrCode, Sparkles, ArrowRight, Layers
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref, computed } from 'vue';

interface Props {
    exams?: any[];
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    canManageExams?: boolean;
    canCreateExams?: boolean;
}

const props = defineProps<Props>();

// Search & Filter State for Exercise Cards
const searchQuery = ref('');
const filterSessionId = ref('');
const filterSemesterId = ref('');

const filteredExams = computed(() => {
    let list = props.exams || [];
    if (filterSessionId.value) {
        list = list.filter(e => e.session_id === filterSessionId.value);
    }
    if (filterSemesterId.value) {
        list = list.filter(e => e.semester_id === filterSemesterId.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(e => 
            (e.title && e.title.toLowerCase().includes(q)) ||
            (e.session?.name && e.session.name.toLowerCase().includes(q)) ||
            (e.semester?.name && e.semester.name.toLowerCase().includes(q))
        );
    }
    return list;
});

// Dropdown Options
const sessionFilterOptions = computed(() => [
    { value: '', label: 'All Academic Sessions' },
    ...(props.sessions || []).map(s => ({ value: s.id, label: s.name }))
]);

const semesterFilterOptions = computed(() => {
    let list = props.semesters || [];
    if (filterSessionId.value) {
        list = list.filter(s => s.session_id === filterSessionId.value);
    }
    return [
        { value: '', label: 'All Semesters' },
        ...list.map(sem => ({ value: sem.id, label: sem.name }))
    ];
});

const exerciseSemesterOptions = computed(() => {
    let list = props.semesters || [];
    if (exerciseForm.session_id) {
        list = list.filter(s => s.session_id === exerciseForm.session_id);
    }
    return list.map(sem => ({ value: sem.id, label: sem.name }));
});

const sessionModalOptions = computed(() => [
    ...(props.sessions || []).map(s => ({ value: s.id, label: s.name }))
]);

const examTypeOptions = [
    { value: '', label: 'General / Main Semester' },
    { value: 'final', label: 'Final Exam' },
    { value: 'mid_term', label: 'Mid-Term Exam' },
    { value: 'cbt', label: 'Computer Based Test (CBT)' },
    { value: 'resit', label: 'Resit / Make-up Exam' },
];

// Exercise Modal State
const isExerciseModalOpen = ref(false);
const isEditingExercise = ref(false);
const editingExerciseId = ref<string | null>(null);

const exerciseForm = useForm({
    title: '',
    session_id: '',
    semester_id: '',
    exam_type: '',
    start_date: '',
    end_date: '',
    instructions: '',
});

const openCreateExerciseModal = () => {
    isEditingExercise.value = false;
    editingExerciseId.value = null;
    exerciseForm.reset();
    exerciseForm.clearErrors();
    exerciseForm.session_id = filterSessionId.value || props.sessions[0]?.id || '';
    const filtered = (props.semesters || []).filter(s => s.session_id === exerciseForm.session_id);
    exerciseForm.semester_id = filtered[0]?.id || props.semesters[0]?.id || '';
    exerciseForm.exam_type = '';
    isExerciseModalOpen.value = true;
};

const openEditExerciseModal = (exam: any) => {
    isEditingExercise.value = true;
    editingExerciseId.value = exam.id;
    exerciseForm.title = exam.title || '';
    exerciseForm.session_id = exam.session_id || '';
    exerciseForm.semester_id = exam.semester_id || '';
    exerciseForm.exam_type = exam.exam_type || '';
    exerciseForm.start_date = exam.start_date ? exam.start_date.substring(0, 10) : '';
    exerciseForm.end_date = exam.end_date ? exam.end_date.substring(0, 10) : '';
    exerciseForm.instructions = exam.instructions || '';
    isExerciseModalOpen.value = true;
};

const submitExercise = () => {
    if (isEditingExercise.value && editingExerciseId.value) {
        exerciseForm.put(route('admin.exams.exercises.update', editingExerciseId.value), {
            onSuccess: () => {
                isExerciseModalOpen.value = false;
                exerciseForm.reset();
            },
        });
    } else {
        exerciseForm.post(route('admin.exams.exercises.store'), {
            onSuccess: () => {
                isExerciseModalOpen.value = false;
                exerciseForm.reset();
            },
        });
    }
};

const deleteExercise = (examId: string) => {
    if (confirm('Are you sure you want to delete this examination exercise and its associated paper slots?')) {
        router.delete(route('admin.exams.exercises.destroy', examId));
    }
};

const breadcrumbs = [
    { title: 'Academic Management', href: '#' },
    { title: 'Examination Exercises', href: route('admin.exams.index') },
];
</script>

<template>
    <Head title="Examination Exercises" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 md:p-8 space-y-8 w-full max-w-7xl mx-auto">
            
            <!-- Sleek Top Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-3">
                        <Layers class="w-8 h-8 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        Examination Exercises
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-normal">
                        Manage major examination events. Click an exercise card to view paper slots, hall venues, invigilators, and malpractice records.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <Button 
                        v-if="props.canCreateExams" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold gap-2 text-xs h-10 px-5 rounded-xl shadow-sm transition-all" 
                        @click="openCreateExerciseModal"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Create Exercise</span>
                    </Button>

                    <Button 
                        variant="outline"
                        class="border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-700 dark:text-slate-200 font-medium gap-2 text-xs h-10 px-4 rounded-xl transition-all" 
                        @click="router.visit(route('admin.exams.scanner'))"
                    >
                        <QrCode class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <span>QR Scanner</span>
                    </Button>
                </div>
            </div>

            <!-- Clean Toolbar Filters & Search -->
            <div class="bg-slate-50/70 dark:bg-slate-900/50 p-3.5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full md:w-auto">
                    <SearchableSelect
                        v-model="filterSessionId"
                        :items="sessionFilterOptions"
                        placeholder="All Academic Sessions"
                        search-placeholder="Search session..."
                        trigger-class="h-9 text-xs rounded-lg bg-white dark:bg-slate-900"
                    />
                    <SearchableSelect
                        v-model="filterSemesterId"
                        :items="semesterFilterOptions"
                        placeholder="All Semesters"
                        search-placeholder="Search semester..."
                        trigger-class="h-9 text-xs rounded-lg bg-white dark:bg-slate-900"
                    />
                </div>

                <div class="relative w-full md:w-80">
                    <Input 
                        v-model="searchQuery" 
                        placeholder="Search exercises..." 
                        class="pl-9 h-9 text-xs rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus-visible:ring-indigo-500"
                    />
                    <Search class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                </div>
            </div>

            <!-- Examination Exercises Cards Grid -->
            <div v-if="filteredExams.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card 
                    v-for="exam in filteredExams" 
                    :key="exam.id"
                    class="group border border-slate-200/80 dark:border-slate-800/80 shadow-xs hover:shadow-md bg-white dark:bg-slate-900 hover:border-indigo-400 dark:hover:border-indigo-600 transition-all duration-200 flex flex-col justify-between rounded-2xl overflow-hidden"
                >
                    <CardContent class="p-6 space-y-5">
                        <!-- Top Metadata & Title -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <Badge class="bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 text-[11px] font-semibold px-2.5 py-0.5 rounded-md">
                                    {{ exam.session?.name || 'Academic Session' }}
                                </Badge>
                                <Badge class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 text-[11px] font-semibold px-2.5 py-0.5 rounded-md">
                                    {{ exam.semester?.name || 'Semester' }}
                                </Badge>
                                <Badge v-if="exam.exam_type" variant="outline" class="text-[10px] font-medium capitalize text-slate-500 border-slate-200 dark:border-slate-800">
                                    {{ exam.exam_type.replace('_', ' ') }}
                                </Badge>
                            </div>

                            <h3 class="font-bold text-slate-900 dark:text-slate-100 text-lg leading-snug group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                {{ exam.title }}
                            </h3>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <div class="space-y-1">
                                <span class="text-slate-400 font-medium text-[11px]">Duration</span>
                                <p class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                    <span>
                                        {{ exam.start_date ? format(new Date(exam.start_date), 'MMM dd') : 'TBD' }} - 
                                        {{ exam.end_date ? format(new Date(exam.end_date), 'MMM dd, yyyy') : 'TBD' }}
                                    </span>
                                </p>
                            </div>

                            <div class="space-y-1">
                                <span class="text-slate-400 font-medium text-[11px]">Paper Slots</span>
                                <p class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <FileText class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                    <span>{{ exam.schedules_count || 0 }} Scheduled</span>
                                </p>
                            </div>
                        </div>

                        <!-- Status Bar -->
                        <div class="flex items-center justify-between pt-2 text-xs">
                            <span class="text-slate-400 text-[11px]">Status</span>
                            <div class="flex items-center gap-1.5">
                                <span :class="['w-2 h-2 rounded-full', exam.is_published ? 'bg-emerald-500' : 'bg-amber-500']"></span>
                                <span :class="['text-xs font-semibold', exam.is_published ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400']">
                                    {{ exam.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </div>
                        </div>
                    </CardContent>

                    <!-- Card Actions Footer -->
                    <div class="px-6 py-3.5 bg-slate-50/80 dark:bg-slate-950/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 text-xs">
                        <Button 
                            size="sm" 
                            class="h-8 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-xs px-4 gap-1.5 transition-all"
                            @click="router.visit(route('admin.exams.exercises.show', exam.id))"
                        >
                            <Eye class="w-3.5 h-3.5" />
                            <span>{{ props.canManageExams ? 'Manage Exercise' : 'View Timetable' }}</span>
                            <ArrowRight class="w-3 h-3 ml-0.5 opacity-70" />
                        </Button>

                        <div v-if="props.canManageExams" class="flex items-center gap-1">
                            <Button 
                                size="sm" 
                                variant="ghost" 
                                class="h-8 w-8 p-0 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg"
                                @click="openEditExerciseModal(exam)"
                                title="Edit Exercise"
                            >
                                <Edit3 class="w-3.5 h-3.5" />
                            </Button>

                            <Button 
                                size="sm" 
                                variant="ghost" 
                                class="h-8 w-8 p-0 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg"
                                @click="deleteExercise(exam.id)"
                                title="Delete Exercise"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 space-y-4 shadow-xs">
                <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <CalendarRange class="w-7 h-7" />
                </div>
                <div class="max-w-md mx-auto space-y-1">
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                        No Examination Exercises Found
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Create an exercise event (e.g. 2025/2026 First Semester Main Examinations) to organize timetable paper slots and venues.
                    </p>
                </div>
                <div class="pt-2">
                    <Button v-if="props.canCreateExams" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs gap-2 rounded-xl h-9 px-4" @click="openCreateExerciseModal">
                        <Plus class="w-4 h-4" /> Create First Exercise
                    </Button>
                </div>
            </div>

        </div>

        <!-- Create / Edit Examination Exercise Modal -->
        <Dialog v-model:open="isExerciseModalOpen">
            <DialogContent class="sm:max-w-[500px] rounded-2xl">
                <DialogHeader class="border-b pb-3">
                    <DialogTitle class="flex items-center gap-2 text-slate-900 dark:text-slate-100 font-bold text-base">
                        <CalendarRange class="w-4.5 h-4.5 text-indigo-600" /> 
                        {{ isEditingExercise ? 'Edit Examination Exercise' : 'Create Examination Exercise' }}
                    </DialogTitle>
                    <DialogDescription class="text-xs text-slate-500">
                        Set up main semester examination exercise metadata.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitExercise" class="space-y-4 py-2 text-xs">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Exercise Title *</Label>
                        <Input
                            v-model="exerciseForm.title"
                            placeholder="e.g. 2025/2026 First Semester Main Examinations"
                            class="text-xs h-9 rounded-lg border-slate-200 dark:border-slate-800"
                            required
                        />
                        <p v-if="exerciseForm.errors.title" class="text-red-500 text-[11px]">{{ exerciseForm.errors.title }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Academic Session *</Label>
                            <SearchableSelect
                                v-model="exerciseForm.session_id"
                                :items="sessionModalOptions"
                                placeholder="Select Session"
                                search-placeholder="Search session..."
                                trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                            />
                            <p v-if="exerciseForm.errors.session_id" class="text-red-500 text-[11px]">{{ exerciseForm.errors.session_id }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Semester *</Label>
                            <SearchableSelect
                                v-model="exerciseForm.semester_id"
                                :items="exerciseSemesterOptions"
                                placeholder="Select Semester"
                                search-placeholder="Search semester..."
                                trigger-class="h-9 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                            />
                            <p v-if="exerciseForm.errors.semester_id" class="text-red-500 text-[11px]">{{ exerciseForm.errors.semester_id }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Exam Category (Optional)</Label>
                        <select v-model="exerciseForm.exam_type" class="w-full h-9 text-xs border rounded-lg px-3 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 focus:ring-1 focus:ring-indigo-500">
                            <option v-for="opt in examTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Start Date</Label>
                            <Input v-model="exerciseForm.start_date" type="date" class="text-xs h-9 rounded-lg border-slate-200 dark:border-slate-800" />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">End Date</Label>
                            <Input v-model="exerciseForm.end_date" type="date" class="text-xs h-9 rounded-lg border-slate-200 dark:border-slate-800" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Instructions / Remarks</Label>
                        <Textarea v-model="exerciseForm.instructions" rows="2" placeholder="Optional notes..." class="text-xs rounded-lg border-slate-200 dark:border-slate-800" />
                    </div>

                    <DialogFooter class="pt-3 border-t gap-2">
                        <Button type="button" variant="ghost" class="h-9 text-xs rounded-lg" @click="isExerciseModalOpen = false">Cancel</Button>
                        <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold h-9 text-xs rounded-lg px-4" :disabled="exerciseForm.processing">
                            {{ isEditingExercise ? 'Save Changes' : 'Create Exercise' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
