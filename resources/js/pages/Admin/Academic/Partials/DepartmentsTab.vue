<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Plus, Pencil } from 'lucide-vue-next';

defineProps<{
    departments: {
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
                <CardTitle>Departments</CardTitle>
                <CardDescription>Manage departments within faculties.</CardDescription>
            </div>
            <Button @click="emit('create')"><Plus class="mr-2 h-4 w-4" /> Add Department</Button>
        </CardHeader>
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Faculty</TableHead>
                        <TableHead>Programmes</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="dept in departments.data" :key="dept.id">
                        <TableCell class="font-mono font-medium">{{ dept.code }}</TableCell>
                        <TableCell>{{ dept.name }}</TableCell>
                        <TableCell>{{ dept.faculty?.name }}</TableCell>
                        <TableCell>{{ dept.programmes_count }}</TableCell>
                        <TableCell>
                                <Switch :checked="dept.is_active" @update:checked="emit('toggle', dept.id, dept.is_active)" />
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="icon" @click="emit('edit', dept)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <div class="flex justify-center mt-4 space-x-2" v-if="departments.links">
                <Button 
                    v-for="(link, i) in departments.links" 
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
