<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Switch } from '@/components/ui/switch';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Swal from 'sweetalert2';
import { Plus, Pencil, BookOpen, Trash2, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js'; 
import SearchableSelect from '@/components/SearchableSelect.vue';
import FacultiesTab from './Partials/FacultiesTab.vue';
import DepartmentsTab from './Partials/DepartmentsTab.vue';
import ProgrammesTab from './Partials/ProgrammesTab.vue';
import CoursesTab from './Partials/CoursesTab.vue';
import UnitsTab from './Partials/UnitsTab.vue';

const props = defineProps<{
    faculties: any;
    departments: any; // Updated for pagination
    programmes: any; // Updated for pagination
    courses: any; 
    units: any;
    allFaculties: any[];
    allDepartments: any[];
    allProgrammes: any[];
    allCourses: any[];
    filters: any;
}>();

const courseSearchItems = computed(() => props.allCourses.map(c => ({ value: c.id, label: `${c.code} - ${c.title} (${c.units} Units)` })));

const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const activeType = ref<'faculty' | 'department' | 'programme' | 'course' | 'unit'>('faculty');

// Filter State
import { watch } from 'vue';
import { debounce } from 'lodash';

const filterForm = ref({
    search: props.filters?.search || '',
    faculty_id: props.filters?.faculty_id || '',
    department_id: props.filters?.department_id || '',
    tab: props.filters?.tab || 'faculties',
});

const filterDepartments = computed(() => {
    if (!filterForm.value.faculty_id) return props.allDepartments;
    return props.allDepartments.filter((dept: any) => dept.faculty_id === filterForm.value.faculty_id);
});

watch(filterForm, debounce(() => {
    router.get(route('admin.academics.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300), { deep: true });


const form = useForm({
    id: '',
    type: 'faculty',
    name: '',
    code: '',
    title: '',
    units: 2,
    level: '100',
    semester: '1',
    faculty_id: '',
    department_id: '',
    programme_id: '',
    program_type: 'UG',
    is_academic: true,
    scholarship_eligible: true,
});

// Computed properties for filtering
import { computed } from 'vue';

const filteredDepartments = computed(() => {
    if (!form.faculty_id) return props.allDepartments;
    return props.allDepartments.filter((dept: any) => dept.faculty_id === form.faculty_id);
});

const filteredProgrammes = computed(() => {
    if (!form.department_id) return props.allProgrammes;
    return props.allProgrammes.filter((prog: any) => prog.department_id === form.department_id);
});

const handleFacultyChange = (val: string) => {
    if (activeType.value === 'department') {
        form.faculty_id = val === 'none' ? '' : val;
        // If a faculty is selected (and not 'none'), it's likely an academic department
        if (val && val !== 'none') {
            form.is_academic = true;
        } else if (modalMode.value === 'create') {
            form.is_academic = false;
        }
    } else {
        form.faculty_id = val === 'none' ? '' : val;
    }
};


const openCreate = (type: 'faculty' | 'department' | 'programme' | 'course' | 'unit') => {
    // ... function content
    modalMode.value = 'create';
    activeType.value = type;
    form.reset();
    form.type = type;
    if (type === 'department') form.is_academic = true;
    isModalOpen.value = true;
};

const openEdit = (type: 'faculty' | 'department' | 'programme' | 'course' | 'unit', item: any) => {
    // ... function content
    modalMode.value = 'edit';
    activeType.value = type;
    form.reset();
    form.id = item.id;
    form.type = type;
    form.name = item.name || '';
    form.code = item.code || '';
    form.title = item.title || '';
    form.units = item.units || 2;
    form.level = item.level ? String(item.level) : '100';
    form.semester = item.semester || '1';
    
    // Logic to set relationships
    if (type === 'course') {
         form.faculty_id = item.department?.faculty_id || '';
         form.department_id = item.department_id || '';
         form.programme_id = item.programme_id || '';
    } else {
         form.faculty_id = item.faculty_id || item.department?.faculty_id || '';
         form.department_id = item.department_id || '';
    }
   
    form.program_type = item.type || 'UG';
    form.is_academic = item.is_academic === undefined ? true : !!item.is_academic;
    form.scholarship_eligible = item.scholarship_eligible === undefined ? true : !!item.scholarship_eligible;
    isModalOpen.value = true;
};

const submitForm = () => {
    // ... function content
     const url = modalMode.value === 'create' ? route('admin.academics.store') : route('admin.academics.update');
    
    form.post(url, {
        onSuccess: () => {
            isModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `${activeType.value} ${modalMode.value === 'create' ? 'created' : 'updated'} successfully`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
        onError: () => Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to submit form',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        }),
    });
};

const toggleActive = (type: string, id: string, currentState: boolean) => {
    // ... function content
    router.post(route('admin.academics.toggle'), {
        type,
        id,
        is_active: !currentState
    }, {
        preserveScroll: true,
        onSuccess: () => Swal.fire({
            icon: 'success',
            title: 'Updated',
            text: 'Status updated',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        }),
        onError: () => Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to update status',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        })
    });
};

// Programme Courses Management State & Methods
const isProgrammeCoursesOpen = ref(false);
watch(isProgrammeCoursesOpen, (isOpen) => {
    if (!isOpen) {
        if (document.activeElement && document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
    }
});
const selectedProgrammeForCourses = ref<any>(null);
const programmeCoursesList = ref<any[]>([]);
const isFetchingCourses = ref(false);

const newProgrammeCourseForm = ref({
    course_id: '',
    is_compulsory: false,
});
const isSubmittingProgrammeCourse = ref(false);

const importProgrammeId = ref('');
const isImportingCourses = ref(false);
const assignedSearchQuery = ref('');

const importProgrammeSearchItems = computed(() => {
    if (!selectedProgrammeForCourses.value) return [];
    return props.allProgrammes
        .filter(p => p.id !== selectedProgrammeForCourses.value.id)
        .map(p => ({ value: p.id, label: p.name }));
});

const filteredAssignedCourses = computed(() => {
    if (!assignedSearchQuery.value) return programmeCoursesList.value;
    const q = assignedSearchQuery.value.toLowerCase();
    return programmeCoursesList.value.filter(c => 
        c.code.toLowerCase().includes(q) || 
        c.title.toLowerCase().includes(q)
    );
});

const openManageProgrammeCourses = async (prog: any) => {
    selectedProgrammeForCourses.value = prog;
    isProgrammeCoursesOpen.value = true;
    newProgrammeCourseForm.value.course_id = '';
    newProgrammeCourseForm.value.is_compulsory = false;
    importProgrammeId.value = '';
    assignedSearchQuery.value = '';
    await fetchProgrammeCourses(prog.id);
};

const fetchProgrammeCourses = async (progId: string) => {
    isFetchingCourses.value = true;
    try {
        const res = await window.fetch(route('admin.academics.programmes.courses', progId));
        if (res.ok) {
            programmeCoursesList.value = await res.json();
        } else {
            Swal.fire('Error', 'Failed to load programme courses', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to load programme courses', 'error');
    } finally {
        isFetchingCourses.value = false;
    }
};

const submitAddProgrammeCourse = async () => {
    if (!newProgrammeCourseForm.value.course_id) {
        Swal.fire('Validation Error', 'Please select a course to add', 'warning');
        return;
    }
    isSubmittingProgrammeCourse.value = true;
    try {
        const res = await window.fetch(route('admin.academics.programmes.courses.store', selectedProgrammeForCourses.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify(newProgrammeCourseForm.value),
        });
        const data = await res.json();
        if (res.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Added',
                text: data.message || 'Course added successfully',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            newProgrammeCourseForm.value.course_id = '';
            newProgrammeCourseForm.value.is_compulsory = false;
            await fetchProgrammeCourses(selectedProgrammeForCourses.value.id);
        } else {
            Swal.fire('Error', data.message || 'Failed to add course', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to add course', 'error');
    } finally {
        isSubmittingProgrammeCourse.value = false;
    }
};

const submitImportProgrammeCourses = async () => {
    if (!importProgrammeId.value) {
        Swal.fire('Validation Error', 'Please select a programme to import from', 'warning');
        return;
    }
    const confirmResult = window.confirm('Import courses from the selected programme? Existing course mappings will be preserved.');
    if (!confirmResult) return;

    isImportingCourses.value = true;
    try {
        const res = await window.fetch(route('admin.academics.programmes.courses.import', selectedProgrammeForCourses.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({ source_programme_id: importProgrammeId.value }),
        });
        const data = await res.json();
        if (res.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Imported',
                text: data.message || 'Courses imported successfully',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            importProgrammeId.value = '';
            await fetchProgrammeCourses(selectedProgrammeForCourses.value.id);
        } else {
            Swal.fire('Error', data.message || 'Failed to import courses', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to import courses', 'error');
    } finally {
        isImportingCourses.value = false;
    }
};

const removeProgrammeCourse = async (courseId: string) => {
    const confirmResult = window.confirm('Are you sure you want to remove this course from the programme?');
    if (!confirmResult) return;

    try {
        const res = await window.fetch(route('admin.academics.programmes.courses.destroy', {
            programme: selectedProgrammeForCourses.value.id,
            course: courseId
        }), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
        });
        const data = await res.json();
        if (res.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Removed',
                text: data.message || 'Course removed successfully',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            await fetchProgrammeCourses(selectedProgrammeForCourses.value.id);
        } else {
            Swal.fire('Error', data.message || 'Failed to remove course', 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed to remove course', 'error');
    }
};
</script>

<template>
    <Head title="Academic Structure" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Academic Structure</h2>
                    <p class="text-muted-foreground">Manage Faculties, Departments, Programmes and Courses.</p>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative">
                     <Input v-model="filterForm.search" placeholder="Search by name or code..." class="pl-8" />
                     <span class="absolute left-2.5 top-2.5 text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                     </span>
                </div>
                <!-- Faculty Filter - Hidden on Faculties tab, visible on others -->
                <div v-if="filterForm.tab !== 'faculties'">
                     <Select v-model="filterForm.faculty_id" @update:model-value="filterForm.department_id = ''">
                        <SelectTrigger>
                            <SelectValue placeholder="All Faculties" />
                        </SelectTrigger>
                        <SelectContent>
                             <SelectItem value="ALL">All Faculties</SelectItem>
                             <SelectItem value="NON_ACADEMIC">Non-Academic Departments</SelectItem>
                             <SelectItem v-for="fac in allFaculties" :key="fac.id" :value="fac.id">
                                {{ fac.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                 <!-- Department Filter - Visible on Programmes and Courses tabs -->
                 <div v-if="['programmes', 'courses'].includes(filterForm.tab)">
                     <Select v-model="filterForm.department_id">
                        <SelectTrigger>
                            <SelectValue placeholder="All Departments" />
                        </SelectTrigger>
                        <SelectContent>
                             <SelectItem value="ALL">All Departments</SelectItem>
                             <SelectItem v-for="dept in filterDepartments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Tabs v-model="filterForm.tab" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="faculties">Faculties</TabsTrigger>
                    <TabsTrigger value="departments">Departments</TabsTrigger>
                    <TabsTrigger value="programmes">Programmes</TabsTrigger>
                    <TabsTrigger value="courses">Courses</TabsTrigger>
                    <TabsTrigger value="units">Units</TabsTrigger>
                </TabsList>

                <TabsContent value="faculties" class="space-y-4">
                    <FacultiesTab 
                        :faculties="faculties" 
                        @create="openCreate('faculty')" 
                        @edit="(item) => openEdit('faculty', item)"
                        @toggle="(id, state) => toggleActive('faculty', id, state)"
                    />
                </TabsContent>

                <TabsContent value="departments" class="space-y-4">
                    <DepartmentsTab 
                        :departments="departments" 
                        @create="openCreate('department')" 
                        @edit="(item) => openEdit('department', item)"
                         @toggle="(id, state) => toggleActive('department', id, state)"
                    />
                </TabsContent>

                <TabsContent value="programmes" class="space-y-4">
                    <ProgrammesTab 
                        :programmes="programmes" 
                        @create="openCreate('programme')" 
                        @edit="(item) => openEdit('programme', item)"
                        @toggle="(id, state) => toggleActive('programme', id, state)"
                        @manage-courses="openManageProgrammeCourses"
                    />
                </TabsContent>

                <TabsContent value="courses" class="space-y-4">
                    <CoursesTab 
                        :courses="courses" 
                        @create="openCreate('course')" 
                        @edit="(item) => openEdit('course', item)"
                         @toggle="(id, state) => toggleActive('course', id, state)"
                    />
                </TabsContent>

                <TabsContent value="units" class="space-y-4">
                    <UnitsTab 
                        :units="units" 
                        @create="openCreate('unit')" 
                        @edit="(item) => openEdit('unit', item)"
                         @toggle="(id, state) => toggleActive('unit', id, state)"
                    />
                </TabsContent>
            </Tabs>

             <!-- MODAL DIALOG -->
            <Dialog v-model:open="isModalOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{ modalMode === 'create' ? 'Create' : 'Edit' }} {{ activeType }}</DialogTitle>
                        <DialogDescription>
                            {{ modalMode === 'create' ? 'Add a new ' + activeType : 'Update existing ' + activeType }}. Click save when you're done.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <!-- FACULTY FORM -->
                        <div v-if="activeType === 'faculty'" class="space-y-3">
                            <div class="space-y-1">
                                <Label>Faculty Name</Label>
                                <Input v-model="form.name" placeholder="Faculty of Sciences" />
                            </div>
                            <div class="space-y-1">
                                <Label>Code</Label>
                                <Input v-model="form.code" placeholder="SCI" />
                            </div>
                        </div>

                         <!-- DEPARTMENT FORM -->
                        <div v-if="activeType === 'department'" class="space-y-3">
                            <div class="space-y-1">
                                <Label>Department Name</Label>
                                <Input v-model="form.name" placeholder="Computer Science" />
                            </div>
                             <div class="space-y-1">
                                <Label>Code</Label>
                                <Input v-model="form.code" placeholder="CSC" />
                            </div>
                            <div class="space-y-1">
                                <Label>Faculty</Label>
                                <Select :model-value="form.faculty_id || 'none'" @update:model-value="handleFacultyChange">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Faculty (Optional for non-academic)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None (Non-Academic)</SelectItem>
                                        <SelectItem v-for="fac in allFaculties" :key="fac.id" :value="fac.id">
                                            {{ fac.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex items-center space-x-2 pt-2">
                                <Switch :checked="form.is_academic" @update:checked="(val) => form.is_academic = val" id="is_academic_dept" />
                                <Label for="is_academic_dept" class="cursor-pointer font-medium">
                                    {{ form.is_academic ? 'Academic Department' : 'Non-Academic Department' }}
                                </Label>
                            </div>
                        </div>

                         <!-- UNIT FORM -->
                        <div v-if="activeType === 'unit'" class="space-y-3">
                            <div class="space-y-1">
                                <Label>Unit Name</Label>
                                <Input v-model="form.name" placeholder="Payroll Unit" />
                            </div>
                             <div class="space-y-1">
                                <Label>Code</Label>
                                <Input v-model="form.code" placeholder="PAY" />
                            </div>
                            <div class="space-y-1">
                                <Label>Department</Label>
                                <Select v-model="form.department_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dept in allDepartments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- PROGRAMME FORM -->
                        <div v-if="activeType === 'programme'" class="space-y-3">
                             <div class="space-y-1">
                                <Label>Programme Name</Label>
                                <Input v-model="form.name" placeholder="B.Sc Computer Science" />
                            </div>
                            <div class="space-y-1">
                                <Label>Type</Label>
                                <Select v-model="form.program_type"> <!-- Corrected v-model -->
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="UG">Undergraduate (UG)</SelectItem>
                                        <SelectItem value="PG">Postgraduate (PG)</SelectItem>
                                        <SelectItem value="PHD">PhD</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-1">
                                <Label>Faculty</Label>
                                <Select v-model="form.faculty_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Faculty" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="fac in allFaculties" :key="fac.id" :value="fac.id">
                                            {{ fac.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-1">
                                <Label>Department</Label>
                                 <Select v-model="form.department_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex items-center space-x-2 pt-2">
                                <Switch :checked="form.scholarship_eligible" @update:checked="(val) => form.scholarship_eligible = val" id="scholarship_eligible" />
                                <Label for="scholarship_eligible" class="cursor-pointer font-medium">
                                    Eligible for Scholarships
                                </Label>
                            </div>
                        </div>

                         <!-- COURSE FORM -->
                        <div v-if="activeType === 'course'" class="space-y-3">
                            <div class="space-y-1">
                                <Label>Course Title</Label>
                                <Input v-model="form.title" placeholder="Introduction to Computing" />
                            </div>
                             <div class="space-y-1">
                                <Label>Course Code</Label>
                                <Input v-model="form.code" placeholder="CSC 101" />
                            </div>
                             <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <Label>Units</Label>
                                    <Input type="number" v-model="form.units" min="1" max="6" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Level</Label>
                                    <Select v-model="form.level">
                                        <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="100">100</SelectItem>
                                            <SelectItem value="200">200</SelectItem>
                                            <SelectItem value="300">300</SelectItem>
                                            <SelectItem value="400">400</SelectItem>
                                            <SelectItem value="500">500</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                             </div>
                             <div class="grid grid-cols-2 gap-4">
                                  <div class="space-y-1">
                                    <Label>Semester</Label>
                                     <Select v-model="form.semester">
                                        <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">First</SelectItem>
                                            <SelectItem value="2">Second</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                 <div class="space-y-1">
                                    <Label>Faculty</Label>
                                    <Select v-model="form.faculty_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Faculty" class="truncate" />
                                        </SelectTrigger>
                                        <SelectContent>
                                             <SelectItem v-for="fac in allFaculties" :key="fac.id" :value="fac.id">
                                                {{ fac.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                             </div>
                             
                             <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <Label>Department</Label>
                                     <Select v-model="form.department_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Dept" class="truncate" />
                                        </SelectTrigger>
                                        <SelectContent>
                                             <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                                {{ dept.code }} - {{ dept.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1">
                                    <Label>Programme</Label>
                                     <Select v-model="form.programme_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Programme" class="truncate" />
                                        </SelectTrigger>
                                        <SelectContent>
                                             <SelectItem v-for="prog in filteredProgrammes" :key="prog.id" :value="prog.id">
                                                {{ prog.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                             </div>
                        </div>

                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isModalOpen = false">Cancel</Button>
                        <Button @click="submitForm" :disabled="form.processing">Save changes</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- PROGRAMME COURSES MANAGEMENT DIALOG -->
            <Dialog v-model:open="isProgrammeCoursesOpen">
                <DialogContent 
                    @pointer-down-outside.prevent
                    class="sm:max-w-[95vw] md:max-w-[90vw] lg:max-w-[1100px] xl:max-w-[1200px] max-h-[90vh] flex flex-col p-6 overflow-hidden bg-card/95 backdrop-blur-md border border-border/80 shadow-2xl rounded-2xl"
                >
                    <DialogHeader class="pb-4 border-b border-border/50">
                        <div class="flex items-center space-x-3">
                            <div class="p-2.5 bg-primary/10 text-primary rounded-xl">
                                <BookOpen class="h-6 w-6 animate-pulse" />
                            </div>
                            <div>
                                <DialogTitle class="text-xl font-bold tracking-tight text-foreground">
                                    {{ selectedProgrammeForCourses?.name || 'Programme Courses' }}
                                </DialogTitle>
                                <DialogDescription class="text-sm text-muted-foreground mt-0.5">
                                    Manage assigned courses and academic requirements for this programme.
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 flex-1 overflow-hidden py-4 min-h-0">
                        <!-- LEFT COLUMN: Assigned Courses List -->
                        <div class="lg:col-span-7 flex flex-col min-h-0 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold tracking-wide uppercase text-muted-foreground/80">Assigned Courses</h3>
                                    <Badge variant="secondary" class="font-semibold bg-muted hover:bg-muted text-muted-foreground text-xs py-0.5 px-2">
                                        {{ programmeCoursesList.length }} Assigned
                                    </Badge>
                                </div>
                                <div class="relative w-48 sm:w-64">
                                     <Input v-slot="input" v-model="assignedSearchQuery" placeholder="Search assigned courses..." class="h-8 text-xs pl-8 pr-3" />
                                     <span class="absolute left-2.5 top-2 text-muted-foreground/60">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                     </span>
                                </div>
                            </div>
                            
                            <div v-if="isFetchingCourses" class="flex flex-col items-center justify-center flex-1 py-12 space-y-3 border border-dashed rounded-xl bg-card">
                                <Loader2 class="h-8 w-8 text-primary animate-spin" />
                                <p class="text-sm text-muted-foreground animate-pulse">Loading programme courses...</p>
                            </div>

                            <div v-else-if="programmeCoursesList.length === 0" class="flex flex-col items-center justify-center flex-1 py-12 border border-dashed rounded-xl bg-card text-center px-4">
                                <div class="p-3 bg-muted rounded-full text-muted-foreground/60 mb-3">
                                    <BookOpen class="h-6 w-6" />
                                </div>
                                <p class="text-sm font-medium text-foreground">No courses assigned yet</p>
                                <p class="text-xs text-muted-foreground mt-1 max-w-[280px]">Choose a course or import them using the options on the right.</p>
                            </div>

                            <div v-else-if="filteredAssignedCourses.length === 0" class="flex flex-col items-center justify-center flex-1 py-12 border border-dashed rounded-xl bg-card text-center px-4">
                                <p class="text-sm font-medium text-foreground">No matching courses found</p>
                                <p class="text-xs text-muted-foreground mt-1">Try checking your search spelling or change the search query.</p>
                            </div>

                            <div v-else class="flex-1 overflow-y-auto border rounded-xl bg-card/50 min-h-0">
                                <Table>
                                    <TableHeader class="bg-muted/30 sticky top-0 z-10">
                                        <TableRow>
                                            <TableHead class="font-semibold text-xs text-muted-foreground/80 py-3">Code</TableHead>
                                            <TableHead class="font-semibold text-xs text-muted-foreground/80 py-3">Title</TableHead>
                                            <TableHead class="font-semibold text-xs text-muted-foreground/80 py-3">Units</TableHead>
                                            <TableHead class="font-semibold text-xs text-muted-foreground/80 py-3">Type</TableHead>
                                            <TableHead class="w-[80px] text-right py-3"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow 
                                            v-for="course in filteredAssignedCourses" 
                                            :key="course.id"
                                            class="hover:bg-muted/20 transition-colors duration-150"
                                        >
                                            <TableCell class="font-mono font-bold text-sm text-foreground/90 py-3.5">{{ course.code }}</TableCell>
                                            <TableCell class="font-medium text-sm text-foreground/90 py-3.5 max-w-[220px] truncate" :title="course.title">{{ course.title }}</TableCell>
                                            <TableCell class="text-sm text-muted-foreground py-3.5">{{ course.units }} Units</TableCell>
                                            <TableCell class="py-3.5">
                                                <span 
                                                    :class="[
                                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold select-none border',
                                                        course.is_compulsory 
                                                            ? 'bg-rose-500/10 text-rose-500 border-rose-500/20' 
                                                            : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                                    ]"
                                                >
                                                    {{ course.is_compulsory ? 'Core' : 'Elective' }}
                                                </span>
                                            </TableCell>
                                            <TableCell class="text-right py-3.5">
                                                <Button 
                                                    variant="ghost" 
                                                    size="icon" 
                                                    @click="removeProgrammeCourse(course.id)"
                                                    class="h-8 w-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-all"
                                                    title="Remove from programme"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Add / Import Tools -->
                        <div class="lg:col-span-5 flex flex-col space-y-6 min-h-0 overflow-y-auto pr-1">
                            <!-- Assign New Course Panel -->
                            <div class="p-5 bg-muted/40 border border-muted/80 rounded-xl space-y-4 shadow-sm">
                                <h3 class="text-sm font-semibold tracking-wide uppercase text-muted-foreground/80">Assign New Course</h3>
                                <form @submit.prevent="submitAddProgrammeCourse" class="space-y-4">
                                    <div class="space-y-1.5 w-full">
                                        <Label class="text-xs font-medium text-muted-foreground">Select Course</Label>
                                        <SearchableSelect 
                                            v-model="newProgrammeCourseForm.course_id" 
                                            :items="courseSearchItems" 
                                            placeholder="Search and select course..." 
                                            disable-portal 
                                        />
                                    </div>
                                    
                                    <div class="flex items-center justify-between h-10 px-4 bg-background border rounded-lg w-full select-none">
                                        <span class="text-sm font-medium text-foreground">Core / Compulsory Course</span>
                                        <Switch 
                                            :checked="newProgrammeCourseForm.is_compulsory" 
                                            @update:checked="(val) => newProgrammeCourseForm.is_compulsory = val" 
                                            id="is_compulsory" 
                                        />
                                    </div>

                                    <Button 
                                        type="submit" 
                                        :disabled="isSubmittingProgrammeCourse || !newProgrammeCourseForm.course_id" 
                                        class="w-full h-10 px-5 bg-gradient-to-r from-primary to-indigo-600 text-white font-medium hover:from-primary/90 hover:to-indigo-600/90 active:scale-95 transition-all duration-150 shadow-md flex items-center justify-center gap-2"
                                    >
                                        <Loader2 v-if="isSubmittingProgrammeCourse" class="h-4 w-4 animate-spin" />
                                        <Plus v-else class="h-4 w-4" />
                                        Add Course
                                    </Button>
                                </form>
                            </div>

                            <!-- Import Courses Panel -->
                            <div class="p-5 bg-muted/40 border border-muted/80 rounded-xl space-y-4 shadow-sm">
                                <h3 class="text-sm font-semibold tracking-wide uppercase text-muted-foreground/80">Import from Programme</h3>
                                <p class="text-xs text-muted-foreground leading-normal">
                                    Quickly copy and assign all courses from another programme. Duplicates will be bypassed automatically.
                                </p>
                                <form @submit.prevent="submitImportProgrammeCourses" class="space-y-4">
                                    <div class="space-y-1.5 w-full">
                                        <Label class="text-xs font-medium text-muted-foreground">Select Source Programme</Label>
                                        <SearchableSelect 
                                            v-model="importProgrammeId" 
                                            :items="importProgrammeSearchItems" 
                                            placeholder="Select programme to copy from..." 
                                            disable-portal 
                                        />
                                    </div>

                                    <Button 
                                        type="submit" 
                                        :disabled="isImportingCourses || !importProgrammeId" 
                                        class="w-full h-10 px-5 bg-gradient-to-r from-indigo-500 to-indigo-700 text-white font-medium hover:from-indigo-500/90 hover:to-indigo-700/90 active:scale-95 transition-all duration-150 shadow-md flex items-center justify-center gap-2"
                                    >
                                        <Loader2 v-if="isImportingCourses" class="h-4 w-4 animate-spin" />
                                        <Plus v-else class="h-4 w-4" />
                                        Import All Courses
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="pt-4 border-t border-border/50">
                        <Button variant="outline" @click="isProgrammeCoursesOpen = false" class="border-border/80 hover:bg-muted">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
