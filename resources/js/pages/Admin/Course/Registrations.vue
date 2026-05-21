<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { throttle } from 'lodash';
import { BookOpen, Download, ChevronRight, Home } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';

import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps<{
    course: {
        id: string;
        title: string;
        code: string;
        units: number;
    };
    registrations: {
        data: Array<{
            id: string;
            created_at: string;
            session: { name: string };
            student: {
                id: string;
                matriculation_number: string;
                current_level: string;
                user: { name: string; email: string };
                department: { name: string };
                program: any;
            };
        }>;
        links: any[];
    };
    sessions: Array<{ id: string; name: string }>;
    departments: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string }>;
    programmes: Array<{ id: string; name: string }>;
    filters: {
        session_id: string;
        level: string;
        department_id: string;
        faculty_id: string;
        programme_id: string;
    };
}>();

const form = ref({
    session_id: props.filters.session_id || 'ALL',
    level: props.filters.level || 'ALL',
    department_id: props.filters.department_id || 'ALL',
    faculty_id: props.filters.faculty_id || 'ALL',
    programme_id: props.filters.programme_id || 'ALL',
});

// Watch for changes and update results
watch(form, throttle(() => {
    // Convert 'ALL' back to empty string for backend
    const filters = { ...form.value };
    (Object.keys(filters) as Array<keyof typeof filters>).forEach(key => {
        if (filters[key] === 'ALL') filters[key] = '';
    });

    router.get(route('admin.courses.registrations.index', props.course.id), filters, {
        preserveState: true,
        replace: true,
    });
}, 500), { deep: true });

const levels = ['100', '200', '300', '400', '500', '600'];

const exportRegistrations = () => {
    const filters = { ...form.value };
    (Object.keys(filters) as Array<keyof typeof filters>).forEach(key => {
        if (filters[key] === 'ALL') filters[key] = '';
    });

    const url = route('admin.courses.registrations.export', {
        course: props.course.id,
        ...filters
    });
    window.location.href = url;
};
</script>

<template>
    <Head title="Course Registrations" />

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
                        <BreadcrumbLink>Academics</BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                     <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                             <Link :href="route('admin.academics.index') + '?tab=courses'">Courses</Link>
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
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">{{ course.code }} - Registrations</h2>
                    <p class="text-lg text-muted-foreground mt-1">{{ course.title }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <Badge variant="secondary" class="text-sm px-3 py-0.5">{{ course.units }} Units</Badge>
                        <Badge variant="outline" class="text-sm px-3 py-0.5">{{ registrations.data.length }} Students Found</Badge>
                    </div>
                </div>
                <div>
                     <Button size="lg" @click="exportRegistrations" class="shadow-sm">
                        <Download class="mr-2 h-4 w-4" /> Export Report
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <Card class="bg-muted/30 border-none shadow-none">
                <CardHeader class="pb-2">
                     <CardTitle class="text-lg font-medium">Filter Results</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div class="space-y-2">
                            <Label class="text-xs font-medium uppercase text-muted-foreground">Session</Label>
                             <Select v-model="form.session_id">
                                <SelectTrigger class="bg-card w-full">
                                    <SelectValue placeholder="All Sessions" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Sessions</SelectItem>
                                    <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                        {{ session.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                             <Label class="text-xs font-medium uppercase text-muted-foreground">Level</Label>
                             <Select v-model="form.level">
                                <SelectTrigger class="bg-card w-full">
                                    <SelectValue placeholder="All Levels" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Levels</SelectItem>
                                    <SelectItem v-for="lvl in levels" :key="lvl" :value="lvl">
                                        {{ lvl }} Level
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                         <div class="space-y-2">
                             <Label class="text-xs font-medium uppercase text-muted-foreground">Faculty</Label>
                            <Select v-model="form.faculty_id">
                                <SelectTrigger class="bg-card w-full">
                                    <SelectValue placeholder="All Faculties" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Faculties</SelectItem>
                                    <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                                        {{ faculty.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                             <Label class="text-xs font-medium uppercase text-muted-foreground">Department</Label>
                            <Select v-model="form.department_id">
                                <SelectTrigger class="bg-card w-full">
                                    <SelectValue placeholder="All Departments" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Departments</SelectItem>
                                    <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                             <Label class="text-xs font-medium uppercase text-muted-foreground">Programme</Label>
                             <Select v-model="form.programme_id">
                                <SelectTrigger class="bg-card w-full">
                                    <SelectValue placeholder="All Programmes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Programmes</SelectItem>
                                    <SelectItem v-for="prog in programmes" :key="prog.id" :value="prog.id">
                                        {{ prog.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="shadow-sm border-muted">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-b">
                                <TableHead class="h-12 w-[180px]">Matric Number</TableHead>
                                <TableHead class="h-12">Student Name</TableHead>
                                <TableHead class="h-12">Department</TableHead>
                                <TableHead class="h-12">Programme</TableHead>
                                <TableHead class="h-12 w-[100px]">Level</TableHead>
                                <TableHead class="h-12 w-[150px]">Session</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <tr v-if="registrations.data.length === 0">
                                <td colspan="6" class="p-12 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="p-4 rounded-full bg-muted/50">
                                            <BookOpen class="h-8 w-8 text-muted-foreground/50" />
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-medium text-lg text-foreground">No students found</p>
                                            <p class="text-sm">Try adjusting your filters to see more results.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <TableRow v-for="reg in registrations.data" :key="reg.id" class="hover:bg-muted/30 transition-colors">
                                <TableCell class="font-mono font-medium text-primary">{{ reg.student.matriculation_number }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground">{{ reg.student.user.name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ reg.student.user.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>{{ reg.student.department?.name || 'N/A' }}</TableCell>
                                <TableCell>{{ reg.student.program?.name || 'N/A' }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ reg.student.current_level }}</Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ reg.session.name }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div class="mt-6 flex justify-end">
                <Pagination :links="registrations.links" />
            </div>
        </div>
    </AdminLayout>
</template>
