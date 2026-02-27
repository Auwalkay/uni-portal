<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { route } from 'ziggy-js';

defineProps<{
    canLogin?: boolean;
}>();
</script>

<template>
    <Head title="Welcome | NBTE Central Portal" />

    <div class="min-h-screen bg-slate-50 flex flex-col">
        <!-- Navigation -->
        <nav class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex flex-shrink-0 items-center gap-2">
                            <ApplicationMark class="block h-8 w-auto text-primary" />
                            <Link :href="route('central.home')">
                                <span class="text-2xl font-bold text-primary">NBTE Portal</span>
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <template v-if="$page.props.auth?.user">
                            <Link :href="route('central.dashboard')">
                                <Button variant="ghost">Dashboard</Button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('central.login')">
                                <Button variant="ghost">Super Admin Login</Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="flex-grow flex flex-col justify-center items-center text-center px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center max-w-3xl mx-auto space-y-6">
                <h1 class="text-5xl font-extrabold text-slate-900 tracking-tight sm:text-6xl">
                    National Board for Technical Education
                </h1>
                <p class="text-xl text-slate-600">
                The ultimate multi-tenant platform for managing student records, admissions, finance, and staff across entirely isolated polytechnic environments.
                </p>
            </div>
            <div class="mt-10 flex justify-center gap-4">
                <Link :href="route('central.login')">
                    <Button size="lg" class="px-8">Enter Portal</Button>
                </Link>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t py-12 text-center text-slate-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p>&copy; {{ new Date().getFullYear() }} National Board for Technical Education. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
