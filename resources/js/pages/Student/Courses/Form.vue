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
import { Loader2, Search, X } from 'lucide-vue-next';

const props = defineProps<{
    student: any;
    session: any;
    semester: any;
    semesters: any[]; 
    courses: any[];
    registeredCourseIds: number[];
    registeredCourses: any[]; 
    faculties: any[];
    departments: any[];
    maxUnits: number; 
    isLocked: boolean; // New Prop for locking status
    filters: {
        level: string;
        semester: string;
        faculty_id: string;
        department_id: string;
    }
}>();

const form = useForm({
    courses: [...props.registeredCourseIds],
    semester_id: props.semester.id,
});

// Selected Courses Map (for Preview)
const selectedCoursesMap = ref(new Map<number, any>());

// Initialize
if (props.registeredCourses.length > 0) {
    props.registeredCourses.forEach(course => {
        selectedCoursesMap.value.set(course.id, course);
    });
} else {
    // Auto-select compulsory courses
    if (!props.isLocked) {
        let runningTotal = 0;
        props.courses.forEach(course => {
            if (course.is_compulsory) {
                 if ((runningTotal + course.units) <= props.maxUnits) {
                     form.courses.push(course.id);
                     selectedCoursesMap.value.set(course.id, course);
                     runningTotal += course.units;
                 }
            }
        });
    }
}

// Filter State
const filterForm = ref({
    faculty_id: props.filters.faculty_id || '',
    department_id: props.filters.department_id || '',
    level: props.filters.level ? String(props.filters.level) : '',
    semester_id: props.semester.id, // Use ID for filtering/switching
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
        only: ['courses', 'filters', 'flash'],
        onFinish: () => isLoading.value = false
    });
};

// Computed Array for Reactivity
const selectedCourses = computed(() => {
    return Array.from(selectedCoursesMap.value.values());
});

const totalUnits = computed(() => {
    return selectedCourses.value.reduce((sum, course) => sum + course.units, 0);
});

