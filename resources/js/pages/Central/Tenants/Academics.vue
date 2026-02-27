<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { 
    ArrowLeft,
    Building2,
    GraduationCap,
    BookOpen,
    PlusCircle,
    Upload
} from 'lucide-vue-next';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps<{
    tenant: any;
    academics: any[]; // Faculty[] -> Departments[] -> Programmes[]
    flash?: { success?: string, error?: string };
}>();

// Modals State
const showFacultyModal = ref(false);
const showDeptModal = ref(false);
const showProgModal = ref(false);
const showUploadModal = ref(false);

const activeFacultyId = ref('');
const activeDepartmentId = ref('');

// Forms
const facultyForm = useForm({
    name: '',
    code: '',
});

const deptForm = useForm({
    faculty_id: '',
    name: '',
    code: '',
});

const progForm = useForm({
    department_id: '',
    name: '',
    duration: 1,
    award: 'ND'
});

const uploadForm = useForm({
    file: null as File | null,
});

const submitFaculty = () => {
    facultyForm.post(route('central.tenants.faculties.store', props.tenant.id), {
        onSuccess: () => {
            showFacultyModal.value = false;
            facultyForm.reset();
            Swal.fire('Success', 'Faculty added successfully!', 'success');
        }
    });
};

const submitDepartment = () => {
    deptForm.faculty_id = activeFacultyId.value;
    deptForm.post(route('central.tenants.departments.store', props.tenant.id), {
        onSuccess: () => {
            showDeptModal.value = false;
            deptForm.reset();
            Swal.fire('Success', 'Department added successfully!', 'success');
        }
    });
};

const submitProgramme = () => {
    progForm.department_id = activeDepartmentId.value;
    progForm.post(route('central.tenants.programmes.store', props.tenant.id), {
        onSuccess: () => {
            showProgModal.value = false;
            progForm.reset();
            Swal.fire('Success', 'Programme mapped successfully!', 'success');
        }
    });
};

const submitUpload = () => {
    uploadForm.post(route('central.tenants.academics.upload', props.tenant.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
            Swal.fire('Imported!', 'The academic structure has been successfully imported.', 'success');
        },
        onError: (errors) => {
            Swal.fire('Upload Failed', errors.file || 'An error occurred during import.', 'error');
        }
    });
};

const openDeptModal = (facultyId: string) => {
    activeFacultyId.value = facultyId;
    showDeptModal.value = true;
};

const openProgModal = (deptId: string) => {
    activeDepartmentId.value = deptId;
    showProgModal.value = true;
};
</script>

