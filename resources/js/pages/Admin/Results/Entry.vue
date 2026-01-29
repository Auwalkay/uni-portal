<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref } from 'vue';

const props = defineProps<{
    course: any;
    session: any;
    registrations: any[];
}>();

const form = useForm({
    scores: props.registrations.map(reg => ({
        id: reg.id,
        ca_score: parseFloat(reg.ca_score),
        exam_score: parseFloat(reg.exam_score),
    })),
});

const submit = () => {
    form.post(route('admin.results.update', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: toast notification
        }
    });
};

const getStudentName = (regId: string) => {
    const reg = props.registrations.find(r => r.id === regId);
    return reg ? `${reg.student.user.last_name} ${reg.student.user.name}` : 'Unknown';
};

const getMatricNo = (regId: string) => {
    const reg = props.registrations.find(r => r.id === regId);
    return reg ? reg.student.matriculation_number : 'N/A';
};

const calculateTotal = (index: number) => {
    const s = form.scores[index];
    return (Number(s.ca_score) || 0) + (Number(s.exam_score) || 0);
};
</script>

<template>
    <Head title="Enter Results" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Score Entry</h2>
                    <p class="text-muted-foreground">
                        {{ course.code }} - {{ course.title }} ({{ session.name }})
                    </p>
                </div>
                <Button @click="submit" :disabled="form.processing">Save Results</Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Matric No</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead class="w-[100px]">CA (40)</TableHead>
                                <TableHead class="w-[100px]">Exam (60)</TableHead>
                                <TableHead class="w-[100px]">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(score, index) in form.scores" :key="score.id">
                                <TableCell class="font-mono">{{ getMatricNo(score.id) }}</TableCell>
                                <TableCell>{{ getStudentName(score.id) }}</TableCell>
                                <TableCell>
                                    <Input 
                                        type="number" 
                                        v-model="score.ca_score" 
                                        min="0" max="40" step="0.5"
                                        class="w-20"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Input 
                                        type="number" 
                                        v-model="score.exam_score" 
                                        min="0" max="100" step="0.5"
                                        class="w-20"
                                    />
                                </TableCell>
                                <TableCell class="font-bold">
                                    {{ calculateTotal(index) }}
                                </TableCell>
                            </TableRow>
                             <tr v-if="form.scores.length === 0">
                                <td colspan="5" class="p-4 text-center text-muted-foreground">
                                    No students registered for this course yet.
                                </td>
                            </tr>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
