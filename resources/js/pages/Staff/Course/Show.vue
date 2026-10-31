<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BookOpen, Users, ArrowLeft, Download, Search, MoreHorizontal,
    Upload, FileSpreadsheet, AlertTriangle, CheckCircle2, X, Loader2
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle
} from '@/components/ui/dialog';
import { ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    course: { id: string; code: string; title: string; units: number };
    session: { id: string; name: string };
    students: Array<{
        id: string;
        student_name: string;
        matriculation_number: string;
        email: string;
        department: string;
        level: string;
        registered_at: string;
    }>;
    importSummary?: {
        enrolled: number;
        skipped: number;
        errors: Array<{ row: number; matric: string; reason: string }>;
    } | null;
}>();

const search = ref('');

const filteredStudents = computed(() => {
    if (!search.value) return props.students;
    const lower = search.value.toLowerCase().trim();
    return props.students.filter(s =>
        (s.student_name || '').toLowerCase().includes(lower) ||
        (s.matriculation_number || '').toLowerCase().includes(lower) ||
        (s.email || '').toLowerCase().includes(lower) ||
        (s.department || '').toLowerCase().includes(lower)
    );
});

const breadcrumbs = [
    { title: 'Teaching Schedule', href: route().has('admin.teaching.courses.index') ? route('admin.teaching.courses.index') : '/admin/dashboard' },
    { title: props.course.code, href: '#' }
];

// Upload dialog & state
const showUploadModal = ref(false);
const showResultsModal = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const form = useForm({
    file: null as File | null,
    skip_existing: true,
});

watch(() => props.importSummary, (newVal) => {
    if (newVal) {
        showResultsModal.value = true;
    }
}, { immediate: true });

const onFileSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFile.value = target.files[0];
        form.file = target.files[0];
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const removeFile = () => {
    selectedFile.value = null;
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitUpload = () => {
    if (!form.file) return;

    form.post(route('admin.teaching.courses.students.upload', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            removeFile();
            if (props.importSummary && (props.importSummary.enrolled > 0 || props.importSummary.errors.length > 0)) {
                showResultsModal.value = true;
            }
        },
    });
};
</script>

