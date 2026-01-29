<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Students',
        href: '/admin/students',
    },
];

defineProps<{
    students: Object;
}>();
</script>

<template>
    <Head title="Manage Students" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Students</h1>
                <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Add Student
                </button>
            </div>
            
            <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                <p class="text-sm text-muted-foreground">Student management table will go here.</p>
                <!-- TODO: Implement DataTable -->
            </div>
        </div>
    </AdminLayout>
</template>
