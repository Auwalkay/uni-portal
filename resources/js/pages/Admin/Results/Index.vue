<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Search, Filter, BookOpen, Home, GraduationCap, FileText } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { throttle } from 'lodash';
import Pagination from '@/Components/Pagination.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

const props = defineProps<{
    sessions: Array<{ id: string; name: string }>;
    departments: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string }>;
    courses: {
        data: Array<{
            id: string;
            code: string;
            title: string;
            units: number;
            level: string; 
            department: { name: string };
            program: { name: string };
            graded_count: number;
            total_students: number;
        }>;
        links: any[];
    };
    filters: {
        session_id: string;
        department_id: string;
        level: string;
        has_registrations: boolean;
    };
}>();

const form = ref({
    session_id: props.filters.session_id || 'ALL',
    department_id: props.filters.department_id || 'ALL',
    level: props.filters.level || 'ALL',
    has_registrations: props.filters.has_registrations || false,
});

// Watch for changes and update results
watch(form, throttle(() => {
    const filters = { ...form.value };
    (Object.keys(filters) as Array<keyof typeof filters>).forEach(key => {
        if (filters[key] === 'ALL') filters[key] = '';
    });
    
    router.get(route('admin.results.index'), filters, {
        preserveState: true,
        replace: true,
    });
}, 500), { deep: true });

const levels = ['100', '200', '300', '400', '500', '600'];

const getProgressVariant = (course: any) => {
    if (course.graded_count === 0) return 'outline';
    if (course.graded_count === course.total_students) return 'default';
    return 'secondary';
};

const getProgressColor = (course: any) => {
    if (course.graded_count === 0) return '';
    if (course.graded_count === course.total_students) return 'bg-green-50 text-green-700 border-green-200';
    return 'bg-yellow-50 text-yellow-700 border-yellow-200';
};
</script>

<template>
    <Head title="Course Results" />

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
                        <BreadcrumbPage>Results</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b pb-6">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">Result Management</h2>
                    <p class="text-lg text-muted-foreground mt-1">Manage and upload course results for academic sessions</p>
                    <div class="flex items-center gap-2 mt-2">
                        <Badge variant="secondary" class="text-sm px-3 py-0.5">
                            <FileText class="h-3 w-3 mr-1" />
                            {{ courses.data.length }} Courses
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <Card class="bg-muted/30 border-none shadow-none">
                <CardHeader class="pb-2">
                    <CardTitle class="text-lg font-medium">Filter Courses</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="space-y-2">
                            <Label class="text-xs font-medium uppercase text-muted-foreground">Academic Session</Label>
                            <Select v-model="form.session_id">
                                <SelectTrigger class="bg-card w-full">
                                    <SelectValue placeholder="Select Session" />
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
                            <Label class="text-xs font-medium uppercase text-muted-foreground">Filter Options</Label>
                            <div class="flex items-center space-x-2 h-10 px-3 rounded-md border bg-card">
                                <Switch 
                                    id="has_registrations" 
                                    v-model:checked="form.has_registrations"
                                />
                                <Label for="has_registrations" class="text-sm font-normal cursor-pointer">
                                    With Registrations Only
                                </Label>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Courses Table -->
            <Card class="shadow-sm border-muted">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-b">
                                <TableHead class="h-12 w-[140px]">Course Code</TableHead>
                                <TableHead class="h-12">Course Title</TableHead>
                                <TableHead class="h-12">Department</TableHead>
                                <TableHead class="h-12 w-[100px]">Level</TableHead>
                                <TableHead class="h-12 w-[180px]">Grading Progress</TableHead>
                                <TableHead class="h-12 w-[160px] text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <tr v-if="courses.data.length === 0">
                                <td colspan="6" class="p-12 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="p-4 rounded-full bg-muted/50">
                                            <BookOpen class="h-8 w-8 text-muted-foreground/50" />
                                        </div>
                                        <div class="space-y-1">
                                            <p class="font-medium text-lg text-foreground">No courses found</p>
                                            <p class="text-sm">Try adjusting your filters to see more results.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <TableRow v-for="course in courses.data" :key="course.id" class="hover:bg-muted/30 transition-colors">
                                <TableCell class="font-mono font-medium text-primary">{{ course.code }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground">{{ course.title }}</span>
                                        <span class="text-xs text-muted-foreground">{{ course.units }} Units</span>
                                    </div>
                                </TableCell>
                                <TableCell>{{ course.department?.name || 'N/A' }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ course.level }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="getProgressVariant(course)" 
                                        :class="getProgressColor(course)"
                                    >
                                        {{ course.graded_count }} / {{ course.total_students }} Graded
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('admin.results.edit', { course: course.id, session_id: form.session_id === 'ALL' ? '' : form.session_id })">
                                        <Button size="sm" variant="default" class="shadow-sm">
                                            <GraduationCap class="h-4 w-4 mr-1" />
                                            Manage Results
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div class="mt-6 flex justify-end">
                <Pagination :links="courses.links" />
            </div>
        </div>
    </AdminLayout>
</template>
