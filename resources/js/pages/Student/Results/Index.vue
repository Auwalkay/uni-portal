<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

defineProps<{
    cgpa: number;
    results: Record<string, Record<string, {
        gpa: number;
        courses: Array<{
            id: string;
            score: number;
            grade: string;
            grade_point: string;
            course: {
                id: string;
                code: string;
                title: string;
                units: number;
            };
        }>;
    }>>;
}>();
</script>

<template>
    <Head title="My Results" />

    <StudentLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Academic Results</h2>
                    <p class="text-muted-foreground">Detailed breakdown of your performance.</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-muted-foreground">Current CGPA</p>
                    <p class="text-4xl font-bold text-primary">{{ cgpa }}</p>
                </div>
            </div>

            <div v-if="Object.keys(results).length === 0" class="p-8 text-center border rounded-lg bg-muted/20">
                <p class="text-muted-foreground">No results published yet.</p>
            </div>

            <div v-for="(semesters, sessionName) in results" :key="sessionName" class="space-y-4">
                <h3 class="text-xl font-semibold">{{ sessionName }} Session</h3>
                
                <div v-for="(data, semesterName) in semesters" :key="semesterName">
                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                             <CardTitle class="text-lg font-medium">{{ semesterName }}</CardTitle>
                             <Badge variant="secondary" class="text-lg">GPA: {{ data.gpa }}</Badge>
                        </CardHeader>
                        <CardContent>
                             <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Course Code</TableHead>
                                        <TableHead>Title</TableHead>
                                        <TableHead>Units</TableHead>
                                        <TableHead>Score</TableHead>
                                        <TableHead>Grade</TableHead>
                                        <TableHead>Point</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="reg in data.courses" :key="reg.id">
                                        <TableCell class="font-mono font-medium">{{ reg.course.code }}</TableCell>
                                        <TableCell>{{ reg.course.title }}</TableCell>
                                        <TableCell>{{ reg.course.units }}</TableCell>
                                        <TableCell>{{ reg.score ?? '-' }}</TableCell>
                                        <TableCell class="font-bold">{{ reg.grade ?? '-' }}</TableCell>
                                        <TableCell>{{ reg.grade_point ?? '-' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                             </Table>
                        </CardContent>
                     </Card>
                </div>
                <Separator class="my-6" />
            </div>
        </div>
    </StudentLayout>
</template>
