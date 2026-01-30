<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import Swal from 'sweetalert2';
import { route } from 'ziggy-js';
import { Loader2, Search, X, Lock, ShieldAlert, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps<{
    student: any;
    session: any;
    semesters: any[]; 
    locks: { [key: string]: boolean };
    courses: any[];
    registeredCourseIds: number[];
    registeredCourses: any[]; 
    faculties: any[];
    departments: any[];
    maxUnits: number; 
    filters: {
        level: string;
        faculty_id: string;
        department_id: string;
    }
}>();

const form = useForm({
    courses: [...props.registeredCourseIds],
});

// Selected Courses Map (for Preview)
const selectedCoursesMap = ref(new Map<number, any>());

// Initialize Map
if (props.registeredCourses.length > 0) {
    props.registeredCourses.forEach(course => {
        selectedCoursesMap.value.set(course.id, course);
    });
}

// Auto-select compulsory courses (For unlocked semesters)
// We need to check if we are "new" to this semester registration to avoid overwriting user choice?
// Actually simpler: If nothing registered for a semester, auto-select compulsory?
// For now, let's trust existing registeredCourseIds or empty.

// Filter State
const filterForm = ref({
    faculty_id: props.filters.faculty_id || '',
    department_id: props.filters.department_id || '',
    level: props.filters.level ? String(props.filters.level) : '',
});

const isLoading = ref(false);

const filteredDepartments = computed(() => {
    if (!filterForm.value.faculty_id) return [];
    return props.departments.filter(d => d.faculty_id === filterForm.value.faculty_id);
});

watch(() => filterForm.value.faculty_id, () => {
    filterForm.value.department_id = '';
});

const loadCourses = () => {
    isLoading.value = true;
    router.get(route('student.courses.create'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    });
};

const selectedCourses = computed(() => {
    return Array.from(selectedCoursesMap.value.values());
});

// Computed Units
const unitsFirst = computed(() => {
    return selectedCourses.value
        .filter(c => c.semester === '1')
        .reduce((sum, c) => sum + c.units, 0);
});

const unitsSecond = computed(() => {
    return selectedCourses.value
        .filter(c => c.semester === '2')
        .reduce((sum, c) => sum + c.units, 0);
});

const toggleCourse = (courseId: number, semesterCode: string) => {
    if (props.locks[semesterCode]) return;

    if (form.courses.includes(courseId)) {
        form.courses = form.courses.filter(id => id !== courseId);
        selectedCoursesMap.value.delete(courseId);
    } else {
        const course = props.courses.find(c => c.id === courseId);
        if (!course) return; 

        // Check Max Units Per Semester
        const currentUnits = semesterCode === '1' ? unitsFirst.value : unitsSecond.value;
        
        if ((currentUnits + course.units) > props.maxUnits) {
             Swal.fire({
                icon: 'error',
                title: 'Limit Exceeded',
                text: `Maximum of ${props.maxUnits} units allowed for this semester.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            return;
        }
        
        form.courses.push(courseId);
        selectedCoursesMap.value.set(courseId, course);
    }
};

const removeCourse = (courseId: number) => {
    const course = selectedCoursesMap.value.get(courseId);
    if (!course) return;
    
    // Check lock
    if (props.locks[course.semester]) return;

    if (form.courses.includes(courseId)) {
        form.courses = form.courses.filter(id => id !== courseId);
        selectedCoursesMap.value.delete(courseId);
    }
};

const submit = () => {
    if (form.courses.length === 0) {
        Swal.fire({
             icon: 'warning',
             title: 'No Selection',
             text: 'Please select at least one course.',
             toast: true,
             position: 'top-end',
             showConfirmButton: false,
             timer: 3000
        });
        return;
    }
    form.post(route('student.courses.store'), {
        preserveScroll: true,
        onError: () => {
             Swal.fire({
                 icon: 'error',
                 title: 'Registration Failed',
                 text: 'An error occurred during registration.',
                 toast: true,
                 position: 'top-end',
                 showConfirmButton: false,
                 timer: 3000
             });
        }
    });
};

const getSemesterCourses = (semesterCode: string) => {
    return props.courses.filter(c => c.semester === semesterCode);
};
</script>

<template>
    <Head title="Course Registration" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50/50 pb-20">
            <!-- Immersive Header -->
             <div class="relative bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-900 pt-12 pb-24 px-6 md:px-10 overflow-hidden shadow-xl">
                 <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 contrast-125 mix-blend-overlay"></div>
                 <div class="absolute inset-0 bg-grid-white/5 [mask-image:linear-gradient(0deg,transparent,rgba(255,255,255,0.4))]"></div>
                 <div class="absolute -right-20 -top-20 w-96 h-96 bg-emerald-500/30 rounded-full blur-3xl pointer-events-none"></div>

                 <div class="relative max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start gap-6 z-10">
                    <div>
                         <div class="flex items-center gap-2 mb-3">
                            <Badge variant="outline" class="text-emerald-50 border-emerald-400/30 bg-emerald-500/10 backdrop-blur-sm px-3 py-1">Academic Session</Badge>
                             <span class="text-emerald-200/60 text-sm font-medium tracking-wide mx-2">/</span>
                             <span class="text-emerald-100 font-medium tracking-wide">{{ session?.name }}</span>
                        </div>
                        <h1 class="text-3xl md:text-5xl font-bold tracking-tight text-white mb-2 font-display">Course Registration</h1>
                        <p class="text-emerald-100/80 text-lg max-w-xl font-light leading-relaxed">
                            Select courses for both first and second semesters. Watch your unit limits.
                        </p>
                    </div>
                 </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 md:px-8 -mt-16 relative z-20">
                <!-- Smart Notification / Alert -->
                <div v-if="isLocked" class="mb-6 animate-in slide-in-from-top-4 duration-500 fade-in">
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm flex items-start gap-3">
                         <ShieldAlert class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" />
                         <div>
                            <h4 class="font-bold text-red-900 text-sm uppercase tracking-wide">Registration Locked</h4>
                            <p class="text-red-700 text-sm mt-1">Course registration is currently closed. You may view courses but cannot make changes.</p>
                         </div>
                    </div>
                </div>

                <!-- PREVIEW SECTION (Sticky functionality handled by parent or just robust layout) -->
                <div v-show="selectedCourses.length > 0" class="mb-10 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden animate-in fade-in zoom-in-95 duration-300">
                    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 p-4 md:p-5 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-emerald-100/50 p-2.5 rounded-xl text-emerald-600">
                                <CheckCircle2 class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Your Selection</h3>
                                <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Ready for submission</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                             <div class="flex flex-col items-end">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-2xl font-bold font-mono text-gray-900">{{ unitsFirst + unitsSecond }}</span>
                                    <span class="text-xs font-semibold text-gray-400 uppercase">Total Units</span>
                                </div>
                             </div>
                             <Separator orientation="vertical" class="h-8" />
                             <!-- Mini Progress Group -->
                            <div class="flex gap-3">
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[10px] uppercase font-bold text-gray-500">
                                        <span>1st Sem</span>
                                        <span :class="unitsFirst > maxUnits ? 'text-red-600' : 'text-emerald-600'">{{ unitsFirst }}u</span>
                                    </div>
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 transition-all duration-300" :style="{ width: Math.min((unitsFirst / maxUnits) * 100, 100) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[10px] uppercase font-bold text-gray-500">
                                        <span>2nd Sem</span>
                                        <span :class="unitsSecond > maxUnits ? 'text-red-600' : 'text-blue-600'">{{ unitsSecond }}u</span>
                                    </div>
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: Math.min((unitsSecond / maxUnits) * 100, 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Items Grid -->
                    <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100 bg-white">
                        <!-- First Sem Column -->
                        <div class="p-6 bg-emerald-50/10">
                            <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></span>
                                First Semester
                            </h4>
                            <div v-if="selectedCourses.filter(c => c.semester === '1').length === 0" class="flex flex-col items-center justify-center py-6 text-center border-2 border-dashed border-emerald-100 rounded-xl bg-emerald-50/50">
                                <span class="text-emerald-300 mb-1"><Loader2 class="w-5 h-5 animate-spin-slow" /></span>
                                <span class="text-sm font-medium text-emerald-800/60">No courses selected</span>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <TransitionGroup 
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition-all duration-200 ease-in absolute"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div v-for="course in selectedCourses.filter(c => c.semester === '1')" :key="course.id" 
                                        class="group relative flex items-center gap-2 bg-white ring-1 ring-gray-200/70 pl-3 pr-1 py-1.5 rounded-lg shadow-sm hover:shadow-md hover:ring-emerald-400 transition-all cursor-default"
                                    >
                                        <div class="flex flex-col">
                                            <span class="font-mono text-xs font-bold text-emerald-800 tracking-tight">{{ course.code }}</span>
                                            <span class="text-[10px] text-gray-500 truncate max-w-[120px]">{{ course.title }}</span>
                                        </div>
                                        <div class="flex flex-col items-end gap-1 border-l pl-2 ml-1 border-gray-100">
                                            <Badge variant="secondary" class="h-4 px-1 text-[9px] bg-gray-100 text-gray-500 hover:bg-gray-100">{{ course.units }}u</Badge>
                                            <button @click.stop="removeCourse(course.id)" class="text-gray-300 hover:text-red-500 transition-colors">
                                                <X class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </TransitionGroup>
                            </div>
                        </div>

                        <!-- Second Sem Column -->
                        <div class="p-6 bg-blue-50/10">
                            <h4 class="text-xs font-bold text-blue-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500 ring-4 ring-blue-50"></span>
                                Second Semester
                            </h4>
                            <div v-if="selectedCourses.filter(c => c.semester === '2').length === 0" class="flex flex-col items-center justify-center py-6 text-center border-2 border-dashed border-blue-100 rounded-xl bg-blue-50/50">
                                <span class="text-blue-300 mb-1"><Loader2 class="w-5 h-5 animate-spin-slow" /></span>
                                <span class="text-sm font-medium text-blue-800/60">No courses selected</span>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <TransitionGroup 
                                    enter-active-class="transition-all duration-300 ease-out"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition-all duration-200 ease-in absolute"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div v-for="course in selectedCourses.filter(c => c.semester === '2')" :key="course.id" 
                                        class="group relative flex items-center gap-2 bg-white ring-1 ring-gray-200/70 pl-3 pr-1 py-1.5 rounded-lg shadow-sm hover:shadow-md hover:ring-blue-400 transition-all cursor-default"
                                    >
                                        <div class="flex flex-col">
                                            <span class="font-mono text-xs font-bold text-blue-800 tracking-tight">{{ course.code }}</span>
                                            <span class="text-[10px] text-gray-500 truncate max-w-[120px]">{{ course.title }}</span>
                                        </div>
                                        <div class="flex flex-col items-end gap-1 border-l pl-2 ml-1 border-gray-100">
                                            <Badge variant="secondary" class="h-4 px-1 text-[9px] bg-gray-100 text-gray-500 hover:bg-gray-100">{{ course.units }}u</Badge>
                                            <button @click.stop="removeCourse(course.id)" class="text-gray-300 hover:text-red-500 transition-colors">
                                                <X class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </TransitionGroup>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Sidebar Area -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Smart Filter Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 sticky top-6">
                             <div class="p-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                                <h3 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                                    <Search class="w-4 h-4 text-gray-400" />
                                    Filter Courses
                                </h3>
                            </div>
                            <div class="p-4 space-y-4">
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-semibold uppercase text-gray-500">Faculty</Label>
                                    <Select v-model="filterForm.faculty_id">
                                        <SelectTrigger class="h-9 w-full bg-gray-50/50 border-gray-200">
                                            <SelectValue placeholder="All Faculties" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="fac in faculties" :key="fac.id" :value="fac.id">{{ fac.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs font-semibold uppercase text-gray-500">Department</Label>
                                    <Select v-model="filterForm.department_id" :disabled="!filterForm.faculty_id">
                                        <SelectTrigger class="h-9 w-full bg-gray-50/50 border-gray-200">
                                            <SelectValue placeholder="All Departments" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">{{ dept.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label class="text-xs font-semibold uppercase text-gray-500">Level</Label>
                                    <Select v-model="filterForm.level">
                                        <SelectTrigger class="h-9 w-full bg-gray-50/50 border-gray-200">
                                            <SelectValue placeholder="All Levels" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="100">100 Level</SelectItem>
                                            <SelectItem value="200">200 Level</SelectItem>
                                            <SelectItem value="300">300 Level</SelectItem>
                                            <SelectItem value="400">400 Level</SelectItem>
                                            <SelectItem value="500">500 Level</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Button @click="loadCourses" class="w-full bg-gray-900 text-white hover:bg-gray-800 shadow-lg shadow-gray-200/50 transition-all font-medium py-2.5 h-auto text-sm" :disabled="isLoading">
                                    <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                                    <Search v-else class="mr-2 h-4 w-4" />
                                    Apply Filters
                                </Button>
                            </div>
                        </div>

                         <!-- Mobile Action Button (Submit) -->
                        <div class="lg:hidden">
                             <Button @click="submit" size="lg" :disabled="form.processing || form.courses.length === 0" class="w-full bg-emerald-600 hover:bg-emerald-700 shadow-xl shadow-emerald-200">
                                {{ registeredCourseIds.length > 0 ? 'Update Registration' : 'Complete Registration' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Main Course Lists -->
                    <div class="lg:col-span-3 space-y-10">
                        <!-- Semester Blocks Loop -->
                         <div v-for="semCode in ['1', '2']" :key="semCode" class="animate-in fade-in slide-in-from-bottom-4 duration-500" :style="{ animationDelay: semCode === '1' ? '0ms' : '150ms' }">
                            
                            <!-- Section Header -->
                            <div class="flex items-end justify-between mb-4 px-1">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-xl shadow-sm text-lg font-bold" :class="semCode === '1' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">
                                        {{ semCode }}
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">{{ semCode === '1' ? 'First' : 'Second' }} Semester</h3>
                                        <div class="flex items-center gap-2 text-sm mt-0.5">
                                            <span v-if="locks[semCode]" class="text-red-600 font-bold flex items-center gap-1"><Lock class="w-3 h-3" /> Locked</span>
                                            <span v-else class="text-green-600 font-bold flex items-center gap-1">Registration Open</span>
                                            <span class="text-gray-300">|</span>
                                            <span class="text-muted-foreground">{{ getSemesterCourses(semCode).length }} Courses Available</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right hidden sm:block">
                                    <span class="block text-2xl font-bold font-mono leading-none" :class="semCode === '1' ? 'text-emerald-600' : 'text-blue-600'">
                                        {{ semCode === '1' ? unitsFirst : unitsSecond }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Units Selected</span>
                                </div>
                            </div>

                            <!-- Course Table Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden ring-1 ring-black/[0.02]">
                                <Table>
                                    <TableHeader class="bg-gray-50/60 uppercase tracking-wider text-[11px] font-bold text-gray-500">
                                        <TableRow class="hover:bg-transparent border-b-gray-100">
                                            <TableHead class="w-[60px] text-center py-4">Select</TableHead>
                                            <TableHead class="w-[100px] py-4">Code</TableHead>
                                            <TableHead class="py-4">Course Title</TableHead>
                                            <TableHead class="w-[80px] text-center py-4">Units</TableHead>
                                            <TableHead class="w-[100px] text-center py-4">Type</TableHead>
                                            <TableHead class="w-[120px] text-center py-4">Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <tr v-if="getSemesterCourses(semCode).length === 0">
                                            <td colspan="6" class="h-32 text-center">
                                                <div class="flex flex-col items-center justify-center text-gray-400">
                                                    <Search class="w-8 h-8 mb-2 opacity-50" />
                                                    <p>No courses found for this semester.</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <TableRow 
                                            v-for="(course, index) in getSemesterCourses(semCode)" 
                                            :key="course.id" 
                                            class="cursor-pointer transition-all duration-200 border-b border-gray-50 last:border-0 group"
                                            :class="[
                                                locks[semCode] ? 'opacity-50 grayscale cursor-not-allowed bg-gray-50/50' : 'hover:bg-gray-50',
                                                form.courses.includes(course.id) ? (semCode === '1' ? 'bg-emerald-50/40 hover:bg-emerald-50/60' : 'bg-blue-50/40 hover:bg-blue-50/60') : ''
                                            ]"
                                            @click="toggleCourse(course.id, semCode)"
                                        >
                                            <TableCell class="text-center p-3 relative" @click.stop>
                                                <!-- Custom Checkbox Visual -->
                                                 <div 
                                                    class="w-5 h-5 mx-auto rounded border transition-all duration-200 flex items-center justify-center shadow-sm"
                                                    :class="[
                                                        form.courses.includes(course.id) 
                                                            ? (semCode === '1' ? 'bg-emerald-500 border-emerald-500' : 'bg-blue-500 border-blue-500') 
                                                            : 'bg-white border-gray-300 group-hover:border-gray-400'
                                                    ]"
                                                 >
                                                    <CheckCircle2 v-if="form.courses.includes(course.id)" class="w-3.5 h-3.5 text-white" />
                                                 </div>
                                            </TableCell>
                                            <TableCell class="font-mono font-bold text-sm" :class="semCode === '1' ? 'text-emerald-700' : 'text-blue-700'">
                                                {{ course.code }}
                                            </TableCell>
                                            <TableCell class="py-4">
                                                <div class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">{{ course.title }}</div>
                                                <div class="text-[11px] text-gray-400 mt-0.5 font-medium flex items-center gap-1" v-if="course.department">
                                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span> {{ course.department.name }}
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-center font-mono text-gray-600 font-medium bg-gray-50/50 group-hover:bg-transparent transition-colors">
                                                {{ course.units }}
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <Badge :variant="course.is_compulsory ? 'default' : 'outline'" 
                                                    class="rounded-md px-2 py-0.5 text-[10px] uppercase tracking-wide font-bold shadow-none"
                                                    :class="course.is_compulsory ? 'bg-gray-900 text-white hover:bg-gray-800' : 'text-gray-500 border-gray-200'"
                                                >
                                                    {{ course.is_compulsory ? 'Core' : 'Elective' }}
                                                </Badge>
                                            </TableCell>
                                             <TableCell class="text-center">
                                                <Transition
                                                    enter-active-class="transition duration-200 ease-out"
                                                    enter-from-class="scale-90 opacity-0"
                                                    enter-to-class="scale-100 opacity-100"
                                                >
                                                    <span v-if="registeredCourseIds.includes(course.id)" class="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-100/80 px-2.5 py-1 rounded-full shadow-sm">
                                                        Registered
                                                    </span>
                                                    <span v-else-if="form.courses.includes(course.id)" class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-700 bg-indigo-100/80 px-2.5 py-1 rounded-full shadow-sm">
                                                        Selected
                                                    </span>
                                                </Transition>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                         </div>
                    </div>
                </div>

                <!-- Footer Action Bar (Sticky Bottom) -->
                <div class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-gray-200 shadow-lg z-50 p-4 md:px-8">
                     <div class="max-w-7xl mx-auto flex items-center justify-between">
                        <div class="text-sm text-gray-500 hidden sm:block">
                            Current selection: <span class="font-bold text-gray-900">{{ selectedCourses.length }} courses</span> with <span class="font-bold text-gray-900">{{ unitsFirst + unitsSecond }} units</span>.
                        </div>
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-end">
                            <Link :href="route('student.courses.index')" class="text-sm font-medium text-gray-500 hover:text-gray-900 hidden sm:block">Cancel</Link>
                             <Button @click="submit" size="lg" :disabled="form.processing || form.courses.length === 0" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-900/10 text-base font-semibold px-8 rounded-full transition-transform active:scale-95">
                                {{ registeredCourseIds.length > 0 ? 'Update Registration' : 'Submit Courses' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