<template>
    <Head :title="`${tenant.school_name || tenant.id} - Academics`" />

    <CentralLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('central.tenants.show', tenant.id)" class="p-2 border rounded-md hover:bg-slate-50 transition">
                        <ArrowLeft class="w-4 h-4 text-slate-600" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                            {{ tenant.school_name || tenant.id }} <span class="text-slate-400 font-normal">Academic Structure</span>
                        </h2>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="showUploadModal = true">
                        <Upload class="mr-2 h-4 w-4"/> Bulk Upload
                    </Button>
                    <Button @click="showFacultyModal = true">
                        <PlusCircle class="mr-2 h-4 w-4"/> Add Faculty
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Alert -->
                <div v-if="flash?.success" class="p-4 rounded-lg bg-green-50 border border-green-200 text-green-700">
                    {{ flash.success }}
                </div>

                <!-- Empty State -->
                <div v-if="academics.length === 0" class="bg-white p-12 text-center rounded-xl border border-dashed border-slate-300 shadow-sm">
                    <Building2 class="h-12 w-12 text-slate-300 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-slate-900">No Faculties Defined</h3>
                    <p class="text-slate-500 mt-1 max-w-sm mx-auto">This polytechnic currently has no academic structure. Add the first faculty to get started.</p>
                </div>

                <!-- Hierarchy Grid -->
                <div v-else class="space-y-6">
                    <div v-for="faculty in academics" :key="faculty.id" class="bg-white border rounded-xl shadow-sm overflow-hidden">
                        
                        <!-- Faculty Header -->
                        <div class="bg-slate-50 border-b px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-100 p-2 rounded-lg text-indigo-700">
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-slate-900">{{ faculty.name }}</h3>
                                    <p class="text-sm text-slate-500 font-mono">CODE: {{ faculty.code }}</p>
                                </div>
                            </div>
                            <Button variant="outline" size="sm" @click="openDeptModal(faculty.id)">
                                <PlusCircle class="mr-2 h-4 w-4"/> Add Department
                            </Button>
                        </div>

                        <!-- Departments List -->
                        <div class="p-6">
                            <div v-if="faculty.departments.length === 0" class="text-center py-6 text-slate-500 text-sm border border-dashed rounded-lg bg-slate-50">
                                No departments registered in this faculty.
                            </div>
                            
                            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div v-for="dept in faculty.departments" :key="dept.id" class="border rounded-lg p-5 shadow-sm hover:border-slate-300 transition relative group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center gap-2">
                                            <GraduationCap class="h-4 w-4 text-slate-400" />
                                            <h4 class="font-semibold text-slate-900">{{ dept.name }}</h4>
                                        </div>
                                        <Badge variant="secondary" class="font-mono text-xs">{{ dept.code }}</Badge>
                                    </div>

                                    <!-- Programmes inside Department -->
                                    <div class="space-y-2 mb-4">
                                        <div v-for="prog in dept.programmes" :key="prog.id" class="flex items-center justify-between bg-slate-50 px-3 py-2 rounded-md text-sm">
                                            <div class="flex items-center gap-2">
                                                <BookOpen class="h-3 w-3 text-slate-400" />
                                                <span class="font-medium text-slate-700">{{ prog.name }}</span>
                                            </div>
                                            <div class="flex gap-2">
                                                <Badge variant="outline" class="text-[10px]">{{ prog.award }}</Badge>
                                                <Badge variant="outline" class="text-[10px]">{{ prog.duration }} Yrs</Badge>
                                            </div>
                                        </div>
                                        <div v-if="dept.programmes.length === 0" class="text-xs text-slate-400 italic">No programmes mapped.</div>
                                    </div>

                                    <!-- Add Prog Button -->
                                    <Button variant="ghost" size="sm" class="w-full text-xs h-8 border border-dashed border-slate-200" @click="openProgModal(dept.id)">
                                        <PlusCircle class="mr-2 h-3 w-3" /> Insert Programme
                                    </Button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Dialogs -->
        <!-- Add Faculty Modal -->
        <Dialog :open="showFacultyModal" @update:open="showFacultyModal = false">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Faculty</DialogTitle>
                    <DialogDescription>Create a new top-level academic faculty for this polytechnic.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label>Faculty Name</Label>
                        <Input v-model="facultyForm.name" placeholder="e.g. Faculty of Engineering" />
                        <span v-if="facultyForm.errors.name" class="text-xs text-red-500">{{ facultyForm.errors.name }}</span>
                    </div>
                    <div class="space-y-2">
                        <Label>Short Code</Label>
                        <Input v-model="facultyForm.code" placeholder="e.g. FENG" />
                        <span v-if="facultyForm.errors.code" class="text-xs text-red-500">{{ facultyForm.errors.code }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showFacultyModal = false">Cancel</Button>
                    <Button :disabled="facultyForm.processing" @click="submitFaculty">Save Faculty</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Department Modal -->
        <Dialog :open="showDeptModal" @update:open="showDeptModal = false">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Department</DialogTitle>
                    <DialogDescription>Attach a new department to the selected faculty.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label>Department Name</Label>
                        <Input v-model="deptForm.name" placeholder="e.g. Computer Science" />
                        <span v-if="deptForm.errors.name" class="text-xs text-red-500">{{ deptForm.errors.name }}</span>
                    </div>
                    <div class="space-y-2">
                        <Label>Short Code</Label>
                        <Input v-model="deptForm.code" placeholder="e.g. CSC" />
                        <span v-if="deptForm.errors.code" class="text-xs text-red-500">{{ deptForm.errors.code }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showDeptModal = false">Cancel</Button>
                    <Button :disabled="deptForm.processing" @click="submitDepartment">Save Department</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Add Programme Modal -->
        <Dialog :open="showProgModal" @update:open="showProgModal = false">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Academic Programme</DialogTitle>
                    <DialogDescription>Define a specific course of study offered by this department.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label>Programme Title</Label>
                        <Input v-model="progForm.name" placeholder="e.g. Software Engineering" />
                        <span v-if="progForm.errors.name" class="text-xs text-red-500">{{ progForm.errors.name }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Award / Degree</Label>
                            <select v-model="progForm.award" class="w-full border-slate-200 rounded-md text-sm px-3 py-2">
                                <option value="ND">National Diploma (ND)</option>
                                <option value="HND">Higher National Diploma (HND)</option>
                                <option value="BSc">Bachelor of Science (BSc)</option>
                                <option value="BA">Bachelor of Arts (BA)</option>
                                <option value="MSc">Master of Science (MSc)</option>
                                <option value="PhD">Doctor of Philosophy (PhD)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>Duration (Years)</Label>
                            <Input type="number" v-model="progForm.duration" min="1" max="10" />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showProgModal = false">Cancel</Button>
                    <Button :disabled="progForm.processing" @click="submitProgramme">Save Programme</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Bulk Upload Modal -->
        <Dialog :open="showUploadModal" @update:open="showUploadModal = false">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Bulk Academic Upload</DialogTitle>
                    <DialogDescription>
                        Upload a CSV or Excel file to provision faculties, departments, and programmes at once.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4 py-4">
                    <div class="bg-slate-50 border rounded-lg p-4 mb-4">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Required CSV Format:</h4>
                        <div class="text-[10px] font-mono text-slate-600 bg-white p-2 border rounded overflow-x-auto">
                            faculty_name,faculty_code,department_name,department_code,programme_name,award,duration<br/>
                            Science,SCI,Computer Science,CSC,Software Engineering,ND,2
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Select File (CSV/XLSX)</Label>
                        <Input type="file" @input="uploadForm.file = ($event.target as HTMLInputElement).files?.[0] || null" accept=".csv,.xlsx,.xls" />
                        <span v-if="uploadForm.errors.file" class="text-xs text-red-500">{{ uploadForm.errors.file }}</span>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showUploadModal = false">Cancel</Button>
                    <Button :disabled="uploadForm.processing" @click="submitUpload">
                        {{ uploadForm.processing ? 'Uploading...' : 'Start Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </CentralLayout>
</template>
