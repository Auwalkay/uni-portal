<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Plus, Pencil } from 'lucide-vue-next';

defineProps<{
    programmes: {
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
                <CardTitle>Programmes</CardTitle>
                <CardDescription>View degree programmes.</CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Department</TableHead>
                        <TableHead>Faculty</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="prog in programmes.data" :key="prog.id">
                        <TableCell class="font-medium">{{ prog.name }}</TableCell>
                        <TableCell><Badge variant="outline">{{ prog.type }}</Badge></TableCell>
                        <TableCell>{{ prog.department?.name }}</TableCell>
                        <TableCell>{{ prog.department?.faculty?.name }}</TableCell>
                        <TableCell>
                            <Badge :variant="prog.is_active ? 'default' : 'secondary'">{{ prog.is_active ? 'Active' : 'Inactive' }}</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <div class="flex justify-center mt-4 space-x-2" v-if="programmes.links">
                <Button 
                    v-for="(link, i) in programmes.links" 
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
