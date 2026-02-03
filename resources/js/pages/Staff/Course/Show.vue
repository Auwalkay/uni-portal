<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen, Users, ArrowLeft, Download, Search, MoreHorizontal
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
import { ref, computed } from 'vue';

const props = defineProps<{
    course: { id: string; code: string; title: string; unit: number };
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
}>();

const search = ref('');

const filteredStudents = computed(() => {
    if (!search.value) return props.students;
    const lower = search.value.toLowerCase();
    return props.students.filter(s =>
        s.student_name.toLowerCase().includes(lower) ||
        s.matric_number.toLowerCase().includes(lower)
    );
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Course Details', href: '#' }
];
</script>

<template>
    <Head :title="`${course.code} - Student List`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-[1600px] mx-auto">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" as-child>
                        <Link href="/admin/dashboard">
                            <ArrowLeft class="w-4 h-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ course.code }} - {{ course.title }}</h1>
                        <p class="text-muted-foreground flex items-center gap-2">
                            <span>{{ session.name }} Session</span>
                            <span>•</span>
                            <span>{{ course.unit }} Units</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                     <Button variant="outline">
                        <Download class="w-4 h-4 mr-2" /> Export List
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
                                <Input v-model="search" placeholder="Search by name or matric no..." class="pl-8" />
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
    </AdminLayout>
</template>
