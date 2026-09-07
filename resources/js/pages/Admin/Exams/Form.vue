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
    CheckCircle2, Sparkles, AlertCircle, BookOpen, Layers, School
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

interface Props {
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    buildings: any[];
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

// Dropdown Options
const sessionOptions = computed(() => [
    ...(props.sessions || []).map(s => ({ value: s.id, label: `${s.name}${s.is_current ? ' (Current)' : ''}` }))
]);

const semesterOptions = computed(() => [
    ...(props.semesters || []).map(sem => ({ value: sem.id, label: sem.name }))
]);

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
    { value: '', label: '+ Add Course to Exam Schedule...' },
    ...(props.courses || []).map(c => ({ value: c.id, label: `${c.code} - ${c.title}` }))
]);

const buildingSelectOptions = computed(() => [
    { value: '', label: 'Quick Select Registered Building...' },
    ...(props.buildings || []).map(b => ({
        value: b.name,
        label: `🏢 ${b.name} (${b.code}) — Cap: ${b.capacity}`
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

// Submit Form
const handleSubmit = () => {
    if (selectedCourseIds.value.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Course Required',
            text: 'Please select at least one course for the examination schedule.',
        });
        return;
    }

    const validVenues = venueList.value.filter(v => v.name.trim() !== '');
    if (validVenues.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Venue Required',
            text: 'Please enter at least one venue or hall for this exam schedule.',
        });
        return;
    }

    form.course_id = selectedCourseIds.value[0];
    (form as any).course_ids = selectedCourseIds.value;
    form.venue = validVenues.map(v => v.name.trim()).join(', ');
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
                    router.visit(route('admin.exams.index'));
                });
            },
        });
    } else {
        form.post(route('admin.exams.store'), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Scheduled!',
                    text: 'Exam schedule(s) created successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    router.visit(route('admin.exams.index'));
                });
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="isEditing ? 'Edit Exam Schedule' : 'Schedule Examination Timetable'" />

        <div class="max-w-6xl mx-auto space-y-6 pb-24">
            <!-- Top Header & Navigation -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-8 px-2 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100" 
                            @click="router.visit(route('admin.exams.index'))"
                        >
                            <ArrowLeft class="w-4 h-4 mr-1" /> Back to Timetable Hub
                        </Button>
                        <Badge variant="outline" class="bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300">
                            {{ isEditing ? 'Edit Schedule' : 'New Schedule' }}
                        </Badge>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <Calendar class="w-7 h-7 text-purple-600 dark:text-purple-400 inline" />
                        {{ isEditing ? 'Edit Examination Schedule' : 'Schedule Examination Timetable' }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Configure course papers, date, time slots, and allocate hall venues with capacity limits.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="router.visit(route('admin.exams.index'))">
                        Cancel
                    </Button>
                    <Button 
                        size="sm" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-5"
                        :disabled="form.processing"
                        @click="handleSubmit"
                    >
                        <CheckCircle2 class="w-4 h-4 mr-1.5" />
                        {{ isEditing ? 'Save Changes' : 'Publish Schedule' }}
                    </Button>
                </div>
            </div>

            <!-- Form Content Grid -->
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Left Column: Academic & Course Details (7 Cols) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- 1. Academic Session & Semester Card -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-xs">
                            <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                    <School class="w-4 h-4 text-purple-600" />
                                    Academic Term & Session
                                </CardTitle>
                                <CardDescription class="text-xs">Select the target academic session and semester for this timetable entry.</CardDescription>
                            </CardHeader>
                            <CardContent class="p-4 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Academic Session</Label>
                                        <SearchableSelect
                                            v-model="form.session_id"
                                            :items="sessionOptions"
                                            placeholder="Select Session"
                                            search-placeholder="Search sessions..."
                                            :error-class="!!form.errors.session_id"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Semester</Label>
                                        <SearchableSelect
                                            v-model="form.semester_id"
                                            :items="semesterOptions"
                                            placeholder="Select Semester"
                                            search-placeholder="Search semesters..."
                                            :error-class="!!form.errors.semester_id"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 2. Course Selection Card (Multi-Course Support) -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-xs">
                            <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                            <BookOpen class="w-4 h-4 text-purple-600" />
                                            Course Selection (Multi-Course Supported)
                                        </CardTitle>
                                        <CardDescription class="text-xs">Add one or multiple courses if venues are shared during this exam session.</CardDescription>
                                    </div>
                                    <Badge v-if="selectedCourseIds.length > 0" class="bg-purple-100 text-purple-900 dark:bg-purple-950 dark:text-purple-200 font-bold text-xs">
                                        {{ selectedCourseIds.length }} Course(s) Selected
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="p-4 space-y-3">
                                <SearchableSelect
                                    v-model="tempCourseSelect"
                                    :items="courseOptions"
                                    placeholder="+ Add Course to Schedule..."
                                    search-placeholder="Search course by code or title..."
                                    @update:model-value="addCoursePill"
                                />

                                <!-- Selected Courses List -->
                                <div v-if="selectedCourseIds.length > 0" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Selected Exam Courses</Label>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="cId in selectedCourseIds"
                                            :key="cId"
                                            class="inline-flex items-center gap-2 bg-purple-50 text-purple-950 dark:bg-purple-950/80 dark:text-purple-200 border border-purple-200 dark:border-purple-800 rounded-lg py-1.5 px-3 text-xs font-semibold shadow-2xs"
                                        >
                                            <BookOpen class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                                            <span>{{ getCourseLabel(cId) }}</span>
                                            <button
                                                type="button"
                                                @click="removeCoursePill(cId)"
                                                class="text-purple-500 hover:text-red-600 dark:hover:text-red-400 font-bold ml-1 rounded-full hover:bg-purple-200/60 dark:hover:bg-purple-900/60 w-4 h-4 inline-flex items-center justify-center transition-colors"
                                                title="Remove course"
                                            >
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-900 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2">
                                    <AlertCircle class="w-4 h-4 text-amber-600 shrink-0" />
                                    <span>Please select at least one course from the dropdown above to create the examination schedule.</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Department (Optional)</Label>
                                        <SearchableSelect
                                            v-model="form.department_id"
                                            :items="departmentOptions"
                                            placeholder="All Departments"
                                            search-placeholder="Search departments..."
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Level</Label>
                                        <SearchableSelect
                                            v-model="form.level"
                                            :items="levelOptions"
                                            placeholder="Select Level"
                                            search-placeholder="Search levels..."
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 3. Date & Time Slot Card -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-xs">
                            <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                    <Clock class="w-4 h-4 text-purple-600" />
                                    Date & Time Slot
                                </CardTitle>
                                <CardDescription class="text-xs">Specify the examination date and exact start / end duration.</CardDescription>
                            </CardHeader>
                            <CardContent class="p-4 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Exam Date</Label>
                                        <Input v-model="form.exam_date" type="date" class="h-9 text-xs" required />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Start Time</Label>
                                        <Input v-model="form.start_time" type="time" class="h-9 text-xs" required />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">End Time</Label>
                                        <Input v-model="form.end_time" type="time" class="h-9 text-xs" required />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column: Venues Array & Capacity Card (5 Cols) -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- 4. Venues & Hall Capacity Card -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-xs">
                            <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                            <Building2 class="w-4 h-4 text-purple-600" />
                                            Exam Venues & Capacities
                                        </CardTitle>
                                        <CardDescription class="text-xs">Add halls to schedule exams across multiple buildings.</CardDescription>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" class="h-7 text-xs gap-1 border-purple-300 text-purple-700 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-300" @click="addVenueRow">
                                        <Plus class="w-3.5 h-3.5" /> Add Venue
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent class="p-4 space-y-4">
                                <!-- Quick Select Registered Campus Building -->
                                <div v-if="buildingSelectOptions.length > 1" class="space-y-1.5">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Quick Select Registered Campus Building</Label>
                                    <SearchableSelect
                                        v-model="selectedBuildingQuick"
                                        :items="buildingSelectOptions"
                                        placeholder="⚡ Select building to add..."
                                        search-placeholder="Search campus building..."
                                        @update:model-value="handleBuildingQuickSelect"
                                    />
                                </div>

                                <!-- Dynamic Venue Rows -->
                                <div class="space-y-2.5 pt-1">
                                    <div 
                                        v-for="(vItem, idx) in venueList" 
                                        :key="idx" 
                                        class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800"
                                    >
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400 w-5 text-center">#{{ idx + 1 }}</span>
                                        <div class="flex-1 min-w-0">
                                            <Input
                                                v-model="vItem.name"
                                                placeholder="Venue / Hall Name"
                                                class="h-8 text-xs bg-white dark:bg-slate-950"
                                                required
                                            />
                                        </div>
                                        <div class="w-24">
                                            <Input
                                                v-model="vItem.capacity"
                                                type="number"
                                                min="1"
                                                placeholder="Seats"
                                                class="h-8 text-xs bg-white dark:bg-slate-950"
                                                title="Hall Capacity"
                                                required
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30"
                                            :disabled="venueList.length <= 1"
                                            @click="removeVenueRow(idx)"
                                            title="Remove venue"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Capacity Counter Summary -->
                                <div class="p-3 bg-purple-50/70 dark:bg-purple-950/40 rounded-xl border border-purple-200 dark:border-purple-900 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="text-slate-500 font-medium block text-[11px]">Selected Halls</span>
                                        <strong class="text-slate-900 dark:text-slate-100 text-sm font-bold">{{ venueList.filter(v => v.name.trim()).length }} Hall(s)</strong>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-purple-600 dark:text-purple-400 font-semibold block text-[11px]">Combined Seating Capacity</span>
                                        <strong class="text-purple-700 dark:text-purple-300 text-base font-black">{{ totalSeatingCapacity }} seats</strong>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 5. Instructions & Hall Rules Card -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-xs">
                            <CardHeader class="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <CardTitle class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                    Hall Rules & Instructions
                                </CardTitle>
                                <CardDescription class="text-xs">Optional instructions or required exam materials for students.</CardDescription>
                            </CardHeader>
                            <CardContent class="p-4 space-y-3">
                                <Textarea 
                                    v-model="form.instructions" 
                                    rows="4" 
                                    placeholder="E.g. Candidates must bring valid student ID card and exam docket. No mobile phones or programmable calculators allowed." 
                                    class="text-xs" 
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Sticky Bottom Action Footer -->
                <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 py-3 px-6 shadow-lg">
                    <div class="max-w-6xl mx-auto flex items-center justify-between">
                        <div class="text-xs text-slate-500 hidden sm:block">
                            <span v-if="selectedCourseIds.length > 0 && venueList.filter(v => v.name.trim()).length > 0">
                                Ready to schedule <strong>{{ selectedCourseIds.length }} course(s)</strong> across <strong>{{ venueList.filter(v => v.name.trim()).length }} hall(s)</strong> ({{ totalSeatingCapacity }} seats).
                            </span>
                            <span v-else class="text-amber-600 font-medium">
                                Complete course and venue selection to publish timetable.
                            </span>
                        </div>
                        <div class="flex items-center gap-3 ml-auto sm:ml-0">
                            <Button type="button" variant="outline" size="sm" @click="router.visit(route('admin.exams.index'))">
                                Cancel
                            </Button>
                            <Button 
                                type="submit" 
                                size="sm" 
                                class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-6"
                                :disabled="form.processing"
                            >
                                <CheckCircle2 class="w-4 h-4 mr-1.5" />
                                {{ isEditing ? 'Save Changes' : 'Publish Schedule' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
