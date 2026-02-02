<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Plus, Pencil } from 'lucide-vue-next';

defineProps<{
    faculties: {
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
                <CardTitle>Faculties</CardTitle>
                <CardDescription>Manage university faculties.</CardDescription>
            </div>
            <Button @click="emit('create')"><Plus class="mr-2 h-4 w-4" /> Add Faculty</Button>
        </CardHeader>
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Departments</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="faculty in faculties.data" :key="faculty.id">
                        <TableCell class="font-mono font-medium">{{ faculty.code }}</TableCell>
                        <TableCell>{{ faculty.name }}</TableCell>
                        <TableCell>{{ faculty.departments_count }}</TableCell>
                        <TableCell>
                            <div class="flex items-center space-x-2">
                                <Switch :checked="faculty.is_active" @update:checked="emit('toggle', faculty.id, faculty.is_active)" />
                            </div>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="icon" @click="emit('edit', faculty)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
             <div class="flex justify-center mt-4 space-x-2" v-if="faculties.links">
                <Button 
                    v-for="(link, i) in faculties.links" 
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
