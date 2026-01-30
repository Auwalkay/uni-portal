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
import { Plus, Pencil } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js'; 
import FacultiesTab from './Partials/FacultiesTab.vue';
import DepartmentsTab from './Partials/DepartmentsTab.vue';
import ProgrammesTab from './Partials/ProgrammesTab.vue';
import CoursesTab from './Partials/CoursesTab.vue';

const props = defineProps<{
    faculties: any[];
    departments: any; // Updated for pagination
    programmes: any; // Updated for pagination
    courses: any; 
    allFaculties: any[];
    allDepartments: any[];
    allProgrammes: any[];
    filters: any;
}>();

const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const activeType = ref<'faculty' | 'department' | 'programme' | 'course'>('faculty');

// Filter State
import { watch } from 'vue';
import { debounce } from 'lodash';

const filterForm = ref({
    search: props.filters?.search || '',
    faculty_id: props.filters?.faculty_id || '',
    department_id: props.filters?.department_id || '',
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


const openCreate = (type: 'faculty' | 'department' | 'programme' | 'course') => {
    // ... function content
    modalMode.value = 'create';
    activeType.value = type;
    form.reset();
    form.type = type;
    isModalOpen.value = true;
};

const openEdit = (type: 'faculty' | 'department' | 'programme' | 'course', item: any) => {
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
                <div>
                     <Select v-model="filterForm.faculty_id" @update:model-value="filterForm.department_id = ''">
                        <SelectTrigger>
                            <SelectValue placeholder="All Faculties" />
                        </SelectTrigger>
                        <SelectContent>
                             <SelectItem value="ALL">All Faculties</SelectItem>
                             <SelectItem v-for="fac in allFaculties" :key="fac.id" :value="fac.id">
                                {{ fac.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                 <div>
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

            <Tabs defaultValue="faculties" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="faculties">Faculties</TabsTrigger>
                    <TabsTrigger value="departments">Departments</TabsTrigger>
                    <TabsTrigger value="programmes">Programmes</TabsTrigger>
                    <TabsTrigger value="courses">Courses</TabsTrigger>
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

        </div>
    </AdminLayout>
</template>
