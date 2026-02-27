<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShieldX } from 'lucide-vue-next';

defineProps<{
    schoolName?: string;
}>();
</script>

<template>
    <Head title="Access Suspended" />

    <div class="min-h-screen flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center bg-white p-10 rounded-2xl shadow-xl border border-slate-100">
            <div>
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6 border-4 border-white shadow-sm ring-4 ring-red-50">
                    <ShieldX class="h-10 w-10 text-red-600" aria-hidden="true" />
                </div>
                
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900 tracking-tight">
                    Access Suspended
                </h2>
                
                <div class="mt-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <p class="text-slate-600 font-medium pb-2 border-b border-slate-200">
                        {{ schoolName || 'This Polytechnic' }}
                    </p>
                    <p class="text-sm text-slate-500 mt-2">
                        Portal access has been temporarily revoked by the central administration.
                    </p>
                </div>
            </div>

            <div class="mt-8 space-y-4">
                <p class="text-sm text-slate-500 bg-red-50 text-red-700 p-3 rounded-md border border-red-100">
                    If you believe this is an error, please contact your university's IT department or the platform administrator.
                </p>
            </div>
        </div>
    </div>
</template>
