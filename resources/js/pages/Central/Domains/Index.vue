<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { 
    Globe, 
    Building2, 
    ExternalLink,
    Search,
    Plus,
    Calendar,
    ArrowRight
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

defineProps<{
    domains: {
        data: any[];
        links: any[];
        meta: any;
    };
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Domain Management" />

    <CentralLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-3xl text-slate-900 tracking-tight">University Domains</h2>
                    <p class="text-slate-500 mt-1">Manage and monitor polytechnic URLs across the platform.</p>
                </div>
                <!-- Future action: Add Domain -->
                <button disabled title="Domains are currently managed per tenant" class="opacity-50 cursor-not-allowed bg-indigo-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 font-medium">
                    <Plus class="h-4 w-4" /> Register New Domain
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Stats / Quick Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card class="bg-indigo-50 border-indigo-100 shadow-none">
                        <CardContent class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-indigo-500 text-white rounded-xl">
                                    <Globe class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-indigo-600 uppercase tracking-wider">Total Domains</p>
                                    <h4 class="text-2xl font-bold text-indigo-900">{{ domains.data.length }}</h4>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Domains List -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="relative w-full md:w-96">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input placeholder="Filter domains..." class="pl-10 h-10" />
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Domain URL</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Institution</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-widest">Linked On</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-widest">Options</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="domain in domains.data" :key="domain.id" class="hover:bg-slate-50/50 transition cursor-default">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <Globe class="h-4 w-4 text-indigo-500" />
                                        <span class="text-sm font-bold text-slate-900">{{ domain.domain }}</span>
                                        <a :href="'http://' + domain.domain" target="_blank" class="text-slate-400 hover:text-indigo-600 transition">
                                            <ExternalLink class="h-3.5 w-3.5" />
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 bg-slate-100 rounded-lg flex items-center justify-center overflow-hidden border">
                                            <img v-if="domain.tenant?.logo_path" :src="'/storage/' + domain.tenant.logo_path" class="h-full w-full object-contain p-1" />
                                            <Building2 v-else class="h-4 w-4 text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ domain.tenant?.school_name || 'N/A' }}</p>
                                            <p class="text-[10px] text-slate-400 uppercase tracking-tight">ID: {{ domain.tenant_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <Calendar class="h-3.5 w-3.5" />
                                        {{ formatDate(domain.created_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('central.tenants.show', domain.tenant_id)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 justify-end">
                                        View Tenant <ArrowRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="domains.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 bg-slate-50/20">
                                    <Globe class="h-12 w-12 text-slate-200 mx-auto mb-4" />
                                    <p class="font-medium">No domains registered yet.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </CentralLayout>
</template>
