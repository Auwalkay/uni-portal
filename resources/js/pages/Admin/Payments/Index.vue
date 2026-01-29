<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: '/admin/payments',
    },
];

defineProps<{
    invoices: Object;
}>();
</script>

<template>
    <Head title="Manage Payments" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Payments</h1>
            </div>
            
            <div class="rounded-xl border border-sidebar-border bg-sidebar p-6 shadow-sm">
                <p class="text-sm text-muted-foreground">Payment records table will go here.</p>
            </div>
        </div>
    </AdminLayout>
</template>
