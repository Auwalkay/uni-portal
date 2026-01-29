<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue'; // Import computed
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { route } from 'ziggy-js';
import { FileText, PlusCircle } from 'lucide-vue-next';

const props = defineProps<{
    student: any;
    history: any[];
}>();

const hasCurrentRegistration = computed(() => {
    return props.history.some(h => h.is_current);
});
</script>

<template>
    <Head title="My Courses" />

    <StudentLayout>
        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">My Courses</h2>
                    <p class="text-muted-foreground">View your course registration history.</p>
                </div>
                <div>
                    <Link :href="route('student.courses.create')">
                        <Button :variant="hasCurrentRegistration ? 'outline' : 'default'">
                            <PlusCircle v-if="!hasCurrentRegistration" class="mr-2 h-4 w-4" /> 
                            {{ hasCurrentRegistration ? 'Edit Current Registration' : 'Start Course Registration' }}
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-if="history.length === 0" class="text-center py-12">
                <p class="text-muted-foreground">No course registrations found.</p>
                <Link :href="route('student.courses.create')">
                    <Button variant="link">Register for the current session</Button>
                </Link>
            </div>

            <div v-else class="space-y-6">
                <!-- Loop through history items (Sessions) -->
                <Card v-for="(sessionRecord, index) in history" :key="index">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <div>
                            <CardTitle>{{ sessionRecord.session }} Session</CardTitle>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge v-if="sessionRecord.is_current" variant="default">Current</Badge>
                            <Badge v-else variant="outline">History</Badge>
                            
                             <div class="flex gap-2">
                                <Link :href="route('student.courses.create')" v-if="sessionRecord.is_current">
                                    <Button variant="secondary" size="sm">
                                        Edit
                                    </Button>
                                </Link>
                                <a :href="route('student.courses.form')" target="_blank" v-if="sessionRecord.is_current">
                                    <Button variant="ghost" size="sm">
                                        <FileText class="mr-2 h-4 w-4" /> Print Form
                                    </Button>
                                </a>
                             </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div v-for="semester in sessionRecord.semesters" :key="semester.id">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                                    {{ semester.name }} ({{ semester.total_units }} Units)
                                </h4>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Code</TableHead>
                                            <TableHead>Title</TableHead>
                                            <TableHead>Units</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Level</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="course in semester.courses" :key="course.id">
                                            <TableCell class="font-mono font-medium">{{ course.code }}</TableCell>
                                            <TableCell>{{ course.title }}</TableCell>
                                            <TableCell>{{ course.units }}</TableCell>
                                            <TableCell>
                                                <Badge variant="secondary" class="text-xs">
                                                    {{ course.is_compulsory ? 'Core' : 'Elective' }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{{ course.level }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>
