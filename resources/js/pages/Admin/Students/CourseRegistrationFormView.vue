<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    Download, 
    ArrowLeft, 
    Printer, 
    BookOpen, 
    GraduationCap, 
    Building2, 
    Folder, 
    CalendarRange,
    FileCheck
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    student: any;
    registrations: Record<string, any[]>;
    session: any;
    total_units: number;
}>();

const breadcrumbs = [
    { title: 'Academics', href: '#' },
    { title: 'Course Registration', href: '/admin/course-registration' },
    { title: 'Form Review', href: '#' }
];
</script>

<template>
    <Head title="Course Registration Form Review" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-5xl mx-auto">
            
            <!-- Actions Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border shadow-sm">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.course_registration.index')">
                        <Button variant="outline" size="icon" class="rounded-xl">
                            <ArrowLeft class="w-4 h-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Registration Form Review</h1>
                        <p class="text-xs text-muted-foreground">Session: {{ session.name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a 
                        :href="route('admin.course_registration.form', { student: student.id }) + '?download=1'" 
                        target="_blank"
                        class="w-full sm:w-auto"
                    >
                        <Button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white gap-2 h-11 rounded-xl shadow-lg shadow-emerald-600/10">
                            <Download class="w-4 h-4" />
                            Download PDF
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Student Summary Card -->
            <Card class="border-none shadow-md overflow-hidden rounded-2xl bg-gradient-to-br from-card to-background">
                <div class="h-2 bg-primary w-full"></div>
                <CardContent class="p-8">
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <Avatar class="h-28 w-24 border-2 border-border shadow-sm rounded-xl">
                            <AvatarImage :src="student.passport_photo_path ? `/storage/${student.passport_photo_path}` : ''" />
                            <AvatarFallback class="bg-primary/10 text-primary font-bold text-2xl rounded-xl">
                                {{ student.user.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>
                        
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 flex-1 w-full">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase text-muted-foreground block tracking-wider">Student Name</span>
                                <span class="font-extrabold text-lg text-gray-900 block">{{ student.user.name }} {{ student.user.last_name }}</span>
                                <span class="text-xs text-muted-foreground block font-mono">{{ student.matriculation_number }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase text-muted-foreground block tracking-wider">Faculty & Department</span>
                                <span class="font-bold text-sm text-gray-800 block">{{ student.academic_department?.faculty?.name }}</span>
                                <span class="text-xs text-muted-foreground block">{{ student.academic_department?.name }}</span>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase text-muted-foreground block tracking-wider">Programme & level</span>
                                <span class="font-bold text-sm text-gray-800 block">{{ student.program?.name }}</span>
                                <span class="text-xs text-primary font-bold block">Level {{ student.current_level }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Semester Courses Breakdown -->
            <div class="space-y-8">
                <div v-for="(courses, semesterName) in registrations" :key="semesterName" class="space-y-4">
                    <Card class="border-none shadow-md overflow-hidden rounded-2xl">
                        <CardHeader class="bg-muted/30 pb-4">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <BookOpen class="w-5 h-5 text-primary" />
                                    <CardTitle class="text-lg font-bold">{{ semesterName }}</CardTitle>
                                </div>
                                <Badge variant="outline" class="bg-primary/5 text-primary border-primary/20 px-3 py-1 font-semibold">
                                    {{ courses.reduce((sum, r) => sum + Number(r.course?.units || 0), 0) }} Units Registered
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="pl-8">Course Code</TableHead>
                                        <TableHead>Course Title</TableHead>
                                        <TableHead class="text-right pr-8">Units</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="reg in courses" :key="reg.id" class="hover:bg-muted/10">
                                        <TableCell class="pl-8 font-mono font-bold text-primary">{{ reg.course?.code }}</TableCell>
                                        <TableCell class="font-semibold text-gray-800">{{ reg.course?.title }}</TableCell>
                                        <TableCell class="text-right pr-8 font-bold">{{ reg.course?.units }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Footer Summary -->
            <div class="flex justify-between items-center bg-slate-900 text-white p-6 rounded-2xl shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-full bg-white/10">
                        <FileCheck class="h-6 w-6 text-emerald-400" />
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block uppercase tracking-wider font-semibold">Registration Status</span>
                        <span class="text-sm font-extrabold text-emerald-400 block">Verified & Approved</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block uppercase tracking-wider font-semibold">Total Session Credit Units</span>
                    <span class="text-2xl font-black text-white block">{{ total_units }} Units</span>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