const toggleCourse = (courseId: number) => {
    if (props.isLocked) return;

    if (form.courses.includes(courseId)) {
        form.courses = form.courses.filter(id => id !== courseId);
        selectedCoursesMap.value.delete(courseId);
    } else {
        const course = props.courses.find(c => c.id === courseId);
        
        if (!course) return; 

        if ((totalUnits.value + course.units) > props.maxUnits) {
            Swal.fire({
                icon: 'error',
                title: 'Limit Exceeded',
                text: `Maximum of ${props.maxUnits} units allowed.`,
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
    if (props.isLocked) return;

    if (form.courses.includes(courseId)) {
        form.courses = form.courses.filter(id => id !== courseId);
        selectedCoursesMap.value.delete(courseId);
    }
};

const submit = () => {
    if (props.isLocked) return;

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
</script>

<template>
    <Head title="Course Registration Form" />

    <StudentLayout>
        <div class="space-y-6 p-6">
            <div class="border-b pb-4 mb-4 flex justify-between items-end">
                 <div>
                     <h2 class="text-2xl font-serif font-bold tracking-tight text-gray-900">Course Registration Form</h2>
                     <p class="text-sm text-muted-foreground mt-1">
                        {{ session?.name }} &bull; {{ semester?.name }}
                     </p>
                 </div>
                 <div v-if="isLocked">
                     <Badge variant="destructive" class="text-sm px-3 py-1 flex gap-2 items-center">
                        <Lock class="w-3 h-3" />
                        Registration Closed
                     </Badge>
                 </div>
            </div>

            <!-- Selected Courses (Top Section) -->
            <Card class="mb-6 rounded-none border-t-4 border-t-primary shadow-sm bg-background">
                <CardHeader class="pb-2">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <CardTitle class="text-xl">Your Course List</CardTitle>
                            <CardDescription>Courses selected for registration</CardDescription>
                        </div>
                        <div class="flex items-center gap-4 bg-muted/40 p-2 rounded-lg border">
                            <div class="text-right">
                                <span class="block text-xs uppercase font-medium text-muted-foreground">Total Units</span>
                                <span class="block text-2xl font-mono font-bold leading-none" :class="totalUnits > maxUnits ? 'text-red-600' : 'text-primary'">
                                    {{ totalUnits }}
                                </span>
                            </div>
                            <Separator orientation="vertical" class="h-8" />
                             <div class="text-left">
                                <span class="block text-xs uppercase font-medium text-muted-foreground">Max Allowed</span>
                                <span class="block text-xl font-mono leading-none text-muted-foreground">
                                    {{ maxUnits }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="selectedCourses.length === 0" class="text-center py-8 border-2 border-dashed rounded-lg bg-muted/10">
                        <p class="text-muted-foreground">No courses selected yet. Select courses from the available list below.</p>
                    </div>
                    <div v-else>
                         <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <div v-for="course in selectedCourses" :key="course.id" 
                                class="relative group bg-card border rounded-md p-3 hover:shadow-md transition-all flex flex-col justify-between h-full hover:border-primary/50"
                            >
                                <button v-if="!isLocked" @click="removeCourse(course.id)" class="absolute top-1 right-1 p-1 text-muted-foreground hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" title="Remove course">
                                    <X class="h-4 w-4" />
                                </button>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <Badge variant="outline" class="font-mono text-xs">{{ course.code }}</Badge>
                                        <Badge :variant="course.is_compulsory ? 'default' : 'secondary'" class="text-[10px] px-1 h-5">
                                            {{ course.is_compulsory ? 'Core' : 'Elective' }}
                                        </Badge>
                                    </div>
                                    <h4 class="font-medium text-sm leading-tight line-clamp-2 pr-4 mb-2">{{ course.title }}</h4>
                                </div>
                                <div class="mt-auto pt-2 border-t flex justify-between items-center text-xs text-muted-foreground">
                                    <span>{{ course.units }} Units</span>
                                    <span>{{ course.level }} Lvl</span>
                                </div>
                            </div>
                         </div>
                         
                         <div class="mt-4 flex justify-end" v-if="!isLocked">
                            <Button @click="submit" size="lg" :disabled="form.processing || form.courses.length === 0" class="w-full md:w-auto min-w-[200px]">
                                {{ registeredCourseIds.length > 0 ? 'Update Registration' : 'Submit Registration' }}
                            </Button>
                         </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 md:grid-cols-4">
                <!-- Sidebar / Filters -->
                <div class="md:col-span-1 space-y-6">
                    <Card class="rounded-none border-2 shadow-sm">
                        <CardHeader class="pb-3 bg-muted/30 border-b">
                            <CardTitle class="text-base font-semibold">Filter Courses</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4 pt-4">
                             <div class="grid gap-2">
                                <Label>Faculty</Label>
                                <Select v-model="filterForm.faculty_id">
                                    <SelectTrigger class="h-8">
                                        <SelectValue placeholder="Select Faculty" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="fac in faculties" :key="fac.id" :value="fac.id">
                                            {{ fac.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="grid gap-2">
                                <Label>Department</Label>
                                <Select v-model="filterForm.department_id" :disabled="!filterForm.faculty_id">
                                    <SelectTrigger class="h-8">
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                             <div class="grid gap-2">
                                <Label>Level</Label>
                                <Select v-model="filterForm.level">
                                    <SelectTrigger class="h-8">
                                        <SelectValue placeholder="Select Level" />
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

                            <div class="grid gap-2">
                                <Label>Semester</Label>
                                <Select v-model="filterForm.semester_id">
                                    <SelectTrigger class="h-8">
                                        <SelectValue placeholder="Select Semester" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="sem in semesters" :key="sem.id" :value="sem.id">
                                            {{ sem.name }} <span v-if="sem.is_current">(Current)</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Button @click="loadCourses" class="w-full mt-2" :disabled="isLoading">
                                <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                                <Search v-else class="mr-2 h-4 w-4" />
                                Load Courses
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Selected Courses Preview -->
                    <!-- Selected Courses Preview Removed (Moved to Top) -->
                </div>

                <!-- Main Content / Course Table -->
                 <div class="md:col-span-3">
                    <Card class="rounded-none border-2 shadow-sm min-h-[500px]">
                         <CardHeader class="bg-muted/10 border-b py-3">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-lg">Available Courses</CardTitle>
                                <Badge variant="outline" class="font-normal">
                                    {{ courses.length }} courses found
                                </Badge>
                            </div>
                        </CardHeader>
                         <div class="overflow-x-auto">
                            <Table class="border-collapse">
                                <TableHeader class="bg-muted/50">
                                    <TableRow class="hover:bg-transparent">
                                        <TableHead class="w-[50px] border-r font-bold text-gray-900">Select</TableHead>
                                        <TableHead class="border-r font-bold text-gray-900 w-[120px]">Code</TableHead>
                                        <TableHead class="border-r font-bold text-gray-900">Course Title</TableHead>
                                        <TableHead class="border-r font-bold text-gray-900 w-[80px] text-center">Units</TableHead>
                                        <TableHead class="border-r font-bold text-gray-900 w-[100px] text-center">Type</TableHead>
                                        <TableHead class="font-bold text-gray-900 w-[120px]">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="courses.length === 0">
                                        <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
                                            No courses found matching your criteria.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow 
                                        v-for="(course, index) in courses" 
                                        :key="course.id" 
                                        class="cursor-pointer transition-colors hover:bg-yellow-50/50"
                                        :class="[
                                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50/50',
                                            isLocked ? 'cursor-not-allowed opacity-60' : ''
                                        ]"
                                        @click="toggleCourse(course.id)"
                                    >
                                        <TableCell class="border-r text-center align-middle p-2" @click.stop>
                                             <Checkbox 
                                                :checked="form.courses.includes(course.id)" 
                                                @update:checked="toggleCourse(course.id)"
                                                :disabled="isLocked"
                                                class="translate-y-[2px]"
                                            />
                                        </TableCell>
                                        <TableCell class="border-r font-mono font-medium text-primary">
                                            {{ course.code }}
                                        </TableCell>
                                        <TableCell class="border-r font-medium">
                                            {{ course.title }}
                                             <div class="text-xs text-muted-foreground mt-0.5" v-if="course.department">
                                                {{ course.department.name }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="border-r text-center font-mono">
                                            {{ course.units }}
                                        </TableCell>
                                        <TableCell class="border-r text-center">
                                            <Badge :variant="course.is_compulsory ? 'default' : 'secondary'" class="rounded-sm px-1.5 py-0">
                                                {{ course.is_compulsory ? 'Core' : 'Elective' }}
                                            </Badge>
                                        </TableCell>
                                         <TableCell>
                                            <span v-if="registeredCourseIds.includes(course.id)" class="inline-flex items-center text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                                Registered
                                            </span>
                                            <span v-if="form.courses.includes(course.id) && !registeredCourseIds.includes(course.id)" class="inline-flex items-center text-xs font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">
                                                Selected
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                         </div>
                    </Card>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
