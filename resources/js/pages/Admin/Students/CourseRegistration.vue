<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import Swal from 'sweetalert2';
import { route } from 'ziggy-js';
import { Loader2, Search, X, CheckCircle2, ArrowLeft, GraduationCap, Banknote, Save, FileText } from 'lucide-vue-next';

const props = defineProps<{
    student: any;
    session: any;
    semesters: any[]; 
    courses: any[];
    registeredCourseIds: number[];
    registeredCourses: any[]; 
    faculties: any[];
    departments: any[];
    programmes: any[];
    maxUnits: number; 
    filters: any;
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

// Filter State
const filterForm = ref({
    faculty_id: props.filters?.faculty_id || '',
    department_id: props.filters?.department_id || '',
    level: props.filters?.level || String(props.student.current_level) || '100',
});

const isLoading = ref(false);

const filteredDepartments = computed(() => {
    if (!filterForm.value.faculty_id) return [];
    return props.departments.filter(d => d.faculty_id === filterForm.value.faculty_id);
});

const facultyItems = computed(() => [
    { value: '', label: 'All Faculties' },
    ...props.faculties.map(f => ({ value: String(f.id), label: f.name }))
]);

const departmentItems = computed(() => [
    { value: '', label: 'All Departments' },
    ...filteredDepartments.value.map(d => ({ value: String(d.id), label: d.name }))
]);

const levelItems = [
    { value: '', label: 'All Levels' },
    { value: '100', label: '100 Level' },
    { value: '200', label: '200 Level' },
    { value: '300', label: '300 Level' },
    { value: '400', label: '400 Level' },
    { value: '500', label: '500 Level' },
];

watch(() => filterForm.value.faculty_id, () => {
    filterForm.value.department_id = '';
});

const loadCourses = () => {
    isLoading.value = true;
    router.get(route('admin.course_registration.manage', props.student.id), filterForm.value, {
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
    form.post(route('admin.course_registration.store', props.student.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Success', 'Course registration updated successfully.', 'success');
        }
    });
};

const getSemesterCourses = (semesterCode: string) => {
    return props.courses.filter(c => c.semester === semesterCode);
};

const breadcrumbs = [
    { title: 'Academics', href: route('admin.course_registration.index') },
    { title: 'Course Registration', href: route('admin.course_registration.index') },
    { title: props.student.user.name, href: '#' }
];
</script>

<template>
    <Head :title="`Course Registration - ${student.user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-[1400px] mx-auto pb-32">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child class="rounded-full">
                        <Link :href="route('admin.students.show', student.id)">
                            <ArrowLeft class="w-5 h-5" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Manage Course Registration</h1>
                        <p class="text-muted-foreground flex items-center gap-2 mt-1">
                             <GraduationCap class="w-4 h-4" /> {{ student.user.name }} &bull; {{ student.matriculation_number }} &bull; {{ session.name }} Session
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button v-if="registeredCourseIds.length > 0" variant="outline" as-child>
                        <a :href="route('admin.course_registration.form', student.id)" target="_blank">
                            <FileText class="w-4 h-4 mr-2" /> Preview Form
                        </a>
                    </Button>
                    <Badge variant="outline" class="bg-emerald-50 text-emerald-700 border-emerald-200 px-3 py-1 text-sm font-semibold shadow-sm">
                        <CheckCircle2 class="w-4 h-4 mr-1.5" /> Fees Paid
                    </Badge>
                    <Button @click="submit" :disabled="form.processing" class="shadow-lg px-8">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Save Registration
                    </Button>
                </div>
            </div>

            <!-- Selection Preview -->
            <Card v-if="selectedCourses.length > 0" class="border-emerald-100 bg-emerald-50/20 shadow-sm animate-in fade-in zoom-in-95 duration-300">
                <CardContent class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-6">
                        <div class="flex gap-8">
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">First Semester</p>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-2xl font-bold text-gray-900">{{ unitsFirst }}</span>
                                    <span class="text-xs text-muted-foreground">/ {{ maxUnits }} Units</span>
                                </div>
                            </div>
                            <Separator orientation="vertical" class="h-10 bg-emerald-100" />
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Second Semester</p>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-2xl font-bold text-gray-900">{{ unitsSecond }}</span>
                                    <span class="text-xs text-muted-foreground">/ {{ maxUnits }} Units</span>
                                </div>
                            </div>
                            <Separator orientation="vertical" class="h-10 bg-emerald-100" />
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Total Courses</p>
                                <p class="text-2xl font-bold text-gray-900">{{ selectedCourses.length }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 flex-1 justify-end max-w-2xl">
                             <Badge v-for="course in selectedCourses" :key="course.id" 
                                variant="secondary" 
                                class="bg-white border-emerald-100 text-emerald-800 hover:bg-red-50 hover:text-red-600 cursor-pointer group transition-all pr-1"
                                @click="removeCourse(course.id)"
                            >
                                <span class="font-mono text-[10px] mr-1.5">{{ course.code }}</span>
                                <span class="max-w-[100px] truncate">{{ course.title }}</span>
                                <X class="w-3 h-3 ml-1 text-muted-foreground group-hover:text-red-500" />
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Filters -->
                <div class="lg:col-span-1 space-y-6">
                    <Card class="sticky top-6">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <Search class="w-5 h-5 text-muted-foreground" /> Filter Courses
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                             <div class="space-y-2">
                                <Label class="text-xs font-bold text-gray-500 uppercase">Faculty</Label>
                                <SearchableSelect
                                    v-model="filterForm.faculty_id"
                                    :items="facultyItems"
                                    placeholder="All Faculties"
                                    search-placeholder="Search faculties..."
                                />
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-bold text-gray-500 uppercase">Department</Label>
                                <SearchableSelect
                                    v-model="filterForm.department_id"
                                    :items="departmentItems"
                                    placeholder="All Departments"
                                    search-placeholder="Search departments..."
                                    :disabled="!filterForm.faculty_id"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label class="text-xs font-bold text-gray-500 uppercase">Academic Level</Label>
                                <SearchableSelect
                                    v-model="filterForm.level"
                                    :items="levelItems"
                                    placeholder="All Levels"
                                    search-placeholder="Search levels..."
                                />
                            </div>

                            <Button @click="loadCourses" class="w-full" variant="secondary" :disabled="isLoading">
                                <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                                <Search v-else class="mr-2 h-4 w-4" />
                                Apply Filters
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Course Tables -->
                <div class="lg:col-span-3 space-y-10">
                    <div v-for="semCode in ['1', '2']" :key="semCode" class="space-y-4">
                        <div class="flex items-center justify-between border-b pb-4">
                            <h3 class="text-2xl font-bold flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-lg text-sm bg-primary text-primary-foreground">
                                    {{ semCode }}
                                </span>
                                {{ semCode === '1' ? 'First' : 'Second' }} Semester Courses
                            </h3>
                            <Badge variant="outline" class="font-mono text-base px-3 py-1">
                                {{ semCode === '1' ? unitsFirst : unitsSecond }} / {{ maxUnits }} Units
                            </Badge>
                        </div>

                        <div class="bg-card border rounded-xl overflow-hidden">
                            <Table>
                                <TableHeader class="bg-muted/50">
                                    <TableRow>
                                        <TableHead class="w-[50px] text-center">Sel</TableHead>
                                        <TableHead class="w-[100px]">Code</TableHead>
                                        <TableHead>Title</TableHead>
                                        <TableHead class="text-center w-[80px]">Units</TableHead>
                                        <TableHead class="text-center w-[100px]">Type</TableHead>
                                        <TableHead class="text-right w-[120px]">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow 
                                        v-for="course in getSemesterCourses(semCode)" 
                                        :key="course.id"
                                        class="cursor-pointer hover:bg-muted/30 transition-colors"
                                        :class="form.courses.includes(course.id) ? 'bg-primary/5' : ''"
                                        @click="toggleCourse(course.id, semCode)"
                                    >
                                        <TableCell class="text-center" @click.stop>
                                            <div 
                                                class="w-5 h-5 mx-auto rounded border-2 transition-all flex items-center justify-center"
                                                :class="form.courses.includes(course.id) ? 'bg-primary border-primary text-white' : 'border-muted-foreground/30'"
                                            >
                                                <CheckCircle2 v-if="form.courses.includes(course.id)" class="w-3.5 h-3.5" />
                                            </div>
                                        </TableCell>
                                        <TableCell class="font-mono font-bold">{{ course.code }}</TableCell>
                                        <TableCell>
                                            <div class="font-medium text-gray-900">{{ course.title }}</div>
                                            <div v-if="course.department" class="text-[10px] text-muted-foreground uppercase tracking-tight">{{ course.department.name }}</div>
                                        </TableCell>
                                        <TableCell class="text-center font-bold">{{ course.units }}</TableCell>
                                        <TableCell class="text-center">
                                            <Badge :variant="course.is_compulsory ? 'default' : 'outline'" class="text-[9px] uppercase">
                                                {{ course.is_compulsory ? 'Core' : 'Elective' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Badge v-if="registeredCourseIds.includes(course.id)" variant="outline" class="bg-green-50 text-green-700 border-green-200 text-[10px] font-bold">
                                                REGISTERED
                                            </Badge>
                                            <Badge v-else-if="form.courses.includes(course.id)" variant="outline" class="bg-blue-50 text-blue-700 border-blue-200 text-[10px] font-bold">
                                                SELECTED
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="getSemesterCourses(semCode).length === 0">
                                        <TableCell colspan="6" class="h-32 text-center text-muted-foreground italic">
                                            No courses found for this criteria.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Footer Action -->
        <div class="fixed bottom-0 left-0 right-0 lg:left-[var(--sidebar-width)] bg-white/80 backdrop-blur-md border-t p-4 z-40">
            <div class="max-w-[1400px] mx-auto flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="hidden md:block">
                        <p class="text-xs font-bold text-gray-500 uppercase">Selection Progress</p>
                        <p class="text-sm font-medium">{{ selectedCourses.length }} courses &bull; {{ unitsFirst + unitsSecond }} Total Units</p>
                    </div>
                    <div class="flex gap-4">
                         <div class="space-y-1">
                            <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                                <span>1st Sem</span>
                                <span>{{ unitsFirst }}/{{ maxUnits }}</span>
                            </div>
                            <div class="w-20 h-1 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 transition-all" :style="{ width: Math.min((unitsFirst / maxUnits) * 100, 100) + '%' }"></div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px] font-bold uppercase text-gray-400">
                                <span>2nd Sem</span>
                                <span>{{ unitsSecond }}/{{ maxUnits }}</span>
                            </div>
                            <div class="w-20 h-1 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 transition-all" :style="{ width: Math.min((unitsSecond / maxUnits) * 100, 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                     <Link :href="route('admin.students.show', student.id)" class="text-sm font-medium text-muted-foreground hover:text-foreground">Discard Changes</Link>
                     <Button @click="submit" :disabled="form.processing" size="lg" class="px-10 shadow-xl shadow-primary/20">
                        <Save class="w-4 h-4 mr-2" /> Save Registration
                    </Button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
