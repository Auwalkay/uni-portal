<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { route } from 'ziggy-js'; // Fix: Import route


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Staff Management',
        href: '/admin/staff',
    },
];

const props = defineProps<{
    staff: {
        data: Array<{
            id: string;
            name: string;
            email: string;
            staff: {
                staff_number: string;
                designation: string;
                department: {
                    name: string;
                } | null;
            } | null;
        }>;
        links: Array<any>;
    };
}>();

const deleteStaff = (id: string) => {
    if (confirm('Are you sure you want to delete this staff member?')) {
        router.delete(route('admin.staff.destroy', id));
    }
};
</script>

<template>
    <Head title="Staff Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Staff Management</h1>
                <Button as-child>
                    <Link :href="route('admin.staff.create')">Add Staff</Link>
                </Button>
            </div>
            
            <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Staff Number</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Designation</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in staff.data" :key="user.id">
                            <TableCell>{{ user.staff?.staff_number || 'N/A' }}</TableCell>
                            <TableCell>{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>{{ user.staff?.department?.name || '-' }}</TableCell>
                            <TableCell>{{ user.staff?.designation || '-' }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="outline" size="sm" as-child>
                                        <Link :href="route('admin.staff.edit', user.id)">Edit</Link>
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deleteStaff(user.id)">
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="staff.data.length === 0">
                            <TableCell colspan="6" class="text-center">No staff found.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <!-- Pagination could go here -->
            </div>
        </div>
    </AdminLayout>
</template>
