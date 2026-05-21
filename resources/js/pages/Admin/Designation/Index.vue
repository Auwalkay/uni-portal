<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import Swal from 'sweetalert2';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    designations: any[];
}>();

const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');

const form = useForm({
    id: '',
    name: '',
    is_active: true,
});

const openCreate = () => {
    modalMode.value = 'create';
    form.reset();
    isModalOpen.value = true;
};

const openEdit = (designation: any) => {
    modalMode.value = 'edit';
    form.clearErrors();
    form.id = designation.id;
    form.name = designation.name;
    form.is_active = designation.is_active;
    isModalOpen.value = true;
};

const submitForm = () => {
    const url = modalMode.value === 'create' 
        ? route('admin.designations.store') 
        : route('admin.designations.update', { designation: form.id });
    
    const method = modalMode.value === 'create' ? 'post' : 'put';

    form.submit(method, url, {
        onSuccess: () => {
            isModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `Designation ${modalMode.value === 'create' ? 'created' : 'updated'} successfully`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
    });
};

const deleteDesignation = (id: string) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({}).delete(route('admin.designations.destroy', { designation: id }), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Designation has been deleted.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        }
    });
};

</script>

<template>
    <Head title="Designations" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Designations</h2>
                    <p class="text-muted-foreground">Manage staff designations within the university.</p>
                </div>
                <Button @click="openCreate">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Designation
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Staff Designations</CardTitle>
                    <CardDescription>A list of all staff roles and designations.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="designation in designations" :key="designation.id">
                                <TableCell class="font-medium">{{ designation.name }}</TableCell>
                                <TableCell>
                                    <Badge :variant="designation.is_active ? 'default' : 'secondary'">
                                        {{ designation.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ new Date(designation.created_at).toLocaleDateString() }}</TableCell>
                                <TableCell class="text-right space-x-2">
                                    <Button variant="ghost" size="icon" @click="openEdit(designation)">
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-destructive" @click="deleteDesignation(designation.id)">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="designations.length === 0">
                                <TableCell colspan="4" class="text-center py-4 text-muted-foreground">
                                    No designations found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Modal for Create/Edit -->
            <Dialog v-model:open="isModalOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{ modalMode === 'create' ? 'Create' : 'Edit' }} Designation</DialogTitle>
                        <DialogDescription>
                            {{ modalMode === 'create' ? 'Add a new staff designation' : 'Update existing staff designation' }}.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <div class="space-y-1">
                            <Label for="name">Designation Name</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Professor, Registrar, Clerk" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <Switch id="is_active" :checked="form.is_active" @update:checked="val => form.is_active = val" />
                            <Label for="is_active">Is Active</Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isModalOpen = false" :disabled="form.processing">Cancel</Button>
                        <Button @click="submitForm" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
