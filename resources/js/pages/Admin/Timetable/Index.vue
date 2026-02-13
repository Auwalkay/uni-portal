<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { 
    CalendarRange, Plus, Trash2, Filter, Save, X, Search, Upload, Download 
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
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import Swal from 'sweetalert2';

const props = defineProps<{
    timetables: any[];
    sessions: any[];
    semesters: any[];
    departments: any[];
    courses: any[];
    filters: any;
    currentSession: any;
}>();

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

const applyFilters = () => {
    router.get(route('admin.timetables.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(filterForm, () => {
    // Optional: Auto-apply filters on change or use a button
}, { deep: true });

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
                title: 'Entry Added',
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
        title: 'Delete Entry?',
        text: "This will remove this class from the schedule.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.timetables.destroy', id));
        }
    });
};

const isImportOpen = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const submitImport = () => {
    importForm.post(route('admin.timetables.import'), {
        onSuccess: () => {
            isImportOpen.value = false;
            importForm.reset();
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Timetable Imported',
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                text: 'Please check the file and try again.',
            });
        }
    });
};

const downloadTemplate = () => {
    window.location.href = route('admin.timetables.template');
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Timetable Management', href: '/admin/timetables' },
];
</script>

<template>
    <Head title="Timetable Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">

            <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Timetable Management</h1>
                    <p class="text-muted-foreground">Manage class schedules for departments and levels.</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="isImportOpen = true">
                        <Upload class="w-4 h-4 mr-2" /> Import
                    </Button>
                    <Button @click="isCreateOpen = true" class="bg-indigo-600 hover:bg-indigo-700">
                        <Plus class="w-4 h-4 mr-2" /> Add Class
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border shadow-sm grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div class="space-y-1">
                    <Label>Session</Label>
                    <Select v-model="filterForm.session_id">
                         <SelectTrigger><SelectValue placeholder="Select Session" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                 <div class="space-y-1">
                    <Label>Semester</Label>
                    <Select v-model="filterForm.semester_id">
                         <SelectTrigger><SelectValue placeholder="Any Semester" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Semesters</SelectItem>
                             <SelectItem v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                 <div class="space-y-1">
                    <Label>Department</Label>
                    <Select v-model="filterForm.department_id">
                         <SelectTrigger><SelectValue placeholder="Any Department" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Departments</SelectItem>
                             <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                <div class="space-y-1">
                    <Label>Level</Label>
                    <Select v-model="filterForm.level">
                         <SelectTrigger><SelectValue placeholder="Any Level" /></SelectTrigger>
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
                <Button variant="secondary" @click="applyFilters">
                    <Filter class="w-4 h-4 mr-2" /> Filter
                </Button>
            </div>

            <!-- Timetable List -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Day</TableHead>
                            <TableHead>Time</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Venue</TableHead>
                            <TableHead>Department / Level</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="entry in timetables" :key="entry.id">
                            <TableCell class="font-medium">{{ entry.day }}</TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ entry.start_time.substring(0,5) }} - {{ entry.end_time.substring(0,5) }}</Badge>
                            </TableCell>
                            <TableCell>
                                <div class="font-bold text-xs font-mono">{{ entry.course.code }}</div>
                                <div class="text-xs text-muted-foreground">{{ entry.course.title }}</div>
                            </TableCell>
                            <TableCell>{{ entry.venue || 'TBA' }}</TableCell>
                            <TableCell>
                                <div class="text-xs">{{ entry.department.name }}</div>
                                <div class="text-[10px] text-muted-foreground">{{ entry.level }} Level</div>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteEntry(entry.id)">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="timetables.length === 0">
                            <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                No entries found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Create Modal -->
            <Dialog v-model:open="isCreateOpen">
                <DialogContent class="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Add Timetable Entry</DialogTitle>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>Session</Label>
                                <Select v-model="form.session_id">
                                     <SelectTrigger :class="{'border-red-500': form.errors.session_id}"><SelectValue placeholder="Session" /></SelectTrigger>
                                     <SelectContent>
                                         <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                     </SelectContent>
                                </Select>
                                <p v-if="form.errors.session_id" class="text-xs text-red-500">{{ form.errors.session_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Select Semester</Label>
                                <Select v-model="form.semester_id">
                                     <SelectTrigger :class="{'border-red-500': form.errors.semester_id}"><SelectValue placeholder="Semester" /></SelectTrigger>
                                     <SelectContent>
                                         <SelectItem v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                     </SelectContent>
                                </Select>
                                <p v-if="form.errors.semester_id" class="text-xs text-red-500">{{ form.errors.semester_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>Department</Label>
                                <Select v-model="form.department_id">
                                     <SelectTrigger :class="{'border-red-500': form.errors.department_id}"><SelectValue placeholder="Department" /></SelectTrigger>
                                     <SelectContent>
                                         <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                                     </SelectContent>
                                </Select>
                                <p v-if="form.errors.department_id" class="text-xs text-red-500">{{ form.errors.department_id }}</p>
                            </div>
                             <div class="space-y-2">
                                <Label>Level</Label>
                                <Select v-model="form.level">
                                     <SelectTrigger :class="{'border-red-500': form.errors.level}"><SelectValue placeholder="Level" /></SelectTrigger>
                                     <SelectContent>
                                         <SelectItem value="100">100 Level</SelectItem>
                                         <SelectItem value="200">200 Level</SelectItem>
                                         <SelectItem value="300">300 Level</SelectItem>
                                         <SelectItem value="400">400 Level</SelectItem>
                                         <SelectItem value="500">500 Level</SelectItem>
                                     </SelectContent>
                                </Select>
                                <p v-if="form.errors.level" class="text-xs text-red-500">{{ form.errors.level }}</p>
                            </div>
                        </div>

                         <div class="grid gap-4">
                            <div class="space-y-2">
                                <Label>Course</Label>
                                <Select v-model="form.course_id">
                                     <SelectTrigger :class="{'border-red-500': form.errors.course_id}"><SelectValue placeholder="Select Course" /></SelectTrigger>
                                     <SelectContent>
                                         <!-- Really should filter courses by department/level, but showing all for simplicity/speed or use a combobox -->
                                         <SelectItem v-for="c in courses" :key="c.id" :value="String(c.id)">{{ c.code }} - {{ c.title }}</SelectItem>
                                     </SelectContent>
                                </Select>
                                <p v-if="form.errors.course_id" class="text-xs text-red-500">{{ form.errors.course_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                             <div class="space-y-2">
                                <Label>Day</Label>
                                <Select v-model="form.day">
                                     <SelectTrigger :class="{'border-red-500': form.errors.day}"><SelectValue placeholder="Day" /></SelectTrigger>
                                     <SelectContent>
                                         <SelectItem value="Monday">Monday</SelectItem>
                                         <SelectItem value="Tuesday">Tuesday</SelectItem>
                                         <SelectItem value="Wednesday">Wednesday</SelectItem>
                                         <SelectItem value="Thursday">Thursday</SelectItem>
                                         <SelectItem value="Friday">Friday</SelectItem>
                                         <SelectItem value="Saturday">Saturday</SelectItem>
                                     </SelectContent>
                                </Select>
                                <p v-if="form.errors.day" class="text-xs text-red-500">{{ form.errors.day }}</p>
                            </div>
                             <div class="space-y-2">
                                <Label>Start Time</Label>
                                <Input type="time" v-model="form.start_time" :class="{'border-red-500': form.errors.start_time}" />
                                <p v-if="form.errors.start_time" class="text-xs text-red-500">{{ form.errors.start_time }}</p>
                            </div>
                             <div class="space-y-2">
                                <Label>End Time</Label>
                                <Input type="time" v-model="form.end_time" :class="{'border-red-500': form.errors.end_time}" />
                                <p v-if="form.errors.end_time" class="text-xs text-red-500">{{ form.errors.end_time }}</p>
                            </div>
                        </div>

                         <div class="space-y-2">
                            <Label>Venue</Label>
                            <Input v-model="form.venue" placeholder="e.g. Lecture Hall 1" :class="{'border-red-500': form.errors.venue}" />
                            <p v-if="form.errors.venue" class="text-xs text-red-500">{{ form.errors.venue }}</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isCreateOpen = false">Cancel</Button>
                        <Button @click="submit" :disabled="form.processing">Save Entry</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Import Modal -->
            <Dialog v-model:open="isImportOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import Timetable</DialogTitle>
                    </DialogHeader>
                    
                    <div class="space-y-4 py-4">
                        <div class="p-4 bg-muted/50 rounded-lg flex items-center justify-between">
                            <div class="text-sm">
                                <p class="font-medium">CSV Template</p>
                                <p class="text-muted-foreground">Download format guide</p>
                            </div>
                            <Button variant="outline" size="sm" @click="downloadTemplate">
                                <Download class="w-4 h-4 mr-2" /> Download
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <Label>Upload File (CSV/Excel)</Label>
                            <Input 
                                type="file" 
                                accept=".csv,.xlsx,.xls"
                                @change="(e: any) => importForm.file = e.target.files[0]"
                            />
                            <p v-if="importForm.errors.file" class="text-xs text-red-500">{{ importForm.errors.file }}</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isImportOpen = false">Cancel</Button>
                        <Button @click="submitImport" :disabled="importForm.processing">
                            <Upload class="w-4 h-4 mr-2" /> Import Data
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>


    </AdminLayout>

</template>