<template>
    <Head :title="`${course.code} - Student List`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-[1600px] mx-auto">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" as-child>
                        <Link :href="route().has('admin.teaching.courses.index') ? route('admin.teaching.courses.index') : '/admin/dashboard'">
                            <ArrowLeft class="w-4 h-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ course.code }} - {{ course.title }}</h1>
                        <p class="text-muted-foreground flex items-center gap-2">
                            <span>{{ session.name }}</span>
                            <span>•</span>
                            <span>{{ course.units }} Units</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a :href="route('admin.teaching.courses.students.export', course.id)">
                        <Button variant="outline" class="gap-2">
                            <Download class="w-4 h-4" /> Export List
                        </Button>
                    </a>
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2" @click="showUploadModal = true">
                        <Upload class="w-4 h-4" /> Upload Students
                    </Button>
                </div>
            </div>

            <!-- Stats & Search -->
             <div class="grid gap-6 md:grid-cols-4">
                <Card class="md:col-span-1 bg-indigo-50 border-indigo-100 dark:bg-indigo-950/20 dark:border-indigo-900">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-indigo-800 dark:text-indigo-400">Total Registered</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ students.length }}</div>
                        <p class="text-xs text-indigo-600/80 mt-1">Students enrolled in this course</p>
                    </CardContent>
                </Card>

                 <Card class="md:col-span-3">
                    <CardHeader class="pb-3">
                         <div class="flex items-center justify-between">
                            <CardTitle>Registered Students</CardTitle>
                            <div class="relative w-64">
                                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input v-model="search" placeholder="Search by name, matric no, or dept..." class="pl-8" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                         <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[50px]">#</TableHead>
                                    <TableHead>Student Name</TableHead>
                                    <TableHead>Matric Number</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Registered On</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(student, index) in filteredStudents" :key="student.id">
                                    <TableCell class="text-muted-foreground font-mono text-xs">{{ index + 1 }}</TableCell>
                                    <TableCell class="font-medium">
                                        <div class="flex flex-col">
                                            <span>{{ student.student_name }}</span>
                                            <span class="text-xs text-muted-foreground">{{ student.email }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell><Badge variant="outline" class="font-mono">{{ student.matriculation_number }}</Badge></TableCell>
                                    <TableCell>{{ student.department }}</TableCell>
                                    <TableCell>{{ student.level }}</TableCell>
                                    <TableCell class="text-xs text-muted-foreground">
                                        {{ student.registered_at }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" class="h-8 w-8 p-0">
                                                    <span class="sr-only">Open menu</span>
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuItem disabled>View Results (Coming Soon)</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredStudents.length === 0">
                                    <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                        No students found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
             </div>
        </div>

        <!-- Upload Students Dialog Modal -->
        <Dialog v-model:open="showUploadModal">
            <DialogContent class="sm:max-w-[560px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg">
                        <FileSpreadsheet class="w-5 h-5 text-indigo-600" />
                        Upload Students for {{ course.code }}
                    </DialogTitle>
                    <DialogDescription>
                        Bulk enroll students to <strong>{{ course.code }} - {{ course.title }}</strong> for the <strong>{{ session.name }}</strong> session using an Excel or CSV file.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitUpload" class="space-y-5 pt-2">
                    <!-- Template Download Helper -->
                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-3 flex items-center justify-between">
                        <div class="text-xs text-indigo-950">
                            <span class="font-bold">Need the standard spreadsheet format?</span>
                            <p class="text-[11px] text-indigo-700">Includes required matriculation_number column.</p>
                        </div>
                        <a :href="route('admin.teaching.courses.students.template', course.id)" download>
                            <Button type="button" variant="outline" size="sm" class="h-8 text-xs border-indigo-200 text-indigo-700 hover:bg-indigo-100 gap-1.5 font-bold">
                                <Download class="w-3.5 h-3.5" /> Sample Template
                            </Button>
                        </a>
                    </div>

                    <!-- Drag & Drop Zone -->
                    <input 
                        ref="fileInput" 
                        type="file" 
                        accept=".xlsx,.xls,.csv" 
                        class="hidden" 
                        @change="onFileSelected" 
                    />

                    <div 
                        v-if="!selectedFile"
                        @click="triggerFileInput"
                        class="border-2 border-dashed border-slate-200 hover:border-indigo-400 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-slate-50/50 hover:bg-indigo-50/30 flex flex-col items-center justify-center gap-2"
                    >
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                            <Upload class="w-6 h-6" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-bold text-slate-700">Click to upload spreadsheet</p>
                            <p class="text-xs text-slate-400">Supports .xlsx, .xls, and .csv (Max 5MB)</p>
                        </div>
                    </div>

                    <!-- Selected File Card -->
                    <div v-else class="p-3 border border-slate-200 rounded-xl flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-green-100 text-green-700 flex items-center justify-center font-bold text-xs">
                                XLS
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 truncate max-w-[320px]">{{ selectedFile.name }}</p>
                                <p class="text-[10px] text-slate-400">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
                            </div>
                        </div>
                        <Button type="button" variant="ghost" size="sm" class="h-8 w-8 p-0 text-slate-400 hover:text-red-600" @click="removeFile">
                            <X class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- Option checkbox -->
                    <div class="flex items-center gap-2">
                        <input 
                            id="skip_existing" 
                            type="checkbox" 
                            v-model="form.skip_existing"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" 
                        />
                        <label for="skip_existing" class="text-xs font-medium text-slate-700 cursor-pointer select-none">
                            Skip students who are already enrolled in this course for this session
                        </label>
                    </div>

                    <div v-if="form.errors.file" class="text-xs text-red-600 font-bold">
                        {{ form.errors.file }}
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button type="button" variant="outline" @click="showUploadModal = false">
                            Cancel
                        </Button>
                        <Button 
                            type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold gap-2"
                            :disabled="!selectedFile || form.processing"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <Upload v-else class="w-4 h-4" />
                            Enroll Students
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Enrolment Results Summary Modal -->
        <Dialog v-model:open="showResultsModal">
            <DialogContent class="sm:max-w-[620px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg">
                        <CheckCircle2 class="w-5 h-5 text-green-600" />
                        Enrolment Upload Summary
                    </DialogTitle>
                    <DialogDescription>
                        Summary of processed student enrolments for {{ course.code }}.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="importSummary" class="space-y-4 pt-2">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 bg-green-50 border border-green-200 rounded-xl">
                            <div class="text-2xl font-black text-green-700">{{ importSummary.enrolled }}</div>
                            <div class="text-[11px] font-bold text-green-800 uppercase tracking-wider">Enrolled</div>
                        </div>
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <div class="text-2xl font-black text-amber-700">{{ importSummary.skipped }}</div>
                            <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Skipped / Duplicates</div>
                        </div>
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <div class="text-2xl font-black text-red-700">{{ importSummary.errors.length }}</div>
                            <div class="text-[11px] font-bold text-red-800 uppercase tracking-wider">Errors</div>
                        </div>
                    </div>

                    <!-- List of failures/errors if any -->
                    <div v-if="importSummary.errors.length > 0" class="space-y-2">
                        <p class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <AlertTriangle class="w-4 h-4 text-amber-600" /> Unprocessed Records ({{ importSummary.errors.length }})
                        </p>
                        <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-xl bg-slate-50 p-2 text-xs divide-y divide-slate-200">
                            <div v-for="(err, idx) in importSummary.errors" :key="idx" class="py-2 px-1 flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-bold text-slate-700">Row {{ err.row }}:</span>
                                    <span class="font-mono ml-1 text-slate-900 bg-slate-200 px-1 py-0.5 rounded">{{ err.matric }}</span>
                                    <p class="text-[11px] text-red-600 mt-0.5">{{ err.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white" @click="showResultsModal = false">
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
