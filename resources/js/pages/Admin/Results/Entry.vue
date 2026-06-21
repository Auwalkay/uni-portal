<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Upload, FileSpreadsheet, Save, ArrowLeft, Download, Users, BookOpen, Home, GraduationCap, Printer } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

import { Badge } from '@/components/ui/badge';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps<{
    course: any;
    session: any;
    registrations: any[];
}>();

const form = useForm({
    scores: props.registrations.map(reg => ({
        id: reg.id,
        ca_score: reg.ca_score,
        exam_score: reg.exam_score,
        is_absent: !!reg.is_absent,
    })),
});

const isUploadOpen = ref(false);
const uploadForm = useForm({
    file: null as File | null,
    session_id: props.session.id,
});

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        uploadForm.file = target.files[0];
    }
};

const submitUpload = () => {
    if (!uploadForm.file) return;

    uploadForm.post(route('admin.results.upload', props.course.id), {
        onSuccess: () => {
            isUploadOpen.value = false;
            Swal.fire('Imported', 'Results uploaded successfully', 'success');
        },
        onError: () => {
            Swal.fire('Error', 'Failed to upload results. Check file format.', 'error');
        }
    });
};

const submit = () => {
    // Client-side validation: CA max 40, Exam max 80, Total max 100
    for (let i = 0; i < form.scores.length; i++) {
        const score = form.scores[i];
        if (score.is_absent) continue;
        const ca = Number(score.ca_score) || 0;
        const exam = Number(score.exam_score) || 0;
        if (ca < 0 || ca > 40) {
            Swal.fire('Validation Error', `CA score for student "${getStudentName(score.id)}" must be between 0 and 40.`, 'error');
            return;
        }
        if (exam < 0 || exam > 80) {
            Swal.fire('Validation Error', `Exam score for student "${getStudentName(score.id)}" must be between 0 and 80.`, 'error');
            return;
        }
        if (ca + exam > 100) {
            Swal.fire('Validation Error', `Total score (CA + Exam) for student "${getStudentName(score.id)}" cannot exceed 100 (currently ${ca + exam}).`, 'error');
            return;
        }
    }

    form.post(route('admin.results.update', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Saved', 'Results updated successfully', 'success');
        },
        onError: (errors) => {
            const errorList = Object.values(errors).map(err => `<li>${err}</li>`).join('');
            Swal.fire({
                icon: 'error',
                title: 'Failed to Save Results',
                html: `<div class="text-left text-sm text-rose-600 dark:text-rose-400 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20"><ul class="list-disc list-inside space-y-1">${errorList}</ul></div>`,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

const getStudentName = (regId: string) => {
    const reg = props.registrations.find(r => r.id === regId);
    return reg?.student?.user?.name || 'Unknown';
};

const getMatricNo = (regId: string) => {
    const reg = props.registrations.find(r => r.id === regId);
    return reg?.student?.matriculation_number || 'N/A';
};

const calculateTotal = (index: number) => {
    const s = form.scores[index];
    if (s.is_absent) return 0;
    return (Number(s.ca_score) || 0) + (Number(s.exam_score) || 0);
};

const getGrade = (total: number, isAbsent = false) => {
    if (isAbsent) return { grade: 'ABS', color: 'bg-rose-50 text-rose-700 border-rose-200' };
    if (total >= 70) return { grade: 'A', color: 'bg-green-50 text-green-700 border-green-200' };
    if (total >= 60) return { grade: 'B', color: 'bg-blue-50 text-blue-700 border-blue-200' };
    if (total >= 50) return { grade: 'C', color: 'bg-yellow-50 text-yellow-700 border-yellow-200' };
    if (total >= 45) return { grade: 'D', color: 'bg-orange-50 text-orange-700 border-orange-200' };
    return { grade: 'F', color: 'bg-red-50 text-red-700 border-red-200' };
};

const stats = computed(() => {
    const total = form.scores.length;
    const graded = form.scores.filter(s => s.is_absent || s.ca_score !== null || s.exam_score !== null).length;
    const gradedForAvg = form.scores.filter(s => !s.is_absent && (s.ca_score !== null || s.exam_score !== null));
    const avgTotal = gradedForAvg.length > 0 
        ? gradedForAvg.reduce((sum, s) => {
            const idx = form.scores.findIndex(x => x.id === s.id);
            return sum + calculateTotal(idx);
          }, 0) / gradedForAvg.length
        : 0;

    return {
        total,
        graded,
        pending: total - graded,
        average: avgTotal.toFixed(1)
    };
});

const downloadTemplate = () => {
    const headers = ['matric_number', 'ca', 'exam'];
    const rows = props.registrations.map(r => [r.student.matriculation_number, '', '']);

    const csvContent = "data:text/csv;charset=utf-8,"
        + headers.join(",") + "\n"
        + rows.map(e => e.join(",")).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `${props.course.code}_result_template.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Enter Results" />

    <AdminLayout>
        <div class="space-y-8 p-8">
            <!-- Breadcrumb -->
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link :href="route('dashboard')">
                                <Home class="h-4 w-4" />
                            </Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link :href="route('admin.results.index', { session_id: session.id })">
                                Results
                            </Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>{{ course.code }}</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b pb-6">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">Score Entry</h2>
                    <p class="text-lg text-muted-foreground mt-1">
                        <span class="font-mono font-semibold text-primary">{{ course.code }}</span> - {{ course.title }}
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <Badge variant="secondary" class="text-sm px-3 py-0.5">
                            {{ session.name }}
                        </Badge>
                        <Badge variant="outline" class="text-sm px-3 py-0.5">
                            {{ course.units }} Units
                        </Badge>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('admin.results.print', { course_id: course.id, session_id: session.id })" target="_blank">
                        <Button variant="outline" class="shadow-sm">
                            <Printer class="mr-2 h-4 w-4" /> Print Results
                        </Button>
                    </a>
                    <Button variant="outline" @click="isUploadOpen = true">
                        <Upload class="mr-2 h-4 w-4" /> Import Excel
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="shadow-sm">
                        <Save class="mr-2 h-4 w-4" /> Save Changes
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase">Total Students</p>
                                <p class="text-2xl font-bold">{{ stats.total }}</p>
                            </div>
                            <Users class="h-8 w-8 text-muted-foreground/50" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase">Graded</p>
                                <p class="text-2xl font-bold text-green-600">{{ stats.graded }}</p>
                            </div>
                            <GraduationCap class="h-8 w-8 text-green-600/50" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase">Pending</p>
                                <p class="text-2xl font-bold text-orange-600">{{ stats.pending }}</p>
                            </div>
                            <FileSpreadsheet class="h-8 w-8 text-orange-600/50" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase">Class Average</p>
                                <p class="text-2xl font-bold">{{ stats.average }}%</p>
                            </div>
                            <BookOpen class="h-8 w-8 text-muted-foreground/50" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Results Table -->
            <Card class="shadow-sm border-muted">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent border-b">
                                    <TableHead class="h-12 w-[50px]">#</TableHead>
                                    <TableHead class="h-12 w-[150px]">Matric No</TableHead>
                                    <TableHead class="h-12">Student Name</TableHead>
                                    <TableHead class="h-12 w-[80px]">Absent</TableHead>
                                    <TableHead class="h-12 w-[140px]">CA (40)</TableHead>
                                    <TableHead class="h-12 w-[140px]">Exam (80)</TableHead>
                                    <TableHead class="h-12 w-[120px]">Total (100)</TableHead>
                                    <TableHead class="h-12 w-[100px]">Grade</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(score, index) in form.scores" :key="score.id" class="hover:bg-muted/30 transition-colors">
                                    <TableCell class="text-muted-foreground">{{ index + 1 }}</TableCell>
                                    <TableCell class="font-mono font-medium text-primary">{{ getMatricNo(score.id) }}</TableCell>
                                    <TableCell class="font-medium">{{ getStudentName(score.id) }}</TableCell>
                                    <TableCell>
                                        <Checkbox 
                                            v-model:checked="score.is_absent" 
                                            @update:checked="(val) => { if (val) { score.ca_score = null; score.exam_score = null; } }" 
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            type="number"
                                            v-model="score.ca_score"
                                            min="0" max="40" step="0.5"
                                            class="w-28 font-medium transition-opacity"
                                            placeholder="0"
                                            :disabled="score.is_absent"
                                            :class="{'opacity-40 pointer-events-none': score.is_absent, 'border-rose-500 text-rose-600 focus-visible:ring-rose-500': !score.is_absent && (Number(score.ca_score) > 40 || Number(score.ca_score) < 0 || (Number(score.ca_score) || 0) + (Number(score.exam_score) || 0) > 100 || form.errors[`scores.${index}.ca_score`])}"
                                        />
                                        <p v-if="form.errors[`scores.${index}.ca_score`]" class="text-xs text-rose-600 mt-1 max-w-[120px] leading-tight">
                                            {{ form.errors[`scores.${index}.ca_score`] }}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            type="number"
                                            v-model="score.exam_score"
                                            min="0" max="80" step="0.5"
                                            class="w-28 font-medium transition-opacity"
                                            placeholder="0"
                                            :disabled="score.is_absent"
                                            :class="{'opacity-40 pointer-events-none': score.is_absent, 'border-rose-500 text-rose-600 focus-visible:ring-rose-500': !score.is_absent && (Number(score.exam_score) > 80 || Number(score.exam_score) < 0 || (Number(score.ca_score) || 0) + (Number(score.exam_score) || 0) > 100 || form.errors[`scores.${index}.exam_score`])}"
                                        />
                                        <p v-if="form.errors[`scores.${index}.exam_score`]" class="text-xs text-rose-600 mt-1 max-w-[120px] leading-tight">
                                            {{ form.errors[`scores.${index}.exam_score`] }}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-lg font-bold" :class="{'text-muted-foreground opacity-50': score.is_absent, 'text-rose-600': !score.is_absent && (Number(score.ca_score) > 40 || Number(score.exam_score) > 80 || calculateTotal(index) > 100)}">
                                            {{ score.is_absent ? 'ABS' : calculateTotal(index) }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant="outline"
                                            :class="getGrade(calculateTotal(index), score.is_absent).color"
                                            class="font-semibold"
                                        >
                                            {{ getGrade(calculateTotal(index), score.is_absent).grade }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                 <tr v-if="form.scores.length === 0">
                                    <td colspan="8" class="p-12 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="p-4 rounded-full bg-muted/50">
                                                <FileSpreadsheet class="h-8 w-8 text-muted-foreground/50" />
                                            </div>
                                            <div class="space-y-1">
                                                <p class="font-medium text-lg text-foreground">No students registered</p>
                                                <p class="text-sm">No students have registered for this course in this session yet.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Upload Dialog -->
            <Dialog v-model:open="isUploadOpen">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Import Results from Excel</DialogTitle>
                        <DialogDescription>
                            Upload an Excel or CSV file containing student scores.
                            Required columns: <code class="bg-muted px-1 py-0.5 rounded text-xs">matric_number</code>, <code class="bg-muted px-1 py-0.5 rounded text-xs">ca</code>, <code class="bg-muted px-1 py-0.5 rounded text-xs">exam</code>.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                         <div class="rounded-lg bg-muted/50 p-4 border">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-sm">Template File</span>
                                <Button variant="link" size="sm" class="h-auto p-0 text-primary" @click="downloadTemplate">
                                    <Download class="mr-1 h-3 w-3" /> Download CSV
                                </Button>
                            </div>
                            <p class="text-sm text-muted-foreground">Download the template pre-filled with registered students.</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="file">Select File</Label>
                            <Input id="file" type="file" accept=".xlsx,.xls,.csv" @change="handleFileChange" />
                            <p v-if="uploadForm.errors.file" class="text-sm text-destructive">{{ uploadForm.errors.file }}</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isUploadOpen = false">Cancel</Button>
                        <Button @click="submitUpload" :disabled="uploadForm.processing || !uploadForm.file">
                            {{ uploadForm.processing ? 'Uploading...' : 'Import Results' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
