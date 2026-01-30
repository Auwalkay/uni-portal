<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Plus, Pencil } from 'lucide-vue-next';

defineProps<{
    courses: {
        data: Array<any>;
        links: Array<any>;
    };
}>();

const emit = defineEmits(['create', 'edit', 'toggle']);
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <div>
                <CardTitle>Courses</CardTitle>
                <CardDescription>Manage all courses.</CardDescription>
            </div>
                <Button @click="emit('create')"><Plus class="mr-2 h-4 w-4" /> Add Course</Button>
        </CardHeader>
        <CardContent>
                <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Title</TableHead>
                        <TableHead>Units</TableHead>
                        <TableHead>Department</TableHead>
                        <TableHead>Level</TableHead>
                        <TableHead>Sem</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="course in courses.data" :key="course.id">
                        <TableCell class="font-mono font-medium">{{ course.code }}</TableCell>
                        <TableCell>{{ course.title }}</TableCell>
                        <TableCell>{{ course.units }}</TableCell>
                        <TableCell>{{ course.department?.name }}</TableCell>
                            <TableCell>{{ course.level }}</TableCell>
                            <TableCell>{{ course.semester }}</TableCell>
                        <TableCell>
                                <Switch :checked="course.is_active" @update:checked="emit('toggle', course.id, course.is_active)" />
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="icon" @click="emit('edit', course)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
                <div class="flex justify-center mt-4 space-x-2" v-if="courses.links">
                <Button 
                    v-for="(link, i) in courses.links" 
                    :key="i"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    size="sm"
                    as-child
                >
                <a :href="link.url" v-html="link.label"></a>
                </Button>
                </div>
        </CardContent>
    </Card>
</template>
