<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Upload, FileSpreadsheet, Save, ArrowLeft, Download, Users, BookOpen, Home, GraduationCap } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

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
    form.post(route('admin.results.update', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Saved', 'Results updated successfully', 'success');
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
    return (Number(s.ca_score) || 0) + (Number(s.exam_score) || 0);
};

const getGrade = (total: number) => {
    if (total >= 70) return { grade: 'A', color: 'bg-green-50 text-green-700 border-green-200' };
    if (total >= 60) return { grade: 'B', color: 'bg-blue-50 text-blue-700 border-blue-200' };
    if (total >= 50) return { grade: 'C', color: 'bg-yellow-50 text-yellow-700 border-yellow-200' };
    if (total >= 45) return { grade: 'D', color: 'bg-orange-50 text-orange-700 border-orange-200' };
    return { grade: 'F', color: 'bg-red-50 text-red-700 border-red-200' };
};

const stats = computed(() => {
    const total = form.scores.length;
    const graded = form.scores.filter(s => s.ca_score !== null || s.exam_score !== null).length;
    const avgTotal = form.scores.reduce((sum, s, idx) => sum + calculateTotal(idx), 0) / (total || 1);
    
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
    
    let csvContent = "data:text/csv;charset=utf-8," 
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
                                    <TableHead class="h-12 w-[140px]">CA (40)</TableHead>
                                    <TableHead class="h-12 w-[140px]">Exam (60)</TableHead>
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
                                        <Input 
                                            type="number" 
                                            v-model="score.ca_score" 
                                            min="0" max="40" step="0.5"
                                            class="w-28 font-medium"
                                            placeholder="0"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Input 
                                            type="number" 
                                            v-model="score.exam_score" 
                                            min="0" max="60" step="0.5"
                                            class="w-28 font-medium"
                                            placeholder="0"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-lg font-bold">{{ calculateTotal(index) }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge 
                                            variant="outline" 
                                            :class="getGrade(calculateTotal(index)).color"
                                            class="font-semibold"
                                        >
                                            {{ getGrade(calculateTotal(index)).grade }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                 <tr v-if="form.scores.length === 0">
                                    <td colspan="7" class="p-12 text-center text-muted-foreground">
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
