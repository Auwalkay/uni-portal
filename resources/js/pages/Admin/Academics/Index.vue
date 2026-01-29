<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Academics',
        href: '/admin/academics',
    },
];
</script>

<template>
    <Head title="Academics" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Academics</h1>
                <div class="flex gap-2">
                    <button class="rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground hover:bg-secondary/90">
                        Manage Courses
                    </button>
                    <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                        Create Session
                    </button>
                </div>
            </div>
            
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-foreground">Faculties & Departments</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Manage academic structures.</p>
                </div>
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-foreground">Course Catalog</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Manage courses and units.</p>
                </div>
                <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                    <h3 class="text-lg font-medium text-foreground">Sessions & Semesters</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Configure academic calendar.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
