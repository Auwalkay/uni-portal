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
import { route } from 'ziggy-js'; // Fixing route import

const props = defineProps<{
    faculties: any[];
    departments: any[];
    programmes: any[];
    courses: any; // Fixing type to any to avoid property access errors
}>();

const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const activeType = ref<'faculty' | 'department' | 'programme' | 'course'>('faculty');

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
    program_type: 'UG',
});

const openCreate = (type: 'faculty' | 'department' | 'programme' | 'course') => {
    modalMode.value = 'create';
    activeType.value = type;
    form.reset();
    form.type = type;
    isModalOpen.value = true;
};

const openEdit = (type: 'faculty' | 'department' | 'programme' | 'course', item: any) => {
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
    form.faculty_id = item.faculty_id || item.department?.faculty_id || '';
    form.department_id = item.department_id || '';
    form.program_type = item.type || 'UG';
    isModalOpen.value = true;
};

const submitForm = () => {
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

            <Tabs defaultValue="faculties" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="faculties">Faculties</TabsTrigger>
                    <TabsTrigger value="departments">Departments</TabsTrigger>
                    <TabsTrigger value="programmes">Programmes</TabsTrigger>
                    <TabsTrigger value="courses">Courses</TabsTrigger>
                </TabsList>

                <!-- FACULTIES TAB -->
                <TabsContent value="faculties" class="space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Faculties</CardTitle>
                                <CardDescription>Manage university faculties.</CardDescription>
                            </div>
                            <Button @click="openCreate('faculty')"><Plus class="mr-2 h-4 w-4" /> Add Faculty</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Departments</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="faculty in faculties" :key="faculty.id">
                                        <TableCell class="font-mono font-medium">{{ faculty.code }}</TableCell>
                                        <TableCell>{{ faculty.name }}</TableCell>
                                        <TableCell>{{ faculty.departments_count }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center space-x-2">
                                                <Switch :checked="faculty.is_active" @update:checked="toggleActive('faculty', faculty.id, faculty.is_active)" />
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" @click="openEdit('faculty', faculty)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- DEPARTMENTS TAB -->
                <TabsContent value="departments" class="space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Departments</CardTitle>
                                <CardDescription>Manage departments within faculties.</CardDescription>
                            </div>
                            <Button @click="openCreate('department')"><Plus class="mr-2 h-4 w-4" /> Add Department</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Faculty</TableHead>
                                        <TableHead>Programmes</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="dept in departments" :key="dept.id">
                                        <TableCell class="font-mono font-medium">{{ dept.code }}</TableCell>
                                        <TableCell>{{ dept.name }}</TableCell>
                                        <TableCell>{{ dept.faculty?.name }}</TableCell>
                                        <TableCell>{{ dept.programmes_count }}</TableCell>
                                        <TableCell>
                                             <Switch :checked="dept.is_active" @update:checked="toggleActive('department', dept.id, dept.is_active)" />
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" @click="openEdit('department', dept)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- PROGRAMMES TAB -->
                 <TabsContent value="programmes" class="space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                             <div>
                                <CardTitle>Programmes</CardTitle>
                                <CardDescription>Manage degree programmes.</CardDescription>
                            </div>
                            <Button @click="openCreate('programme')"><Plus class="mr-2 h-4 w-4" /> Add Programme</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Department</TableHead>
                                        <TableHead>Faculty</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="prog in programmes" :key="prog.id">
                                        <TableCell class="font-medium">{{ prog.name }}</TableCell>
                                        <TableCell><Badge variant="outline">{{ prog.type }}</Badge></TableCell>
                                        <TableCell>{{ prog.department?.name }}</TableCell>
                                        <TableCell>{{ prog.department?.faculty?.name }}</TableCell>
                                        <TableCell>
                                             <Switch :checked="prog.is_active" @update:checked="toggleActive('programme', prog.id, prog.is_active)" />
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" @click="openEdit('programme', prog)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- COURSES TAB -->
                 <TabsContent value="courses" class="space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Courses</CardTitle>
                                <CardDescription>Manage all courses.</CardDescription>
                            </div>
                             <Button @click="openCreate('course')"><Plus class="mr-2 h-4 w-4" /> Add Course</Button>
                        </CardHeader>
                        <CardContent>
                             <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Title</TableHead>
                                        <TableHead>Units</TableHead>
                                        <TableHead>Department</TableHead>
                                        <TableHead>Level</TableHead>
                                        <TableHead>Sem</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="course in courses.data" :key="course.id">
                                        <TableCell class="font-mono font-medium">{{ course.code }}</TableCell>
                                        <TableCell>{{ course.title }}</TableCell>
                                        <TableCell>{{ course.units }}</TableCell>
                                        <TableCell>{{ course.department?.name }}</TableCell>
                                         <TableCell>{{ course.level }}</TableCell>
                                          <TableCell>{{ course.semester }}</TableCell>
                                        <TableCell>
                                              <Switch :checked="course.is_active" @update:checked="toggleActive('course', course.id, course.is_active)" />
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" @click="openEdit('course', course)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                             <div class="flex justify-center mt-4 space-x-2" v-if="courses.links">
                                <Button 
                                    v-for="(link, i) in courses.links" 
                                    :key="i"
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url"
                                    size="sm"
                                    as-child
                                >
                                <a :href="link.url" v-html="link.label"></a>
                                </Button>
                             </div>
                        </CardContent>
                    </Card>
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
                                        <SelectItem v-for="fac in faculties" :key="fac.id" :value="fac.id">
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
                                <Label>Department</Label>
                                 <Select v-model="form.department_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
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
                                    <Label>Department</Label>
                                     <Select v-model="form.department_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Dept" class="truncate" />
                                        </SelectTrigger>
                                        <SelectContent>
                                             <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                                                {{ dept.code }} - {{ dept.name }}
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
