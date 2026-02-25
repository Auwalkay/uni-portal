<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Award, Plus, Edit, Trash2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    scholarships: Array<{ id: string; name: string; percentage: string }>;
}>();

// Create Modal State
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    percentage: '',
});

const submitCreate = () => {
    createForm.post(route('admin.scholarships.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State
const showEditModal = ref(false);
const editForm = useForm({
    id: '',
    name: '',
    percentage: '',
});

const openEditModal = (scholarship: { id: string; name: string; percentage: string }) => {
    editForm.id = scholarship.id;
    editForm.name = scholarship.name;
    editForm.percentage = scholarship.percentage;
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('admin.scholarships.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Delete
const deleteScholarship = (id: string, name: string) => {
    if (confirm(`Are you sure you want to delete the ${name} scholarship? This action cannot be undone.`)) {
        router.delete(route('admin.scholarships.destroy', id));
    }
};

const formatPercentage = (val: string) => {
    return Number(val).toString() + '%';
};
</script>

<template>
    <Head title="Manage Scholarships" />

    <AdminLayout>
        <div class="py-10 px-6 space-y-8 w-full max-w-[1200px] mx-auto">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
                        <Award class="w-8 h-8 text-primary" /> Scholarships
                    </h1>
                    <p class="text-muted-foreground mt-1">Manage scholarship categories and their associated fee discounts.</p>
                </div>

                <Button @click="showCreateModal = true" shadow="md">
                    <Plus class="w-4 h-4 mr-2" /> Add Scholarship
                </Button>
            </div>

            <!-- Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Active Scholarships</CardTitle>
                    <CardDescription>A list of all available scholarships in the institution.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead class="text-center">Discount Percentage</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="scholarship in scholarships" :key="scholarship.id">
                                <TableCell class="font-medium">{{ scholarship.name }}</TableCell>
                                <TableCell class="text-center">
                                    <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-sm font-semibold text-primary">
                                        {{ formatPercentage(scholarship.percentage) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="ghost" size="icon" @click="openEditModal(scholarship)">
                                            <Edit class="w-4 h-4 text-muted-foreground" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="deleteScholarship(scholarship.id, scholarship.name)">
                                            <Trash2 class="w-4 h-4 text-destructive" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="scholarships.length === 0">
                                <TableCell colspan="3" class="h-24 text-center text-muted-foreground">
                                    No scholarships found. Click "Add Scholarship" to create one.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Create Modal -->
            <Dialog v-model:open="showCreateModal">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Add New Scholarship</DialogTitle>
                        <DialogDescription>
                            Create a new scholarship category. The percentage represents the fee discount applied.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitCreate" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="name">Scholarship Name</Label>
                            <Input id="name" v-model="createForm.name" placeholder="e.g Presidential Scholarship" required />
                            <p v-if="createForm.errors.name" class="text-sm text-destructive">{{ createForm.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="percentage">Discount Percentage (%)</Label>
                            <Input id="percentage" type="number" step="0.01" min="0" max="100" v-model="createForm.percentage" placeholder="e.g 50" required />
                            <p v-if="createForm.errors.percentage" class="text-sm text-destructive">{{ createForm.errors.percentage }}</p>
                        </div>
                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                            <Button type="submit" :disabled="createForm.processing">Create Scholarship</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Modal -->
            <Dialog v-model:open="showEditModal">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Edit Scholarship</DialogTitle>
                        <DialogDescription>
                            Update the details of this scholarship.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="edit-name">Scholarship Name</Label>
                            <Input id="edit-name" v-model="editForm.name" required />
                            <p v-if="editForm.errors.name" class="text-sm text-destructive">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-percentage">Discount Percentage (%)</Label>
                            <Input id="edit-percentage" type="number" step="0.01" min="0" max="100" v-model="editForm.percentage" required />
                            <p v-if="editForm.errors.percentage" class="text-sm text-destructive">{{ editForm.errors.percentage }}</p>
                        </div>
                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
                            <Button type="submit" :disabled="editForm.processing">Save Changes</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
