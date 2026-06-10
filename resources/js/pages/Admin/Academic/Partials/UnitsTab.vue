<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Plus, Pencil } from 'lucide-vue-next';

defineProps<{
    units: {
        data: Array<any>;
        links: Array<any>;
    };
    canManage: boolean;
}>();

const emit = defineEmits(['create', 'edit', 'toggle']);
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <div>
                <CardTitle>Units</CardTitle>
                <CardDescription>Manage units within departments.</CardDescription>
            </div>
            <Button v-if="canManage" @click="emit('create')"><Plus class="mr-2 h-4 w-4" /> Add Unit</Button>
        </CardHeader>
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Department</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right" v-if="canManage">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="unit in units.data" :key="unit.id">
                        <TableCell class="font-mono font-medium">{{ unit.code }}</TableCell>
                        <TableCell>{{ unit.name }}</TableCell>
                        <TableCell>{{ unit.department?.name }}</TableCell>
                        <TableCell>
                            <Switch :disabled="!canManage" :checked="unit.is_active" @update:checked="emit('toggle', unit.id, unit.is_active)" />
                        </TableCell>
                        <TableCell class="text-right" v-if="canManage">
                            <Button variant="ghost" size="icon" @click="emit('edit', unit)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <div class="flex justify-center mt-4 space-x-2" v-if="units.links">
                <Button 
                    v-for="(link, i) in units.links" 
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
