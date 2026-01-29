<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

defineProps<{
    courses: Array<{
        id: string;
        code: string;
        title: string;
        units: number;
        department: {
            name: string;
        };
    }>;
}>();
</script>

<template>
    <Head title="Course Results" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Result Management</h2>
                    <p class="text-muted-foreground">Select a course to enter results for the current session.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Courses</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Title</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Units</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <tr v-if="courses.length === 0">
                                <td colspan="5" class="p-4 text-center text-muted-foreground">
                                    No courses found.
                                </td>
                            </tr>
                            <TableRow v-for="course in courses" :key="course.id">
                                <TableCell class="font-mono font-medium">{{ course.code }}</TableCell>
                                <TableCell>{{ course.title }}</TableCell>
                                <TableCell>{{ course.department?.name }}</TableCell>
                                <TableCell>{{ course.units }}</TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('admin.results.edit', course.id)">
                                        <Button variant="outline" size="sm">Enter Results</Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
